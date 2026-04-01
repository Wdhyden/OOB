<?php
// public/chat.php
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
    <title>COMM_TERMINAL // OOB</title>
    <style>
        :root { --neon: <?= $my_neon ?>; --bg: #030304; --panel: #080809; --border: #1a1a1c; }
        
        html, body { height: 100%; margin: 0; padding: 0; overflow: hidden; background: var(--bg); color: #888; font-family: 'Segoe UI', sans-serif; }
        body { display: flex; flex-direction: column; }

        /* Effet Scanline (Cohérence avec Dashboard/Profil) */
        body::before {
            content: " "; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: linear-gradient(rgba(18, 16, 16, 0) 50%, rgba(0, 0, 0, 0.1) 50%), 
                        linear-gradient(90deg, rgba(255, 0, 0, 0.02), rgba(0, 255, 0, 0.01), rgba(0, 0, 255, 0.02));
            background-size: 100% 4px, 3px 100%; pointer-events: none; z-index: 100;
        }

        /* Top Bar Industrielle */
        .nav { 
            height: 45px; background: #000; border-bottom: 1px solid var(--border); 
            display: flex; align-items: center; justify-content: space-between; 
            padding: 0 20px; flex-shrink: 0; z-index: 50;
        }
        .btn-nav { 
            color: #555; text-decoration: none; font-size: 0.65rem; font-weight: bold; 
            letter-spacing: 1px; border: 1px solid var(--border); padding: 5px 12px; 
            border-radius: 4px; transition: 0.2s; 
        }
        .btn-nav:hover { background: #fff; color: #000; border-color: #fff; }

        /* Fenêtre de Chat (Espace de données) */
        #chatWindow { 
            flex: 1; overflow-y: auto; padding: 30px; display: flex; 
            flex-direction: column; background: var(--panel);
            scroll-behavior: smooth;
        }

        /* Zone d'Input (Terminal Style) */
        .input-area { 
            height: 90px; background: #000; border-top: 1px solid var(--border); 
            display: flex; align-items: center; padding: 0 30px; flex-shrink: 0; 
        }
        #chatForm { display: flex; width: 100%; gap: 15px; align-items: center; }
        
        .input-wrapper { flex: 1; position: relative; display: flex; align-items: center; }
        .input-wrapper::before { content: ">"; position: absolute; left: 15px; color: var(--neon); font-family: monospace; font-weight: bold; }

        #msgInput { 
            width: 100%; background: rgba(255,255,255,0.02); border: 1px solid var(--border); 
            color: #eee; padding: 12px 12px 12px 35px; border-radius: 8px; outline: none; 
            transition: 0.3s; font-family: 'Courier New', monospace;
        }
        #msgInput:focus { border-color: var(--neon); background: rgba(255,255,255,0.05); }

        .btn-send { 
            background: var(--neon); color: #000; border: none; padding: 0 30px; 
            height: 45px; border-radius: 8px; font-weight: 900; cursor: pointer; 
            transition: 0.3s; text-transform: uppercase; font-size: 0.75rem;
        }
        .btn-send:hover { filter: brightness(1.2); box-shadow: 0 0 15px rgba(var(--neon), 0.3); }
        .btn-send:disabled { background: #1a1a1c !important; color: #444 !important; cursor: not-allowed; opacity: 0.5; }

        /* MODALE PROFIL (Style Industrial) */
        #profileModal {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.9); z-index: 9999; backdrop-filter: blur(4px);
            align-items: center; justify-content: center;
        }
        .modal-card {
            background: var(--panel); border: 1px solid var(--border); width: 360px;
            border-radius: 0; /* Carré pour le style industriel */
            position: relative; box-shadow: 0 0 40px rgba(0,0,0,0.8);
            border-top: 4px solid var(--neon);
        }

        /* Scrollbar Personnalisée */
        #chatWindow::-webkit-scrollbar { width: 6px; }
        #chatWindow::-webkit-scrollbar-track { background: #000; }
        #chatWindow::-webkit-scrollbar-thumb { background: var(--border); }
        #chatWindow::-webkit-scrollbar-thumb:hover { background: var(--neon); }

    </style>
</head>
<body>
    <div class="nav">
        <a href="dashboard.php" class="btn-nav">? BACK_TO_DASHBOARD</a>
        <div style="color:var(--neon); font-weight:900; letter-spacing: 4px; font-size: 0.75rem; font-family: monospace;">
            DATA_STREAM // SECURED_CHANNEL
        </div>
        <div style="width:140px; text-align:right; font-size:0.6rem; font-family:monospace; color:#333;">
            v4.0.1_SYNC
        </div>
    </div>

    <div id="chatWindow">
        </div>

    <div class="input-area">
        <form id="chatForm">
            <div class="input-wrapper">
                <input type="text" id="msgInput" placeholder="Saisir la commande de signal..." required autocomplete="off">
            </div>
            <button type="submit" class="btn-send" id="sendBtn">EXECUTE</button>
        </form>
    </div>

    <div id="profileModal" onclick="closeModal()">
        <div class="modal-card" id="modalContent" onclick="event.stopPropagation()">
            </div>
    </div>

    <script>
        const win = document.getElementById('chatWindow');
        const form = document.getElementById('chatForm');
        const input = document.getElementById('msgInput');
        const btn = document.getElementById('sendBtn');
        const modal = document.getElementById('profileModal');
        const modalContent = document.getElementById('modalContent');
        
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

        function openMiniProfile(userId) {
            modal.style.display = 'flex';
            modalContent.innerHTML = '<div style="padding:60px; text-align:center; font-family:monospace; font-size:0.7rem; color:#222;">DECRYPTING_USER_DATA...</div>';
            fetch('get_user_card.php?id=' + userId)
                .then(r => r.text())
                .then(html => { modalContent.innerHTML = html; });
        }

        function closeModal() { modal.style.display = 'none'; }

        document.addEventListener('keydown', (e) => { if (e.key === "Escape") closeModal(); });

        function startCooldown() {
            let timeLeft = 5;
            btn.disabled = true;
            btn.innerText = timeLeft + "S";
            const timer = setInterval(() => {
                timeLeft--;
                btn.innerText = timeLeft + "S";
                if (timeLeft <= 0) {
                    clearInterval(timer);
                    btn.disabled = false;
                    btn.innerText = "EXECUTE";
                }
            }, 1000);
        }

        form.onsubmit = e => {
            e.preventDefault();
            const message = input.value.trim();
            if(!message) return;
            const fd = new FormData(); 
            fd.append('message', message);
            input.value = "";
            startCooldown();
            fetch('post_chat.php', { method: 'POST', body: fd }).then(() => load());
        };

        setInterval(load, 2000);
        load();
    </script>
</body>
</html>