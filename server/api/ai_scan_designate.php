<?php
/**
 * AI Scan Designate & Save API
 * Handles automatic data mapping and saving to designated modules
 * Supports: Employee Masterlist, Birthday Celebrants, Schedule Database
 */

require_once 'db.php';

$method = $_SERVER['REQUEST_METHOD'];
$conn   = getConnection();

if ($method !== 'POST') {
    sendError('Method not allowed. Use POST.', 405);
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) sendError('Invalid JSON body');

$destination = $data['destination'] ?? '';
$scanData    = $data['scan_data'] ?? [];
$scanId      = $data['scan_id'] ?? null;

if (!$destination || !in_array($destination, ['employee_masterlist', 'birthday_celebrants', 'schedule_database'])) {
    sendError('Invalid destination. Must be: employee_masterlist, birthday_celebrants, or schedule_database');
}

if (empty($scanData)) {
    sendError('No scan data provided');
}

// Track results
$results = [
    'success' => false,
    'destination' => $destination,
    'inserted' => 0,
    'updated' => 0,
    'skipped' => 0,
    'errors' => [],
    'details' => []
];

try {
    switch ($destination) {
        case 'employee_masterlist':
            $results = saveToEmployeeMasterlist($conn, $scanData, $scanId);
            break;
        
        case 'birthday_celebrants':
            $results = saveToBirthdayCelebrants($conn, $scanData, $scanId);
            break;
        
        case 'schedule_database':
            $results = saveToScheduleDatabase($conn, $scanData, $scanId);
            break;
    }
    
    $results['success'] = true;
    sendJson($results, 200);
    
} catch (Exception $e) {
    sendError('Save failed: ' . $e->getMessage(), 500);
}

$conn->close();

// ══════════════════════════════════════════════════════════════════════════════
// EMPLOYEE MASTERLIST HANDLER
// ══════════════════════════════════════════════════════════════════════════════

function saveToEmployeeMasterlist($conn, $scanData, $scanId) {
    $results = [
        'success' => true,
        'destination' => 'employee_masterlist',
        'inserted' => 0,
        'updated' => 0,
        'skipped' => 0,
        'errors' => [],
        'details' => []
    ];
    
    // Extract employee data from scan
    $employees = extractEmployeeData($scanData);
    
    if (empty($employees)) {
        $results['errors'][] = 'No valid employee data found in scan';
        return $results;
    }
    
    foreach ($employees as $emp) {
        try {
            // Validate required fields
            if (empty($emp['employee_no']) || empty($emp['last_name']) || empty($emp['first_name'])) {
                $results['skipped']++;
                $results['errors'][] = "Skipped: Missing required fields for employee";
                continue;
            }
            
            // Check for duplicate
            $stmt = $conn->prepare('SELECT id FROM employees WHERE employee_no = ?');
            $stmt->bind_param('s', $emp['employee_no']);
            $stmt->execute();
            $existing = $stmt->get_result()->fetch_assoc();
            
            if ($existing) {
                // Update existing employee
                $stmt = $conn->prepare(
                    'UPDATE employees SET 
                     last_name=?, first_name=?, middle_name=?, position=?, department=?,
                     employment_status=?, birth_date=?, gender=?, contact_no=?, email=?,
                     updated_at=CURRENT_TIMESTAMP
                     WHERE employee_no=?'
                );
                $stmt->bind_param('sssssssssss',
                    $emp['last_name'], $emp['first_name'], $emp['middle_name'],
                    $emp['position'], $emp['department'], $emp['employment_status'],
                    $emp['birth_date'], $emp['gender'], $emp['contact_no'], $emp['email'],
                    $emp['employee_no']
                );
                $stmt->execute();
                $results['updated']++;
                $results['details'][] = "Updated: {$emp['last_name']}, {$emp['first_name']} ({$emp['employee_no']})";
            } else {
                // Insert new employee
                $stmt = $conn->prepare(
                    'INSERT INTO employees 
                     (employee_no, last_name, first_name, middle_name, position, department,
                      employment_status, birth_date, gender, contact_no, email, active)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?,1)'
                );
                $stmt->bind_param('sssssssssss',
                    $emp['employee_no'], $emp['last_name'], $emp['first_name'], $emp['middle_name'],
                    $emp['position'], $emp['department'], $emp['employment_status'],
                    $emp['birth_date'], $emp['gender'], $emp['contact_no'], $emp['email']
                );
                $stmt->execute();
                $results['inserted']++;
                $results['details'][] = "Inserted: {$emp['last_name']}, {$emp['first_name']} ({$emp['employee_no']})";
            }
            
        } catch (Exception $e) {
            $results['errors'][] = "Error processing employee: " . $e->getMessage();
        }
    }
    
    // Update scan record status
    if ($scanId) {
        updateScanStatus($conn, $scanId, 'Saved to Employee Masterlist');
    }
    
    return $results;
}

