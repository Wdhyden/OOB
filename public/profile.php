<?php
// public/profile.php
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/lib/auth.php';
require_once __DIR__ . '/../app/lib/ranks.php';

if (session_status() === PHP_SESSION_NONE) session_start();
check_auth();

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : $_SESSION['user_id'];
$is_owner = ($user_id == $_SESSION['user_id']);

try {
    $stmt = $pdo->prepare("
        SELECT u.username, u.role, u.created_at, p.* FROM users u 
        JOIN user_profiles p ON u.id = p.user_id 
        WHERE u.id = ?
    ");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    if (!$user) die("ERREUR_SUJET_INTROUVABLE");

    // Statistiques de chat
    $stmtMsg = $pdo->prepare("SELECT COUNT(*) FROM global_chat WHERE user_id = ?");
    $stmtMsg->execute([$user_id]);
    $msg_count = $stmtMsg->fetchColumn();

    // Logique de progression XP
    $baseXp = 100;
    $currentLvl = $user['level'];
    $xpAccumulated = 0;
    for ($i = 1; $i < $currentLvl; $i++) { $xpAccumulated += $baseXp * pow(1.02, $i - 1); }
    $xpNeededForNext = $baseXp * pow(1.02, $currentLvl - 1);
    $relativeXp = $user['xp'] - $xpAccumulated;
    $progressPercent = ($xpNeededForNext > 0) ? min(100, ($relativeXp / $xpNeededForNext) * 100) : 100;

    // Données du rang
    $rk = getRankData($pdo, $user['rank']);
    $rankColor = $rk['color'] ?? '#ffffff';
    $nextRank = function_exists('levelsUntilNextRank') ? levelsUntilNextRank($pdo, $currentLvl) : null;

} catch (Exception $e) { die("ERREUR_SYSTÈME_CRITIQUE"); }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>ID_CARD // <?= htmlspecialchars($user['username']) ?></title>
    <style>
        :root {
            --neon: <?= $rankColor ?>;
            --bg: #050506;
            --surface: #0c0c0e;
            --border: rgba(255, 255, 255, 0.05);
        }

        body {
            background: var(--bg); color: #eee; font-family: 'Inter', sans-serif;
            margin: 0; display: flex; justify-content: center; align-items: center; min-height: 100vh;
            overflow-x: hidden;
        }

        /* Scanline effect */
        body::before {
            content: " "; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: linear-gradient(rgba(18, 16, 16, 0) 50%, rgba(0, 0, 0, 0.25) 50%), 
                        linear-gradient(90deg, rgba(255, 0, 0, 0.06), rgba(0, 255, 0, 0.02), rgba(0, 0, 255, 0.06));
            background-size: 100% 4px, 3px 100%; pointer-events: none; z-index: 10;
        }

        .profile-container {
            width: 100%; max-width: 850px; display: grid; grid-template-columns: 350px 1fr; gap: 30px;
            padding: 20px; animation: glitch-in 0.6s ease-out;
        }

        @keyframes glitch-in {
            from { opacity: 0; transform: scale(1.1) skewX(-5deg); }
            to { opacity: 1; transform: scale(1) skewX(0); }
        }

        /* --- COLONNE GAUCHE --- */
        .id-sidebar {
            background: var(--surface); border: 1px solid var(--border); border-radius: 30px;
            padding: 40px; text-align: center; position: relative; border-bottom: 5px solid var(--neon);
        }

        .avatar-box {
            width: 220px; height: 220px; margin: 0 auto 25px; border-radius: 25px;
            padding: 5px; border: 1px solid var(--border); position: relative;
        }

        .avatar-box img {
            width: 100%; height: 100%; object-fit: cover; border-radius: 20px;
            filter: grayscale(20%) contrast(1.1);
        }

        .avatar-box::after {
            content: "ONLINE"; position: absolute; bottom: -10px; right: 20px;
            background: var(--neon); color: #000; font-size: 0.6rem; font-weight: 900;
            padding: 2px 8px; border-radius: 4px; box-shadow: 0 0 15px var(--neon);
        }

        .username { font-size: 2.2rem; font-weight: 900; margin: 0; letter-spacing: -1px; }
        .rank-badge {
            display: inline-block; margin-top: 10px; padding: 5px 15px; 
            background: rgba(0,0,0,0.5); border: 1px solid var(--neon); color: var(--neon);
            font-size: 0.7rem; font-weight: 900; text-transform: uppercase; letter-spacing: 2px;
            border-radius: 4px; box-shadow: inset 0 0 10px rgba(var(--neon), 0.1);
        }

        /* --- COLONNE DROITE --- */
        .id-content { display: flex; flex-direction: column; gap: 20px; }

        .module {
            background: var(--surface); border: 1px solid var(--border);
            border-radius: 25px; padding: 30px; position: relative;
        }

        h2 { font-size: 0.65rem; color: #444; text-transform: uppercase; letter-spacing: 3px; margin: 0 0 20px 0; }

        /* Barre XP */
        .xp-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 12px; }
        .lvl-text { font-size: 3rem; font-weight: 900; line-height: 0.8; color: #fff; }
        .progress-track { width: 100%; height: 6px; background: #000; border-radius: 10px; overflow: hidden; }
        .progress-fill { height: 100%; background: var(--neon); width: <?= $progressPercent ?>%; box-shadow: 0 0 20px var(--neon); transition: 1.5s cubic-bezier(0.19, 1, 0.22, 1); }

        .stats-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-top: 25px; }
        .stat-card { background: rgba(255,255,255,0.02); padding: 15px; border-radius: 15px; border: 1px solid var(--border); text-align: center; }
        .stat-card b { display: block; font-size: 1.2rem; color: #fff; }
        .stat-card span { font-size: 0.55rem; color: #555; text-transform: uppercase; font-weight: bold; }

        .bio-text { font-size: 0.9rem; color: #aaa; line-height: 1.6; font-style: italic; margin: 0; }

        /* Navigation */
        .nav-footer { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 10px; }
        .btn-action {
            padding: 18px; border-radius: 15px; text-align: center; text-decoration: none;
            font-size: 0.75rem; font-weight: 900; text-transform: uppercase; letter-spacing: 1px;
            transition: 0.3s; border: 1px solid var(--border);
        }
        .btn-edit { background: var(--neon); color: #000; border: none; }
        .btn-chat { background: #111; color: #fff; }
        .btn-action:hover { transform: translateY(-3px); filter: brightness(1.2); }

        .stamp {
            position: absolute; top: 20px; right: 20px; font-size: 0.5rem; color: #222;
            text-transform: uppercase; transform: rotate(90deg); transform-origin: top right;
        }
    </style>
</head>
<body>

<div class="profile-container">
    
    <div class="id-sidebar">
        <div class="stamp">OUT_OF_BOUNDS_VERIFIED_SIGNAL</div>
        <div class="avatar-box">
            <img src="/<?= htmlspecialchars($user['avatar_path'] ?: 'assets/img/default-avatar.png') ?>" alt="AVATAR">
        </div>
        <h1 class="username"><?= htmlspecialchars($user['username']) ?></h1>
        <div class="rank-badge"><?= htmlspecialchars($user['rank']) ?></div>

        <div style="margin-top: 40px; text-align: left; padding: 20px; background: rgba(0,0,0,0.2); border-radius: 15px;">
            <div style="font-size: 0.55rem; color: #333; font-weight: bold; margin-bottom: 5px;">UNIT_LOCATION</div>
            <div style="font-size: 0.85rem; color: #888;"><?= htmlspecialchars($user['region'] ?: 'Secteur Inconnu') ?></div>
            
            <div style="font-size: 0.55rem; color: #333; font-weight: bold; margin-top: 15px; margin-bottom: 5px;">SIGNAL_INIT</div>
            <div style="font-size: 0.85rem; color: #888;"><?= date('d F Y', strtotime($user['created_at'])) ?></div>
        </div>
    </div>

    <div class="id-content">
        
        <div class="module">
            <h2>01 // PROGRESSION_ANALYSIS</h2>
            <div class="xp-header">
                <div>
                    <div style="font-size: 0.6rem; color: #444; font-weight: bold; margin-bottom: 5px;">ACCESS_LEVEL</div>
                    <div class="lvl-text"><?= $user['level'] ?></div>
                </div>
                <div style="text-align: right;">
                    <div style="font-size: 0.6rem; color: #444; font-weight: bold; margin-bottom: 5px;">STABILITY</div>
                    <div style="font-weight: 900; font-size: 1.5rem; color: var(--neon);"><?= round($progressPercent) ?>%</div>
                </div>
            </div>
            <div class="progress-track"><div class="progress-fill"></div></div>
            
            <div class="stats-row">
                <div class="stat-card"><b><?= number_format($user['xp']) ?></b><span>TOTAL_XP</span></div>
                <div class="stat-card"><b><?= $msg_count ?></b><span>MESSAGES</span></div>
                <div class="stat-card"><b><?= strtoupper($user['role']) ?></b><span>CLEARANCE</span></div>
            </div>
            
            <?php if($nextRank): ?>
            <div style="margin-top: 20px; font-size: 0.65rem; color: #333; text-align: center; letter-spacing: 1px;">
                PROCHAIN_OBJECTIF : GRADE <b><?= strtoupper($nextRank['name']) ?></b> AU NIVEAU <?= $nextRank['min_lvl'] ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="module" style="flex: 1;">
            <h2>02 // BIOGRAPHICAL_TRANSMISSION</h2>
            <p class="bio-text">
                "<?= nl2br(htmlspecialchars($user['bio'] ?: 'Aucune donnée biographique n\'a été transmise par cette unité.')) ?>"
            </p>
        </div>

        <div class="nav-footer">
            <a href="chat.php" class="btn-action btn-chat">FLUX_GLOBAL</a>
            <?php if($is_owner): ?>
                <a href="settings.php" class="btn-action btn-edit">MODIFIER_PROFIL</a>
            <?php endif; ?>
                <a href="dashboard.php" class="btn-action btn-chat">DASHBOARD</a>
        </div>
    </div>

</div>

</body>
</html>