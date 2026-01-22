<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

$role = (string)($_SESSION['user_role'] ?? 'user');

if ($role !== 'admin') {
  // 403 + redirect (scegli tu se vuoi solo redirect)
  http_response_code(403);
  header('Location: dashboard.php');
  exit;
}
