<?php
/**
 * Practical 9: User Registration
 * Robust backend security: MySQLi Prepared Statements, Duplicate Email/Username Checks,
 * password_hash() BCRYPT encryption, CSRF Protection, and Audit Logging.
 */

session_start();
require_once 'db.php';

// Generate CSRF token if not set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Variables for form state and feedback
$errors = [];
$success_message = "";
$duplicate_field = "";
$fullname = "";
$username = "";
$email = "";
$branch = "Computer Engineering";
$semester = 5;

// Client environment info for audit logging
$client_ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown Browser';

/**
 * Helper function to record audit log entries using MySQLi Prepared Statement
 */
function record_audit_log($conn, $identifier, $ip, $agent, $status, $details) {
    if (!$conn || $conn->connect_error) return;
    try {
        $logStmt = $conn->prepare("INSERT INTO audit_logs (event_type, identifier, ip_address, user_agent, status, details) VALUES ('USER_REGISTRATION', ?, ?, ?, ?, ?)");
        if ($logStmt) {
            $logStmt->bind_param("sssss", $identifier, $ip, $agent, $status, $details);
            $logStmt->execute();
            $logStmt->close();
        }
    } catch (Exception $e) {
        // Silently fail if audit log table doesn't exist yet
    }
}

// Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // 1. Validate CSRF Token
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (empty($csrf_token) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
        $errors[] = "Security Token validation failed. Please refresh and try again.";
        record_audit_log($conn, 'UNKNOWN', $client_ip, $user_agent, 'VALIDATION_FAILED', 'CSRF token mismatch');
    } else {

        // 2. Collect and sanitize input
        $fullname = trim($_POST['fullname'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $branch   = trim($_POST['branch'] ?? 'Computer Engineering');
        $semester = intval($_POST['semester'] ?? 5);
        $role = 'student';

        // 3. Backend Validation (Consistent with Frontend Rules)
        if (empty($fullname)) {
            $errors[] = "Full Name is required.";
        } elseif (strlen($fullname) < 2 || !preg_match("/^[a-zA-Z\s\.\'-]+$/", $fullname)) {
            $errors[] = "Full Name must contain at least 2 characters (letters only).";
        }

        if (empty($username)) {
            $errors[] = "Username is required.";
        } elseif (!preg_match("/^[a-zA-Z0-9_]{3,30}$/", $username)) {
            $errors[] = "Username must be 3-30 characters long and contain only letters, numbers, and underscores.";
        }

        if (empty($email)) {
            $errors[] = "Email Address is required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Please provide a valid email address (e.g., student@charusat.edu.in).";
        }

        if (empty($password)) {
            $errors[] = "Password is required.";
        } elseif (strlen($password) < 8) {
            $errors[] = "Password must be at least 8 characters long.";
        } elseif (!preg_match("/[A-Za-z]/", $password) || !preg_match("/[0-9]/", $password)) {
            $errors[] = "Password must contain both letters and at least one number.";
        }

        if ($password !== $confirm_password) {
            $errors[] = "Password confirmation does not match the chosen password.";
        }

        if ($semester < 1 || $semester > 8) {
            $errors[] = "Invalid semester selected.";
        }

        // 4. Database Check and Insert if validations pass
        if (empty($errors)) {
            if (!$mysqli_connected || !$conn) {
                $errors[] = "Database service is temporarily unavailable. Please try again later.";
            } else {
                try {
                    // 5. Check Duplicate Email & Username using MySQLi Prepared Statement
                    $checkStmt = $conn->prepare("SELECT id, email, username FROM students WHERE email = ? OR username = ?");
                    if (!$checkStmt) {
                        throw new Exception("Prepared statement failed: " . $conn->error);
                    }

                    $checkStmt->bind_param("ss", $email, $username);
                    $checkStmt->execute();
                    $result = $checkStmt->get_result();

                    $is_duplicate_email = false;
                    $is_duplicate_username = false;

                    while ($row = $result->fetch_assoc()) {
                        if (strcasecmp($row['email'], $email) === 0) {
                            $is_duplicate_email = true;
                        }
                        if (strcasecmp($row['username'], $username) === 0) {
                            $is_duplicate_username = true;
                        }
                    }
                    $checkStmt->close();

                    // Handle Duplicate Detection
                    if ($is_duplicate_email && $is_duplicate_username) {
                        $errors[] = "Both Email and Username are already registered. Please log in or use different credentials.";
                        $duplicate_field = "both";
                        record_audit_log($conn, $email, $client_ip, $user_agent, 'DUPLICATE_BOTH', "Duplicate registration blocked: email '$email' and username '$username' already exist.");
                    } elseif ($is_duplicate_email) {
                        $errors[] = "The email address '$email' is already registered. Please use another email or log in.";
                        $duplicate_field = "email";
                        record_audit_log($conn, $email, $client_ip, $user_agent, 'DUPLICATE_EMAIL', "Duplicate registration blocked: email '$email' already exists.");
                    } elseif ($is_duplicate_username) {
                        $errors[] = "The username '$username' is already taken. Please choose a different username.";
                        $duplicate_field = "username";
                        record_audit_log($conn, $username, $client_ip, $user_agent, 'DUPLICATE_USERNAME', "Duplicate registration blocked: username '$username' already exists.");
                    } else {
                        // 6. Security: Hash Password using password_hash()
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                        // 7. Insert User using MySQLi Prepared Statement
                        $insertStmt = $conn->prepare("INSERT INTO students (fullname, username, email, password, branch, semester, role) VALUES (?, ?, ?, ?, ?, ?, ?)");
                        if (!$insertStmt) {
                            throw new Exception("Insert prepare failed: " . $conn->error);
                        }

                        $insertStmt->bind_param("sssssis", $fullname, $username, $email, $hashed_password, $branch, $semester, $role);
                        
                        if ($insertStmt->execute()) {
                            $new_student_id = $conn->insert_id;
                            $insertStmt->close();

                            // 8. Audit Log Entry
                            record_audit_log(
                                $conn, 
                                $email, 
                                $client_ip, 
                                $user_agent, 
                                'SUCCESS', 
                                "Student #$new_student_id ($username) successfully registered."
                            );

                            $success_message = "Your student account has been created successfully!";
                            
                            // Reset form fields
                            $fullname = "";
                            $username = "";
                            $email = "";
                            $branch = "Computer Engineering";
                            $semester = 5;

                            // Refresh CSRF token on success
                            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                        } else {
                            throw new Exception("Insert execution failed: " . $insertStmt->error);
                        }
                    }
                } catch (Exception $e) {
                    $errors[] = "Registration could not be completed. Please try again.";
                    record_audit_log($conn, $email ?: 'UNKNOWN', $client_ip, $user_agent, 'ERROR', $e->getMessage());
                }
            }
        } else {
            record_audit_log($conn, $email ?: $username ?: 'UNKNOWN', $client_ip, $user_agent, 'VALIDATION_FAILED', implode("; ", $errors));
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Create an Account - StudentHub</title>
    <link rel="stylesheet" href="../CSS/script.css" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <style>
        .page-container {
            max-width: 640px;
            margin: 32px auto;
            padding: 0 16px 40px;
        }
        .card-box {
            background: var(--surface, #ffffff);
            border-radius: 18px;
            padding: 36px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            border: 1px solid var(--border, #e5e7eb);
        }
        .header-section {
            margin-bottom: 26px;
            text-align: center;
        }
        .header-section h2 {
            font-size: 1.85rem;
            margin-bottom: 8px;
            color: var(--text, #111827);
        }
        .header-section p {
            color: var(--muted, #6b7280);
            font-size: 0.95rem;
            line-height: 1.5;
        }
        .alert-panel {
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 22px;
            font-size: 0.92rem;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .alert-error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        .alert-success {
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        .alert-item {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            line-height: 1.4;
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        @media (max-width: 600px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
        .form-group {
            margin-bottom: 16px;
        }
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            font-size: 0.88rem;
            color: var(--text, #374151);
        }
        .form-group label span.req {
            color: #ef4444;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 11px 14px;
            border: 1.5px solid var(--border, #d1d5db);
            border-radius: 9px;
            font-size: 0.95rem;
            background: var(--bg, #f9fafb);
            color: var(--text, #111827);
            transition: all 0.2s ease;
            box-sizing: border-box;
        }
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #6366f1;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
        }
        .form-group input.is-invalid {
            border-color: #ef4444;
            background-color: #fffaf0;
        }
        .form-group input.is-valid {
            border-color: #10b981;
        }
        .field-helper {
            font-size: 0.78rem;
            color: var(--muted, #6b7280);
            margin-top: 4px;
            display: block;
        }
        .field-error {
            font-size: 0.78rem;
            color: #dc2626;
            margin-top: 4px;
            display: none;
            font-weight: 500;
        }
        .password-container {
            position: relative;
        }
        .toggle-pwd-btn {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1rem;
            color: #6b7280;
            padding: 0;
            width: auto;
        }
        .submit-btn {
            width: 100%;
            padding: 13px;
            background: #4f46e5;
            color: #ffffff;
            font-weight: 600;
            font-size: 1rem;
            border: none;
            border-radius: 9px;
            cursor: pointer;
            transition: background 0.2s ease;
            margin-top: 10px;
        }
        .submit-btn:hover {
            background: #4338ca;
        }
        .quick-nav {
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid var(--border, #e5e7eb);
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 0.92rem;
        }
        .quick-nav a {
            color: #4f46e5;
            text-decoration: none;
            font-weight: 600;
        }
        .quick-nav a:hover {
            text-decoration: underline;
        }
        .password-strength-bar {
            height: 4px;
            width: 100%;
            background: #e5e7eb;
            border-radius: 2px;
            margin-top: 6px;
            overflow: hidden;
        }
        .password-strength-fill {
            height: 100%;
            width: 0%;
            transition: width 0.3s ease, background-color 0.3s ease;
        }
    </style>
</head>
<body>
    <script src="../JS/theme.js"></script>

    <!-- Top Navigation -->
    <nav class="navbar">
        <a class="brand" href="index.html">StudentHub</a>
        <div class="nav-links">
            <a href="index.html">Home</a>
            <a href="dashboard.html">Dashboard</a>
            <a href="courses.html">Courses</a>
            <a href="assignment.html">Assignments</a>
            <a href="attendence.html">Attendance</a>
            <a href="result.html">Result</a>
            <a href="profile.html">Profile</a>
            <a href="register.php" class="active">Register</a>
            <a href="login.php">Login</a>
        </div>
        <button class="theme-toggle" id="themeToggle" aria-label="Toggle dark mode">🌙</button>
    </nav>

    <main class="page-container">
        <div class="card-box">
            <!-- Header section -->
            <div class="header-section">
                <h2>Create Your Account</h2>
                <p>Join StudentHub to access your courses, track attendance, and view academic records.</p>
            </div>

            <!-- Error Feedback Alerts -->
            <?php if (!empty($errors)): ?>
                <div class="alert-panel alert-error" role="alert">
                    <?php foreach ($errors as $err): ?>
                        <div class="alert-item">
                            <span>⚠️</span>
                            <span><?= htmlspecialchars($err) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Success Feedback Alert -->
            <?php if (!empty($success_message)): ?>
                <div class="alert-panel alert-success" role="alert">
                    <div class="alert-item">
                        <span>✅</span>
                        <div>
                            <strong>Account Created!</strong>
                            <p style="margin: 4px 0 0;"><?= htmlspecialchars($success_message) ?></p>
                        </div>
                    </div>
                    <div style="margin-top: 10px;">
                        <a href="login.php" style="color: #065f46; font-weight: 600; text-decoration: underline;">
                            Proceed to Login →
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Registration Form -->
            <form method="POST" action="register.php" id="registrationForm" novalidate>
                <!-- CSRF Token -->
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>" />
                <input type="hidden" name="role" value="student" />

                <div class="form-grid">
                    <!-- Full Name -->
                    <div class="form-group full-width">
                        <label for="fullname">Full Name <span class="req">*</span></label>
                        <input 
                            type="text" 
                            id="fullname" 
                            name="fullname" 
                            value="<?= htmlspecialchars($fullname) ?>" 
                            placeholder="e.g., Ansh Dabhi" 
                            required 
                            autocomplete="name"
                        />
                        <span class="field-error" id="fullnameError">Please enter a valid full name (letters only, min 2 chars).</span>
                    </div>

                    <!-- Username -->
                    <div class="form-group">
                        <label for="username">Username <span class="req">*</span></label>
                        <input 
                            type="text" 
                            id="username" 
                            name="username" 
                            value="<?= htmlspecialchars($username) ?>" 
                            placeholder="e.g., anshdabhi" 
                            required 
                            autocomplete="username"
                        />
                        <span class="field-helper">3-30 characters (letters, numbers, underscore)</span>
                        <span class="field-error" id="usernameError">Username must be 3-30 alphanumeric characters.</span>
                    </div>

                    <!-- Email Address -->
                    <div class="form-group">
                        <label for="email">Email Address <span class="req">*</span></label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            value="<?= htmlspecialchars($email) ?>" 
                            placeholder="e.g., ansh.dabhi@charusat.edu.in" 
                            required 
                            autocomplete="email"
                        />
                        <span class="field-helper">Use your college or personal email</span>
                        <span class="field-error" id="emailError">Please enter a valid email address.</span>
                    </div>

                    <!-- Password -->
                    <div class="form-group">
                        <label for="password">Password <span class="req">*</span></label>
                        <div class="password-container">
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                placeholder="Min 8 chars (letters & numbers)" 
                                required 
                                autocomplete="new-password"
                            />
                            <button type="button" class="toggle-pwd-btn" id="togglePasswordBtn" aria-label="Toggle password visibility">👁️</button>
                        </div>
                        <div class="password-strength-bar">
                            <div class="password-strength-fill" id="pwdStrengthFill"></div>
                        </div>
                        <span class="field-helper" id="pwdStrengthText">Use at least 8 characters with letters & numbers</span>
                        <span class="field-error" id="passwordError">At least 8 characters with letters and numbers.</span>
                    </div>

                    <!-- Confirm Password -->
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password <span class="req">*</span></label>
                        <div class="password-container">
                            <input 
                                type="password" 
                                id="confirm_password" 
                                name="confirm_password" 
                                placeholder="Re-enter password" 
                                required 
                                autocomplete="new-password"
                            />
                            <button type="button" class="toggle-pwd-btn" id="toggleConfirmBtn" aria-label="Toggle confirm password visibility">👁️</button>
                        </div>
                        <span class="field-error" id="confirmError">Passwords do not match.</span>
                    </div>

                    <!-- Branch -->
                    <div class="form-group">
                        <label for="branch">Academic Branch</label>
                        <select id="branch" name="branch">
                            <option value="Computer Engineering" <?= $branch === 'Computer Engineering' ? 'selected' : '' ?>>Computer Engineering</option>
                            <option value="Information Technology" <?= $branch === 'Information Technology' ? 'selected' : '' ?>>Information Technology</option>
                            <option value="Electronics & Communication" <?= $branch === 'Electronics & Communication' ? 'selected' : '' ?>>Electronics & Communication</option>
                            <option value="Mechanical Engineering" <?= $branch === 'Mechanical Engineering' ? 'selected' : '' ?>>Mechanical Engineering</option>
                            <option value="Civil Engineering" <?= $branch === 'Civil Engineering' ? 'selected' : '' ?>>Civil Engineering</option>
                        </select>
                    </div>

                    <!-- Semester -->
                    <div class="form-group">
                        <label for="semester">Current Semester</label>
                        <select id="semester" name="semester">
                            <?php for ($s = 1; $s <= 8; $s++): ?>
                                <option value="<?= $s ?>" <?= $semester == $s ? 'selected' : '' ?>>Semester <?= $s ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="submit-btn" id="submitBtn">
                    Create Account
                </button>
            </form>

            <!-- Bottom Navigation Links -->
            <div class="quick-nav">
                <span>Already have an account? <a href="login.php">Log in here</a></span>
            </div>
        </div>
    </main>

    <!-- Client-side Validation Script -->
    <script src="../JS/register.js"></script>
</body>
</html>
