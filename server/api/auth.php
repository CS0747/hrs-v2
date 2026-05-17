<?php
/**
 * Auth API — login, signup, profile update, user management
 */
ini_set('display_errors', 0);
error_reporting(0);
ob_start();
require_once 'db.php';
require_once 'cors.php';
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

        // Check duplicate username (case-insensitive)
        $chk = $conn->prepare('SELECT id FROM users WHERE LOWER(username) = LOWER(?)');
        $chk->bind_param('s', $username);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            $chk->close();
            sendError('Username already exists.', 409);
        }
        $chk->close();

        // Use transaction to prevent race conditions
        $conn->begin_transaction();
        
        try {
            $stmt = $conn->prepare(
                'INSERT INTO users (username, password, name, role, department)
                 VALUES (?, SHA2(?, 256), ?, ?, ?)'
            );
            $stmt->bind_param('sssss', $username, $password, $name, $role, $dept);
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to create account: ' . $stmt->error);
            }
            
            $insertId = $conn->insert_id;
            $stmt->close();
            
            $conn->commit();
            sendJson(['id' => $insertId, 'message' => 'Account created'], 201);
            
        } catch (Exception $e) {
            $conn->rollback();
            sendError($e->getMessage(), 500);
        }
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
        $stmt = $conn->prepare("SELECT role FROM users WHERE id=?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $target = $stmt->get_result()->fetch_assoc();
        if ($target && $target['role'] === 'Super Admin' && (int)$check['cnt'] <= 1) {
            sendError('Cannot delete the last Super Admin account', 403);
        }

        // Permanently delete the user from database
        $stmt = $conn->prepare('DELETE FROM users WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        sendJson(['message' => 'User permanently deleted']);
        break;

    // PUT /auth.php?action=reactivate_user&id=X
    case 'reactivate_user':
        if ($method !== 'PUT') sendError('PUT required', 405);
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) sendError('ID required');

        $stmt = $conn->prepare('UPDATE users SET active = 1 WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        sendJson(['message' => 'User reactivated']);
        break;

    // POST /auth.php?action=request_password_reset
    case 'request_password_reset':
        if ($method !== 'POST') sendError('POST required', 405);
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) sendError('Invalid JSON body');

        $username = trim($data['username'] ?? '');
        if (!$username) sendError('Username is required');

        // Check if user exists
        $stmt = $conn->prepare('SELECT id, name FROM users WHERE username = ? AND active = 1');
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        
        if (!$user) {
            sendError('Username not found or account is inactive', 404);
        }

        // Check if there's already a pending request
        $chk = $conn->prepare('SELECT id FROM password_reset_requests WHERE user_id = ? AND status = ?');
        $status = 'pending';
        $chk->bind_param('is', $user['id'], $status);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            $chk->close();
            sendError('You already have a pending password reset request. Please contact your administrator.', 409);
        }
        $chk->close();

        // Create password reset request
        $stmt = $conn->prepare(
            'INSERT INTO password_reset_requests (user_id, username, user_name, status)
             VALUES (?, ?, ?, ?)'
        );
        $status = 'pending';
        $stmt->bind_param('isss', $user['id'], $username, $user['name'], $status);
        
        if (!$stmt->execute()) {
            sendError('Failed to create password reset request: ' . $stmt->error, 500);
        }

        $requestId = $conn->insert_id;
        
        // Create notification for DIOS users
        require_once 'notification_helpers.php';
        notifyPasswordResetRequest($conn, $username, $user['name'], $requestId);

        sendJson(['message' => 'Password reset request submitted successfully'], 201);
        break;

    // GET /auth.php?action=get_password_reset_requests
    case 'get_password_reset_requests':
        if ($method !== 'GET') sendError('GET required', 405);
        
        // Only DIOS can view password reset requests
        $userId = (int)($_SERVER['HTTP_X_USER_ID'] ?? 0);
        $role = getUserRole($conn, $userId);
        if ($role !== 'DIOS') {
            sendError('Access denied. Only DIOS can view password reset requests.', 403);
        }
        
        $result = $conn->query(
            'SELECT * FROM password_reset_requests ORDER BY requested_at DESC'
        );
        $rows = $result->fetch_all(MYSQLI_ASSOC);
        sendJson(['requests' => $rows]);
        break;

    // POST /auth.php?action=process_password_reset
    case 'process_password_reset':
        if ($method !== 'POST') sendError('POST required', 405);
        
        // Only DIOS can process password reset requests
        $userId = (int)($_SERVER['HTTP_X_USER_ID'] ?? 0);
        $role = getUserRole($conn, $userId);
        if ($role !== 'DIOS') {
            sendError('Access denied. Only DIOS can process password reset requests.', 403);
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) sendError('Invalid JSON body');

        $requestId = (int)($data['request_id'] ?? 0);
        $targetUserId = (int)($data['user_id'] ?? 0);
        $action = $data['action'] ?? ''; // 'approve' or 'reject'
        $newPassword = $data['new_password'] ?? '';

        if (!$requestId || !$targetUserId || !$action) {
            sendError('Missing required fields');
        }

        if (!in_array($action, ['approve', 'reject'])) {
            sendError('Invalid action. Must be approve or reject');
        }

        // Get current user name
        $stmt = $conn->prepare('SELECT name FROM users WHERE id = ?');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $currentUser = $stmt->get_result()->fetch_assoc();
        $processedBy = $currentUser['name'] ?? 'DIOS';

        $conn->begin_transaction();
        
        try {
            if ($action === 'approve') {
                if (!$newPassword || strlen($newPassword) < 6) {
                    throw new Exception('New password must be at least 6 characters');
                }

                // Update user password
                $stmt = $conn->prepare('UPDATE users SET password = SHA2(?, 256) WHERE id = ?');
                $stmt->bind_param('si', $newPassword, $targetUserId);
                if (!$stmt->execute()) {
                    throw new Exception('Failed to update password');
                }

                // Update request status
                $stmt = $conn->prepare(
                    'UPDATE password_reset_requests 
                     SET status = ?, processed_at = NOW(), processed_by = ?
                     WHERE id = ?'
                );
                $status = 'approved';
                $stmt->bind_param('ssi', $status, $processedBy, $requestId);
                $stmt->execute();

                $conn->commit();
                sendJson(['message' => 'Password reset successful']);
                
            } else { // reject
                $stmt = $conn->prepare(
                    'UPDATE password_reset_requests 
                     SET status = ?, processed_at = NOW(), processed_by = ?
                     WHERE id = ?'
                );
                $status = 'rejected';
                $stmt->bind_param('ssi', $status, $processedBy, $requestId);
                $stmt->execute();

                $conn->commit();
                sendJson(['message' => 'Request rejected']);
            }
            
        } catch (Exception $e) {
            $conn->rollback();
            sendError($e->getMessage(), 500);
        }
        break;

    default:
        sendError('Unknown action', 400);
}

$conn->close();
