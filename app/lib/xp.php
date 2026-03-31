<?php
// app/lib/xp.php

// Calcule l'XP nécessaire pour atteindre un niveau donné
function totalXpForLevel($level) {
    if ($level <= 1) return 0;
    return pow($level, 2) * 100; // Niveau 2 = 400xp, Niveau 3 = 900xp, etc.
}

// Ajoute de l'XP à un utilisateur
function addXp($pdo, $user_id, $amount = 10) {
    // 1. Récupérer l'XP et le niveau actuel
    $stmt = $pdo->prepare("SELECT xp, level FROM user_profiles WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $profile = $stmt->fetch();

    $newXp = $profile['xp'] + $amount;
    $currentLevel = $profile['level'];

    // 2. Vérifier si on monte de niveau
    $nextLevelXp = totalXpForLevel($currentLevel + 1);
    
    if ($newXp >= $nextLevelXp) {
        $currentLevel++;
        // Optionnel : Tu pourrais ici envoyer un message système "LEVEL UP !"
    }

    // 3. Update
    $update = $pdo->prepare("UPDATE user_profiles SET xp = ?, level = ? WHERE user_id = ?");
    $update->execute([$newXp, $currentLevel, $user_id]);
}