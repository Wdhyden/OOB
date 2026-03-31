<?php
// public/private_messages.php
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/lib/auth.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

$user_id = $_SESSION['user_id'];
$contact_id = isset($_GET['with']) ? (int)$_GET['with'] : null;

// 1. Récupérer la liste des conversations actives
$stmt = $pdo->prepare("
    SELECT DISTINCT u.id, u.username, p.avatar_path, p.nickname_color
    FROM users u
    JOIN user_profiles p ON u.id = p.user_id
    JOIN private_messages m ON (m.sender_id = u.id OR m.receiver_id = u.id)
    WHERE (m.sender_id = :uid OR m.receiver_id = :uid) AND u.id != :uid
");
$stmt->execute(['uid' => $user_id]);
$contacts = $stmt->fetchAll();

// 2. Récupérer TOUS les utilisateurs pour la modale
$stmt = $pdo->prepare("
    SELECT u.id, u.username, p.avatar_path, p.nickname_color 
    FROM users u 
    JOIN user_profiles p ON u.id = p.user_id 
    WHERE u.id != ? 
    ORDER BY u.username ASC
");
$stmt->execute([$user_id]);
$all_users = $stmt->fetchAll();

// 3. Charger les messages de la conversation sélectionnée
$messages = [];
$contact_info = null;
if ($contact_id) {
    $stmt = $pdo->prepare("SELECT u.username, p.nickname_color, p.avatar_path FROM users u JOIN user_profiles p ON u.id = p.user_id WHERE u.id = ?");
    $stmt->execute([$contact_id]);
    $contact_info = $stmt->fetch();

    if ($contact_info) {
        $stmt = $pdo->prepare("SELECT * FROM private_messages WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) ORDER BY sent_at ASC");
        $stmt->execute([$user_id, $contact_id, $contact_id, $user_id]);
        $messages = $stmt->fetchAll();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messagerie Sécurisée - OOB</title>
    <style>
        :root {
            --neon: <?= htmlspecialchars($contact_info['nickname_color'] ?? '#ff6fd8') ?>;
            --bg: #0a0a0b;
            --glass: rgba(255, 255, 255, 0.03);
            --glass-border: rgba(255, 255, 255, 0.1);
        }

        body {
            background: var(--bg);
            color: #eee;
            font-family: 'Segoe UI', system-ui, sans-serif;
            margin: 0;
            height: 100vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        /* --- Barre de Navigation --- */
        .top-nav {
            padding: 15px 30px;
            background: rgba(255, 255, 255, 0.02);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--glass-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 10;
        }

        .btn-nav {
            background: var(--glass);
            border: 1px solid var(--glass-border);
            color: #fff;
            padding: 8px 16px;
            border-radius: 10px;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 700;
            transition: 0.3s;
        }

        .btn-nav:hover { background: var(--neon); color: #000; box-shadow: 0 0 15px var(--neon); }

        /* --- Layout --- */
        .messenger-wrapper { display: flex; flex: 1; overflow: hidden; }

        /* --- Contacts (Gauche) --- */
        .side-panel { width: 320px; background: rgba(0, 0, 0, 0.2); border-right: 1px solid var(--glass-border); display: flex; flex-direction: column; }
        .side-header { padding: 20px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--glass-border); }
        .btn-plus { background: var(--neon); color: #000; border: none; width: 28px; height: 28px; border-radius: 8px; font-weight: 900; cursor: pointer; transition: 0.3s; }
        .btn-plus:hover { transform: scale(1.1); box-shadow: 0 0 10px var(--neon); }

        .contacts-list { flex: 1; overflow-y: auto; }
        .contact-item { display: flex; align-items: center; padding: 15px 20px; text-decoration: none; color: #888; transition: 0.2s; border-left: 3px solid transparent; }
        .contact-item:hover { background: rgba(255,255,255,0.03); }
        .contact-item.active { background: rgba(255,255,255,0.05); border-left-color: var(--neon); color: #fff; }
        .avatar-small { width: 40px; height: 40px; border-radius: 10px; margin-right: 15px; object-fit: cover; border: 1px solid var(--glass-border); }

        /* --- Chat (Droite) --- */
        .main-chat { flex: 1; display: flex; flex-direction: column; background: radial-gradient(circle at top right, rgba(255,111,216,0.02), transparent); }
        .chat-header { padding: 18px 30px; background: rgba(255,255,255,0.01); border-bottom: 1px solid var(--glass-border); display: flex; align-items: center; gap: 15px; }
        .messages-container { flex: 1; padding: 30px; overflow-y: auto; display: flex; flex-direction: column; gap: 12px; }

        .bubble { max-width: 65%; padding: 12px 18px; border-radius: 18px; font-size: 0.92rem; line-height: 1.5; }
        .bubble.sent { align-self: flex-end; background: var(--neon); color: #000; font-weight: 600; border-bottom-right-radius: 4px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .bubble.received { align-self: flex-start; background: var(--glass); border: 1px solid var(--glass-border); color: #fff; border-bottom-left-radius: 4px; }
        .time { font-size: 0.6rem; opacity: 0.5; display: block; margin-top: 5px; text-align: right; }

        /* --- Input Area --- */
        .input-bar { padding: 20px 30px; background: rgba(0,0,0,0.2); display: flex; gap: 12px; border-top: 1px solid var(--glass-border); }
        .input-bar input { flex: 1; background: rgba(255,255,255,0.03); border: 1px solid var(--glass-border); padding: 12px 18px; border-radius: 12px; color: #fff; outline: none; transition: 0.3s; }
        .input-bar input:focus { border-color: var(--neon); background: rgba(0,0,0,0.4); }
        .btn-send { background: var(--neon); color: #000; border: none; padding: 0 20px; border-radius: 12px; font-weight: 800; cursor: pointer; transition: 0.2s; }
        .btn-send:hover { transform: translateY(-2px); box-shadow: 0 0 15px var(--neon); }

        /* --- Modale --- */
        .modal { position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.9); backdrop-filter: blur(10px); display: none; justify-content: center; align-items: center; z-index: 1000; }
        .modal-card { background: #111; border: 1px solid var(--glass-border); width: 380px; border-radius: 24px; padding: 25px; box-shadow: 0 0 40px rgba(0,0,0,0.5); }
        .user-row { display: flex; align-items: center; padding: 10px; text-decoration: none; color: #fff; border-radius: 10px; margin-bottom: 5px; transition: 0.2s; }
        .user-row:hover { background: var(--glass); }

        ::-webkit-scrollbar { width: 4px; }
        ::-webkit-scrollbar-thumb { background: var(--glass-border); border-radius: 10px; }
    </style>
</head>
<body>

<div class="top-nav">
    <a href="dashboard.php" class="btn-nav">🏠 DASHBOARD</a>
    <div style="letter-spacing: 4px; font-weight: 900; font-size: 0.65rem; color: #444;">OOB // SECURE_COMMS</div>
    <div style="width: 100px;"></div>
</div>

<div class="messenger-wrapper">
    <div class="side-panel">
        <div class="side-header">
            <span style="font-weight: 800; font-size: 0.75rem; letter-spacing: 1px; color: #666;">TRANSMISSIONS</span>
            <button class="btn-plus" onclick="toggleModal(true)">+</button>
        </div>
        <div class="contacts-list">
            <?php foreach ($contacts as $c): ?>
                <a href="?with=<?= $c['id'] ?>" class="contact-item <?= ($contact_id == $c['id']) ? 'active' : '' ?>">
                    <img src="<?= htmlspecialchars($c['avatar_path'] ?: 'assets/img/default-avatar.png') ?>" class="avatar-small">
                    <span style="color: <?= htmlspecialchars($c['nickname_color']) ?>; font-weight: 600;">
                        <?= htmlspecialchars($c['username']) ?>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="main-chat">
        <?php if ($contact_id && $contact_info): ?>
            <div class="chat-header">
                <img src="<?= htmlspecialchars($contact_info['avatar_path'] ?: 'assets/img/default-avatar.png') ?>" class="avatar-small" style="width: 32px; height: 32px;">
                <span style="color: <?= htmlspecialchars($contact_info['nickname_color']) ?>; font-weight: 800; letter-spacing: 1px;">
                    @<?= htmlspecialchars($contact_info['username']) ?>
                </span>
            </div>

            <div class="messages-container" id="scrollBox">
                <?php foreach ($messages as $m): ?>
                    <div class="bubble <?= ($m['sender_id'] == $user_id) ? 'sent' : 'received' ?>">
                        <?= nl2br(htmlspecialchars($m['content'])) ?>
                        <span class="time"><?= date('H:i', strtotime($m['sent_at'])) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>

            <form class="input-bar" method="POST" action="send_message.php">
                <input type="hidden" name="receiver_id" value="<?= $contact_id ?>">
                <input type="text" name="message" placeholder="Entrez une commande ou un message..." required autocomplete="off">
                <button type="submit" class="btn-send">TRANSMETTRE</button>
            </form>
        <?php else: ?>
            <div style="flex: 1; display: flex; align-items: center; justify-content: center; opacity: 0.15; text-align: center;">
                <div>
                    <div style="font-size: 4rem; margin-bottom: 15px;">📡</div>
                    <p style="letter-spacing: 2px; font-weight: 700;">INITIALISEZ UNE CONNEXION<br>VIA LE TERMINAL GAUCHE</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="modal" id="userModal" onclick="toggleModal(false)">
    <div class="modal-card" onclick="event.stopPropagation()">
        <h2 style="margin-top: 0; font-size: 1rem; color: var(--neon); letter-spacing: 2px;">NOUVELLE CIBLE</h2>
        <div style="max-height: 350px; overflow-y: auto; margin-top: 15px;">
            <?php foreach ($all_users as $u): ?>
                <a href="?with=<?= $u['id'] ?>" class="user-row">
                    <img src="<?= htmlspecialchars($u['avatar_path'] ?: 'assets/img/default-avatar.png') ?>" class="avatar-small" style="width: 30px; height: 30px;">
                    <span style="color: <?= htmlspecialchars($u['nickname_color']) ?>"><?= htmlspecialchars($u['username']) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
    const box = document.getElementById('scrollBox');
    if(box) box.scrollTop = box.scrollHeight;

    function toggleModal(show) {
        document.getElementById('userModal').style.display = show ? 'flex' : 'none';
    }
</script>

</body>
</html>