// ══════════════════════════════════════════════════════════════════════════════
// BIRTHDAY CELEBRANTS HANDLER
// ══════════════════════════════════════════════════════════════════════════════

function saveToBirthdayCelebrants($conn, $scanData, $scanId) {
    $results = [
        'success' => true,
        'destination' => 'birthday_celebrants',
        'inserted' => 0,
        'updated' => 0,
        'skipped' => 0,
        'errors' => [],
        'details' => []
    ];
    
    // Extract employee data with birth dates
    $employees = extractEmployeeData($scanData);
    
    if (empty($employees)) {
        $results['errors'][] = 'No valid employee data found in scan';
        return $results;
    }
    
    foreach ($employees as $emp) {
        try {
            // Must have birth date for birthday celebrants
            if (empty($emp['birth_date'])) {
                $results['skipped']++;
                continue;
            }
            
            // Check if employee exists in masterlist
            $stmt = $conn->prepare('SELECT id FROM employees WHERE employee_no = ?');
            $stmt->bind_param('s', $emp['employee_no']);
            $stmt->execute();
            $existing = $stmt->get_result()->fetch_assoc();
            
            if ($existing) {
                // Update birth date in employee record
                $stmt = $conn->prepare('UPDATE employees SET birth_date=? WHERE employee_no=?');
                $stmt->bind_param('ss', $emp['birth_date'], $emp['employee_no']);
                $stmt->execute();
                $results['updated']++;
                $results['details'][] = "Updated birth date: {$emp['last_name']}, {$emp['first_name']}";
            } else {
                // Insert as new employee with birth date
                $stmt = $conn->prepare(
                    'INSERT INTO employees 
                     (employee_no, last_name, first_name, middle_name, birth_date, department, active)
                     VALUES (?,?,?,?,?,?,1)'
                );
                $stmt->bind_param('ssssss',
                    $emp['employee_no'], $emp['last_name'], $emp['first_name'],
                    $emp['middle_name'], $emp['birth_date'], $emp['department']
                );
                $stmt->execute();
                $results['inserted']++;
                $results['details'][] = "Inserted: {$emp['last_name']}, {$emp['first_name']} (Birthday: {$emp['birth_date']})";
            }
            
        } catch (Exception $e) {
            $results['errors'][] = "Error processing celebrant: " . $e->getMessage();
        }
    }
    
    // Update scan record status
    if ($scanId) {
        updateScanStatus($conn, $scanId, 'Saved to Birthday Celebrants');
    }
    
    return $results;
}

// ══════════════════════════════════════════════════════════════════════════════
// SCHEDULE DATABASE HANDLER
// ══════════════════════════════════════════════════════════════════════════════

