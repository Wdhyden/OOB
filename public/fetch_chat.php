<?php
// public/fetch_chat.php
require_once __DIR__ . '/../app/config/database.php';

// --- STYLES DES ANIMATIONS (Inclus pour l'injection AJAX) ---
?>
<style>
    /* Animation ADMIN : Arc-en-ciel */
    @keyframes admin-rainbow {
        0% { color: #ff0000; text-shadow: 0 0 5px #ff0000; }
        33% { color: #00f2ff; text-shadow: 0 0 5px #00f2ff; }
        66% { color: #00ff64; text-shadow: 0 0 5px #00ff64; }
        100% { color: #ff0000; text-shadow: 0 0 5px #ff0000; }
    }
    .admin-glow { animation: admin-rainbow 3s linear infinite; font-weight: 900 !important; }

    /* Animation VIP : Pulsation Bleue */
    @keyframes vip-blue {
        0% { color: #00d4ff; text-shadow: 0 0 7px rgba(0, 212, 255, 0.5); }
        50% { color: #0044ff; text-shadow: 0 0 7px rgba(0, 68, 255, 0.5); }
        100% { color: #00d4ff; text-shadow: 0 0 7px rgba(0, 212, 255, 0.5); }
    }
    .vip-glow { animation: vip-blue 2.5s ease-in-out infinite; font-weight: 800 !important; }

    /* Structure des lignes de message */
    .message-row {
        display: flex;
        gap: 12px;
        margin-bottom: 15px;
        align-items: flex-start;
        animation: fadeIn 0.3s ease;
    }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
</style>

<?php
try {
    // Récupération des 100 derniers messages
    $stmt = $pdo->query("
        SELECT c.*, u.username, u.role, p.nickname_color, p.avatar_path 
        FROM global_chat c 
        JOIN users u ON c.user_id = u.id 
        JOIN user_profiles p ON u.id = p.user_id 
        ORDER BY c.created_at ASC 
        LIMIT 100
    ");
    $messages = $stmt->fetchAll();

    foreach($messages as $m): 
        // Logique de distinction des rôles
        $role = $m['role'];
        $isAdmin = ($role === 'admin');
        $isVIP   = ($role === 'vip');
        
        // Attribution de la classe CSS
        $nameClass = '';
        if ($isAdmin) {
            $nameClass = 'admin-glow';
        } elseif ($isVIP) {
            $nameClass = 'vip-glow';
        }

        // Couleur fixe uniquement si pas d'animation de rôle
        $nameStyle = ($isAdmin || $isVIP) ? '' : 'style="color:'.$m['nickname_color'].'"';
        
        // Sécurité Avatar (40x40 strict)
        $avatar = !empty($m['avatar_path']) ? htmlspecialchars($m['avatar_path']) : 'assets/img/default-avatar.png';
    ?>
        <div class="message-row">
            
            <img src="/<?= $avatar ?>" 
                 style="width: 40px; height: 40px; min-width: 40px; min-height: 40px; 
                        max-width: 40px; max-height: 40px; border-radius: 10px; 
                        object-fit: cover; border: 1px solid rgba(255,255,255,0.1); 
                        flex-shrink: 0; background: #111;">
            
            <div class="msg-bubble" style="background: rgba(255,255,255,0.03); padding: 10px 15px; 
                                          border-radius: 0 12px 12px 12px; border: 1px solid rgba(255,255,255,0.05); 
                                          max-width: 85%;">
                
                <div style="margin-bottom: 4px; display: flex; justify-content: space-between; align-items: center; gap: 15px;">
                    <b class="<?= $nameClass ?>" <?= $nameStyle ?> style="font-size: 0.85rem; letter-spacing: 0.5px;">
                        <?= htmlspecialchars($m['username']) ?>
                        <?php 
                            if($isAdmin) echo ' <small style="font-size:0.6rem; opacity:0.8;">[STAFF]</small>';
                            elseif($isVIP) echo ' <small style="font-size:0.6rem; opacity:0.8;">[VIP]</small>';
                        ?>
                    </b>
                    
                    <span style="font-size: 0.65rem; color: #444;">
                        <?= date('H:i', strtotime($m['created_at'])) ?>
                    </span>
                </div>

                <div style="color: #ddd; font-size: 0.92rem; line-height: 1.4; word-break: break-word;">
                    <?= nl2br(htmlspecialchars($m['message'])) ?>
                </div>
            </div>
        </div>
    <?php endforeach;

} catch (Exception $e) {
    echo '<div style="color:#ff4444; padding:10px;">SIGNAL_ERROR: Flux corrompu.</div>';
}
?>