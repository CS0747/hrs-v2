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
if (!checkPermission($conn, $userId, 'Schedule Database', $action)) {
    denyAccess('Schedule Database', $action);
}

switch ($method) {

    // GET /schedule.php          -> all schedules (grouped by employee)
    // GET /schedule.php?id=1     -> single record
    // GET /schedule.php?emp=GEAMH-001 -> by employee no
    // GET /schedule.php?grouped=1 -> grouped by employee with latest schedule first
    case 'GET':
        if (isset($_GET['id'])) {
            $id   = (int) $_GET['id'];
            $stmt = $conn->prepare('SELECT * FROM schedules WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            if (!$row) sendError('Schedule not found', 404);
            $row['days'] = json_decode($row['days'] ?? '[]');
            sendJson($row);
        } elseif (isset($_GET['emp'])) {
            $emp  = $_GET['emp'];
            $stmt = $conn->prepare(
                'SELECT * FROM schedules WHERE employee_no = ? ORDER BY effective_date DESC'
            );
            $stmt->bind_param('s', $emp);
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            foreach ($rows as &$r) $r['days'] = json_decode($r['days'] ?? '[]');
            sendJson($rows);
        } elseif (isset($_GET['grouped'])) {
            // Group schedules by employee, showing latest schedule first per employee
            $result = $conn->query(
                'SELECT s1.* FROM schedules s1
                 INNER JOIN (
                     SELECT employee_no, MAX(effective_date) as max_date
                     FROM schedules
                     GROUP BY employee_no
                 ) s2 ON s1.employee_no = s2.employee_no AND s1.effective_date = s2.max_date
                 ORDER BY s1.employee_name, s1.effective_date DESC'
            );
            $rows = $result->fetch_all(MYSQLI_ASSOC);
            foreach ($rows as &$r) $r['days'] = json_decode($r['days'] ?? '[]');
            sendJson($rows);
        } else {
            // Default: return all schedules ordered by employee name and effective date
            $result = $conn->query(
                'SELECT * FROM schedules ORDER BY employee_name, effective_date DESC'
            );
            $rows = $result->fetch_all(MYSQLI_ASSOC);
            foreach ($rows as &$r) $r['days'] = json_decode($r['days'] ?? '[]');
            sendJson($rows);
        }
        break;

    // POST /schedule.php -> create
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) sendError('Invalid JSON body');

        $employee_id   = (int)($data['employeeId']    ?? 0) ?: null;
        $employee_no   = $data['employeeNo']           ?? '';
        $employee_name = $data['employeeName']         ?? '';
        $department    = $data['department']           ?? '';
        $shift         = $data['shift']                ?? 'Morning';
        $shift_time    = $data['shiftTime']            ?? '';
        $days          = json_encode($data['days']     ?? []);
        $effective_date = ($data['effectiveDate']      ?? '') ?: null;
        $end_date      = ($data['endDate']             ?? '') ?: null;
        $rest_day      = $data['restDay']              ?? '';

        $stmt = $conn->prepare(
            'INSERT INTO schedules
             (employee_id, employee_no, employee_name, department, shift, shift_time,
              days, effective_date, end_date, rest_day)
             VALUES (?,?,?,?,?,?,?,?,?,?)'
        );
        $stmt->bind_param('isssssssss',
            $employee_id, $employee_no, $employee_name, $department,
            $shift, $shift_time, $days, $effective_date, $end_date, $rest_day
        );

        if (!$stmt->execute()) sendError('Insert failed: ' . $stmt->error, 500);
        sendJson(['id' => $conn->insert_id, 'message' => 'Schedule created'], 201);
        break;

    // PUT /schedule.php?id=1 -> update
    case 'PUT':
        $id   = (int) ($_GET['id'] ?? 0);
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$id || !$data) sendError('Invalid request');

        $employee_id   = (int)($data['employeeId']    ?? 0) ?: null;
        $employee_no   = $data['employeeNo']           ?? '';
        $employee_name = $data['employeeName']         ?? '';
        $department    = $data['department']           ?? '';
        $shift         = $data['shift']                ?? 'Morning';
        $shift_time    = $data['shiftTime']            ?? '';
        $days          = json_encode($data['days']     ?? []);
        $effective_date = ($data['effectiveDate']      ?? '') ?: null;
        $end_date      = ($data['endDate']             ?? '') ?: null;
        $rest_day      = $data['restDay']              ?? '';

        $stmt = $conn->prepare(
            'UPDATE schedules SET
             employee_id=?, employee_no=?, employee_name=?, department=?,
             shift=?, shift_time=?, days=?, effective_date=?, end_date=?, rest_day=?
             WHERE id=?'
        );
        $stmt->bind_param('isssssssssi',
            $employee_id, $employee_no, $employee_name, $department,
            $shift, $shift_time, $days, $effective_date, $end_date, $rest_day, $id
        );

        if (!$stmt->execute()) sendError('Update failed: ' . $stmt->error, 500);
        sendJson(['message' => 'Schedule updated']);
        break;

    // DELETE /schedule.php?id=1
    case 'DELETE':
        $id = (int) ($_GET['id'] ?? 0);
        if (!$id) sendError('ID required');
        $stmt = $conn->prepare('DELETE FROM schedules WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        sendJson(['message' => 'Schedule deleted']);
        break;

    default:
        sendError('Method not allowed', 405);
}

$conn->close();
