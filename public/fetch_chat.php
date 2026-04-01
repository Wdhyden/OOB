<?php
// public/fetch_chat.php
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/lib/auth.php';
require_once __DIR__ . '/../app/lib/ranks.php';

if (session_status() === PHP_SESSION_NONE) session_start();
check_auth();

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
    .message-row { display: flex; gap: 12px; margin-bottom: 18px; align-items: flex-start; }
    .msg-bubble { background: rgba(255,255,255,0.02); padding: 12px 16px; border-radius: 0 12px 12px 12px; border: 1px solid rgba(255,255,255,0.05); max-width: 85%; }
    
    .animated-rank-text { 
        display: inline-block; background-size: 200% auto; -webkit-background-clip: text; 
        background-clip: text; -webkit-text-fill-color: transparent; 
        animation: gradient-shift 3s linear infinite; font-weight: 900; 
    }

    .lvl-tag {
        font-family: monospace; font-size: 0.55rem; background: rgba(255,255,255,0.05);
        color: #444; padding: 1px 4px; border-radius: 3px; font-weight: bold;
    }

    /* Effet au survol pour les éléments cliquables */
    .clickable-user {
        cursor: pointer;
        transition: transform 0.2s, filter 0.2s;
    }
    .clickable-user:hover {
        filter: brightness(1.3);
        transform: scale(1.02);
    }
</style>

<?php
try {
    $stmt = $pdo->query("
        SELECT c.*, u.username, p.nickname_color, p.avatar_path, p.rank AS current_user_rank, p.level AS user_level
        FROM global_chat c 
        JOIN users u ON c.user_id = u.id 
        JOIN user_profiles p ON u.id = p.user_id 
        ORDER BY c.created_at ASC LIMIT 100
    ");
    $messages = $stmt->fetchAll();

    foreach($messages as $m): 
        $rk = getRankData($pdo, $m['current_user_rank']);
        
        $c1 = $rk['color'] ?? '#ffffff';
        $c2 = (!empty($rk['color_two'])) ? $rk['color_two'] : adjustBrightness($c1, 60);
        $isAnim = (isset($rk['is_animated']) && $rk['is_animated'] == 1);

        $rankStyle = $isAnim ? "background-image: linear-gradient(90deg, $c1, $c2, $c1);" : "color: $c1;";
        $rankClass = $isAnim ? "animated-rank-text" : "";
        
        $avatar = !empty($m['avatar_path']) ? htmlspecialchars($m['avatar_path']) : 'assets/img/default-avatar.png';
    ?>
        <div class="message-row">
            <img src="/<?= $avatar ?>" 
                 class="clickable-user" 
                 onclick="openMiniProfile(<?= $m['user_id'] ?>)"
                 style="width: 40px; height: 40px; border-radius: 10px; object-fit: cover; border: 1px solid rgba(255,255,255,0.1);">
            
            <div class="msg-bubble">
                <div style="margin-bottom: 5px; display: flex; align-items: center; gap: 8px;">
                    
                    <small class="<?= $rankClass ?>" style="<?= $rankStyle ?> font-size:0.6rem; text-transform:uppercase; letter-spacing:1px;">
                        <?= htmlspecialchars($rk['name']) ?>
                    </small>

                    <span class="lvl-tag">[L.<?= $m['user_level'] ?>]</span>
                    
                    <b class="<?= $rankClass ?> clickable-user" 
                       onclick="openMiniProfile(<?= $m['user_id'] ?>)"
                       style="<?= $rankStyle ?> font-size: 0.9rem;">
                        <?= htmlspecialchars($m['username']) ?>
                    </b>

                    <span style="font-size: 0.6rem; color: #222; margin-left: auto; font-family: monospace;">
                        <?= date('H:i', strtotime($m['created_at'])) ?>
                    </span>
                </div>
                <div style="color: #ccc; font-size: 0.92rem; line-height: 1.4;">
                    <?= nl2br(htmlspecialchars($m['message'])) ?>
                </div>
            </div>
        </div>
    <?php endforeach;
} catch (Exception $e) { echo "Erreur flux."; }
?>