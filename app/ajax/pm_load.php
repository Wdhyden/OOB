<?php
// app/ajax/pm_load.php
require_once __DIR__ . '/../lib/auth.php';
require_login();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$user_id = current_user_id();
$conv_id = (int)($_GET['conv_id'] ?? 0);
$after_id = (int)($_GET['after_id'] ?? 0);

if ($conv_id <= 0) {
    echo json_encode(['ok' => false, 'error' => 'conv_id required']);
    exit;
}

// Vérifier que l'utilisateur appartient à cette conversation
$stmt = $pdo->prepare("SELECT u1_id, u2_id FROM private_conversations WHERE id = ?");
$stmt->execute([$conv_id]);
$conv = $stmt->fetch();
if (!$conv || ($conv['u1_id'] != $user_id && $conv['u2_id'] != $user_id)) {
    echo json_encode(['ok' => false, 'error' => 'Accès refusé']);
    exit;
}

// Marquer comme lus
$pdo->prepare("UPDATE private_messages SET read_at = NOW() 
               WHERE conversation_id = ? AND sender_id != ? AND read_at IS NULL")
    ->execute([$conv_id, $user_id]);

// Charger messages
$sql = "SELECT pm.id, pm.content, pm.message_type, pm.youtube_url, pm.created_at,
               u.username, up.avatar_path, up.nickname_color,
               CASE WHEN pm.sender_id = ? THEN 'sent' ELSE 'received' END as direction
        FROM private_messages pm
        JOIN users u ON u.id = pm.sender_id
        LEFT JOIN user_profiles up ON up.user_id = u.id
        WHERE pm.conversation_id = ? AND (pm.id > ?)
        ORDER BY pm.id ASC
        LIMIT 100";

$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id, $conv_id, $after_id]);
$messages = $stmt->fetchAll();

echo json_encode([
    'ok' => true, 
    'messages' => $messages,
    'other_user_id' => $conv['u1_id'] == $user_id ? $conv['u2_id'] : $conv['u1_id']
]);
