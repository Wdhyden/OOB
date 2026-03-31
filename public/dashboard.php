<?php
// public/dashboard.php - VERSION STABLE
session_start();

// DEBUG : Affiche session (à supprimer après)
echo "<pre style='position:fixed;top:10px;right:10px;background:black;color:lime;padding:10px;z-index:999'>";
echo "SESSION:\n";
var_dump($_SESSION);
echo "</pre>";

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
    <style>
        body { 
            background: #0a0a0a; 
            color: #f5f5f5; 
            font-family: Arial; 
            padding: 50px; 
        }
        .stats { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
            gap: 20px; 
            margin: 20px 0; 
        }
        .stat { 
            background: #1f2020; 
            padding: 20px; 
            border-radius: 12px; 
            text-align: center; 
        }
        a { 
            color: #ff6fd8; 
            text-decoration: none; 
            padding: 10px 20px; 
            background: #1f2020; 
            border-radius: 8px; 
            margin: 5px; 
            display: inline-block; 
        }
    </style>
</head>
<body>
    <h1>🚀 Dashboard Community App</h1>
    <p>✅ Connecté : <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></p>
    
    <div class="stats">
        <div class="stat">
            <h2>💬 Chat</h2>
            <a href="chat.php">Rejoindre</a>
        </div>
        <div class="stat">
            <h2>📨 Messages</h2>
            <a href="private_messages.php">Ouvrir</a>
        </div>
        <div class="stat">
            <h2>📁 Drive</h2>
            <a href="drive.php">Gérer</a>
        </div>
        <div class="stat">
            <h2>👤 Profil</h2>
            <a href="profile.php">Voir</a>
        </div>
    </div>
    
    <a href="?logout=1">🔓 Déconnexion</a>
    
    <?php if (isset($_GET['logout'])): ?>
        <?php 
        session_destroy(); 
        header('Location: login.php'); 
        exit; 
        ?>
    <?php endif; ?>
</body>
</html>
