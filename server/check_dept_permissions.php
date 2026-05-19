<?php
require_once 'api/db.php';

$conn = getConnection();
$result = $conn->query("SELECT * FROM module_permissions WHERE module = 'Departments'");

echo "Departments module permissions: " . $result->num_rows . " rows found\n\n";

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        print_r($row);
    }
} else {
    echo "No permissions found for Departments module!\n";
    echo "Adding default permissions...\n\n";
    
    // Add default permissions for Departments module
    $roles = ['Super Admin', 'Admin', 'DIOS'];
    $actions = ['View', 'Add', 'Edit', 'Delete'];
    
    foreach ($roles as $role) {
        foreach ($actions as $action) {
            $stmt = $conn->prepare("INSERT INTO module_permissions (module, role, action) VALUES ('Departments', ?, ?)");
            $stmt->bind_param('ss', $role, $action);
            $stmt->execute();
            echo "Added: Departments - $role - $action\n";
        }
    }
    
    echo "\nPermissions added successfully!\n";
}

$conn->close();
