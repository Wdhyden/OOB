<?php
// app/lib/ranks.php

/**
 * Récupère tous les grades configurés en base de données
 * Utilisé pour le listing dans l'administration
 */
function getAllRanks($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM ranks ORDER BY min_lvl ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Récupère les grades qu'un utilisateur a débloqués selon son niveau
 * Utilisé pour le sélecteur dans le profil ou les paramètres
 */
function getUnlockedRanks($pdo, $user_level) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM ranks WHERE min_lvl <= ? ORDER BY min_lvl ASC");
        $stmt->execute([$user_level]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Récupère les données d'un grade spécifique par son nom
 */
function getRankData($pdo, $rankName) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM ranks WHERE name = ? LIMIT 1");
        $stmt->execute([$rankName]);
        $rank = $stmt->fetch(PDO::FETCH_ASSOC);

        // Valeurs par défaut si le grade n'existe pas pour éviter un crash
        if (!$rank) {
            return [
                'name' => $rankName,
                'color' => '#ffffff',
                'color_two' => null,
                'is_animated' => 0,
                'min_lvl' => 1
            ];
        }
        return $rank;
    } catch (Exception $e) {
        return ['name' => 'User', 'color' => '#eee', 'min_lvl' => 1];
    }
}

/**
 * Détermine quel est le prochain grade à atteindre
 * Utilisé pour l'affichage de l'objectif dans le profil
 */
function levelsUntilNextRank($pdo, $current_lvl) {
    try {
        $stmt = $pdo->prepare("SELECT name, min_lvl FROM ranks WHERE min_lvl > ? ORDER BY min_lvl ASC LIMIT 1");
        $stmt->execute([$current_lvl]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Synchronise le grade d'un utilisateur en fonction de son niveau actuel
 * Utile après un "Force Level" dans l'admin ou un gain d'XP
 */
function checkRankUp($pdo, $user_id, $current_lvl) {
    try {
        // On cherche le grade le plus élevé correspondant au niveau
        $stmt = $pdo->prepare("SELECT name FROM ranks WHERE min_lvl <= ? ORDER BY min_lvl DESC LIMIT 1");
        $stmt->execute([$current_lvl]);
        $targetRankName = $stmt->fetchColumn();

        if ($targetRankName) {
            $update = $pdo->prepare("UPDATE user_profiles SET rank = ? WHERE user_id = ?");
            $update->execute([$targetRankName, $user_id]);
            return true;
        }
    } catch (Exception $e) {
        error_log("Erreur checkRankUp: " . $e->getMessage());
    }
    return false;
}

/**
 * Calcule l'XP nécessaire pour atteindre un niveau spécifique
 * (Utile si tu veux afficher la progression totale)
 */
function getXpForLevel($level) {
    $baseXp = 100;
    $totalXp = 0;
    for ($i = 1; $i < $level; $i++) {
        $totalXp += $baseXp * pow(1.02, $i - 1);
    }
    return round($totalXp);
}