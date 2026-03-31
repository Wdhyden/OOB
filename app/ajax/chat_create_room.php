<?php
require_once __DIR__ . '/../lib/auth.php';
require_login();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$user_id = current_user_id();
$name = trim($_POST['name'] ?? '');
$is_private = (bool)$_POST['is_private'] ?? false;

if (strlen($name) < 3 || strlen($name) > 50) {
    echo json_encode(['ok' => false, 'error' => 'Nom invalide']);
    exit;
}

$pdo->beginTransaction();
try {
    // Créer salon
    $stmt = $pdo->prepare("INSERT INTO chat_rooms (name, description, is_private, created_by) VALUES (?, '', ?, ?)");
    $stmt->execute([$name, $is_private, $user_id]);
    $room_id = $pdo->lastInsertId();
    
    // Ajouter créateur comme admin
    $stmt = $pdo->prepare("INSERT INTO room_members (room_id, user_id, is_admin) VALUES (?, ?, 1)");
    $stmt->execute([$room_id, $user_id]);
    
    $pdo->commit();
    echo json_encode(['ok' => true, 'room_id' => $room_id]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['ok' => false, 'error' => 'Erreur création']);
}
