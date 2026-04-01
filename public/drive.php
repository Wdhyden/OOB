<?php
// public/drive.php
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/lib/auth.php';

if (session_status() === PHP_SESSION_NONE) session_start();
check_auth();
check_validation($pdo);

$user_id = $_SESSION['user_id'];

// Récupération de la couleur néon
$stmt = $pdo->prepare("SELECT nickname_color FROM user_profiles WHERE user_id = ?");
$stmt->execute([$user_id]);
$my_neon = $stmt->fetch()['nickname_color'] ?? '#00f2ff';

// Récupération des fichiers
$stmt = $pdo->query("SELECT f.*, u.username FROM files f JOIN users u ON f.user_id = u.id ORDER BY f.uploaded_at DESC");
$all_files = $stmt->fetchAll();

// Fonction pour formater la taille des fichiers
function formatSize($bytes) {
    if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
    if ($bytes >= 1048576) return number_format($bytes / 1048576, 2) . ' MB';
    return number_format($bytes / 1024, 2) . ' KB';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>STORAGE_UNIT // OOB_OS</title>
    <style>
        :root { --neon: <?= $my_neon ?>; --bg: #030304; --panel: #080809; --border: #1a1a1c; }
        
        body {
            background: var(--bg); color: #888; font-family: 'Segoe UI', sans-serif;
            margin: 0; padding: 0; display: flex; flex-direction: column; height: 100vh;
            overflow: hidden;
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

        /* Layout Grid */
        .main-layout {
            display: grid; grid-template-columns: 300px 1fr; flex: 1; gap: 1px; background: var(--border);
        }

        /* Sidebar : Upload */
        .sidebar { background: var(--panel); padding: 30px; display: flex; flex-direction: column; }
        .section-header { 
            font-size: 0.6rem; color: #333; text-transform: uppercase; 
            letter-spacing: 2px; margin-bottom: 25px; display: flex; align-items: center; gap: 8px;
        }
        .section-header::before { content: ""; width: 4px; height: 4px; background: var(--neon); border-radius: 50%; }

        .upload-box {
            border: 1px dashed var(--border); border-radius: 12px; padding: 25px;
            text-align: center; transition: 0.3s;
        }
        .upload-box:hover { border-color: var(--neon); background: rgba(255,255,255,0.01); }
        
        input[type="file"] { width: 100%; font-size: 0.7rem; color: #444; margin-bottom: 15px; }

        .btn-inject {
            width: 100%; background: var(--neon); color: #000; border: none; padding: 15px;
            border-radius: 10px; font-weight: 900; cursor: pointer; text-transform: uppercase;
            font-size: 0.7rem; letter-spacing: 1px; transition: 0.3s;
        }
        .btn-inject:hover { filter: brightness(1.2); box-shadow: 0 0 15px rgba(var(--neon), 0.3); }

        /* Explorer */
        .explorer { background: var(--panel); padding: 0; overflow-y: auto; }
        .explorer-header {
            display: grid; grid-template-columns: 1fr 120px 120px 120px;
            padding: 15px 30px; border-bottom: 1px solid var(--border);
            font-size: 0.6rem; font-weight: 900; color: #333; text-transform: uppercase; position: sticky; top: 0; background: var(--panel);
        }

        .file-row {
            display: grid; grid-template-columns: 1fr 120px 120px 120px;
            padding: 18px 30px; border-bottom: 1px solid rgba(255,255,255,0.01);
            align-items: center; transition: 0.2s; font-size: 0.8rem; color: #ccc;
            text-decoration: none;
        }
        .file-row:hover { background: rgba(255,255,255,0.02); color: #fff; }

        .file-info { display: flex; align-items: center; gap: 15px; }
        .file-icon { font-size: 1.2rem; opacity: 0.4; }
        .file-meta { font-family: monospace; font-size: 0.7rem; color: #444; }

        .btn-dl-mini {
            background: transparent; border: 1px solid #333; color: #888;
            padding: 5px 10px; border-radius: 4px; font-size: 0.6rem; font-weight: bold;
            text-decoration: none; transition: 0.2s; text-align: center;
        }
        .btn-dl-mini:hover { border-color: var(--neon); color: var(--neon); }

        /* Empty State */
        .empty { padding: 100px; text-align: center; opacity: 0.2; font-family: monospace; letter-spacing: 4px; }
    </style>
</head>
<body>

    <div class="top-bar">
        <div><span style="color:var(--neon)">?</span> OOB_CORP // STORAGE_UNIT</div>
        <div style="font-family:monospace; font-size:0.6rem; color:#333;">ENCRYPTION_LEVEL: AES-256</div>
        <a href="dashboard.php" class="btn-nav">? ESC_BACK</a>
    </div>

    <div class="main-layout">
        
        <div class="sidebar">
            <div class="section-header">DATA_INJECTION</div>
            <form action="upload_handler.php" method="POST" enctype="multipart/form-data">
                <div class="upload-box">
                    <div style="font-size: 2rem; margin-bottom: 15px;">??</div>
                    <input type="file" name="drive_file" required>
                    <button type="submit" class="btn-inject">Exécuter l'injection</button>
                </div>
            </form>

            <div style="margin-top: auto; padding: 20px; background: rgba(0,0,0,0.2); border-radius: 12px; border: 1px solid var(--border);">
                <div style="font-size: 0.55rem; color: #444; font-weight: 900; letter-spacing: 1px; margin-bottom: 10px;">SYSTEM_ADVISORY</div>
                <div style="font-size: 0.7rem; line-height: 1.4; color: #555;">
                    Tous les fichiers injectés sont scannés par le protocole OOB. Évitez les transmissions de signaux corrompus.
                </div>
            </div>
        </div>

        <div class="explorer">
            <div class="explorer-header">
                <div>IDENTIFIANT_FICHIER</div>
                <div>TAILLE</div>
                <div>PROPRIÉTAIRE</div>
                <div>ACTION</div>
            </div>

            <?php if(empty($all_files)): ?>
                <div class="empty">AUCUNE_DONNÉE_DÉTECTÉE</div>
            <?php else: ?>
                <?php foreach($all_files as $f): ?>
                    <div class="file-row">
                        <div class="file-info">
                            <span class="file-icon">??</span>
                            <div>
                                <div style="margin-bottom: 2px;"><?= htmlspecialchars($f['original_name']) ?></div>
                                <div class="file-meta"><?= date('d/m/Y H:i', strtotime($f['uploaded_at'])) ?></div>
                            </div>
                        </div>
                        <div class="file-meta"><?= formatSize($f['file_size'] ?? 0) ?></div>
                        <div class="file-meta" style="color:var(--neon)">@<?= htmlspecialchars($f['username']) ?></div>
                        <div>
                            <a href="uploads/drive/<?= $f['stored_name'] ?>" class="btn-dl-mini" download>DOWNLOAD</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>

</body>
</html>