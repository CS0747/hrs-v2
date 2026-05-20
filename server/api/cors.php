<?php
/**
 * CORS Configuration
 * Handles Cross-Origin Resource Sharing for API requests
 */

$defaultOrigins = 'http://localhost,http://localhost:5173,http://localhost:3000,http://127.0.0.1';
$allowedOrigins = array_filter(array_map('trim', explode(',', getenv('HRIS_ALLOWED_ORIGINS') ?: $defaultOrigins)));

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if ($origin && in_array($origin, $allowedOrigins, true)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Vary: Origin');
}

header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-User-Id, X-User-ID, X-User-Role');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Max-Age: 86400'); // 24 hours

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
