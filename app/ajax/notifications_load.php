<?php
// app/ajax/notifications_load.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../lib/auth.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// Sécurité : Vérification de session
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode([]);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // On récupère les notifications non lues (is_read = 0)
    $stmt = $pdo->prepare("
        SELECT id, message, link, created_at 
        FROM notifications 
        WHERE user_id = ? AND is_read = 0 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$user_id]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($notifications);

} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage()]);
}