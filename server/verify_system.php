<?php
/**
 * System Verification Script
 * Checks database tables, connections, and critical functionality
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== GEAMH HRIS System Verification ===\n\n";

// 1. Database Connection
echo "1. Testing Database Connection...\n";
$conn = new mysqli('localhost', 'root', '', 'geamh_hris');

if ($conn->connect_error) {
    die("   ❌ FAILED: " . $conn->connect_error . "\n");
}
echo "   ✓ Database connected successfully\n\n";

// 2. Check Required Tables
echo "2. Checking Required Tables...\n";
$requiredTables = [
    'users',
    'employees',
    'notifications',
    'password_reset_requests',
    'module_permissions',
    'audit_logs',
    'schedules',
    'leave_records',
    'travel_orders',
    'trainings',
    'departments',
    'signatories',
];

$existingTables = [];
$result = $conn->query("SHOW TABLES");
while ($row = $result->fetch_array()) {
    $existingTables[] = $row[0];
}

$missingTables = [];
foreach ($requiredTables as $table) {
    if (in_array($table, $existingTables)) {
        echo "   ✓ $table\n";
    } else {
        echo "   ❌ $table (MISSING)\n";
        $missingTables[] = $table;
    }
}

if (count($missingTables) > 0) {
    echo "\n   ⚠️  Missing tables: " . implode(', ', $missingTables) . "\n";
} else {
    echo "\n   ✓ All required tables exist\n";
}

// 3. Check Users Table Structure
echo "\n3. Verifying Users Table Structure...\n";
$result = $conn->query("DESCRIBE users");
$userColumns = [];
while ($row = $result->fetch_assoc()) {
    $userColumns[] = $row['Field'];
}

$requiredUserColumns = ['id', 'username', 'password', 'name', 'role', 'department', 'active', 'created_at'];
$missingColumns = array_diff($requiredUserColumns, $userColumns);

if (count($missingColumns) > 0) {
    echo "   ❌ Missing columns: " . implode(', ', $missingColumns) . "\n";
} else {
    echo "   ✓ All required columns exist\n";
}

// 4. Check Notifications Table
echo "\n4. Verifying Notifications Table...\n";
if (in_array('notifications', $existingTables)) {
    $result = $conn->query("DESCRIBE notifications");
    $notifColumns = [];
    while ($row = $result->fetch_assoc()) {
        $notifColumns[] = $row['Field'];
    }
    
    $requiredNotifColumns = ['id', 'user_id', 'type', 'title', 'message', 'is_read', 'created_at'];
    $missingNotifColumns = array_diff($requiredNotifColumns, $notifColumns);
    
    if (count($missingNotifColumns) > 0) {
        echo "   ❌ Missing columns: " . implode(', ', $missingNotifColumns) . "\n";
    } else {
        echo "   ✓ All required columns exist\n";
    }
} else {
    echo "   ❌ Notifications table does not exist\n";
}

// 5. Check Password Reset Requests Table
echo "\n5. Verifying Password Reset Requests Table...\n";
if (in_array('password_reset_requests', $existingTables)) {
    $result = $conn->query("DESCRIBE password_reset_requests");
    $resetColumns = [];
    while ($row = $result->fetch_assoc()) {
        $resetColumns[] = $row['Field'];
    }
    
    $requiredResetColumns = ['id', 'user_id', 'username', 'user_name', 'status', 'requested_at'];
    $missingResetColumns = array_diff($requiredResetColumns, $resetColumns);
    
    if (count($missingResetColumns) > 0) {
        echo "   ❌ Missing columns: " . implode(', ', $missingResetColumns) . "\n";
    } else {
        echo "   ✓ All required columns exist\n";
    }
} else {
    echo "   ❌ Password reset requests table does not exist\n";
}

// 6. Check for DIOS User
echo "\n6. Checking for DIOS User...\n";
$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'DIOS' AND active = 1");
$row = $result->fetch_assoc();
if ($row['count'] > 0) {
    echo "   ✓ DIOS user(s) found: " . $row['count'] . "\n";
} else {
    echo "   ⚠️  No active DIOS users found\n";
}

// 7. Check Total Users
echo "\n7. Checking User Accounts...\n";
$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE active = 1");
$row = $result->fetch_assoc();
echo "   ✓ Active users: " . $row['count'] . "\n";

// 8. Check API Files
echo "\n8. Verifying API Files...\n";
$apiFiles = [
    'auth.php',
    'db.php',
    'cors.php',
    'notifications.php',
    'notification_helpers.php',
    'employees.php',
    'schedule.php',
    'leave.php',
    'travel_orders.php',
    'trainings.php',
    'departments.php',
];

$apiPath = __DIR__ . '/api/';
foreach ($apiFiles as $file) {
    if (file_exists($apiPath . $file)) {
        echo "   ✓ $file\n";
    } else {
        echo "   ❌ $file (MISSING)\n";
    }
}

// 9. Test Database Query Performance
echo "\n9. Testing Database Performance...\n";
$start = microtime(true);
$conn->query("SELECT COUNT(*) FROM users");
$duration = (microtime(true) - $start) * 1000;
echo "   ✓ Query executed in " . number_format($duration, 2) . "ms\n";

// 10. Summary
echo "\n=== Verification Summary ===\n";
if (count($missingTables) === 0 && count($missingColumns) === 0) {
    echo "✓ System is HEALTHY and ready for use\n";
} else {
    echo "⚠️  System has issues that need attention\n";
    if (count($missingTables) > 0) {
        echo "   - Missing tables: " . implode(', ', $missingTables) . "\n";
    }
    if (count($missingColumns) > 0) {
        echo "   - Missing columns in users table\n";
    }
}

$conn->close();
echo "\nVerification complete!\n";
