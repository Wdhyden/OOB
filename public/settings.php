<?php
// public/settings.php
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/lib/auth.php';
require_once __DIR__ . '/../app/lib/security.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$success = "";
$error = "";

// 1. Récupération des données actuelles du profil
try {
    $stmt = $pdo->prepare("SELECT * FROM user_profiles WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $profile = $stmt->fetch();
} catch (Exception $e) {
    $error = "Erreur de chargement du profil.";
}

// 2. Traitement du formulaire lors de l'envoi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name'] ?? '');
    $region     = trim($_POST['region'] ?? '');
    $bio        = trim($_POST['bio'] ?? '');
    $color      = $_POST['nickname_color'] ?? '#ff6fd8';
    
    $avatar_path = $profile['avatar_path'] ?? 'assets/img/default-avatar.png';

    // --- Gestion de l'Upload d'Avatar ---
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        // Validation via ta bibliothèque security.php
        $validation_error = validate_uploaded_file($_FILES['avatar'], ['image/jpeg', 'image/png', 'image/gif'], 2 * 1024 * 1024);
        
        if ($validation_error) {
            $error = $validation_error;
        } else {
            $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
            $new_filename = uniqid('avatar_', true) . '.' . $ext;
            
            // Chemin absolu pour le serveur
            $targetDir = __DIR__ . '/../storage/uploads/avatars/';
            
            // Créer le dossier s'il n'existe pas
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }
            
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $targetDir . $new_filename)) {
                $avatar_path = 'uploads/avatars/' . $new_filename;
            } else {
                $error = "Le serveur n'a pas pu déplacer le fichier vers le dossier final. Vérifiez les permissions (chmod).";
            }
        }
    } elseif ($_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
        // Si une erreur d'upload réelle a eu lieu (autre que "pas de fichier")
        $error = "Erreur d'upload PHP code : " . $_FILES['avatar']['error'];
    }

    // --- Mise à jour de la Base de Données ---
    if (empty($error)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE user_profiles 
                SET first_name = ?, last_name = ?, region = ?, bio = ?, nickname_color = ?, avatar_path = ?
                WHERE user_id = ?
            ");
            $stmt->execute([$first_name, $last_name, $region, $bio, $color, $avatar_path, $user_id]);
            
            $success = "Profil synchronisé avec succès !";
            // On recharge les données pour l'affichage immédiat
            $profile['first_name'] = $first_name;
            $profile['last_name'] = $last_name;
            $profile['region'] = $region;
            $profile['bio'] = $bio;
            $profile['nickname_color'] = $color;
            $profile['avatar_path'] = $avatar_path;
        } catch (Exception $e) {
            $error = "Erreur SQL : " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Out-Of-Bounds</title>
    <style>
        :root { --neon: <?= htmlspecialchars($profile['nickname_color'] ?? '#ff6fd8') ?>; }
        body { background: #0a0a0b; color: #fff; font-family: 'Segoe UI', sans-serif; display: flex; justify-content: center; padding: 40px 20px; margin: 0; }
        .card { background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(15px); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 20px; padding: 30px; width: 100%; max-width: 550px; }
        h1 { color: var(--neon); text-shadow: 0 0 10px var(--neon); text-align: center; margin-bottom: 30px; text-transform: uppercase; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; color: #888; font-size: 0.8rem; font-weight: bold; letter-spacing: 1px; }
        input, textarea, select { width: 100%; padding: 12px; background: rgba(0,0,0,0.4); border: 1px solid #333; border-radius: 10px; color: #fff; box-sizing: border-box; transition: 0.3s; }
        input:focus, textarea:focus { border-color: var(--neon); outline: none; box-shadow: 0 0 10px rgba(255,111,216,0.1); }
        .avatar-section { text-align: center; margin-bottom: 30px; }
        .avatar-preview { width: 120px; height: 120px; border-radius: 20px; border: 3px solid var(--neon); object-fit: cover; margin-bottom: 15px; box-shadow: 0 0 20px rgba(0,0,0,0.5); }
        .btn-submit { background: var(--neon); color: #000; border: none; padding: 15px; border-radius: 10px; font-weight: 800; cursor: pointer; width: 100%; transition: 0.3s; margin-top: 10px; text-transform: uppercase; }
        .btn-submit:hover { filter: brightness(1.2); box-shadow: 0 0 20px var(--neon); transform: translateY(-2px); }
        .alert { padding: 15px; border-radius: 10px; margin-bottom: 20px; font-weight: bold; text-align: center; }
        .alert-success { background: rgba(0, 255, 100, 0.1); border: 1px solid #00ff64; color: #00ff64; }
        .alert-error { background: rgba(255, 50, 50, 0.1); border: 1px solid #ff3232; color: #ff3232; }
        .back { display: block; margin-top: 25px; text-align: center; color: var(--neon); text-decoration: none; font-size: 0.9rem; font-weight: bold; }
    </style>
</head>
<body>

<div class="card">
    <h1>Paramètres</h1>

    <?php if($success): ?> <div class="alert alert-success"><?= $success ?></div> <?php endif; ?>
    <?php if($error): ?> <div class="alert alert-error"><?= $error ?></div> <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="avatar-section">
            <img src="<?= htmlspecialchars($profile['avatar_path'] ?? 'assets/img/default-avatar.png') ?>" class="avatar-preview" alt="Avatar">
            <div class="form-group">
                <label>NOUVEL AVATAR (JPG, PNG, GIF)</label>
                <input type="file" name="avatar" accept="image/*" style="border: none; background: transparent;">
            </div>
        </div>

        <div class="form-group">
            <label>COULEUR NÉON DU COMPTE</label>
            <input type="color" name="nickname_color" value="<?= htmlspecialchars($profile['nickname_color'] ?? '#ff6fd8') ?>" style="height: 50px; cursor: pointer;">
        </div>

        <div style="display: flex; gap: 15px;">
            <div class="form-group" style="flex:1;">
                <label>PRÉNOM</label>
                <input type="text" name="first_name" value="<?= htmlspecialchars($profile['first_name'] ?? '') ?>">
            </div>
            <div class="form-group" style="flex:1;">
                <label>NOM</label>
                <input type="text" name="last_name" value="<?= htmlspecialchars($profile['last_name'] ?? '') ?>">
            </div>
        </div>

        <div class="form-group">
            <label>RÉGION</label>
            <input type="text" name="region" value="<?= htmlspecialchars($profile['region'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label>BIO / TRANSMISSION</label>
            <textarea name="bio" rows="4"><?= htmlspecialchars($profile['bio'] ?? '') ?></textarea>
        </div>

        <button type="submit" class="btn-submit">Mettre à jour les données</button>
    </form>

    <a href="profile.php" class="back">← RETOUR AU PROFIL</a>
</div>

</body>
</html>