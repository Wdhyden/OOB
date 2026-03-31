<?php
// public/mp_debug.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "=== DEBUG PRIVATE MESSAGES ===<br><br>";

session_start();
echo "✅ Session OK: " . ($_SESSION['user_id'] ?? 'NON') . "<br>";

try {
    require_once __DIR__ . '/../app/config/database.php';
    echo "✅ DB OK<br>";
    
    $pdo->query("SELECT 1");
    echo "✅ Connexion DB fonctionnelle<br>";
} catch (Exception $e) {
    echo "❌ ERREUR DB: " . $e->getMessage() . "<br>";
}

echo "<br><a href='/private_messages.php'>→ Tester MP</a>";
?>
