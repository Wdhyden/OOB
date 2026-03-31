<?php
// public/error_test.php - DIAGNOSTIC
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "PHP OK ! Version: " . PHP_VERSION . "<br>";
echo "Chemins: " . __DIR__ . "<br>";

// Test require
try {
    require_once __DIR__ . '/../app/lib/auth.php';
    echo "✅ auth.php chargé<br>";
} catch (Exception $e) {
    echo "❌ ERREUR auth.php: " . $e->getMessage() . "<br>";
}

try {
    require_once __DIR__ . '/../app/config/database.php';
    echo "✅ database.php chargé<br>";
} catch (Exception $e) {
    echo "❌ ERREUR database.php: " . $e->getMessage() . "<br>";
}
?>
