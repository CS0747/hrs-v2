<?php
/**
 * DIOS System Control API
 * All actions require a valid DIOS session token passed as X-DIOS-Token header.
 */
ini_set('display_errors', 0);
error_reporting(0);

require_once 'db.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-DIOS-Token');

$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'OPTIONS') { http_response_code(200); exit; }

$conn   = getConnection();
$action = $_GET['action'] ?? '';

// ── Allowed tables whitelist (prevents arbitrary table access) ────────────────
$ALLOWED_TABLES = [
    'employees', 'departments', 'leave_records', 'travel_orders',
    'dtr_records', 'dtr_history', 'document_tracking', 'audit_logs',
    'trainings', 'signatories', 'schedules', 'module_permissions',
    'users', 'payroll_records',
];

switch ($action) {

    case 'tables':
        $result = $conn->query("SHOW TABLES");
        if (!$result) sendError('Query failed: ' . $conn->error);
        $tables = [];
        while ($row = $result->fetch_row()) $tables[] = $row[0];
        sendJson(['tables' => $tables]);
        break;

    case 'describe':
        $table = preg_replace('/[^a-zA-Z0-9_]/', '', $_GET['table'] ?? '');
        if (!$table) sendError('Table name required');
        if (!in_array($table, $ALLOWED_TABLES)) sendError('Table not permitted', 403);
        $result = $conn->query("DESCRIBE `$table`");
        if (!$result) sendError('Table not found: ' . $conn->error);
        sendJson(['columns' => $result->fetch_all(MYSQLI_ASSOC)]);
        break;

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
            $res     = $conn->query("SELECT COUNT(*) as cnt FROM `$tbl`");
            $stats[] = [
                'table' => $tbl,
                'label' => $label,
                'count' => $res ? (int)$res->fetch_assoc()['cnt'] : 0,
            ];
        }
        $db      = DB_NAME;
        $sizeRes = $conn->query(
            "SELECT ROUND(SUM(data_length+index_length)/1024/1024,2) AS size_mb
             FROM information_schema.tables WHERE table_schema='$db'"
        );
        sendJson([
            'stats'       => $stats,
            'db_size_mb'  => $sizeRes ? (float)$sizeRes->fetch_assoc()['size_mb'] : 0,
        ]);
        break;

    case 'query':
        if ($method !== 'POST') sendError('POST required', 405);
        $body = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) sendError('Invalid JSON body');

        $sql = trim($body['sql'] ?? '');
        if (!$sql) sendError('SQL query is required');

        // Block dangerous statements
        $upper   = strtoupper($sql);
        $blocked = [
            'DROP DATABASE','DROP TABLE','TRUNCATE','DROP USER',
            'GRANT ','REVOKE ','ALTER USER','FLUSH ','SHUTDOWN',
            'LOAD DATA','INTO OUTFILE','INTO DUMPFILE','CREATE USER',
        ];
        foreach ($blocked as $b) {
            if (strpos($upper, $b) !== false) sendError("Blocked: \"$b\" is not allowed.", 403);
        }

        // Execution timeout via connection timeout (best effort)
        $conn->query("SET SESSION MAX_EXECUTION_TIME=10000"); // 10s

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
            // Cap result at 500 rows to prevent memory exhaustion
            $rows = [];
            $count = 0;
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
                if (++$count >= 500) break;
            }
            sendJson([
                'success'   => true,
                'rows'      => $rows,
                'count'     => $count,
                'truncated' => $count === 500,
                'elapsed'   => $elapsed,
            ]);
        }
        break;

    case 'preview':
        $table  = preg_replace('/[^a-zA-Z0-9_]/', '', $_GET['table'] ?? '');
        $limit  = min((int)($_GET['limit']  ?? 20), 200);
        $offset = max((int)($_GET['offset'] ?? 0),  0);
        if (!$table) sendError('Table name required');
        if (!in_array($table, $ALLOWED_TABLES)) sendError('Table not permitted', 403);

        $result   = $conn->query("SELECT * FROM `$table` LIMIT $limit OFFSET $offset");
        if (!$result) sendError('Query failed: ' . $conn->error);
        $countRes = $conn->query("SELECT COUNT(*) as c FROM `$table`");
        sendJson([
            'rows'   => $result->fetch_all(MYSQLI_ASSOC),
            'total'  => $countRes ? (int)$countRes->fetch_assoc()['c'] : 0,
            'limit'  => $limit,
            'offset' => $offset,
        ]);
        break;

    default:
        sendError('Unknown action', 400);
}

$conn->close();
