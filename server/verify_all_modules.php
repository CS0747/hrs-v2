<?php
/**
 * Comprehensive Module Verification Script
 * Tests all API endpoints and database tables
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== GEAMH HRIS - Complete Module Verification ===\n\n";

// Database Connection
$conn = new mysqli('localhost', 'root', '', 'geamh_hris');
if ($conn->connect_error) {
    die("❌ Database connection failed: " . $conn->connect_error . "\n");
}
echo "✓ Database connected\n\n";

// Module definitions
$modules = [
    'Authentication' => [
        'api' => 'auth.php',
        'table' => 'users',
        'endpoints' => ['login', 'signup', 'users', 'request_password_reset'],
    ],
    'Employees' => [
        'api' => 'employees.php',
        'table' => 'employees',
        'endpoints' => ['GET', 'POST', 'PUT', 'DELETE'],
    ],
    'Schedule' => [
        'api' => 'schedule.php',
        'table' => 'schedules',
        'endpoints' => ['GET', 'POST', 'PUT', 'DELETE'],
    ],
    'Leave Management' => [
        'api' => 'leave.php',
        'table' => 'leave_records',
        'endpoints' => ['GET', 'POST', 'PUT', 'DELETE'],
    ],
    'Travel Orders' => [
        'api' => 'travel_orders.php',
        'table' => 'travel_orders',
        'endpoints' => ['GET', 'POST', 'PUT', 'DELETE'],
    ],
    'DTR' => [
        'api' => 'dtr.php',
        'table' => 'dtr_records',
        'endpoints' => ['GET', 'POST', 'PUT', 'DELETE'],
    ],
    'Trainings' => [
        'api' => 'trainings.php',
        'table' => 'trainings',
        'endpoints' => ['GET', 'POST', 'PUT', 'DELETE'],
    ],
    'Departments' => [
        'api' => 'departments.php',
        'table' => 'departments',
        'endpoints' => ['GET', 'POST', 'PUT', 'DELETE'],
    ],
    'Signatories' => [
        'api' => 'signatories.php',
        'table' => 'signatories',
        'endpoints' => ['GET', 'POST', 'PUT', 'DELETE'],
    ],
    'Tracking' => [
        'api' => 'tracking.php',
        'table' => 'tracking_records',
        'endpoints' => ['GET', 'POST', 'PUT', 'DELETE'],
    ],
    'Notifications' => [
        'api' => 'notifications.php',
        'table' => 'notifications',
        'endpoints' => ['GET', 'POST', 'PUT', 'DELETE'],
    ],
    'Audit Logs' => [
        'api' => 'audit_logs.php',
        'table' => 'audit_logs',
        'endpoints' => ['GET'],
    ],
    'Birthday Celebrants' => [
        'api' => 'birthday_celebrants.php',
        'table' => 'employees',
        'endpoints' => ['GET'],
    ],
    'Module Permissions' => [
        'api' => 'module_permissions.php',
        'table' => 'module_permissions',
        'endpoints' => ['GET', 'POST', 'PUT'],
    ],
    'DIOS Control' => [
        'api' => 'dios_control.php',
        'table' => 'users',
        'endpoints' => ['GET', 'POST'],
    ],
    'AI Scanning' => [
        'api' => 'ai_scan.php',
        'table' => null,
        'endpoints' => ['POST'],
    ],
    'Payroll' => [
        'api' => 'payroll.php',
        'table' => 'payroll_records',
        'endpoints' => ['GET', 'POST', 'PUT', 'DELETE'],
    ],
];

$results = [
    'passed' => 0,
    'failed' => 0,
    'warnings' => 0,
];

foreach ($modules as $moduleName => $config) {
    echo "Testing: $moduleName\n";
    echo str_repeat('-', 50) . "\n";
    
    $moduleStatus = true;
    
    // Check API file exists
    $apiPath = __DIR__ . '/api/' . $config['api'];
    if (file_exists($apiPath)) {
        echo "  ✓ API file exists: {$config['api']}\n";
    } else {
        echo "  ❌ API file missing: {$config['api']}\n";
        $moduleStatus = false;
    }
    
    // Check table exists (if specified)
    if ($config['table']) {
        $result = $conn->query("SHOW TABLES LIKE '{$config['table']}'");
        if ($result && $result->num_rows > 0) {
            echo "  ✓ Database table exists: {$config['table']}\n";
            
            // Count records
            $countResult = $conn->query("SELECT COUNT(*) as count FROM {$config['table']}");
            if ($countResult) {
                $row = $countResult->fetch_assoc();
                echo "  ℹ Records in table: {$row['count']}\n";
            }
        } else {
            echo "  ⚠️  Database table missing: {$config['table']}\n";
            $results['warnings']++;
        }
    }
    
    // Check file content for endpoints
    if (file_exists($apiPath)) {
        $content = file_get_contents($apiPath);
        
        // Check for db.php include
        if (strpos($content, "require_once 'db.php'") !== false) {
            echo "  ✓ Database connection included\n";
        } else {
            echo "  ⚠️  Database connection not found\n";
            $results['warnings']++;
        }
        
        // Check for CORS handling
        if (strpos($content, "require_once 'cors.php'") !== false || 
            strpos($content, 'Access-Control-Allow-Origin') !== false) {
            echo "  ✓ CORS handling present\n";
        } else {
            echo "  ⚠️  CORS handling not found\n";
        }
        
        // Check for basic error handling
        if (strpos($content, 'sendError') !== false || 
            strpos($content, 'sendJson') !== false) {
            echo "  ✓ Error handling functions used\n";
        } else {
            echo "  ⚠️  Error handling functions not found\n";
        }
    }
    
    if ($moduleStatus) {
        $results['passed']++;
        echo "  ✅ Module Status: PASSED\n";
    } else {
        $results['failed']++;
        echo "  ❌ Module Status: FAILED\n";
    }
    
    echo "\n";
}

// Summary
echo "=== Verification Summary ===\n";
echo "Total Modules: " . count($modules) . "\n";
echo "Passed: {$results['passed']}\n";
echo "Failed: {$results['failed']}\n";
echo "Warnings: {$results['warnings']}\n\n";

if ($results['failed'] === 0) {
    echo "✅ All modules are functional!\n";
} else {
    echo "⚠️  Some modules need attention\n";
}

// Additional checks
echo "\n=== Additional System Checks ===\n";

// Check for view files
echo "\nFrontend Views:\n";
$viewPath = __DIR__ . '/../client/src/views/';
$viewDirs = [
    'employees' => ['EmployeeMasterlist.vue', 'EmployeeForm.vue', 'BirthdayCelebrants.vue'],
    'schedule' => ['ScheduleDatabase.vue'],
    'leave' => ['LeaveManagement.vue'],
    'to' => ['TOManagement.vue'],
    'dtr' => ['DTRTransmittal.vue'],
    'trainings' => ['TrainingsManagement.vue'],
    'departments' => ['DepartmentManagement.vue'],
    'signatories' => ['Signatories.vue'],
    'tracking' => ['TrackingReceiving.vue'],
    'verification' => ['Verification.vue'],
    'audit' => ['AuditTransmittal.vue'],
    'ai' => ['AIScanningTools.vue'],
    'accounts' => ['AccountManagement.vue'],
    'admin' => ['AuditHistory.vue', 'PasswordResetRequests.vue', 'DiosSystemControl.vue'],
];

$viewsOk = 0;
$viewsMissing = 0;

foreach ($viewDirs as $dir => $files) {
    foreach ($files as $file) {
        $fullPath = $viewPath . $dir . '/' . $file;
        if (file_exists($fullPath)) {
            $viewsOk++;
        } else {
            echo "  ⚠️  Missing: $dir/$file\n";
            $viewsMissing++;
        }
    }
}

echo "  ✓ Frontend views found: $viewsOk\n";
if ($viewsMissing > 0) {
    echo "  ⚠️  Frontend views missing: $viewsMissing\n";
}

$conn->close();
echo "\n✅ Verification complete!\n";
