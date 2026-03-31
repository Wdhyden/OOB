<?php
// public/login.php
session_start();
require_once __DIR__ . '/../app/config/database.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if ($username === 'admin') {
        $error = "Utilisateur non autorisé via cette interface";
    } else {
        try {
            // Recherche de l'utilisateur dans la base de données
            $stmt = $pdo->prepare("SELECT id, username, password_hash, role FROM users WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            // Vérification du mot de passe avec password_verify
            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'] ?? 'user';
                
                // Redirection forcée vers le dashboard
                echo "<script>window.location.href='dashboard.php';</script>";
                exit;
            } else {
                $error = "Identifiants incorrects";
            }
        } catch (PDOException $e) {
            $error = "Erreur de connexion à la base de données";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion - Out-Of-Bounds</title>
    <style>
        body {
            background: linear-gradient(135deg, #0a0a0a 0%, #1a1a2e 100%);
            color: #f5f5f5;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            margin: 0;
        }
        .login-box {
            background: rgba(31, 32, 32, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 40px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 25px 70px rgba(0,0,0,0.6);
            border: 1px solid #333;
        }
        h1 {
            color: #ff6fd8;
            text-align: center;
            font-size: 2.5em;
            margin-bottom: 30px;
            font-weight: 800;
            text-shadow: 0 0 10px rgba(255, 111, 216, 0.3);
        }
        .input-group {
            margin-bottom: 20px;
        }
        input {
            width: 100%;
            padding: 15px 20px;
            background: rgba(42, 42, 42, 0.8);
            border: 2px solid #333;
            border-radius: 12px;
            color: #f5f5f5;
            font-size: 16px;
            transition: all 0.3s;
            box-sizing: border-box;
        }
        input:focus {
            outline: none;
            border-color: #ff6fd8;
            box-shadow: 0 0 15px rgba(255, 111, 216, 0.3);
        }
        button.btn-submit {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #ff6fd8, #ff85e4);
            color: #000;
            border: none;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
        }
        button.btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(255, 111, 216, 0.4);
        }
        .error {
            background: rgba(255, 68, 68, 0.1);
            border: 1px solid #ff4444;
            color: #ff4444;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 14px;
        }
        .register-footer {
            text-align: center;
            margin-top: 30px;
            border-top: 1px solid #333;
            padding-top: 20px;
        }
        .register-footer p {
            color: #888;
            font-size: 14px;
            margin-bottom: 15px;
        }
        .btn-register {
            display: block;
            padding: 12px;
            border: 2px solid #ff6fd8;
            border-radius: 12px;
            color: #ff6fd8;
            text-decoration: none;
            font-weight: 700;
            transition: all 0.3s;
        }
        .btn-register:hover {
            background: rgba(255, 111, 216, 0.1);
            box-shadow: 0 0 15px rgba(255, 111, 216, 0.2);
        }
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
            <button type="submit" class="btn-submit">Entrer</button>
        </form>

        <div class="register-footer">
            <p>Nouveau sur Out-Of-Bounds ?</p>
            <a href="register.php" class="btn-register">REJOINDRE LA MATRICE</a>
        </div>
    </div>
</body>
</html>