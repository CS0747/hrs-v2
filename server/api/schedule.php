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
if (!checkPermission($conn, $userId, 'Schedule Database', $action)) {
    denyAccess('Schedule Database', $action);
}

switch ($method) {

    // GET /schedule.php          -> all schedules (filtered by department for Admin)
    // GET /schedule.php?id=1     -> single record
    // GET /schedule.php?emp=GEAMH-001 -> by employee no
    // GET /schedule.php?dept=Nursing -> by department
    // GET /schedule.php?date=2026-05-18 -> by specific date
    case 'GET':
        // Get user info to check role and department
        $userStmt = $conn->prepare('SELECT role, department FROM users WHERE id = ?');
        $userStmt->bind_param('i', $userId);
        $userStmt->execute();
        $userInfo = $userStmt->get_result()->fetch_assoc();
        $userRole = $userInfo['role'] ?? 'Admin';
        $userDept = $userInfo['department'] ?? null;
        
        if (isset($_GET['id'])) {
            $id   = (int) $_GET['id'];
            
            // For Admin users, check if schedule belongs to their department
            if ($userRole === 'Admin' && $userDept) {
                $stmt = $conn->prepare('SELECT * FROM schedules WHERE id = ? AND department = ?');
                $stmt->bind_param('is', $id, $userDept);
            } else {
                $stmt = $conn->prepare('SELECT * FROM schedules WHERE id = ?');
                $stmt->bind_param('i', $id);
            }
            
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            if (!$row) sendError('Schedule not found', 404);
            $row['days'] = json_decode($row['days'] ?? '[]');
            sendJson($row);
        } elseif (isset($_GET['emp'])) {
            $emp  = $_GET['emp'];
            
            // For Admin users, filter by department
            if ($userRole === 'Admin' && $userDept) {
                $stmt = $conn->prepare(
                    'SELECT * FROM schedules WHERE employee_no = ? AND department = ? ORDER BY schedule_date DESC, effective_date DESC'
                );
                $stmt->bind_param('ss', $emp, $userDept);
            } else {
                $stmt = $conn->prepare(
                    'SELECT * FROM schedules WHERE employee_no = ? ORDER BY schedule_date DESC, effective_date DESC'
                );
                $stmt->bind_param('s', $emp);
            }
            
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            foreach ($rows as &$r) $r['days'] = json_decode($r['days'] ?? '[]');
            sendJson($rows);
        } elseif (isset($_GET['dept'])) {
            $dept = $_GET['dept'];
            
            // For Admin users, only allow their own department
            if ($userRole === 'Admin' && $userDept && $dept !== $userDept) {
                sendError('Access denied: You can only view schedules from your department', 403);
            }
            
            $stmt = $conn->prepare(
                'SELECT * FROM schedules WHERE department = ? ORDER BY employee_name, schedule_date DESC'
            );
            $stmt->bind_param('s', $dept);
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            foreach ($rows as &$r) $r['days'] = json_decode($r['days'] ?? '[]');
            sendJson($rows);
        } elseif (isset($_GET['date'])) {
            $date = $_GET['date'];
            
            // For Admin users, filter by department
            if ($userRole === 'Admin' && $userDept) {
                $stmt = $conn->prepare(
                    'SELECT * FROM schedules WHERE schedule_date = ? AND department = ? ORDER BY employee_name'
                );
                $stmt->bind_param('ss', $date, $userDept);
            } else {
                $stmt = $conn->prepare(
                    'SELECT * FROM schedules WHERE schedule_date = ? ORDER BY employee_name'
                );
                $stmt->bind_param('s', $date);
            }
            
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            foreach ($rows as &$r) $r['days'] = json_decode($r['days'] ?? '[]');
            sendJson($rows);
        } else {
            // For Admin users, only show schedules from their department
            if ($userRole === 'Admin' && $userDept) {
                $stmt = $conn->prepare(
                    'SELECT * FROM schedules WHERE department = ? ORDER BY employee_name, schedule_date DESC, effective_date DESC'
                );
                $stmt->bind_param('s', $userDept);
                $stmt->execute();
                $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            } else {
                // Super Admin sees all schedules
                $result = $conn->query(
                    'SELECT * FROM schedules ORDER BY employee_name, schedule_date DESC, effective_date DESC'
                );
                $rows = $result->fetch_all(MYSQLI_ASSOC);
            }
            
            foreach ($rows as &$r) $r['days'] = json_decode($r['days'] ?? '[]');
            sendJson($rows);
        }
        break;

    // POST /schedule.php -> create
    case 'POST':
        // Get user info to check role and department
        $userStmt = $conn->prepare('SELECT role, department FROM users WHERE id = ?');
        $userStmt->bind_param('i', $userId);
        $userStmt->execute();
        $userInfo = $userStmt->get_result()->fetch_assoc();
        $userRole = $userInfo['role'] ?? 'Admin';
        $userDept = $userInfo['department'] ?? null;
        
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) sendError('Invalid JSON body');

        $employee_id   = (int)($data['employeeId']    ?? 0) ?: null;
        $employee_no   = $data['employeeNo']           ?? '';
        $employee_name = $data['employeeName']         ?? '';
        $department    = $data['department']           ?? '';
        
        // For Admin users, only allow creating schedules for their department
        if ($userRole === 'Admin' && $userDept && $department !== $userDept) {
            sendError('Access denied: You can only create schedules for your department', 403);
        }
        
        // New format fields
        $schedule_date = $data['scheduleDate']         ?? null;
        $start_time    = $data['startTime']            ?? null;
        $end_time      = $data['endTime']              ?? null;
        $shift_code    = $data['shiftCode']            ?? null;
        $shift_name    = $data['shiftName']            ?? null;
        $status        = $data['status']               ?? 'Pending';
        $remarks       = $data['remarks']              ?? null;
        $specific_dates = $data['specificDates']       ?? [];
        
        // Legacy format fields (for backward compatibility)
        $shift         = $data['shift']                ?? 'Morning';
        $shift_time    = $data['shiftTime']            ?? '';
        $days          = json_encode($data['days']     ?? []);
        $effective_date = ($data['effectiveDate']      ?? '') ?: null;
        $end_date      = ($data['endDate']             ?? '') ?: null;
        $rest_day      = $data['restDay']              ?? '';
        
        // Validation for new format
        if ($schedule_date && $start_time && $end_time && $shift_code) {
            // Validate time range
            if ($shift_code !== 'OFF' && $end_time <= $start_time) {
                sendError('End time must be after start time', 400);
            }
            
            // Check for duplicate schedule (same employee + date)
            if ($employee_id) {
                $checkStmt = $conn->prepare(
                    'SELECT id FROM schedules WHERE employee_id = ? AND schedule_date = ?'
                );
                $checkStmt->bind_param('is', $employee_id, $schedule_date);
            } else {
                $checkStmt = $conn->prepare(
                    'SELECT id FROM schedules WHERE employee_no = ? AND schedule_date = ?'
                );
                $checkStmt->bind_param('ss', $employee_no, $schedule_date);
            }
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            
            if ($checkResult->num_rows > 0) {
                sendError('A schedule already exists for this employee on this date', 409);
            }
        }
        
        // Handle bulk assignment with specific dates
        if (!empty($specific_dates) && is_array($specific_dates)) {
            $insertedIds = [];
            $conn->begin_transaction();
            
            try {
                foreach ($specific_dates as $date) {
                    // Check for duplicate
                    if ($employee_id) {
                        $checkStmt = $conn->prepare(
                            'SELECT id FROM schedules WHERE employee_id = ? AND schedule_date = ?'
                        );
                        $checkStmt->bind_param('is', $employee_id, $date);
                    } else {
                        $checkStmt = $conn->prepare(
                            'SELECT id FROM schedules WHERE employee_no = ? AND schedule_date = ?'
                        );
                        $checkStmt->bind_param('ss', $employee_no, $date);
                    }
                    $checkStmt->execute();
                    if ($checkStmt->get_result()->num_rows > 0) {
                        continue; // Skip duplicate dates
                    }
                    
                    $stmt = $conn->prepare(
                        'INSERT INTO schedules
                         (employee_id, employee_no, employee_name, department, 
                          schedule_date, start_time, end_time, shift_code, shift_name, status, remarks,
                          shift, shift_time, days, effective_date, end_date, rest_day, created_by)
                         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
                    );
                    $stmt->bind_param('issssssssssssssssi',
                        $employee_id, $employee_no, $employee_name, $department,
                        $date, $start_time, $end_time, $shift_code, $shift_name, $status, $remarks,
                        $shift, $shift_time, $days, $effective_date, $end_date, $rest_day, $userId
                    );
                    
                    if ($stmt->execute()) {
                        $insertedIds[] = $conn->insert_id;
                    }
                }
                
                $conn->commit();
                
                // Notify employee
                if ($employee_id && !empty($insertedIds)) {
                    $userStmt = $conn->prepare('SELECT user_id FROM employees WHERE id = ?');
                    $userStmt->bind_param('i', $employee_id);
                    $userStmt->execute();
                    $userResult = $userStmt->get_result()->fetch_assoc();
                    
                    if ($userResult && $userResult['user_id']) {
                        notifyScheduleAssigned($conn, $userResult['user_id'], $employee_name, $shift_name ?? $shift, $insertedIds[0]);
                    }
                }
                
                sendJson([
                    'ids' => $insertedIds,
                    'count' => count($insertedIds),
                    'message' => 'Schedules created successfully'
                ], 201);
                
            } catch (Exception $e) {
                $conn->rollback();
                sendError('Bulk insert failed: ' . $e->getMessage(), 500);
            }
        } else {
            // Single schedule insert
            $stmt = $conn->prepare(
                'INSERT INTO schedules
                 (employee_id, employee_no, employee_name, department, 
                  schedule_date, start_time, end_time, shift_code, shift_name, status, remarks,
                  shift, shift_time, days, effective_date, end_date, rest_day, created_by)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
            );
            $stmt->bind_param('issssssssssssssssi',
                $employee_id, $employee_no, $employee_name, $department,
                $schedule_date, $start_time, $end_time, $shift_code, $shift_name, $status, $remarks,
                $shift, $shift_time, $days, $effective_date, $end_date, $rest_day, $userId
            );

            if (!$stmt->execute()) sendError('Insert failed: ' . $stmt->error, 500);
            
            $scheduleId = $conn->insert_id;
            
            // Notify employee
            if ($employee_id) {
                $userStmt = $conn->prepare('SELECT user_id FROM employees WHERE id = ?');
                $userStmt->bind_param('i', $employee_id);
                $userStmt->execute();
                $userResult = $userStmt->get_result()->fetch_assoc();
                
                if ($userResult && $userResult['user_id']) {
                    notifyScheduleAssigned($conn, $userResult['user_id'], $employee_name, $shift_name ?? $shift, $scheduleId);
                }
            }
            
            sendJson(['id' => $scheduleId, 'message' => 'Schedule created'], 201);
        }
        break;

    // PUT /schedule.php?id=1 -> update
    case 'PUT':
        // Get user info to check role and department
        $userStmt = $conn->prepare('SELECT role, department FROM users WHERE id = ?');
        $userStmt->bind_param('i', $userId);
        $userStmt->execute();
        $userInfo = $userStmt->get_result()->fetch_assoc();
        $userRole = $userInfo['role'] ?? 'Admin';
        $userDept = $userInfo['department'] ?? null;
        
        $id   = (int) ($_GET['id'] ?? 0);
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$id || !$data) sendError('Invalid request');
        
        // Check if schedule exists and belongs to user's department (for Admin)
        if ($userRole === 'Admin' && $userDept) {
            $checkStmt = $conn->prepare('SELECT department FROM schedules WHERE id = ?');
            $checkStmt->bind_param('i', $id);
            $checkStmt->execute();
            $existingSchedule = $checkStmt->get_result()->fetch_assoc();
            
            if (!$existingSchedule) {
                sendError('Schedule not found', 404);
            }
            
            if ($existingSchedule['department'] !== $userDept) {
                sendError('Access denied: You can only edit schedules from your department', 403);
            }
        }

        $employee_id   = (int)($data['employeeId']    ?? 0) ?: null;
        $employee_no   = $data['employeeNo']           ?? '';
        $employee_name = $data['employeeName']         ?? '';
        $department    = $data['department']           ?? '';
        
        // For Admin users, prevent changing department to another department
        if ($userRole === 'Admin' && $userDept && $department !== $userDept) {
            sendError('Access denied: You cannot change schedule to another department', 403);
        }
        
        // New format fields
        $schedule_date = $data['scheduleDate']         ?? null;
        $start_time    = $data['startTime']            ?? null;
        $end_time      = $data['endTime']              ?? null;
        $shift_code    = $data['shiftCode']            ?? null;
        $shift_name    = $data['shiftName']            ?? null;
        $status        = $data['status']               ?? 'Pending';
        $remarks       = $data['remarks']              ?? null;
        
        // Legacy format fields
        $shift         = $data['shift']                ?? 'Morning';
        $shift_time    = $data['shiftTime']            ?? '';
        $days          = json_encode($data['days']     ?? []);
        $effective_date = ($data['effectiveDate']      ?? '') ?: null;
        $end_date      = ($data['endDate']             ?? '') ?: null;
        $rest_day      = $data['restDay']              ?? '';
        
        // Validation for new format
        if ($schedule_date && $start_time && $end_time && $shift_code) {
            // Validate time range
            if ($shift_code !== 'OFF' && $end_time <= $start_time) {
                sendError('End time must be after start time', 400);
            }
            
            // Check for duplicate schedule (excluding current record)
            if ($employee_id) {
                $checkStmt = $conn->prepare(
                    'SELECT id FROM schedules WHERE employee_id = ? AND schedule_date = ? AND id != ?'
                );
                $checkStmt->bind_param('isi', $employee_id, $schedule_date, $id);
            } else {
                $checkStmt = $conn->prepare(
                    'SELECT id FROM schedules WHERE employee_no = ? AND schedule_date = ? AND id != ?'
                );
                $checkStmt->bind_param('ssi', $employee_no, $schedule_date, $id);
            }
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            
            if ($checkResult->num_rows > 0) {
                sendError('A schedule already exists for this employee on this date', 409);
            }
        }

        $stmt = $conn->prepare(
            'UPDATE schedules SET
             employee_id=?, employee_no=?, employee_name=?, department=?,
             schedule_date=?, start_time=?, end_time=?, shift_code=?, shift_name=?, status=?, remarks=?,
             shift=?, shift_time=?, days=?, effective_date=?, end_date=?, rest_day=?
             WHERE id=?'
        );
        $stmt->bind_param('issssssssssssssssi',
            $employee_id, $employee_no, $employee_name, $department,
            $schedule_date, $start_time, $end_time, $shift_code, $shift_name, $status, $remarks,
            $shift, $shift_time, $days, $effective_date, $end_date, $rest_day, $id
        );

        if (!$stmt->execute()) sendError('Update failed: ' . $stmt->error, 500);
        
        // Notify employee about schedule update
        if ($employee_id) {
            $userStmt = $conn->prepare('SELECT user_id FROM employees WHERE id = ?');
            $userStmt->bind_param('i', $employee_id);
            $userStmt->execute();
            $userResult = $userStmt->get_result()->fetch_assoc();
            
            if ($userResult && $userResult['user_id']) {
                notifyScheduleAssigned($conn, $userResult['user_id'], $employee_name, $shift_name ?? $shift, $id);
            }
        }
        
        sendJson(['message' => 'Schedule updated']);
        break;

    // DELETE /schedule.php?id=1
    case 'DELETE':
        // Get user info to check role and department
        $userStmt = $conn->prepare('SELECT role, department FROM users WHERE id = ?');
        $userStmt->bind_param('i', $userId);
        $userStmt->execute();
        $userInfo = $userStmt->get_result()->fetch_assoc();
        $userRole = $userInfo['role'] ?? 'Admin';
        $userDept = $userInfo['department'] ?? null;
        
        $id = (int) ($_GET['id'] ?? 0);
        if (!$id) sendError('ID required');
        
        // Check if schedule exists and belongs to user's department (for Admin)
        if ($userRole === 'Admin' && $userDept) {
            $checkStmt = $conn->prepare('SELECT department FROM schedules WHERE id = ?');
            $checkStmt->bind_param('i', $id);
            $checkStmt->execute();
            $existingSchedule = $checkStmt->get_result()->fetch_assoc();
            
            if (!$existingSchedule) {
                sendError('Schedule not found', 404);
            }
            
            if ($existingSchedule['department'] !== $userDept) {
                sendError('Access denied: You can only delete schedules from your department', 403);
            }
        }
        
        $stmt = $conn->prepare('DELETE FROM schedules WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        sendJson(['message' => 'Schedule deleted']);
        break;

    default:
        sendError('Method not allowed', 405);
}

$conn->close();
