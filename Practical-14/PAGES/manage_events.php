<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

require_role(['admin'], $conn);

$uploadDirectory = __DIR__ . '/../uploads/events';
if (!is_dir($uploadDirectory)) {
    mkdir($uploadDirectory, 0777, true);
}

function studenthub_event_message(string $message, string $type): array
{
    return ['message' => $message, 'type' => $type];
}

$message = '';
$messageType = 'success';
$editEvent = null;

if (isset($_GET['edit'])) {
    $eventId = (int) $_GET['edit'];
    if ($eventId > 0) {
        $stmt = $conn->prepare('SELECT id, title, description, category, event_date, location, capacity, status, poster FROM events WHERE id = ? LIMIT 1');
        if ($stmt) {
            $stmt->bind_param('i', $eventId);
            $stmt->execute();
            $result = $stmt->get_result();
            $editEvent = $result->fetch_assoc();
            $stmt->close();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'add';
    $title = trim((string) ($_POST['title'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));
    $category = trim((string) ($_POST['category'] ?? 'Technical'));
    $eventDate = trim((string) ($_POST['event_date'] ?? ''));
    $location = trim((string) ($_POST['location'] ?? ''));
    $capacity = (int) ($_POST['capacity'] ?? 0);
    $status = trim((string) ($_POST['status'] ?? 'Upcoming'));
    $eventId = (int) ($_POST['id'] ?? 0);

    $allowedStatuses = ['Upcoming', 'Ongoing', 'Completed'];
    $allowedCategories = ['Technical', 'Workshop', 'Seminar', 'Competition', 'Cultural', 'Career', 'Networking', 'Sports'];

    if ($title === '' || $description === '' || $location === '' || $eventDate === '') {
        $message = 'Title, description, date, and location are required.';
        $messageType = 'error';
    } elseif (!in_array($category, $allowedCategories, true)) {
        $message = 'Please select a valid event category.';
        $messageType = 'error';
    } elseif (!in_array($status, $allowedStatuses, true)) {
        $message = 'Please select a valid status.';
        $messageType = 'error';
    } elseif ($capacity <= 0) {
        $message = 'Event capacity must be greater than zero.';
        $messageType = 'error';
    } elseif (!strtotime($eventDate)) {
        $message = 'Please enter a valid event date.';
        $messageType = 'error';
    } else {
        $posterFile = null;
        $currentPoster = null;

        if ($action === 'delete') {
            $deleteId = $eventId;
            $stmt = $conn->prepare('SELECT poster FROM events WHERE id = ? LIMIT 1');
            if ($stmt) {
                $stmt->bind_param('i', $deleteId);
                $stmt->execute();
                $result = $stmt->get_result();
                $currentPoster = $result->fetch_assoc()['poster'] ?? null;
                $stmt->close();
            }

            $stmt = $conn->prepare('DELETE FROM events WHERE id = ?');
            if ($stmt) {
                $stmt->bind_param('i', $deleteId);
                $stmt->execute();
                $stmt->close();

                if ($currentPoster && file_exists($uploadDirectory . DIRECTORY_SEPARATOR . $currentPoster)) {
                    unlink($uploadDirectory . DIRECTORY_SEPARATOR . $currentPoster);
                }

                $message = 'Event deleted successfully.';
                $messageType = 'success';
            } else {
                $message = 'Unable to delete the event.';
                $messageType = 'error';
            }
        } else {
            if (isset($_FILES['poster']) && $_FILES['poster']['error'] !== UPLOAD_ERR_NO_FILE) {
                $uploadedFile = $_FILES['poster'];
                $tmpName = $uploadedFile['tmp_name'] ?? '';
                $originalName = $uploadedFile['name'] ?? '';
                $fileSize = (int) ($uploadedFile['size'] ?? 0);
                $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
                $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];
                $mimeType = mime_content_type($tmpName) ?: '';

                if ($uploadedFile['error'] !== UPLOAD_ERR_OK) {
                    $message = 'Poster upload failed. Please try again.';
                    $messageType = 'error';
                } elseif (!in_array($extension, $allowedExtensions, true) || !in_array($mimeType, $allowedMimeTypes, true)) {
                    $message = 'Only JPG, JPEG, PNG, and WEBP posters are allowed.';
                    $messageType = 'error';
                } elseif ($fileSize > 2 * 1024 * 1024) {
                    $message = 'Poster size must be 2MB or less.';
                    $messageType = 'error';
                } else {
                    $posterFile = 'event_' . uniqid('', true) . '.' . $extension;
                    $destination = $uploadDirectory . DIRECTORY_SEPARATOR . $posterFile;

                    if (!move_uploaded_file($tmpName, $destination)) {
                        $message = 'Unable to store the uploaded poster. Please check file permissions.';
                        $messageType = 'error';
                    }
                }
            }

            if ($messageType !== 'error') {
                if ($action === 'update' && $eventId > 0) {
                    $stmt = $conn->prepare('SELECT poster FROM events WHERE id = ? LIMIT 1');
                    if ($stmt) {
                        $stmt->bind_param('i', $eventId);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $currentPoster = $result->fetch_assoc()['poster'] ?? null;
                        $stmt->close();
                    }

                    if ($posterFile !== null) {
                        $sql = 'UPDATE events SET title = ?, description = ?, category = ?, event_date = ?, location = ?, capacity = ?, status = ?, poster = ? WHERE id = ?';
                        $params = [$title, $description, $category, $eventDate, $location, $capacity, $status, $posterFile, $eventId];
                        $types = 'sssssissi';

                        if ($currentPoster && $currentPoster !== $posterFile && file_exists($uploadDirectory . DIRECTORY_SEPARATOR . $currentPoster)) {
                            unlink($uploadDirectory . DIRECTORY_SEPARATOR . $currentPoster);
                        }
                    } else {
                        $sql = 'UPDATE events SET title = ?, description = ?, category = ?, event_date = ?, location = ?, capacity = ?, status = ? WHERE id = ?';
                        $params = [$title, $description, $category, $eventDate, $location, $capacity, $status, $eventId];
                        $types = 'sssssisi';
                    }

                    $stmt = $conn->prepare($sql);
                    if ($stmt) {
                        $stmt->bind_param($types, ...$params);
                        $stmt->execute();
                        $stmt->close();
                        $message = 'Event updated successfully.';
                        $messageType = 'success';
                    } else {
                        $message = 'Unable to update the event.';
                        $messageType = 'error';
                    }
                } else {
                    $sql = 'INSERT INTO events (title, description, category, event_date, location, capacity, status, poster) VALUES (?, ?, ?, ?, ?, ?, ?, ?)';
                    $posterValue = $posterFile ?? null;
                    $stmt = $conn->prepare($sql);
                    if ($stmt) {
                        $stmt->bind_param('sssssiss', $title, $description, $category, $eventDate, $location, $capacity, $status, $posterValue);
                        $stmt->execute();
                        $stmt->close();
                        $message = 'Event added successfully.';
                        $messageType = 'success';
                    } else {
                        $message = 'Unable to add the event.';
                        $messageType = 'error';
                    }
                }
            }
        }
    }
}

$events = [];
$stmt = $conn->prepare('SELECT id, title, description, category, event_date, location, capacity, status, poster FROM events ORDER BY event_date DESC');
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    $events = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$defaultDate = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Manage Events</title>
    <link rel="stylesheet" href="../CSS/script.css" />
    <style>
        .page-shell { max-width: 1200px; margin: 38px auto; padding: 0 20px 40px; }
        .panel { background: var(--surface); border: 1px solid var(--border); border-radius: 16px; box-shadow: var(--shadow); padding: 28px; }
        .panel-grid { display: grid; grid-template-columns: 360px 1fr; gap: 24px; }
        .grid-form { display: grid; gap: 15px; }
        .grid-form label { display: grid; gap: 8px; font-weight: 600; }
        .grid-form input, .grid-form textarea, .grid-form select { width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: 10px; font: inherit; background: #fff; }
        .grid-form textarea { min-height: 120px; resize: vertical; }
        .form-actions { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; }
        .btn { border: none; border-radius: 10px; padding: 10px 16px; font-weight: 700; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-secondary { background: #eef2ff; color: var(--primary-dark); }
        .btn-danger { background: #fee2e2; color: #b91c1c; }
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 18px; }
        th, td { padding: 12px 10px; border-bottom: 1px solid var(--border); text-align: left; vertical-align: top; }
        th { background: rgba(37, 99, 235, 0.05); }
        .status-tag { display: inline-block; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 700; }
        .tag-upcoming { background: #dbeafe; color: #1d4ed8; }
        .tag-ongoing { background: #dcfce7; color: #166534; }
        .tag-completed { background: #f3f4f6; color: #374151; }
        .poster-thumb { width: 72px; height: 72px; object-fit: cover; border-radius: 10px; border: 1px solid var(--border); }
        .alert { margin-bottom: 22px; padding: 12px 14px; border-radius: 10px; font-weight: 600; }
        .alert-success { background: #dcfce7; color: #166534; }
        .alert-error { background: #fee2e2; color: #991b1b; }
        .current-poster { display: inline-grid; gap: 8px; }
        .current-poster img { max-width: 180px; border-radius: 8px; border: 1px solid var(--border); }
        @media (max-width: 900px) { .panel-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <script src="../JS/theme.js"></script>
    <nav class="navbar">
        <a class="brand" href="index.html">StudentHub</a>
        <div class="nav-links">
            <a href="index.html">Home</a>
            <a href="admin_dashboard.php">Admin</a>
            <a href="manage_events.php" class="active">Events</a>
            <a href="logout.php">Logout</a>
        </div>
        <button class="theme-toggle" id="themeToggle" aria-label="Toggle dark mode">🌙</button>
    </nav>

    <main class="page-shell">
        <?php if ($message !== ''): ?>
            <div class="alert alert-<?= $messageType === 'error' ? 'error' : 'success'; ?>">
                <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <div class="panel panel-grid">
            <section>
                <h1><?= $editEvent ? 'Edit Event' : 'Add New Event' ?></h1>
                <form method="post" enctype="multipart/form-data" class="grid-form">
                    <input type="hidden" name="action" value="<?= $editEvent ? 'update' : 'add' ?>" />
                    <?php if ($editEvent): ?>
                        <input type="hidden" name="id" value="<?= (int) $editEvent['id']; ?>" />
                    <?php endif; ?>

                    <label>
                        Event Title
                        <input type="text" name="title" value="<?= htmlspecialchars($editEvent['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required />
                    </label>

                    <label>
                        Description
                        <textarea name="description" required><?= htmlspecialchars($editEvent['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </label>

                    <label>
                        Category
                        <select name="category" required>
                            <?php foreach (['Technical', 'Workshop', 'Seminar', 'Competition', 'Cultural', 'Career', 'Networking', 'Sports'] as $option): ?>
                                <option value="<?= htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?>" <?= (($editEvent['category'] ?? 'Technical') === $option) ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label>
                        Event Date
                        <input type="date" name="event_date" value="<?= htmlspecialchars($editEvent['event_date'] ?? $defaultDate, ENT_QUOTES, 'UTF-8'); ?>" required />
                    </label>

                    <label>
                        Venue / Location
                        <input type="text" name="location" value="<?= htmlspecialchars($editEvent['location'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required />
                    </label>

                    <label>
                        Capacity
                        <input type="number" name="capacity" min="1" value="<?= htmlspecialchars((string) ($editEvent['capacity'] ?? 100), ENT_QUOTES, 'UTF-8'); ?>" required />
                    </label>

                    <label>
                        Status
                        <select name="status" required>
                            <?php foreach (['Upcoming', 'Ongoing', 'Completed'] as $statusOption): ?>
                                <option value="<?= htmlspecialchars($statusOption, ENT_QUOTES, 'UTF-8'); ?>" <?= (($editEvent['status'] ?? 'Upcoming') === $statusOption) ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($statusOption, ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label>
                        Poster Upload
                        <input type="file" name="poster" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" />
                    </label>

                    <?php if (!empty($editEvent['poster'])): ?>
                        <div class="current-poster">
                            <span>Current poster:</span>
                            <img src="../uploads/events/<?= htmlspecialchars($editEvent['poster'], ENT_QUOTES, 'UTF-8'); ?>" alt="Event poster" />
                        </div>
                    <?php endif; ?>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary"><?= $editEvent ? 'Save Changes' : 'Add Event' ?></button>
                        <?php if ($editEvent): ?>
                            <a href="manage_events.php" class="btn btn-secondary">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </section>

            <section>
                <h2>Existing Events</h2>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Poster</th>
                                <th>Event</th>
                                <th>Date</th>
                                <th>Location</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($events)): ?>
                                <tr>
                                    <td colspan="6">No events found. Add the first event to get started.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($events as $event): ?>
                                    <tr>
                                        <td>
                                            <?php if (!empty($event['poster'])): ?>
                                                <img class="poster-thumb" src="../uploads/events/<?= htmlspecialchars($event['poster'], ENT_QUOTES, 'UTF-8'); ?>" alt="Poster for <?= htmlspecialchars($event['title'], ENT_QUOTES, 'UTF-8'); ?>" />
                                            <?php else: ?>
                                                <span>No image</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($event['title'], ENT_QUOTES, 'UTF-8'); ?></strong><br />
                                            <small><?= htmlspecialchars($event['category'], ENT_QUOTES, 'UTF-8'); ?></small>
                                        </td>
                                        <td><?= htmlspecialchars(date('d M Y', strtotime($event['event_date'])), ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?= htmlspecialchars($event['location'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td>
                                            <span class="status-tag tag-<?= strtolower($event['status']); ?>"><?= htmlspecialchars($event['status'], ENT_QUOTES, 'UTF-8'); ?></span>
                                        </td>
                                        <td>
                                            <div class="form-actions">
                                                <a class="btn btn-secondary" href="manage_events.php?edit=<?= (int) $event['id']; ?>">Edit</a>
                                                <form method="post" onsubmit="return confirm('Delete this event?');" style="display:inline; margin:0;">
                                                    <input type="hidden" name="action" value="delete" />
                                                    <input type="hidden" name="id" value="<?= (int) $event['id']; ?>" />
                                                    <button type="submit" class="btn btn-danger">Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </main>
</body>
</html>
