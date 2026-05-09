<?php
require_once 'db.php';

$method = $_SERVER['REQUEST_METHOD'];
$conn   = getConnection();

switch ($method) {

    // GET /schedule.php          -> all schedules
    // GET /schedule.php?id=1     -> single record
    // GET /schedule.php?emp=GEAMH-001 -> by employee no
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
        } else {
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
