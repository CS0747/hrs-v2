<?php
require_once 'db.php';

$method = $_SERVER['REQUEST_METHOD'];
$conn   = getConnection();

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
            $result = $conn->query('SELECT * FROM document_tracking ORDER BY date_forwarded DESC');
            sendJson($result->fetch_all(MYSQLI_ASSOC));
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) sendError('Invalid JSON body');

        $stmt = $conn->prepare(
            'INSERT INTO document_tracking
             (doc_type, doc_no, from_office, to_office, date_forwarded,
              date_received, received_by, status, remarks)
             VALUES (?,?,?,?,?,?,?,?,?)'
        );
        $stmt->bind_param(
            'sssssssss',
            $data['doc_type'],
            $data['doc_no'],
            $data['from_office'],
            $data['to_office'],
            $data['date_forwarded'],
            $data['date_received'],
            $data['received_by'],
            $data['status'],
            $data['remarks']
        );
        $stmt->execute();
        sendJson(['id' => $conn->insert_id, 'message' => 'Tracking record created'], 201);
        break;

    case 'PUT':
        $id   = (int) ($_GET['id'] ?? 0);
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$id || !$data) sendError('Invalid request');

        $stmt = $conn->prepare(
            'UPDATE document_tracking SET
             doc_type=?, doc_no=?, from_office=?, to_office=?, date_forwarded=?,
             date_received=?, received_by=?, status=?, remarks=?
             WHERE id=?'
        );
        $stmt->bind_param(
            'sssssssss i',
            $data['doc_type'],
            $data['doc_no'],
            $data['from_office'],
            $data['to_office'],
            $data['date_forwarded'],
            $data['date_received'],
            $data['received_by'],
            $data['status'],
            $data['remarks'],
            $id
        );
        $stmt->execute();
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
