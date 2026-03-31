<?php
// public/admin/index.php
require_once __DIR__ . '/../../app/lib/auth.php';
require_login();
require_once __DIR__ . '/../../app/config/database.php';

$user_id = current_user_id();
$role = $_SESSION['role'];

if ($role !== 'admin') {
    header('Location: /dashboard.php');
    exit;
}

// Traitement actions admin
$success_msg = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['change_role'])) {
        $target_user_id = (int)$_POST['user_id'];
        $new_role = $_POST['new_role'];
        
        if (in_array($new_role, ['user', 'helper', 'moderator', 'admin'])) {
            $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
            $stmt->execute([$new_role, $target_user_id]);
            $success_msg = "Rôle modifié avec succès.";
        }
    }
    
    if (isset($_POST['sanction_user'])) {
        $target_user_id = (int)$_POST['target_user_id'];
        $type = $_POST['sanction_type'];
        $duration = (int)$_POST['duration']; // jours
        $reason = trim($_POST['reason']);
        
        $expires_at = $duration > 0 ? date('Y-m-d H:i:s', strtotime("+$duration days")) : null;
        
        $stmt = $pdo->prepare("INSERT INTO sanctions (user_id, moderator_id, type, reason, expires_at) 
                               VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$target_user_id, $user_id, $type, $reason, $expires_at]);
        $success_msg = "Sanction appliquée.";
    }
    
    if (isset($_POST['delete_message'])) {
        $message_id = (int)$_POST['message_id'];
        $stmt = $pdo->prepare("UPDATE messages SET deleted_at = NOW() WHERE id = ?");
        $stmt->execute([$message_id]);
        $success_msg = "Message supprimé.";
    }
}

