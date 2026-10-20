<?php
/**
 * Practical 8: Connection Test & Database Records Viewer
 * Demonstrates:
 * 1. Secure PDO Database Connection
 * 2. Prepared Statements with Parameter Binding
 * 3. Normalized Relational JOIN queries (Students, Events, Registrations)
 * 4. Stored Procedure Invocation (Advanced Extension)
 */

require_once 'db.php';

$students = [];
$events = [];
$registrations = [];
$procedure_results = [];
$server_info = [];

if ($db_connected && $pdo) {
    try {
        // Server & Driver Info
        $server_info = [
            'Driver'         => $pdo->getAttribute(PDO::ATTR_DRIVER_NAME),
            'Server Version' => $pdo->getAttribute(PDO::ATTR_SERVER_VERSION),
            'Client Version' => $pdo->getAttribute(PDO::ATTR_CLIENT_VERSION),
            'Connection'     => $pdo->getAttribute(PDO::ATTR_CONNECTION_STATUS),
        ];

        // 1. Prepared Statement: Fetch Students
        $stmtStudents = $pdo->prepare("SELECT id, fullname, email, branch, semester, created_at FROM students ORDER BY id ASC");
        $stmtStudents->execute();
        $students = $stmtStudents->fetchAll();

        // 2. Prepared Statement: Fetch Events
        $stmtEvents = $pdo->prepare("SELECT id, title, category, event_date, location, capacity FROM events ORDER BY event_date ASC");
        $stmtEvents->execute();
        $events = $stmtEvents->fetchAll();

        // 3. Prepared Statement with Relational INNER JOINs
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
        $stmtReg = $pdo->prepare($sqlJoin);
        $stmtReg->execute();
        $registrations = $stmtReg->fetchAll();

        // 4. Advanced Extension: Test Stored Procedure (sp_GetEventAttendees for event_id = 1)
        try {
            $stmtProc = $pdo->prepare("CALL sp_GetEventAttendees(?)");
            $stmtProc->execute([1]);
            $procedure_results = $stmtProc->fetchAll();
            $stmtProc->closeCursor(); // Free connection for next queries
        } catch (PDOException $pe) {
            // Procedure might not yet be imported, handled gracefully
            $procedure_results = [];
        }

    } catch (PDOException $e) {
        $db_error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Status & Records - Practical 8</title>
    <link rel="stylesheet" href="../CSS/script.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .db-container {
            max-width: 1100px;
            margin: 30px auto;
            padding: 0 16px;
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
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
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
        .section-box h3 {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
            color: var(--text);
        }
        .table-responsive {
            overflow-x: auto;
            margin-top: 14px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 0.92rem;
        }
        th, td {
            padding: 12px 14px;
            border-bottom: 1px solid var(--border);
        }
        th {
            background: rgba(99, 102, 241, 0.08);
            font-weight: 600;
            color: var(--text);
        }
        tr:hover td {
            background: rgba(99, 102, 241, 0.03);
        }
        .pill {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .pill-active { background: #d1fae5; color: #065f46; }
        .pill-tag { background: #e0e7ff; color: #3730a3; }
        .action-btns {
            display: flex;
            gap: 12px;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        .btn-link {
            padding: 10px 18px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            background: var(--primary);
            color: white;
            transition: opacity 0.2s;
        }
        .btn-link:hover { opacity: 0.9; }
        .btn-secondary {
            background: transparent;
            color: var(--text);
            border: 1px solid var(--border);
        }
        .instruction-box {
            background: #fffbeb;
            border: 1px solid #fde68a;
            color: #92400e;
            padding: 16px;
            border-radius: 12px;
            margin-top: 16px;
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
            <a href="assignment.html">Assignments</a>
            <a href="attendence.html">Attendance</a>
            <a href="result.html">Result</a>
            <a href="profile.html">Profile</a>
            <a href="contact.html">Contact</a>
            <a href="register_db.php">Register (MySQL)</a>
            <a href="test_connection.php" class="active">DB Status</a>
        </div>
        <button class="theme-toggle" id="themeToggle" aria-label="Toggle dark mode">🌙</button>
    </nav>

    <main class="db-container">
        <!-- Header -->
        <div style="margin-bottom: 24px;">
            <h1>🔌 Practical 8: MySQL & PDO Connectivity</h1>
            <p style="color: var(--muted);">Schema Design, Normalized ER Model, PDO Prepared Statements & Stored Procedures</p>
        </div>

        <!-- Connection Status -->
        <div class="section-box">
            <h3>⚡ Database Connection Status</h3>
            <?php if ($db_connected): ?>
                <div class="status-badge status-online">
                    🟢 Connected Successfully via PHP PDO (Database: <code>studenthub</code>)
                </div>
                <div class="info-grid">
                    <div class="info-card">
                        <div class="info-label">Database Host</div>
                        <div class="info-val"><?= htmlspecialchars($host) ?></div>
                    </div>
                    <div class="info-card">
                        <div class="info-label">Database Name</div>
                        <div class="info-val"><?= htmlspecialchars($dbname) ?></div>
                    </div>
                    <div class="info-card">
                        <div class="info-label">Character Set</div>
                        <div class="info-val"><?= htmlspecialchars($charset) ?> (Safe Unicode)</div>
                    </div>
                    <div class="info-card">
                        <div class="info-label">Driver</div>
                        <div class="info-val"><?= htmlspecialchars($server_info['Driver'] ?? 'mysql') ?> (PDO)</div>
                    </div>
                </div>
            <?php else: ?>
                <div class="status-badge status-offline">
                    🔴 Database Offline: <?= htmlspecialchars($db_error ?? 'Unable to connect to MySQL') ?>
                </div>
                <div class="instruction-box">
                    <strong>📌 Setup Instructions for Practical 8:</strong>
                    <ol style="margin: 8px 0 0 20px; line-height: 1.6;">
                        <li>Start <strong>Apache</strong> and <strong>MySQL</strong> in the <strong>XAMPP Control Panel</strong>.</li>
                        <li>Open <a href="http://localhost/phpmyadmin" target="_blank" style="color: #b45309; text-decoration: underline;">phpMyAdmin</a>.</li>
                        <li>Import the file <code>Practicals/Practical-8/studenthub.sql</code> (or run its contents in the SQL tab).</li>
                        <li>Refresh this page to verify live PDO connectivity and view seeded records!</li>
                    </ol>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($db_connected): ?>
            <!-- 1. Registrations Join Section -->
            <div class="section-box">
                <h3>🎟️ Relational Registrations (Normalized M:N JOIN)</h3>
                <p style="color: var(--muted); font-size: 0.9rem;">
                    Demonstrates relational normalization: <code>registrations</code> references <code>students(id)</code> and <code>events(id)</code> via Foreign Keys.
                </p>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Student Name</th>
                                <th>Email</th>
                                <th>Branch</th>
                                <th>Event Title</th>
                                <th>Category</th>
                                <th>Event Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($registrations as $r): ?>
                                <tr>
                                    <td><?= htmlspecialchars($r['reg_id']) ?></td>
                                    <td><strong><?= htmlspecialchars($r['student_name']) ?></strong></td>
                                    <td><?= htmlspecialchars($r['student_email']) ?></td>
                                    <td><?= htmlspecialchars($r['branch']) ?></td>
                                    <td><?= htmlspecialchars($r['event_title']) ?></td>
                                    <td><span class="pill pill-tag"><?= htmlspecialchars($r['event_category']) ?></span></td>
                                    <td><?= htmlspecialchars($r['event_date']) ?></td>
                                    <td><span class="pill pill-active"><?= htmlspecialchars($r['status']) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 2. Students Table Section -->
            <div class="section-box">
                <h3>👨‍🎓 Students Table (Prepared Statement: <code>SELECT * FROM students</code>)</h3>
                <p style="color: var(--muted); font-size: 0.9rem;">Total <?= count($students) ?> students seeded.</p>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Branch</th>
                                <th>Semester</th>
                                <th>Registered At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $s): ?>
                                <tr>
                                    <td><?= htmlspecialchars($s['id']) ?></td>
                                    <td><strong><?= htmlspecialchars($s['fullname']) ?></strong></td>
                                    <td><?= htmlspecialchars($s['email']) ?></td>
                                    <td><?= htmlspecialchars($s['branch'] ?? 'Computer Engineering') ?></td>
                                    <td>Sem <?= htmlspecialchars($s['semester'] ?? '5') ?></td>
                                    <td><?= htmlspecialchars($s['created_at'] ?? 'N/A') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 3. Events Table Section -->
            <div class="section-box">
                <h3>📅 Events Table (Prepared Statement: <code>SELECT * FROM events</code>)</h3>
                <p style="color: var(--muted); font-size: 0.9rem;">Total <?= count($events) ?> events seeded.</p>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Date</th>
                                <th>Location</th>
                                <th>Capacity</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($events as $ev): ?>
                                <tr>
                                    <td><?= htmlspecialchars($ev['id']) ?></td>
                                    <td><strong><?= htmlspecialchars($ev['title']) ?></strong></td>
                                    <td><span class="pill pill-tag"><?= htmlspecialchars($ev['category']) ?></span></td>
                                    <td><?= htmlspecialchars($ev['event_date']) ?></td>
                                    <td>🏛️ <?= htmlspecialchars($ev['location']) ?></td>
                                    <td><?= htmlspecialchars($ev['capacity']) ?> seats</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 4. Advanced Extension: Stored Procedure -->
            <div class="section-box">
                <h3>⚙️ Advanced Extension: Stored Procedure Execution</h3>
                <p style="color: var(--muted); font-size: 0.9rem;">
                    Invoked MySQL Stored Procedure <code>CALL sp_GetEventAttendees(1)</code> for <em>Event #1 (Web Development Workshop)</em>:
                </p>
                <div class="table-responsive">
                    <?php if (!empty($procedure_results)): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Event Title</th>
                                    <th>Student ID</th>
                                    <th>Attendee Name</th>
                                    <th>Email</th>
                                    <th>Branch</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($procedure_results as $pr): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($pr['event_title']) ?></td>
                                        <td><?= htmlspecialchars($pr['student_id']) ?></td>
                                        <td><strong><?= htmlspecialchars($pr['fullname']) ?></strong></td>
                                        <td><?= htmlspecialchars($pr['email']) ?></td>
                                        <td><?= htmlspecialchars($pr['branch']) ?></td>
                                        <td><span class="pill pill-active"><?= htmlspecialchars($pr['status']) ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p style="color: var(--muted); font-style: italic;">No attendees found or stored procedure awaiting database initialization.</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Action Links -->
        <div class="action-btns">
            <a href="register_db.php" class="btn-link">➕ Register New Student / Event (MySQL)</a>
            <a href="index.html" class="btn-link btn-secondary">🏠 Back to Home</a>
            <a href="dashboard.html" class="btn-link btn-secondary">📊 Dashboard</a>
        </div>
    </main>
</body>
</html>
