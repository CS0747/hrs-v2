<?php
require_once 'db.php';

$method = $_SERVER['REQUEST_METHOD'];
$conn = getConnection();
$userId = (int)($_SERVER['HTTP_X_USER_ID'] ?? 0);

switch ($method) {
    // GET /notifications.php - Get user's notifications
    case 'GET':
        if (!$userId) sendError('User ID required', 401);
        
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
        $unreadOnly = isset($_GET['unread']) && $_GET['unread'] === 'true';
        
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
    
    // POST /notifications.php - Create notification
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) sendError('Invalid JSON body');
        
        $targetUserId = (int)($data['userId'] ?? 0);
        $type = $data['type'] ?? '';
        $title = $data['title'] ?? '';
        $message = $data['message'] ?? '';
        $referenceId = isset($data['referenceId']) ? (int)$data['referenceId'] : null;
        $referenceType = $data['referenceType'] ?? null;
        $link = $data['link'] ?? null;
        
        if (!$targetUserId || !$type || !$title || !$message) {
            sendError('Missing required fields');
        }
        
        $stmt = $conn->prepare(
            'INSERT INTO notifications (user_id, type, title, message, reference_id, reference_type, link) 
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('isssis', $targetUserId, $type, $title, $message, $referenceId, $referenceType, $link);
        
        if (!$stmt->execute()) sendError('Failed to create notification', 500);
        
        sendJson(['id' => $conn->insert_id, 'message' => 'Notification created'], 201);
        break;
    
    // PUT /notifications.php?id=1 - Mark as read
    // PUT /notifications.php?action=mark_all_read - Mark all as read
    case 'PUT':
        if (!$userId) sendError('User ID required', 401);
        
        $action = $_GET['action'] ?? '';
        
        if ($action === 'mark_all_read') {
            // Mark all unread notifications as read
            $stmt = $conn->prepare(
                'UPDATE notifications SET is_read = 1, read_at = NOW() 
                 WHERE user_id = ? AND is_read = 0'
            );
            $stmt->bind_param('i', $userId);
            
            if (!$stmt->execute()) sendError('Failed to update notifications', 500);
            
            sendJson(['message' => 'All notifications marked as read', 'count' => $stmt->affected_rows]);
        } else {
            // Mark single notification as read
            $id = (int)($_GET['id'] ?? 0);
            if (!$id) sendError('Notification ID required');
            
            $stmt = $conn->prepare(
                'UPDATE notifications SET is_read = 1, read_at = NOW() 
                 WHERE id = ? AND user_id = ?'
            );
            $stmt->bind_param('ii', $id, $userId);
            
            if (!$stmt->execute()) sendError('Failed to update notification', 500);
            
            sendJson(['message' => 'Notification marked as read']);
        }
        break;
    
    // DELETE /notifications.php?id=1 - Delete notification
    case 'DELETE':
        if (!$userId) sendError('User ID required', 401);
        
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) sendError('Notification ID required');
        
        $stmt = $conn->prepare('DELETE FROM notifications WHERE id = ? AND user_id = ?');
        $stmt->bind_param('ii', $id, $userId);
        
        if (!$stmt->execute()) sendError('Failed to delete notification', 500);
        
        sendJson(['message' => 'Notification deleted']);
        break;
    
    default:
        sendError('Method not allowed', 405);
}

$conn->close();
