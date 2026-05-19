<?php
/**
 * Migration: Remove Duplicate Schedules
 * 
 * This script removes duplicate schedule entries and adds a unique constraint
 * to prevent future duplicates.
 */

$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'geamh_hris';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Starting duplicate schedule removal...\n\n";

// Count duplicates before removal
$result = $conn->query("
    SELECT employee_no, schedule_date, COUNT(*) as count
    FROM schedules
    GROUP BY employee_no, schedule_date
    HAVING count > 1
");

$duplicateCount = 0;
$duplicateGroups = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $duplicateCount += ($row['count'] - 1); // -1 because we keep one
        $duplicateGroups[] = $row;
    }
}

echo "Found " . count($duplicateGroups) . " groups with duplicates\n";
echo "Total duplicate entries to remove: $duplicateCount\n\n";

if ($duplicateCount > 0) {
    echo "Sample duplicates:\n";
    foreach (array_slice($duplicateGroups, 0, 5) as $dup) {
        echo "  - Employee: {$dup['employee_no']}, Date: {$dup['schedule_date']}, Count: {$dup['count']}\n";
    }
    echo "\n";
}

// Read and execute migration SQL
$sql = file_get_contents(__DIR__ . '/remove_duplicate_schedules.sql');

// Split by semicolon and execute each statement
$statements = array_filter(array_map('trim', explode(';', $sql)));

foreach ($statements as $statement) {
    if (empty($statement) || strpos($statement, '--') === 0) {
        continue;
    }
    
    echo "Executing: " . substr($statement, 0, 80) . "...\n";
    
    if (!$conn->query($statement)) {
        echo "ERROR: " . $conn->error . "\n";
        // Continue with other statements even if one fails
    } else {
        echo "✓ Success\n";
    }
}

// Count remaining schedules
$result = $conn->query("SELECT COUNT(*) as total FROM schedules");
$row = $result->fetch_assoc();
echo "\nTotal schedules after cleanup: " . $row['total'] . "\n";

// Verify no duplicates remain
$result = $conn->query("
    SELECT COUNT(*) as dup_count
    FROM (
        SELECT employee_no, schedule_date, COUNT(*) as count
        FROM schedules
        GROUP BY employee_no, schedule_date
        HAVING count > 1
    ) as duplicates
");
$row = $result->fetch_assoc();

if ($row['dup_count'] == 0) {
    echo "✓ No duplicates remaining\n";
} else {
    echo "⚠ Warning: " . $row['dup_count'] . " duplicate groups still exist\n";
}

echo "\nMigration completed!\n";

$conn->close();
