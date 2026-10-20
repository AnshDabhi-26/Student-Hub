<?php
require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

function sendJson(array $payload): void
{
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

if (!$db_connected || !$conn instanceof mysqli) {
    sendJson([
        'success' => false,
        'message' => 'Database connection failed. Please check MySQL configuration.',
        'error' => $db_error ?? 'Unknown DB error'
    ]);
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$input = [];

if (in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    $raw = file_get_contents('php://input');

    if (stripos($contentType, 'application/json') !== false && $raw !== '') {
        $decoded = json_decode($raw, true);
        $input = is_array($decoded) ? $decoded : [];
    } else {
        $input = $_POST;
    }
}

if ($method === 'GET') {
    $action = $_GET['action'] ?? 'list';
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    if ($action === 'list') {
        $result = $conn->query('SELECT * FROM events ORDER BY event_date DESC, id DESC');
        $events = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        sendJson(['success' => true, 'message' => 'Events loaded successfully.', 'data' => $events]);
    }

    if ($action === 'view') {
        if ($id <= 0) {
            sendJson(['success' => false, 'message' => 'Invalid event ID.']);
        }

        $stmt = $conn->prepare('SELECT * FROM events WHERE id = ? LIMIT 1');
        if (!$stmt) {
            sendJson(['success' => false, 'message' => 'Unable to fetch event details.']);
        }

        $stmt->bind_param('i', $id);
        $stmt->execute();
        $record = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$record) {
            sendJson(['success' => false, 'message' => 'Event not found.']);
        }

        sendJson(['success' => true, 'message' => 'Event found.', 'data' => $record]);
    }

    sendJson(['success' => false, 'message' => 'Unsupported GET action.']);
}

if ($method !== 'POST') {
    sendJson(['success' => false, 'message' => 'Only POST requests are supported for CRUD operations.']);
}

$action = $input['action'] ?? $_POST['action'] ?? 'add';

if ($action === 'add') {
    $title = trim((string) ($input['title'] ?? $_POST['title'] ?? ''));
    $description = trim((string) ($input['description'] ?? $_POST['description'] ?? ''));
    $category = trim((string) ($input['category'] ?? $_POST['category'] ?? 'Technical'));
    $eventDate = trim((string) ($input['event_date'] ?? $_POST['event_date'] ?? ''));
    $location = trim((string) ($input['location'] ?? $_POST['location'] ?? ''));
    $capacity = (int) ($input['capacity'] ?? $_POST['capacity'] ?? 0);
    $status = trim((string) ($input['status'] ?? $_POST['status'] ?? 'Upcoming'));

    if ($title === '' || $description === '' || $eventDate === '' || $location === '') {
        sendJson(['success' => false, 'message' => 'Title, description, date and location are required.']);
    }

    if ($capacity <= 0) {
        sendJson(['success' => false, 'message' => 'Capacity must be greater than zero.']);
    }

    $stmt = $conn->prepare('INSERT INTO events (title, description, category, event_date, location, capacity, status) VALUES (?, ?, ?, ?, ?, ?, ?)');
    if (!$stmt) {
        sendJson(['success' => false, 'message' => 'Unable to prepare add event query.']);
    }

    $stmt->bind_param('sssssis', $title, $description, $category, $eventDate, $location, $capacity, $status);
    if (!$stmt->execute()) {
        $stmt->close();
        sendJson(['success' => false, 'message' => 'Event could not be added: ' . $stmt->error]);
    }

    $newId = $conn->insert_id;
    $stmt->close();

    $event = $conn->query('SELECT * FROM events WHERE id = ' . (int) $newId)->fetch_assoc();
    sendJson(['success' => true, 'message' => 'Event added successfully.', 'data' => $event]);
}

if ($action === 'update') {
    $id = (int) ($input['id'] ?? $_POST['id'] ?? 0);
    $title = trim((string) ($input['title'] ?? $_POST['title'] ?? ''));
    $description = trim((string) ($input['description'] ?? $_POST['description'] ?? ''));
    $category = trim((string) ($input['category'] ?? $_POST['category'] ?? 'Technical'));
    $eventDate = trim((string) ($input['event_date'] ?? $_POST['event_date'] ?? ''));
    $location = trim((string) ($input['location'] ?? $_POST['location'] ?? ''));
    $capacity = (int) ($input['capacity'] ?? $_POST['capacity'] ?? 0);
    $status = trim((string) ($input['status'] ?? $_POST['status'] ?? 'Upcoming'));

    if ($id <= 0) {
        sendJson(['success' => false, 'message' => 'Invalid event ID for update.']);
    }

    if ($title === '' || $description === '' || $eventDate === '' || $location === '') {
        sendJson(['success' => false, 'message' => 'Title, description, date and location are required.']);
    }

    if ($capacity <= 0) {
        sendJson(['success' => false, 'message' => 'Capacity must be greater than zero.']);
    }

    $stmt = $conn->prepare('UPDATE events SET title = ?, description = ?, category = ?, event_date = ?, location = ?, capacity = ?, status = ? WHERE id = ?');
    if (!$stmt) {
        sendJson(['success' => false, 'message' => 'Unable to prepare update event query.']);
    }

    $stmt->bind_param('sssssisi', $title, $description, $category, $eventDate, $location, $capacity, $status, $id);
    if (!$stmt->execute()) {
        $stmt->close();
        sendJson(['success' => false, 'message' => 'Event could not be updated: ' . $stmt->error]);
    }

    $stmt->close();
    $updated = $conn->query('SELECT * FROM events WHERE id = ' . $id)->fetch_assoc();
    sendJson(['success' => true, 'message' => 'Event updated successfully.', 'data' => $updated]);
}

if ($action === 'delete') {
    $id = (int) ($input['id'] ?? $_POST['id'] ?? 0);
    if ($id <= 0) {
        sendJson(['success' => false, 'message' => 'Invalid event ID for deletion.']);
    }

    $stmt = $conn->prepare('DELETE FROM events WHERE id = ?');
    if (!$stmt) {
        sendJson(['success' => false, 'message' => 'Unable to prepare delete event query.']);
    }

    $stmt->bind_param('i', $id);
    if (!$stmt->execute()) {
        $stmt->close();
        sendJson(['success' => false, 'message' => 'Event could not be deleted: ' . $stmt->error]);
    }

    $stmt->close();
    sendJson(['success' => true, 'message' => 'Event deleted successfully.', 'deleted_id' => $id]);
}

sendJson(['success' => false, 'message' => 'Unsupported action.']);
