<?php
// public/dashboard.php
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/lib/auth.php';
require_once __DIR__ . '/../app/lib/ranks.php';

if (session_status() === PHP_SESSION_NONE) session_start();
check_auth();

$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("SELECT u.username, u.role, p.* FROM users u JOIN user_profiles p ON u.id = p.user_id WHERE u.id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    $stmtMsg = $pdo->prepare("SELECT COUNT(*) FROM global_chat WHERE user_id = ?");
    $stmtMsg->execute([$user_id]);
    $msg_count = $stmtMsg->fetchColumn();

    $rk = getRankData($pdo, $user['rank']);
    $themeColor = $rk['color'] ?? '#00f2ff';

} catch (Exception $e) { die("SYSTEM_FAILURE"); }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>DASHBOARD // OS_v4</title>
    <style>
        :root { --neon: <?= $themeColor ?>; --bg: #030304; --panel: #080809; --border: #1a1a1c; }
        
        body {
            background: var(--bg); color: #666; font-family: 'Courier New', monospace;
            margin: 0; padding: 0; display: flex; flex-direction: column; height: 100vh;
            overflow: hidden;
        }

        /* --- SÉQUENCE DE BOOT (LOADER) --- */
        #boot-loader {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: #000; z-index: 9999; display: flex; flex-direction: column;
            justify-content: center; align-items: center;
            transition: opacity 0.5s ease, visibility 0.5s;
        }
        .boot-box { width: 300px; }
        .boot-title { color: var(--neon); font-size: 0.7rem; letter-spacing: 5px; margin-bottom: 20px; text-align: center; font-weight: bold; }
        .boot-track { width: 100%; height: 1px; background: #111; position: relative; margin-bottom: 10px; }
        .boot-bar { position: absolute; height: 100%; width: 0%; background: var(--neon); box-shadow: 0 0 10px var(--neon); animation: bootProgress 1.8s forwards; }
        .boot-status { display: flex; justify-content: space-between; font-size: 0.55rem; color: #333; }
        .boot-logs { margin-top: 30px; font-size: 0.5rem; color: #111; line-height: 1.4; }
        @keyframes bootProgress { to { width: 100%; } }
        .loaded #boot-loader { opacity: 0; visibility: hidden; }

        /* --- TOP BAR --- */
        .top-bar {
            height: 35px; border-bottom: 1px solid var(--border);
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 20px; background: #000; font-size: 0.65rem; letter-spacing: 2px;
            z-index: 50;
        }

        /* --- LAYOUT GRILLE --- */
        .main-layout {
            display: grid;
            grid-template-columns: 250px 1fr 300px;
            grid-template-rows: 1fr 200px;
            gap: 1px; background: var(--border);
            flex: 1;
        }

        .cell { background: var(--panel); padding: 25px; position: relative; overflow: hidden; }
        .cell-title {
            font-size: 0.6rem; color: #333; font-weight: bold; 
            text-transform: uppercase; margin-bottom: 20px; display: flex; justify-content: space-between;
        }

        /* --- DATA METRICS --- */
        .big-data { font-size: 3rem; color: #fff; font-weight: 100; font-family: sans-serif; letter-spacing: -2px; }
        .data-unit { font-size: 0.7rem; color: var(--neon); vertical-align: middle; margin-left: 5px; }

        /* --- NAVIGATION --- */
        .nav-item {
            border: 1px solid var(--border); margin-bottom: 10px;
            transition: 0.2s; cursor: pointer; display: block; text-decoration: none;
            padding: 15px; color: #888; font-size: 0.75rem;
        }
        .nav-item:hover { background: #fff; color: #000; border-color: #fff; }
        .nav-item span { float: right; opacity: 0.3; }

        /* --- IDENTITY --- */
        .mini-id { display: flex; gap: 15px; align-items: flex-start; }
        .mini-id img { width: 50px; height: 50px; filter: grayscale(1); border: 1px solid var(--border); }
        .mini-id b { color: #fff; font-size: 0.8rem; display: block; }
        .mini-id small { color: var(--neon); font-size: 0.6rem; }

        /* --- TERMINAL --- */
        .console { font-size: 0.7rem; line-height: 1.5; color: #444; }
        .blink { animation: blink 1s infinite; color: var(--neon); }
        @keyframes blink { 0%, 100% { opacity: 1; } 50% { opacity: 0; } }

        .corner { position: absolute; width: 5px; height: 5px; border: 1px solid var(--neon); opacity: 0.3; }
        .tl { top: 10px; left: 10px; border-right: 0; border-bottom: 0; }
        .tr { top: 10px; right: 10px; border-left: 0; border-bottom: 0; }

    </style>
</head>
<body>

    <div id="boot-loader">
        <div class="boot-box">
            <div class="boot-title">CORE_BOOT_SEQUENCE</div>
            <div class="boot-track"><div class="boot-bar"></div></div>
            <div class="boot-status">
                <span>MOUNTING_FILESYSTEM</span>
                <span id="load-val">0%</span>
            </div>
            <div class="boot-logs">
                > LOADING OOB_KERNEL... OK<br>
                > DECRYPTING_SESSION... OK<br>
                > SYNCING_DATA_GRID... OK
            </div>
        </div>
    </div>

    <div class="top-bar">
        <div>OOB_SYSTEM_v4.0.1 // STATUS: <span style="color:var(--neon)">ONLINE</span></div>
        <div>SESSION_TOKEN: <?= bin2hex($user['username']) ?></div>
    </div>

    <div class="main-layout">
        
        <div class="cell">
            <div class="cell-title">CORE_NAVIGATION <span>[01]</span></div>
            <a href="chat.php" class="nav-item">CHAT PUBLIC <span>01</span></a>
            <a href="profile.php" class="nav-item">PROFILE <span>02</span></a>
            <a href="drive.php" class="nav-item">DRIVE <span>03</span></a>
            <?php if($user['role'] === 'admin'): ?>
                <a href="admin.php" class="nav-item" style="color:#ff4444">OVERLORD_ACCESS <span>!!</span></a>
            <?php endif; ?>
        </div>

        <div class="cell" style="display: flex; flex-direction: column; justify-content: center; align-items: center;">
            <div class="corner tl"></div><div class="corner tr"></div>
            <div class="cell-title">DATA_METRICS</div>
            
            <div style="margin-bottom: 40px; text-align: center;">
                <div class="big-data"><?= number_format($user['xp']) ?><span class="data-unit">XP</span></div>
                <div style="font-size: 0.6rem; letter-spacing: 5px;">ACCUMULATED_EXPERIENCE</div>
            </div>

            <div style="text-align: center;">
                <div class="big-data"><?= $msg_count ?><span class="data-unit">MSG</span></div>
                <div style="font-size: 0.6rem; letter-spacing: 5px;">NETWORK_CONTRIBUTION</div>
            </div>
        </div>

        <div class="cell">
            <div class="cell-title">USER_STATUS <span>[ID]</span></div>
            <div class="mini-id">
                <img src="/<?= htmlspecialchars($user['avatar_path'] ?: 'assets/img/default-avatar.png') ?>">
                <div>
                    <b><?= htmlspecialchars($user['username']) ?></b>
                    <small><?= htmlspecialchars($user['rank']) ?></small>
                </div>
            </div>
            <div style="margin-top: 30px; font-size: 0.65rem; line-height: 2;">
                <div style="display:flex; justify-content:space-between"><span>LEVEL:</span> <span style="color:#fff"><?= $user['level'] ?></span></div>
                <div style="display:flex; justify-content:space-between"><span>CLEARANCE:</span> <span style="color:#fff"><?= strtoupper($user['role']) ?></span></div>
                <div style="display:flex; justify-content:space-between"><span>REGION:</span> <span style="color:#fff"><?= htmlspecialchars($user['region'] ?: 'N/A') ?></span></div>
            </div>
        </div>

        <div class="cell" style="grid-column: span 3;">
            <div class="cell-title">TERMINAL_OUTPUT</div>
            <div class="console">
                [SYSTEM] INITIALIZING CORE... DONE.<br>
                [AUTH] IDENTITY VERIFIED FOR USER "<?= strtoupper($user['username']) ?>"<br>
                [NETWORK] CONNECTION STABLE AT SECTOR <?= htmlspecialchars($user['region'] ?: 'GLOBAL') ?><br>
                [READY] WAITING FOR INPUT <span class="blink">_</span>
            </div>
        </div>

    </div>

    <div class="top-bar" style="border-top:1px solid var(--border); border-bottom:0; color:#222;">
        <div>© OOB_CORP 2026 // ALL_RIGHTS_RESERVED</div>
        <div>LATENCY: 12ms</div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const loadVal = document.getElementById('load-val');
            let progress = 0;

            // Simule l'incrémentation du %
            const interval = setInterval(() => {
                progress += 2;
                if(progress > 100) progress = 100;
                loadVal.innerText = progress + "%";
                
                if (progress >= 100) {
                    clearInterval(interval);
                    setTimeout(() => {
                        document.body.classList.add('loaded');
                    }, 300);
                }
            }, 30); // Environ 1.5s - 2s total
        });
    </script>

</body>
</html>