function saveToScheduleDatabase($conn, $scanData, $scanId) {
    $results = [
        'success' => true,
        'destination' => 'schedule_database',
        'inserted' => 0,
        'updated' => 0,
        'skipped' => 0,
        'errors' => [],
        'details' => []
    ];
    
    // Extract schedule data
    $schedules = extractScheduleData($scanData);
    
    if (empty($schedules)) {
        $results['errors'][] = 'No valid schedule data found in scan';
        return $results;
    }
    
    // Get or create schedule period
    $period = $schedules['period'] ?? date('F Y');
    $department = $schedules['department'] ?? 'Unknown';
    
    foreach ($schedules['employees'] as $empSchedule) {
        try {
            // Find employee by name
            $fullName = $empSchedule['name'];
            $nameParts = parseEmployeeName($fullName);
            
            $stmt = $conn->prepare(
                'SELECT id, employee_no FROM employees 
                 WHERE last_name LIKE ? AND first_name LIKE ? 
                 LIMIT 1'
            );
            $lastNamePattern = '%' . $nameParts['last_name'] . '%';
            $firstNamePattern = '%' . $nameParts['first_name'] . '%';
            $stmt->bind_param('ss', $lastNamePattern, $firstNamePattern);
            $stmt->execute();
            $employee = $stmt->get_result()->fetch_assoc();
            
            if (!$employee) {
                $results['skipped']++;
                $results['errors'][] = "Employee not found: {$fullName}";
                continue;
            }
            
            // Check for existing schedule
            $stmt = $conn->prepare(
                'SELECT id FROM employee_schedules 
                 WHERE employee_id = ? AND period = ? AND department = ?'
            );
            $stmt->bind_param('iss', $employee['id'], $period, $department);
            $stmt->execute();
            $existing = $stmt->get_result()->fetch_assoc();
            
            // Prepare schedule JSON
            $scheduleJson = json_encode($empSchedule['schedule']);
            
            if ($existing) {
                // Update existing schedule
                $stmt = $conn->prepare(
                    'UPDATE employee_schedules SET 
                     schedule_data=?, work_days=?, updated_at=CURRENT_TIMESTAMP
                     WHERE id=?'
                );
                $stmt->bind_param('sii', $scheduleJson, $empSchedule['numDays'], $existing['id']);
                $stmt->execute();
                $results['updated']++;
                $results['details'][] = "Updated schedule: {$fullName} ({$empSchedule['numDays']} days)";
            } else {
                // Insert new schedule
                $stmt = $conn->prepare(
                    'INSERT INTO employee_schedules 
                     (employee_id, employee_no, employee_name, department, period, 
                      schedule_data, work_days, created_at)
                     VALUES (?,?,?,?,?,?,?,CURRENT_TIMESTAMP)'
                );
                $stmt->bind_param('isssssi',
                    $employee['id'], $employee['employee_no'], $fullName,
                    $department, $period, $scheduleJson, $empSchedule['numDays']
                );
                $stmt->execute();
                $results['inserted']++;
                $results['details'][] = "Inserted schedule: {$fullName} ({$empSchedule['numDays']} days)";
            }
            
        } catch (Exception $e) {
            $results['errors'][] = "Error processing schedule: " . $e->getMessage();
        }
    }
    
    // Update scan record status
    if ($scanId) {
        updateScanStatus($conn, $scanId, 'Saved to Schedule Database');
    }
    
    return $results;
}

// ══════════════════════════════════════════════════════════════════════════════
// DATA EXTRACTION HELPERS
// ══════════════════════════════════════════════════════════════════════════════

function extractEmployeeData($scanData) {
    $employees = [];
    
    // Check if scan data has structured employee list
    if (isset($scanData['groups']) && is_array($scanData['groups'])) {
        // Schedule format with groups
        foreach ($scanData['groups'] as $group) {
            if (isset($group['employees']) && is_array($group['employees'])) {
                foreach ($group['employees'] as $emp) {
                    $nameParts = parseEmployeeName($emp['name'] ?? '');
                    $employees[] = [
                        'employee_no' => generateEmployeeNo($nameParts),
                        'last_name' => $nameParts['last_name'],
                        'first_name' => $nameParts['first_name'],
                        'middle_name' => $nameParts['middle_name'],
                        'position' => '',
                        'department' => $scanData['department'] ?? '',
                        'employment_status' => 'Casual',
                        'birth_date' => null,
                        'gender' => '',
                        'contact_no' => '',
                        'email' => ''
                    ];
                }
            }
        }
    }
    // Check if scan data has rows (spreadsheet format)
    elseif (isset($scanData['rows']) && is_array($scanData['rows'])) {
        $headers = $scanData['rows'][0] ?? [];
        $headerMap = mapHeaders($headers);
        
        for ($i = 1; $i < count($scanData['rows']); $i++) {
            $row = $scanData['rows'][$i];
            $emp = extractEmployeeFromRow($row, $headerMap);
            if ($emp) $employees[] = $emp;
        }
    }
    // Check extracted_data format
    elseif (isset($scanData['extracted_data'])) {
        $data = $scanData['extracted_data'];
        if (isset($data['employeeName'])) {
            $nameParts = parseEmployeeName($data['employeeName']);
            $employees[] = [
                'employee_no' => $data['employeeNo'] ?? generateEmployeeNo($nameParts),
                'last_name' => $nameParts['last_name'],
                'first_name' => $nameParts['first_name'],
                'middle_name' => $nameParts['middle_name'],
                'position' => $data['position'] ?? '',
                'department' => $data['department'] ?? '',
                'employment_status' => 'Casual',
                'birth_date' => $data['birthDate'] ?? $data['date'] ?? null,
                'gender' => '',
                'contact_no' => '',
                'email' => ''
            ];
        }
    }
    
    return $employees;
}

