<?php
/**
 * Practical 8: Secure MySQL Database Connection using PHP PDO
 * 
 * Features:
 * - DSN with utf8mb4 charset (prevents multibyte injection attacks)
 * - PDO::ERRMODE_EXCEPTION for robust database error handling
 * - PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC for clean array access
 * - PDO::ATTR_EMULATE_PREPARES => false for genuine server-side prepared statements
 */

$host    = "localhost";
$dbname  = "studenthub";
$user    = "root";
$pass    = "";
$charset = "utf8mb4";

$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

$pdo = null;
$db_connected = false;
$db_error = null;

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    $db_connected = true;
} catch (PDOException $e) {
    $db_connected = false;
    $db_error = $e->getMessage();
}
?>
