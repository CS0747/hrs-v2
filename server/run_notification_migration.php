<?php
$conn = new mysqli('localhost', 'root', '', 'geamh_hris');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

$sql = file_get_contents(__DIR__ . '/migrate_notifications.sql');
if ($conn->multi_query($sql)) {
    do {
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->next_result());
    echo "Notifications table created successfully\n";
} else {
    echo "Error: " . $conn->error . "\n";
}

$conn->close();
