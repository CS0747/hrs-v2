<?php
require_once 'db.php';
require_once 'cors.php';

// Wrap everything in try-catch to ensure JSON response
try {
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
if (!checkPermission($conn, $userId, 'Departments', $action)) {
    denyAccess('Departments', $action);
}

switch ($method) {

    // GET /departments.php        -> all active departments
    // GET /departments.php?all=1  -> include inactive
    case 'GET':
        if (isset($_GET['id'])) {
            $id   = (int) $_GET['id'];
            $stmt = $conn->prepare('SELECT * FROM departments WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $row ? sendJson($row) : sendError('Department not found', 404);
        } else {
            $all = isset($_GET['all']) && $_GET['all'] == '1';
            $sql = $all
                ? 'SELECT * FROM departments ORDER BY name'
                : 'SELECT * FROM departments WHERE active = 1 ORDER BY name';
            $rows = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
            sendJson($rows);
        }
        break;

    // POST /departments.php -> create
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Log for debugging
        error_log("POST /departments.php - Data received: " . print_r($data, true));
        error_log("User ID: $userId");
        
        if (!$data || empty($data['name'])) {
            error_log("Error: Department name is required");
            sendError('Department name is required');
        }

        $name        = trim($data['name']);
        $code        = trim($data['code']        ?? '');
        $description = trim($data['description'] ?? '');
        $active      = (int)($data['active']     ?? 1);

        $stmt = $conn->prepare(
            'INSERT INTO departments (name, code, description, active) VALUES (?,?,?,?)'
        );
        $stmt->bind_param('sssi', $name, $code, $description, $active);

        if (!$stmt->execute()) {
            // Duplicate name
            if ($conn->errno === 1062) {
                error_log("Error: Department already exists");
                sendError('Department already exists', 409);
            }
            error_log("Error: Insert failed - " . $stmt->error);
            sendError('Insert failed: ' . $stmt->error, 500);
        }
        
        error_log("Success: Department created with ID " . $conn->insert_id);
        sendJson(['id' => $conn->insert_id, 'message' => 'Department created'], 201);
        break;

    // PUT /departments.php?id=1 -> update
    case 'PUT':
        $id   = (int) ($_GET['id'] ?? 0);
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$id || !$data) sendError('Invalid request');

        $name        = trim($data['name']        ?? '');
        $code        = trim($data['code']        ?? '');
        $description = trim($data['description'] ?? '');
        $active      = (int)($data['active']     ?? 1);

        if (!$name) sendError('Department name is required');

        $stmt = $conn->prepare(
            'UPDATE departments SET name=?, code=?, description=?, active=? WHERE id=?'
        );
        $stmt->bind_param('sssii', $name, $code, $description, $active, $id);

        if (!$stmt->execute()) {
            if ($conn->errno === 1062) sendError('Department name already exists', 409);
            sendError('Update failed: ' . $stmt->error, 500);
        }
        sendJson(['message' => 'Department updated']);
        break;

    // DELETE /departments.php?id=1 -> permanently delete
    case 'DELETE':
        $id = (int) ($_GET['id'] ?? 0);
        if (!$id) sendError('ID required');

        // Check if department is being used by employees
        $checkStmt = $conn->prepare('SELECT COUNT(*) as count FROM employees WHERE department = (SELECT name FROM departments WHERE id = ?)');
        $checkStmt->bind_param('i', $id);
        $checkStmt->execute();
        $result = $checkStmt->get_result()->fetch_assoc();
        
        if ($result['count'] > 0) {
            sendError('Cannot delete department: ' . $result['count'] . ' employee(s) are assigned to this department', 409);
        }
        
        // Check if department is being used by users
        $checkStmt = $conn->prepare('SELECT COUNT(*) as count FROM users WHERE department = (SELECT name FROM departments WHERE id = ?)');
        $checkStmt->bind_param('i', $id);
        $checkStmt->execute();
        $result = $checkStmt->get_result()->fetch_assoc();
        
        if ($result['count'] > 0) {
            sendError('Cannot delete department: ' . $result['count'] . ' user(s) belong to this department', 409);
        }

        // Permanently delete the department
        $stmt = $conn->prepare('DELETE FROM departments WHERE id = ?');
        $stmt->bind_param('i', $id);
        
        if (!$stmt->execute()) {
            sendError('Delete failed: ' . $stmt->error, 500);
        }
        
        sendJson(['message' => 'Department permanently deleted']);
        break;

    default:
        sendError('Method not allowed', 405);
}

$conn->close();

} catch (Exception $e) {
    // Ensure we always return JSON even on error
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'error' => 'Server error: ' . $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
