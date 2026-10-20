<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

require_auth($conn);

if (($_SESSION['user_role'] ?? 'student') === 'admin') {
    header('Location: admin_dashboard.php');
    exit;
}

header('Location: student_dashboard.php');
exit;
