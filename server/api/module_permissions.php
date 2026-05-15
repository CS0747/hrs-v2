<?php
/**
 * Module Permissions API
 * GET  (no params)   → all permissions grouped by module → role → action
 * GET  ?module=X     → permissions for one module
 * POST               → upsert permissions for one module
 * DELETE ?module=X   → delete all rows for a module (reset)
 */
ini_set('display_errors', 0);
error_reporting(0);
ob_start();
require_once 'cors.php';
require_once 'db.php';
ob_clean();

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];

$conn = getConnection();

// Auto-create table if it doesn't exist yet
$conn->query("CREATE TABLE IF NOT EXISTS `module_permissions` (
    `id`         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `module`     VARCHAR(80)   NOT NULL,
    `role`       VARCHAR(40)   NOT NULL,
    `action`     VARCHAR(30)   NOT NULL,
    `granted`    TINYINT(1)    NOT NULL DEFAULT 1,
    `updated_by` VARCHAR(100)  DEFAULT NULL,
    `updated_at` TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_module_role_action` (`module`, `role`, `action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

switch ($method) {

    case 'GET':
        $module = trim($_GET['module'] ?? '');
        if ($module) {
            $stmt = $conn->prepare(
                'SELECT role, action, granted FROM module_permissions WHERE module = ? ORDER BY role, action'
            );
            $stmt->bind_param('s', $module);
            $stmt->execute();
            $rows   = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $result = [];
            foreach ($rows as $row) {
                $result[$row['role']][$row['action']] = (bool)$row['granted'];
            }
            sendJson(['module' => $module, 'permissions' => $result]);
        } else {
            $rows   = $conn->query(
                'SELECT module, role, action, granted FROM module_permissions ORDER BY module, role, action'
            )->fetch_all(MYSQLI_ASSOC);
            $result = [];
            foreach ($rows as $row) {
                $result[$row['module']][$row['role']][$row['action']] = (bool)$row['granted'];
            }
            sendJson(['permissions' => $result]);
        }
        break;

    case 'POST':
        $body = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) sendError('Invalid JSON');

        $module    = trim($body['module']     ?? '');
        $perms     = $body['permissions']     ?? [];
        $updatedBy = trim($body['updated_by'] ?? 'DIOS');

        if (!$module)         sendError('Module name required');
        if (!is_array($perms)) sendError('Permissions must be an object');

        $stmt = $conn->prepare(
            'INSERT INTO module_permissions (module, role, action, granted, updated_by)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE granted = VALUES(granted), updated_by = VALUES(updated_by)'
        );

        $saved = 0;
        foreach ($perms as $role => $actions) {
            if (!is_array($actions)) continue;
            foreach ($actions as $action => $granted) {
                $grantedInt = $granted ? 1 : 0;
                $stmt->bind_param('sssss', $module, $role, $action, $grantedInt, $updatedBy);
                if ($stmt->execute()) $saved++;
            }
        }

        sendJson(['message' => "Saved $saved permission(s) for \"$module\"", 'saved' => $saved]);
        break;

    case 'DELETE':
        $module = trim($_GET['module'] ?? '');
        if (!$module) sendError('Module name required');
        $stmt = $conn->prepare('DELETE FROM module_permissions WHERE module = ?');
        $stmt->bind_param('s', $module);
        $stmt->execute();
        sendJson(['message' => "Reset " . $conn->affected_rows . " permission(s) for \"$module\""]);
        break;

    default:
        sendError('Method not allowed', 405);
}

$conn->close();
