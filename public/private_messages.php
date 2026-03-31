<?php
// public/private_messages.php - FONCTIONNEL SANS DB SPÉCIFIQUE
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'User';

// FICHIER JSON pour stocker messages (pas besoin DB)
$messages_file = __DIR__ . '/../storage/mp_messages.json';
if (!is_dir(dirname($messages_file))) {
    mkdir(dirname($messages_file), 0777, true);
}

$all_messages = [];
if (file_exists($messages_file)) {
    $all_messages = json_decode(file_get_contents($messages_file), true) ?: [];
}

// Envoi message
if ($_POST['action'] === 'send') {
    $recipient = trim($_POST['recipient']);
    $message = trim($_POST['message']);
    
    if ($recipient && $message && strlen($message) <= 500) {
        $all_messages[] = [
            'id' => uniqid(),
            'sender_id' => $user_id,
            'sender' => $username,
            'recipient' => $recipient,
            'message' => $message,
            'time' => date('Y-m-d H:i:s'),
            'read' => false
        ];
        file_put_contents($messages_file, json_encode($all_messages));
    }
    header('Location: private_messages.php?with=' . urlencode($recipient));
    exit;
}

// Messages pour utilisateur connecté
$recipient_filter = $_GET['with'] ?? '';
$my_messages = array_filter($all_messages, function($msg) use ($user_id, $username, $recipient_filter) {
    return ($msg['sender_id'] == $user_id || $msg['recipient'] == $username) &&
           (!$recipient_filter || $msg['recipient'] == $recipient_filter || $msg['sender'] == $recipient_filter);
});