// Liste utilisateurs (avec recherche)
$users = [];
$search = trim($_GET['search'] ?? '');
if ($search) {
    $stmt = $pdo->prepare("SELECT u.*, up.first_name, up.last_name, up.region, up.political_view, up.message_count,
                                  (SELECT COUNT(*) FROM sanctions s WHERE s.user_id = u.id AND s.is_active = 1) as active_sanctions
                           FROM users u 
                           LEFT JOIN user_profiles up ON up.user_id = u.id 
                           WHERE u.username LIKE ? OR up.first_name LIKE ? OR up.last_name LIKE ?
                           ORDER BY u.last_seen_at DESC 
                           LIMIT 50");
    $stmt->execute(["%$search%", "%$search%", "%$search%"]);
    $users = $stmt->fetchAll();
} else {
    $stmt = $pdo->query("SELECT u.*, up.first_name, up.last_name, up.region, up.political_view, up.message_count,
                                (SELECT COUNT(*) FROM sanctions s WHERE s.user_id = u.id AND s.is_active = 1) as active_sanctions
                         FROM users u 
                         LEFT JOIN user_profiles up ON up.user_id = u.id 
                         ORDER BY u.last_seen_at DESC 
                         LIMIT 50");
    $users = $stmt->fetchAll();
}

// Stats globales
$stats = [];
$stmt = $pdo->query("SELECT 
    (SELECT COUNT(*) FROM users) as total_users,
    (SELECT COUNT(*) FROM users WHERE online_status = 'online') as online_users,
    (SELECT COUNT(*) FROM messages) as total_messages,
    (SELECT COUNT(*) FROM sanctions WHERE is_active = 1) as active_sanctions");
$stats = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Panel Admin</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="theme-dark admin-panel">
<div class="admin-container">
    <!-- Header admin -->
    <div class="admin-header">
        <h1>⚙️ Panel d'administration</h1>
        <a href="/dashboard.php" class="btn-primary">← Retour Dashboard</a>
    </div>

    <!-- Stats rapides -->
    <div class="admin-stats-grid">
        <div class="stat-card">
            <div class="stat-number"><?= number_format($stats['total_users']) ?></div>
            <div class="stat-label">Utilisateurs</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= number_format($stats['online_users']) ?></div>
            <div class="stat-label">En ligne</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= number_format($stats['total_messages']) ?></div>
            <div class="stat-label">Messages</div>
        </div>
        <div class="stat-card danger">
            <div class="stat-number"><?= number_format($stats['active_sanctions']) ?></div>
            <div class="stat-label">Sanctions actives</div>
        </div>
    </div>

    <?php if ($success_msg): ?>
        <div class="alert alert-success"><?= e($success_msg) ?></div>
    <?php endif; ?>

    <!-- Recherche utilisateurs -->
    <div class="admin-search-section">
        <form method="get" class="search-form">
            <input type="text" name="search" placeholder="Rechercher un utilisateur..." 
                   value="<?= e($search) ?>">
            <button type="submit" class="btn-primary">Rechercher</button>
        </form>
    </div>

    <!-- Liste utilisateurs -->
    <div class="admin-users-grid">
        <?php foreach ($users as $user): ?>
            <div class="user-card-admin" data-user-id="<?= (int)$user['id'] ?>">
                <div class="user-header-admin">
                    <div class="user-avatar-admin" style="background-image: url('<?= e($user['avatar_path'] ?? '/assets/img/default-avatar.png') ?>')"></div>
                    <div class="user-info-admin">
                        <div class="username"><?= e($user['username']) ?></div>
                        <div class="user-details">
                            <span><?= e($user['first_name'] . ' ' . $user['last_name']) ?></span>
                            <span><?= e($user['region']) ?></span>
                            <?php if ($user['political_view']): ?>
                                <span class="political-badge"><?= e($user['political_view']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="user-status">
                        <span class="status-<?= $user['online_status'] ?>"></span>
                        <?= ucfirst($user['online_status']) ?>
                    </div>
                </div>

                <div class="user-stats">
                    <span>Messages: <?= number_format($user['message_count']) ?></span>
                    <?php if ($user['active_sanctions'] > 0): ?>
                        <span class="sanction-count"><?= $user['active_sanctions'] ?> sanction(s)</span>
                    <?php endif; ?>
                </div>

                <div class="user-actions-admin">
                    <!-- Changement de rôle -->
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>">
                        <select name="new_role" class="role-select">
                            <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>Utilisateur</option>
                            <option value="helper" <?= $user['role'] === 'helper' ? 'selected' : '' ?>>Helper</option>
                            <option value="moderator" <?= $user['role'] === 'moderator' ? 'selected' : '' ?>>Modérateur</option>
                            <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                        </select>
                        <button type="submit" name="change_role" class="btn-small">Changer</button>
                    </form>

                    <!-- Sanctions rapides -->
                    <div class="sanction-buttons">
                        <form method="post" style="display: inline;" onsubmit="return confirmSanction(this)">
                            <input type="hidden" name="target_user_id" value="<?= (int)$user['id'] ?>">
                            <input type="hidden" name="sanction_type" value="mute">
                            <input type="hidden" name="duration" value="7">
                            <input type="hidden" name="reason" value="Mute 7 jours">
                            <button type="submit" name="sanction_user" class="btn-mute" title="Mute 7 jours">🔇</button>
                        </form>
                        <form method="post" style="display: inline;" onsubmit="return confirmBan(this)">
                            <input type="hidden" name="target_user_id" value="<?= (int)$user['id'] ?>">
                            <input type="hidden" name="sanction_type" value="ban">
                            <input type="hidden" name="duration" value="0">
                            <input type="hidden" name="reason" value="Ban permanent">
                            <button type="submit" name="sanction_user" class="btn-ban" title="Ban permanent">🚫</button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Historique sanctions (derniers 20) -->
    <div class="sanctions-history">
        <h3>📋 Historique des sanctions récentes</h3>
        <?php
        $stmt = $pdo->query("SELECT s.*, tu.username as target_user, mu.username as mod_username
                             FROM sanctions s 
                             JOIN users tu ON tu.id = s.user_id
                             JOIN users mu ON mu.id = s.moderator_id
                             ORDER BY s.created_at DESC LIMIT 20");
        $sanctions = $stmt->fetchAll();
        ?>
        <div class="sanctions-table">
            <?php foreach ($sanctions as $sanction): ?>
                <div class="sanction-row <?= $sanction['is_active'] ? 'active' : 'expired' ?>">
                    <div class="sanction-user"><?= e($sanction['target_user']) ?></div>
                    <div class="sanction-type"><?= $sanction['type'] === 'mute' ? '🔇 Mute' : '🚫 Ban' ?></div>
                    <div class="sanction-date"><?= date('d/m H:i', strtotime($sanction['created_at'])) ?></div>
                    <div class="sanction-mod"><?= e($sanction['mod_username']) ?></div>
                    <?php if ($sanction['expires_at']): ?>
                        <div class="sanction-expires"><?= date('d/m H:i', strtotime($sanction['expires_at'])) ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script src="/assets/js/theme.js"></script>
<script src="/assets/js/admin.js"></script>
</body>
</html>
