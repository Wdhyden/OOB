<?php
// public/chat.php - VERSION COMPLÈTE
require_once __DIR__ . '/../app/lib/auth.php';
require_login();
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/lib/security.php';

$user_id = current_user_id();
$role = $_SESSION['role'];

// Charger les salons accessibles à l'utilisateur
$stmt = $pdo->prepare("
    SELECT cr.id, cr.name, cr.description, rm.is_admin,
           (SELECT COUNT(*) FROM room_members rm2 WHERE rm2.room_id = cr.id) as member_count
    FROM chat_rooms cr
    JOIN room_members rm ON rm.room_id = cr.id
    WHERE rm.user_id = ?
    ORDER BY cr.name
");
$stmt->execute([$user_id]);
$rooms = $stmt->fetchAll();

// Charger utilisateurs en ligne pour sidebar droite
$stmt = $pdo->query("SELECT id, username FROM users WHERE online_status = 'online' ORDER BY username LIMIT 20");
$online_users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Chat - Community App</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="theme-dark">
<div class="app-shell">
    <!-- Sidebar salons (gauche) -->
    <aside class="sidebar-rooms">
        <div class="sidebar-header">
            <h3>Salons</h3>
            <?php if (in_array($role, ['admin', 'moderator'])): ?>
                <button id="create-room" class="btn-primary btn-small" title="Nouveau salon">➕</button>
            <?php endif; ?>
        </div>
        
        <div class="rooms-list">
            <?php foreach ($rooms as $room): ?>
                <div class="room-item" data-room-id="<?= (int)$room['id'] ?>">
                    <span>#<?= e($room['name']) ?></span>
                    <?php if ($room['is_admin']): ?>
                        <span class="room-admin-badge" title="Admin">👑</span>
                    <?php endif; ?>
                    <span class="room-member-count"><?= $room['member_count'] ?> membres</span>
                </div>
            <?php endforeach; ?>
        </div>
    </aside>

    <!-- Header chat -->
    <header class="chat-header">
        <h2 id="current-room-name">Sélectionner un salon</h2>
        <div class="header-actions">
            <button id="theme-toggle" class="btn-small">🌙</button>
            <a href="/dashboard.php" class="btn-primary btn-small">← Dashboard</a>
        </div>
    </header>

    <!-- Zone principale chat -->
    <main class="chat-main">
        <div id="messages" class="messages-list">
            <div class="no-room-selected">
                <h3>Bienvenue dans le chat !</h3>
                <p>Cliquez sur un salon à gauche pour commencer.</p>
            </div>
        </div>

        <div class="chat-input-container">
            <div class="chat-input">
                <textarea id="message-input" rows="1" placeholder="Tapez votre message (Ctrl+Entrée)..."></textarea>
                <input type="file" id="file-input" hidden accept="image/*,video/*,audio/*,.pdf">
                <button id="btn-attach" class="btn-small" title="Pièce jointe">📎</button>
                <button id="btn-send" class="btn-primary">Envoyer</button>
            </div>
        </div>
    </main>

    <!-- Sidebar utilisateurs en ligne (droite) -->
    <aside class="sidebar-users">
        <div class="sidebar-header">
            <h3>En ligne (<?= count($online_users) ?>)</h3>
        </div>
        <div id="online-users-list" class="online-users-list">
            <?php foreach ($online_users as $user): ?>
                <div class="online-user" data-user-id="<?= (int)$user['id'] ?>">
                    <a href="/profile.php?id=<?= $user['id'] ?>" class="user-link">
                        <?= e($user['username']) ?>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </aside>

    <!-- Modal création salon -->
    <?php if (in_array($role, ['admin', 'moderator'])): ?>
    <div id="create-room-modal" class="modal">
        <div class="modal-content">
            <h3>Créer un nouveau salon</h3>
            <form id="create-room-form">
                <div class="input-group">
                    <label>Nom du salon</label>
                    <input type="text" name="name" required maxlength="50">
                </div>
                <div class="input-group">
                    <label>Privé ?</label>
                    <input type="checkbox" name="is_private">
                    <small>Seuls les membres invités peuvent rejoindre</small>
                </div>
                <div class="modal-actions">
                    <button type="submit" class="btn-primary">Créer</button>
                    <button type="button" class="btn-cancel" id="cancel-create-room">Annuler</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
const USER_ID = <?= json_encode($user_id) ?>;
const USER_ROLE = <?= json_encode($role) ?>;
</script>
<script src="/assets/js/theme.js"></script>
<script src="/assets/js/chat.js"></script>
</body>
</html>
