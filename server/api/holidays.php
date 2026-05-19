<?php
/**
 * Holidays API
 * Manages Philippine holidays
 */

require_once 'db.php';
require_once 'cors.php';

$conn = getConnection();
$method = $_SERVER['REQUEST_METHOD'];

// GET: Fetch holidays
if ($method === 'GET') {
    $year = $_GET['year'] ?? date('Y');
    $month = $_GET['month'] ?? null;
    
    if ($month) {
        // Get holidays for specific month
        $stmt = $conn->prepare(
            "SELECT * FROM holidays 
             WHERE YEAR(date) = ? AND MONTH(date) = ? AND active = TRUE
             ORDER BY date ASC"
        );
        $stmt->bind_param('ii', $year, $month);
    } else {
        // Get all holidays for the year
        $stmt = $conn->prepare(
            "SELECT * FROM holidays 
             WHERE YEAR(date) = ? AND active = TRUE
             ORDER BY date ASC"
        );
        $stmt->bind_param('i', $year);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $holidays = [];
    
    while ($row = $result->fetch_assoc()) {
        $holidays[] = [
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'date' => $row['date'],
            'type' => $row['type'],
            'recurring' => (bool)$row['recurring'],
            'active' => (bool)$row['active']
        ];
    }
    
    sendJson($holidays);
}

// POST: Create new holiday (Admin only)
elseif ($method === 'POST') {
    $userId = $_SERVER['HTTP_X_USER_ID'] ?? null;
    
    // Check permission
    if (!checkPermission($conn, $userId, 'Schedule Database', 'Add')) {
        sendError('Permission denied', 403);
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    if (empty($data['name'])) {
        sendError('Holiday name is required', 400);
    }
    if (empty($data['date'])) {
        sendError('Holiday date is required', 400);
    }
    
    $name = $data['name'];
    $date = $data['date'];
    $type = $data['type'] ?? 'Regular';
    $recurring = $data['recurring'] ?? true;
    
    // Check for duplicate
    $checkStmt = $conn->prepare("SELECT id FROM holidays WHERE date = ?");
    $checkStmt->bind_param('s', $date);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        sendError('A holiday already exists on this date', 409);
    }
    
    // Insert new holiday
    $stmt = $conn->prepare(
        "INSERT INTO holidays (name, date, type, recurring, active)
         VALUES (?, ?, ?, ?, TRUE)"
    );
    $stmt->bind_param('sssi', $name, $date, $type, $recurring);
    
    if ($stmt->execute()) {
        sendJson([
            'id' => $conn->insert_id,
            'message' => 'Holiday created successfully'
        ], 201);
    } else {
        sendError('Failed to create holiday: ' . $conn->error, 500);
    }
}

// PUT: Update holiday (Admin only)
elseif ($method === 'PUT') {
    $userId = $_SERVER['HTTP_X_USER_ID'] ?? null;
    
    // Check permission
    if (!checkPermission($conn, $userId, 'Schedule Database', 'Edit')) {
        sendError('Permission denied', 403);
    }
    
    $id = $_GET['id'] ?? null;
    if (!$id) {
        sendError('Holiday ID is required', 400);
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Build update query dynamically
    $updates = [];
    $types = '';
    $values = [];
    
    if (isset($data['name'])) {
        $updates[] = 'name = ?';
        $types .= 's';
        $values[] = $data['name'];
    }
    if (isset($data['date'])) {
        $updates[] = 'date = ?';
        $types .= 's';
        $values[] = $data['date'];
    }
    if (isset($data['type'])) {
        $updates[] = 'type = ?';
        $types .= 's';
        $values[] = $data['type'];
    }
    if (isset($data['recurring'])) {
        $updates[] = 'recurring = ?';
        $types .= 'i';
        $values[] = $data['recurring'] ? 1 : 0;
    }
    if (isset($data['active'])) {
        $updates[] = 'active = ?';
        $types .= 'i';
        $values[] = $data['active'] ? 1 : 0;
    }
    
    if (empty($updates)) {
        sendError('No fields to update', 400);
    }
    
    $values[] = $id;
    $types .= 'i';
    
    $sql = "UPDATE holidays SET " . implode(', ', $updates) . " WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$values);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            sendJson(['message' => 'Holiday updated successfully']);
        } else {
            sendError('Holiday not found or no changes made', 404);
        }
    } else {
        sendError('Failed to update holiday: ' . $conn->error, 500);
    }
}

// DELETE: Deactivate holiday (Admin only)
elseif ($method === 'DELETE') {
    $userId = $_SERVER['HTTP_X_USER_ID'] ?? null;
    
    // Check permission
    if (!checkPermission($conn, $userId, 'Schedule Database', 'Delete')) {
        sendError('Permission denied', 403);
    }
    
    $id = $_GET['id'] ?? null;
    if (!$id) {
        sendError('Holiday ID is required', 400);
    }
    
    // Soft delete by setting active = FALSE
    $stmt = $conn->prepare("UPDATE holidays SET active = FALSE WHERE id = ?");
    $stmt->bind_param('i', $id);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            sendJson(['message' => 'Holiday deactivated successfully']);
        } else {
            sendError('Holiday not found', 404);
        }
    } else {
        sendError('Failed to deactivate holiday: ' . $conn->error, 500);
    }
}

else {
    sendError('Method not allowed', 405);
}

$conn->close();
