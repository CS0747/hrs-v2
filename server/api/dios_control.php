<?php
/**
 * DIOS System Control API
 * Supports: query execution, table listing, system stats.
 */

// Suppress HTML error output — return JSON errors only
ini_set('display_errors', 0);
error_reporting(0);

// Buffer output so any stray warnings don't corrupt JSON
ob_start();

require_once 'db.php';

// Clear any buffered output from require (warnings, notices, etc.)
ob_clean();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'OPTIONS') { http_response_code(200); exit; }

$conn   = getConnection();
$action = $_GET['action'] ?? '';

switch ($action) {

    // ── List all tables ───────────────────────────────────────────────────────
    case 'tables':
        $result = $conn->query("SHOW TABLES");
        if (!$result) sendError('Query failed: ' . $conn->error);
        $tables = [];
        while ($row = $result->fetch_row()) {
            $tables[] = $row[0];
        }
        sendJson(['tables' => $tables]);
        break;

    // ── Describe a table ──────────────────────────────────────────────────────
    case 'describe':
        $table = preg_replace('/[^a-zA-Z0-9_]/', '', $_GET['table'] ?? '');
        if (!$table) sendError('Table name required');
        $result = $conn->query("DESCRIBE `$table`");
        if (!$result) sendError('Table not found or query failed: ' . $conn->error);
        sendJson(['columns' => $result->fetch_all(MYSQLI_ASSOC)]);
        break;

    // ── System stats ──────────────────────────────────────────────────────────
    case 'stats':
        $tableMap = [
            'employees'         => 'Employees',
            'departments'       => 'Departments',
            'leave_records'     => 'Leave Records',
            'travel_orders'     => 'Travel Orders',
            'dtr_records'       => 'DTR Records',
            'document_tracking' => 'Tracking Records',
            'audit_logs'        => 'Audit Logs',
            'trainings'         => 'Trainings',
            'signatories'       => 'Signatories',
        ];
        $stats = [];
        foreach ($tableMap as $tbl => $label) {
            $res = $conn->query("SELECT COUNT(*) as cnt FROM `$tbl`");
            $stats[] = [
                'table' => $tbl,
                'label' => $label,
                'count' => ($res) ? (int)$res->fetch_assoc()['cnt'] : 0,
            ];
        }
        $db      = DB_NAME;
        $sizeRes = $conn->query(
            "SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
             FROM information_schema.tables WHERE table_schema = '$db'"
        );
        $sizeMb = ($sizeRes) ? (float)$sizeRes->fetch_assoc()['size_mb'] : 0;
        sendJson(['stats' => $stats, 'db_size_mb' => $sizeMb]);
        break;

    // ── Execute a query (POST only) ───────────────────────────────────────────
    case 'query':
        if ($method !== 'POST') sendError('POST required', 405);

        $raw  = file_get_contents('php://input');
        $body = json_decode($raw, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            sendError('Invalid JSON body: ' . json_last_error_msg());
        }

        $sql = trim($body['sql'] ?? '');
        if (!$sql) sendError('SQL query is required');

        // Safety blocklist
        $upper   = strtoupper($sql);
        $blocked = [
            'DROP DATABASE', 'DROP TABLE', 'TRUNCATE', 'DROP USER',
            'GRANT ', 'REVOKE ', 'ALTER USER', 'FLUSH ', 'SHUTDOWN',
            'LOAD DATA', 'INTO OUTFILE', 'INTO DUMPFILE',
        ];
        foreach ($blocked as $b) {
            if (strpos($upper, $b) !== false) {
                sendError('Blocked: "' . $b . '" is not allowed.', 403);
            }
        }

        $start   = microtime(true);
        $result  = $conn->query($sql);
        $elapsed = round((microtime(true) - $start) * 1000, 2);

        if ($result === false) {
            sendJson(['success' => false, 'error' => $conn->error, 'elapsed' => $elapsed]);
        } elseif ($result === true) {
            sendJson([
                'success'       => true,
                'affected_rows' => $conn->affected_rows,
                'insert_id'     => $conn->insert_id ?: null,
                'elapsed'       => $elapsed,
            ]);
        } else {
            $rows = $result->fetch_all(MYSQLI_ASSOC);
            sendJson([
                'success' => true,
                'rows'    => $rows,
                'count'   => count($rows),
                'elapsed' => $elapsed,
            ]);
        }
        break;

    // ── Preview table rows ────────────────────────────────────────────────────
    case 'preview':
        $table  = preg_replace('/[^a-zA-Z0-9_]/', '', $_GET['table'] ?? '');
        $limit  = min((int)($_GET['limit']  ?? 20), 200);
        $offset = max((int)($_GET['offset'] ?? 0),  0);
        if (!$table) sendError('Table name required');

        $result = $conn->query("SELECT * FROM `$table` LIMIT $limit OFFSET $offset");
        if (!$result) sendError('Query failed: ' . $conn->error);

        $countRes = $conn->query("SELECT COUNT(*) as c FROM `$table`");
        $total    = $countRes ? (int)$countRes->fetch_assoc()['c'] : 0;

        sendJson([
            'rows'   => $result->fetch_all(MYSQLI_ASSOC),
            'total'  => $total,
            'limit'  => $limit,
            'offset' => $offset,
        ]);
        break;

    default:
        sendError('Unknown action: ' . htmlspecialchars($action), 400);
}

$conn->close();
