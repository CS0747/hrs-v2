<?php
// Enable error logging for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/ai_scan_errors.log');

require_once 'db.php';

$method = $_SERVER['REQUEST_METHOD'];
$conn   = getConnection();
$action = $_GET['action'] ?? '';
$userId = (int)($_SERVER['HTTP_X_USER_ID'] ?? 0);

// Log request for debugging
error_log("AI Scan API - Method: $method, Action: $action, UserID: $userId");

// Map HTTP methods to actions
$actionMap = [
    'GET'    => 'View',
    'POST'   => $action === 'save' ? 'Add' : 'Upload',
    'DELETE' => 'Delete',
];
$permAction = $actionMap[$method] ?? 'View';

// Check permission before processing request
if (!checkPermission($conn, $userId, 'AI Scanning Tools', $permAction)) {
    error_log("AI Scan API - Permission denied for user $userId, module: AI Scanning Tools, action: $permAction");
    denyAccess('AI Scanning Tools', $permAction);
}

try {

switch ($method) {

    case 'GET':
        if (isset($_GET['id'])) {
            $id   = (int)$_GET['id'];
            $stmt = $conn->prepare('SELECT * FROM ai_scanned_docs WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            if (!$row) sendError('Scan not found', 404);
            $row['extracted_data'] = $row['extracted_data'] ? json_decode($row['extracted_data'], true) : null;
            sendJson($row);
        } else {
            // Check if table exists
            $tableCheck = $conn->query("SHOW TABLES LIKE 'ai_scanned_docs'");
            if ($tableCheck->num_rows === 0) {
                // Table doesn't exist, return empty array
                sendJson([]);
                break;
            }
            
            $result = $conn->query('SELECT * FROM ai_scanned_docs ORDER BY created_at DESC LIMIT 200');
            if (!$result) {
                sendError('Database query failed: ' . $conn->error, 500);
            }
            $rows   = $result->fetch_all(MYSQLI_ASSOC);
            foreach ($rows as &$r) {
                $r['extracted_data'] = $r['extracted_data'] ? json_decode($r['extracted_data'], true) : null;
            }
            sendJson($rows);
        }
        break;

    case 'POST':
        if ($action === 'save') {
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) sendError('Invalid JSON body');

            $file_name      = $data['file_name']      ?? '';
            $doc_type       = $data['doc_type']        ?? 'Unknown';
            $file_size      = $data['file_size']       ?? '';
            $confidence     = (int)($data['confidence'] ?? 0);
            $extracted_data = json_encode($data['extracted_data'] ?? []);
            $raw_text       = $data['raw_text']        ?? '';
            $status         = $data['status']          ?? 'Processed';
            $uploaded_by    = isset($data['uploaded_by']) ? (int)$data['uploaded_by'] : null;
            $file_path      = $data['file_path']       ?? '';

            $stmt = $conn->prepare(
                'INSERT INTO ai_scanned_docs
                 (file_name, doc_type, file_size, confidence, extracted_data, raw_text, status, uploaded_by, file_path)
                 VALUES (?,?,?,?,?,?,?,?,?)'
            );
            $stmt->bind_param('sssisssss',
                $file_name, $doc_type, $file_size, $confidence,
                $extracted_data, $raw_text, $status, $uploaded_by, $file_path
            );
            if (!$stmt->execute()) sendError('Save failed: ' . $stmt->error, 500);
            sendJson(['id' => $conn->insert_id, 'message' => 'Scan saved'], 201);

        } else {
            if (empty($_FILES['file'])) sendError('No file uploaded');

            $file     = $_FILES['file'];
            $origName = basename($file['name']);
            $ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
            $allowed  = ['pdf','jpg','jpeg','png','gif','bmp','webp','xlsx','xls','csv','docx','doc'];

            if (!in_array($ext, $allowed)) sendError('File type not allowed');
            if ($file['size'] > 20 * 1024 * 1024) sendError('File too large. Max 20 MB.');

            $uploadDir = __DIR__ . '/../uploads/ai_scans/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            $safeName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $origName);
            $destPath = $uploadDir . $safeName;
            $webPath  = 'uploads/ai_scans/' . $safeName;

            if (!move_uploaded_file($file['tmp_name'], $destPath)) sendError('Failed to save file', 500);

            $fileSize  = formatBytes($file['size']);
            $docType   = detectDocType($origName);
            $extracted = [];
            $rawText   = '';
            $confidence = 0;
            $previewUrl = null;
            $htmlTable  = '';

            if (in_array($ext, ['jpg','jpeg','png','gif','bmp','webp'])) {
                $previewUrl = $webPath;
                // ── OCR.space API for image scanning ──────────────────────────
                $ocrResult  = ocrSpaceScan($destPath, $origName);
                $rawText    = $ocrResult['text'];
                $confidence = $ocrResult['confidence'];
                
                if ($rawText) {
                    $htmlTable = buildOcrHtml($rawText);
                    $extracted = parseText($rawText, $docType);
                    
                    // Enhance confidence based on extraction quality
                    if (!empty($extracted) && count($extracted) > 3) {
                        $confidence = min(95, $confidence + 5);
                    }
                }
            } elseif ($ext === 'pdf') {
                $previewUrl = $webPath;
                // Try pdftotext first (if available)
                $pdfText = '';
                if (function_exists('exec')) {
                    $escaped = escapeshellarg($destPath);
                    exec("pdftotext $escaped -", $lines, $ret);
                    if ($ret === 0 && !empty($lines)) {
                        $pdfText = implode("\n", $lines);
                    }
                }
                // Fallback: pure-PHP PDF text stream extraction
                if (!$pdfText) {
                    $pdfText = extractPdfText($destPath);
                }
                if ($pdfText) {
                    $rawText    = $pdfText;
                    $htmlTable  = buildPdfHtml($rawText);
                    $extracted  = parseText($rawText, $docType);
                    $confidence = max(70, estimateConfidence($extracted));
                }

            } elseif (in_array($ext, ['xlsx','xls','csv'])) {
                $result     = parseSpreadsheet($destPath, $ext);
                $rawText    = $result['raw'];
                $extracted  = $result['data'];
                $htmlTable  = $result['html_table'];
                $confidence = 95;

            } elseif (in_array($ext, ['docx','doc'])) {
                $result     = extractDocx($destPath);
                $rawText    = $result['text'];
                $htmlTable  = $result['html_table'];
                $extracted  = parseText($rawText, $docType);
                $confidence = estimateConfidence($extracted);
            }

            sendJson([
                'file_name'      => $origName,
                'file_path'      => $webPath,
                'preview_url'    => $previewUrl ? 'http://localhost/hrs/server/' . $previewUrl : null,
                'doc_type'       => $docType,
                'file_size'      => $fileSize,
                'ext'            => $ext,
                'confidence'     => $confidence,
                'extracted_data' => $extracted,
                'raw_text'       => substr($rawText, 0, 5000),
                'html_table'     => $htmlTable,
                'status'         => $confidence >= 60 ? 'Processed' : 'Review Needed',
                'needs_ocr'      => in_array($ext, ['jpg','jpeg','png','gif','bmp','webp']),
            ]);
        }
        break;

    case 'DELETE':
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) sendError('ID required');
        $stmt = $conn->prepare('SELECT file_path FROM ai_scanned_docs WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if ($row && $row['file_path']) {
            $fp = __DIR__ . '/../' . $row['file_path'];
            if (file_exists($fp)) @unlink($fp);
        }
        $stmt = $conn->prepare('DELETE FROM ai_scanned_docs WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        sendJson(['message' => 'Scan deleted']);
        break;

    default:
        sendError('Method not allowed', 405);
}

} catch (Exception $e) {
    error_log("AI Scan API Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    sendError('Server error: ' . $e->getMessage(), 500);
}

$conn->close();

// ── Helpers ───────────────────────────────────────────────────────────────────

function formatBytes(int $bytes): string {
    if ($bytes < 1024)    return $bytes . ' B';
    if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
    return round($bytes / 1048576, 2) . ' MB';
}

function detectDocType(string $name): string {
    $l = strtolower($name);
    if (strpos($l, 'dtr')      !== false) return 'DTR';
    if (strpos($l, 'leave')    !== false) return 'Leave Form';
    if (strpos($l, 'payslip')  !== false || strpos($l, 'payroll') !== false) return 'Payslip';
    if (strpos($l, 'travel')   !== false) return 'Travel Order';
    if (strpos($l, 'schedule') !== false) return 'Schedule';
    return 'Unknown';
}

function parseText(string $text, string $docType): array {
    $lines  = array_filter(array_map('trim', explode("\n", $text)));
    
    // ── Schedule of Duties — use specialized parser ──────────────────────────────
    if (preg_match('/schedule of duties|schedule of duty/i', $text) || $docType === 'Schedule') {
        $parsed = parseScheduleOfDutiesServer($text, $lines);
        // Flatten for extracted_data summary
        $allEmps = [];
        foreach ($parsed['groups'] as $group) {
            foreach ($group['employees'] as $emp) {
                $allEmps[] = $emp['name'] . ' (' . $emp['numDays'] . ' days)';
            }
        }
        return [
            'hospital'     => $parsed['hospital'],
            'project'      => $parsed['project'],
            'department'   => $parsed['department'],
            'period'       => $parsed['period'],
            'employees'    => count($allEmps),
            'employeeList' => implode(', ', array_slice($allEmps, 0, 5)) . (count($allEmps) > 5 ? '...' : ''),
            'preparedBy'   => $parsed['preparedBy'],
            'approvedBy'   => $parsed['approvedBy'],
            'notedBy'      => $parsed['notedBy'],
            '_fullParsed'  => $parsed, // Store full parsed data for HTML rendering
        ];
    }
    
    // ── Generic document parsing ──────────────────────────────────────────────────
    $result = [];
    foreach ($lines as $line) {
        if (preg_match('/(?:employee\s*name|name)[:\s]+(.+)/i',   $line, $m)) $result['employeeName'] = trim($m[1]);
        if (preg_match('/(?:department|dept)[:\s]+(.+)/i',        $line, $m)) $result['department']   = trim($m[1]);
        if (preg_match('/(?:period|month)[:\s]+(.+)/i',           $line, $m)) $result['period']       = trim($m[1]);
        if (preg_match('/(?:position|designation)[:\s]+(.+)/i',   $line, $m)) $result['position']     = trim($m[1]);
        if (preg_match('/(?:leave\s*type)[:\s]+(.+)/i',           $line, $m)) $result['leaveType']    = trim($m[1]);
        if (preg_match('/(?:total\s*hours?)[:\s]+([\d.]+)/i',     $line, $m)) $result['totalHours']   = $m[1];
        if (preg_match('/(?:gross\s*pay)[:\s]+([\d,]+\.?\d*)/i',  $line, $m)) $result['grossPay']     = $m[1];
        if (preg_match('/(?:net\s*pay)[:\s]+([\d,]+\.?\d*)/i',    $line, $m)) $result['netPay']       = $m[1];
        if (preg_match('/(?:destination)[:\s]+(.+)/i',            $line, $m)) $result['destination']  = trim($m[1]);
        if (preg_match('/\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4}/',    $line, $m) && empty($result['date'])) $result['date'] = $m[0];
    }
    if (empty($result)) {
        $result['textPreview'] = substr(implode(' ', array_slice(array_values($lines), 0, 5)), 0, 300);
    }
    return $result;
}

// ── Schedule of Duties parser (SERVER-SIDE) ───────────────────────────────────
function parseScheduleOfDutiesServer(string $text, array $lines): array {
    $result = [
        'docType'    => 'Schedule of Duties',
        'hospital'   => '',
        'project'    => '',
        'location'   => '',
        'period'     => '',
        'department' => '',
        'groups'     => [],
        'legend'     => [],
        'preparedBy' => '', 'preparedByTitle' => '',
        'approvedBy' => '', 'approvedByTitle' => '',
        'notedBy'    => '', 'notedByTitle'    => '',
        'rawText'    => $text,
    ];

    // ── Header fields ────────────────────────────────────────────────────────────
    for ($i = 0; $i < count($lines); $i++) {
        $l = $lines[$i];
        if (preg_match('/general emilio|GEAMH/i', $l) && !$result['hospital'])           $result['hospital']   = trim($l);
        if (preg_match('/korea|friendship|health project/i', $l) && !$result['project']) $result['project']    = trim($l);
        if (preg_match('/trece martires|city|province/i', $l) && !$result['location'])   $result['location']   = trim($l);
        
        if (preg_match('/schedule of duties/i', $l)) {
            for ($j = $i + 1; $j < min($i + 4, count($lines)); $j++) {
                if (isset($lines[$j]) && preg_match('/\d{4}|january|february|march|april|may|june|july|august|september|october|november|december/i', $lines[$j])) {
                    $result['period'] = trim($lines[$j]);
                    break;
                }
            }
        }
        
        if (preg_match('/department[\/\s]*unit/i', $l)) {
            $val = trim(preg_replace('/department[\/\s]*unit[:\s]*/i', '', $l));
            $result['department'] = $val ?: (isset($lines[$i+1]) ? trim($lines[$i+1]) : '');
        }
        if (preg_match('/electronic medical|EMR\b/i', $l) && !$result['department']) $result['department'] = trim($l);

        if (preg_match('/prepared\s*by/i', $l)) {
            $result['preparedBy']      = isset($lines[$i+1]) ? trim($lines[$i+1]) : '';
            $result['preparedByTitle'] = isset($lines[$i+2]) ? trim($lines[$i+2]) : '';
        }
        if (preg_match('/approved\s*by/i', $l)) {
            $result['approvedBy']      = isset($lines[$i+1]) ? trim($lines[$i+1]) : '';
            $result['approvedByTitle'] = isset($lines[$i+2]) ? trim($lines[$i+2]) : '';
        }
        if (preg_match('/noted\s*by/i', $l)) {
            $result['notedBy']      = isset($lines[$i+1]) ? trim($lines[$i+1]) : '';
            $result['notedByTitle'] = isset($lines[$i+2]) ? trim($lines[$i+2]) : '';
        }
    }

    // ── Legend ───────────────────────────────────────────────────────────────────
    if (preg_match('/LEGEND[:\s]*([\s\S]{0,400})(?=prepared by|approved by|noted by|$)/i', $text, $legendMatch)) {
        foreach (explode("\n", $legendMatch[1]) as $ll) {
            $ll = trim($ll);
            if (!$ll) continue;
            if (preg_match('/^([A-Z0-9]+)\s*[-–]\s*(.+)$/i', $ll, $m)) {
                $key = strtoupper(trim($m[1]));
                $val = trim($m[2]);
                if (preg_match('/^(85|O|H)$/', $key)) {
                    $result['legend'][$key] = $val;
                }
            }
        }
    }
    if (!isset($result['legend']['85'])) $result['legend']['85'] = '8:00am to 5:00pm';
    if (!isset($result['legend']['O']))  $result['legend']['O']  = 'Off Duty';
    if (!isset($result['legend']['H']))  $result['legend']['H']  = 'Holiday';

    // ── Employee rows with group detection ───────────────────────────────────────
    $currentGroup = ['label' => '', 'employees' => []];
    $result['groups'][] = &$currentGroup;

    foreach ($lines as $line) {
        $trimmed = trim($line);
        if (!$trimmed) continue;
        
        // Skip header/footer lines
        if (preg_match('/name of employee|department\/unit|schedule of|^legend|prepared by|approved by|noted by|^signature$|no\.\s*of\s*days|^fri$|^sat$|^sun$|^mon$|^tue$|^wed$|^thu$/i', $trimmed)) continue;

        // Group label
        if (preg_match('/^(KPFP|GEAMH|KP|GE|PHO|OPHO|MAB|DIALYSIS)$/i', $trimmed)) {
            $currentGroup = ['label' => strtoupper($trimmed), 'employees' => []];
            $result['groups'][] = &$currentGroup;
            continue;
        }

        // ── Enhanced employee row detection ────────────────────────────────────────
        // Look for name followed by schedule codes
        if (!preg_match('/^([A-Z][A-Za-z\s,\.\'\-]+?)(?=\s+(?:85|8[5S]|[OoHh0]|\[|\(|\d{1,2}\s+\d{1,2}))/u', $trimmed, $nameMatch)) continue;
        
        $namePart = trim($nameMatch[1]);
        if (strlen($namePart) < 5 || preg_match('/^\d/', $namePart) || preg_match('/^(LEGEND|PREPARED|APPROVED|NOTED)/i', $namePart)) continue;

        $rest = trim(substr($trimmed, strlen($namePart)));

        // Extract schedule codes with improved pattern matching
        $codes = [];
        $tokens = preg_split('/[\s\[\]\(\)\|,\/\\\\]+/', $rest, -1, PREG_SPLIT_NO_EMPTY);
        
        foreach ($tokens as $tok) {
            $t = strtoupper(trim($tok));
            
            // Work day: 85, 8S, 851
            if (preg_match('/^8[5S5]1?$/', $t)) {
                $codes[] = '85';
            }
            // Off duty: O, 0, OO, 00
            elseif (preg_match('/^[Oo0]{1,2}$/', $t)) {
                $codes[] = 'O';
            }
            // Holiday: H
            elseif ($t === 'H') {
                $codes[] = 'H';
            }
        }

        // Must have at least 10 schedule codes
        if (count($codes) < 10) continue;

        // Extract number of days
        $numDays = count(array_filter($codes, function($c) { return $c === '85'; }));
        if (preg_match('/\b([12]?\d)\b(?=[^0-9]*$)/', $rest, $numMatch)) {
            $numDays = (int)$numMatch[1];
        }

        // Pad to 31 days
        while (count($codes) < 31) $codes[] = '';
        $schedule = array_slice($codes, 0, 31);

        $currentGroup['employees'][] = [
            'name'     => $namePart,
            'schedule' => $schedule,
            'numDays'  => $numDays,
        ];
    }

    // Remove empty groups
    $result['groups'] = array_values(array_filter($result['groups'], function($g) {
        return count($g['employees']) > 0;
    }));

    return $result;
}

function estimateConfidence(array $extracted): int {
    $count = count(array_filter($extracted, function($v) { return !empty($v); }));
    if ($count === 0) return 30;
    if ($count >= 5)  return 90;
    if ($count >= 3)  return 78;
    return 60;
}

function colLetterToIndex(string $col): int {
    $col   = strtoupper($col);
    $index = 0;
    for ($i = 0; $i < strlen($col); $i++) {
        $index = $index * 26 + (ord($col[$i]) - ord('A') + 1);
    }
    return $index;
}

function buildHtmlTable(array $rows): string {
    if (empty($rows)) return '<p style="color:#aaa;padding:20px;text-align:center">No data found</p>';
    $maxCols = 0;
    foreach ($rows as $r) { $maxCols = max($maxCols, count($r)); }

    // Detect if first row looks like a header (has non-numeric, non-empty cells)
    $firstRow    = $rows[0] ?? [];
    $isHeaderRow = false;
    foreach ($firstRow as $cell) {
        if ($cell !== '' && !is_numeric($cell)) { $isHeaderRow = true; break; }
    }

    $html  = '<table class="scan-table">';
    $first = true;
    foreach ($rows as $row) {
        while (count($row) < $maxCols) $row[] = '';
        if ($first && $isHeaderRow) {
            $html .= '<thead><tr>';
            foreach ($row as $cell) $html .= '<th>' . htmlspecialchars((string)$cell, ENT_QUOTES, 'UTF-8') . '</th>';
            $html .= '</tr></thead><tbody>';
            $first = false;
        } else {
            if ($first) { $html .= '<tbody>'; $first = false; }
            $html .= '<tr>';
            foreach ($row as $cell) $html .= '<td>' . htmlspecialchars((string)$cell, ENT_QUOTES, 'UTF-8') . '</td>';
            $html .= '</tr>';
        }
    }
    $html .= '</tbody></table>';
    return $html;
}

function parseSpreadsheet(string $path, string $ext): array {
    $rows = [];
    $raw  = '';

    if ($ext === 'csv') {
        if (($fh = fopen($path, 'r')) !== false) {
            while (($row = fgetcsv($fh)) !== false) $rows[] = $row;
            fclose($fh);
        }
        $raw = implode("\n", array_map(function($r) { return implode(', ', (array)$r); }, $rows));
        return ['data' => ['rowCount' => count($rows), 'rows' => $rows], 'raw' => $raw, 'html_table' => buildHtmlTable($rows)];
    }

    if (!in_array($ext, ['xlsx', 'xls'])) {
        return ['data' => ['note' => 'Unsupported format'], 'raw' => '', 'html_table' => ''];
    }

    if (!class_exists('ZipArchive')) {
        return ['data' => ['note' => 'ZipArchive not available on server'], 'raw' => '', 'html_table' => '<p style="color:#c0392b;padding:16px;">ZipArchive PHP extension is required to read XLSX files.</p>'];
    }

    $zip = new ZipArchive();
    if ($zip->open($path) !== true) {
        return ['data' => ['note' => 'Could not open file'], 'raw' => '', 'html_table' => '<p style="color:#c0392b;padding:16px;">Could not open the XLSX file.</p>'];
    }

    // ── Read shared strings ───────────────────────────────────────────────────
    $sharedStrings = [];
    $ssXml = $zip->getFromName('xl/sharedStrings.xml');
    if ($ssXml) {
        // Strip namespaces for reliable parsing
        $ssXml = preg_replace('/(<\/?)(\w+):/', '$1', $ssXml);
        $ssXml = preg_replace('/\s\w+:[\w]+=(?:"[^"]*"|\'[^\']*\')/', '', $ssXml);
        $xml = @simplexml_load_string($ssXml);
        if ($xml) {
            foreach ($xml->si as $si) {
                // Collect all <t> text nodes (handles rich text runs)
                $text = '';
                foreach ($si->r as $r) {
                    if (isset($r->t)) $text .= (string)$r->t;
                }
                if (!$text && isset($si->t)) $text = (string)$si->t;
                $sharedStrings[] = $text;
            }
        }
    }

    // ── Find the first sheet ──────────────────────────────────────────────────
    $sheetXml = null;
    // Try sheet1 first, then scan workbook for sheet list
    for ($i = 1; $i <= 10; $i++) {
        $xml = $zip->getFromName("xl/worksheets/sheet{$i}.xml");
        if ($xml) { $sheetXml = $xml; break; }
    }

    if (!$sheetXml) {
        $zip->close();
        return ['data' => ['note' => 'No worksheet found in file'], 'raw' => '', 'html_table' => '<p style="color:#c0392b;padding:16px;">No worksheet found in this XLSX file.</p>'];
    }

    $zip->close();

    // Strip namespaces
    $sheetXml = preg_replace('/(<\/?)(\w+):/', '$1', $sheetXml);
    $sheetXml = preg_replace('/\s\w+:[\w]+=(?:"[^"]*"|\'[^\']*\')/', '', $sheetXml);

    $xml = @simplexml_load_string($sheetXml);
    if (!$xml) {
        return ['data' => ['note' => 'Could not parse worksheet XML'], 'raw' => '', 'html_table' => '<p style="color:#c0392b;padding:16px;">Could not parse worksheet XML.</p>'];
    }

    // ── Parse rows ────────────────────────────────────────────────────────────
    foreach ($xml->sheetData->row as $row) {
        $rowData = [];
        $prevCol = 0;
        foreach ($row->c as $cell) {
            $ref    = (string)($cell['r'] ?? '');
            $colRef = preg_replace('/[0-9]/', '', $ref);
            $colIdx = $colRef ? colLetterToIndex($colRef) : ($prevCol + 1);

            // Fill gaps with empty cells
            while ($prevCol < $colIdx - 1) { $rowData[] = ''; $prevCol++; }

            $t = (string)($cell['t'] ?? '');
            $v = isset($cell->v) ? (string)$cell->v : '';

            if ($t === 's') {
                // Shared string
                $idx = (int)$v;
                $rowData[] = isset($sharedStrings[$idx]) ? $sharedStrings[$idx] : '';
            } elseif ($t === 'b') {
                $rowData[] = $v === '1' ? 'TRUE' : 'FALSE';
            } elseif ($t === 'inlineStr') {
                $is = '';
                foreach ($cell->is->r as $r) { if (isset($r->t)) $is .= (string)$r->t; }
                if (!$is && isset($cell->is->t)) $is = (string)$cell->is->t;
                $rowData[] = $is;
            } else {
                $rowData[] = $v;
            }
            $prevCol = $colIdx;
        }

        // Skip completely empty rows
        if (!empty(array_filter($rowData, function($c) { return $c !== ''; }))) {
            $rows[] = $rowData;
            $raw   .= implode("\t", $rowData) . "\n";
        }
    }

    if (empty($rows)) {
        return ['data' => ['note' => 'File appears to be empty'], 'raw' => '', 'html_table' => '<p style="color:#aaa;padding:16px;text-align:center;">The spreadsheet appears to be empty.</p>'];
    }

    return [
        'data'       => ['rowCount' => count($rows)],
        'raw'        => $raw,
        'html_table' => buildHtmlTable($rows),
    ];
}

// ── Pure-PHP PDF text extractor ───────────────────────────────────────────────
function extractPdfText(string $path): string {
    $content = @file_get_contents($path);
    if (!$content) return '';

    $text = '';

    // Extract text from BT...ET blocks (PDF text objects)
    preg_match_all('/BT(.*?)ET/s', $content, $btBlocks);
    foreach ($btBlocks[1] as $block) {
        // Extract strings from Tj, TJ, ' operators
        preg_match_all('/\(((?:[^()\\\\]|\\\\.)*)\)\s*(?:Tj|\'|\")/s', $block, $tjMatches);
        foreach ($tjMatches[1] as $str) {
            $decoded = pdfDecodeString($str);
            if ($decoded) $text .= $decoded . ' ';
        }
        // TJ arrays: [(text) spacing (text) ...]
        preg_match_all('/\[((?:[^\[\]]|\((?:[^()\\\\]|\\\\.)*\))*)\]\s*TJ/s', $block, $tjArrays);
        foreach ($tjArrays[1] as $arr) {
            preg_match_all('/\(((?:[^()\\\\]|\\\\.)*)\)/', $arr, $strParts);
            foreach ($strParts[1] as $str) {
                $decoded = pdfDecodeString($str);
                if ($decoded) $text .= $decoded;
            }
            $text .= ' ';
        }
        // Add newline after each BT block
        $text .= "\n";
    }

    // Clean up
    $text = preg_replace('/[ \t]+/', ' ', $text);
    $text = preg_replace('/\n{3,}/', "\n\n", $text);
    return trim($text);
}

function pdfDecodeString(string $s): string {
    // Unescape PDF string escapes
    $s = str_replace(['\\n','\\r','\\t','\\b','\\f','\\\\','\\(','\\)'],
                     ["\n","\r","\t","\x08","\x0C",'\\','(', ')'], $s);
    // Decode octal escapes \ddd
    $s = preg_replace_callback('/\\\\([0-7]{1,3})/', function($m) {
        return chr(octdec($m[1]));
    }, $s);
    // Filter to printable ASCII + common chars
    $s = preg_replace('/[^\x20-\x7E\n\r\t]/', '', $s);
    return trim($s);
}

function buildPdfHtml(string $text): string {
    if (!$text) return '';
    $lines = explode("\n", $text);
    $html  = '<div class="docx-body">';
    foreach ($lines as $line) {
        $line = trim($line);
        if (!$line) { $html .= '<br>'; continue; }
        // Detect headings: short ALL-CAPS lines or lines ending with colon
        if (strlen($line) < 80 && strtoupper($line) === $line && preg_match('/[A-Z]{3,}/', $line)) {
            $html .= '<h3 class="docx-h">' . htmlspecialchars($line, ENT_QUOTES, 'UTF-8') . '</h3>';
        } else {
            $html .= '<p class="docx-p">' . htmlspecialchars($line, ENT_QUOTES, 'UTF-8') . '</p>';
        }
    }
    $html .= '</div>';
    return $html;
}

function extractDocx(string $path): array {
    if (!class_exists('ZipArchive')) return ['text' => '', 'html_table' => '<p style="color:#c0392b;padding:16px;">ZipArchive extension not available on this server.</p>'];
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) return ['text' => '', 'html_table' => '<p style="color:#c0392b;padding:16px;">Could not open file.</p>'];

    $xmlContent = $zip->getFromName('word/document.xml');
    $zip->close();
    if (!$xmlContent) return ['text' => '', 'html_table' => '<p style="color:#c0392b;padding:16px;">Could not read document content.</p>'];

    // Strip XML namespaces for easier parsing
    $xmlContent = preg_replace('/(<\/?)(\w+):/', '$1', $xmlContent);
    $xmlContent = preg_replace('/\s\w+:[\w]+="[^"]*"/', '', $xmlContent);

    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    @$dom->loadXML($xmlContent);
    libxml_clear_errors();

    $html    = '<div class="docx-body">';
    $rawText = '';

    $body = $dom->getElementsByTagName('body')->item(0);
    if (!$body) {
        // Fallback: strip all XML tags and return plain text
        $plain = strip_tags(str_replace(['</w:p>', '</w:tr>'], "\n", $xmlContent));
        $plain = preg_replace('/\s+/', ' ', $plain);
        $html .= '<pre style="white-space:pre-wrap;font-size:13px;">' . htmlspecialchars(trim($plain), ENT_QUOTES, 'UTF-8') . '</pre>';
        $html .= '</div>';
        return ['text' => trim($plain), 'html_table' => $html];
    }

    foreach ($body->childNodes as $node) {
        $localName = $node->localName ?? $node->nodeName;

        // ── Paragraph ────────────────────────────────────────────────────────
        if ($localName === 'p') {
            $paraHtml = '';
            $paraText = '';
            $styleId  = '';

            // Get paragraph style
            foreach ($node->getElementsByTagName('pStyle') as $ps) {
                $styleId = strtolower($ps->getAttribute('val') ?: $ps->getAttribute('w:val'));
            }

            // Collect runs
            foreach ($node->getElementsByTagName('r') as $run) {
                $isBold = $run->getElementsByTagName('b')->length > 0;
                $isItal = $run->getElementsByTagName('i')->length > 0;
                $runText = '';
                foreach ($run->getElementsByTagName('t') as $t) {
                    $runText .= $t->nodeValue;
                }
                if (!$runText) continue;
                $escaped = htmlspecialchars($runText, ENT_QUOTES, 'UTF-8');
                if ($isBold && $isItal) $escaped = "<em><strong>$escaped</strong></em>";
                elseif ($isBold)        $escaped = "<strong>$escaped</strong>";
                elseif ($isItal)        $escaped = "<em>$escaped</em>";
                $paraHtml .= $escaped;
                $paraText .= $runText;
            }

            if (trim($paraText) === '') {
                $html    .= '<br>';
                $rawText .= "\n";
                continue;
            }
            $rawText .= $paraText . "\n";

            if (preg_match('/^heading(\d)$/i', $styleId, $hm)) {
                $level = min((int)$hm[1], 6);
                $html .= "<h{$level} class=\"docx-h\">{$paraHtml}</h{$level}>";
            } elseif (in_array($styleId, ['title', 'subtitle'])) {
                $html .= "<h1 class=\"docx-title\">{$paraHtml}</h1>";
            } else {
                $numPr = $node->getElementsByTagName('numPr')->item(0);
                if ($numPr) {
                    $html .= "<li class=\"docx-li\">{$paraHtml}</li>";
                } else {
                    $html .= "<p class=\"docx-p\">{$paraHtml}</p>";
                }
            }
        }

        // ── Table ─────────────────────────────────────────────────────────────
        elseif ($localName === 'tbl') {
            $tableRows = [];
            foreach ($node->getElementsByTagName('tr') as $tr) {
                $rowData = [];
                foreach ($tr->getElementsByTagName('tc') as $tc) {
                    $cellText = '';
                    foreach ($tc->getElementsByTagName('t') as $t) {
                        $cellText .= $t->nodeValue;
                    }
                    $rowData[] = $cellText;
                    $rawText  .= $cellText . "\t";
                }
                if (!empty(array_filter($rowData))) {
                    $tableRows[] = $rowData;
                    $rawText .= "\n";
                }
            }
            if (!empty($tableRows)) {
                $html .= buildHtmlTable($tableRows);
            }
        }
    }

    $html .= '</div>';
    return ['text' => trim($rawText), 'html_table' => $html];
}