// Utilisateurs avec conversations
$users_in_convo = array_unique(array_merge(
    array_column($my_messages, 'recipient'),
    array_column($my_messages, 'sender')
));
$users_in_convo = array_filter($users_in_convo, fn($u) => $u !== $username);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Messages Privés</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box;}
        body{background:#0a0a0a;color:#f5f5f5;font-family:Arial,sans-serif;height:100vh;}
        .header{display:flex;justify-content:space-between;align-items:center;padding:20px;background:#1f2020;}
        .container{display:flex;height:calc(100vh - 80px);max-width:1400px;margin:0 auto;gap:1px;}
        .sidebar{width:320px;background:#1f2020;overflow:auto;}
        .main{flex:1;background:#1f2020;display:flex;flex-direction:column;}
        .conv-section{padding:20px;}
        .new-mp{background:#2a2a2a;padding:20px;border-radius:12px;margin-bottom:20px;}
        .conv-list h3{margin-bottom:15px;color:#ff6fd8;}
        .conv-item{padding:15px;cursor:pointer;border-radius:8px;margin-bottom:8px;transition:all 0.3s;border-left:3px solid #333;}
        .conv-item:hover,.conv-item.active{border-left-color:#ff6fd8;background:#2a2a2a;}
        .conv-name{font-weight:bold;font-size:16px;}
        .conv-preview{font-size:14px;opacity:0.8;margin-top:4px;}
        .unread{background:#00ff88;color:#000;padding:2px 8px;border-radius:12px;font-size:12px;font-weight:bold;float:right;}
        .messages{flex:1;overflow-y:auto;padding:20px;}
        .message{margin-bottom:15px;padding:12px 16px;border-radius:20px;max-width:75%;word-wrap:break-word;}
        .message.sent{background:linear-gradient(135deg,#ff6fd8,#ff85e4);color:#000;margin-left:auto;}
        .message.received{background:#333;}
        .message-header{font-weight:bold;margin-bottom:4px;}
        .message-time{font-size:12px;opacity:0.7;margin-top:4px;}
        .input-area{padding:20px;border-top:1px solid #333;display:flex;gap:10px;}
        .msg-input{flex:1;padding:15px;border:1px solid #444;background:#2a2a2a;color:#f5f5f5;border-radius:25px;}
        .send-btn{padding:15px 25px;background:#ff6fd8;color:#000;border:none;border-radius:25px;cursor:pointer;font-weight:bold;}
        .empty{padding:80px;text-align:center;color:#888;}
        h1{font-size:28px;color:#ff6fd8;}
    </style>
</head>
<body>
    <div class="header">
        <h1>📨 Messages Privés</h1>
        <a href="dashboard.php" style="color:#ff6fd8;padding:12px 24px;background:#2a2a2a;border-radius:12px;text-decoration:none;font-weight:bold;">← Dashboard</a>
    </div>

    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="conv-section">
                <div class="new-mp">
                    <h3 style="color:#ff6fd8;margin-bottom:15px;">✨ Nouveau message</h3>
                    <form method="POST" style="display:flex;flex-direction:column;gap:10px;">
                        <input type="hidden" name="action" value="send">
                        <input type="text" name="recipient" placeholder="Pseudo destinataire..." 
                               value="<?= htmlspecialchars($recipient_filter) ?>" list="users-list" 
                               style="padding:12px;background:#333;color:#f5f5f5;border:none;border-radius:8px;" required>
                        <datalist id="users-list">
                            <?php foreach(array_unique(array_merge(['alice','bob','charlie','diana'], $users_in_convo)) as $user): ?>
                                <option value="<?= htmlspecialchars($user) ?>">
                            <?php endforeach; ?>
                        </datalist>
                        <input type="text" name="message" placeholder="Votre message..." maxlength="500" required 
                               style="padding:12px;background:#333;color:#f5f5f5;border:none;border-radius:8px;">
                        <button type="submit" class="send-btn">📤 Envoyer</button>
                    </form>
                </div>
            </div>
            
            <div class="conv-section">
                <h3>Conversations (<?= count($users_in_convo) ?>)</h3>
                <div class="conv-list">
                    <?php foreach($users_in_convo as $user): ?>
                        <?php 
                        $user_msgs = array_filter($my_messages, fn($m) => $m['recipient'] == $user || $m['sender'] == $user);
                        $unread = count(array_filter($user_msgs, fn($m) => $m['recipient'] == $username && !$m['read']));
                        $last_msg = end($user_msgs)['message'] ?? '';
                        ?>
                        <a href="?with=<?= urlencode($user) ?>" class="conv-item <?= $recipient_filter == $user ? 'active' : '' ?>">
                            <div class="conv-name"><?= htmlspecialchars($user) ?></div>
                            <div class="conv-preview"><?= htmlspecialchars(substr($last_msg, 0, 50)) ?>...</div>
                            <?php if($unread): ?>
                                <span class="unread"><?= $unread ?></span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                    <?php if(empty($users_in_convo)): ?>
                        <div class="conv-item" style="justify-content:center;color:#888;">Aucune conversation</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Chat principal -->
        <div class="main">
            <?php if($recipient_filter && ($recipient_msgs = array_filter($my_messages, fn($m) => 
                ($m['recipient'] == $recipient_filter && $m['sender_id'] == $user_id) || 
                ($m['recipient'] == $username && $m['sender'] == $recipient_filter)))): ?>
                <div class="messages">
                    <?php foreach($recipient_msgs as $msg): ?>
                        <div class="message <?= $msg['sender_id'] == $user_id ? 'sent' : 'received' ?>">
                            <div class="message-header"><?= htmlspecialchars($msg['sender']) ?></div>
                            <div><?= nl2br(htmlspecialchars($msg['message'])) ?></div>
                            <div class="message-time"><?= date('H:i', strtotime($msg['time'])) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <form method="POST" class="input-area">
                    <input type="hidden" name="action" value="send">
                    <input type="hidden" name="recipient" value="<?= htmlspecialchars($recipient_filter) ?>">
                    <input type="text" name="message" placeholder="Tapez votre message..." maxlength="500" required class="msg-input">
                    <button type="submit" class="send-btn">Envoyer</button>
                </form>
            <?php else: ?>
                <div class="messages empty">
                    <h3>💬 Messages Privés</h3>
                    <p>Choisissez une conversation ou envoyez un message à quelqu'un</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Auto-scroll
        document.querySelector('.messages')?.scrollTo(0, 999999);
        
        // Input focus
        document.querySelector('.msg-input')?.focus();
    </script>
</body>
</html>
