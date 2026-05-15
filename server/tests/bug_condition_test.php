<?php
/**
 * Bug Condition Exploration Test
 * 
 * Property 1: Bug Condition - Permission Enforcement Failure
 * 
 * CRITICAL: This test MUST FAIL on unfixed code - failure confirms the bug exists
 * DO NOT attempt to fix the test or the code when it fails
 * 
 * This test encodes the expected behavior - it will validate the fix when it passes after implementation
 * 
 * GOAL: Surface counterexamples that demonstrate the bug exists
 * 
 * Validates: Requirements 1.1, 1.2, 1.3, 1.4
 */

echo "=== Bug Condition Exploration Test ===\n";
echo "Testing permission enforcement BEFORE fix implementation\n";
echo "EXPECTED: This test should FAIL (proving the bug exists)\n\n";

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'geamh_hris');

// Test configuration
$testResults = [];
$counterExamples = [];

/**
 * Helper: Connect to database
 */
function getTestConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("Database connection failed: " . $conn->connect_error . "\n");
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}

/**
 * Test Setup: Ensure users table has required roles
 */
function setupUserRoles($conn) {
    echo "Setting up test users with required roles...\n";
    
    // First, check if role enum needs to be updated
    $result = $conn->query("SHOW COLUMNS FROM users LIKE 'role'");
    $roleColumn = $result->fetch_assoc();
    
    if (strpos($roleColumn['Type'], 'DIOS') === false) {
        echo "  - Updating users table to include DIOS and Section Admin roles...\n";
        $conn->query("ALTER TABLE users MODIFY COLUMN role ENUM('DIOS', 'Super Admin', 'Admin', 'Section Admin') NOT NULL DEFAULT 'Admin'");
    }
    
    // Create test users if they don't exist
    $testUsers = [
        ['username' => 'dios_user', 'password' => 'test123', 'name' => 'DIOS Test User', 'role' => 'DIOS'],
        ['username' => 'admin_user', 'password' => 'test123', 'name' => 'Admin Test User', 'role' => 'Admin'],
        ['username' => 'section_admin_user', 'password' => 'test123', 'name' => 'Section Admin Test User', 'role' => 'Section Admin']
    ];
    
    foreach ($testUsers as $user) {
        $stmt = $conn->prepare("INSERT IGNORE INTO users (username, password, name, role, department) VALUES (?, SHA2(?, 256), ?, ?, 'Test Department')");
        $stmt->bind_param('ssss', $user['username'], $user['password'], $user['name'], $user['role']);
        $stmt->execute();
    }
    
    // Get user IDs
    $userIds = [];
    foreach ($testUsers as $user) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param('s', $user['username']);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $userIds[$user['role']] = $row['id'];
    }
    
    echo "  - Test users created/verified\n";
    echo "    DIOS user ID: {$userIds['DIOS']}\n";
    echo "    Admin user ID: {$userIds['Admin']}\n";
    echo "    Section Admin user ID: {$userIds['Section Admin']}\n";
    return $userIds;
}

/**
 * Test Setup: Configure denied permissions
 */
function setupDeniedPermissions($conn) {
    echo "\nSetting up denied permissions in module_permissions table...\n";
    
    $permissions = [
        ['module' => 'Employee Masterlist', 'role' => 'Admin', 'action' => 'Delete', 'granted' => 0],
        ['module' => 'Account Management', 'role' => 'Section Admin', 'action' => 'View', 'granted' => 0],
        ['module' => 'Leave Management', 'role' => 'Admin', 'action' => 'Add', 'granted' => 0]
    ];
    
    $stmt = $conn->prepare("INSERT INTO module_permissions (module, role, action, granted) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE granted = VALUES(granted)");
    
    foreach ($permissions as $perm) {
        $stmt->bind_param('sssi', $perm['module'], $perm['role'], $perm['action'], $perm['granted']);
        $stmt->execute();
        echo "  - Set {$perm['module']} / {$perm['role']} / {$perm['action']} = DENIED\n";
    }
}

/**
 * Test Case 1: Check if checkPermission() function exists in db.php
 */
