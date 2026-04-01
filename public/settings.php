<?php
// public/settings.php
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/lib/auth.php';
require_once __DIR__ . '/../app/lib/ranks.php';
require_once __DIR__ . '/../app/lib/security.php';

if (session_status() === PHP_SESSION_NONE) session_start();
check_auth();

$user_id = $_SESSION['user_id'];
$success = "";
$error = "";

// 1. Récupération des données et des grades débloqués
try {
    $stmt = $pdo->prepare("SELECT u.username, p.* FROM users u JOIN user_profiles p ON u.id = p.user_id WHERE u.id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    $unlockedRanks = getUnlockedRanks($pdo, $user['level']);
} catch (Exception $e) { $error = "ERREUR_INITIALISATION"; }

// 2. Traitement global du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_color = $_POST['nickname_color'] ?? $user['nickname_color'];
    $selected_rank = $_POST['selected_rank'] ?? $user['rank'];
    $new_bio = htmlspecialchars($_POST['bio'] ?? '');
    $new_region = htmlspecialchars($_POST['region'] ?? '');
    $avatar_path = $user['avatar_path'];

    // --- Sécurité Grade ---
    $stmtCheck = $pdo->prepare("SELECT name FROM ranks WHERE name = ? AND min_lvl <= ?");
    $stmtCheck->execute([$selected_rank, $user['level']]);
    
    if ($stmtCheck->fetch()) {
        // --- Gestion Avatar ---
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $val_err = validate_uploaded_file($_FILES['avatar'], ['image/jpeg', 'image/png', 'image/webp'], 2 * 1024 * 1024);
            if (!$val_err) {
                $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
                $new_name = uniqid('avatar_', true) . '.' . $ext;
                $target = __DIR__ . '/../storage/uploads/avatars/';
                if (!is_dir($target)) mkdir($target, 0755, true);
                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $target . $new_name)) {
                    $avatar_path = 'storage/uploads/avatars/' . $new_name;
                }
            } else { $error = $val_err; }
        }

        // --- Mise à jour BDD ---
        if (empty($error)) {
            $upd = $pdo->prepare("UPDATE user_profiles SET nickname_color = ?, rank = ?, bio = ?, region = ?, avatar_path = ? WHERE user_id = ?");
            $upd->execute([$new_color, $selected_rank, $new_bio, $new_region, $avatar_path, $user_id]);
            $success = "UNITÉ_MISE_À_JOUR";
            header("Refresh:1"); 
        }
    } else { $error = "GRADE_NON_AUTORISÉ"; }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>CONFIG_SYSTEM // OOB_OS</title>
    <style>
        :root { --neon: <?= $user['nickname_color'] ?>; --bg: #030304; --panel: #080809; --border: #1a1a1c; }
        
        body {
            background: var(--bg); color: #888; font-family: 'Segoe UI', sans-serif;
            margin: 0; padding: 0; display: flex; flex-direction: column; height: 100vh;
        }

        /* Scanline Overlay */
        body::before {
            content: " "; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: linear-gradient(rgba(18, 16, 16, 0) 50%, rgba(0, 0, 0, 0.1) 50%), 
                        linear-gradient(90deg, rgba(255, 0, 0, 0.02), rgba(0, 255, 0, 0.01), rgba(0, 0, 255, 0.02));
            background-size: 100% 4px, 3px 100%; pointer-events: none; z-index: 100;
        }

        /* Top Bar */
        .top-bar {
            height: 40px; border-bottom: 1px solid var(--border);
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 25px; background: #000; z-index: 50;
        }
        .btn-nav { 
            color: #555; text-decoration: none; font-size: 0.65rem; font-weight: bold; 
            border: 1px solid var(--border); padding: 5px 12px; border-radius: 4px; transition: 0.2s; 
        }
        .btn-nav:hover { background: #fff; color: #000; border-color: #fff; }

        /* Layout */
        .main-layout {
            display: grid; grid-template-columns: 1fr; flex: 1; background: var(--border); overflow-y: auto; padding: 40px 0;
        }

        .config-container {
            width: 100%; max-width: 650px; margin: 0 auto; animation: fadeIn 0.4s ease;
        }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        .section { background: var(--panel); border: 1px solid var(--border); padding: 35px; margin-bottom: 1px; position: relative; }
        
        .section-header { 
            font-size: 0.6rem; color: #333; text-transform: uppercase; 
            letter-spacing: 2px; margin-bottom: 25px; display: flex; align-items: center; gap: 8px;
        }
        .section-header::before { content: ""; width: 4px; height: 4px; background: var(--neon); border-radius: 50%; }

        label { display: block; font-size: 0.55rem; color: #444; text-transform: uppercase; margin-bottom: 8px; font-weight: bold; }
        
        input, select, textarea { 
            width: 100%; background: #000; border: 1px solid var(--border); color: #eee; 
            padding: 12px; border-radius: 4px; margin-bottom: 20px; box-sizing: border-box; outline: none; 
            font-family: monospace; transition: 0.2s;
        }
        input:focus, select:focus, textarea:focus { border-color: var(--neon); }
        textarea { height: 100px; resize: none; }

        .avatar-group { display: flex; gap: 25px; align-items: center; margin-bottom: 25px; }
        .avatar-preview { width: 90px; height: 90px; border-radius: 4px; border: 1px solid var(--neon); object-fit: cover; filter: grayscale(0.5); }
        
        .btn-save { 
            width: 100%; background: var(--neon); color: #000; border: none; padding: 20px; 
            border-radius: 4px; font-weight: 900; cursor: pointer; text-transform: uppercase; 
            font-size: 0.8rem; letter-spacing: 2px; margin-top: 20px; transition: 0.3s;
        }
        .btn-save:hover { filter: brightness(1.2); box-shadow: 0 0 20px rgba(var(--neon), 0.2); }

        .status-alert { 
            padding: 15px; border-radius: 4px; font-size: 0.7rem; text-align: center; 
            margin-bottom: 20px; font-weight: bold; font-family: monospace; border: 1px solid;
        }
        .success { background: rgba(0, 255, 100, 0.02); color: #00ff64; border-color: #00ff64; }
        .error { background: rgba(255, 68, 68, 0.02); color: #ff4444; border-color: #ff4444; }
    </style>
</head>
<body>

    <div class="top-bar">
        <div style="font-size: 0.7rem; font-weight: 900; letter-spacing: 2px;">
            <span style="color:var(--neon)">?</span> OOB_OS // CONFIG_TERMINAL
        </div>
        <a href="profile.php" class="btn-nav">ESC_BACK</a>
    </div>

    <div class="main-layout">
        <div class="config-container">
            
            <?php if($success): ?> <div class="status-alert success">> <?= $success ?></div> <?php endif; ?>
            <?php if($error): ?> <div class="status-alert error">> <?= $error ?></div> <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                
                <div class="section">
                    <div class="section-header">VISUAL_IDENTITY_CORE</div>
                    <div class="avatar-group">
                        <img src="/<?= $user['avatar_path'] ?: 'assets/img/default-avatar.png' ?>" class="avatar-preview">
                        <div style="flex: 1;">
                            <label>Update_Avatar_File</label>
                            <input type="file" name="avatar" accept="image/*">
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div>
                            <label>Signal_Color</label>
                            <input type="color" name="nickname_color" value="<?= $user['nickname_color'] ?>" style="height: 45px; padding: 4px; cursor: pointer;">
                        </div>
                        <div>
                            <label>Clearance_Level</label>
                            <select name="selected_rank">
                                <?php foreach($unlockedRanks as $rk): ?>
                                    <option value="<?= $rk['name'] ?>" <?= ($user['rank'] == $rk['name']) ? 'selected' : '' ?>><?= strtoupper($rk['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="section">
                    <div class="section-header">DATA_FIELD_TRANSMISSION</div>
                    <label>Sector_Location</label>
                    <input type="text" name="region" value="<?= htmlspecialchars($user['region']) ?>" placeholder="LOC_UNKNOWN">
                    
                    <label>Biographical_Stream</label>
                    <textarea name="bio" placeholder="Saisir les données de l'unité..."><?= htmlspecialchars($user['bio']) ?></textarea>
                </div>

                <button type="submit" class="btn-save">Execute_Sync_Sequence</button>
            </form>
        </div>
    </div>

</body>
</html>