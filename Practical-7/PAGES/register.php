<?php
session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors = [];
$success = "";
$name = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $errors[] = "Invalid CSRF token.";
    } else {
        $name  = htmlspecialchars(trim($_POST['name'] ?? ''));
        $email = htmlspecialchars(trim($_POST['email'] ?? ''));
        $pass  = trim($_POST['password'] ?? '');

        if (empty($name)) {
            $errors[] = "Full Name is required.";
        }
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Valid email is required.";
        }
        if (strlen($pass) < 6) {
            $errors[] = "Password must be at least 6 characters.";
        }

        if (empty($errors)) {
            $id = "STU-" . rand(100, 999);
            $time = date("Y-m-d H:i");

            // Save to JSON
            $jsonFile = "../JSON/submissions.json";
            $data = file_exists($jsonFile) ? json_decode(file_get_contents($jsonFile), true) : [];
            $data[] = ["id" => $id, "name" => $name, "email" => $email, "time" => $time];
            file_put_contents($jsonFile, json_encode($data, JSON_PRETTY_PRINT));

            // Save to CSV
            $csvFile = "../JSON/submissions.csv";
            $fp = fopen($csvFile, "a");
            if (filesize($csvFile) == 0) {
                fputcsv($fp, ["ID", "Name", "Email", "Time"]);
            }
            fputcsv($fp, [$id, $name, $email, $time]);
            fclose($fp);

            $success = "Registration successful! Record saved to JSON & CSV.";
            $name = $email = "";
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Register - StudentHub</title>
    <link rel="stylesheet" href="../CSS/script.css" />
    <style>
        .register-box {
            margin: 20px auto;
        }
        .alert-error {
            background-color: #fee2e2;
            color: #991b1b;
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 16px;
            border: 1px solid #fca5a5;
        }
        .alert-success {
            background-color: #d1fae5;
            color: #065f46;
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 16px;
            border: 1px solid #6ee7b7;
        }
        .view-link {
            display: inline-block;
            margin-top: 14px;
            color: var(--primary);
            font-weight: 600;
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
            <a href="register.php" class="active">Register</a>
            <a href="view_submissions.php">View Records</a>
        </div>
        <button class="theme-toggle" id="themeToggle" aria-label="Toggle dark mode">🌙</button>
    </nav>

    <main>
        <section class="register-box">
            <h2>Create Account (Practical 7)</h2>
            <p style="color: var(--muted); margin-bottom: 20px;">PHP Server-side validation with CSV & JSON storage</p>

            <?php if (!empty($errors)): ?>
                <div class="alert-error">
                    <?php foreach ($errors as $e): ?>
                        <p style="margin: 4px 0;">⚠️ <?= htmlspecialchars($e) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert-success">
                    <p style="margin: 0;">✅ <?= htmlspecialchars($success) ?></p>
                    <a href="view_submissions.php" class="view-link">📊 View Stored CSV / JSON Records →</a>
                </div>
            <?php endif; ?>

            <form method="POST" action="register.php">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" value="<?= htmlspecialchars($name) ?>" placeholder="Your full name" required />

                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($email) ?>" placeholder="student@example.com" required />

                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Min 6 characters" required />

                <button type="submit" style="margin-top: 10px;">Register Account</button>

                <a href="view_submissions.php" class="view-link" style="text-align: center;">View Saved Records</a>
            </form>
        </section>
    </main>
</body>
</html>