// ── OCR.space API integration ─────────────────────────────────────────────────
function ocrSpaceScan(string $filePath, string $fileName): array {
    $apiKey   = 'K83763523288957';
    $endpoint = 'https://api.ocr.space/parse/image';

    // Preprocess image for better OCR accuracy
    $processedPath = preprocessImageForOCR($filePath);
    if (!$processedPath) $processedPath = $filePath;

    // Build multipart form data
    $boundary = '----OCRBoundary' . uniqid();
    $body     = '';

    // File field
    $mimeType = mime_content_type($processedPath);
    $fileData = file_get_contents($processedPath);
    if (!$fileData) return ['text' => '', 'confidence' => 0];

    $body .= "--{$boundary}\r\n";
    $body .= "Content-Disposition: form-data; name=\"file\"; filename=\"{$fileName}\"\r\n";
    $body .= "Content-Type: {$mimeType}\r\n\r\n";
    $body .= $fileData . "\r\n";

    // Enhanced parameters for maximum data extraction
    $params = [
        'apikey'              => $apiKey,
        'language'            => 'eng',
        'isOverlayRequired'   => 'false',  // Disable overlay for faster processing
        'detectOrientation'   => 'true',
        'scale'               => 'true',
        'OCREngine'           => '2',      // Engine 2 is more accurate for tables
        'isTable'             => 'true',   // Enable table detection
        'filetype'            => strtoupper(pathinfo($fileName, PATHINFO_EXTENSION)),
    ];
    
    foreach ($params as $key => $val) {
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Disposition: form-data; name=\"{$key}\"\r\n\r\n";
        $body .= $val . "\r\n";
    }
    $body .= "--{$boundary}--\r\n";

    $context = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: multipart/form-data; boundary={$boundary}\r\n" .
                         "Content-Length: " . strlen($body) . "\r\n",
            'content' => $body,
            'timeout' => 90,
            'ignore_errors' => true,
        ],
    ]);

    $response = @file_get_contents($endpoint, false, $context);
    
    // Clean up processed image if different from original
    if ($processedPath !== $filePath && file_exists($processedPath)) {
        @unlink($processedPath);
    }
    
    if (!$response) return ['text' => '', 'confidence' => 0];

    $json = json_decode($response, true);
    if (!$json || !isset($json['ParsedResults'])) {
        return ['text' => '', 'confidence' => 0];
    }

    $fullText   = '';
    $totalConf  = 0;
    $pageCount  = 0;

    foreach ($json['ParsedResults'] as $page) {
        if (!empty($page['ParsedText'])) {
            $rawText = $page['ParsedText'];
            // Post-process OCR text for better accuracy
            $cleanedText = postProcessOCRText($rawText);
            $fullText  .= $cleanedText . "\n";
            $totalConf += (float)($page['TextOverlay']['MeanConfidence'] ?? 85);
            $pageCount++;
        }
    }

    $confidence = $pageCount > 0 ? (int)round($totalConf / $pageCount) : 0;
    if ($confidence === 0 && $fullText) $confidence = 85;

    return [
        'text'       => trim($fullText),
        'confidence' => min($confidence, 99),
    ];
}

