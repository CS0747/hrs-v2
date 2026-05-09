<?php
require_once 'db.php';

$method = $_SERVER['REQUEST_METHOD'];
$conn   = getConnection();

// ── Route: /trainings.php?participants=1&training_id=X ──────────────────────
// Handles participant sub-resource separately
if (isset($_GET['participants'])) {
    $training_id = (int)($_GET['training_id'] ?? 0);
    if (!$training_id) sendError('training_id required');

    if ($method === 'GET') {
        // List participants with employee details
        $stmt = $conn->prepare(
            'SELECT tp.id, tp.training_id, tp.employee_id, tp.attended, tp.remarks,
                    e.employee_no, e.last_name, e.first_name, e.middle_name,
                    e.position, e.department
             FROM training_participants tp
             JOIN employees e ON e.id = tp.employee_id
             WHERE tp.training_id = ?
             ORDER BY e.last_name, e.first_name'
        );
        $stmt->bind_param('i', $training_id);
        $stmt->execute();
        sendJson($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    }

    if ($method === 'POST') {
        // Add participant(s) — accepts { employee_id } or { employee_ids: [] }
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) sendError('Invalid JSON body');

        $ids = $data['employee_ids'] ?? ($data['employee_id'] ? [$data['employee_id']] : []);
        if (empty($ids)) sendError('employee_id or employee_ids required');

        $added = 0;
        $stmt  = $conn->prepare(
            'INSERT IGNORE INTO training_participants (training_id, employee_id, attended)
             VALUES (?, ?, 0)'
        );
        foreach ($ids as $emp_id) {
            $emp_id = (int)$emp_id;
            if (!$emp_id) continue;
            $stmt->bind_param('ii', $training_id, $emp_id);
            $stmt->execute();
            $added += $stmt->affected_rows;
        }

        // Update enrolled count
        $conn->query(
            "UPDATE trainings SET enrolled = (
                SELECT COUNT(*) FROM training_participants WHERE training_id = $training_id
             ) WHERE id = $training_id"
        );

        sendJson(['added' => $added, 'message' => "$added participant(s) added"], 201);
    }

    if ($method === 'PUT') {
        // Toggle attended for a participant
        $participant_id = (int)($_GET['participant_id'] ?? 0);
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$participant_id) sendError('participant_id required');

        $attended = (int)($data['attended'] ?? 0);
        $remarks  = $data['remarks'] ?? '';
        $stmt = $conn->prepare(
            'UPDATE training_participants SET attended=?, remarks=? WHERE id=?'
        );
        $stmt->bind_param('isi', $attended, $remarks, $participant_id);
        $stmt->execute();
        sendJson(['message' => 'Participant updated']);
    }

    if ($method === 'DELETE') {
        // Remove participant
        $participant_id = (int)($_GET['participant_id'] ?? 0);
        if (!$participant_id) sendError('participant_id required');

        $stmt = $conn->prepare('DELETE FROM training_participants WHERE id = ?');
        $stmt->bind_param('i', $participant_id);
        $stmt->execute();

        // Update enrolled count
        $conn->query(
            "UPDATE trainings SET enrolled = (
                SELECT COUNT(*) FROM training_participants WHERE training_id = $training_id
             ) WHERE id = $training_id"
        );

        sendJson(['message' => 'Participant removed']);
    }

    sendError('Method not allowed', 405);
}

// ── Main trainings CRUD ──────────────────────────────────────────────────────
switch ($method) {

    case 'GET':
        if (isset($_GET['id'])) {
            $id   = (int) $_GET['id'];
            $stmt = $conn->prepare('SELECT * FROM trainings WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $row ? sendJson($row) : sendError('Training not found', 404);
        } else {
            $result = $conn->query(
                'SELECT t.*,
                    (SELECT COUNT(*) FROM training_participants tp WHERE tp.training_id = t.id) AS enrolled
                 FROM trainings t
                 ORDER BY t.date_from DESC'
            );
            sendJson($result->fetch_all(MYSQLI_ASSOC));
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) sendError('Invalid JSON body');

        $title            = trim($data['title']            ?? '');
        $category         = $data['category']              ?? 'Medical';
        $instructor       = trim($data['instructor']       ?? '');
        $venue            = trim($data['venue']            ?? '');
        $date_from        = ($data['dateFrom']             ?? '') ?: null;
        $date_to          = ($data['dateTo']               ?? '') ?: null;
        $duration         = (int)($data['duration']        ?? 1);
        $max_participants = (int)($data['maxParticipants'] ?? 30);
        $status           = $data['status']                ?? 'Upcoming';
        $description      = trim($data['description']      ?? '');

        if (!$title)     sendError('Title is required');
        if (!$date_from) sendError('Start date is required');

        $stmt = $conn->prepare(
            'INSERT INTO trainings
             (title, category, instructor, venue, date_from, date_to,
              duration, max_participants, enrolled, status, description)
             VALUES (?,?,?,?,?,?,?,?,0,?,?)'
        );
        $stmt->bind_param('ssssssiiss',
            $title, $category, $instructor, $venue, $date_from, $date_to,
            $duration, $max_participants, $status, $description
        );

        if (!$stmt->execute()) sendError('Insert failed: ' . $stmt->error, 500);
        sendJson(['id' => $conn->insert_id, 'message' => 'Training created'], 201);
        break;

    case 'PUT':
        $id   = (int)($_GET['id'] ?? 0);
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$id || !$data) sendError('Invalid request');

        $title            = trim($data['title']            ?? '');
        $category         = $data['category']              ?? 'Medical';
        $instructor       = trim($data['instructor']       ?? '');
        $venue            = trim($data['venue']            ?? '');
        $date_from        = ($data['dateFrom']             ?? '') ?: null;
        $date_to          = ($data['dateTo']               ?? '') ?: null;
        $duration         = (int)($data['duration']        ?? 1);
        $max_participants = (int)($data['maxParticipants'] ?? 30);
        $status           = $data['status']                ?? 'Upcoming';
        $description      = trim($data['description']      ?? '');

        if (!$title)     sendError('Title is required');
        if (!$date_from) sendError('Start date is required');

        $stmt = $conn->prepare(
            'UPDATE trainings SET
             title=?, category=?, instructor=?, venue=?, date_from=?, date_to=?,
             duration=?, max_participants=?, status=?, description=?
             WHERE id=?'
        );
        $stmt->bind_param('ssssssiissi',
            $title, $category, $instructor, $venue, $date_from, $date_to,
            $duration, $max_participants, $status, $description, $id
        );

        if (!$stmt->execute()) sendError('Update failed: ' . $stmt->error, 500);
        sendJson(['message' => 'Training updated']);
        break;

    case 'DELETE':
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) sendError('ID required');
        // training_participants cascade deletes via FK
        $stmt = $conn->prepare('DELETE FROM trainings WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        sendJson(['message' => 'Training deleted']);
        break;

    default:
        sendError('Method not allowed', 405);
}

$conn->close();
