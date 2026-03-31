<?php
// public/register.php
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/lib/security.php';
require_once __DIR__ . '/../app/lib/auth.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username   = trim($_POST['username'] ?? '');
    $password   = $_POST['password'] ?? '';
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name'] ?? '');
    $gender     = trim($_POST['gender'] ?? '');
    $age        = (int)($_POST['age'] ?? 0);
    $region     = trim($_POST['region'] ?? '');
    $political  = $_POST['political_view'] ?? null;
    $bio        = trim($_POST['bio'] ?? '');

    if ($username === '' || !preg_match('/^[A-Za-z0-9_]{3,30}$/', $username)) {
        $errors[] = 'Pseudonyme invalide.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Mot de passe trop court.';
    }
    if ($first_name === '' || $last_name === '') {
        $errors[] = 'Nom et prénom obligatoires.';
    }
    if (!in_array($political, ['Berdella','Mélenchon','Macron','Mr Vanneste'], true)) {
        $errors[] = 'Orientation politique invalide.';
    }

    // Avatar optionnel
    $avatar_path = null;
    if (!empty($_FILES['avatar']['name'])) {
        $err = validate_uploaded_file(
            $_FILES['avatar'],
            ['image/jpeg','image/png','image/gif'],
            2 * 1024 * 1024
        );
        if ($err) {
            $errors[] = $err;
        } else {
            $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
            $stored = uniqid('avatar_', true) . '.' . $ext;
            $targetDir = __DIR__ . '/../storage/uploads/avatars/';
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }
            move_uploaded_file($_FILES['avatar']['tmp_name'], $targetDir . $stored);
            $avatar_path = 'uploads/avatars/' . $stored;
        }
    }

    if (!$errors) {
        try {
            $pdo->beginTransaction();

            $username_lc = mb_strtolower($username, 'UTF-8');
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username_lc = ?");
            $stmt->execute([$username_lc]);
            if ($stmt->fetch()) {
                $errors[] = 'Ce pseudonyme est déjà pris.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $pdo->prepare("INSERT INTO users (username, username_lc, password_hash, role) 
                                       VALUES (?, ?, ?, 'user')");
                $stmt->execute([$username, $username_lc, $hash]);
                $user_id = (int)$pdo->lastInsertId();

                $stmt = $pdo->prepare("INSERT INTO user_profiles
                    (user_id, first_name, last_name, gender, age, region, political_view, bio, avatar_path)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $user_id, $first_name, $last_name, $gender ?: null,
                    $age ?: null, $region ?: null, $political, $bio ?: null, $avatar_path
                ]);

                $pdo->commit();
                login($username, $password);
                header('Location: dashboard.php');
                exit;
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Erreur interne, réessayez plus tard.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Inscription</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="theme-dark">
<div class="auth-container">
    <div class="card">
        <h1>Créer un compte</h1>

        <?php if ($errors): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $err): ?>
                    <p><?= e($err) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">
            <label>Pseudonyme</label>
            <input type="text" name="username" required>

            <label>Mot de passe</label>
            <input type="password" name="password" required>

            <label>Nom</label>
            <input type="text" name="last_name" required>

            <label>Prénom</label>
            <input type="text" name="first_name" required>

            <label>Genre</label>
            <input type="text" name="gender">

            <label>Âge</label>
            <input type="number" name="age" min="13">

            <label>Région</label>
            <input type="text" name="region">

            <label>Orientation politique</label>
            <select name="political_view" required>
                <option value="">-- Choisir --</option>
                <option value="Berdella">Berdella</option>
                <option value="Mélenchon">Mélenchon</option>
                <option value="Macron">Macron</option>
                <option value="Mr Vanneste">Mr Vanneste</option>
            </select>

            <label>Biographie</label>
            <textarea name="bio"></textarea>

            <label>Photo de profil</label>
            <input type="file" name="avatar" accept="image/*">

            <button type="submit" class="btn-primary">S'inscrire</button>
        </form>
    </div>
</div>
<script src="/assets/js/theme.js"></script>
</body>
</html>
