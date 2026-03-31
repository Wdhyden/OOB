<?php
// app/lib/ranks.php

function getAllRanks($pdo) {
    return $pdo->query("SELECT * FROM ranks ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
}

function getRankData($pdo, $rankName) {
    $stmt = $pdo->prepare("SELECT * FROM ranks WHERE name = ?");
    $stmt->execute([$rankName]);
    $rank = $stmt->fetch(PDO::FETCH_ASSOC);
    return $rank ? $rank : ['color' => '#888888', 'icon' => '??', 'name' => 'User', 'is_animated' => 0];
}