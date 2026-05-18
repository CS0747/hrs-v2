<?php
/**
 * Test DIOS Control API
 */

// Test 1: Get tables
echo "Test 1: Get tables\n";
$ch = curl_init('http://localhost/hrs-v2/server/api/dios_control.php?action=tables');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response: $response\n\n";

// Test 2: Get stats
echo "Test 2: Get stats\n";
$ch = curl_init('http://localhost/hrs-v2/server/api/dios_control.php?action=stats');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response: $response\n\n";

// Test 3: Run simple query
echo "Test 3: Run query (SHOW TABLES)\n";
$ch = curl_init('http://localhost/hrs-v2/server/api/dios_control.php?action=query');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['sql' => 'SHOW TABLES']));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response: $response\n\n";

echo "All tests completed!\n";