// ── Image preprocessing for better OCR ────────────────────────────────────────
function preprocessImageForOCR(string $filePath): ?string {
    // Check if GD library is available
    if (!function_exists('imagecreatefromjpeg')) return null;

    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    
    // Load image based on type
    $img = null;
    switch ($ext) {
        case 'jpg':
        case 'jpeg':
            $img = @imagecreatefromjpeg($filePath);
            break;
        case 'png':
            $img = @imagecreatefrompng($filePath);
            break;
        case 'gif':
            $img = @imagecreatefromgif($filePath);
            break;
        case 'bmp':
            $img = @imagecreatefrombmp($filePath);
            break;
        case 'webp':
            $img = @imagecreatefromwebp($filePath);
            break;
        default:
            return null;
    }

    if (!$img) return null;

    $width  = imagesx($img);
    $height = imagesy($img);

    // Step 1: Upscale if image is too small (min 2000px on longest side for better OCR)
    $minSize = 2000;
    $maxDim  = max($width, $height);
    if ($maxDim < $minSize) {
        $scale     = $minSize / $maxDim;
        $newWidth  = (int)($width * $scale);
        $newHeight = (int)($height * $scale);
        $scaled    = imagecreatetruecolor($newWidth, $newHeight);
        
        // Use bicubic interpolation for better quality
        imagecopyresampled($scaled, $img, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagedestroy($img);
        $img    = $scaled;
        $width  = $newWidth;
        $height = $newHeight;
    }

    // Step 2: Convert to grayscale and enhance contrast
    imagefilter($img, IMG_FILTER_GRAYSCALE);
    imagefilter($img, IMG_FILTER_CONTRAST, -20); // Increase contrast more aggressively

    // Step 3: Sharpen for better edge detection
    $sharpenMatrix = [
        [-1, -1, -1],
        [-1, 16, -1],
        [-1, -1, -1]
    ];
    $divisor = 8;
    $offset  = 0;
    imageconvolution($img, $sharpenMatrix, $divisor, $offset);

    // Step 4: Brightness adjustment to ensure text is dark on light background
    imagefilter($img, IMG_FILTER_BRIGHTNESS, 15);

    // Step 5: Apply adaptive threshold for better text separation
    // This is a simplified version - for production, consider using ImageMagick
    $imageData = [];
    for ($y = 0; $y < $height; $y++) {
        for ($x = 0; $x < $width; $x++) {
            $rgb = imagecolorat($img, $x, $y);
            $gray = ($rgb >> 16) & 0xFF; // Already grayscale, just extract value
            $imageData[$y][$x] = $gray;
        }
    }

    // Apply simple threshold (Otsu's method approximation)
    $histogram = array_fill(0, 256, 0);
    foreach ($imageData as $row) {
        foreach ($row as $pixel) {
            $histogram[$pixel]++;
        }
    }

    $total = $width * $height;
    $sum = 0;
    for ($i = 0; $i < 256; $i++) {
        $sum += $i * $histogram[$i];
    }

    $sumB = 0;
    $wB = 0;
    $wF = 0;
    $maxVariance = 0;
    $threshold = 0;

    for ($t = 0; $t < 256; $t++) {
        $wB += $histogram[$t];
        if ($wB == 0) continue;

        $wF = $total - $wB;
        if ($wF == 0) break;

        $sumB += $t * $histogram[$t];
        $mB = $sumB / $wB;
        $mF = ($sum - $sumB) / $wF;

        $variance = $wB * $wF * ($mB - $mF) * ($mB - $mF);

        if ($variance > $maxVariance) {
            $maxVariance = $variance;
            $threshold = $t;
        }
    }

    // Apply threshold
    for ($y = 0; $y < $height; $y++) {
        for ($x = 0; $x < $width; $x++) {
            $value = $imageData[$y][$x] >= $threshold ? 255 : 0;
            $color = imagecolorallocate($img, $value, $value, $value);
            imagesetpixel($img, $x, $y, $color);
        }
    }

    // Save processed image
    $tempPath = sys_get_temp_dir() . '/ocr_processed_' . uniqid() . '.png';
    imagepng($img, $tempPath, 0); // 0 = no compression for best quality
    imagedestroy($img);

    return $tempPath;
}

// ── Post-process OCR text for better accuracy ─────────────────────────────────
function postProcessOCRText(string $text): string {
    // Common OCR mistakes and corrections
    $corrections = [
        // Number confusions - be more aggressive
        '/\b[Oo0]{2,}\b/'     => 'O',  // oo, 00, ooo → O (Off Duty)
        '/\b8[5S5]\b/'        => '85', // 8S, 85 → 85
        '/\b[Il1]\s*[Il1]\b/' => '11', // Il or 1l → 11
        '/\b[0O](?=\s|$)/'    => 'O',  // Standalone 0 or O → O
        
        // Letter confusions in common words
        '/\bH[0O]UR[S5]?\b/i'     => 'HOURS',
        '/\bN[A4]ME\b/i'          => 'NAME',
        '/\bD[A4]TE\b/i'          => 'DATE',
        '/\bDEP[A4]RTMENT\b/i'    => 'DEPARTMENT',
        '/\bEMPL[O0]YEE\b/i'      => 'EMPLOYEE',
        '/\bSCHEDULE\b/i'         => 'SCHEDULE',
        '/\bDUT[I1]ES\b/i'        => 'DUTIES',
        '/\bS[I1]GNATURE\b/i'     => 'SIGNATURE',
        
        // Common schedule-related terms
        '/\bGE[A4]MH\b/i'         => 'GEAMH',
        '/\bKPFP\b/i'             => 'KPFP',
        '/\bOPH[O0]\b/i'          => 'OPHO',
        '/\bM[A4]B\b/i'           => 'MAB',
        '/\bDI[A4]LYSIS\b/i'      => 'DIALYSIS',
        
        // Fix common table separators
        '/\s*\|\s*/'              => ' ',  // Remove pipe separators
        '/\s*\[\s*/'              => ' ',  // Remove brackets
        '/\s*\]\s*/'              => ' ',
        '/\s*\(\s*/'              => ' ',  // Remove parentheses
        '/\s*\)\s*/'              => ' ',
        
        // Remove excessive spaces
        '/\s{3,}/'                => '  ', // 3+ spaces → 2 spaces
        '/\t+/'                   => "\t", // Multiple tabs → single tab
    ];

    foreach ($corrections as $pattern => $replacement) {
        $text = preg_replace($pattern, $replacement, $text);
    }

    // Fix common character substitutions in table data
    $text = str_replace([
        '|85|', '[85]', '(85)', '{85}', '«85»',  // Bracketed 85
        '|O|', '[O]', '(O)', '{O}', '«O»',       // Bracketed O
        '|H|', '[H]', '(H)', '{H}', '«H»',       // Bracketed H
        '8S', '8s', '85|', '|85',                // 85 variations
        'oO', 'Oo', 'oo', '00',                  // O variations
    ], [
        '85', '85', '85', '85', '85',
        'O', 'O', 'O', 'O', 'O',
        'H', 'H', 'H', 'H', 'H',
        '85', '85', '85', '85',
        'O', 'O', 'O', 'O',
    ], $text);

    // Clean up line breaks
    $text = preg_replace('/\r\n|\r/', "\n", $text);
    $text = preg_replace('/\n{3,}/', "\n\n", $text);

    return trim($text);
}

// ── Build HTML from OCR text ──────────────────────────────────────────────────
function buildOcrHtml(string $text): string {
    if (!$text) return '';
    
    // Detect Schedule of Duties — render structured table
    if (preg_match('/schedule of duties|schedule of duty/i', $text)) {
        $lines = array_filter(array_map('trim', explode("\n", $text)));
        $parsed = parseScheduleOfDutiesServer($text, $lines);
        return buildScheduleHtml($parsed);
    }
    
    $lines = explode("\n", $text);
    $html  = '<div class="docx-body">';
    foreach ($lines as $line) {
        $line = trim($line);
        if (!$line) { $html .= '<br>'; continue; }
        // Detect table-like rows (multiple tab/space separated columns)
        $cols = preg_split('/\t|  {2,}/', $line);
        $cols = array_filter(array_map('trim', $cols));
        if (count($cols) >= 3) {
            $html .= '<p class="docx-p ocr-row">';
            foreach ($cols as $col) {
                $html .= '<span class="ocr-cell">' . htmlspecialchars($col, ENT_QUOTES, 'UTF-8') . '</span>';
            }
            $html .= '</p>';
        } else {
            $html .= '<p class="docx-p">' . htmlspecialchars($line, ENT_QUOTES, 'UTF-8') . '</p>';
        }
    }
    $html .= '</div>';
    return $html;
}

// ── Schedule of Duties HTML renderer ──────────────────────────────────────────
function buildScheduleHtml(array $parsed): string {
    $days = range(1, 31);
    
    // Count total employees
    $totalEmps = 0;
    foreach ($parsed['groups'] as $g) {
        $totalEmps += count($g['employees']);
    }

    $html = '<div class="sched-doc">
        <div class="sched-header">
            <div class="sched-hosp">' . htmlspecialchars($parsed['hospital'], ENT_QUOTES, 'UTF-8') . '</div>';
    
    if ($parsed['project'])  $html .= '<div class="sched-sub">' . htmlspecialchars($parsed['project'], ENT_QUOTES, 'UTF-8') . '</div>';
    if ($parsed['location']) $html .= '<div class="sched-sub">' . htmlspecialchars($parsed['location'], ENT_QUOTES, 'UTF-8') . '</div>';
    
    $html .= '<div class="sched-title">Schedule of Duties</div>
            <div class="sched-period">' . htmlspecialchars($parsed['period'], ENT_QUOTES, 'UTF-8') . '</div>
        </div>

        <div class="sched-dept-row">
            <span><strong>Department/Unit:</strong> ' . htmlspecialchars($parsed['department'], ENT_QUOTES, 'UTF-8') . '</span>
        </div>

        <div class="sched-table-wrap">
        <table class="sched-table">
            <thead>
                <tr class="tr-days">
                    <th rowspan="2" class="th-name">NAME OF EMPLOYEE</th>';
    
    foreach ($days as $d) {
        $html .= '<th class="th-day">' . $d . '</th>';
    }
    
    $html .= '<th rowspan="2" class="th-days">No. of<br>days</th>
                    <th rowspan="2" class="th-sig">Signature</th>
                </tr>
                <tr class="tr-daynames">';
    
    foreach ($days as $d) {
        $dow = ['FRI','SAT','SUN','MON','TUE','WED','THU'];
        $dowName = $dow[($d-1) % 7];
        $isWeekend = in_array($dowName, ['SAT','SUN']);
        $html .= '<th class="th-dow' . ($isWeekend ? ' th-weekend' : '') . '">' . $dowName . '</th>';
    }
    
    $html .= '</tr>
            </thead>
            <tbody>';

    if ($totalEmps === 0) {
        // Show raw OCR text
        $rawLines = array_filter(explode("\n", $parsed['rawText'] ?? ''));
        $html .= '<tr><td colspan="' . (count($days) + 3) . '" class="td-empty">
            <div style="text-align:left;padding:8px;">
                <strong style="color:#c0392b;">⚠ Employee schedule rows could not be auto-extracted.</strong><br>
                <small style="color:#888;">The OCR captured the following text — employee data may be present but in an unexpected format:</small>
                <pre style="margin-top:8px;font-size:10px;background:#f8f9fa;padding:10px;border-radius:6px;max-height:200px;overflow-y:auto;white-space:pre-wrap;text-align:left;">' . htmlspecialchars(implode("\n", $rawLines), ENT_QUOTES, 'UTF-8') . '</pre>
            </div>
        </td></tr>';
    } else {
        foreach ($parsed['groups'] as $group) {
            if ($group['label']) {
                $html .= '<tr><td colspan="' . (count($days) + 3) . '" class="td-group">' . htmlspecialchars($group['label'], ENT_QUOTES, 'UTF-8') . '</td></tr>';
            }
            foreach ($group['employees'] as $emp) {
                $html .= '<tr><td class="td-name">' . htmlspecialchars($emp['name'], ENT_QUOTES, 'UTF-8') . '</td>';
                for ($d = 0; $d < 31; $d++) {
                    $code = isset($emp['schedule'][$d]) ? $emp['schedule'][$d] : '';
                    $cls  = $code === '85' ? 'day-work' : ($code === 'H' ? 'day-holiday' : ($code === 'O' ? 'day-off' : ''));
                    $html .= '<td class="td-day ' . $cls . '">' . htmlspecialchars($code, ENT_QUOTES, 'UTF-8') . '</td>';
                }
                $html .= '<td class="td-days">' . $emp['numDays'] . '</td>';
                $html .= '<td class="td-sig"></td>';
                $html .= '</tr>';
            }
        }
    }

    $html .= '</tbody></table></div>';

    // Legend
    if (!empty($parsed['legend'])) {
        $html .= '<div class="sched-legend"><strong>LEGEND:</strong>';
        foreach ($parsed['legend'] as $k => $v) {
            $html .= '<span class="leg-item"><strong>' . htmlspecialchars($k, ENT_QUOTES, 'UTF-8') . '</strong> &nbsp;-&nbsp; ' . htmlspecialchars($v, ENT_QUOTES, 'UTF-8') . '</span>';
        }
        $html .= '</div>';
    }

    // Signatories
    $html .= '<div class="sched-sigs">
        <div class="sig-col">
            <div class="sig-label">Prepared by:</div>
            <div class="sig-name">' . htmlspecialchars($parsed['preparedBy'], ENT_QUOTES, 'UTF-8') . '</div>
            <div class="sig-title">' . htmlspecialchars($parsed['preparedByTitle'], ENT_QUOTES, 'UTF-8') . '</div>
        </div>
        <div class="sig-col">
            <div class="sig-label">Approved by:</div>
            <div class="sig-name">' . htmlspecialchars($parsed['approvedBy'], ENT_QUOTES, 'UTF-8') . '</div>
            <div class="sig-title">' . htmlspecialchars($parsed['approvedByTitle'], ENT_QUOTES, 'UTF-8') . '</div>
        </div>
        <div class="sig-col">
            <div class="sig-label">Noted by:</div>
            <div class="sig-name">' . htmlspecialchars($parsed['notedBy'], ENT_QUOTES, 'UTF-8') . '</div>
            <div class="sig-title">' . htmlspecialchars($parsed['notedByTitle'], ENT_QUOTES, 'UTF-8') . '</div>
        </div>
    </div>
    </div>

    <style>
        .sched-doc { font-family: Arial, sans-serif; font-size: 11px; padding: 12px; background: #fff; }
        .sched-header { text-align: center; margin-bottom: 12px; }
        .sched-hosp { font-size: 13px; font-weight: 700; color: #1a3a5c; }
        .sched-sub { font-size: 11px; color: #555; }
        .sched-title { font-size: 12px; font-weight: 700; margin-top: 8px; text-decoration: underline; }
        .sched-period { font-size: 12px; font-weight: 700; text-decoration: underline; }
        .sched-dept-row { margin-bottom: 8px; font-size: 11px; }
        .sched-table-wrap { overflow-x: auto; }
        .sched-table { border-collapse: collapse; font-size: 9px; min-width: 100%; }
        .sched-table th, .sched-table td { border: 1px solid #999; padding: 2px 3px; text-align: center; white-space: nowrap; }
        .th-name, .td-name { text-align: left; min-width: 130px; max-width: 160px; font-weight: 600; background: #f8f9fa; white-space: normal; }
        .th-day, .th-dow { min-width: 18px; max-width: 22px; font-size: 8px; background: #1a3a5c; color: #fff; }
        .th-weekend { background: #c0392b; }
        .th-days, .th-sig { background: #1a3a5c; color: #fff; min-width: 36px; font-size: 9px; }
        .td-days { font-weight: 700; color: #1a3a5c; }
        .td-sig { min-width: 60px; }
        .td-group { background: #e8f0fe; font-weight: 700; color: #1a3a5c; text-align: left; padding: 3px 6px; font-size: 10px; }
        .td-empty { text-align: center; color: #aaa; padding: 20px; font-size: 12px; }
        .day-work { background: #eafaf1; color: #1a6b3c; font-weight: 700; }
        .day-off { background: #f9f9f9; color: #bbb; }
        .day-holiday { background: #fef3e2; color: #e67e22; font-weight: 700; }
        .sched-legend { margin-top: 10px; font-size: 10px; display: flex; gap: 12px; flex-wrap: wrap; align-items: center; }
        .leg-item { background: #f8f9fa; border: 1px solid #ddd; padding: 2px 8px; border-radius: 4px; }
        .sched-sigs { display: flex; gap: 40px; margin-top: 20px; flex-wrap: wrap; }
        .sig-col { display: flex; flex-direction: column; gap: 2px; min-width: 160px; }
        .sig-label { font-size: 10px; color: #888; }
        .sig-name { font-size: 11px; font-weight: 700; color: #1a3a5c; border-bottom: 1px solid #333; padding-bottom: 2px; min-width: 140px; }
        .sig-title { font-size: 10px; color: #555; }
    </style>';

    return $html;
}
