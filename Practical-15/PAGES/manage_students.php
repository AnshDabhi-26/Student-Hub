<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

require_role(['admin'], $conn);

$message = '';
$messageType = '';
$editStudent = null;

$search = trim($_GET['search'] ?? '');
$branchFilter = trim($_GET['branch'] ?? '');
$courseFilter = trim($_GET['course'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

if (isset($_GET['edit'])) {
    $editId = (int) $_GET['edit'];
    if ($editId > 0) {
        $stmt = $conn->prepare('SELECT id, fullname, username, email, password, branch, semester, course, status FROM students WHERE id = ? AND role = ? LIMIT 1');
        if ($stmt) {
            $role = 'student';
            $stmt->bind_param('is', $editId, $role);
            $stmt->execute();
            $result = $stmt->get_result();
            $editStudent = $result->fetch_assoc();
            $stmt->close();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'add';

    if ($action === 'delete') {
        $deleteId = (int) ($_POST['id'] ?? 0);
        if ($deleteId > 0) {
            $stmt = $conn->prepare('DELETE FROM students WHERE id = ? AND role = ?');
            if ($stmt) {
                $role = 'student';
                $stmt->bind_param('is', $deleteId, $role);
                $stmt->execute();
                $stmt->close();
                $message = 'Student deleted successfully.';
                $messageType = 'success';
            } else {
                $message = 'Unable to delete the student record.';
                $messageType = 'error';
            }
        } else {
            $message = 'Invalid student record selected for deletion.';
            $messageType = 'error';
        }
    } else {
        $studentId = (int) ($_POST['id'] ?? 0);
        $fullname = trim($_POST['fullname'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $branch = trim($_POST['branch'] ?? 'Computer Engineering');
        $course = trim($_POST['course'] ?? '');
        $semester = (int) ($_POST['semester'] ?? 1);
        $status = trim($_POST['status'] ?? 'Active');
        $password = trim($_POST['password'] ?? '');

        if ($fullname === '' || $username === '' || $email === '' || $course === '') {
            $message = 'Full name, username, email and course are required.';
            $messageType = 'error';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Please enter a valid email address.';
            $messageType = 'error';
        } elseif ($semester < 1 || $semester > 8) {
            $message = 'Semester must be between 1 and 8.';
            $messageType = 'error';
        } else {
            if ($action === 'update' && $studentId > 0) {
                $checkStmt = $conn->prepare('SELECT id FROM students WHERE email = ? AND id != ? LIMIT 1');
                if ($checkStmt) {
                    $checkStmt->bind_param('si', $email, $studentId);
                    $checkStmt->execute();
                    $exists = $checkStmt->get_result()->fetch_assoc();
                    $checkStmt->close();
                    if ($exists) {
                        $message = 'Another student already uses this email address.';
                        $messageType = 'error';
                    }
                }

                if ($messageType !== 'error') {
                    if ($password !== '') {
                        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $conn->prepare('UPDATE students SET fullname = ?, username = ?, email = ?, branch = ?, course = ?, semester = ?, status = ?, password = ? WHERE id = ? AND role = ?');
                        if ($stmt) {
                            $role = 'student';
                            $stmt->bind_param('sssssisisi', $fullname, $username, $email, $branch, $course, $semester, $status, $passwordHash, $studentId, $role);
                            $stmt->execute();
                            $stmt->close();
                            $message = 'Student record updated successfully.';
                            $messageType = 'success';
                        }
                    } else {
                        $stmt = $conn->prepare('UPDATE students SET fullname = ?, username = ?, email = ?, branch = ?, course = ?, semester = ?, status = ? WHERE id = ? AND role = ?');
                        if ($stmt) {
                            $role = 'student';
                            $stmt->bind_param('sssssisis', $fullname, $username, $email, $branch, $course, $semester, $status, $studentId, $role);
                            $stmt->execute();
                            $stmt->close();
                            $message = 'Student record updated successfully.';
                            $messageType = 'success';
                        }
                    }
                }
            } elseif ($action === 'add') {
                $checkStmt = $conn->prepare('SELECT id FROM students WHERE email = ? OR username = ? LIMIT 1');
                if ($checkStmt) {
                    $checkStmt->bind_param('ss', $email, $username);
                    $checkStmt->execute();
                    $exists = $checkStmt->get_result()->fetch_assoc();
                    $checkStmt->close();
                    if ($exists) {
                        $message = 'A student with this email or username already exists.';
                        $messageType = 'error';
                    }
                }

                if ($messageType !== 'error') {
                    $passwordHash = password_hash(($password !== '' ? $password : 'pass123'), PASSWORD_DEFAULT);
                    $stmt = $conn->prepare('INSERT INTO students (fullname, username, email, password, role, branch, course, semester, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
                    if ($stmt) {
                        $role = 'student';
                        $stmt->bind_param('sssssssis', $fullname, $username, $email, $passwordHash, $role, $branch, $course, $semester, $status);
                        $stmt->execute();
                        $stmt->close();
                        $message = 'New student added successfully.';
                        $messageType = 'success';
                    } else {
                        $message = 'Unable to add the student record.';
                        $messageType = 'error';
                    }
                }
            }
        }
    }
}

$filters = [];
$types = '';
$sql = 'SELECT id, fullname, username, email, course, branch, semester, status FROM students WHERE role = ?';
$params = ['student'];
$types = 's';

if ($search !== '') {
    $searchTerm = '%' . $search . '%';
    $sql .= ' AND (fullname LIKE ? OR email LIKE ? OR course LIKE ?)';
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= 'sss';
}

if ($branchFilter !== '') {
    $sql .= ' AND branch = ?';
    $params[] = $branchFilter;
    $types .= 's';
}

if ($courseFilter !== '') {
    $sql .= ' AND course = ?';
    $params[] = $courseFilter;
    $types .= 's';
}

if ($statusFilter !== '') {
    $sql .= ' AND status = ?';
    $params[] = $statusFilter;
    $types .= 's';
}

$sql .= ' ORDER BY fullname ASC';

$students = [];
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $students = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$branchList = [];
$courseList = [];
$branchResult = $conn->query("SELECT DISTINCT branch FROM students WHERE role = 'student' ORDER BY branch ASC");
if ($branchResult) {
    while ($row = $branchResult->fetch_assoc()) {
        $branchList[] = $row['branch'];
    }
}

$courseResult = $conn->query("SELECT DISTINCT course FROM students WHERE role = 'student' AND course IS NOT NULL AND course <> '' ORDER BY course ASC");
if ($courseResult) {
    while ($row = $courseResult->fetch_assoc()) {
        $courseList[] = $row['course'];
    }
}

$editValues = $editStudent ?: [
    'id' => '',
    'fullname' => '',
    'username' => '',
    'email' => '',
    'branch' => 'Computer Engineering',
    'course' => '',
    'semester' => 1,
    'status' => 'Active',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Student Management CRUD</title>
    <link rel="stylesheet" href="../CSS/script.css" />
    <style>
        body { background: #f8fafc; }
        .page-shell { max-width: 1200px; margin: 32px auto; padding: 0 18px 32px; }
        .panel { background: var(--surface); border: 1px solid var(--border); border-radius: 18px; box-shadow: var(--shadow); }
        .topbar { display:flex; justify-content:space-between; align-items:center; gap:16px; margin-bottom: 22px; }
        .controls { display:flex; flex-wrap:wrap; gap: 10px; align-items:center; }
        .controls input, .controls select, .form-grid input, .form-grid select { padding: 10px 12px; border-radius: 10px; border: 1px solid var(--border); }
        .controls button, .btn-primary, .btn-danger, .btn-secondary { border:none; border-radius:10px; padding: 10px 14px; cursor:pointer; }
        .btn-primary { background: #4f46e5; color:white; }
        .btn-secondary { background: #e2e8f0; color:#0f172a; }
        .btn-danger { background: #dc2626; color:white; }
        .alert { padding: 12px 14px; border-radius: 10px; margin-bottom: 20px; }
        .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #6ee7b7; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
        .layout { display:grid; grid-template-columns: 360px 1fr; gap: 20px; }
        .form-panel { padding: 22px; }
        .table-panel { padding: 18px; }
        .form-grid { display:grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .full { grid-column: 1 / -1; }
        label { display:block; margin-bottom: 7px; font-weight:600; font-size:0.88rem; }
        table { width:100%; border-collapse: collapse; }
        th, td { padding: 12px 10px; border-bottom: 1px solid var(--border); text-align:left; }
        th { color: var(--muted); font-size: 0.8rem; text-transform: uppercase; }
        .badge { display:inline-block; padding:6px 8px; border-radius: 999px; font-size: 0.74rem; font-weight:600; }
        .badge-active { background:#dcfce7; color:#166534; }
        .badge-probation { background:#fef3c7; color:#92400e; }
        .badge-inactive { background:#e5e7eb; color:#374151; }
        .actions { display:flex; gap:8px; }
        @media (max-width: 900px) { .layout { grid-template-columns: 1fr; } .form-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <script src="../JS/theme.js"></script>
    <nav class="navbar">
        <a class="brand" href="index.html">StudentHub</a>
        <div class="nav-links">
            <a href="index.html">Home</a>
            <a href="admin_dashboard.php">Admin</a>
            <a href="manage_students.php" class="active">Manage Students</a>
            <a href="logout.php">Logout</a>
        </div>
        <button class="theme-toggle" id="themeToggle" aria-label="Toggle dark mode">🌙</button>
    </nav>

    <div class="page-shell">
        <div class="topbar">
            <div>
                <h1 style="margin:0;">Student Management</h1>
                <p style="margin:8px 0 0; color: var(--muted);">Add, edit, delete, search and filter students.</p>
            </div>
            <a class="btn-secondary" href="admin_dashboard.php" style="text-decoration:none; display:inline-block;">Back to Admin</a>
        </div>

        <?php if ($message !== ''): ?>
            <div class="alert alert-<?= htmlspecialchars($messageType) ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <div class="layout">
            <div class="panel form-panel">
                <h2 style="margin-top: 0;">
                    <?= ($editStudent ? 'Edit Student' : 'Add Student') ?>
                </h2>

                <form method="POST" action="manage_students.php">
                    <input type="hidden" name="action" value="<?= $editStudent ? 'update' : 'add' ?>" />
                    <?php if ($editStudent): ?>
                        <input type="hidden" name="id" value="<?= (int) $editStudent['id'] ?>" />
                    <?php endif; ?>

                    <div class="form-grid">
                        <div class="full">
                            <label for="fullname">Full Name</label>
                            <input id="fullname" name="fullname" type="text" value="<?= htmlspecialchars($editValues['fullname']) ?>" required />
                        </div>

                        <div>
                            <label for="username">Username</label>
                            <input id="username" name="username" type="text" value="<?= htmlspecialchars($editValues['username']) ?>" required />
                        </div>

                        <div>
                            <label for="email">Email</label>
                            <input id="email" name="email" type="email" value="<?= htmlspecialchars($editValues['email']) ?>" required />
                        </div>

                        <div>
                            <label for="course">Course</label>
                            <input id="course" name="course" type="text" value="<?= htmlspecialchars($editValues['course']) ?>" required />
                        </div>

                        <div>
                            <label for="branch">Branch</label>
                            <select id="branch" name="branch">
                                <?php foreach (['Computer Engineering','Information Technology','Electronics & Communication','Mechanical Engineering','Civil Engineering'] as $branchOption): ?>
                                    <option value="<?= htmlspecialchars($branchOption) ?>" <?= ($editValues['branch'] === $branchOption ? 'selected' : '') ?>><?= htmlspecialchars($branchOption) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label for="semester">Semester</label>
                            <select id="semester" name="semester">
                                <?php for ($s = 1; $s <= 8; $s++): ?>
                                    <option value="<?= $s ?>" <?= ((int) $editValues['semester'] === $s ? 'selected' : '') ?>>Semester <?= $s ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div>
                            <label for="status">Status</label>
                            <select id="status" name="status">
                                <?php foreach (['Active','Probation','Inactive'] as $statusOption): ?>
                                    <option value="<?= htmlspecialchars($statusOption) ?>" <?= ($editValues['status'] === $statusOption ? 'selected' : '') ?>><?= htmlspecialchars($statusOption) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label for="password"><?= $editStudent ? 'New Password (optional)' : 'Password' ?></label>
                            <input id="password" name="password" type="password" placeholder="<?= $editStudent ? 'Leave blank to keep existing' : 'Default: pass123' ?>" <?= $editStudent ? '' : 'required' ?> />
                        </div>
                    </div>

                    <div style="margin-top:18px; display:flex; gap:10px;">
                        <button class="btn-primary" type="submit"><?= $editStudent ? 'Update Student' : 'Add Student' ?></button>
                        <?php if ($editStudent): ?>
                            <a class="btn-secondary" href="manage_students.php" style="text-decoration:none; display:inline-flex; align-items:center;">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <div class="panel table-panel">
                <div style="display:flex; justify-content:space-between; align-items:center; gap:14px; flex-wrap:wrap; margin-bottom:16px;">
                    <h2 style="margin:0;">Student Records</h2>
                    <span style="color: var(--muted); font-size:0.9rem;">Total: <?= count($students) ?></span>
                </div>

                <form method="GET" action="manage_students.php" class="controls" style="margin-bottom:18px;">
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search name, email, or course" />
                    <select name="branch">
                        <option value="">All Branches</option>
                        <?php foreach ($branchList as $branchOption): ?>
                            <option value="<?= htmlspecialchars($branchOption) ?>" <?= ($branchFilter === $branchOption ? 'selected' : '') ?>><?= htmlspecialchars($branchOption) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="course">
                        <option value="">All Courses</option>
                        <?php foreach ($courseList as $courseOption): ?>
                            <option value="<?= htmlspecialchars($courseOption) ?>" <?= ($courseFilter === $courseOption ? 'selected' : '') ?>><?= htmlspecialchars($courseOption) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="status">
                        <option value="">All Status</option>
                        <?php foreach (['Active','Probation','Inactive'] as $statusOption): ?>
                            <option value="<?= htmlspecialchars($statusOption) ?>" <?= ($statusFilter === $statusOption ? 'selected' : '') ?>><?= htmlspecialchars($statusOption) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn-primary" type="submit">Filter</button>
                    <a class="btn-secondary" href="manage_students.php" style="text-decoration:none; display:inline-flex; align-items:center;">Reset</a>
                </form>

                <div style="overflow-x:auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Course</th>
                                <th>Branch</th>
                                <th>Semester</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($students)): ?>
                                <tr>
                                    <td colspan="9" style="text-align:center; color: var(--muted); padding: 20px;">No students found matching the selected filters.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($students as $student): ?>
                                    <tr>
                                        <td><?= (int) $student['id'] ?></td>
                                        <td><?= htmlspecialchars($student['fullname']) ?></td>
                                        <td><?= htmlspecialchars($student['username']) ?></td>
                                        <td><?= htmlspecialchars($student['email']) ?></td>
                                        <td><?= htmlspecialchars($student['course']) ?></td>
                                        <td><?= htmlspecialchars($student['branch']) ?></td>
                                        <td><?= (int) $student['semester'] ?></td>
                                        <td>
                                            <span class="badge badge-<?= strtolower(str_replace(' ', '-', $student['status'])) ?>"><?= htmlspecialchars($student['status']) ?></span>
                                        </td>
                                        <td>
                                            <div class="actions">
                                                <a class="btn-secondary" style="text-decoration:none; display:inline-block;" href="manage_students.php?edit=<?= (int) $student['id'] ?>">Edit</a>
                                                <form method="POST" action="manage_students.php" onsubmit="return confirm('Delete this student record?');" style="display:inline;">
                                                    <input type="hidden" name="action" value="delete" />
                                                    <input type="hidden" name="id" value="<?= (int) $student['id'] ?>" />
                                                    <button class="btn-danger" type="submit">Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