function extractScheduleData($scanData) {
    if (isset($scanData['groups']) && is_array($scanData['groups'])) {
        return [
            'period' => $scanData['period'] ?? date('F Y'),
            'department' => $scanData['department'] ?? 'Unknown',
            'employees' => array_merge(...array_map(function($g) {
                return $g['employees'] ?? [];
            }, $scanData['groups']))
        ];
    }
    return [];
}

function parseEmployeeName($fullName) {
    $fullName = trim($fullName);
    $parts = [
        'last_name' => '',
        'first_name' => '',
        'middle_name' => ''
    ];
    
    // Format: "LAST NAME, FIRST NAME MIDDLE NAME" or "LAST NAME, FIRST NAME M."
    if (strpos($fullName, ',') !== false) {
        list($lastName, $rest) = array_map('trim', explode(',', $fullName, 2));
        $parts['last_name'] = $lastName;
        
        $names = preg_split('/\s+/', $rest);
        $parts['first_name'] = $names[0] ?? '';
        $parts['middle_name'] = $names[1] ?? '';
    }
    // Format: "FIRST MIDDLE LAST"
    else {
        $names = preg_split('/\s+/', $fullName);
        if (count($names) >= 3) {
            $parts['first_name'] = $names[0];
            $parts['middle_name'] = $names[1];
            $parts['last_name'] = implode(' ', array_slice($names, 2));
        } elseif (count($names) == 2) {
            $parts['first_name'] = $names[0];
            $parts['last_name'] = $names[1];
        } else {
            $parts['last_name'] = $fullName;
        }
    }
    
    return $parts;
}

function generateEmployeeNo($nameParts) {
    $prefix = 'GEAMH-';
    $initials = strtoupper(substr($nameParts['first_name'], 0, 1) . substr($nameParts['last_name'], 0, 1));
    $random = str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
    return $prefix . $initials . $random;
}

function mapHeaders($headers) {
    $map = [];
    foreach ($headers as $i => $header) {
        $h = strtolower(trim($header));
        if (strpos($h, 'employee') !== false && strpos($h, 'no') !== false) $map['employee_no'] = $i;
        if (strpos($h, 'last') !== false && strpos($h, 'name') !== false) $map['last_name'] = $i;
        if (strpos($h, 'first') !== false && strpos($h, 'name') !== false) $map['first_name'] = $i;
        if (strpos($h, 'middle') !== false) $map['middle_name'] = $i;
        if (strpos($h, 'position') !== false) $map['position'] = $i;
        if (strpos($h, 'department') !== false) $map['department'] = $i;
        if (strpos($h, 'birth') !== false) $map['birth_date'] = $i;
        if (strpos($h, 'gender') !== false || strpos($h, 'sex') !== false) $map['gender'] = $i;
        if (strpos($h, 'contact') !== false || strpos($h, 'phone') !== false) $map['contact_no'] = $i;
        if (strpos($h, 'email') !== false) $map['email'] = $i;
    }
    return $map;
}

function extractEmployeeFromRow($row, $headerMap) {
    if (empty($row)) return null;
    
    $emp = [
        'employee_no' => $row[$headerMap['employee_no'] ?? -1] ?? '',
        'last_name' => $row[$headerMap['last_name'] ?? -1] ?? '',
        'first_name' => $row[$headerMap['first_name'] ?? -1] ?? '',
        'middle_name' => $row[$headerMap['middle_name'] ?? -1] ?? '',
        'position' => $row[$headerMap['position'] ?? -1] ?? '',
        'department' => $row[$headerMap['department'] ?? -1] ?? '',
        'employment_status' => 'Casual',
        'birth_date' => $row[$headerMap['birth_date'] ?? -1] ?? null,
        'gender' => $row[$headerMap['gender'] ?? -1] ?? '',
        'contact_no' => $row[$headerMap['contact_no'] ?? -1] ?? '',
        'email' => $row[$headerMap['email'] ?? -1] ?? ''
    ];
    
    // Must have at least name
    if (empty($emp['last_name']) && empty($emp['first_name'])) return null;
    
    // Generate employee number if missing
    if (empty($emp['employee_no'])) {
        $emp['employee_no'] = generateEmployeeNo($emp);
    }
    
    return $emp;
}

function updateScanStatus($conn, $scanId, $status) {
    $stmt = $conn->prepare('UPDATE ai_scanned_docs SET status=?, updated_at=CURRENT_TIMESTAMP WHERE id=?');
    $stmt->bind_param('si', $status, $scanId);
    $stmt->execute();
}
