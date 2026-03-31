<?php
// app/ajax/pm_start.php
require_once __DIR__ . '/../lib/auth.php';
require_login();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$user_id = current_user_id();
$target_user_id = (int)($_POST['target_user_id'] ?? 0);

if ($target_user_id <= 0 || $target_user_id == $user_id) {
    echo json_encode(['ok' => false, 'error' => 'Utilisateur cible invalide']);
    exit;
}

// Vérifier que la conversation n'existe pas déjà
$stmt = $pdo->prepare("SELECT id FROM private_conversations 
                       WHERE (user1_id = ? AND user2_id = ?) OR (user1_id = ? AND user2_id = ?)");
$stmt->execute([$user_id, $target_user_id, $target_user_id, $user_id]);
if ($stmt->fetch()) {
    echo json_encode(['ok' => false, 'error' => 'Conversation déjà existante']);
    exit;
}

// Créer conversation
$stmt = $pdo->prepare("INSERT INTO private_conversations (user1_id, user2_id) VALUES (?, ?)");
$stmt->execute([$user_id, $target_user_id]);

echo json_encode(['ok' => true, 'conv_id' => (int)$pdo->lastInsertId()]);
