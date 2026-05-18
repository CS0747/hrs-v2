<?php
/**
 * Run Password Reset Migration
 * Creates the password_reset_requests table
 */

$conn = new mysqli('localhost', 'root', '', 'geamh_hris');

if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

echo "Running password reset migration...\n";

$sql = file_get_contents(__DIR__ . '/migrate_password_resets.sql');

if ($conn->multi_query($sql)) {
    do {
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->next_result());
    echo "✓ Password reset requests table created successfully\n";
} else {
    echo "Error: " . $conn->error . "\n";
}

$conn->close();
echo "\nMigration complete!\n";
