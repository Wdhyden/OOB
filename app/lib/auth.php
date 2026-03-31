<?php
// app/lib/auth.php
if (session_status() === PHP_SESSION_NONE) session_start();

function check_auth() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /login.php');
        exit;
    }
}

function check_admin() {
    check_auth();
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        header('Location: /dashboard.php');
        exit;
    }
}

/**
 * Vérifie si le compte est en attente de validation.
 * Si oui, affiche un écran de verrouillage.
 */
function check_validation($pdo) {
    if (!isset($_SESSION['user_id'])) return;

    $stmt = $pdo->prepare("SELECT is_pending FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if ($user && $user['is_pending'] == 1) {
        die("
        <div style='background:#050506; color:#ff4444; height:100vh; display:flex; align-items:center; justify-content:center; font-family:sans-serif; text-align:center;'>
            <div style='border:1px solid #ff4444; padding:40px; border-radius:20px; background:rgba(255,0,0,0.05);'>
                <h1 style='letter-spacing:5px; margin-bottom:10px;'>ACCÈS RESTREINT</h1>
                <p style='color:#888; margin-bottom:30px;'>Votre compte est en cours de vérification par l'administration.<br>L'accès au site est suspendu.</p>
                <a href='logout.php' style='color:#fff; text-decoration:none; border:1px solid #444; padding:10px 25px; border-radius:10px; font-weight:bold;'>QUITTER LE RÉSEAU</a>
            </div>
        </div>");
    }
}