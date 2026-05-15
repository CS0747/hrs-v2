<?php
/**
 * CORS Helper for HRS-V2 API
 * Include this file at the top of each API endpoint to handle CORS consistently
 */

// Allow requests from any origin (for development)
// In production, you might want to restrict this to specific domains
header('Access-Control-Allow-Origin: *');

// Allow specific HTTP methods
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');

// Allow specific headers
header('Access-Control-Allow-Headers: Content-Type, X-User-Id, X-DIOS-Token');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}