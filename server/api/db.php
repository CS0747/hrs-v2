<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'geamh_hris');

function getConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        http_response_code(500);
        die(json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]));
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}

// Common response helpers
function sendJson($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function sendError($message, $code = 400) {
    sendJson(['error' => $message], $code);
}

// Permission checking functions
function getUserRole($conn, $userId) {
    if (!$userId || $userId <= 0) {
        return null;
    }
    
    $stmt = $conn->prepare('SELECT role FROM users WHERE id = ? AND active = 1');
    if (!$stmt) {
        return null;
    }
    
    $stmt->bind_param('i', $userId);
    if (!$stmt->execute()) {
        return null;
    }
    
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    
    return $user ? $user['role'] : null;
}

function checkPermission($conn, $userId, $module, $action) {
    // Deny access if no valid user ID is provided
    if (!$userId || $userId <= 0) {
        return false;
    }

    // Retrieve user role
    $role = getUserRole($conn, $userId);

    // Deny if role cannot be determined
    if (!$role) {
        return false;
    }

    // DIOS role retains unrestricted access
    if ($role === 'DIOS') {
        return true;
    }

    // Prepare permission lookup
    $stmt = $conn->prepare(
        'SELECT granted FROM module_permissions WHERE module = ? AND role = ? AND action = ?'
    );

    if (!$stmt) {
        // If the permissions table is unreachable, deny access
        return false;
    }

    $stmt->bind_param('sss', $module, $role, $action);
    if (!$stmt->execute()) {
        $stmt->close();
        // Query execution failed; deny access
        return false;
    }

    $result = $stmt->get_result();
    $permission = $result->fetch_assoc();
    $stmt->close();

    // Deny if no permission record exists for this module/role/action
    if (!$permission) {
        return false;
    }

    // Return true only if permission is explicitly granted
    return (bool)$permission['granted'];
}

function denyAccess($module, $action) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode([
        'error' => "Access denied: You do not have permission to perform $action on $module"
    ]);
    exit;
}

// CORS headers (adjust origin for production)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-User-Id, X-User-ID');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
