<?php
// app/ajax/user_status.php
require_once __DIR__ . '/../lib/auth.php';
require_login();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$user_id = (int)($_GET['user_id'] ?? 0);
if ($user_id <= 0) {
    echo json_encode(['ok' => false]);
    exit;
}

$stmt = $pdo->prepare("SELECT username, online_status FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

echo json_encode([
    'ok' => true,
    'username' => $user['username'],
    'online_status' => $user['online_status']
]);
