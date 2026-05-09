<?php
require_once 'db.php';

$method = $_SERVER['REQUEST_METHOD'];
$conn   = getConnection();
$action = $_GET['action'] ?? '';

switch ($action) {

    // POST /auth.php?action=login
    case 'login':
        if ($method !== 'POST') sendError('POST required', 405);
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) sendError('Invalid JSON body');

        $stmt = $conn->prepare(
            'SELECT id, username, name, role, department FROM users
             WHERE username = ? AND password = SHA2(?, 256) AND active = 1'
        );
        $stmt->bind_param('ss', $data['username'], $data['password']);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user) {
            // Log the login
            $logStmt = $conn->prepare(
                'INSERT INTO audit_logs (user_id, user_name, action, module, details, status)
                 VALUES (?,?,?,?,?,?)'
            );
            $action_log = 'Login';
            $module     = 'Auth';
            $details    = $user['name'] . ' logged in.';
            $status     = 'OK';
            $logStmt->bind_param('isssss', $user['id'], $user['name'], $action_log, $module, $details, $status);
            $logStmt->execute();

            sendJson(['user' => $user, 'message' => 'Login successful']);
        } else {
            sendError('Invalid username or password.', 401);
        }
        break;

    // POST /auth.php?action=signup
    case 'signup':
        if ($method !== 'POST') sendError('POST required', 405);
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) sendError('Invalid JSON body');

        // Check limit (max 2 accounts)
        $count = $conn->query('SELECT COUNT(*) AS cnt FROM users')->fetch_assoc()['cnt'];
        if ($count >= 2) sendError('Account limit reached. Only 2 accounts allowed.', 403);

        // Check duplicate username
        $chk = $conn->prepare('SELECT id FROM users WHERE username = ?');
        $chk->bind_param('s', $data['username']);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) sendError('Username already exists.', 409);

        $stmt = $conn->prepare(
            'INSERT INTO users (username, password, name, role, department)
             VALUES (?, SHA2(?, 256), ?, ?, ?)'
        );
        $role = $data['role'] ?? 'Admin';
        $dept = $data['department'] ?? 'Human Resources';
        $stmt->bind_param('sssss', $data['username'], $data['password'], $data['name'], $role, $dept);
        $stmt->execute();
        sendJson(['id' => $conn->insert_id, 'message' => 'Account created'], 201);
        break;

    // PUT /auth.php?action=update_profile&id=1
    case 'update_profile':
        if ($method !== 'PUT') sendError('PUT required', 405);
        $id   = (int) ($_GET['id'] ?? 0);
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$id || !$data) sendError('Invalid request');

        $stmt = $conn->prepare(
            'UPDATE users SET name=?, role=?, department=? WHERE id=?'
        );
        $stmt->bind_param('sssi', $data['name'], $data['role'], $data['department'], $id);
        $stmt->execute();
        sendJson(['message' => 'Profile updated']);
        break;

    default:
        sendError('Unknown action', 400);
}

$conn->close();
