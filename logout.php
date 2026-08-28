<?php
define('ROOT_URL', '');
require_once __DIR__ . '/includes/auth.php';

$_SESSION = [];
session_destroy();
header('Location: login.php?logout=1');
exit;
