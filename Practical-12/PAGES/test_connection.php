<?php
/**
 * Practical 9: Connection Test, Security Inspector & Database Records Viewer
 * Demonstrates:
 * 1. Secure MySQLi and PDO Connection
 * 2. Prepared Statements with Parameter Binding
 * 3. Password Hashing Verification (BCRYPT $2y$...)
 * 4. Duplicate Email/Username Prevention
 * 5. Audit Log Trail Inspection (Advanced Extension)
 */

require_once 'db.php';

$students = [];
$audit_logs = [];
$events = [];
$registrations = [];
$server_info = [];

if ($mysqli_connected && $conn) {
    try {
        // Server & Driver Info via MySQLi
        $server_info = [
            'Driver'         => 'MySQLi (Object-Oriented)',
            'Server Version' => $conn->server_info,
            'Protocol'       => $conn->protocol_version,
            'Host Info'      => $conn->host_info,
            'Charset'        => $conn->character_set_name(),
        ];

        // 1. Fetch Students with MySQLi Prepared Statement
        $stmtStudents = $conn->prepare("SELECT id, fullname, username, email, password, branch, semester, created_at FROM students ORDER BY id ASC");
        if ($stmtStudents) {
            $stmtStudents->execute();
            $res = $stmtStudents->get_result();
            while ($row = $res->fetch_assoc()) {
                $students[] = $row;
            }
            $stmtStudents->close();
        }

        // 2. Fetch Audit Logs with MySQLi Prepared Statement (Advanced Extension)
        $stmtAudit = $conn->prepare("SELECT id, event_type, identifier, ip_address, status, details, created_at FROM audit_logs ORDER BY id DESC LIMIT 20");
        if ($stmtAudit) {
            $stmtAudit->execute();
            $resAudit = $stmtAudit->get_result();
            while ($row = $resAudit->fetch_assoc()) {
                $audit_logs[] = $row;
            }
            $stmtAudit->close();
        }

        // 3. Fetch Events
        $stmtEvents = $conn->prepare("SELECT id, title, category, event_date, location, capacity FROM events ORDER BY event_date ASC");
        if ($stmtEvents) {
            $stmtEvents->execute();
            $resEvents = $stmtEvents->get_result();
            while ($row = $resEvents->fetch_assoc()) {
                $events[] = $row;
            }
            $stmtEvents->close();
        }

        // 4. Fetch Registrations with JOIN
        $sqlJoin = "SELECT 
                        r.id AS reg_id,
                        s.fullname AS student_name,
                        s.email AS student_email,
                        s.branch,
                        e.title AS event_title,
                        e.category AS event_category,
                        e.event_date,
                        e.location,
                        r.registration_date,
                        r.status
                    FROM registrations r
                    INNER JOIN students s ON r.student_id = s.id
                    INNER JOIN events e ON r.event_id = e.id
                    ORDER BY r.id ASC";
        $stmtReg = $conn->prepare($sqlJoin);
        if ($stmtReg) {
            $stmtReg->execute();
            $resReg = $stmtReg->get_result();
            while ($row = $resReg->fetch_assoc()) {
                $registrations[] = $row;
            }
            $stmtReg->close();
        }

    } catch (Exception $e) {
        $db_error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Status & Security Inspector - Practical 9</title>
    <link rel="stylesheet" href="../CSS/script.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .db-container {
            max-width: 1100px;
            margin: 30px auto;
            padding: 0 16px 40px;
        }
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 999px;
            font-weight: 600;
            font-size: 0.95rem;
        }
        .status-online {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #6ee7b7;
        }
        .status-offline {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin: 20px 0;
        }
        .info-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 16px;
            box-shadow: var(--shadow);
        }
        .info-label {
            font-size: 0.8rem;
            text-transform: uppercase;
            color: var(--muted);
            letter-spacing: 0.05em;
        }
        .info-val {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text);
            margin-top: 4px;
        }
        .section-box {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 28px;
            box-shadow: var(--shadow);
        }
        .table-responsive {
            overflow-x: auto;
            margin-top: 14px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }
        th, td {
            padding: 10px 14px;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }
        th {
            background: rgba(99, 102, 241, 0.05);
            font-weight: 600;
            color: var(--text);
        }
        .hash-code {
            font-family: monospace;
            background: rgba(0,0,0,0.06);
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.8rem;
            color: #4f46e5;
            display: inline-block;
            max-width: 260px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .badge-status {
            padding: 3px 8px;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge-status.success { background: #d1fae5; color: #065f46; }
        .badge-status.error   { background: #fee2e2; color: #991b1b; }
        .badge-status.warn    { background: #fef3c7; color: #92400e; }
        .check-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 14px;
            margin-top: 14px;
        }
        .check-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px;
            background: var(--bg);
            border-radius: 10px;
            border: 1px solid var(--border);
            font-size: 0.9rem;
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
            <a href="register.php">Practical 9 (Register)</a>
            <a href="test_connection.php" class="active">DB Status & Security</a>
            <a href="view_submissions.php">Audit & Records</a>
        </div>
        <button class="theme-toggle" id="themeToggle" aria-label="Toggle dark mode">🌙</button>
    </nav>

    <main class="db-container">
        <!-- Top Status -->
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: gap; margin-bottom: 20px;">
            <div>
                <h2>Practical 9: Database & Security Inspector</h2>
                <p style="color: var(--muted); margin-top: 4px;">MySQLi Prepared Statements, BCRYPT Hashing & Registration Audit Trail</p>
            </div>
            <div>
                <?php if ($mysqli_connected): ?>
                    <span class="status-badge status-online">🟢 MySQLi Connected</span>
                <?php else: ?>
                    <span class="status-badge status-offline">🔴 MySQLi Disconnected</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Security Verification Checklist -->
        <div class="section-box">
            <h3>🔒 Practical 9 Security Architecture Checklist</h3>
            <div class="check-grid">
                <div class="check-item">
                    <span>✅</span>
                    <div>
                        <strong>MySQLi Database Engine</strong>
                        <div style="font-size: 0.8rem; color: var(--muted);">Object-oriented MySQLi with utf8mb4</div>
                    </div>
                </div>
                <div class="check-item">
                    <span>✅</span>
                    <div>
                        <strong>password_hash() (BCRYPT)</strong>
                        <div style="font-size: 0.8rem; color: var(--muted);">One-way irreversible 60-char hashing</div>
                    </div>
                </div>
                <div class="check-item">
                    <span>✅</span>
                    <div>
                        <strong>Duplicate Email/User Check</strong>
                        <div style="font-size: 0.8rem; color: var(--muted);">Pre-insert check with unique constraints</div>
                    </div>
                </div>
                <div class="check-item">
                    <span>✅</span>
                    <div>
                        <strong>Consistent Validations</strong>
                        <div style="font-size: 0.8rem; color: var(--muted);">Identical rules on JS client & PHP backend</div>
                    </div>
                </div>
                <div class="check-item">
                    <span>✅</span>
                    <div>
                        <strong>Prepared Statements (Intermediate)</strong>
                        <div style="font-size: 0.8rem; color: var(--muted);">Parameter binding blocks SQL injection</div>
                    </div>
                </div>
                <div class="check-item">
                    <span>✅</span>
                    <div>
                        <strong>CSRF & Audit Logs (Advanced)</strong>
                        <div style="font-size: 0.8rem; color: var(--muted);">Cryptographic tokens & audit logging</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Server Info Grid -->
        <?php if ($mysqli_connected && !empty($server_info)): ?>
            <div class="info-grid">
                <?php foreach ($server_info as $label => $val): ?>
                    <div class="info-card">
                        <div class="info-label"><?= htmlspecialchars($label) ?></div>
                        <div class="info-val"><?= htmlspecialchars($val) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Table 1: Registered Students & Password Hashes -->
        <div class="section-box">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                <h3>👤 Registered Students (Demonstrating Password Hashing)</h3>
                <a href="register.php" style="background: #4f46e5; color: white; padding: 6px 14px; border-radius: 8px; text-decoration: none; font-size: 0.85rem; font-weight: 600;">
                    + Test New Registration
                </a>
            </div>
            <p style="color: var(--muted); font-size: 0.85rem; margin-top: 4px;">
                Notice all passwords begin with <code>$2y$10$...</code> (PHP standard BCRYPT hash). No plain text passwords are ever stored.
            </p>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Full Name</th>
                            <th>Username</th>
                            <th>Email Address</th>
                            <th>Password Hash (password_hash)</th>
                            <th>Branch</th>
                            <th>Sem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($students)): ?>
                            <tr><td colspan="7" style="text-align: center; color: var(--muted);">No students found. Import studenthub.sql in phpMyAdmin.</td></tr>
                        <?php else: ?>
                            <?php foreach ($students as $stu): ?>
                                <tr>
                                    <td><strong>#<?= htmlspecialchars($stu['id']) ?></strong></td>
                                    <td><?= htmlspecialchars($stu['fullname']) ?></td>
                                    <td><code><?= htmlspecialchars($stu['username'] ?? 'N/A') ?></code></td>
                                    <td><?= htmlspecialchars($stu['email']) ?></td>
                                    <td><span class="hash-code" title="<?= htmlspecialchars($stu['password']) ?>"><?= htmlspecialchars($stu['password']) ?></span></td>
                                    <td><?= htmlspecialchars($stu['branch']) ?></td>
                                    <td><?= htmlspecialchars($stu['semester']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Table 2: Registration Audit Logs -->
        <div class="section-box">
            <h3>📋 Registration Audit Log Trail (Advanced Extension)</h3>
            <p style="color: var(--muted); font-size: 0.85rem; margin-top: 4px;">
                Records successful signups, duplicate email attempts, and security validation failures.
            </p>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Log ID</th>
                            <th>Event</th>
                            <th>Identifier (Email/User)</th>
                            <th>IP Address</th>
                            <th>Status</th>
                            <th>Details</th>
                            <th>Timestamp</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($audit_logs)): ?>
                            <tr><td colspan="7" style="text-align: center; color: var(--muted);">No audit log entries found yet. Submit the registration form to create entries!</td></tr>
                        <?php else: ?>
                            <?php foreach ($audit_logs as $log): ?>
                                <tr>
                                    <td>#<?= htmlspecialchars($log['id']) ?></td>
                                    <td><code><?= htmlspecialchars($log['event_type']) ?></code></td>
                                    <td><?= htmlspecialchars($log['identifier']) ?></td>
                                    <td><?= htmlspecialchars($log['ip_address']) ?></td>
                                    <td>
                                        <?php if ($log['status'] === 'SUCCESS'): ?>
                                            <span class="badge-status success">SUCCESS</span>
                                        <?php elseif (strpos($log['status'], 'DUPLICATE') !== false): ?>
                                            <span class="badge-status warn"><?= htmlspecialchars($log['status']) ?></span>
                                        <?php else: ?>
                                            <span class="badge-status error"><?= htmlspecialchars($log['status']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($log['details']) ?></td>
                                    <td><?= htmlspecialchars($log['created_at']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>
