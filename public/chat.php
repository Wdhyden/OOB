<?php
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/lib/auth.php';

check_auth();
check_validation($pdo);

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT p.nickname_color FROM user_profiles p WHERE p.user_id = ?");
$stmt->execute([$user_id]);
$my_neon = $stmt->fetch()['nickname_color'] ?? '#00f2ff';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Global Chat // OOB</title>
    <style>
        :root { --neon: <?= $my_neon ?>; --bg: #0a0a0c; --border: rgba(255, 255, 255, 0.08); }
        
        html, body { height: 100%; margin: 0; padding: 0; overflow: hidden; background: var(--bg); color: #eee; font-family: sans-serif; }
        body { display: flex; flex-direction: column; }
        /* Animation Dégradé de Bleus */
@keyframes blue-pulse {
    0% { color: #00d4ff; text-shadow: 0 0 8px rgba(0, 212, 255, 0.6); } /* Bleu clair */
    50% { color: #0044ff; text-shadow: 0 0 8px rgba(0, 68, 255, 0.6); }  /* Bleu foncé */
    100% { color: #00d4ff; text-shadow: 0 0 8px rgba(0, 212, 255, 0.6); }
}

.vip-glow { 
    animation: blue-pulse 3s ease-in-out infinite; 
    font-weight: bold;
}
        @keyframes admin-rainbow {
            0% { color: #ff0000; text-shadow: 0 0 5px #ff0000; }
            50% { color: #00f2ff; text-shadow: 0 0 5px #00f2ff; }
            100% { color: #ff0000; text-shadow: 0 0 5px #ff0000; }
        }
        .admin-glow { animation: admin-rainbow 3s linear infinite; font-weight: 900; }

        .nav { height: 60px; background: #000; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; padding: 0 20px; flex-shrink: 0; }
        
        #chatWindow { 
            flex: 1; 
            overflow-y: auto; 
            padding: 20px; 
            display: flex; 
            flex-direction: column; 
            background: rgba(255,255,255,0.01);
        }

        .input-area { height: 80px; background: #000; border-top: 1px solid var(--border); display: flex; align-items: center; padding: 0 20px; flex-shrink: 0; }
        #chatForm { display: flex; width: 100%; gap: 10px; }
        #msgInput { flex: 1; background: #111; border: 1px solid var(--border); color: #fff; padding: 12px; border-radius: 8px; outline: none; }
        .btn-send { background: var(--neon); color: #000; border: none; padding: 0 25px; border-radius: 8px; font-weight: bold; cursor: pointer; }

        /* Pour éviter que les images ne fassent sauter le layout au chargement */
        img { border-radius: 8px; background: #222; }
    </style>
</head>
<body>
    <div class="nav">
        <a href="dashboard.php" style="color:#fff; text-decoration:none; font-size:0.8rem; border:1px solid #333; padding:5px 10px; border-radius:5px;">🏠 DASHBOARD</a>
        <div style="color:var(--neon); font-weight:bold;">STREAM_GLOBAL</div>
        <div style="width:80px;"></div>
    </div>

    <div id="chatWindow">
        </div>

    <div class="input-area">
        <form id="chatForm">
            <input type="text" id="msgInput" placeholder="Saisir un signal..." required autocomplete="off">
            <button type="submit" class="btn-send">SEND</button>
        </form>
    </div>

    <script>
        const win = document.getElementById('chatWindow');
        let lastHTML = "";
        let initial = true;

        function load() {
            fetch('fetch_chat.php').then(r => r.text()).then(html => {
                if (html === lastHTML) return;
                const isBottom = win.scrollHeight - win.scrollTop <= win.clientHeight + 150;
                win.innerHTML = html;
                lastHTML = html;
                if (isBottom || initial) { win.scrollTop = win.scrollHeight; initial = false; }
            });
        }

        document.getElementById('chatForm').onsubmit = e => {
            e.preventDefault();
            const fd = new FormData(); fd.append('message', document.getElementById('msgInput').value);
            document.getElementById('msgInput').value = "";
            fetch('post_chat.php', { method: 'POST', body: fd }).then(() => load());
        };

        setInterval(load, 2000);
        load();
    </script>
</body>
</html>