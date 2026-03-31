<?php
// /var/www/html/community_hub/app/handlers/send_pm.php

// 1. Chemin direct vers la config (vu qu'on est dans app/handlers/)
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// 2. Sécurité Session
if (!isset($_SESSION['user_id'])) {
    // Redirection vers la racine web (qui est public/)
    header('Location: /login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['receiver_id'], $_POST['message'])) {
    
    $sender_id = $_SESSION['user_id'];
    $receiver_id = (int)$_POST['receiver_id'];
    $message = trim($_POST['message']);

    if (!empty($message) && $receiver_id > 0) {
        try {
            // Vérifie bien que ta variable dans database.php est $pdo
            $stmt = $pdo->prepare("INSERT INTO private_messages (sender_id, receiver_id, content) VALUES (?, ?, ?)");
            $stmt->execute([$sender_id, $receiver_id, $message]);
        } catch (Exception $e) {
            die("Erreur de transmission : " . $e->getMessage());
        }
    }

    // 3. Redirection propre
    // Puisque le domaine pointe sur public/, on utilise des chemins absolus
    header("Location: /private_messages.php?with=" . $receiver_id);
    exit;
} else {
    header('Location: /private_messages.php');
    exit;
}   