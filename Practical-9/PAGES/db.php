<?php
/**
 * Practical 9: Database Connection Configuration using PHP MySQLi & PDO
 * 
 * Requirements:
 * - MySQLi connection for Practical 9 (Secure User Registration & Prepared Statements)
 * - UTF-8 character encoding (utf8mb4) to prevent multibyte injection attacks
 * - Graceful error handling and status indicators
 */

$host    = "localhost";
$dbname  = "studenthub";
$user    = "root";
$pass    = "";
$charset = "utf8mb4";
$port    = 3306;

// ==========================================
// 1. MySQLi Connection (Primary for Practical 9)
// ==========================================
$conn = null;
$mysqli = null;
$mysqli_connected = false;
$mysqli_error = null;

try {
    // Attempt object-oriented MySQLi connection
    $conn = @new mysqli($host, $user, $pass, $dbname, $port);

    if ($conn->connect_error) {
        $mysqli_connected = false;
        $mysqli_error = $conn->connect_error;
        $conn = null;
    } else {
        $conn->set_charset($charset);
        $mysqli = $conn; // Alias for flexibility
        $mysqli_connected = true;
    }
} catch (Exception $e) {
    $mysqli_connected = false;
    $mysqli_error = $e->getMessage();
    $conn = null;
}

// ==========================================
// 2. PDO Connection (Auxiliary support)
// ==========================================
$pdo = null;
$pdo_connected = false;
$pdo_error = null;

try {
    $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, $user, $pass, $options);
    $pdo_connected = true;
} catch (PDOException $e) {
    $pdo_connected = false;
    $pdo_error = $e->getMessage();
}

// Global connection state
$db_connected = $mysqli_connected || $pdo_connected;
$db_error = $mysqli_error ?? $pdo_error;
?>
