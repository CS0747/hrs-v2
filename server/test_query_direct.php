<?php
// Direct test of query action
$_SERVER['REQUEST_METHOD'] = 'POST';
$_GET['action'] = 'query';

// Simulate POST body
$GLOBALS['HTTP_RAW_POST_DATA'] = json_encode(['sql' => 'SHOW TABLES']);

// Capture output
ob_start();
include 'api/dios_control.php';
$output = ob_get_clean();

echo "Output:\n";
echo $output;
echo "\n";
