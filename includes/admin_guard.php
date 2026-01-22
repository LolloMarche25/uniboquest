<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

$role = (string)($_SESSION['user_role'] ?? 'user');
if ($role !== 'admin') {
  header('Location: dashboard.php');
  exit;
}