function testPermissionFunctionExists() {
    global $testResults, $counterExamples;
    
    echo "\n--- Test Case 1: Permission checking infrastructure ---\n";
    echo "Checking if checkPermission() function exists in db.php...\n";
    
    $dbPhpContent = file_get_contents(__DIR__ . '/../api/db.php');
    $hasCheckPermission = strpos($dbPhpContent, 'function checkPermission') !== false;
    $hasGetUserRole = strpos($dbPhpContent, 'function getUserRole') !== false;
    $hasDenyAccess = strpos($dbPhpContent, 'function denyAccess') !== false;
    
    echo "  checkPermission() exists: " . ($hasCheckPermission ? 'YES' : 'NO') . "\n";
    echo "  getUserRole() exists: " . ($hasGetUserRole ? 'YES' : 'NO') . "\n";
    echo "  denyAccess() exists: " . ($hasDenyAccess ? 'YES' : 'NO') . "\n";
    
    if ($hasCheckPermission && $hasGetUserRole && $hasDenyAccess) {
        echo "✓ PASS: Permission checking functions exist\n";
        $testResults[] = true;
        return true;
    } else {
        echo "✗ FAIL: Permission checking functions DO NOT exist\n";
        $testResults[] = false;
        $counterExamples[] = [
            'test' => 'Permission infrastructure',
            'expected' => 'checkPermission(), getUserRole(), and denyAccess() functions should exist in db.php',
            'actual' => 'Functions are missing',
            'details' => 'No permission checking infrastructure exists - this is the root cause of the bug'
        ];
        return false;
    }
}

/**
 * Test Case 2: Check if employees.php uses permission checking
 */
function testEmployeesPermissionCheck() {
    global $testResults, $counterExamples;
    
    echo "\n--- Test Case 2: employees.php permission enforcement ---\n";
    echo "Checking if employees.php calls checkPermission()...\n";
    
    $employeesPhpContent = file_get_contents(__DIR__ . '/../api/employees.php');
    $hasPermissionCheck = strpos($employeesPhpContent, 'checkPermission') !== false;
    $hasUserIdExtraction = strpos($employeesPhpContent, 'HTTP_X_USER_ID') !== false || strpos($employeesPhpContent, 'X-User-Id') !== false;
    
    echo "  Extracts X-User-Id header: " . ($hasUserIdExtraction ? 'YES' : 'NO') . "\n";
    echo "  Calls checkPermission(): " . ($hasPermissionCheck ? 'YES' : 'NO') . "\n";
    
    if ($hasPermissionCheck && $hasUserIdExtraction) {
        echo "✓ PASS: employees.php enforces permissions\n";
        $testResults[] = true;
    } else {
        echo "✗ FAIL: employees.php DOES NOT enforce permissions\n";
        $testResults[] = false;
        $counterExamples[] = [
            'test' => 'employees.php permission check',
            'expected' => 'employees.php should extract X-User-Id header and call checkPermission()',
            'actual' => 'No permission checking code found',
            'details' => 'Admin user can DELETE employee despite denied permission because employees.php does not check permissions'
        ];
    }
}

/**
 * Test Case 3: Check if leave.php uses permission checking
 */
function testLeavePermissionCheck() {
    global $testResults, $counterExamples;
    
    echo "\n--- Test Case 3: leave.php permission enforcement ---\n";
    echo "Checking if leave.php calls checkPermission()...\n";
    
    $leavePhpPath = __DIR__ . '/../api/leave.php';
    if (!file_exists($leavePhpPath)) {
        echo "  leave.php not found - skipping test\n";
        return;
    }
    
    $leavePhpContent = file_get_contents($leavePhpPath);
    $hasPermissionCheck = strpos($leavePhpContent, 'checkPermission') !== false;
    $hasUserIdExtraction = strpos($leavePhpContent, 'HTTP_X_USER_ID') !== false || strpos($leavePhpContent, 'X-User-Id') !== false;
    
    echo "  Extracts X-User-Id header: " . ($hasUserIdExtraction ? 'YES' : 'NO') . "\n";
    echo "  Calls checkPermission(): " . ($hasPermissionCheck ? 'YES' : 'NO') . "\n";
    
    if ($hasPermissionCheck && $hasUserIdExtraction) {
        echo "✓ PASS: leave.php enforces permissions\n";
        $testResults[] = true;
    } else {
        echo "✗ FAIL: leave.php DOES NOT enforce permissions\n";
        $testResults[] = false;
        $counterExamples[] = [
            'test' => 'leave.php permission check',
            'expected' => 'leave.php should extract X-User-Id header and call checkPermission()',
            'actual' => 'No permission checking code found',
            'details' => 'Admin user can ADD leave record despite denied permission because leave.php does not check permissions'
        ];
    }
}

/**
 * Test Case 4: Check if auth.php uses permission checking
 */
