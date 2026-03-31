<?php
// app/lib/auth.php - VERSION PROPRE
function require_login() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['user_id'])) {
        header('Location: /public/login.php');
        exit;
    }
}
?>