<?php
require_once 'db.php';

$method = $_SERVER['REQUEST_METHOD'];
$conn   = getConnection();
$action = $_GET['action'] ?? '';

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
            $result = $conn->query('SELECT * FROM ai_scanned_docs ORDER BY created_at DESC LIMIT 200');
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
        'isOverlayRequired'   => 'true',
        'detectOrientation'   => 'true',
        'scale'               => 'true',
        'OCREngine'           => '2',
        'isTable'             => 'true',
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

    // Step 1: Upscale if image is too small (min 1500px on longest side)
    $minSize = 1500;
    $maxDim  = max($width, $height);
    if ($maxDim < $minSize) {
        $scale     = $minSize / $maxDim;
        $newWidth  = (int)($width * $scale);
        $newHeight = (int)($height * $scale);
        $scaled    = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled($scaled, $img, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagedestroy($img);
        $img    = $scaled;
        $width  = $newWidth;
        $height = $newHeight;
    }

    // Step 2: Convert to grayscale and enhance contrast
    imagefilter($img, IMG_FILTER_GRAYSCALE);
    imagefilter($img, IMG_FILTER_CONTRAST, -15); // Increase contrast

    // Step 3: Sharpen for better edge detection
    $sharpenMatrix = [
        [-1, -1, -1],
        [-1, 16, -1],
        [-1, -1, -1]
    ];
    $divisor = 8;
    $offset  = 0;
    imageconvolution($img, $sharpenMatrix, $divisor, $offset);

    // Step 4: Slight brightness adjustment
    imagefilter($img, IMG_FILTER_BRIGHTNESS, 10);

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
        // Number confusions
        '/\b[Oo0]{2}\b/'      => 'O',  // oo or 00 → O (Off Duty)
        '/\b8[5S]\b/'         => '85', // 8S → 85
        '/\b[Il1]\s*[Il1]\b/' => '11', // Il or 1l → 11
        
        // Letter confusions in common words
        '/\bH[0O]UR[S5]?\b/i'     => 'HOURS',
        '/\bN[A4]ME\b/i'          => 'NAME',
        '/\bD[A4]TE\b/i'          => 'DATE',
        '/\bDEP[A4]RTMENT\b/i'    => 'DEPARTMENT',
        '/\bEMPL[O0]YEE\b/i'      => 'EMPLOYEE',
        '/\bSCHEDULE\b/i'         => 'SCHEDULE',
        '/\bDUT[I1]ES\b/i'        => 'DUTIES',
        
        // Common schedule-related terms
        '/\bGE[A4]MH\b/i'         => 'GEAMH',
        '/\bKPFP\b/i'             => 'KPFP',
        '/\bOPH[O0]\b/i'          => 'OPHO',
        
        // Remove excessive spaces
        '/\s{3,}/'                => '  ', // 3+ spaces → 2 spaces
        '/\t+/'                   => "\t", // Multiple tabs → single tab
    ];

    foreach ($corrections as $pattern => $replacement) {
        $text = preg_replace($pattern, $replacement, $text);
    }

    // Fix common character substitutions in table data
    $text = str_replace([
        '|85|', '[85]', '(85)', '{85}',  // Bracketed 85
        '|O|', '[O]', '(O)', '{O}',      // Bracketed O
        '|H|', '[H]', '(H)', '{H}',      // Bracketed H
    ], [
        '85', '85', '85', '85',
        'O', 'O', 'O', 'O',
        'H', 'H', 'H', 'H',
    ], $text);

    // Clean up line breaks
    $text = preg_replace('/\r\n|\r/', "\n", $text);
    $text = preg_replace('/\n{3,}/', "\n\n", $text);

    return trim($text);
}

// ── Build HTML from OCR text ──────────────────────────────────────────────────
function buildOcrHtml(string $text): string {
    if (!$text) return '';
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
