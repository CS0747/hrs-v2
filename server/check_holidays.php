<?php
require_once 'api/db.php';

$conn = getConnection();

// Check if holidays table exists
$result = $conn->query("SHOW TABLES LIKE 'holidays'");
if ($result->num_rows === 0) {
    echo "<h2 style='color:red;'>❌ Holidays table does NOT exist!</h2>";
    echo "<p>Run the migration: <a href='migrations/run_holiday_migration.php'>migrations/run_holiday_migration.php</a></p>";
} else {
    echo "<h2 style='color:green;'>✓ Holidays table exists</h2>";
    
    // Check if there are any holidays
    $holidays = $conn->query("SELECT * FROM holidays ORDER BY date");
    $count = $holidays->num_rows;
    
    echo "<p><strong>Total holidays in database: $count</strong></p>";
    
    if ($count > 0) {
        echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
        echo "<tr><th>ID</th><th>Date</th><th>Name</th><th>Type</th></tr>";
        while ($row = $holidays->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['date']}</td>";
            echo "<td>{$row['name']}</td>";
            echo "<td>{$row['type']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:orange;'>⚠ No holidays found in database!</p>";
        echo "<p>Run the migration to insert holidays: <a href='migrations/run_holiday_migration.php'>migrations/run_holiday_migration.php</a></p>";
    }
}

// Check shift_legends table for H legend
echo "<hr><h2>Shift Legends - Holiday (H)</h2>";
$legends = $conn->query("SELECT * FROM shift_legends WHERE code = 'H'");
if ($legends->num_rows > 0) {
    echo "<p style='color:green;'>✓ Holiday legend (H) exists</p>";
    while ($row = $legends->fetch_assoc()) {
        echo "<pre>";
        print_r($row);
        echo "</pre>";
    }
} else {
    echo "<p style='color:red;'>❌ Holiday legend (H) does NOT exist!</p>";
}

$conn->close();
?>
