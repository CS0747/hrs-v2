<?php
require_once 'api/db.php';

$conn = getConnection();

echo "<h2>Syncing User Positions from Employee Masterlist</h2>";

// Get all users
$users = $conn->query("SELECT id, name, position FROM users WHERE active = 1");

$updated = 0;
$skipped = 0;

while ($user = $users->fetch_assoc()) {
    // Try to find matching employee by name
    // User name format: "Last Name, First Name"
    $nameParts = explode(',', $user['name']);
    if (count($nameParts) === 2) {
        $lastName = trim($nameParts[0]);
        $firstName = trim($nameParts[1]);
        
        // Find employee
        $stmt = $conn->prepare("SELECT position FROM employees WHERE last_name = ? AND first_name = ? LIMIT 1");
        $stmt->bind_param('ss', $lastName, $firstName);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($emp = $result->fetch_assoc()) {
            if ($emp['position'] && $emp['position'] !== $user['position']) {
                // Update user position
                $updateStmt = $conn->prepare("UPDATE users SET position = ? WHERE id = ?");
                $updateStmt->bind_param('si', $emp['position'], $user['id']);
                $updateStmt->execute();
                
                echo "✓ Updated {$user['name']}: '{$user['position']}' → '{$emp['position']}'<br>";
                $updated++;
            } else {
                echo "- Skipped {$user['name']}: Already has position '{$user['position']}'<br>";
                $skipped++;
            }
        } else {
            echo "⚠ No employee found for {$user['name']}<br>";
            $skipped++;
        }
    } else {
        echo "⚠ Invalid name format for user ID {$user['id']}: {$user['name']}<br>";
        $skipped++;
    }
}

echo "<br><strong>Summary:</strong><br>";
echo "Updated: $updated<br>";
echo "Skipped: $skipped<br>";

$conn->close();
?>
