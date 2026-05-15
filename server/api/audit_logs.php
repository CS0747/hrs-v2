<?php
require_once 'db.php';

$method = $_SERVER['REQUEST_METHOD'];
$conn   = getConnection();
$userId = (int)($_SERVER['HTTP_X_USER_ID'] ?? 0);

// Map HTTP methods to actions
$actionMap = [
    'GET'    => 'View',
    'POST'   => 'Add',
    'PUT'    => 'Edit',
    'DELETE' => 'Delete',
];
$action = $actionMap[$method] ?? 'View';

// Check permission before processing request
if (!checkPermission($conn, $userId, 'Audit History', $action)) {
    denyAccess('Audit History', $action);
}

switch ($method) {

    // GET /audit_logs.php
    // ?limit=200          → max rows (default 500)
    // ?module=Employee    → filter by module
    // ?action_type=UPDATE → filter by action type
    // ?user=John          → filter by user name (LIKE)
    // ?from=2026-01-01    → date range start
    // ?to=2026-12-31      → date range end
    // ?search=keyword     → full-text search in action/details
    case 'GET':
        $where  = ['archived = 0'];
        $params = [];
        $types  = '';

        if (!empty($_GET['module'])) {
            $where[]  = 'module = ?';
            $params[] = $_GET['module'];
            $types   .= 's';
        }
        if (!empty($_GET['action_type'])) {
            $where[]  = 'action_type = ?';
            $params[] = $_GET['action_type'];
            $types   .= 's';
        }
        if (!empty($_GET['user'])) {
            $where[]  = 'user_name LIKE ?';
            $params[] = '%' . $_GET['user'] . '%';
            $types   .= 's';
        }
        if (!empty($_GET['from'])) {
            $where[]  = 'DATE(created_at) >= ?';
            $params[] = $_GET['from'];
            $types   .= 's';
        }
        if (!empty($_GET['to'])) {
            $where[]  = 'DATE(created_at) <= ?';
            $params[] = $_GET['to'];
            $types   .= 's';
        }
        if (!empty($_GET['search'])) {
            $where[]  = '(action LIKE ? OR details LIKE ? OR user_name LIKE ?)';
            $like     = '%' . $_GET['search'] . '%';
            $params   = array_merge($params, [$like, $like, $like]);
            $types   .= 'sss';
        }

        $limit = min((int)($_GET['limit'] ?? 500), 2000);
        $sql   = 'SELECT * FROM audit_logs'
               . (count($where) ? ' WHERE ' . implode(' AND ', $where) : '')
               . ' ORDER BY created_at DESC LIMIT ' . $limit;

        if ($params) {
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } else {
            $rows = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
        }

        // Decode JSON columns
        foreach ($rows as &$r) {
            $r['old_values'] = $r['old_values'] ? json_decode($r['old_values'], true) : null;
            $r['new_values'] = $r['new_values'] ? json_decode($r['new_values'], true) : null;
        }
        unset($r);

        sendJson($rows);
        break;

    // POST /audit_logs.php → create a log entry
    // Body: {
    //   user_id, user_name, action, action_type,
    //   module, affected_table, record_id,
    //   old_values, new_values, details, status, ip_address
    // }
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) sendError('Invalid JSON body');

        $user_id        = isset($data['user_id']) ? (int)$data['user_id'] : null;
        $user_name      = $data['user_name']      ?? 'System';
        $action         = $data['action']         ?? '';
        $action_type    = $data['action_type']    ?? 'OTHER';
        $module         = $data['module']         ?? 'Other';
        $affected_table = $data['affected_table'] ?? null;
        $record_id      = isset($data['record_id']) ? (int)$data['record_id'] : null;
        $old_values     = isset($data['old_values']) ? json_encode($data['old_values']) : null;
        $new_values     = isset($data['new_values']) ? json_encode($data['new_values']) : null;
        $details        = $data['details']        ?? null;
        $status         = $data['status']         ?? 'OK';
        $ip_address     = $data['ip_address']     ?? ($_SERVER['REMOTE_ADDR'] ?? null);

        $stmt = $conn->prepare(
            'INSERT INTO audit_logs
             (user_id, user_name, action, action_type, module,
              affected_table, record_id, old_values, new_values,
              details, status, ip_address)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?)'
        );
        $stmt->bind_param('issssssissss',
            $user_id, $user_name, $action, $action_type, $module,
            $affected_table, $record_id, $old_values, $new_values,
            $details, $status, $ip_address
        );

        if (!$stmt->execute()) sendError('Insert failed: ' . $stmt->error, 500);
        sendJson(['id' => $conn->insert_id, 'message' => 'Log entry created'], 201);
        break;

    // PUT /audit_logs.php?id=1 → archive / unarchive
    case 'PUT':
        $id   = (int)($_GET['id'] ?? 0);
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$id || !$data) sendError('Invalid request');

        $archived = (int)($data['archived'] ?? 1);
        $stmt = $conn->prepare('UPDATE audit_logs SET archived=? WHERE id=?');
        $stmt->bind_param('ii', $archived, $id);
        $stmt->execute();
        sendJson(['message' => 'Log entry updated']);
        break;

    // DELETE /audit_logs.php?purge=1 → hard-delete archived logs (Super Admin only)
    case 'DELETE':
        if (!isset($_GET['purge'])) sendError('Use ?purge=1 to confirm deletion', 400);
        $conn->query('DELETE FROM audit_logs WHERE archived = 1');
        sendJson(['message' => 'Archived logs purged', 'affected' => $conn->affected_rows]);
        break;

    default:
        sendError('Method not allowed', 405);
}

$conn->close();
