<?php
/**
 * ============================================================================
 * PRACTICAL 9: Student Registration with MySQLi, Duplicate Email Check,
 *              and Password Hashing
 * ============================================================================
 */
session_start();
require_once 'db.php';

// Generate CSRF token if empty
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = "";
$message_type = "info"; // success, error, info
$available_events = [];

// Client environment info for audit logging
$client_ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown Browser';

// Fetch available events for the dropdown using MySQLi Prepared Statement
if ($mysqli_connected && $conn) {
    try {
        $evStmt = $conn->prepare("SELECT id, title, category, event_date, location FROM events ORDER BY event_date ASC");
        if ($evStmt) {
            $evStmt->execute();
            $result = $evStmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $available_events[] = $row;
            }
            $evStmt->close();
        }
    } catch (Exception $e) {
        $available_events = [];
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // 1. CSRF Verification
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (empty($csrf_token) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
        $message = "Security Token (CSRF) validation failed. Please refresh.";
        $message_type = "error";
    } elseif (!$mysqli_connected || !$conn) {
        $message = "Database is currently offline. Please start MySQL in XAMPP.";
        $message_type = "error";
    } else {
        $name     = trim($_POST['fullname'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $pass     = $_POST['password'] ?? '';
        $branch   = trim($_POST['branch'] ?? 'Computer Engineering');
        $semester = intval($_POST['semester'] ?? 5);
        $event_id = !empty($_POST['event_id']) ? intval($_POST['event_id']) : null;

        // Auto-generate username from email prefix if left blank
        if (empty($username) && !empty($email)) {
            $parts = explode('@', $email);
            $username = preg_replace("/[^a-zA-Z0-9_]/", "", $parts[0]);
        }

        // 2. Consistent Backend Validation
        if (empty($name) || empty($email) || empty($pass)) {
            $message = "Please fill in all required fields.";
            $message_type = "error";
        } elseif (strlen($name) < 2) {
            $message = "Full Name must be at least 2 characters.";
            $message_type = "error";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = "Please enter a valid email address.";
            $message_type = "error";
        } elseif (strlen($pass) < 8 || !preg_match("/[A-Za-z]/", $pass) || !preg_match("/[0-9]/", $pass)) {
            $message = "Password must be at least 8 characters long and contain both letters and numbers.";
            $message_type = "error";
        } else {
            try {
                // 3. MySQLi Prepared Statement: Check if student email or username already exists
                $checkStmt = $conn->prepare("SELECT id, email, username FROM students WHERE email = ? OR username = ?");
                $checkStmt->bind_param("ss", $email, $username);
                $checkStmt->execute();
                $checkResult = $checkStmt->get_result();

                $student_id = null;
                $is_duplicate = false;

                if ($existing = $checkResult->fetch_assoc()) {
                    $is_duplicate = true;
                    if (strcasecmp($existing['email'], $email) === 0) {
                        $message = "Duplicate Check Warning: Email '$email' is already registered!";
                    } else {
                        $message = "Duplicate Check Warning: Username '$username' is already taken!";
                    }
                    $message_type = "error";

                    // Record Audit Log for duplicate attempt
                    $logStmt = $conn->prepare("INSERT INTO audit_logs (event_type, identifier, ip_address, user_agent, status, details) VALUES ('USER_REGISTRATION', ?, ?, ?, 'DUPLICATE_EMAIL', ?)");
                    if ($logStmt) {
                        $details = "Duplicate registration attempt for email: $email";
                        $logStmt->bind_param("ssss", $email, $client_ip, $user_agent, $details);
                        $logStmt->execute();
                        $logStmt->close();
                    }
                }
                $checkStmt->close();

                if (!$is_duplicate) {
                    // 4. Secure Password Hashing using password_hash()
                    $hashed_password = password_hash($pass, PASSWORD_DEFAULT);

                    // 5. MySQLi Prepared Statement: Insert new student
                    $insertStmt = $conn->prepare("INSERT INTO students (fullname, username, email, password, branch, semester) VALUES (?, ?, ?, ?, ?, ?)");
                    $insertStmt->bind_param("sssssi", $name, $username, $email, $hashed_password, $branch, $semester);
                    $insertStmt->execute();
                    $student_id = $conn->insert_id;
                    $insertStmt->close();

                    // Optional Event Registration
                    if ($event_id && $student_id) {
                        $regStmt = $conn->prepare("INSERT INTO registrations (student_id, event_id, status) VALUES (?, ?, 'Confirmed')");
                        $regStmt->bind_param("ii", $student_id, $event_id);
                        $regStmt->execute();
                        $regStmt->close();
                    }

                    // Record Audit Log for successful registration
                    $logStmt = $conn->prepare("INSERT INTO audit_logs (event_type, identifier, ip_address, user_agent, status, details) VALUES ('USER_REGISTRATION', ?, ?, ?, 'SUCCESS', ?)");
                    if ($logStmt) {
                        $details = "Registered student ID #$student_id with hashed password via MySQLi.";
                        $logStmt->bind_param("ssss", $email, $client_ip, $user_agent, $details);
                        $logStmt->execute();
                        $logStmt->close();
                    }

                    $message = "Student account created successfully (ID: #$student_id) with password_hash() BCRYPT encryption!";
                    $message_type = "success";
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                }

            } catch (Exception $e) {
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
    <title>Student Registration - StudentHub</title>
    <link rel="stylesheet" href="../CSS/script.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .register-container {
            max-width: 560px;
            margin: 30px auto;
            background: var(--surface);
            padding: 2.2rem;
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
        .tag-pill {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
            background: #eef2ff;
            color: #4f46e5;
            margin-bottom: 12px;
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
            <a href="register.php" class="active">Register</a>
            <a href="login.php">Login</a>
        </div>
        <button class="theme-toggle" id="themeToggle" aria-label="Toggle dark mode">🌙</button>
    </nav>

    <main>
        <section class="register-container">
            <h2>Student Registration</h2>
            <p style="color: var(--muted); margin-bottom: 20px; font-size: 0.95rem;">
                Create an account to register for university events and academic courses.
            </p>

            <?php if (!empty($message)): ?>
                <div class="alert-box alert-<?= $message_type ?>">
                    <?= $message_type === 'success' ? '✅' : '⚠️' ?> <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="register_db.php">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                <label for="fullname">Full Name *</label>
                <input type="text" id="fullname" name="fullname" placeholder="e.g. Ansh Dabhi" required>

                <div class="form-row">
                    <div>
                        <label for="username">Username *</label>
                        <input type="text" id="username" name="username" placeholder="e.g. anshdabhi" required>
                    </div>
                    <div>
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" placeholder="e.g. ansh@charusat.edu.in" required>
                    </div>
                </div>

                <label for="password">Password (will be hashed with password_hash) *</label>
                <input type="password" id="password" name="password" placeholder="Min 8 characters (letters & numbers)" required>

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

                <button type="submit" style="width: 100%; margin-top: 6px;">Register Account</button>
            </form>

            <a href="login.php" class="view-link">Already registered? Log in here →</a>
        </section>
    </main>
</body>
</html>
