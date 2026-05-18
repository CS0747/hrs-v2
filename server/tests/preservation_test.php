<?php
/**
 * Preservation Property Tests
 * 
 * Property 2: Preservation - DIOS Unrestricted Access and Granted Permissions
 * 
 * IMPORTANT: These tests should PASS on UNFIXED code (confirming baseline behavior to preserve)
 * 
 * This test follows observation-first methodology:
 * 1. Observe behavior on unfixed code
 * 2. Write tests that capture that behavior
 * 3. Verify tests pass on unfixed code
 * 4. After fix, verify tests still pass (no regressions)
 * 
 * Validates: Requirements 3.1, 3.2, 3.3, 3.4, 3.5
 */

echo "=== Preservation Property Tests ===\n";
echo "Testing baseline behavior that must be preserved AFTER fix implementation\n";
echo "EXPECTED: These tests should PASS on unfixed code\n\n";

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'geamh_hris');

// Test configuration
$testResults = [];
$preservationIssues = [];

/**
 * Helper: Connect to database
 */
function getTestConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("Database connection failed: " . $conn->connect_error);
    }
    return $conn;
}

/**
 * Helper: Record test result
 */
function recordTest($name, $passed, $message = '') {
    global $testResults, $preservationIssues;
    $testResults[] = [
        'name' => $name,
        'passed' => $passed,
        'message' => $message
    ];
    
    if (!$passed) {
        $preservationIssues[] = $name;
    }
    
    $status = $passed ? '✓ PASS' : '✗ FAIL';
    $color = $passed ? "\033[32m" : "\033[31m";
    $reset = "\033[0m";
    
    echo "{$color}{$status}{$reset} - {$name}\n";
    if ($message) {
        echo "  → {$message}\n";
    }
}

/**
 * Test 1: DIOS user has unrestricted access (no permission checks)
 * Requirement 3.1: DIOS users bypass all permission checks
 */
function testDiosUnrestrictedAccess() {
    $conn = getTestConnection();
    
    // Get DIOS user
    $result = $conn->query("SELECT id FROM users WHERE role = 'DIOS' LIMIT 1");
    if (!$result || $result->num_rows === 0) {
        recordTest('DIOS Unrestricted Access', false, 'No DIOS user found in database');
        return;
    }
    
    $diosUser = $result->fetch_assoc();
    $diosUserId = $diosUser['id'];
    
    // Check that DIOS user has NO entries in module_permissions table
    $permResult = $conn->query("SELECT COUNT(*) as count FROM module_permissions WHERE user_id = {$diosUserId}");
    $permCount = $permResult->fetch_assoc()['count'];
    
    $passed = ($permCount == 0);
    $message = $passed 
        ? "DIOS user has no permission entries (unrestricted access confirmed)"
        : "DIOS user has {$permCount} permission entries (should have 0 for unrestricted access)";
    
    recordTest('DIOS Unrestricted Access', $passed, $message);
    $conn->close();
}

/**
 * Test 2: Non-DIOS users with granted permissions can access modules
 * Requirement 3.2: Users with explicit permissions maintain access
 */
