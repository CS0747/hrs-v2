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
if (!checkPermission($conn, $userId, 'DTR Transmittal', $action)) {
    denyAccess('DTR Transmittal', $action);
}

switch ($method) {

    // GET /dtr.php              → all records
    // GET /dtr.php?id=1         → single record
    // GET /dtr.php?history=1    → transmittal history log
    case 'GET':
        if (isset($_GET['history'])) {
            $where  = ['1=1'];
            $params = [];
            $types  = '';

            if (!empty($_GET['employee_no'])) {
                $where[]  = 'employee_no = ?';
                $params[] = $_GET['employee_no'];
                $types   .= 's';
            }
            if (!empty($_GET['status'])) {
                $where[]  = 'status = ?';
                $params[] = $_GET['status'];
                $types   .= 's';
            }
            if (!empty($_GET['search'])) {
                $like     = '%' . $_GET['search'] . '%';
                $where[]  = '(employee_name LIKE ? OR employee_no LIKE ?)';
                $params   = array_merge($params, [$like, $like]);
                $types   .= 'ss';
            }

            $sql = 'SELECT * FROM dtr_history WHERE ' . implode(' AND ', $where)
                 . ' ORDER BY created_at DESC LIMIT 500';

            if ($params) {
                $stmt = $conn->prepare($sql);
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            } else {
                $rows = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
            }
            sendJsonCamelCase($rows);
            break;
        }

        if (isset($_GET['id'])) {
            $id   = (int) $_GET['id'];
            $stmt = $conn->prepare('SELECT * FROM dtr_records WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $row ? sendJsonCamelCase($row) : sendError('Record not found', 404);
        } else {
            $where  = ['1=1'];
            $params = [];
            $types  = '';

            if (!empty($_GET['transmittal_type'])) {
                $where[]  = 'transmittal_type = ?';
                $params[] = $_GET['transmittal_type'];
                $types   .= 's';
            }
            if (!empty($_GET['status'])) {
                $where[]  = 'status = ?';
                $params[] = $_GET['status'];
                $types   .= 's';
            }
            if (!empty($_GET['search'])) {
                $like     = '%' . $_GET['search'] . '%';
                $where[]  = '(employee_name LIKE ? OR employee_no LIKE ?)';
                $params   = array_merge($params, [$like, $like]);
                $types   .= 'ss';
            }

            $sql = 'SELECT * FROM dtr_records WHERE ' . implode(' AND ', $where)
                 . ' ORDER BY date_submitted DESC, id DESC';

            if ($params) {
                $stmt = $conn->prepare($sql);
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            } else {
                $rows = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
            }
            sendJsonCamelCase($rows);
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) sendError('Invalid JSON body');

        $employee_id       = isset($data['employee_id'])       ? (int)$data['employee_id']       : null;
        $employee_no       = $data['employeeNo']       ?? $data['employee_no']       ?? '';
        $employee_name     = $data['employeeName']     ?? $data['employee_name']     ?? '';
        $department        = $data['department']       ?? '';
        $period            = $data['period']           ?? '';
        $transmittal_type  = $data['transmittalType']  ?? $data['transmittal_type']  ?? 'Main';
        $submitted_by      = $data['submittedBy']      ?? $data['submitted_by']      ?? '';
        $date_submitted    = ($data['dateSubmitted']   ?? $data['date_submitted']    ?? '') ?: null;
        $date_received     = ($data['dateReceived']    ?? $data['date_received']     ?? '') ?: null;
        $verified_by       = $data['verifiedBy']       ?? $data['verified_by']       ?? '';
        $verification_date = ($data['verificationDate']?? $data['verification_date'] ?? '') ?: null;
        $status            = $data['status']           ?? 'Pending';
        $remarks           = $data['remarks']          ?? '';
        $processed_by      = $data['processedBy']      ?? $data['processed_by']      ?? 'System';

        $stmt = $conn->prepare(
            'INSERT INTO dtr_records
             (employee_id, employee_no, employee_name, department, period,
              transmittal_type, submitted_by, date_submitted, date_received,
              verified_by, verification_date, status, remarks)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)'
        );
        $stmt->bind_param('isssssssssss s',
            $employee_id, $employee_no, $employee_name, $department, $period,
            $transmittal_type, $submitted_by, $date_submitted, $date_received,
            $verified_by, $verification_date, $status, $remarks
        );

        // Fix: no space in bind_param
        $stmt = $conn->prepare(
            'INSERT INTO dtr_records
             (employee_id, employee_no, employee_name, department, period,
              transmittal_type, submitted_by, date_submitted, date_received,
              verified_by, verification_date, status, remarks)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)'
        );
        $stmt->bind_param('issssssssssss',
            $employee_id, $employee_no, $employee_name, $department, $period,
            $transmittal_type, $submitted_by, $date_submitted, $date_received,
            $verified_by, $verification_date, $status, $remarks
        );

        if (!$stmt->execute()) sendError('Insert failed: ' . $stmt->error, 500);
        $new_id = $conn->insert_id;

        // Log to dtr_history
        $action = 'DTR Submitted';
        $hstmt = $conn->prepare(
            'INSERT INTO dtr_history (dtr_record_id, employee_no, employee_name, period, transmittal_type, action, status, remarks, processed_by)
             VALUES (?,?,?,?,?,?,?,?,?)'
        );
        $hstmt->bind_param('issssssss',
            $new_id, $employee_no, $employee_name, $period, $transmittal_type,
            $action, $status, $remarks, $processed_by
        );
        $hstmt->execute();

        sendJsonCamelCase(['id' => $new_id, 'message' => 'DTR record created'], 201);
        break;

    case 'PUT':
        $id   = (int) ($_GET['id'] ?? 0);
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$id || !$data) sendError('Invalid request');

        $employee_id       = isset($data['employee_id'])       ? (int)$data['employee_id']       : null;
        $employee_no       = $data['employeeNo']       ?? $data['employee_no']       ?? '';
        $employee_name     = $data['employeeName']     ?? $data['employee_name']     ?? '';
        $department        = $data['department']       ?? '';
        $period            = $data['period']           ?? '';
        $transmittal_type  = $data['transmittalType']  ?? $data['transmittal_type']  ?? 'Main';
        $submitted_by      = $data['submittedBy']      ?? $data['submitted_by']      ?? '';
        $date_submitted    = ($data['dateSubmitted']   ?? $data['date_submitted']    ?? '') ?: null;
        $date_received     = ($data['dateReceived']    ?? $data['date_received']     ?? '') ?: null;
        $verified_by       = $data['verifiedBy']       ?? $data['verified_by']       ?? '';
        $verification_date = ($data['verificationDate']?? $data['verification_date'] ?? '') ?: null;
        $status            = $data['status']           ?? 'Pending';
        $remarks           = $data['remarks']          ?? '';
        $processed_by      = $data['processedBy']      ?? $data['processed_by']      ?? 'System';

        $stmt = $conn->prepare(
            'UPDATE dtr_records SET
             employee_id=?, employee_no=?, employee_name=?, department=?, period=?,
             transmittal_type=?, submitted_by=?, date_submitted=?, date_received=?,
             verified_by=?, verification_date=?, status=?, remarks=?
             WHERE id=?'
        );
        $stmt->bind_param('issssssssssssi',
            $employee_id, $employee_no, $employee_name, $department, $period,
            $transmittal_type, $submitted_by, $date_submitted, $date_received,
            $verified_by, $verification_date, $status, $remarks, $id
        );

        if (!$stmt->execute()) sendError('Update failed: ' . $stmt->error, 500);

        // Log to dtr_history
        $action = 'DTR Updated';
        $hstmt = $conn->prepare(
            'INSERT INTO dtr_history (dtr_record_id, employee_no, employee_name, period, transmittal_type, action, status, remarks, processed_by)
             VALUES (?,?,?,?,?,?,?,?,?)'
        );
        $hstmt->bind_param('issssssss',
            $id, $employee_no, $employee_name, $period, $transmittal_type,
            $action, $status, $remarks, $processed_by
        );
        $hstmt->execute();

        sendJsonCamelCase(['message' => 'DTR record updated']);
        break;

    case 'DELETE':
        $id = (int) ($_GET['id'] ?? 0);
        if (!$id) sendError('ID required');

        // Fetch before delete for history log
        $stmt = $conn->prepare("SELECT * FROM dtr_records WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        $stmt = $conn->prepare('DELETE FROM dtr_records WHERE id = ?');
        $stmt->bind_param('i', $id);
        if (!$stmt->execute()) sendError('Delete failed: ' . $stmt->error, 500);

        // Log deletion
        if ($row) {
            $processed_by = $_GET['processed_by'] ?? 'System';
            $action = 'DTR Deleted';
            $hstmt = $conn->prepare(
                'INSERT INTO dtr_history (dtr_record_id, employee_no, employee_name, period, transmittal_type, action, status, remarks, processed_by)
                 VALUES (?,?,?,?,?,?,?,?,?)'
            );
            $hstmt->bind_param('issssssss',
                $id, $row['employee_no'], $row['employee_name'], $row['period'],
                $row['transmittal_type'], $action, $row['status'], $row['remarks'], $processed_by
            );
            $hstmt->execute();
        }

        sendJsonCamelCase(['message' => 'DTR record deleted']);
        break;

    default:
        sendError('Method not allowed', 405);
}

$conn->close();
