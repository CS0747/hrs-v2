<?php
require_once 'db.php';

$method = $_SERVER['REQUEST_METHOD'];
$conn   = getConnection();

switch ($method) {

    case 'GET':
        $result = $conn->query('SELECT * FROM signatories ORDER BY signing_order ASC');
        sendJson($result->fetch_all(MYSQLI_ASSOC));
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) sendError('Invalid JSON body');

        $stmt = $conn->prepare(
            'INSERT INTO signatories (name, position, role, department, signing_order, active)
             VALUES (?,?,?,?,?,?)'
        );
        $active = isset($data['active']) ? (int)$data['active'] : 1;
        $stmt->bind_param(
            'ssssis',
            $data['name'],
            $data['position'],
            $data['role'],
            $data['department'],
            $data['signing_order'],
            $active
        );
        $stmt->execute();
        sendJson(['id' => $conn->insert_id, 'message' => 'Signatory created'], 201);
        break;

    case 'PUT':
        $id   = (int) ($_GET['id'] ?? 0);
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$id || !$data) sendError('Invalid request');

        $active = isset($data['active']) ? (int)$data['active'] : 1;
        $stmt = $conn->prepare(
            'UPDATE signatories SET name=?, position=?, role=?, department=?, signing_order=?, active=?
             WHERE id=?'
        );
        $stmt->bind_param(
            'sssssii',
            $data['name'],
            $data['position'],
            $data['role'],
            $data['department'],
            $data['signing_order'],
            $active,
            $id
        );
        $stmt->execute();
        sendJson(['message' => 'Signatory updated']);
        break;

    case 'DELETE':
        $id = (int) ($_GET['id'] ?? 0);
        if (!$id) sendError('ID required');
        $stmt = $conn->prepare('DELETE FROM signatories WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        sendJson(['message' => 'Signatory deleted']);
        break;

    default:
        sendError('Method not allowed', 405);
}

$conn->close();
