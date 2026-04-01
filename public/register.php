<?php
// public/register.php
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/lib/security.php';
require_once __DIR__ . '/../app/lib/auth.php';

header('Content-Type: text/html; charset=utf-8');

$errors = [];
$username = $first_name = $last_name = $age = $region = $bio = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username   = trim($_POST['username'] ?? '');
    $password   = $_POST['password'] ?? '';
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name'] ?? '');
    $age        = (int)($_POST['age'] ?? 0);
    $region     = trim($_POST['region'] ?? '');
    $political  = $_POST['political_view'] ?? '';
    $bio        = trim($_POST['bio'] ?? '');

    if ($username === '' || !preg_match('/^[A-Za-z0-9_]{3,30}$/', $username)) {
        $errors[] = 'ERR_INVALID_ID (3-30 CHARS, ALPHANUM)';
    }
    if (strlen($password) < 8) {
        $errors[] = 'ERR_KEY_TOO_SHORT (MIN_8)';
    }

    $avatar_path = 'assets/img/default-avatar.png';
    if (!empty($_FILES['avatar']['name'])) {
        $err = validate_uploaded_file($_FILES['avatar'], ['image/jpeg','image/png','image/gif'], 2 * 1024 * 1024);
        if ($err) {
            $errors[] = $err;
        } else {
            $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
            $stored = uniqid('avatar_', true) . '.' . $ext;
            $targetDir = __DIR__ . '/../storage/uploads/avatars/';
            if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $targetDir . $stored)) {
                $avatar_path = 'storage/uploads/avatars/' . $stored;
            }
        }
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("SELECT id FROM users WHERE LOWER(username) = LOWER(?)");
            $stmt->execute([$username]);
            
            if ($stmt->fetch()) {
                $errors[] = 'ERR_ID_ALREADY_RESERVED';
                $pdo->rollBack();
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role, created_at, is_validated) VALUES (?, ?, 'user', NOW(), 0)");
                $stmt->execute([$username, $hash]);
                $user_id = (int)$pdo->lastInsertId();

                $stmt = $pdo->prepare("INSERT INTO user_profiles 
                    (user_id, first_name, last_name, age, region, political_view, bio, avatar_path, rank, level, xp, nickname_color) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'User', 1, 0, '#ffffff')");
                
                $stmt->execute([$user_id, $first_name ?: null, $last_name ?: null, $age ?: null, $region ?: null, $political ?: null, $bio ?: null, $avatar_path]);

                $pdo->commit();
                
                if (session_status() === PHP_SESSION_NONE) session_start();
                $_SESSION['user_id'] = $user_id;
                $_SESSION['username'] = $username;
                $_SESSION['role'] = 'user';

                echo "<script>window.location.href='dashboard.php';</script>";
                exit;
            }
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $errors[] = 'ERR_DATABASE_FAULT : ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>REGISTRATION // OOB_OS</title>
    <style>
        :root { --neon: #ffffff; --bg: #030304; --panel: #080809; --border: #1a1a1c; }
        
        body {
            background: var(--bg); color: #888; font-family: 'Segoe UI', sans-serif;
            margin: 0; padding: 20px; display: flex; align-items: center; justify-content: center; min-height: 100vh;
        }

        body::before {
            content: " "; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: linear-gradient(rgba(18, 16, 16, 0) 50%, rgba(0, 0, 0, 0.1) 50%), 
                        linear-gradient(90deg, rgba(255, 0, 0, 0.02), rgba(0, 255, 0, 0.01), rgba(0, 0, 255, 0.02));
            background-size: 100% 4px, 3px 100%; pointer-events: none; z-index: 100;
        }

        .reg-frame {
            width: 100%; max-width: 550px; background: var(--panel);
            border: 1px solid var(--border); position: relative;
            animation: bootIn 0.4s ease-out;
        }

        @keyframes bootIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .reg-header {
            padding: 15px; background: #000; border-bottom: 1px solid var(--border);
            text-align: center; font-family: monospace; font-size: 0.7rem;
            letter-spacing: 4px; color: #fff; font-weight: 900;
        }

        .reg-content { padding: 30px; }

        .section-tag {
            font-size: 0.55rem; color: #333; text-transform: uppercase; 
            letter-spacing: 2px; margin-bottom: 20px; display: flex; align-items: center; gap: 8px;
        }
        .section-tag::before { content: ""; width: 4px; height: 4px; background: #fff; border-radius: 50%; }

        .input-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px; }
        .input-group { margin-bottom: 15px; }
        
        label {
            font-family: monospace; font-size: 0.55rem; color: #444;
            text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px; display: block;
        }

        input, select, textarea {
            width: 100%; background: #000; border: 1px solid var(--border);
            padding: 12px; border-radius: 4px; color: #eee; font-family: monospace;
            font-size: 0.8rem; box-sizing: border-box; outline: none; transition: 0.2s;
        }
        input:focus, select:focus, textarea:focus { border-color: #fff; }

        .btn-submit {
            width: 100%; background: #fff; color: #000; border: none;
            padding: 18px; border-radius: 4px; font-weight: 900; cursor: pointer;
            text-transform: uppercase; font-size: 0.75rem; letter-spacing: 2px;
            transition: 0.3s; margin-top: 20px;
        }
        .btn-submit:hover { filter: brightness(0.8); }

        .alert-terminal {
            background: rgba(255, 68, 68, 0.05); border: 1px solid #ff4444;
            color: #ff4444; padding: 12px; font-family: monospace; font-size: 0.6rem;
            margin-bottom: 20px;
        }

        .reg-footer {
            padding: 15px; border-top: 1px solid var(--border); text-align: center;
            background: rgba(0,0,0,0.3); font-size: 0.65rem;
        }
        .reg-footer a { color: #fff; text-decoration: none; font-weight: bold; }

        /* Corners décoratifs */
        .corner { position: absolute; width: 6px; height: 6px; border: 1px solid #333; }
        .tl { top: -1px; left: -1px; border-right: 0; border-bottom: 0; }
        .tr { top: -1px; right: -1px; border-left: 0; border-bottom: 0; }
        .bl { bottom: -1px; left: -1px; border-right: 0; border-top: 0; }
        .br { bottom: -1px; right: -1px; border-left: 0; border-top: 0; }
    </style>
</head>
<body>

    <div class="reg-frame">
        <div class="corner tl"></div><div class="corner tr"></div>
        <div class="corner bl"></div><div class="corner br"></div>

        <div class="reg-header">UNIT_REGISTRATION_PROTOCOL</div>
        
        <div class="reg-content">
            <?php if (!empty($errors)): ?>
                <div class="alert-terminal">
                    <?php foreach ($errors as $err): ?>
                        <div>>> <?= htmlspecialchars($err) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <form method="post" enctype="multipart/form-data">
                <div class="section-tag">01 // AUTH_CREDENTIALS</div>
                <div class="input-row">
                    <div class="input-group">
                        <label>USER_ID *</label>
                        <input type="text" name="username" value="<?= htmlspecialchars($username) ?>" required placeholder="ID_ALPHA">
                    </div>
                    <div class="input-group">
                        <label>ACCESS_KEY *</label>
                        <input type="password" name="password" required placeholder="SECRET_HEX">
                    </div>
                </div>

                <div class="section-tag">02 // UNIT_SPECIFICATIONS</div>
                <div class="input-row">
                    <div class="input-group">
                        <label>FIRST_NAME</label>
                        <input type="text" name="first_name" value="<?= htmlspecialchars($first_name) ?>">
                    </div>
                    <div class="input-group">
                        <label>LAST_NAME</label>
                        <input type="text" name="last_name" value="<?= htmlspecialchars($last_name) ?>">
                    </div>
                </div>

                <div class="input-row">
                    <div class="input-group">
                        <label>UNIT_AGE</label>
                        <input type="number" name="age" value="<?= htmlspecialchars($age) ?>">
                    </div>
                    <div class="input-group">
                        <label>SECTOR_REGION</label>
                        <input type="text" name="region" value="<?= htmlspecialchars($region) ?>">
                    </div>
                </div>

                <div class="input-group">
                    <label>BIOGRAPHICAL_DATA</label>
                    <input type="text" name="bio" value="<?= htmlspecialchars($bio) ?>" placeholder="Initialiser bio...">
                </div>

                <div class="input-group">
                    <label>VISUAL_IDENTIFIER (AVATAR)</label>
                    <input type="file" name="avatar" accept="image/*" style="font-size:0.6rem;">
                </div>

                <button type="submit" class="btn-submit">EXECUTE_REGISTRY</button>
            </form>
        </div>

        <div class="reg-footer">
            SIGNAL DÉJÀ DÉTECTÉ ? <a href="login.php">AUTH_TERMINAL</a>
        </div>
    </div>

</body>
</html>