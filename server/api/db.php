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

function getRequestUserId() {
    return (int)($_SERVER['HTTP_X_USER_ID'] ?? $_SERVER['HTTP_X_USERID'] ?? 0);
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

function getUserById($conn, $userId) {
    if (!$userId || $userId <= 0) {
        return null;
    }

    $stmt = $conn->prepare('SELECT id, username, name, role, department, position FROM users WHERE id = ? AND active = 1');
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('i', $userId);
    if (!$stmt->execute()) {
        $stmt->close();
        return null;
    }

    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $user ?: null;
}

function getCurrentUser($conn) {
    return getUserById($conn, getRequestUserId());
}

function requireUser($conn) {
    $user = getCurrentUser($conn);
    if (!$user) {
        sendError('Authentication required', 401);
    }

    return $user;
}

function requireRole($conn, $allowedRoles) {
    $user = requireUser($conn);
    if (!in_array($user['role'], $allowedRoles, true)) {
        sendError('Access denied', 403);
    }

    return $user;
}

function checkPermission($conn, $userId, $module, $action) {
    if (!$userId || $userId <= 0) {
        return false;
    }

    $role = getUserRole($conn, $userId);
    if (!$role) {
        return false;
    }

    if ($role === 'DIOS') {
        return true;
    }

    $stmt = $conn->prepare(
        'SELECT granted FROM module_permissions WHERE module = ? AND role = ? AND action = ?'
    );
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('sss', $module, $role, $action);
    if (!$stmt->execute()) {
        $stmt->close();
        return false;
    }

    $result = $stmt->get_result();
    $permission = $result->fetch_assoc();
    $stmt->close();

    if (!$permission) {
        return false;
    }

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

// CORS headers
$allowedOrigins = array_filter(array_map('trim', explode(',', getenv('HRIS_ALLOWED_ORIGINS') ?: 'http://localhost,http://localhost:5173,http://localhost:3000,http://127.0.0.1')));
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin && in_array($origin, $allowedOrigins, true)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Vary: Origin');
}
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-User-Id, X-User-ID');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
