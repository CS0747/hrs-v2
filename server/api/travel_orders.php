<?php
require_once 'db.php';

$method = $_SERVER['REQUEST_METHOD'];
$conn   = getConnection();

switch ($method) {

    case 'GET':
        if (isset($_GET['id'])) {
            $id   = (int) $_GET['id'];
            $stmt = $conn->prepare('SELECT * FROM travel_orders WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $row ? sendJson($row) : sendError('Record not found', 404);
        } else {
            $result = $conn->query('SELECT * FROM travel_orders ORDER BY date_from DESC');
            sendJson($result->fetch_all(MYSQLI_ASSOC));
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) sendError('Invalid JSON body');

        $stmt = $conn->prepare(
            'INSERT INTO travel_orders
             (employee_id, employee_no, employee_name, department, destination,
              purpose, date_from, date_to, days, transport, approved_by, status, remarks)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)'
        );
        $stmt->bind_param(
            'isssssssdssss',
            $data['employee_id'],
            $data['employee_no'],
            $data['employee_name'],
            $data['department'],
            $data['destination'],
            $data['purpose'],
            $data['date_from'],
            $data['date_to'],
            $data['days'],
            $data['transport'],
            $data['approved_by'],
            $data['status'],
            $data['remarks']
        );
        $stmt->execute();
        sendJson(['id' => $conn->insert_id, 'message' => 'Travel order created'], 201);
        break;

    case 'PUT':
        $id   = (int) ($_GET['id'] ?? 0);
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$id || !$data) sendError('Invalid request');

        $stmt = $conn->prepare(
            'UPDATE travel_orders SET
             employee_id=?, employee_no=?, employee_name=?, department=?, destination=?,
             purpose=?, date_from=?, date_to=?, days=?, transport=?,
             approved_by=?, status=?, remarks=?
             WHERE id=?'
        );
        $stmt->bind_param(
            'isssssssdssssi',
            $data['employee_id'],
            $data['employee_no'],
            $data['employee_name'],
            $data['department'],
            $data['destination'],
            $data['purpose'],
            $data['date_from'],
            $data['date_to'],
            $data['days'],
            $data['transport'],
            $data['approved_by'],
            $data['status'],
            $data['remarks'],
            $id
        );
        $stmt->execute();
        sendJson(['message' => 'Travel order updated']);
        break;

    case 'DELETE':
        $id = (int) ($_GET['id'] ?? 0);
        if (!$id) sendError('ID required');
        $stmt = $conn->prepare('DELETE FROM travel_orders WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        sendJson(['message' => 'Travel order deleted']);
        break;

    default:
        sendError('Method not allowed', 405);
}

$conn->close();
