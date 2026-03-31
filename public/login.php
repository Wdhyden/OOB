<?php
// public/login.php - REDIRECTION FORCÉE
session_start();
require_once __DIR__ . '/../app/config/database.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    
    if ($username === 'admin') {
        $error = "Utilisateur non autorisé";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, username, password_hash, role FROM users WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($_POST['password'], $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'] ?? 'user';
                
                // REDIRECTION FORCÉE - ignore header() problèmes
                echo "<script>window.location.href='dashboard.php';</script>";
                exit;
            } else {
                $error = "Identifiants incorrects";
            }
        } catch (PDOException $e) {
            $error = "Erreur base de données";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Connexion</title>
    <style>
        body {background: linear-gradient(135deg, #0a0a0a 0%, #1a1a2e 100%); color: #f5f5f5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px;}
        .login-box {background: rgba(31,32,32,0.95); backdrop-filter: blur(20px); border-radius: 24px; padding: 60px; width: 100%; max-width: 420px; box-shadow: 0 25px 70px rgba(0,0,0,0.6); border: 1px solid #333;}
        h1 {color: #ff6fd8; text-align: center; font-size: 2.8em; margin-bottom: 40px; font-weight: 800;}
        .input-group {position: relative; margin-bottom: 25px;}
        input {width: 100%; padding: 20px 24px; background: rgba(42,42,42,0.8); border: 2px solid #333; border-radius: 16px; color: #f5f5f5; font-size: 17px; transition: all 0.3s; box-sizing: border-box;}
        input:focus {outline: none; border-color: #ff6fd8; box-shadow: 0 0 25px rgba(255,111,216,0.3); background: rgba(42,42,42,1);}
        input::placeholder {color: #888;}
        button {width: 100%; padding: 20px; background: linear-gradient(135deg, #ff6fd8, #ff85e4); color: #000; border: none; border-radius: 16px; font-size: 18px; font-weight: 700; cursor: pointer; transition: all 0.3s;}
        button:hover {transform: translateY(-3px); box-shadow: 0 15px 40px rgba(255,111,216,0.5);}
        .error {background: rgba(255,68,68,0.2); border: 1px solid #ff4444; color: #ff4444; padding: 18px; border-radius: 12px; margin: 25px 0; text-align: center; font-weight: 500;}
    </style>
</head>
<body>
    <div class="login-box">
        <h1>🔐 Connexion</h1>
        
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="input-group">
                <input type="text" name="username" placeholder="Nom d'utilisateur" 
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autofocus>
            </div>
            <div class="input-group">
                <input type="password" name="password" placeholder="Mot de passe" required>
            </div>
            <button type="submit">Entrer</button>
        </form>
    </div>
</body>
</html>
