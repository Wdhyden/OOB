<?php
// public/post_chat.php
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/lib/auth.php';
require_once __DIR__ . '/../app/lib/ranks.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// 1. Sécurité : Vérification de l'authentification
check_auth();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty(trim($_POST['message']))) {
    
    // --- LOGIQUE DU COOLDOWN (5 secondes) ---
    $cooldown_limit = 5; 
    if (isset($_SESSION['last_post_time'])) {
        $elapsed = time() - $_SESSION['last_post_time'];
        if ($elapsed < $cooldown_limit) {
            $remaining = $cooldown_limit - $elapsed;
            die("ALERTE_SPAM : Veuillez attendre encore " . $remaining . " seconde(s).");
        }
    }

    $user_id = $_SESSION['user_id'];
    $message = trim($_POST['message']);

    try {
        $pdo->beginTransaction();

        // 2. Enregistrement du message
        $stmt = $pdo->prepare("INSERT INTO global_chat (user_id, message, created_at) VALUES (?, ?, NOW())");
        $stmt->execute([$user_id, $message]);

        // 3. LOGIQUE XP (Multiplicateur 1.02)
        $xpGain = 15; 
        $baseXp = 100; 

        $stmtUser = $pdo->prepare("SELECT xp, level FROM user_profiles WHERE user_id = ? FOR UPDATE");
        $stmtUser->execute([$user_id]);
        $profile = $stmtUser->fetch(PDO::FETCH_ASSOC);

        if ($profile) {
            $newXpTotal = $profile['xp'] + $xpGain;
            $currentLevel = $profile['level'];

            // Calcul du seuil pour le passage de niveau
            $totalXpThreshold = 0;
            for ($i = 1; $i <= $currentLevel; $i++) {
                $totalXpThreshold += $baseXp * pow(1.02, $i - 1);
            }

            if ($newXpTotal >= $totalXpThreshold) {
                $currentLevel++;
            }

            // Mise à jour de l'XP et du Niveau UNIQUEMENT
            $update = $pdo->prepare("UPDATE user_profiles SET xp = ?, level = ? WHERE user_id = ?");
            $update->execute([$newXpTotal, $currentLevel, $user_id]);
            
            // NOTE : Le Rank-Up automatique est désactivé ici. 
            // L'utilisateur choisit son grade dans profile.php
        }

        $pdo->commit();

        $_SESSION['last_post_time'] = time();

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            echo "SUCCESS";
        } else {
            header("Location: chat.php");
        }
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        die("ERREUR_CRITIQUE : " . $e->getMessage());
    }
} else {
    header("Location: chat.php");
    exit;
}