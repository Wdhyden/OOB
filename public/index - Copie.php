<?php
// public/index.php
require_once __DIR__ . '/app/lib/auth.php';

secure_session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: /dashboard.php');
} else {
    header('Location: /login.php');
}
exit;
?>
