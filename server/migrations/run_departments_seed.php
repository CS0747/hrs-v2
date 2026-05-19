<?php
/**
 * Run Departments Seed
 * Creates departments table and seeds initial data
 */

require_once __DIR__ . '/../api/db.php';

header('Content-Type: application/json');

try {
    $conn = getConnection();
    
    // Read the SQL file
    $sqlFile = __DIR__ . '/../seed_departments.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("Seed file not found: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Split by semicolons to execute multiple statements
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^--/', $stmt) && stripos($stmt, 'USE ') !== 0;
        }
    );
    
    $results = [];
    
    try {
        foreach ($statements as $statement) {
            $conn->query($statement);
            $results[] = [
                'affected_rows' => $conn->affected_rows,
                'statement' => substr($statement, 0, 100) . '...'
            ];
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Departments seed completed successfully',
            'results' => $results
        ], JSON_PRETTY_PRINT);
        
    } catch (Exception $e) {
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
