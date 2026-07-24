<?php
require_once __DIR__ . '/../config.php';
Auth::platformLogout();
header('Location: /platform/login.php');
exit;
