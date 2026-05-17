<?php
/**
 * Notifications API — real-time notifications with mark as read
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
$userId = (int)($_SERVER['HTTP_X_USER_ID'] ?? 0);

if (!$userId) {
    sendError('Unauthorized', 401);
}

switch ($action) {

    // GET /notifications.php?action=list - Get user notifications
    case 'list':
        if ($method !== 'GET') sendError('GET required', 405);
        
        $limit = (int)($_GET['limit'] ?? 50);
        $unreadOnly = isset($_GET['unread_only']) && $_GET['unread_only'] === 'true';
        
        $sql = 'SELECT * FROM notifications WHERE user_id = ?';
        if ($unreadOnly) {
            $sql .= ' AND is_read = 0';
        }
        $sql .= ' ORDER BY created_at DESC LIMIT ?';
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $userId, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $notifications = $result->fetch_all(MYSQLI_ASSOC);
        
        sendJson(['notifications' => $notifications]);
        break;

    // GET /notifications.php?action=count_unread - Get unread count
    case 'count_unread':
        if ($method !== 'GET') sendError('GET required', 405);
        
        $stmt = $conn->prepare('SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        sendJson(['count' => (int)$result['count']]);
        break;

    // POST /notifications.php?action=mark_read - Mark notification as read
    case 'mark_read':
        if ($method !== 'POST') sendError('POST required', 405);
        
        $data = json_decode(file_get_contents('php://input'), true);
        $notificationId = (int)($data['notification_id'] ?? 0);
        
        if (!$notificationId) sendError('Notification ID required');
        
        $stmt = $conn->prepare('UPDATE notifications SET is_read = 1, read_at = NOW() WHERE id = ? AND user_id = ?');
        $stmt->bind_param('ii', $notificationId, $userId);
        $stmt->execute();
        
        sendJson(['message' => 'Notification marked as read']);
        break;

    // POST /notifications.php?action=mark_all_read - Mark all as read
    case 'mark_all_read':
        if ($method !== 'POST') sendError('POST required', 405);
        
        $stmt = $conn->prepare('UPDATE notifications SET is_read = 1, read_at = NOW() WHERE user_id = ? AND is_read = 0');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        
        sendJson(['message' => 'All notifications marked as read']);
        break;

    // DELETE /notifications.php?action=delete&id=X - Delete notification
    case 'delete':
        if ($method !== 'DELETE') sendError('DELETE required', 405);
        
        $notificationId = (int)($_GET['id'] ?? 0);
        if (!$notificationId) sendError('Notification ID required');
        
        $stmt = $conn->prepare('DELETE FROM notifications WHERE id = ? AND user_id = ?');
        $stmt->bind_param('ii', $notificationId, $userId);
        $stmt->execute();
        
        sendJson(['message' => 'Notification deleted']);
        break;

    // DELETE /notifications.php?action=clear_all - Clear all notifications
    case 'clear_all':
        if ($method !== 'DELETE') sendError('DELETE required', 405);
        
        $stmt = $conn->prepare('DELETE FROM notifications WHERE user_id = ?');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        
        sendJson(['message' => 'All notifications cleared']);
        break;

    // POST /notifications.php?action=create - Create notification (internal use)
    case 'create':
        if ($method !== 'POST') sendError('POST required', 405);
        
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) sendError('Invalid JSON body');
        
        $targetUserId = (int)($data['user_id'] ?? 0);
        $type = trim($data['type'] ?? '');
        $title = trim($data['title'] ?? '');
        $message = trim($data['message'] ?? '');
        $referenceId = isset($data['reference_id']) ? (int)$data['reference_id'] : null;
        $referenceType = isset($data['reference_type']) ? trim($data['reference_type']) : null;
        $link = isset($data['link']) ? trim($data['link']) : null;
        
        if (!$targetUserId || !$type || !$title || !$message) {
            sendError('Missing required fields');
        }
        
        $stmt = $conn->prepare(
            'INSERT INTO notifications (user_id, type, title, message, reference_id, reference_type, link)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('isssiis', $targetUserId, $type, $title, $message, $referenceId, $referenceType, $link);
        $stmt->execute();
        
        sendJson(['id' => $conn->insert_id, 'message' => 'Notification created'], 201);
        break;

    // GET /notifications.php?action=poll - Poll for new notifications (real-time)
    case 'poll':
        if ($method !== 'GET') sendError('GET required', 405);
        
        $lastId = (int)($_GET['last_id'] ?? 0);
        
        $stmt = $conn->prepare(
            'SELECT * FROM notifications WHERE user_id = ? AND id > ? ORDER BY created_at DESC'
        );
        $stmt->bind_param('ii', $userId, $lastId);
        $stmt->execute();
        $result = $stmt->get_result();
        $newNotifications = $result->fetch_all(MYSQLI_ASSOC);
        
        sendJson(['notifications' => $newNotifications]);
        break;

    default:
        sendError('Unknown action', 400);
}

$conn->close();
