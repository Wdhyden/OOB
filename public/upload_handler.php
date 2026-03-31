<?php
// upload_handler.php
require_once 'app/config/database.php';
require_once 'app/lib/auth.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// Sécurité : On vérifie que l'utilisateur est loggé et validé
check_auth();
check_validation($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['drive_file'])) {
    $user_id = $_SESSION['user_id'];
    $file = $_FILES['drive_file'];

    // 1. Chemin du dossier d'upload (Relatif à la racine)
    $upload_dir = 'uploads/drive/';
    
    // Création automatique du dossier si inexistant
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $original_name = basename($file['name']);
    $file_extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
    
    // 2. Sécurité : Liste noire des extensions dangereuses
    $forbidden = ['php', 'phtml', 'php5', 'exe', 'bat', 'js', 'sh'];
    if (in_array($file_extension, $forbidden)) {
        die("ERREUR : Injection de script détectée. Extension interdite.");
    }

    // 3. Renommage pour éviter les conflits et caractères spéciaux
    $stored_name = bin2hex(random_bytes(10)) . '_' . time() . '.' . $file_extension;
    $target_path = $upload_dir . $stored_name;

    // 4. Transfert du fichier temporaire vers le stockage définitif
    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        try {
            // 5. Enregistrement des métadonnées en BDD
            $stmt = $pdo->prepare("
                INSERT INTO files (user_id, original_name, stored_name, file_type, context) 
                VALUES (?, ?, ?, ?, 'drive')
            ");
            $stmt->execute([
                $user_id, 
                $original_name, 
                $stored_name, 
                $file['type']
            ]);

            // Retour au drive avec un signal de succès
            header('Location: drive.php?status=success');
            exit;

        } catch (Exception $e) {
            // En cas d'erreur BDD, on supprime le fichier physiquement pour rester propre
            unlink($target_path);
            die("Erreur de synchronisation BDD : " . $e->getMessage());
        }
    } else {
        die("Erreur : Échec du transfert vers le serveur.");
    }
} else {
    header('Location: drive.php');
    exit;
}