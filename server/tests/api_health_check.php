<?php
/**
 * API Health Check - Tests all backend API endpoints
 */

$baseUrl = 'http://localhost/hrs-v2/server/api/';

$apis = [
    'employees' => 'employees.php',
    'departments' => 'departments.php',
    'dtr' => 'dtr.php',
    'leave' => 'leave.php',
    'travel_orders' => 'travel_orders.php',
    'trainings' => 'trainings.php',
    'tracking' => 'tracking.php',
    'signatories' => 'signatories.php',
    'schedule' => 'schedule.php',
    'audit_logs' => 'audit_logs.php',
    'birthday_celebrants' => 'birthday_celebrants.php',
    'module_permissions' => 'module_permissions.php',
];

echo "=== API Health Check ===\n";
echo "Testing all backend API endpoints...\n\n";

$passed = 0;
$failed = 0;

foreach ($apis as $name => $endpoint) {
    echo "Testing $name... ";
    
    $url = $baseUrl . $endpoint;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo "✗ FAILED (Connection error: $error)\n";
        $failed++;
        continue;
    }
    
    if ($httpCode !== 200) {
        echo "✗ FAILED (HTTP $httpCode)\n";
        $failed++;
        continue;
    }
    
    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "✗ FAILED (Invalid JSON: " . json_last_error_msg() . ")\n";
        $failed++;
        continue;
    }
    
    echo "✓ PASSED (HTTP $httpCode, " . strlen($response) . " bytes)\n";
    $passed++;
}

echo "\n=== Summary ===\n";
echo "Passed: $passed\n";
echo "Failed: $failed\n";
echo "Total: " . ($passed + $failed) . "\n";

if ($failed === 0) {
    echo "\n✓ All APIs are working correctly!\n";
} else {
    echo "\n✗ Some APIs have issues. Check the output above.\n";
}
