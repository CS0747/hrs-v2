<?php
require_once 'db.php';

$method = $_SERVER['REQUEST_METHOD'];
$conn   = getConnection();

switch ($method) {

    case 'GET':
        if (isset($_GET['id'])) {
            $id   = (int) $_GET['id'];
            $stmt = $conn->prepare('SELECT * FROM payroll_records WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $row ? sendJson($row) : sendError('Record not found', 404);
        } else {
            $result = $conn->query('SELECT * FROM payroll_records ORDER BY period DESC, employee_name ASC');
            sendJson($result->fetch_all(MYSQLI_ASSOC));
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) sendError('Invalid JSON body');

        $stmt = $conn->prepare(
            'INSERT INTO payroll_records
             (employee_id, employee_no, employee_name, position, department,
              period, period_label, basic_salary, pera, rata, overtime, night_diff,
              gross_pay, withholding_tax, gsis, philhealth, pagibig,
              total_deductions, net_pay, status, remarks)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
        );
        $stmt->bind_param(
            'issssssdddddddddddds s',
            $data['employee_id'],
            $data['employee_no'],
            $data['employee_name'],
            $data['position'],
            $data['department'],
            $data['period'],
            $data['period_label'],
            $data['basic_salary'],
            $data['pera'],
            $data['rata'],
            $data['overtime'],
            $data['night_diff'],
            $data['gross_pay'],
            $data['withholding_tax'],
            $data['gsis'],
            $data['philhealth'],
            $data['pagibig'],
            $data['total_deductions'],
            $data['net_pay'],
            $data['status'],
            $data['remarks']
        );
        $stmt->execute();
        sendJson(['id' => $conn->insert_id, 'message' => 'Payroll record created'], 201);
        break;

    case 'PUT':
        $id   = (int) ($_GET['id'] ?? 0);
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$id || !$data) sendError('Invalid request');

        $stmt = $conn->prepare(
            'UPDATE payroll_records SET
             employee_id=?, employee_no=?, employee_name=?, position=?, department=?,
             period=?, period_label=?, basic_salary=?, pera=?, rata=?, overtime=?, night_diff=?,
             gross_pay=?, withholding_tax=?, gsis=?, philhealth=?, pagibig=?,
             total_deductions=?, net_pay=?, status=?, remarks=?
             WHERE id=?'
        );
        $stmt->bind_param(
            'issssssddddddddddddss i',
            $data['employee_id'],
            $data['employee_no'],
            $data['employee_name'],
            $data['position'],
            $data['department'],
            $data['period'],
            $data['period_label'],
            $data['basic_salary'],
            $data['pera'],
            $data['rata'],
            $data['overtime'],
            $data['night_diff'],
            $data['gross_pay'],
            $data['withholding_tax'],
            $data['gsis'],
            $data['philhealth'],
            $data['pagibig'],
            $data['total_deductions'],
            $data['net_pay'],
            $data['status'],
            $data['remarks'],
            $id
        );
        $stmt->execute();
        sendJson(['message' => 'Payroll record updated']);
        break;

    case 'DELETE':
        $id = (int) ($_GET['id'] ?? 0);
        if (!$id) sendError('ID required');
        $stmt = $conn->prepare('DELETE FROM payroll_records WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        sendJson(['message' => 'Payroll record deleted']);
        break;

    default:
        sendError('Method not allowed', 405);
}

$conn->close();
