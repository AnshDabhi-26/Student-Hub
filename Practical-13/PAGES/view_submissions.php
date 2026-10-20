<?php
/**
 * Practical 9: View Registered Users, Password Hashes & Registration Audit Logs
 */
require_once 'db.php';

$jsonFile = "../JSON/submissions.json";
$csvFile  = "../JSON/submissions.csv";

$jsonData = file_exists($jsonFile) ? json_decode(file_get_contents($jsonFile), true) : [];

$csvData = [];
if (file_exists($csvFile) && ($fp = fopen($csvFile, "r")) !== false) {
    fgetcsv($fp); // Skip header row
    while (($row = fgetcsv($fp)) !== false) {
        $csvData[] = $row;
    }
    fclose($fp);
}

// Fetch MySQLi Students & Audit Logs
$dbStudents = [];
$auditLogs = [];

if ($mysqli_connected && $conn) {
    try {
        $res = $conn->query("SELECT id, fullname, username, email, password, branch, semester, created_at FROM students ORDER BY id DESC");
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $dbStudents[] = $row;
            }
        }

        $resLogs = $conn->query("SELECT id, event_type, identifier, ip_address, status, details, created_at FROM audit_logs ORDER BY id DESC");
        if ($resLogs) {
            while ($row = $resLogs->fetch_assoc()) {
                $auditLogs[] = $row;
            }
        }
    } catch (Exception $e) {
        // Handled gracefully
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Registered Users & Audit Records - StudentHub</title>
    <link rel="stylesheet" href="../CSS/script.css" />
    <style>
        .records-card {
            background: var(--surface);
            padding: 30px;
            border: 1px solid var(--border);
            border-radius: 18px;
            box-shadow: var(--shadow);
            margin: 20px auto;
            max-width: 1050px;
        }
        .section-title {
            margin-top: 28px;
            color: var(--primary);
            font-size: 1.15rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .table-wrapper {
            overflow-x: auto;
            margin-top: 10px;
            margin-bottom: 25px;
        }
        .empty-text {
            color: var(--muted);
            font-style: italic;
            margin: 10px 0;
        }
        .hash-code {
            font-family: monospace;
            background: rgba(0,0,0,0.06);
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.8rem;
            color: #4f46e5;
            display: inline-block;
            max-width: 250px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .status-badge {
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-error { background: #fee2e2; color: #991b1b; }
        .badge-warn { background: #fef3c7; color: #92400e; }
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
            <a href="register.php">Practical 9 (Register)</a>
            <a href="test_connection.php">DB Status</a>
            <a href="view_submissions.php" class="active">Audit & Records</a>
        </div>
        <button class="theme-toggle" id="themeToggle" aria-label="Toggle dark mode">🌙</button>
    </nav>

    <main>
        <div class="records-card">
            <h2>Practical 9: Registered Accounts & Security Audit Trail</h2>
            <p style="color: var(--muted);">Live verification of MySQLi Database inserts, BCRYPT hashed passwords, and audit logs.</p>

            <!-- Section 1: Live MySQLi Database Registrations -->
            <h3 class="section-title">🗄️ MySQL Database Registrations (BCRYPT Hashes)</h3>
            <div class="table-wrapper">
                <?php if (!empty($dbStudents)): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Full Name</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Password Hash</th>
                                <th>Branch</th>
                                <th>Sem</th>
                                <th>Registered At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dbStudents as $s): ?>
                                <tr>
                                    <td><strong>#<?= htmlspecialchars($s['id']) ?></strong></td>
                                    <td><?= htmlspecialchars($s['fullname']) ?></td>
                                    <td><code><?= htmlspecialchars($s['username'] ?? 'N/A') ?></code></td>
                                    <td><?= htmlspecialchars($s['email']) ?></td>
                                    <td><span class="hash-code" title="<?= htmlspecialchars($s['password']) ?>"><?= htmlspecialchars($s['password']) ?></span></td>
                                    <td><?= htmlspecialchars($s['branch']) ?></td>
                                    <td><?= htmlspecialchars($s['semester']) ?></td>
                                    <td><?= htmlspecialchars($s['created_at']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="empty-text">No database records found. Register an account using <a href="register.php">register.php</a>.</p>
                <?php endif; ?>
            </div>

            <!-- Section 2: Audit Logs -->
            <h3 class="section-title">📋 Practical 9 Audit Logs (Advanced Extension)</h3>
            <div class="table-wrapper">
                <?php if (!empty($auditLogs)): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Event</th>
                                <th>Target</th>
                                <th>IP</th>
                                <th>Status</th>
                                <th>Details</th>
                                <th>Timestamp</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($auditLogs as $log): ?>
                                <tr>
                                    <td>#<?= htmlspecialchars($log['id']) ?></td>
                                    <td><code><?= htmlspecialchars($log['event_type']) ?></code></td>
                                    <td><?= htmlspecialchars($log['identifier']) ?></td>
                                    <td><?= htmlspecialchars($log['ip_address']) ?></td>
                                    <td>
                                        <span class="status-badge <?= $log['status'] === 'SUCCESS' ? 'badge-success' : (strpos($log['status'], 'DUPLICATE') !== false ? 'badge-warn' : 'badge-error') ?>">
                                            <?= htmlspecialchars($log['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($log['details']) ?></td>
                                    <td><?= htmlspecialchars($log['created_at']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="empty-text">No audit logs recorded yet.</p>
                <?php endif; ?>
            </div>

            <div style="display: flex; gap: 16px; margin-top: 20px;">
                <a href="register.php" style="color: var(--primary); font-weight: 600;">← Back to Registration Form</a>
                <a href="test_connection.php" style="color: var(--primary); font-weight: 600;">Inspect Database Engine & Hashes →</a>
            </div>
        </div>
    </main>
</body>
</html>
