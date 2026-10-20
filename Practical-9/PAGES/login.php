<?php
/**
 * StudentHub User Login
 */
session_start();
require_once 'db.php';

$error = "";
$success = "";
$identifier = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $identifier = trim($_POST['identifier'] ?? '');
    $password   = $_POST['password'] ?? '';

    if (empty($identifier) || empty($password)) {
        $error = "Please enter both Email/Username and Password.";
    } elseif (!$mysqli_connected || !$conn) {
        $error = "Database service is temporarily unavailable. Please try again later.";
    } else {
        try {
            // Prepared statement to find user by email or username
            $stmt = $conn->prepare("SELECT id, fullname, username, email, password FROM students WHERE email = ? OR username = ?");
            $stmt->bind_param("ss", $identifier, $identifier);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($user = $result->fetch_assoc()) {
                // Verify password against secure hash
                if (password_verify($password, $user['password'])) {
                    $success = "Welcome back, " . htmlspecialchars($user['fullname']) . "!";
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['fullname'];
                } else {
                    $error = "Invalid credentials. Please verify your password.";
                }
            } else {
                $error = "No account found matching '$identifier'.";
            }
            $stmt->close();
        } catch (Exception $e) {
            $error = "An error occurred during authentication. Please try again.";
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

                <button type="submit" style="width: 100%; margin-top: 20px;">Log In</button>
            </form>

            <div style="margin-top: 22px; text-align: center; font-size: 0.9rem;">
                Don't have an account? <a href="register.php" style="color: var(--primary); font-weight: 600;">Register here</a>
            </div>
        </div>
    </main>
</body>
</html>
