<?php
declare(strict_types=1);

require __DIR__ . '/../config/db.php';

$userId = (int)($_SESSION['user_id'] ?? 0);
if ($userId <= 0) {
    header('Location: login.php');
    exit;
}

$stmt = $mysqli->prepare("SELECT user_id FROM profiles WHERE user_id = ? LIMIT 1");
$stmt->bind_param("i", $userId);
$stmt->execute();
$res = $stmt->get_result();
$ok = (bool)$res->fetch_assoc();
$stmt->close();

if (!$ok) {
    header('Location: edit_profile.php');
    exit;
}
