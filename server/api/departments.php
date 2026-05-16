<?php
require_once 'cors.php';
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
            $row ? sendJsonCamelCase($row) : sendError('Department not found', 404);
        } else {
            $all = isset($_GET['all']) && $_GET['all'] == '1';
            $sql = $all
                ? 'SELECT * FROM departments ORDER BY name'
                : 'SELECT * FROM departments WHERE active = 1 ORDER BY name';
            $rows = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
            sendJsonCamelCase($rows);
        }
        break;

    // POST /departments.php -> create
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data || empty($data['name'])) sendError('Department name is required');

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
            if ($conn->errno === 1062) sendError('Department already exists', 409);
            sendError('Insert failed: ' . $stmt->error, 500);
        }
        sendJsonCamelCase(['id' => $conn->insert_id, 'message' => 'Department created'], 201);
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
        sendJsonCamelCase(['message' => 'Department updated']);
        break;

    // DELETE /departments.php?id=1 -> soft delete (set active=0)
    case 'DELETE':
        $id = (int) ($_GET['id'] ?? 0);
        if (!$id) sendError('ID required');

        $stmt = $conn->prepare('UPDATE departments SET active = 0 WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        sendJsonCamelCase(['message' => 'Department deactivated']);
        break;

    default:
        sendError('Method not allowed', 405);
}

$conn->close();
