<?php
require_once 'api/db.php';

$conn = getConnection();

// Check Herbert Lugay's user record
$stmt = $conn->prepare("SELECT id, username, name, role, department, position FROM users WHERE name LIKE '%Herbert%' OR name LIKE '%Lugay%'");
$stmt->execute();
$result = $stmt->get_result();

echo "<h2>Users matching 'Herbert' or 'Lugay':</h2>";
echo "<pre>";
while ($row = $result->fetch_assoc()) {
    print_r($row);
}
echo "</pre>";

// Check if there's a matching employee record
$stmt2 = $conn->prepare("SELECT id, employee_no, last_name, first_name, position, department FROM employees WHERE last_name LIKE '%Lugay%' OR first_name LIKE '%Herbert%'");
$stmt2->execute();
$result2 = $stmt2->get_result();

echo "<h2>Employees matching 'Herbert' or 'Lugay':</h2>";
echo "<pre>";
while ($row = $result2->fetch_assoc()) {
    print_r($row);
}
echo "</pre>";

$conn->close();
?>