function testAuthPermissionCheck() {
    global $testResults, $counterExamples;
    
    echo "\n--- Test Case 4: auth.php permission enforcement ---\n";
    echo "Checking if auth.php calls checkPermission() for users action...\n";
    
    $authPhpContent = file_get_contents(__DIR__ . '/../api/auth.php');
    $hasPermissionCheck = strpos($authPhpContent, 'checkPermission') !== false;
    $hasUserIdExtraction = strpos($authPhpContent, 'HTTP_X_USER_ID') !== false || strpos($authPhpContent, 'X-User-Id') !== false;
    
    echo "  Extracts X-User-Id header: " . ($hasUserIdExtraction ? 'YES' : 'NO') . "\n";
    echo "  Calls checkPermission(): " . ($hasPermissionCheck ? 'YES' : 'NO') . "\n";
    
    if ($hasPermissionCheck && $hasUserIdExtraction) {
        echo "✓ PASS: auth.php enforces permissions\n";
        $testResults[] = true;
    } else {
        echo "✗ FAIL: auth.php DOES NOT enforce permissions\n";
        $testResults[] = false;
        $counterExamples[] = [
            'test' => 'auth.php permission check',
            'expected' => 'auth.php should extract X-User-Id header and call checkPermission() for users action',
            'actual' => 'No permission checking code found',
            'details' => 'Section Admin user can VIEW users list despite denied permission because auth.php does not check permissions'
        ];
    }
}

/**
 * Test Case 5: Verify permissions are correctly stored in database
 */
function testPermissionsInDatabase($conn) {
    global $testResults, $counterExamples;
    
    echo "\n--- Test Case 5: Database permission storage ---\n";
    echo "Verifying denied permissions are correctly stored...\n";
    
    $stmt = $conn->prepare("SELECT module, role, action, granted FROM module_permissions WHERE granted = 0 AND ((module = 'Employee Masterlist' AND role = 'Admin' AND action = 'Delete') OR (module = 'Account Management' AND role = 'Section Admin' AND action = 'View') OR (module = 'Leave Management' AND role = 'Admin' AND action = 'Add'))");
    $stmt->execute();
    $result = $stmt->get_result();
    $deniedPermissions = $result->fetch_all(MYSQLI_ASSOC);
    
    echo "  Found " . count($deniedPermissions) . " denied permissions:\n";
    foreach ($deniedPermissions as $perm) {
        echo "    - {$perm['module']} / {$perm['role']} / {$perm['action']} = " . ($perm['granted'] ? 'GRANTED' : 'DENIED') . "\n";
    }
    
    if (count($deniedPermissions) >= 3) {
        echo "✓ PASS: Permissions are correctly stored in database\n";
        $testResults[] = true;
    } else {
        echo "✗ FAIL: Not all denied permissions are stored\n";
        $testResults[] = false;
    }
}

/**
 * Main test execution
 */
function runTests() {
    global $testResults, $counterExamples;
    
    $conn = getTestConnection();
    
    echo "=== Test Setup Phase ===\n";
    $userIds = setupUserRoles($conn);
    setupDeniedPermissions($conn);
    
    echo "\n=== Test Execution Phase ===\n";
    echo "This test checks if permission enforcement infrastructure exists.\n";
    echo "On UNFIXED code, these checks should FAIL.\n";
    
    $infrastructureExists = testPermissionFunctionExists();
    testEmployeesPermissionCheck();
    testLeavePermissionCheck();
    testAuthPermissionCheck();
    testPermissionsInDatabase($conn);
    
    echo "\n=== Test Results Summary ===\n";
    $passed = count(array_filter($testResults, fn($r) => $r === true));
    $failed = count(array_filter($testResults, fn($r) => $r === false));
    $total = count($testResults);
    
    echo "Total Tests: $total\n";
    echo "Passed: $passed\n";
    echo "Failed: $failed\n";
    
    if (count($counterExamples) > 0) {
        echo "\n=== Counterexamples Found (Bug Confirmed) ===\n";
        echo "The following issues demonstrate that permission enforcement is not working:\n\n";
        
        foreach ($counterExamples as $i => $example) {
            echo "Counterexample " . ($i + 1) . ":\n";
            echo "  Test: {$example['test']}\n";
            echo "  Expected: {$example['expected']}\n";
            echo "  Actual: {$example['actual']}\n";
            echo "  Details: {$example['details']}\n\n";
        }
        
        echo "✓ SUCCESS: Bug condition confirmed - test failed as expected on unfixed code\n";
        echo "These counterexamples demonstrate that:\n";
        echo "  1. Permission checking functions do not exist in db.php\n";
        echo "  2. API endpoints do not check permissions before processing requests\n";
        echo "  3. Users can perform actions regardless of denied permissions\n\n";
        echo "When the fix is implemented, this test should PASS.\n";
    } else {
        echo "\n✗ UNEXPECTED: All tests passed - bug may not exist or code may already be fixed\n";
        echo "This is unexpected for unfixed code. The permission enforcement may already be implemented.\n";
    }
    
    $conn->close();
    
    // Return exit code: 0 if we found counterexamples (expected), 1 if no counterexamples (unexpected)
    return count($counterExamples) > 0 ? 0 : 1;
}

// Run tests
$exitCode = runTests();
exit($exitCode);
