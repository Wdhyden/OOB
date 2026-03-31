<?php
// public/profile.php
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/lib/auth.php';

if (session_status() === PHP_SESSION_NONE) session_start();
check_auth();

// On récupère l'ID : soit celui dans l'URL, soit le nôtre
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : $_SESSION['user_id'];

try {
    // Récupération des infos utilisateur + profil
    $stmt = $pdo->prepare("
        SELECT u.username, u.role, u.created_at, p.* FROM users u 
        JOIN user_profiles p ON u.id = p.user_id 
        WHERE u.id = ?
    ");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        die("Erreur : Sujet introuvable dans la base de données.");
    }

    // Récupération du nombre réel de messages envoyés
    $stmtMsg = $pdo->prepare("SELECT COUNT(*) FROM global_chat WHERE user_id = ?");
    $stmtMsg->execute([$user_id]);
    $real_message_count = $stmtMsg->fetchColumn();

} catch (Exception $e) {
    die("Erreur système : " . $e->getMessage());
}

$is_owner = ($user_id == $_SESSION['user_id']);
$role = $user['role'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil de <?= htmlspecialchars($user['username']) ?> - OOB</title>
    <style>
        :root {
            --neon: <?= htmlspecialchars($user['nickname_color'] ?? '#00f2ff') ?>;
            --bg: #050506;
            --card: rgba(255, 255, 255, 0.02);
        }

        /* --- Animations de Rôles --- */
        @keyframes admin-rainbow {
            0% { border-color: #ff0000; box-shadow: 0 0 20px rgba(255,0,0,0.4); }
            50% { border-color: #00f2ff; box-shadow: 0 0 20px rgba(0,242,255,0.4); }
            100% { border-color: #ff0000; box-shadow: 0 0 20px rgba(255,0,0,0.4); }
        }

        @keyframes vip-blue {
            0% { border-color: #00d4ff; box-shadow: 0 0 15px rgba(0, 212, 255, 0.3); }
            50% { border-color: #0044ff; box-shadow: 0 0 15px rgba(0, 68, 255, 0.3); }
            100% { border-color: #00d4ff; box-shadow: 0 0 15px rgba(0, 212, 255, 0.3); }
        }

        body {
            background: var(--bg);
            color: #eee;
            font-family: 'Segoe UI', system-ui, sans-serif;
            margin: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 40px 20px;
            min-height: 100vh;
        }

        .profile-container {
            width: 100%;
            max-width: 700px;
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        .nav-header {
            width: 100%;
            max-width: 700px;
            margin-bottom: 20px;
        }

        .btn-back {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #888;
            padding: 10px 20px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: bold;
            font-size: 0.8rem;
            transition: 0.3s;
        }

        .btn-back:hover { border-color: var(--neon); color: var(--neon); box-shadow: 0 0 15px var(--neon); }

        /* --- Header / Card --- */
        .profile-header {
            height: 120px;
            background: linear-gradient(to bottom, var(--neon), transparent);
            border-radius: 24px 24px 0 0;
            opacity: 0.3;
            <?php if($role === 'admin') echo "animation: admin-rainbow 3s linear infinite;"; ?>
        }

        .profile-main-card {
            background: var(--card);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.05);
            border-radius: 0 0 24px 24px;
            padding: 0 40px 40px 40px;
            position: relative;
        }

        .avatar-container {
            position: absolute;
            top: -60px;
            left: 40px;
        }

        .profile-avatar {
            width: 130px;
            height: 130px;
            border-radius: 25px;
            border: 4px solid var(--bg);
            object-fit: cover;
            background: #111;
            /* Animation de bordure selon le rôle */
            <?php 
                if($role === 'admin') echo "animation: admin-rainbow 3s linear infinite; border-width: 4px;";
                elseif($role === 'vip') echo "animation: vip-blue 3s ease-in-out infinite; border-width: 4px;";
                else echo "border-color: var(--neon);";
            ?>
        }

        .profile-info { padding-top: 80px; }

        .username {
            font-size: 2.2rem;
            font-weight: 900;
            margin: 0;
            color: #fff;
            letter-spacing: -1px;
        }

        .role-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 8px;
            font-size: 0.7rem;
            font-weight: 800;
            text-transform: uppercase;
            background: rgba(255,255,255,0.05);
            margin-top: 8px;
            letter-spacing: 1px;
        }
        .admin-role { color: #ff4444; border: 1px solid #ff4444; }
        .vip-role { color: #00d4ff; border: 1px solid #00d4ff; }

        .btn-edit {
            position: absolute;
            right: 40px;
            top: 25px;
            background: transparent;
            border: 1px solid #333;
            color: #888;
            padding: 8px 16px;
            border-radius: 10px;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.8rem;
            font-weight: bold;
            transition: 0.3s;
        }
        .btn-edit:hover { border-color: var(--neon); color: #fff; }

        /* --- Stats --- */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-top: 35px;
        }

        .stat-item {
            background: rgba(0,0,0,0.3);
            padding: 15px;
            border-radius: 15px;
            text-align: center;
            border: 1px solid rgba(255,255,255,0.02);
        }

        .stat-value { display: block; font-size: 1.2rem; font-weight: 800; color: var(--neon); }
        .stat-label { font-size: 0.6rem; color: #555; text-transform: uppercase; letter-spacing: 1px; }

        .bio-section {
            margin-top: 30px;
            padding: 20px;
            background: rgba(0,0,0,0.2);
            border-radius: 15px;
            border: 1px solid rgba(255,255,255,0.02);
        }

        .section-title {
            font-size: 0.7rem;
            font-weight: 900;
            color: #444;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .bio-text { font-size: 0.9rem; color: #aaa; line-height: 1.6; margin: 0; }
    </style>
</head>
<body>

<div class="nav-header">
    <a href="dashboard.php" class="btn-back">← RETOUR</a>
</div>

<div class="profile-container">
    <div class="profile-header"></div>
    
    <div class="profile-main-card">
        <?php if($is_owner): ?>
            <a href="settings.php" class="btn-edit">MODIFIER PROFIL</a>
        <?php endif; ?>

        <div class="avatar-container">
            <img src="/<?= htmlspecialchars($user['avatar_path'] ?: 'assets/img/default-avatar.png') ?>" alt="Avatar" class="profile-avatar">
        </div>

        <div class="profile-info">
            <h1 class="username"><?= htmlspecialchars($user['username']) ?></h1>
            <div class="role-badge <?= ($role === 'admin') ? 'admin-role' : (($role === 'vip') ? 'vip-role' : '') ?>">
                <?= htmlspecialchars($role) ?>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-item">
                <span class="stat-value"><?= $real_message_count ?></span>
                <span class="stat-label">Messages</span>
            </div>
            <div class="stat-item">
                <span class="stat-value"><?= date('m/y', strtotime($user['created_at'])) ?></span>
                <span class="stat-label">Depuis</span>
            </div>
            <div class="stat-item">
                <span class="stat-value" style="font-size: 0.9rem;"><?= htmlspecialchars($user['region'] ?: '??') ?></span>
                <span class="stat-label">Secteur</span>
            </div>
        </div>

        <div class="bio-section">
            <div class="section-title">Transmission_Bio</div>
            <p class="bio-text"><?= nl2br(htmlspecialchars($user['bio'] ?: 'Aucun signal biographique détecté.')) ?></p>
        </div>
    </div>
</div>

</body>
</html>