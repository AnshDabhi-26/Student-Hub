<?php
/**
 * Practical 8: Student & Event Registration using MySQL and PDO Prepared Statements
 */
require_once 'db.php';

$message = "";
$message_type = "info"; // success, error, info
$available_events = [];

if ($db_connected && $pdo) {
    // Fetch available events for the dropdown using Prepared Statement
    try {
        $evStmt = $pdo->prepare("SELECT id, title, category, event_date, location FROM events ORDER BY event_date ASC");
        $evStmt->execute();
        $available_events = $evStmt->fetchAll();
    } catch (PDOException $e) {
        $available_events = [];
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!$db_connected || !$pdo) {
        $message = "Database is currently offline. Please start MySQL in XAMPP.";
        $message_type = "error";
    } else {
        $name     = htmlspecialchars(trim($_POST['fullname'] ?? ''));
        $email    = htmlspecialchars(trim($_POST['email'] ?? ''));
        $pass     = trim($_POST['password'] ?? '');
        $branch   = htmlspecialchars(trim($_POST['branch'] ?? 'Computer Engineering'));
        $semester = intval($_POST['semester'] ?? 5);
        $event_id = !empty($_POST['event_id']) ? intval($_POST['event_id']) : null;

        if (empty($name) || empty($email) || empty($pass)) {
            $message = "Please fill in all required fields.";
            $message_type = "error";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = "Please enter a valid email address.";
            $message_type = "error";
        } elseif (strlen($pass) < 6) {
            $message = "Password must be at least 6 characters long.";
            $message_type = "error";
        } else {
            try {
                // 1. Prepared Statement: Check if student email already exists
                $checkStmt = $pdo->prepare("SELECT id FROM students WHERE email = ?");
                $checkStmt->execute([$email]);
                $existing = $checkStmt->fetch();

                $student_id = null;

                if ($existing) {
                    $student_id = $existing['id'];
                    $message = "Student with this email already exists (ID: $student_id).";
                } else {
                    // 2. Prepared Statement: Insert new student with branch and semester
                    $insertStmt = $pdo->prepare("INSERT INTO students (fullname, email, password, branch, semester) VALUES (?, ?, ?, ?, ?)");
                    $insertStmt->execute([$name, $email, $pass, $branch, $semester]);
                    $student_id = $pdo->lastInsertId();
                    $message = "Student account created successfully (ID: $student_id)!";
                }

                // 3. Prepared Statement: Register for selected event if chosen
                if ($event_id && $student_id) {
                    // Check if already registered for this event
                    $regCheck = $pdo->prepare("SELECT id FROM registrations WHERE student_id = ? AND event_id = ?");
                    $regCheck->execute([$student_id, $event_id]);
                    
                    if ($regCheck->fetch()) {
                        $message .= " Already registered for selected event.";
                    } else {
                        $regStmt = $pdo->prepare("INSERT INTO registrations (student_id, event_id, status) VALUES (?, ?, 'Confirmed')");
                        $regStmt->execute([$student_id, $event_id]);
                        $message .= " Event registration confirmed!";
                    }
                }

                $message_type = "success";

            } catch (PDOException $e) {
                $message = "Database error: " . $e->getMessage();
                $message_type = "error";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register (MySQL) - Practical 8</title>
    <link rel="stylesheet" href="../CSS/script.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .register-container {
            max-width: 520px;
            margin: 30px auto;
            background: var(--surface);
            padding: 2rem;
            border-radius: 16px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
        }
        .alert-box {
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #6ee7b7; }
        .alert-error   { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
        .alert-info    { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        .view-link {
            display: inline-block;
            margin-top: 16px;
            color: var(--primary);
            font-weight: 600;
            text-align: center;
            width: 100%;
        }
    </style>
</head>
<body>
    <script src="../JS/theme.js"></script>
    <nav class="navbar">
        <a class="brand" href="index.html">StudentHub</a>
        <div class="nav-links">
            <a href="index.html">Home</a>
            <a href="dashboard.html">Dashboard</a>
            <a href="courses.html">Courses</a>
            <a href="profile.html">Profile</a>
            <a href="register_db.php" class="active">Register (MySQL)</a>
            <a href="test_connection.php">DB Status</a>
        </div>
        <button class="theme-toggle" id="themeToggle" aria-label="Toggle dark mode">🌙</button>
    </nav>

    <main>
        <section class="register-container">
            <h2>Student Registration (MySQL)</h2>
            <p style="color: var(--muted); margin-bottom: 20px; font-size: 0.95rem;">
                Practical 8: Stored directly into normalized MySQL tables using PDO Prepared Statements.
            </p>

            <?php if (!empty($message)): ?>
                <div class="alert-box alert-<?= $message_type ?>">
                    <?= $message_type === 'success' ? '✅' : '⚠️' ?> <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="register_db.php">
                <label for="fullname">Full Name *</label>
                <input type="text" id="fullname" name="fullname" placeholder="e.g. Ansh Dabhi" required>

                <label for="email">Email Address *</label>
                <input type="email" id="email" name="email" placeholder="e.g. ansh@charusat.edu.in" required>

                <label for="password">Password *</label>
                <input type="password" id="password" name="password" placeholder="Min 6 characters" required>

                <div class="form-row">
                    <div>
                        <label for="branch">Branch</label>
                        <select id="branch" name="branch" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border); background: var(--bg); color: var(--text);">
                            <option value="Computer Engineering">Computer Engineering</option>
                            <option value="Information Technology">Information Technology</option>
                            <option value="Electronics & Communication">Electronics & Comm.</option>
                            <option value="Mechanical Engineering">Mechanical Eng.</option>
                        </select>
                    </div>
                    <div>
                        <label for="semester">Semester</label>
                        <select id="semester" name="semester" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border); background: var(--bg); color: var(--text);">
                            <option value="1">Semester 1</option>
                            <option value="2">Semester 2</option>
                            <option value="3">Semester 3</option>
                            <option value="4">Semester 4</option>
                            <option value="5" selected>Semester 5</option>
                            <option value="6">Semester 6</option>
                            <option value="7">Semester 7</option>
                            <option value="8">Semester 8</option>
                        </select>
                    </div>
                </div>

                <label for="event_id" style="margin-top: 12px;">Optional Event Registration</label>
                <select id="event_id" name="event_id" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border); background: var(--bg); color: var(--text); margin-bottom: 16px;">
                    <option value="">-- Choose an Event to Register (Optional) --</option>
                    <?php foreach ($available_events as $ev): ?>
                        <option value="<?= $ev['id'] ?>">
                            <?= htmlspecialchars($ev['title']) ?> (<?= htmlspecialchars($ev['event_date']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>

                <button type="submit" style="width: 100%; margin-top: 6px;">Register with MySQL Backend</button>
            </form>

            <a href="test_connection.php" class="view-link">📊 View Live MySQL Database Records →</a>
        </section>
    </main>
</body>
</html>
