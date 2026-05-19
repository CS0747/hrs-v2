<?php
/**
 * Test Schedule API Enhancements
 */

echo "Testing Schedule Management Enhancement APIs\n";
echo str_repeat('=', 60) . "\n\n";

// Test 1: Fetch shift legends
echo "Test 1: Fetch all shift legends\n";
$ch = curl_init('http://localhost/hrs-v2/server/api/shift_legends.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
if ($httpCode === 200) {
    $legends = json_decode($response, true);
    echo "✓ Fetched " . count($legends) . " legends\n";
    echo "Sample legend: " . json_encode($legends[0] ?? [], JSON_PRETTY_PRINT) . "\n";
} else {
    echo "✗ Failed: $response\n";
}

echo "\n" . str_repeat('-', 60) . "\n\n";

// Test 2: Fetch nursing department legends
echo "Test 2: Fetch Nursing department legends\n";
$ch = curl_init('http://localhost/hrs-v2/server/api/shift_legends.php?department=Nursing');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
if ($httpCode === 200) {
    $legends = json_decode($response, true);
    echo "✓ Fetched " . count($legends) . " nursing legends\n";
    foreach ($legends as $legend) {
        echo "  - {$legend['code']}: {$legend['timeRange']} ({$legend['colorPrimary']})\n";
    }
} else {
    echo "✗ Failed: $response\n";
}

echo "\n" . str_repeat('-', 60) . "\n\n";

// Test 3: Fetch schedules by department
echo "Test 3: Fetch schedules (all)\n";
$ch = curl_init('http://localhost/hrs-v2/server/api/schedule.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-User-ID: 1']);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
if ($httpCode === 200) {
    $schedules = json_decode($response, true);
    echo "✓ Fetched " . count($schedules) . " schedules\n";
    if (!empty($schedules)) {
        $sample = $schedules[0];
        echo "Sample schedule:\n";
        echo "  Employee: {$sample['employee_name']}\n";
        echo "  Department: {$sample['department']}\n";
        if ($sample['schedule_date']) {
            echo "  Date: {$sample['schedule_date']}\n";
            echo "  Time: {$sample['start_time']} - {$sample['end_time']}\n";
            echo "  Shift: {$sample['shift_code']}\n";
        } else {
            echo "  Legacy format: {$sample['shift']} ({$sample['shift_time']})\n";
        }
    }
} else {
    echo "✗ Failed: $response\n";
}

echo "\n" . str_repeat('-', 60) . "\n\n";

// Test 4: Create a new schedule (new format)
echo "Test 4: Create new schedule (new format)\n";
$newSchedule = [
    'employeeNo' => 'TEST-001',
    'employeeName' => 'Test Employee',
    'department' => 'Nursing',
    'scheduleDate' => '2026-05-20',
    'startTime' => '06:00:00',
    'endTime' => '14:00:00',
    'shiftCode' => '62',
    'shiftName' => 'Morning',
    'status' => 'Pending',
    'remarks' => 'Test schedule'
];

$ch = curl_init('http://localhost/hrs-v2/server/api/schedule.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($newSchedule));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-User-ID: 1'
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
if ($httpCode === 201) {
    $result = json_decode($response, true);
    echo "✓ Schedule created with ID: {$result['id']}\n";
    $createdId = $result['id'];
} else {
    echo "⚠ Response: $response\n";
    $createdId = null;
}

echo "\n" . str_repeat('-', 60) . "\n\n";

// Test 5: Test duplicate schedule validation
echo "Test 5: Test duplicate schedule validation\n";
if ($createdId) {
    $ch = curl_init('http://localhost/hrs-v2/server/api/schedule.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($newSchedule));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-User-ID: 1'
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP Code: $httpCode\n";
    if ($httpCode === 409) {
        echo "✓ Duplicate validation working correctly\n";
    } else {
        echo "✗ Expected 409, got $httpCode: $response\n";
    }
} else {
    echo "⚠ Skipped (no schedule created in previous test)\n";
}

echo "\n" . str_repeat('-', 60) . "\n\n";

// Test 6: Test invalid time range validation
echo "Test 6: Test invalid time range validation\n";
$invalidSchedule = [
    'employeeNo' => 'TEST-002',
    'employeeName' => 'Test Employee 2',
    'department' => 'Nursing',
    'scheduleDate' => '2026-05-21',
    'startTime' => '14:00:00',
    'endTime' => '06:00:00', // End before start
    'shiftCode' => '62',
    'shiftName' => 'Morning'
];

$ch = curl_init('http://localhost/hrs-v2/server/api/schedule.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($invalidSchedule));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-User-ID: 1'
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
if ($httpCode === 400) {
    echo "✓ Time range validation working correctly\n";
    echo "  Error: $response\n";
} else {
    echo "✗ Expected 400, got $httpCode: $response\n";
}

echo "\n" . str_repeat('-', 60) . "\n\n";

// Cleanup
if ($createdId) {
    echo "Cleanup: Deleting test schedule\n";
    $ch = curl_init("http://localhost/hrs-v2/server/api/schedule.php?id=$createdId");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-User-ID: 1']);
    curl_exec($ch);
    curl_close($ch);
    echo "✓ Test schedule deleted\n";
}

echo "\n" . str_repeat('=', 60) . "\n";
echo "✓ All API tests completed!\n";
