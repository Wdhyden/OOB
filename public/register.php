<?php
// public/register.php
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/lib/security.php';
require_once __DIR__ . '/../app/lib/auth.php';

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
        $errors[] = 'Pseudonyme invalide (3-30 caractères, lettres, chiffres, _).';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Le mot de passe doit faire au moins 8 caractères.';
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
                $avatar_path = 'uploads/avatars/' . $stored;
            }
        }
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("SELECT id FROM users WHERE LOWER(username) = LOWER(?)");
            $stmt->execute([$username]);
            
            if ($stmt->fetch()) {
                $errors[] = 'Ce pseudonyme est déjà pris.';
                $pdo->rollBack();
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role, created_at) VALUES (?, ?, 'user', NOW())");
                $stmt->execute([$username, $hash]);
                $user_id = (int)$pdo->lastInsertId();

                $stmt = $pdo->prepare("INSERT INTO user_profiles 
                    (user_id, first_name, last_name, age, region, political_view, bio, avatar_path) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                
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
            $errors[] = 'Erreur BDD : ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rejoindre Out-Of-Bounds</title>
    <style>
        :root {
            --neon: #ff6fd8;
            --bg: #0a0a0b;
            --glass: rgba(255, 255, 255, 0.05);
        }

        body {
            background: var(--bg);
            color: #fff;
            font-family: 'Segoe UI', Roboto, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            overflow-x: hidden;
        }

        /* Animation de fond */
        body::before {
            content: '';
            position: fixed;
            width: 300px;
            height: 300px;
            background: var(--neon);
            filter: blur(150px);
            opacity: 0.2;
            top: 10%;
            left: 10%;
            z-index: -1;
            animation: move 20s infinite alternate;
        }

        @keyframes move {
            from { transform: translate(0, 0); }
            to { transform: translate(100vw, 80vh); }
        }

        .register-container {
            width: 100%;
            max-width: 500px;
            padding: 20px;
        }

        .glass-card {
            background: var(--glass);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.5);
        }

        h1 {
            color: var(--neon);
            text-align: center;
            font-size: 2.5rem;
            margin-bottom: 30px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 2px;
            text-shadow: 0 0 15px rgba(255, 111, 216, 0.5);
        }

        .input-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #aaa;
            font-size: 0.9rem;
            font-weight: 600;
        }

        input, select, textarea {
            width: 100%;
            padding: 12px 16px;
            background: rgba(0,0,0,0.3);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            color: #fff;
            font-size: 1rem;
            box-sizing: border-box;
            transition: all 0.3s ease;
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--neon);
            box-shadow: 0 0 10px rgba(255, 111, 216, 0.2);
            background: rgba(0,0,0,0.5);
        }

        .btn-neon {
            width: 100%;
            padding: 16px;
            background: var(--neon);
            color: #000;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 800;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 20px;
            transition: all 0.3s ease;
        }

        .btn-neon:hover {
            transform: translateY(-3px);
            box-shadow: 0 0 30px var(--neon);
            filter: brightness(1.1);
        }

        .alert {
            background: rgba(255, 68, 68, 0.1);
            border: 1px solid #ff4444;
            color: #ff4444;
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 25px;
            font-size: 0.9rem;
        }

        .auth-links {
            text-align: center;
            margin-top: 25px;
            color: #888;
        }

        .auth-links a {
            color: var(--neon);
            text-decoration: none;
            font-weight: 700;
        }

        .row {
            display: flex;
            gap: 15px;
        }

        .row > div { flex: 1; }

        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: var(--bg); }
        ::-webkit-scrollbar-thumb { background: #333; border-radius: 10px; }
    </style>
</head>
<body>

<div class="register-container">
    <div class="glass-card">
        <h1>Inscription</h1>

        <?php if (!empty($errors)): ?>
            <div class="alert">
                <?php foreach ($errors as $err): ?>
                    <div>• <?= htmlspecialchars($err) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">
            <div class="input-group">
                <label>NOM D'UTILISATEUR *</label>
                <input type="text" name="username" value="<?= htmlspecialchars($username) ?>" required placeholder="ex: Neo_01">
            </div>

            <div class="input-group">
                <label>MOT DE PASSE *</label>
                <input type="password" name="password" required placeholder="••••••••">
            </div>

            <div class="row">
                <div class="input-group">
                    <label>PRÉNOM</label>
                    <input type="text" name="first_name" value="<?= htmlspecialchars($first_name) ?>">
                </div>
                <div class="input-group">
                    <label>NOM</label>
                    <input type="text" name="last_name" value="<?= htmlspecialchars($last_name) ?>">
                </div>
            </div>

            <div class="row">
                <div class="input-group">
                    <label>ÂGE</label>
                    <input type="number" name="age" value="<?= htmlspecialchars($age) ?>">
                </div>
                <div class="input-group">
                    <label>RÉGION</label>
                    <input type="text" name="region" value="<?= htmlspecialchars($region) ?>">
                </div>
            </div>

            <div class="input-group">
                <label>ORIENTATION POLITIQUE</label>
                <select name="political_view">
                    <option value="">-- Choisir --</option>
                    <option value="Berdella">Berdella</option>
                    <option value="Mélenchon">Mélenchon</option>
                    <option value="Macron">Macron</option>
                    <option value="Mr Vanneste">Mr Vanneste</option>
                </select>
            </div>

            <div class="input-group">
                <label>PHOTO DE PROFIL</label>
                <input type="file" name="avatar" accept="image/*">
            </div>

            <button type="submit" class="btn-neon">Initialiser la connexion</button>
        </form>

        <div class="auth-links">
            Déjà synchronisé ? <a href="login.php">Connexion</a>
        </div>
    </div>
</div>

</body>
</html>