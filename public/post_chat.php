<?php
// public/post_chat.php
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/lib/auth.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// 1. Sécurité : Vérification de l'authentification
check_auth();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty(trim($_POST['message']))) {
    $user_id = $_SESSION['user_id'];
    $message = trim($_POST['message']);

    try {
        // Début de la transaction pour s'assurer que le message ET l'XP sont enregistrés
        $pdo->beginTransaction();

        // 2. Insertion du message dans la table global_chat
        $stmt = $pdo->prepare("INSERT INTO global_chat (user_id, message, created_at) VALUES (?, ?, NOW())");
        $stmt->execute([$user_id, $message]);

        // 3. LOGIQUE XP EXPONENTIELLE (1.2x)
        $xpGain = 15; // Points d'XP gagnés par message
        $baseXp = 100; // XP de base pour le niveau 1 -> 2

        // Récupération du profil actuel
        $stmtUser = $pdo->prepare("SELECT xp, level FROM user_profiles WHERE user_id = ? FOR UPDATE");
        $stmtUser->execute([$user_id]);
        $profile = $stmtUser->fetch(PDO::FETCH_ASSOC);

        if ($profile) {
            $newXpTotal = $profile['xp'] + $xpGain;
            $currentLevel = $profile['level'];

            // Calcul du seuil cumulé pour atteindre le NIVEAU SUIVANT
            // Formule : Somme de (base * 1.2^(n-1)) pour n allant de 1 à currentLevel
            $totalXpThreshold = 0;
            for ($i = 1; $i <= $currentLevel; $i++) {
                $totalXpThreshold += $baseXp * pow(1.2, $i - 1);
            }

            // Si l'XP totale dépasse le seuil du niveau suivant
            if ($newXpTotal >= $totalXpThreshold) {
                $currentLevel++;
            }

            // Mise à jour des statistiques du membre
            $update = $pdo->prepare("UPDATE user_profiles SET xp = ?, level = ? WHERE user_id = ?");
            $update->execute([$newXpTotal, $currentLevel, $user_id]);
        }

        $pdo->commit();

        // 4. Redirection vers le chat
        header("Location: chat.php");
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        die("ERREUR_TRANSMISSION : " . $e->getMessage());
    }
} else {
    // Si message vide ou accès direct, retour au chat
    header("Location: chat.php");
    exit;
}