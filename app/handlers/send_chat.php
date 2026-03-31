<?php
// app/handlers/send_chat.php
require_once __DIR__ . '/../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) { header('Location: /login.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $user_id = $_SESSION['user_id'];
    $message = trim($_POST['message']);

    if (!empty($message)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO global_chat (user_id, message) VALUES (?, ?)");
            $stmt->execute([$user_id, $message]);
        } catch (Exception $e) {
            error_log($e->getMessage());
        }
    }
}

header('Location: /chat.php');
exit;