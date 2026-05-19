<?php
/**
 * Run Medical Department Legends Migration
 * Adds shift legends for medical-related departments
 */

require_once __DIR__ . '/../api/db.php';

header('Content-Type: application/json');

try {
    $conn = getConnection();
    
    // Read the SQL file
    $sqlFile = __DIR__ . '/add_medical_dept_legends.sql';
    
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
    $conn->begin_transaction();
    
    try {
        foreach ($statements as $statement) {
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
            'message' => 'Medical department legends migration completed successfully',
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
