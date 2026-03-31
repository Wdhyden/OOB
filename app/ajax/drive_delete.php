<?php
// app/ajax/drive_delete.php
require_once __DIR__ . '/../lib/auth.php';
require_login();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$user_id = current_user_id();
$role = $_SESSION['role'];
$file_id = (int)($_POST['file_id'] ?? 0);

if ($file_id <= 0) {
    echo json_encode(['ok' => false, 'error' => 'ID fichier invalide']);
    exit;
}

$stmt = $pdo->prepare("SELECT user_id, stored_name FROM files WHERE id = ? AND context = 'drive'");
$stmt->execute([$file_id]);
$file = $stmt->fetch();

if (!$file) {
    echo json_encode(['ok' => false, 'error' => 'Fichier introuvable']);
    exit;
}

if ($role !== 'admin' && $file['user_id'] != $user_id) {
    echo json_encode(['ok' => false, 'error' => 'Non autorisé']);
    exit;
}

// Supprimer fichier physique
$file_path = __DIR__ . '/../../storage/uploads/drive/' . $file['stored_name'];
if (file_exists($file_path)) {
    unlink($file_path);
}

// Supprimer DB
$stmt = $pdo->prepare("DELETE FROM files WHERE id = ?");
$stmt->execute([$file_id]);

echo json_encode(['ok' => true]);
