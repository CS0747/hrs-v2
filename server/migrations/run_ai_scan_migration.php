<?php
/**
 * AI Scan Table Migration Runner
 * Run this file once to create the ai_scanned_docs table
 */

require_once __DIR__ . '/../api/db.php';

echo "=== AI Scan Table Migration ===\n\n";

try {
    $conn = getConnection();
    
    // Read the SQL file
    $sql = file_get_contents(__DIR__ . '/ai_scan_table.sql');
    
    if (!$sql) {
        throw new Exception("Could not read migration file");
    }
    
    echo "Executing migration...\n";
    
    // Execute the SQL
    if ($conn->multi_query($sql)) {
        do {
            // Store first result set
            if ($result = $conn->store_result()) {
                $result->free();
            }
        } while ($conn->next_result());
    }
    
    if ($conn->error) {
        throw new Exception("Migration failed: " . $conn->error);
    }
    
    echo "✓ Migration completed successfully!\n";
    echo "✓ Table 'ai_scanned_docs' is ready.\n\n";
    
    // Verify table exists
    $result = $conn->query("SHOW TABLES LIKE 'ai_scanned_docs'");
    if ($result->num_rows > 0) {
        echo "✓ Verification: Table exists in database.\n";
        
        // Show table structure
        $result = $conn->query("DESCRIBE ai_scanned_docs");
        echo "\nTable structure:\n";
        echo str_repeat("-", 80) . "\n";
        printf("%-20s %-20s %-10s %-10s\n", "Field", "Type", "Null", "Key");
        echo str_repeat("-", 80) . "\n";
        while ($row = $result->fetch_assoc()) {
            printf("%-20s %-20s %-10s %-10s\n", 
                $row['Field'], 
                $row['Type'], 
                $row['Null'], 
                $row['Key']
            );
        }
        echo str_repeat("-", 80) . "\n";
    } else {
        throw new Exception("Table verification failed - table not found after migration");
    }
    
    $conn->close();
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\nMigration complete! You can now use the AI Scanning Tools.\n";
