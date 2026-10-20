<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

require_role(['admin'], $conn);

$userName = htmlspecialchars($_SESSION['user_name'] ?? 'Admin');
$role = $_SESSION['user_role'] ?? 'admin';

$totalUsers = (int) ($conn->query("SELECT COUNT(*) AS total FROM students")->fetch_assoc()['total'] ?? 0);
$totalStudents = (int) ($conn->query("SELECT COUNT(*) AS total FROM students WHERE role = 'student'")->fetch_assoc()['total'] ?? 0);
$totalAdmins = (int) ($conn->query("SELECT COUNT(*) AS total FROM students WHERE role = 'admin'")->fetch_assoc()['total'] ?? 0);
$totalEvents = (int) ($conn->query("SELECT COUNT(*) AS total FROM events")->fetch_assoc()['total'] ?? 0);
$latestRegistrations = $conn->query("SELECT s.fullname, e.title AS event_title, r.registration_date FROM registrations r JOIN students s ON s.id = r.student_id JOIN events e ON e.id = r.event_id ORDER BY r.registration_date DESC LIMIT 5");
$recentAudit = $conn->query("SELECT event_type, identifier, status, created_at FROM audit_logs ORDER BY created_at DESC LIMIT 5");
$navItems = get_role_nav_items($role);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../CSS/script.css" />
    <style>
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 20px; margin-top: 24px; }
        .stat-card { background: var(--surface); border: 1px solid var(--border); border-radius: 14px; padding: 22px; box-shadow: var(--shadow); }
        .stat-card h3 { margin: 0; font-size: 0.9rem; color: var(--muted); }
        .stat-card .value { font-size: 2rem; font-weight: 700; margin-top: 12px; }
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { padding: 12px; border-bottom: 1px solid var(--border); text-align: left; }
        .muted { color: var(--muted); }
    </style>
</head>
<body>
    <script src="../JS/theme.js"></script>
    <nav class="navbar">
        <a class="brand" href="index.html">StudentHub</a>
        <div class="nav-links">
            <?php foreach ($navItems as $item): ?>
                <a href="<?= htmlspecialchars($item['url']) ?>" class="<?= basename($_SERVER['PHP_SELF']) === basename($item['url']) ? 'active' : '' ?>"><?= htmlspecialchars($item['label']) ?></a>
            <?php endforeach; ?>
        </div>
        <button class="theme-toggle" id="themeToggle" aria-label="Toggle dark mode">🌙</button>
    </nav>

    <main style="max-width: 1100px; margin: 40px auto; padding: 0 20px;">
        <div style="background: var(--surface); border: 1px solid var(--border); border-radius: 16px; padding: 28px; box-shadow: var(--shadow);">
            <h1>Admin Panel, <?= $userName ?> 🔒</h1>
            <p class="muted">This dashboard is restricted to admin users only. It displays live statistics, user activity and recent registrations from the database.</p>

            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Users</h3>
                    <div class="value"><?= $totalUsers ?></div>
                </div>
                <div class="stat-card">
                    <h3>Students</h3>
                    <div class="value"><?= $totalStudents ?></div>
                </div>
                <div class="stat-card">
                    <h3>Admins</h3>
                    <div class="value"><?= $totalAdmins ?></div>
                </div>
                <div class="stat-card">
                    <h3>Events</h3>
                    <div class="value"><?= $totalEvents ?></div>
                </div>
            </div>

            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 20px; margin-top: 28px;">
                <a href="manage_students.php" class="card" style="text-decoration:none; color:inherit; display:block;">
                    <h3>👥 Students</h3>
                    <p>Manage user accounts and academic records.</p>
                </a>
                <a href="manage_events.php" class="card" style="text-decoration:none; color:inherit; display:block;">
                    <h3>📅 Events</h3>
                    <p>Review scheduled campus activities, add new posters, and manage event records.</p>
                </a>
                <a href="view_submissions.php" class="card" style="text-decoration:none; color:inherit; display:block;">
                    <h3>🛡️ Audit Logs</h3>
                    <p>Monitor access, sessions, and registration activity logs.</p>
                </a>
            </div>

            <div style="margin-top: 30px; display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px;">
                <section>
                    <h2>Latest Registrations</h2>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr><th>Student</th><th>Event</th><th>Date</th></tr>
                            </thead>
                            <tbody>
                                <?php if ($latestRegistrations && $latestRegistrations->num_rows > 0): ?>
                                    <?php while ($row = $latestRegistrations->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['fullname']) ?></td>
                                            <td><?= htmlspecialchars($row['event_title']) ?></td>
                                            <td><?= htmlspecialchars(date('d M Y', strtotime($row['registration_date']))) ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="3" class="muted">No registration activity found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section>
                    <h2>Recent Audit Log</h2>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr><th>Event</th><th>Identifier</th><th>Status</th></tr>
                            </thead>
                            <tbody>
                                <?php if ($recentAudit && $recentAudit->num_rows > 0): ?>
                                    <?php while ($row = $recentAudit->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['event_type']) ?></td>
                                            <td><?= htmlspecialchars($row['identifier']) ?></td>
                                            <td><?= htmlspecialchars($row['status']) ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="3" class="muted">No audit log entries available.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    </main>
</body>
</html>
