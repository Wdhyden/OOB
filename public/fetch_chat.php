<?php
// public/fetch_chat.php
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/lib/auth.php';
require_once __DIR__ . '/../app/lib/ranks.php';

if (session_status() === PHP_SESSION_NONE) session_start();

check_auth();
check_validation($pdo);

/**
 * Ajuste la luminosité pour l'animation monochrome
 */
function adjustBrightness($hex, $steps) {
    $steps = max(-255, min(255, $steps));
    $hex = str_replace('#', '', $hex);
    if (strlen($hex) == 3) {
        $hex = str_repeat(substr($hex, 0, 1), 2).str_repeat(substr($hex, 1, 1), 2).str_repeat(substr($hex, 2, 1), 2);
    }
    $color_parts = str_split($hex, 2);
    $return = '#';
    foreach ($color_parts as $color) {
        $color = hexdec($color);
        $color = max(0, min(255, $color + $steps));
        $return .= str_pad(dechex($color), 2, '0', STR_PAD_LEFT);
    }
    return $return;
}
?>

<style>
    @keyframes gradient-shift {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }

    .message-row {
        display: flex;
        gap: 12px;
        margin-bottom: 20px;
        align-items: flex-start;
        animation: fadeIn 0.3s ease;
    }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }

    .msg-bubble {
        background: rgba(255,255,255,0.03); 
        padding: 12px 16px;
        border-radius: 0 12px 12px 12px; 
        border: 1px solid rgba(255,255,255,0.05);
        max-width: 85%;
        position: relative;
    }

    .animated-rank-text {
        display: inline-block; 
        background-size: 200% auto;
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
        animation: gradient-shift 3s linear infinite;
        font-weight: bold;
    }

    /* Barre XP minimaliste */
    .xp-container {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-top: 4px;
    }
    .xp-bar-bg {
        width: 80px;
        height: 2px;
        background: rgba(255,255,255,0.05);
        border-radius: 4px;
        overflow: hidden;
    }
    .xp-bar-fill {
        height: 100%;
        transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);
    }
</style>

<?php
try {
    $stmt = $pdo->query("
        SELECT c.*, u.username, p.nickname_color, p.avatar_path, p.rank, p.xp, p.level 
        FROM global_chat c 
        JOIN users u ON c.user_id = u.id 
        JOIN user_profiles p ON u.id = p.user_id 
        ORDER BY c.created_at ASC 
        LIMIT 100
    ");
    $messages = $stmt->fetchAll();

    foreach($messages as $m): 
        $rk = getRankData($pdo, $m['rank']);
        
        // --- COULEURS ---
        $c1 = $rk['color'];
        $c2 = (!empty($rk['color_two'])) ? $rk['color_two'] : adjustBrightness($c1, 60);

        // Styles Rank & Pseudo
        $isAnim = ($rk['is_animated'] == 1);
        $dynamicStyle = $isAnim ? "background-image: linear-gradient(90deg, $c1, $c2, $c1);" : "color: $c1;";
        $dynamicClass = $isAnim ? "animated-rank-text" : "";

        $nameStyle = ($rk['name'] === 'User') ? "color: " . htmlspecialchars($m['nickname_color'] ?? '#ffffff') . ";" : $dynamicStyle;
        $nameClass = ($rk['name'] === 'User') ? "" : $dynamicClass;

        // --- LOGIQUE XP 1.2x ---
        $lvl = $m['level'] ?? 1;
        $xpTotal = $m['xp'] ?? 0;
        $base = 100;
        
        // XP cumulée au début du niveau actuel
        $xpStartCurrent = 0;
        for ($i = 1; $i < $lvl; $i++) {
            $xpStartCurrent += $base * pow(1.2, $i - 1);
        }
        
        // XP nécessaire pour le niveau suivant (XP cumulée totale)
        $xpForNextLevel = $base * pow(1.2, $lvl - 1);
        $xpGoalTotal = $xpStartCurrent + $xpForNextLevel;

        // Calcul du pourcentage dans le niveau actuel
        $xpInLevel = $xpTotal - $xpStartCurrent;
        $percent = ($xpInLevel / $xpForNextLevel) * 100;
        $percent = max(0, min(100, $percent));

        $avatar = !empty($m['avatar_path']) ? htmlspecialchars($m['avatar_path']) : 'assets/img/default-avatar.png';
    ?>
        <div class="message-row">
            <img src="/<?= $avatar ?>" style="width: 40px; height: 40px; border-radius: 8px; object-fit: cover; border: 1px solid rgba(255,255,255,0.1); background: #111;">
            
            <div class="msg-bubble">
                <div style="margin-bottom: 6px; display: flex; justify-content: space-between; align-items: flex-start; gap: 20px;">
                    <div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <small class="<?= $dynamicClass ?>" style="<?= $dynamicStyle ?> font-size:0.65rem; text-transform:uppercase; letter-spacing:1px; font-weight:bold;">
                                <?= htmlspecialchars($rk['name']) ?>
                            </small>
                            <b class="<?= $nameClass ?>" style="<?= $nameStyle ?> font-size: 0.85rem; letter-spacing: 0.3px;">
                                <?= htmlspecialchars($m['username']) ?>
                            </b>
                        </div>
                        
                        <div class="xp-container">
                            <span style="font-size: 0.55rem; color: #444; font-family: monospace; font-weight: 800;">LVL.<?= $lvl ?></span>
                            <div class="xp-bar-bg">
                                <div class="xp-bar-fill" style="width: <?= $percent ?>%; background: <?= $c1 ?>; box-shadow: 0 0 8px <?= $c1 ?>88;"></div>
                            </div>
                        </div>
                    </div>
                    
                    <span style="font-size: 0.6rem; color: #333; font-family: monospace; margin-top: 2px;">
                        <?= date('H:i', strtotime($m['created_at'])) ?>
                    </span>
                </div>

                <div style="color: #ddd; font-size: 0.92rem; line-height: 1.5; word-break: break-word; letter-spacing: 0.2px;">
                    <?= nl2br(htmlspecialchars($m['message'])) ?>
                </div>
            </div>
        </div>
    <?php endforeach;

} catch (Exception $e) {
    echo '<div style="color:#ff4444; padding:10px; font-family:monospace; font-size:0.7rem;">[ SYSTEM_FAILURE ] : Liaison instable.</div>';
}
?>