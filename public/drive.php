<?php
// public/drive.php - TRI PAR DATE RÉCENT → ANCIEN
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

$upload_dir = __DIR__ . '/../storage/uploads/drive/';
$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $file_name = basename($_FILES['file']['name']);
    $target_file = $upload_dir . $file_name;
    
    if ($_FILES['file']['error'] === 0 && move_uploaded_file($_FILES['file']['tmp_name'], $target_file)) {
        $success = "✅ UPLOAD OK: $file_name";
    } else {
        $error = "❌ ÉCHEC upload";
    }
}

// 🔥 TRI PAR DATE : RÉCENTS EN HAUT
$files = [];
if (is_dir($upload_dir)) {
    foreach (glob($upload_dir . '*') as $file) {
        if (is_file($file)) {
            $files[filemtime($file)] = basename($file); // Clé = date modifiée
        }
    }
    // TRI DÉCROISSANT (récent → ancien)
    krsort($files);
}
?>
<!DOCTYPE html>
<html>
<head><title>Drive</title>
<style>
body{background:#0a0a0a;color:#f5f5f5;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;padding:30px;max-width:1000px;margin:auto;}
.upload-zone{background:#1f2020;border:3px dashed #ff6fd8;border-radius:15px;padding:40px;text-align:center;margin:30px 0;}
.success{border-color:#00ff88 !important;background:linear-gradient(135deg,#1a3a1a,#2a4a2a) !important;}
.error{border-color:#ff4444 !important;background:linear-gradient(135deg,#3a1a1a,#4a2a2a) !important;}
input[type=file]{width:100%;padding:20px;border-radius:10px;background:#2a2a2a;color:#f5f5f5;border:2px solid #444;font-size:16px;box-sizing:border-box;}
button{padding:20px 50px;background:linear-gradient(135deg,#ff6fd8,#ff85e4);color:#000;border:none;border-radius:30px;font-size:18px;font-weight:bold;cursor:pointer;transition:all 0.3s;}
button:hover{transform:translateY(-2px);box-shadow:0 10px 25px rgba(255,111,216,0.4);}
.files-grid{display:grid;gap:20px;margin-top:30px;}
.file-item{background:#1f2020;padding:25px;border-radius:15px;border-left:4px solid #ff6fd8;display:flex;justify-content:space-between;align-items:center;transition:all 0.3s;}
.file-item:hover{background:#2a2a2a;transform:translateX(5px);}
.file-info h4{margin:0;font-size:20px;color:#ff6fd8;}
.file-meta{font-size:14px;color:#aaa;}
.download-btn{padding:12px 25px;background:#00ff88;color:#000;border-radius:25px;text-decoration:none;font-weight:bold;transition:all 0.3s;}
.download-btn:hover{background:#00cc66;transform:scale(1.05);}
.new-file{background:#1a3a1a !important;border-left-color:#00ff88 !important;}
.stats{display:flex;justify-content:space-between;background:#1f2020;padding:20px;border-radius:12px;margin-bottom:20px;}
</style>
</head>
<body>
<h1 style="text-align:center;color:#ff6fd8;font-size:3em;margin-bottom:10px;">📁 DRIVE</h1>
<p style="text-align:center;color:#aaa;font-size:18px;">Fichiers triés par date <strong>(récent → ancien)</strong></p>

<!-- Stats -->
<div class="stats">
    <div>Total: <?= count($files) ?> fichiers</div>
    <div>Dossier: <?= is_writable($upload_dir) ? '✅ ÉCRITURE OK' : '❌ PROBLÈME' ?></div>
</div>

<!-- Message upload -->
<?php if ($success): ?>
<div class="upload-zone success new-file">
    <h2 style="color:#00ff88;margin:0;">🎉 <?= $success ?></h2>
    <a href="../storage/uploads/drive/<?= urlencode(basename($_FILES['file']['name'])) ?>" 
       download class="download-btn" style="display:inline-block;margin-top:15px;">⬇️ Télécharger maintenant</a>
</div>
<?php elseif ($error): ?>
<div class="upload-zone error">
    <h2 style="color:#ff4444;margin:0;"><?= $error ?></h2>
</div>
<?php endif; ?>

<!-- Upload -->
<div class="upload-zone">
    <form method="post" enctype="multipart/form-data">
        <input type="file" name="file" required accept="image/*,.pdf,.mp4,.mp3">
        <p style="margin:20px 0;font-size:14px;color:#aaa;">JPG, PNG, PDF, MP4, MP3 • Max 10Mo</p>
        <button>🚀 UPLOAD FICHIER</button>
    </form>
</div>

<!-- Liste fichiers TRIÉE PAR DATE RÉCENTE -->
<div class="files-grid">
    <?php if ($files): ?>
        <?php foreach ($files as $timestamp => $file_name): 
            $file_path = $upload_dir . $file_name;
            $size = filesize($file_path);
            $size_str = $size < 1024*1024 ? round($size/1024,1).' Ko' : round($size/(1024*1024),1).' Mo';
            $is_new = (time() - $timestamp) < 300; // Moins de 5min = "nouveau"
        ?>
        <div class="file-item <?= $is_new ? 'new-file' : '' ?>">
            <div class="file-info">
                <h4><?= htmlspecialchars($file_name) ?></h4>
                <div class="file-meta">
                    <?= date('d/m/Y H:i', $timestamp) ?> • 
                    <?= $size_str ?> 
                    <?= $is_new ? '✨ NOUVEAU' : '' ?>
                </div>
            </div>
            <a href="../storage/uploads/drive/<?= urlencode($file_name) ?>" 
               download class="download-btn" title="Télécharger <?= $file_name ?>">⬇️</a>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="file-item" style="justify-content:center;color:#aaa;">
            📭 Aucun fichier • <strong>Upload le premier !</strong>
        </div>
    <?php endif; ?>
</div>

<p style="text-align:center;color:#888;font-size:12px;margin-top:40px;">
    <strong>🔥 TRI AUTOMATIQUE :</strong> Fichiers les plus récents en haut • 
    Maj: <?= date('d/m H:i:s') ?>
</p>
</body>
</html>
