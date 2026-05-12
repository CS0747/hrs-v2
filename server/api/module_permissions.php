<?php
/**
 * Module Permissions API
 * GET  ?module=X          → get all role/action permissions for a module
 * GET  (no params)        → get all permissions grouped by module
 * POST                    → bulk upsert permissions for a module
 * DELETE ?module=X        → reset a module to defaults (delete rows, re-seed)
 */

ini_set('display_errors', 0);
error_reporting(0);
ob_start();

require_once 'db.php';

ob_clean();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'OPTIONS') { http_response_code(200); exit; }

$conn = getConnection();

switch ($method) {

    // ── GET: fetch permissions ────────────────────────────────────────────────
    case 'GET':
        $module = trim($_GET['module'] ?? '');

        if ($module) {
            $stmt = $conn->prepare(
                'SELECT role, action, granted FROM module_permissions WHERE module = ? ORDER BY role, action'
            );
            $stmt->bind_param('s', $module);
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            // Shape: { role: { action: bool } }
            $result = [];
            foreach ($rows as $row) {
                $result[$row['role']][$row['action']] = (bool)$row['granted'];
            }
            sendJson(['module' => $module, 'permissions' => $result]);
        } else {
            // Return all permissions grouped by module → role → action
            $rows = $conn->query(
                'SELECT module, role, action, granted FROM module_permissions ORDER BY module, role, action'
            )->fetch_all(MYSQLI_ASSOC);

            $result = [];
            foreach ($rows as $row) {
                $result[$row['module']][$row['role']][$row['action']] = (bool)$row['granted'];
            }
            sendJson(['permissions' => $result]);
        }
        break;

    // ── POST: save permissions for one module ─────────────────────────────────
    case 'POST':
        $raw  = file_get_contents('php://input');
        $body = json_decode($raw, true);

        if (json_last_error() !== JSON_ERROR_NONE) sendError('Invalid JSON');

        $module     = trim($body['module']      ?? '');
        $perms      = $body['permissions']      ?? [];   // { role: { action: bool } }
        $updatedBy  = trim($body['updated_by']  ?? 'DIOS');

        if (!$module)  sendError('Module name required');
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
                $stmt->execute();
                $saved++;
            }
        }

        sendJson(['message' => "Saved $saved permission(s) for \"$module\"", 'saved' => $saved]);
        break;

    // ── DELETE: reset module permissions (remove custom, re-seed defaults) ────
    case 'DELETE':
        $module = trim($_GET['module'] ?? '');
        if (!$module) sendError('Module name required');

        $stmt = $conn->prepare('DELETE FROM module_permissions WHERE module = ?');
        $stmt->bind_param('s', $module);
        $stmt->execute();
        $deleted = $conn->affected_rows;

        sendJson(['message' => "Reset $deleted permission(s) for \"$module\". Re-run seed SQL to restore defaults."]);
        break;

    default:
        sendError('Method not allowed', 405);
}

$conn->close();
