<?php
// public/dashboard.php
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/lib/auth.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// Vérification de connexion via notre librairie auth
check_auth();

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? 'user';

// 1. RÉCUPÉRATION DES DONNÉES DU PROFIL ET STATS
try {
    // Infos utilisateur (Pseudo et Couleur)
    $stmt = $pdo->prepare("
        SELECT u.username, p.nickname_color 
        FROM users u 
        JOIN user_profiles p ON u.id = p.user_id 
        WHERE u.id = ?
    ");
    $stmt->execute([$user_id]);
    $me = $stmt->fetch();
    $my_neon = $me['nickname_color'] ?? '#00f2ff';

    // Stats pour les widgets
    $countUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $countFiles = $pdo->query("SELECT COUNT(*) FROM files WHERE context = 'drive'")->fetchColumn();
    
    // Aperçu du Chat Global (3 derniers messages)
    $lastMessages = $pdo->query("
        SELECT c.message, u.username, p.nickname_color 
        FROM global_chat c 
        JOIN users u ON c.user_id = u.id 
        JOIN user_profiles p ON u.id = p.user_id 
        ORDER BY c.created_at DESC LIMIT 3
    ")->fetchAll();

} catch (Exception $e) {
    error_log($e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>OOB // DASHBOARD</title>
    <style>
        :root {
            --neon: <?= $my_neon ?>;
            --bg: #050506;
            --card-bg: rgba(255, 255, 255, 0.02);
            --border: rgba(255, 255, 255, 0.08);
        }

        body {
            background: var(--bg);
            color: #fff;
            font-family: 'Segoe UI', system-ui, sans-serif;
            margin: 0;
            padding: 40px;
            display: flex;
            justify-content: center;
            overflow-x: hidden;
        }

        .container {
            width: 100%;
            max-width: 1200px;
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        /* Animation Dégradé de Bleus */
        @keyframes blue-pulse {
            0% { color: #00d4ff; text-shadow: 0 0 8px rgba(0, 212, 255, 0.6); } 
            50% { color: #0044ff; text-shadow: 0 0 8px rgba(0, 68, 255, 0.6); }  
            100% { color: #00d4ff; text-shadow: 0 0 8px rgba(0, 212, 255, 0.6); }
        }

        .vip-glow { 
            animation: blue-pulse 3s ease-in-out infinite; 
            font-weight: bold;
        }

        /* --- Header --- */
        header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 50px;
            border-bottom: 1px solid var(--border);
            padding-bottom: 25px;
        }

        .sys-info { font-size: 0.65rem; letter-spacing: 4px; color: #444; text-transform: uppercase; margin-bottom: 5px; }
        .welcome { font-size: 2.2rem; font-weight: 900; letter-spacing: -1px; }
        .welcome span { color: var(--neon); text-shadow: 0 0 15px var(--neon); }

        /* --- Stats Widgets --- */
        .stats-bar {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            padding: 20px;
            border-radius: 15px;
            text-align: center;
        }

        .stat-card span { display: block; font-size: 0.6rem; color: #555; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px; }
        .stat-card strong { font-size: 1.6rem; color: var(--neon); font-family: monospace; }

        /* --- Menu Grid --- */
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
        }

        .nav-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            padding: 35px;
            border-radius: 24px;
            text-decoration: none;
            color: #fff;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .nav-card:hover {
            border-color: var(--neon);
            background: rgba(255, 255, 255, 0.05);
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.4);
        }

        .nav-card h2 { margin: 0 0 10px 0; color: var(--neon); font-size: 1.3rem; letter-spacing: 1px; }
        .nav-card p { margin: 0; color: #777; font-size: 0.85rem; line-height: 1.5; }
        .icon { font-size: 2.2rem; margin-bottom: 20px; display: block; }

        /* --- Admin Card Specific --- */
        .admin-card { border-color: rgba(255, 0, 0, 0.2); }
        .admin-card:hover { border-color: #ff4444; box-shadow: 0 0 20px rgba(255, 68, 68, 0.2); }

        /* --- Chat Stream Preview --- */
        .chat-preview {
            grid-column: 1 / -1;
            background: rgba(0,0,0,0.2);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 30px;
            margin-top: 10px;
        }

        .chat-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .chat-line { padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,0.03); font-size: 0.9rem; display: flex; gap: 10px; }
        .chat-line b { min-width: 100px; }

        .btn-logout {
            color: #ff4444;
            text-decoration: none;
            font-size: 0.7rem;
            font-weight: 900;
            border: 1px solid rgba(255, 68, 68, 0.3);
            padding: 8px 20px;
            border-radius: 10px;
            transition: 0.3s;
            letter-spacing: 1px;
        }

        .btn-logout:hover { background: #ff4444; color: #fff; box-shadow: 0 0 15px rgba(255, 68, 68, 0.4); }

        /* --- Style des Notifications --- */
        #notif-area {
            animation: fadeIn 0.4s ease-out;
        }
        .notif-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            font-size: 0.85rem;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .notif-item:last-child { border-bottom: none; }

        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-thumb { background: var(--border); border-radius: 10px; }
    </style>
</head>
<body>

<div class="container">
    <header>
        <div>
            <div class="sys-info">Network Status: Stable // Node_01</div>
            <div class="welcome">BIENVENUE, <span><?= htmlspecialchars($me['username']) ?></span></div>
        </div>
        <a href="logout.php" class="btn-logout">TERMINER LA SESSION</a>
    </header>

    <div class="stats-bar">
        <div class="stat-card"><span>Membres Actifs</span><strong><?= $countUsers ?></strong></div>
        <div class="stat-card"><span>Archives Cloud</span><strong><?= $countFiles ?></strong></div>
        <div class="stat-card"><span>PHP Version</span><strong><?= PHP_VERSION ?></strong></div>
    </div>

    <div id="notif-area" style="width: 100%; margin-bottom: 30px; display: none;">
        <div style="background: rgba(255,255,255,0.03); border: 1px solid var(--neon); border-radius: 18px; padding: 20px; backdrop-filter: blur(10px);">
            <div style="font-size: 0.65rem; color: var(--neon); letter-spacing: 2px; margin-bottom: 10px; font-weight: bold; text-transform: uppercase;">
                Alertes_Système
            </div>
            <div id="notif-list"></div>
        </div>
    </div>

    <div class="menu-grid">
        <?php if($user_role === 'admin'): ?>
        <a href="admin.php" class="nav-card admin-card">
            <span class="icon">⚙️</span>
            <h2 style="color: #ff4444;">Admin PANEL</h2>
            <p>Contrôle total : gestion des utilisateurs, modération et monitoring système.</p>
        </a>
        <?php endif; ?>

        <a href="chat.php" class="nav-card">
            <span class="icon">🛰️</span>
            <h2>Global Chat</h2>
            <p>Rejoignez le flux de communication principal du réseau Out of Bounds.</p>
        </a>

        <a href="drive.php" class="nav-card">
            <span class="icon">💾</span>
            <h2>Drive</h2>
            <p>Stockage décentralisé. Partagez et téléchargez vos fichiers en toute sécurité.</p>
        </a>

        <a href="private_messages.php" class="nav-card">
            <span class="icon">📟</span>
            <h2>Messages Privés</h2>
            <p>Messagerie directe et encryptée de point à point.</p>
        </a>

        <a href="profile.php" class="nav-card">
            <span class="icon">🛡️</span>
            <h2>Profil</h2>
            <p>Modifiez votre signature visuelle, votre avatar et vos paramètres.</p>
        </a>

        <div class="chat-preview">
            <div class="chat-header">
                <h3 style="margin: 0; font-size: 0.75rem; color: #444; letter-spacing: 2px;">DERNIÈRES TRANSMISSIONS</h3>
                <a href="chat.php" style="color: var(--neon); text-decoration: none; font-size: 0.75rem; font-weight: bold;">VOIR LE FLUX →</a>
            </div>
            <?php if(empty($lastMessages)): ?>
                <p style="color: #333; font-size: 0.8rem; text-align: center;">Aucun signal détecté sur le canal global.</p>
            <?php else: ?>
                <?php foreach($lastMessages as $m): ?>
                    <div class="chat-line">
                        <b style="color: <?= $m['nickname_color'] ?>"><?= htmlspecialchars($m['username']) ?>:</b>
                        <span style="color: #aaa;"><?= htmlspecialchars($m['message']) ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function syncNotifications() {
    // Note: On utilise le chemin relatif correct vers ton fichier AJAX
    fetch('../app/ajax/notifications_load.php')
    .then(r => r.json())
    .then(data => {
        const area = document.getElementById('notif-area');
        const list = document.getElementById('notif-list');
        if (data && data.length > 0) {
            area.style.display = 'block';
            list.innerHTML = data.map(n => `
                <div class="notif-item">
                    <span>• ${n.message}</span>
                    <a href="${n.link}" style="color:var(--neon); text-decoration:none; font-weight:bold;">[VOIR]</a>
                </div>
            `).join('');
        } else {
            area.style.display = 'none';
        }
    })
    .catch(err => console.log("Notification sync standby..."));
}

// Vérification toutes les 20 secondes
setInterval(syncNotifications, 20000);
syncNotifications();
</script>

</body>
</html>