<?php
// public/drive.php
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/lib/auth.php';

check_auth();
check_validation($pdo); // Bloque l'accès si non validé

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT nickname_color FROM user_profiles WHERE user_id = ?");
$stmt->execute([$user_id]);
$my_neon = $stmt->fetch()['nickname_color'] ?? '#00f2ff';

// --- RÉCUPÉRATION DES FICHIERS ---
$stmt = $pdo->query("SELECT f.*, u.username FROM files f JOIN users u ON f.user_id = u.id ORDER BY f.uploaded_at DESC");
$all_files = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Cloud Drive // OOB</title>
    <style>
        :root { --neon: <?= $my_neon ?>; --bg: #050506; --border: rgba(255,255,255,0.1); }
        body { background: var(--bg); color: #eee; font-family: sans-serif; padding: 40px; }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 20px; margin-bottom: 30px; }
        .file-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; }
        .file-card { background: rgba(255,255,255,0.02); border: 1px solid var(--border); padding: 20px; border-radius: 15px; text-align: center; transition: 0.3s; }
        .file-card:hover { border-color: var(--neon); transform: translateY(-5px); }
        .btn-dl { display: inline-block; margin-top: 15px; background: var(--neon); color: #000; padding: 8px 15px; border-radius: 8px; text-decoration: none; font-weight: bold; font-size: 0.8rem; }
        .upload-zone { background: rgba(255,255,255,0.01); border: 2px dashed var(--border); padding: 30px; border-radius: 20px; text-align: center; margin-bottom: 40px; }
    </style>
</head>
<body>
    <div class="header">
        <a href="dashboard.php" style="color:#fff; text-decoration:none;">🏠 RETOUR</a>
        <h1 style="color:var(--neon); margin:0; font-size:1.2rem; letter-spacing:3px;">CLOUD_DRIVE</h1>
        <div style="width:70px;"></div>
    </div>

    <form class="upload-zone" action="upload_handler.php" method="POST" enctype="multipart/form-data">
        <input type="file" name="drive_file" required>
        <button type="submit" style="background:var(--neon); border:none; padding:10px 20px; border-radius:10px; font-weight:bold; cursor:pointer;">INJECTER</button>
    </form>

    <div class="file-grid">
        <?php foreach($all_files as $f): ?>
            <div class="file-card">
                <div style="font-size: 2rem; margin-bottom: 10px;">💾</div>
                <div style="font-size: 0.8rem; font-weight: bold; height: 35px; overflow: hidden;"><?= htmlspecialchars($f['original_name']) ?></div>
                <div style="font-size: 0.6rem; color: #555; margin-top: 5px;">BY <?= htmlspecialchars($f['username']) ?></div>
                <a href="uploads/drive/<?= $f['stored_name'] ?>" class="btn-dl" download>DOWNLOAD</a>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>