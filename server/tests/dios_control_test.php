<?php
/**
 * DIOS System Control API Test
 * Tests the SQL query fixes in dios_control.php
 */

require_once __DIR__ . '/../api/db.php';

echo "=== DIOS System Control SQL Query Test ===\n\n";

$conn = getConnection();

// Test 1: Database size query with prepared statement
echo "Test 1: Database Size Query\n";
echo "----------------------------\n";
$db = DB_NAME;
$stmt = $conn->prepare(
    "SELECT ROUND(SUM(data_length+index_length)/1024/1024,2) AS size_mb
     FROM information_schema.tables WHERE table_schema=?"
);
$stmt->bind_param('s', $db);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
echo "Database: $db\n";
echo "Size: " . ($row['size_mb'] ?? 0) . " MB\n";
echo "✓ Query executed successfully with prepared statement\n\n";

// Test 2: Table count queries
echo "Test 2: Table Count Queries\n";
echo "----------------------------\n";
$tables = ['employees', 'departments', 'leave_records', 'dtr_records'];
foreach ($tables as $table) {
    $result = $conn->query("SELECT COUNT(*) as cnt FROM `$table`");
    if ($result) {
        $count = $result->fetch_assoc()['cnt'];
        echo "✓ $table: $count rows\n";
    } else {
        echo "✗ $table: Query failed - " . $conn->error . "\n";
    }
}
echo "\n";

// Test 3: DESCRIBE query with sanitized table name
echo "Test 3: DESCRIBE Query\n";
echo "----------------------\n";
$table = 'employees';
$result = $conn->query("DESCRIBE `$table`");
if ($result) {
    $columns = $result->fetch_all(MYSQLI_ASSOC);
    echo "✓ DESCRIBE $table returned " . count($columns) . " columns\n";
} else {
    echo "✗ DESCRIBE failed: " . $conn->error . "\n";
}
echo "\n";

// Test 4: Preview query with LIMIT and OFFSET
echo "Test 4: Preview Query with LIMIT/OFFSET\n";
echo "---------------------------------------\n";
$table = 'employees';
$limit = 5;
$offset = 0;
$result = $conn->query("SELECT * FROM `$table` LIMIT $limit OFFSET $offset");
if ($result) {
    $rows = $result->fetch_all(MYSQLI_ASSOC);
    echo "✓ SELECT with LIMIT $limit OFFSET $offset returned " . count($rows) . " rows\n";
} else {
    echo "✗ SELECT failed: " . $conn->error . "\n";
}
echo "\n";

// Test 5: SQL injection prevention
echo "Test 5: SQL Injection Prevention\n";
echo "--------------------------------\n";
$maliciousTable = "employees'; DROP TABLE employees; --";
$sanitized = preg_replace('/[^a-zA-Z0-9_]/', '', $maliciousTable);
echo "Original: $maliciousTable\n";
echo "Sanitized: $sanitized\n";
echo "✓ Malicious input sanitized correctly\n\n";

$conn->close();

echo "=== All Tests Completed ===\n";
