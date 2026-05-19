<?php
require_once 'db.php';
require_once 'notification_helpers.php';

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
if (!checkPermission($conn, $userId, 'Employee Masterlist', $action)) {
    denyAccess('Employee Masterlist', $action);
}

switch ($method) {

    case 'GET':
        if (isset($_GET['id'])) {
            $id   = (int) $_GET['id'];
            $stmt = $conn->prepare('SELECT * FROM employees WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $row ? sendJson($row) : sendError('Employee not found', 404);
        } else {
            $result = $conn->query('SELECT * FROM employees ORDER BY last_name, first_name');
            $rows   = $result->fetch_all(MYSQLI_ASSOC);
            sendJson($rows);
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) sendError('Invalid JSON body');

        $employee_no       = $data['employeeNo']       ?? $data['employee_no']       ?? '';
        $last_name         = $data['lastName']         ?? $data['last_name']         ?? '';
        $first_name        = $data['firstName']        ?? $data['first_name']        ?? '';
        $middle_name       = $data['middleName']       ?? $data['middle_name']       ?? '';
        $position          = $data['position']         ?? '';
        $designation       = $data['designation']      ?? '';
        $department        = $data['department']       ?? '';
        $employment_status = $data['employmentStatus'] ?? $data['employment_status'] ?? 'Casual';
        $date_hired        = ($data['dateHired']       ?? $data['date_hired']        ?? '') ?: null;
        $birth_date        = ($data['birthDate']       ?? $data['birth_date']        ?? '') ?: null;
        $age               = (int)($data['age']        ?? 0);
        $gender            = $data['gender']           ?? '';
        $civil_status      = $data['civilStatus']      ?? $data['civil_status']      ?? '';
        $address           = $data['address']          ?? '';
        $contact_no        = $data['contactNo']        ?? $data['contact_no']        ?? '';
        $email             = $data['email']            ?? '';
        $salary            = (float)($data['salary']   ?? 0);
        $sg_step           = $data['sgStep']           ?? $data['sg_step']           ?? '';
        $tin_number        = $data['tin']              ?? $data['tin_number']        ?? '';
        $sss_gsis_number   = $data['sss']              ?? $data['sss_gsis_number']   ?? '';
        $phil_number       = $data['philhealth']       ?? $data['phil_number']       ?? '';
        $pi_number         = $data['pagibig']          ?? $data['pi_number']         ?? '';
        $active            = (int)($data['active']     ?? 1);

        // 23 params: s×10, i, s×5, d, s×5, i
        $stmt = $conn->prepare(
            'INSERT INTO employees
             (employee_no, last_name, first_name, middle_name, position, designation,
              department, employment_status, date_hired, birth_date, age, gender,
              civil_status, address, contact_no, email, salary, sg_step,
              tin_number, sss_gsis_number, phil_number, pi_number, active)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
        );
        $stmt->bind_param('ssssssssssisssssdsssssi',
            $employee_no, $last_name, $first_name, $middle_name, $position,
            $designation, $department, $employment_status, $date_hired, $birth_date,
            $age, $gender, $civil_status, $address, $contact_no, $email,
            $salary, $sg_step, $tin_number, $sss_gsis_number, $phil_number,
            $pi_number, $active
        );

        if (!$stmt->execute()) sendError('Insert failed: ' . $stmt->error, 500);
        
        $employeeId = $conn->insert_id;
        $employeeName = "$first_name $last_name";
        
        // Notify admins about new employee
        notifyEmployeeAdded($conn, $employeeName, $employeeId);
        
        sendJson(['id' => $employeeId, 'message' => 'Employee created'], 201);
        break;

    case 'PUT':
        $id   = (int) ($_GET['id'] ?? 0);
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$id || !$data) sendError('Invalid request');

        $last_name         = $data['lastName']         ?? $data['last_name']         ?? '';
        $first_name        = $data['firstName']        ?? $data['first_name']        ?? '';
        $middle_name       = $data['middleName']       ?? $data['middle_name']       ?? '';
        $position          = $data['position']         ?? '';
        $designation       = $data['designation']      ?? '';
        $department        = $data['department']       ?? '';
        $employment_status = $data['employmentStatus'] ?? $data['employment_status'] ?? 'Casual';
        $date_hired        = ($data['dateHired']       ?? $data['date_hired']        ?? '') ?: null;
        $birth_date        = ($data['birthDate']       ?? $data['birth_date']        ?? '') ?: null;
        $age               = (int)($data['age']        ?? 0);
        $gender            = $data['gender']           ?? '';
        $civil_status      = $data['civilStatus']      ?? $data['civil_status']      ?? '';
        $address           = $data['address']          ?? '';
        $contact_no        = $data['contactNo']        ?? $data['contact_no']        ?? '';
        $email             = $data['email']            ?? '';
        $salary            = (float)($data['salary']   ?? 0);
        $sg_step           = $data['sgStep']           ?? $data['sg_step']           ?? '';
        $tin_number        = $data['tin']              ?? $data['tin_number']        ?? '';
        $sss_gsis_number   = $data['sss']              ?? $data['sss_gsis_number']   ?? '';
        $phil_number       = $data['philhealth']       ?? $data['phil_number']       ?? '';
        $pi_number         = $data['pagibig']          ?? $data['pi_number']         ?? '';
        $active            = (int)($data['active']     ?? 1);

        // 23 params: s×9, s(birth_date), i(age), s×5, d, s×5, i(active), i(id)
        // employee_no excluded from UPDATE to avoid UNIQUE constraint conflicts
        $stmt = $conn->prepare(
            'UPDATE employees SET
             last_name=?, first_name=?, middle_name=?, position=?, designation=?,
             department=?, employment_status=?, date_hired=?, birth_date=?,
             age=?, gender=?, civil_status=?, address=?, contact_no=?, email=?,
             salary=?, sg_step=?, tin_number=?, sss_gsis_number=?, phil_number=?,
             pi_number=?, active=?
             WHERE id=?'
        );
        // 23 params: s×9, i, s×5, d, s×5, i, i
        $stmt->bind_param('ssssssssssisssssdsssssi',
            $last_name, $first_name, $middle_name, $position, $designation,
            $department, $employment_status, $date_hired, $birth_date,
            $age, $gender, $civil_status, $address, $contact_no, $email,
            $salary, $sg_step, $tin_number, $sss_gsis_number, $phil_number,
            $pi_number, $active, $id
        );

        if (!$stmt->execute()) sendError('Update failed: ' . $stmt->error, 500);
        
        $employeeName = "$first_name $last_name";
        notifyEmployeeUpdated($conn, $employeeName, $id);
        
        sendJson(['message' => 'Employee updated']);
        break;

    case 'DELETE':
        $id = (int) ($_GET['id'] ?? 0);
        if (!$id) sendError('ID required');
        $stmt = $conn->prepare('DELETE FROM employees WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        sendJson(['message' => 'Employee deleted']);
        break;

    default:
        sendError('Method not allowed', 405);
}

$conn->close();
