<?php
// public/get_user_card.php
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/lib/auth.php';
require_once __DIR__ . '/../app/lib/ranks.php';

if (session_status() === PHP_SESSION_NONE) session_start();
check_auth();

$current_viewer_id = $_SESSION['user_id'];
$target_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($target_id === 0) die("ID_INVALID");

try {
    $stmt = $pdo->prepare("
        SELECT u.username, u.created_at, p.* FROM users u 
        JOIN user_profiles p ON u.id = p.user_id 
        WHERE u.id = ?
    ");
    $stmt->execute([$target_id]);
    $u = $stmt->fetch();

    if (!$u) die("UNIT_NOT_FOUND");

    $rk = getRankData($pdo, $u['rank']);
    $theme = $rk['color'] ?? '#00f2ff';
    $is_me = ($current_viewer_id === $target_id);

} catch (Exception $e) { die("INTERCEPTION_ERROR"); }
?>

<style>
    .modal-grid {
        display: grid;
        grid-template-rows: auto 1fr auto;
        background: #080809;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    .modal-header-tech {
        padding: 15px 20px;
        background: #000;
        border-bottom: 1px solid #1a1a1c;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .modal-body-tech {
        padding: 25px;
        display: grid;
        grid-template-columns: 100px 1fr;
        gap: 20px;
    }
    .modal-avatar-tech {
        width: 100px;
        height: 100px;
        border: 1px solid #1a1a1c;
        border-radius: 4px;
        object-fit: cover;
        filter: grayscale(0.5);
    }
    .tech-label {
        font-family: monospace;
        font-size: 0.6rem;
        color: #333;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 4px;
    }
    .tech-value {
        color: #eee;
        font-size: 0.9rem;
        font-weight: bold;
        margin-bottom: 15px;
    }
    .modal-footer-tech {
        padding: 15px;
        background: #000;
        border-top: 1px solid #1a1a1c;
    }
    .btn-modal-action {
        display: block;
        width: 100%;
        padding: 10px;
        text-align: center;
        background: <?= $theme ?>;
        color: #000;
        text-decoration: none;
        font-weight: 900;
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    .close-trigger {
        cursor: pointer;
        color: #444;
        font-size: 0.8rem;
    }
</style>

<div class="modal-grid">
    <div class="modal-header-tech">
        <div style="font-family: monospace; font-size: 0.6rem; color: <?= $theme ?>;">
            ID_INTERCEPT // UNIT_<?= str_pad($u['user_id'], 4, '0', STR_PAD_LEFT) ?>
        </div>
        <div class="close-trigger" onclick="closeModal()">[X]</div>
    </div>

    <div class="modal-body-tech">
        <div>
            <img src="/<?= htmlspecialchars($u['avatar_path'] ?: 'assets/img/default-avatar.png') ?>" class="modal-avatar-tech">
            <div style="margin-top:10px; font-family:monospace; font-size:0.55rem; color:#222; text-align:center;">
                STATUS: <span style="color:<?= $theme ?>;">SYNC</span>
            </div>
        </div>

        <div>
            <div class="tech-label">IDENTIFIER</div>
            <div class="tech-value" style="font-size:1.2rem;"><?= htmlspecialchars($u['username']) ?></div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                <div>
                    <div class="tech-label">RANK_CLASS</div>
                    <div class="tech-value" style="color:<?= $theme ?>;"><?= strtoupper($u['rank']) ?></div>
                </div>
                <div>
                    <div class="tech-label">ACCESS_LVL</div>
                    <div class="tech-value">0<?= $u['level'] ?></div>
                </div>
            </div>

            <div class="tech-label">TRANSMISSION_BIO</div>
            <div style="font-size: 0.75rem; color: #666; line-height: 1.4; font-style: italic;">
                "<?= nl2br(htmlspecialchars($u['bio'] ?: 'No data transmission.')) ?>"
            </div>
        </div>
    </div>

    <div class="modal-footer-tech">
        <?php if($is_me): ?>
            <a href="settings.php" class="btn-modal-action">ACCESS_CONFIG_PANEL</a>
        <?php else: ?>
            <a href="profile.php?id=<?= $u['user_id'] ?>" class="btn-modal-action" style="background:#1a1a1c; color:#888; border:1px solid #333;">VIEW_FULL_DOSSIER</a>
        <?php endif; ?>
    </div>
</div>