<?php
/**
 * Shift Legends API
 * Manages color-coded shift legends for schedule display
 */

require_once 'db.php';
require_once 'cors.php';

$conn = getConnection();
$method = $_SERVER['REQUEST_METHOD'];
$userId = $_SERVER['HTTP_X_USER_ID'] ?? null;

// GET: Fetch shift legends
if ($method === 'GET') {
    $department = $_GET['department'] ?? null;
    
    if ($department) {
        // Get department-specific legends first, then standard legends
        $stmt = $conn->prepare(
            "SELECT * FROM shift_legends 
             WHERE (department = ? OR department IS NULL) AND active = TRUE
             ORDER BY department DESC, display_order ASC"
        );
        $stmt->bind_param('s', $department);
    } else {
        // Get all active legends
        $stmt = $conn->prepare(
            "SELECT * FROM shift_legends 
             WHERE active = TRUE
             ORDER BY department, display_order ASC"
        );
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $legends = [];
    
    while ($row = $result->fetch_assoc()) {
        $legends[] = [
            'id' => (int)$row['id'],
            'code' => $row['code'],
            'department' => $row['department'],
            'timeRange' => $row['time_range'],
            'colorPrimary' => $row['color_primary'],
            'colorSecondary' => $row['color_secondary'],
            'displayOrder' => (int)$row['display_order'],
            'active' => (bool)$row['active']
        ];
    }
    
    sendJson($legends);
}

// POST: Create new shift legend (Admin only)
elseif ($method === 'POST') {
    // Check permission
    if (!checkPermission($conn, $userId, 'Schedule Database', 'Add')) {
        sendError('Permission denied', 403);
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    if (empty($data['code'])) {
        sendError('Shift code is required', 400);
    }
    if (empty($data['timeRange'])) {
        sendError('Time range is required', 400);
    }
    if (empty($data['colorPrimary'])) {
        sendError('Primary color is required', 400);
    }
    
    // Validate color format (hex color)
    if (!preg_match('/^#[0-9A-F]{6}$/i', $data['colorPrimary'])) {
        sendError('Invalid primary color format. Use hex format: #RRGGBB', 400);
    }
    if (!empty($data['colorSecondary']) && !preg_match('/^#[0-9A-F]{6}$/i', $data['colorSecondary'])) {
        sendError('Invalid secondary color format. Use hex format: #RRGGBB', 400);
    }
    
    // Validate code length
    if (strlen($data['code']) > 10) {
        sendError('Shift code must be 10 characters or less', 400);
    }
    
    $code = $data['code'];
    $department = $data['department'] ?? null;
    $timeRange = $data['timeRange'];
    $colorPrimary = $data['colorPrimary'];
    $colorSecondary = $data['colorSecondary'] ?? null;
    $displayOrder = $data['displayOrder'] ?? 0;
    
    // Check for duplicate
    $checkStmt = $conn->prepare(
        "SELECT id FROM shift_legends WHERE code = ? AND (department = ? OR (department IS NULL AND ? IS NULL))"
    );
    $checkStmt->bind_param('sss', $code, $department, $department);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        sendError('A legend with this code already exists for this department', 409);
    }
    
    // Insert new legend
    $stmt = $conn->prepare(
        "INSERT INTO shift_legends (code, department, time_range, color_primary, color_secondary, display_order, active)
         VALUES (?, ?, ?, ?, ?, ?, TRUE)"
    );
    $stmt->bind_param('sssssi', $code, $department, $timeRange, $colorPrimary, $colorSecondary, $displayOrder);
    
    if ($stmt->execute()) {
        sendJson([
            'id' => $conn->insert_id,
            'message' => 'Shift legend created successfully'
        ], 201);
    } else {
        sendError('Failed to create shift legend: ' . $conn->error, 500);
    }
}

// PUT: Update shift legend (Admin only)
elseif ($method === 'PUT') {
    // Check permission
    if (!checkPermission($conn, $userId, 'Schedule Database', 'Edit')) {
        sendError('Permission denied', 403);
    }
    
    $id = $_GET['id'] ?? null;
    if (!$id) {
        sendError('Legend ID is required', 400);
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate color formats if provided
    if (!empty($data['colorPrimary']) && !preg_match('/^#[0-9A-F]{6}$/i', $data['colorPrimary'])) {
        sendError('Invalid primary color format. Use hex format: #RRGGBB', 400);
    }
    if (!empty($data['colorSecondary']) && !preg_match('/^#[0-9A-F]{6}$/i', $data['colorSecondary'])) {
        sendError('Invalid secondary color format. Use hex format: #RRGGBB', 400);
    }
    
    // Build update query dynamically
    $updates = [];
    $types = '';
    $values = [];
    
    if (isset($data['code'])) {
        $updates[] = 'code = ?';
        $types .= 's';
        $values[] = $data['code'];
    }
    if (isset($data['department'])) {
        $updates[] = 'department = ?';
        $types .= 's';
        $values[] = $data['department'];
    }
    if (isset($data['timeRange'])) {
        $updates[] = 'time_range = ?';
        $types .= 's';
        $values[] = $data['timeRange'];
    }
    if (isset($data['colorPrimary'])) {
        $updates[] = 'color_primary = ?';
        $types .= 's';
        $values[] = $data['colorPrimary'];
    }
    if (isset($data['colorSecondary'])) {
        $updates[] = 'color_secondary = ?';
        $types .= 's';
        $values[] = $data['colorSecondary'];
    }
    if (isset($data['displayOrder'])) {
        $updates[] = 'display_order = ?';
        $types .= 'i';
        $values[] = $data['displayOrder'];
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
    
    $sql = "UPDATE shift_legends SET " . implode(', ', $updates) . " WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$values);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            sendJson(['message' => 'Shift legend updated successfully']);
        } else {
            sendError('Legend not found or no changes made', 404);
        }
    } else {
        sendError('Failed to update shift legend: ' . $conn->error, 500);
    }
}

// DELETE: Deactivate shift legend (Admin only)
elseif ($method === 'DELETE') {
    // Check permission
    if (!checkPermission($conn, $userId, 'Schedule Database', 'Delete')) {
        sendError('Permission denied', 403);
    }
    
    $id = $_GET['id'] ?? null;
    if (!$id) {
        sendError('Legend ID is required', 400);
    }
    
    // Soft delete by setting active = FALSE
    $stmt = $conn->prepare("UPDATE shift_legends SET active = FALSE WHERE id = ?");
    $stmt->bind_param('i', $id);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            sendJson(['message' => 'Shift legend deactivated successfully']);
        } else {
            sendError('Legend not found', 404);
        }
    } else {
        sendError('Failed to deactivate shift legend: ' . $conn->error, 500);
    }
}

else {
    sendError('Method not allowed', 405);
}

$conn->close();
