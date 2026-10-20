<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

studenthub_logout($conn);
header('Location: login.php');
exit;