function testGrantedPermissionsPreserved() {
    $conn = getTestConnection();
    
    // Get a non-DIOS user with permissions
    $result = $conn->query("
        SELECT u.id, u.name, u.role, COUNT(mp.id) as perm_count
        FROM users u
        LEFT JOIN module_permissions mp ON u.id = mp.user_id
        WHERE u.role != 'DIOS'
        GROUP BY u.id
        HAVING perm_count > 0
        LIMIT 1
    ");
    
    if (!$result || $result->num_rows === 0) {
        recordTest('Granted Permissions Preserved', false, 'No non-DIOS users with permissions found');
        return;
    }
    
    $user = $result->fetch_assoc();
    $userId = $user['id'];
    $permCount = $user['perm_count'];
    
    // Verify permissions exist and are accessible
    $permResult = $conn->query("
        SELECT module_name, can_view, can_add, can_edit, can_delete
        FROM module_permissions
        WHERE user_id = {$userId}
        LIMIT 5
    ");
    
    $permissions = [];
    while ($row = $permResult->fetch_assoc()) {
        $permissions[] = $row['module_name'];
    }
    
    $passed = (count($permissions) > 0);
    $message = $passed
        ? "User '{$user['name']}' has {$permCount} permissions: " . implode(', ', array_slice($permissions, 0, 3))
        : "User has no accessible permissions";
    
    recordTest('Granted Permissions Preserved', $passed, $message);
    $conn->close();
}

/**
 * Test 3: Permission table structure is intact
 * Requirement 3.3: Database schema remains unchanged
 */
function testPermissionTableStructure() {
    $conn = getTestConnection();
    
    // Check table exists
    $result = $conn->query("SHOW TABLES LIKE 'module_permissions'");
    if (!$result || $result->num_rows === 0) {
        recordTest('Permission Table Structure', false, 'module_permissions table does not exist');
        return;
    }
    
    // Check required columns
    $requiredColumns = ['id', 'user_id', 'module_name', 'can_view', 'can_add', 'can_edit', 'can_delete'];
    $result = $conn->query("DESCRIBE module_permissions");
    
    $existingColumns = [];
    while ($row = $result->fetch_assoc()) {
        $existingColumns[] = $row['Field'];
    }
    
    $missingColumns = array_diff($requiredColumns, $existingColumns);
    $passed = (count($missingColumns) === 0);
    
    $message = $passed
        ? "All required columns present: " . implode(', ', $requiredColumns)
        : "Missing columns: " . implode(', ', $missingColumns);
    
    recordTest('Permission Table Structure', $passed, $message);
    $conn->close();
}

/**
 * Test 4: Users table has role column
 * Requirement 3.4: User role identification mechanism intact
 */
function testUserRoleColumn() {
    $conn = getTestConnection();
    
    // Check role column exists
    $result = $conn->query("DESCRIBE users");
    $columns = [];
    while ($row = $result->fetch_assoc()) {
        $columns[] = $row['Field'];
    }
    
    $hasRoleColumn = in_array('role', $columns);
    
    if (!$hasRoleColumn) {
        recordTest('User Role Column', false, 'role column missing from users table');
        return;
    }
    
    // Check DIOS role exists
    $diosResult = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'DIOS'");
    $diosCount = $diosResult->fetch_assoc()['count'];
    
    $passed = ($diosCount > 0);
    $message = $passed
        ? "Role column exists, {$diosCount} DIOS user(s) found"
        : "Role column exists but no DIOS users found";
    
    recordTest('User Role Column', $passed, $message);
    $conn->close();
}

/**
 * Test 5: Permission data integrity
 * Requirement 3.5: Existing permission data is valid
 */
function testPermissionDataIntegrity() {
    $conn = getTestConnection();
    
    // Check for orphaned permissions (user_id doesn't exist in users table)
    $result = $conn->query("
        SELECT COUNT(*) as count
        FROM module_permissions mp
        LEFT JOIN users u ON mp.user_id = u.id
        WHERE u.id IS NULL
    ");
    
    $orphanedCount = $result->fetch_assoc()['count'];
    
    // Check for invalid boolean values
    $invalidResult = $conn->query("
        SELECT COUNT(*) as count
        FROM module_permissions
        WHERE can_view NOT IN (0, 1)
           OR can_add NOT IN (0, 1)
           OR can_edit NOT IN (0, 1)
           OR can_delete NOT IN (0, 1)
    ");
    
    $invalidCount = $invalidResult->fetch_assoc()['count'];
    
    $passed = ($orphanedCount === 0 && $invalidCount === 0);
    
    $issues = [];
    if ($orphanedCount > 0) $issues[] = "{$orphanedCount} orphaned permissions";
    if ($invalidCount > 0) $issues[] = "{$invalidCount} invalid boolean values";
    
    $message = $passed
        ? "All permission data is valid and properly linked"
        : "Data integrity issues: " . implode(', ', $issues);
    
    recordTest('Permission Data Integrity', $passed, $message);
    $conn->close();
}

// ═══════════════════════════════════════════════════════════════════════════
// Run all preservation tests
// ═══════════════════════════════════════════════════════════════════════════

echo "\n--- Running Preservation Tests ---\n\n";

testDiosUnrestrictedAccess();
testGrantedPermissionsPreserved();
testPermissionTableStructure();
testUserRoleColumn();
testPermissionDataIntegrity();

// ═══════════════════════════════════════════════════════════════════════════
// Summary
// ═══════════════════════════════════════════════════════════════════════════

echo "\n=== Test Summary ===\n";

$totalTests = count($testResults);
$passedTests = count(array_filter($testResults, fn($t) => $t['passed']));
$failedTests = $totalTests - $passedTests;

echo "Total Tests: {$totalTests}\n";
echo "\033[32mPassed: {$passedTests}\033[0m\n";

if ($failedTests > 0) {
    echo "\033[31mFailed: {$failedTests}\033[0m\n";
    echo "\nPreservation Issues Found:\n";
    foreach ($preservationIssues as $issue) {
        echo "  • {$issue}\n";
    }
    echo "\n\033[33mWARNING: These baseline behaviors must be preserved after fix!\033[0m\n";
    exit(1);
} else {
    echo "\n\033[32m✓ All preservation tests passed!\033[0m\n";
    echo "Baseline behavior confirmed. These must remain working after fix.\n";
    exit(0);
}
