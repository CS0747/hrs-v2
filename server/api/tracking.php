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
if (!checkPermission($conn, $userId, 'Tracking / Receiving', $action)) {
    denyAccess('Tracking / Receiving', $action);
}

switch ($method) {

    case 'GET':
        if (isset($_GET['id'])) {
            $id   = (int) $_GET['id'];
            $stmt = $conn->prepare('SELECT * FROM document_tracking WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $row ? sendJson($row) : sendError('Record not found', 404);
        } else {
            $result = $conn->query('SELECT * FROM document_tracking ORDER BY created_at DESC');
            sendJson($result->fetch_all(MYSQLI_ASSOC));
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) sendError('Invalid JSON body');

        $doc_type      = trim($data['doc_type']      ?? '');
        $doc_no        = trim($data['doc_no']        ?? '');
        $from_office   = trim($data['from_office']   ?? '');
        $to_office     = trim($data['to_office']     ?? '');
        $direction     = trim($data['direction']     ?? 'incoming'); // incoming | outgoing
        $date_forwarded = ($data['date_forwarded'] ?? '') ?: null;
        $date_received  = ($data['date_received']  ?? '') ?: null;
        $received_by   = trim($data['received_by']  ?? '');
        $status        = trim($data['status']        ?? 'Pending');
        $remarks       = trim($data['remarks']       ?? '');

        if (!$doc_type || !$doc_no) sendError('Document type and number are required');

        // Add direction column if it doesn't exist yet (safe migration)
        $conn->query("ALTER TABLE document_tracking ADD COLUMN IF NOT EXISTS `direction` ENUM('incoming','outgoing') NOT NULL DEFAULT 'incoming'");

        $stmt = $conn->prepare(
            'INSERT INTO document_tracking
             (doc_type, doc_no, from_office, to_office, direction,
              date_forwarded, date_received, received_by, status, remarks)
             VALUES (?,?,?,?,?,?,?,?,?,?)'
        );
        $stmt->bind_param(
            'ssssssssss',
            $doc_type, $doc_no, $from_office, $to_office, $direction,
            $date_forwarded, $date_received, $received_by, $status, $remarks
        );

        if (!$stmt->execute()) sendError('Insert failed: ' . $stmt->error, 500);
        sendJson(['id' => $conn->insert_id, 'message' => 'Tracking record created'], 201);
        break;

    case 'PUT':
        $id   = (int) ($_GET['id'] ?? 0);
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$id || !$data) sendError('Invalid request');

        $doc_type      = trim($data['doc_type']      ?? '');
        $doc_no        = trim($data['doc_no']        ?? '');
        $from_office   = trim($data['from_office']   ?? '');
        $to_office     = trim($data['to_office']     ?? '');
        $direction     = trim($data['direction']     ?? 'incoming');
        $date_forwarded = ($data['date_forwarded'] ?? '') ?: null;
        $date_received  = ($data['date_received']  ?? '') ?: null;
        $received_by   = trim($data['received_by']  ?? '');
        $status        = trim($data['status']        ?? 'Pending');
        $remarks       = trim($data['remarks']       ?? '');

        // Add direction column if it doesn't exist yet (safe migration)
        $conn->query("ALTER TABLE document_tracking ADD COLUMN IF NOT EXISTS `direction` ENUM('incoming','outgoing') NOT NULL DEFAULT 'incoming'");

        $stmt = $conn->prepare(
            'UPDATE document_tracking SET
             doc_type=?, doc_no=?, from_office=?, to_office=?, direction=?,
             date_forwarded=?, date_received=?, received_by=?, status=?, remarks=?
             WHERE id=?'
        );
        $stmt->bind_param(
            'ssssssssssi',
            $doc_type, $doc_no, $from_office, $to_office, $direction,
            $date_forwarded, $date_received, $received_by, $status, $remarks,
            $id
        );

        if (!$stmt->execute()) sendError('Update failed: ' . $stmt->error, 500);
        sendJson(['message' => 'Tracking record updated']);
        break;

    case 'DELETE':
        $id = (int) ($_GET['id'] ?? 0);
        if (!$id) sendError('ID required');

        $stmt = $conn->prepare('DELETE FROM document_tracking WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        sendJson(['message' => 'Tracking record deleted']);
        break;

    default:
        sendError('Method not allowed', 405);
}

$conn->close();
