<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

require_role(['student'], $conn);

$userName = htmlspecialchars($_SESSION['user_name'] ?? 'Student');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="../CSS/script.css" />
</head>
<body>
    <script src="../JS/theme.js"></script>
    <nav class="navbar">
        <a class="brand" href="index.html">StudentHub</a>
        <div class="nav-links">
            <a href="index.html">Home</a>
            <a href="student_dashboard.php" class="active">Dashboard</a>
            <a href="../data/courses.json">Courses</a>
            <a href="logout.php">Logout</a>
        </div>
        <button class="theme-toggle" id="themeToggle" aria-label="Toggle dark mode">🌙</button>
    </nav>

    <main style="max-width: 980px; margin: 40px auto; padding: 0 20px;">
        <div style="background: var(--surface); border: 1px solid var(--border); border-radius: 16px; padding: 28px; box-shadow: var(--shadow);">
            <h1>Welcome back, <?= $userName ?> 👋</h1>
            <p style="color: var(--muted);">You are logged in as a student. Your session is protected and will expire after 15 minutes of inactivity.</p>

            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-top: 24px;">
                <div class="card">
                    <h3>📚 Courses</h3>
                    <p>View your assigned subjects and study updates.</p>
                </div>
                <div class="card">
                    <h3>✅ Attendance</h3>
                    <p>Check your class attendance and course records.</p>
                </div>
                <div class="card">
                    <h3>📝 Results</h3>
                    <p>Track academic performance and semester marks.</p>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
