<?php
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Saved Submissions - StudentHub</title>
    <link rel="stylesheet" href="../CSS/script.css" />
    <style>
        .records-card {
            background: var(--surface);
            padding: 30px;
            border: 1px solid var(--border);
            border-radius: 18px;
            box-shadow: var(--shadow);
            margin: 20px auto;
        }
        .section-title {
            margin-top: 25px;
            color: var(--primary);
            font-size: 1.2rem;
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
            <a href="login.html">Login</a>
            <a href="register.php">Register</a>
            <a href="view_submissions.php" class="active">View Records</a>
        </div>
        <button class="theme-toggle" id="themeToggle" aria-label="Toggle dark mode">🌙</button>
    </nav>

    <main>
        <div class="records-card">
            <h2>Practical 7: Stored Records Viewer</h2>
            <p style="color: var(--muted);">Records saved on the server via PHP into JSON and CSV files.</p>

            <h3 class="section-title">📄 Saved JSON Submissions</h3>
            <div class="table-wrapper">
                <?php if (!empty($jsonData)): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Submitted At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($jsonData as $r): ?>
                                <tr>
                                    <td><code><?= htmlspecialchars($r['id'] ?? 'N/A') ?></code></td>
                                    <td><?= htmlspecialchars($r['name'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($r['email'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($r['time'] ?? '') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="empty-text">No JSON records found yet.</p>
                <?php endif; ?>
            </div>

            <h3 class="section-title">📊 Saved CSV Submissions</h3>
            <div class="table-wrapper">
                <?php if (!empty($csvData)): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Submitted At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($csvData as $r): ?>
                                <tr>
                                    <td><code><?= htmlspecialchars($r[0] ?? 'N/A') ?></code></td>
                                    <td><?= htmlspecialchars($r[1] ?? '') ?></td>
                                    <td><?= htmlspecialchars($r[2] ?? '') ?></td>
                                    <td><?= htmlspecialchars($r[3] ?? '') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="empty-text">No CSV records found yet.</p>
                <?php endif; ?>
            </div>

            <a href="register.php" style="color: var(--primary); font-weight: 600;">← Back to Registration Form</a>
        </div>
    </main>
</body>
</html>
