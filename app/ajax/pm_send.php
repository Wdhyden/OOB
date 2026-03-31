<?php
// app/ajax/pm_send.php
require_once __DIR__ . '/../lib/auth.php';
require_login();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../lib/security.php';

header('Content-Type: application/json');

$user_id = current_user_id();
$conv_id = (int)($_POST['conv_id'] ?? 0);
$content = trim($_POST['content'] ?? '');
$type = $_POST['type'] ?? 'text';

if ($conv_id <= 0 || $content === '') {
    echo json_encode(['ok' => false, 'error' => 'Paramètres manquants']);
    exit;
}

// Vérifier accès conversation
$stmt = $pdo->prepare("SELECT u1_id, u2_id FROM private_conversations WHERE id = ?");
$stmt->execute([$conv_id]);
$conv = $stmt->fetch();
if (!$conv || ($conv['u1_id'] != $user_id && $conv['u2_id'] != $user_id)) {
    echo json_encode(['ok' => false, 'error' => 'Accès refusé']);
    exit;
}

$stmt = $pdo->prepare("INSERT INTO private_messages (conversation_id, sender_id, content, message_type)
                       VALUES (?, ?, ?, ?)");
$stmt->execute([$conv_id, $user_id, $content, $type]);

// Notification
$other_user = $conv['u1_id'] == $user_id ? $conv['u1_id'] : $conv['u2_id'];
$stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, payload_json) VALUES (?, 'new_pm', ?)");
$stmt->execute([$other_user, json_encode([
    'conversation_id' => $conv_id,
    'from_user_id' => $user_id,
    'message_preview' => substr($content, 0, 50)
])]);

echo json_encode(['ok' => true, 'id' => (int)$pdo->lastInsertId()]);
