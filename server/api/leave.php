<?php
require_once 'db.php';
require_once 'cors.php';

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
if (!checkPermission($conn, $userId, 'Leave Management', $action)) {
    denyAccess('Leave Management', $action);
}

switch ($method) {

    case 'GET':
        if (isset($_GET['id'])) {
            $id   = (int) $_GET['id'];
            $stmt = $conn->prepare('SELECT * FROM leave_records WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            if ($row) {
                // Map snake_case to camelCase for frontend
                $mapped = [
                    'id' => (int)$row['id'],
                    'employeeId' => $row['employee_id'] ? (int)$row['employee_id'] : null,
                    'employeeNo' => $row['employee_no'],
                    'employeeName' => $row['employee_name'],
                    'department' => $row['department'],
                    'leaveType' => $row['leave_type'],
                    'dateFrom' => $row['date_from'],
                    'dateTo' => $row['date_to'],
                    'days' => (float)$row['days'],
                    'reason' => $row['reason'],
                    'status' => $row['status'],
                    'approvedBy' => $row['approved_by'],
                    'dateApproved' => $row['date_approved'],
                    'remarks' => $row['remarks']
                ];
                sendJsonCamelCase($mapped);
            } else {
                sendError('Record not found', 404);
            }
        } else {
            $result = $conn->query('SELECT * FROM leave_records ORDER BY date_from DESC');
            $rows = $result->fetch_all(MYSQLI_ASSOC);
            // Map all rows to camelCase
            $mapped = array_map(function($row) {
                return [
                    'id' => (int)$row['id'],
                    'employeeId' => $row['employee_id'] ? (int)$row['employee_id'] : null,
                    'employeeNo' => $row['employee_no'],
                    'employeeName' => $row['employee_name'],
                    'department' => $row['department'],
                    'leaveType' => $row['leave_type'],
                    'dateFrom' => $row['date_from'],
                    'dateTo' => $row['date_to'],
                    'days' => (float)$row['days'],
                    'reason' => $row['reason'],
                    'status' => $row['status'],
                    'approvedBy' => $row['approved_by'],
                    'dateApproved' => $row['date_approved'],
                    'remarks' => $row['remarks']
                ];
            }, $rows);
            sendJsonCamelCase($mapped);
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
        sendJsonCamelCase(['id' => $conn->insert_id, 'message' => 'Leave record created'], 201);
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
        sendJsonCamelCase(['message' => 'Leave record updated']);
        break;

    case 'DELETE':
        $id = (int) ($_GET['id'] ?? 0);
        if (!$id) sendError('ID required');
        $stmt = $conn->prepare('DELETE FROM leave_records WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        sendJsonCamelCase(['message' => 'Leave record deleted']);
        break;

    default:
        sendError('Method not allowed', 405);
}

$conn->close();
