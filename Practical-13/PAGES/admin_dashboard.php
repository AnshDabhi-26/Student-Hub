<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

require_role(['admin'], $conn);

$userName = htmlspecialchars($_SESSION['user_name'] ?? 'Admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../CSS/script.css" />
</head>
<body>
    <script src="../JS/theme.js"></script>
    <nav class="navbar">
        <a class="brand" href="index.html">StudentHub</a>
        <div class="nav-links">
            <a href="index.html">Home</a>
            <a href="admin_dashboard.php" class="active">Admin</a>
            <a href="logout.php">Logout</a>
        </div>
        <button class="theme-toggle" id="themeToggle" aria-label="Toggle dark mode">🌙</button>
    </nav>

    <main style="max-width: 980px; margin: 40px auto; padding: 0 20px;">
        <div style="background: var(--surface); border: 1px solid var(--border); border-radius: 16px; padding: 28px; box-shadow: var(--shadow);">
            <h1>Admin Panel, <?= $userName ?> 🔒</h1>
            <p style="color: var(--muted);">This dashboard is restricted to users with the admin role. Session timeout and authentication are enforced before access is granted.</p>

            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-top: 24px;">
                <a href="manage_students.php" class="card" style="text-decoration:none; color:inherit; display:block;">
                    <h3>👥 Students</h3>
                    <p>Manage user accounts and academic records.</p>
                </a>
                <a href="manage_events.php" class="card" style="text-decoration:none; color:inherit; display:block;">
                    <h3>📅 Events</h3>
                    <p>Review scheduled campus activities, add new posters, and manage event records.</p>
                </a>
                <div class="card">
                    <h3>🛡️ Security</h3>
                    <p>Monitor access, sessions, and protected resources.</p>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
