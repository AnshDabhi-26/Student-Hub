<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function studenthub_session_timeout(): int
{
    return 900;
}

function studenthub_remember_name(): string
{
    return 'studenthub_remember';
}

function studenthub_hash_token(string $token): string
{
    return hash('sha256', $token);
}

function studenthub_logout($conn = null): void
{
    if ($conn && $conn instanceof mysqli && !empty($_SESSION['user_id'])) {
        $stmt = $conn->prepare('UPDATE students SET remember_token = NULL WHERE id = ?');
        if ($stmt) {
            $stmt->bind_param('i', $_SESSION['user_id']);
            $stmt->execute();
            $stmt->close();
        }
    }

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();

    if (isset($_COOKIE[studenthub_remember_name()])) {
        setcookie(studenthub_remember_name(), '', time() - 3600, '/', '', false, true);
        unset($_COOKIE[studenthub_remember_name()]);
    }
}

function require_auth($conn = null): void
{
    if (empty($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }

    $lastActivity = $_SESSION['last_activity'] ?? time();
    if ((time() - (int) $lastActivity) > studenthub_session_timeout()) {
        studenthub_logout($conn);
        header('Location: login.php');
        exit;
    }

    $_SESSION['last_activity'] = time();
}

function require_role(array $allowedRoles, $conn = null): void
{
    require_auth($conn);

    $userRole = $_SESSION['user_role'] ?? 'student';
    if (!in_array($userRole, $allowedRoles, true)) {
        header('Location: student_dashboard.php');
        exit;
    }
}

function restore_remembered_session($conn): bool
{
    if (empty($_COOKIE[studenthub_remember_name()])) {
        return false;
    }

    $cookieValue = $_COOKIE[studenthub_remember_name()];
    $parts = explode(':', $cookieValue, 2);
    if (count($parts) !== 2) {
        return false;
    }

    [$token, $userId] = $parts;
    if (!is_numeric($userId)) {
        return false;
    }

    $userId = (int) $userId;
    $hashedToken = studenthub_hash_token($token);

    $stmt = $conn->prepare('SELECT id, fullname, username, email, password, role FROM students WHERE id = ? AND remember_token = ? LIMIT 1');
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('is', $userId, $hashedToken);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if (!$user) {
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['user_name'] = $user['fullname'];
    $_SESSION['user_role'] = $user['role'] ?? 'student';
    $_SESSION['last_activity'] = time();
    $_SESSION['session_id'] = session_id();

    return true;
}
