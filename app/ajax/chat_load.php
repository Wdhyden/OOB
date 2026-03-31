<?php
require_once __DIR__ . '/../lib/auth.php';
require_login();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../lib/security.php';

header('Content-Type: application/json');

$room_id = (int)($_GET['room_id'] ?? 0);
$after_id = (int)($_GET['after_id'] ?? 0);

if ($room_id <= 0) {
    echo json_encode(['ok' => false, 'error' => 'room_id required']);
    exit;
}

$sql = "SELECT m.id, m.content, m.message_type, m.youtube_url, m.reply_to_id,
               m.created_at,
               u.username, up.avatar_path, up.nickname_color
        FROM messages m
        JOIN users u ON u.id = m.user_id
        LEFT JOIN user_profiles up ON up.user_id = u.id
        WHERE m.room_id = ? AND (m.id > ?)
        ORDER BY m.id ASC
        LIMIT 100";

$stmt = $pdo->prepare($sql);
$stmt->execute([$room_id, $after_id]);
$rows = $stmt->fetchAll();

echo json_encode(['ok' => true, 'messages' => $rows]);
