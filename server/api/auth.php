<?php
/**
 * Auth API — login, signup, profile update, user management
 */
ini_set('display_errors', 0);
error_reporting(0);
ob_start();
require_once 'db.php';
ob_clean();

$method = $_SERVER['REQUEST_METHOD'];
$conn   = getConnection();
$action = $_GET['action'] ?? '';

switch ($action) {

    // POST /auth.php?action=login
    case 'login':
        if ($method !== 'POST') sendError('POST required', 405);
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) sendError('Invalid JSON body');

        $username = trim($data['username'] ?? '');
        $password = trim($data['password'] ?? '');
        if (!$username || !$password) sendError('Username and password required');

        $stmt = $conn->prepare(
            'SELECT id, username, name, role, department
             FROM users WHERE username = ? AND password = SHA2(?, 256) AND active = 1'
        );
        $stmt->bind_param('ss', $username, $password);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user) {
            // Audit log
            $logStmt = $conn->prepare(
                'INSERT INTO audit_logs (user_id, user_name, action, action_type, module, details, status)
                 VALUES (?,?,?,?,?,?,?)'
            );
            $act    = 'Login';
            $atype  = 'LOGIN';
            $mod    = 'Auth';
            $det    = $user['name'] . ' logged in.';
            $status = 'OK';
            $logStmt->bind_param('issssss', $user['id'], $user['name'], $act, $atype, $mod, $det, $status);
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

        $username = trim($data['username'] ?? '');
        $password = trim($data['password'] ?? '');
        $name     = trim($data['name']     ?? '');
        $role     = trim($data['role']     ?? 'Admin');
        $dept     = trim($data['department'] ?? 'Human Resources');

        if (!$username || !$password || !$name) sendError('Username, password, and name are required');
        if (strlen($password) < 6) sendError('Password must be at least 6 characters');

        $allowed_roles = ['Super Admin', 'Admin', 'DIOS', 'Section Admin', 'IT'];
        if (!in_array($role, $allowed_roles)) sendError('Invalid role');

        // Check duplicate username
        $chk = $conn->prepare('SELECT id FROM users WHERE username = ?');
        $chk->bind_param('s', $username);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) sendError('Username already exists.', 409);

        $stmt = $conn->prepare(
            'INSERT INTO users (username, password, name, role, department)
             VALUES (?, SHA2(?, 256), ?, ?, ?)'
        );
        $stmt->bind_param('sssss', $username, $password, $name, $role, $dept);
        if (!$stmt->execute()) sendError('Failed to create account: ' . $stmt->error, 500);

        sendJson(['id' => $conn->insert_id, 'message' => 'Account created'], 201);
        break;

    // GET /auth.php?action=users
    case 'users':
        if ($method !== 'GET') sendError('GET required', 405);
        
        // Check permission for Account Management module
        $userId = (int)($_SERVER['HTTP_X_USER_ID'] ?? 0);
        if (!checkPermission($conn, $userId, 'Account Management', 'View')) {
            denyAccess('Account Management', 'View');
        }
        
        $rows = $conn->query(
            'SELECT id, username, name, role, department, active, created_at FROM users ORDER BY id'
        )->fetch_all(MYSQLI_ASSOC);
        sendJson(['users' => $rows]);
        break;

    // PUT /auth.php?action=update_profile&id=X
    case 'update_profile':
        if ($method !== 'PUT') sendError('PUT required', 405);
        $id   = (int)($_GET['id'] ?? 0);
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$id || !$data) sendError('Invalid request');

        $name = trim($data['name']       ?? '');
        $role = trim($data['role']       ?? '');
        $dept = trim($data['department'] ?? '');
        if (!$name) sendError('Name is required');

        $stmt = $conn->prepare('UPDATE users SET name=?, role=?, department=? WHERE id=?');
        $stmt->bind_param('sssi', $name, $role, $dept, $id);
        $stmt->execute();
        sendJson(['message' => 'Profile updated']);
        break;

    // PUT /auth.php?action=change_password&id=X
    case 'change_password':
        if ($method !== 'PUT') sendError('PUT required', 405);
        $id   = (int)($_GET['id'] ?? 0);
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$id || !$data) sendError('Invalid request');

        $newPass = trim($data['password'] ?? '');
        if (strlen($newPass) < 6) sendError('Password must be at least 6 characters');

        $stmt = $conn->prepare('UPDATE users SET password = SHA2(?, 256) WHERE id = ?');
        $stmt->bind_param('si', $newPass, $id);
        $stmt->execute();
        sendJson(['message' => 'Password updated']);
        break;

    // DELETE /auth.php?action=delete_user&id=X
    case 'delete_user':
        if ($method !== 'DELETE') sendError('DELETE required', 405);
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) sendError('ID required');

        // Prevent deleting the last Super Admin
        $check = $conn->query("SELECT COUNT(*) as cnt FROM users WHERE role='Super Admin' AND active=1")->fetch_assoc();
        $target = $conn->query("SELECT role FROM users WHERE id=$id")->fetch_assoc();
        if ($target && $target['role'] === 'Super Admin' && (int)$check['cnt'] <= 1) {
            sendError('Cannot delete the last Super Admin account', 403);
        }

        $stmt = $conn->prepare('UPDATE users SET active = 0 WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        sendJson(['message' => 'User deactivated']);
        break;

    default:
        sendError('Unknown action', 400);
}

$conn->close();
