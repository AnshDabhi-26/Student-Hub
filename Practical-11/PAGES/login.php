<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

if (restore_remembered_session($conn)) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';
$identifier = '';
$rememberMe = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';
    $rememberMe = !empty($_POST['remember_me']);

    if (empty($identifier) || empty($password)) {
        $error = 'Please enter both Email/Username and Password.';
    } elseif (!$mysqli_connected || !$conn) {
        $error = 'Database service is temporarily unavailable. Please try again later.';
    } else {
        try {
            $stmt = $conn->prepare('SELECT id, fullname, username, email, password, role FROM students WHERE email = ? OR username = ? LIMIT 1');
            $stmt->bind_param('ss', $identifier, $identifier);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();

            if ($user && password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int) $user['id'];
                $_SESSION['user_name'] = $user['fullname'];
                $_SESSION['user_role'] = $user['role'] ?? 'student';
                $_SESSION['last_activity'] = time();
                $_SESSION['session_id'] = session_id();

                if ($rememberMe) {
                    $token = bin2hex(random_bytes(32));
                    $rememberHash = studenthub_hash_token($token);
                    $cookieValue = $token . ':' . $user['id'];
                    setcookie(studenthub_remember_name(), $cookieValue, time() + (60 * 60 * 24 * 30), '/', '', false, true);

                    $rememberStmt = $conn->prepare('UPDATE students SET remember_token = ? WHERE id = ?');
                    if ($rememberStmt) {
                        $rememberStmt->bind_param('si', $rememberHash, $user['id']);
                        $rememberStmt->execute();
                        $rememberStmt->close();
                    }
                } else {
                    setcookie(studenthub_remember_name(), '', time() - 3600, '/', '', false, true);
                    $clearStmt = $conn->prepare('UPDATE students SET remember_token = NULL WHERE id = ?');
                    if ($clearStmt) {
                        $clearStmt->bind_param('i', $user['id']);
                        $clearStmt->execute();
                        $clearStmt->close();
                    }
                }

                $success = 'Welcome back, ' . htmlspecialchars($user['fullname']) . '!';
                header('Location: ' . (($_SESSION['user_role'] === 'admin') ? 'admin_dashboard.php' : 'student_dashboard.php'));
                exit;
            }

            $error = 'Invalid credentials. Please verify your email/username and password.';
        } catch (Exception $e) {
            $error = 'An unexpected authentication error occurred. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login - StudentHub</title>
    <link rel="stylesheet" href="../CSS/script.css" />
    <style>
        .login-card {
            max-width: 440px;
            margin: 40px auto;
            padding: 32px;
            background: var(--surface);
            border-radius: 16px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
        }
        .alert {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 16px;
            font-size: 0.9rem;
        }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
        .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #6ee7b7; }
        .remember-row { display:flex; align-items:center; gap:10px; margin: 16px 0; }
        .remember-row input { width:auto; }
    </style>
</head>
<body>
    <script src="../JS/theme.js"></script>
    <nav class="navbar">
        <a class="brand" href="index.html">StudentHub</a>
        <div class="nav-links">
            <a href="index.html">Home</a>
            <a href="dashboard.php">Dashboard</a>
            <a href="courses.html">Courses</a>
            <a href="assignment.html">Assignments</a>
            <a href="attendence.html">Attendance</a>
            <a href="result.html">Result</a>
            <a href="profile.html">Profile</a>
            <a href="register.php">Register</a>
            <a href="login.php" class="active">Login</a>
        </div>
        <button class="theme-toggle" id="themeToggle" aria-label="Toggle dark mode">🌙</button>
    </nav>

    <main>
        <div class="login-card">
            <h2>Welcome Back</h2>
            <p style="color: var(--muted); margin-bottom: 20px; font-size: 0.9rem;">
                Log in to access your courses, attendance, and student portal.
            </p>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form method="POST" action="login.php">
                <label for="identifier">Email or Username</label>
                <input type="text" id="identifier" name="identifier" value="<?= htmlspecialchars($identifier) ?>" placeholder="e.g., anshdabhi or ansh@charusat.edu.in" required />

                <label for="password" style="margin-top: 14px;">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required />

                <div class="remember-row">
                    <input type="checkbox" id="remember_me" name="remember_me" value="1" <?= $rememberMe ? 'checked' : '' ?> />
                    <label for="remember_me">Remember me</label>
                </div>

                <button type="submit" style="width: 100%; margin-top: 10px;">Log In</button>
            </form>

            <div style="margin-top: 22px; text-align: center; font-size: 0.9rem;">
                Don't have an account? <a href="register.php" style="color: var(--primary); font-weight: 600;">Register here</a>
            </div>
        </div>
    </main>
</body>
</html>
