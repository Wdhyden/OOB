<?php
// app/config/database.php
$host = 'localhost';
$dbname = 'community_app';
$db_user = 'root';  // ou votre utilisateur MySQL
$db_pass = '';      // mot de passe root MySQL

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    http_response_code(500);
    die('Erreur de connexion à la base de données');
}
?>