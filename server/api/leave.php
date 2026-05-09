<?php
require_once 'db.php';

$method = $_SERVER['REQUEST_METHOD'];
$conn   = getConnection();

switch ($method) {

    case 'GET':
        if (isset($_GET['id'])) {
            $id   = (int) $_GET['id'];
            $stmt = $conn->prepare('SELECT * FROM leave_records WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $row ? sendJson($row) : sendError('Record not found', 404);
        } else {
            $result = $conn->query('SELECT * FROM leave_records ORDER BY date_from DESC');
            sendJson($result->fetch_all(MYSQLI_ASSOC));
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) sendError('Invalid JSON body');

        $stmt = $conn->prepare(
            'INSERT INTO leave_records
             (employee_id, employee_no, employee_name, department, leave_type,
              date_from, date_to, days, reason, status, approved_by, date_approved, remarks)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)'
        );
        $stmt->bind_param(
            'issssssdsssss',
            $data['employee_id'],
            $data['employee_no'],
            $data['employee_name'],
            $data['department'],
            $data['leave_type'],
            $data['date_from'],
            $data['date_to'],
            $data['days'],
            $data['reason'],
            $data['status'],
            $data['approved_by'],
            $data['date_approved'],
            $data['remarks']
        );
        $stmt->execute();
        sendJson(['id' => $conn->insert_id, 'message' => 'Leave record created'], 201);
        break;

    case 'PUT':
        $id   = (int) ($_GET['id'] ?? 0);
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$id || !$data) sendError('Invalid request');

        $stmt = $conn->prepare(
            'UPDATE leave_records SET
             employee_id=?, employee_no=?, employee_name=?, department=?, leave_type=?,
             date_from=?, date_to=?, days=?, reason=?, status=?,
             approved_by=?, date_approved=?, remarks=?
             WHERE id=?'
        );
        $stmt->bind_param(
            'issssssdsssssi',
            $data['employee_id'],
            $data['employee_no'],
            $data['employee_name'],
            $data['department'],
            $data['leave_type'],
            $data['date_from'],
            $data['date_to'],
            $data['days'],
            $data['reason'],
            $data['status'],
            $data['approved_by'],
            $data['date_approved'],
            $data['remarks'],
            $id
        );
        $stmt->execute();
        sendJson(['message' => 'Leave record updated']);
        break;

    case 'DELETE':
        $id = (int) ($_GET['id'] ?? 0);
        if (!$id) sendError('ID required');
        $stmt = $conn->prepare('DELETE FROM leave_records WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        sendJson(['message' => 'Leave record deleted']);
        break;

    default:
        sendError('Method not allowed', 405);
}

$conn->close();
