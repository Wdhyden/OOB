<?php
// public/login.php
session_start();
require_once __DIR__ . '/../app/config/database.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if ($username === 'admin') {
        $error = "ERR_AUTH_FORBIDDEN_INTERFACE";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, username, password_hash, role FROM users WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'] ?? 'user';
                
                header("Location: dashboard.php");
                exit;
            } else {
                $error = "ERR_INVALID_CREDENTIALS";
            }
        } catch (PDOException $e) {
            $error = "ERR_DATABASE_DISCONNECT";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>AUTH_REQUIRED // OOB_OS</title>
    <style>
        :root { --neon: #ffffff; --bg: #030304; --panel: #080809; --border: #1a1a1c; }
        
        body {
            background: var(--bg); color: #888; font-family: 'Segoe UI', sans-serif;
            margin: 0; padding: 0; display: flex; align-items: center; justify-content: center; height: 100vh;
            overflow: hidden;
        }

        /* Scanline Overlay */
        body::before {
            content: " "; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: linear-gradient(rgba(18, 16, 16, 0) 50%, rgba(0, 0, 0, 0.1) 50%), 
                        linear-gradient(90deg, rgba(255, 0, 0, 0.02), rgba(0, 255, 0, 0.01), rgba(0, 0, 255, 0.02));
            background-size: 100% 4px, 3px 100%; pointer-events: none; z-index: 100;
        }

        .login-frame {
            width: 100%; max-width: 400px; background: var(--panel);
            border: 1px solid var(--border); position: relative;
            animation: bootIn 0.5s cubic-bezier(0.23, 1, 0.32, 1);
        }

        @keyframes bootIn {
            from { opacity: 0; transform: scale(0.95) translateY(10px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }

        .login-header {
            padding: 20px; background: #000; border-bottom: 1px solid var(--border);
            text-align: center; font-family: monospace; font-size: 0.75rem;
            letter-spacing: 5px; color: #fff; font-weight: 900;
        }

        .login-content { padding: 40px; }

        .input-group { margin-bottom: 25px; position: relative; }
        .input-label {
            font-family: monospace; font-size: 0.55rem; color: #444;
            text-transform: uppercase; letter-spacing: 2px; margin-bottom: 8px; display: block;
        }

        input {
            width: 100%; background: #000; border: 1px solid var(--border);
            padding: 15px; border-radius: 4px; color: #eee; font-family: monospace;
            font-size: 0.85rem; box-sizing: border-box; outline: none; transition: 0.3s;
        }
        input:focus { border-color: #fff; background: rgba(255,255,255,0.02); }

        .btn-submit {
            width: 100%; background: #fff; color: #000; border: none;
            padding: 18px; border-radius: 4px; font-weight: 900; cursor: pointer;
            text-transform: uppercase; font-size: 0.75rem; letter-spacing: 3px;
            transition: 0.3s; margin-top: 10px;
        }
        .btn-submit:hover { filter: brightness(0.8); box-shadow: 0 0 20px rgba(255,255,255,0.1); }

        .error-terminal {
            background: rgba(255, 68, 68, 0.05); border: 1px solid #ff4444;
            color: #ff4444; padding: 12px; font-family: monospace; font-size: 0.65rem;
            margin-bottom: 25px; text-align: center;
        }

        .footer-links {
            padding: 20px; border-top: 1px solid var(--border); text-align: center;
            background: rgba(0,0,0,0.3);
        }
        .footer-links p { font-size: 0.65rem; color: #333; text-transform: uppercase; margin-bottom: 15px; }
        .btn-secondary {
            color: #666; text-decoration: none; font-size: 0.65rem; font-weight: bold;
            text-transform: uppercase; letter-spacing: 1px; transition: 0.2s;
        }
        .btn-secondary:hover { color: #fff; }

        /* Décorations angles */
        .corner { position: absolute; width: 6px; height: 6px; border: 1px solid #333; }
        .tl { top: -1px; left: -1px; border-right: 0; border-bottom: 0; }
        .tr { top: -1px; right: -1px; border-left: 0; border-bottom: 0; }
        .bl { bottom: -1px; left: -1px; border-right: 0; border-top: 0; }
        .br { bottom: -1px; right: -1px; border-left: 0; border-top: 0; }
    </style>
</head>
<body>

    <div class="login-frame">
        <div class="corner tl"></div><div class="corner tr"></div>
        <div class="corner bl"></div><div class="corner br"></div>

        <div class="login-header">INITIALIZE_SESSION</div>
        
        <div class="login-content">
            <?php if ($error): ?>
                <div class="error-terminal">>> <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="input-group">
                    <span class="input-label">USER_IDENTIFIER</span>
                    <input type="text" name="username" placeholder="Saisir ID..." 
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autofocus>
                </div>

                <div class="input-group">
                    <span class="input-label">ACCESS_KEY</span>
                    <input type="password" name="password" placeholder="Saisir clé..." required>
                </div>

                <button type="submit" class="btn-submit">AUTH_PROCEED</button>
            </form>
        </div>

        <div class="footer-links">
            <p>Pas de signal détecté ?</p>
            <a href="register.php" class="btn-secondary">INITIALISER_NOUVELLE_UNITÉ</a>
        </div>
    </div>

</body>
</html>