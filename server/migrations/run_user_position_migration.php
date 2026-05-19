<?php
/**
 * Run User Position Migration
 * Adds position field to users table
 */

require_once __DIR__ . '/../api/db.php';

header('Content-Type: application/json');

try {
    $conn = getConnection();
    
    // Read the SQL file
    $sqlFile = __DIR__ . '/add_user_position.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("Migration file not found: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Split by semicolons to execute multiple statements
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^--/', $stmt);
        }
    );
    
    $results = [];
    
    try {
        // Execute ALTER TABLE first (not in transaction)
        $alterStmt = "ALTER TABLE users ADD COLUMN position VARCHAR(150) DEFAULT NULL COMMENT 'User position/title for signatories'";
        try {
            $conn->query($alterStmt);
            $results[] = ['message' => 'ALTER TABLE executed', 'affected_rows' => $conn->affected_rows];
        } catch (Exception $e) {
            // Column might already exist, continue
            $results[] = ['message' => 'ALTER TABLE skipped (column may exist)', 'error' => $e->getMessage()];
        }
        
        // Now execute other statements in transaction
        $conn->begin_transaction();
        
        foreach ($statements as $statement) {
            // Skip ALTER TABLE as we already executed it
            if (stripos($statement, 'ALTER TABLE') === 0) {
                continue;
            }
            
            if (stripos($statement, 'SELECT') === 0) {
                // Execute SELECT and get result
                $result = $conn->query($statement);
                if ($result) {
                    $row = $result->fetch_assoc();
                    $results[] = $row;
                }
            } else {
                // Execute other statements
                $conn->query($statement);
                $results[] = [
                    'affected_rows' => $conn->affected_rows,
                    'statement' => substr($statement, 0, 100) . '...'
                ];
            }
        }
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'User position migration completed successfully',
            'results' => $results
        ], JSON_PRETTY_PRINT);
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT);
}
