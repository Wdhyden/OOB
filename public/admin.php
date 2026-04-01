<?php
// public/admin.php
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/lib/auth.php';
require_once __DIR__ . '/../app/lib/ranks.php';

if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: text/html; charset=utf-8');
check_auth();
check_admin();

$success_msg = "";
$error_msg = "";

// --- TRAITEMENT DES ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // 1. CRÉATION DE GRADE
    if ($_POST['action'] === 'create_rank') {
        $name = htmlspecialchars($_POST['rank_name']);
        $color = $_POST['rank_color'];
        $color_two = !empty($_POST['rank_color_two']) ? $_POST['rank_color_two'] : null;
        $min_lvl = (int)$_POST['min_lvl'];
        try {
            $stmt = $pdo->prepare("INSERT INTO ranks (name, color, color_two, min_lvl) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $color, $color_two, $min_lvl]);
            $success_msg = "GRADE_FORGÉ_AVEC_SUCCÈS";
        } catch (Exception $e) { $error_msg = "ERREUR_DE_SYNTHÈSE"; }
    }

    // 2. MISE À JOUR D'UN GRADE
    if ($_POST['action'] === 'update_rank') {
        $id = (int)$_POST['rank_id'];
        $name = htmlspecialchars($_POST['rank_name']);
        $color = $_POST['rank_color'];
        $min_lvl = (int)$_POST['min_lvl'];
        $pdo->prepare("UPDATE ranks SET name = ?, color = ?, min_lvl = ? WHERE id = ?")
            ->execute([$name, $color, $min_lvl, $id]);
        $success_msg = "MATRICE_DE_GRADE_MISE_À_JOUR";
    }

    // 3. SUPPRESSION DE GRADE
    if ($_POST['action'] === 'delete_rank') {
        $id = (int)$_POST['rank_id'];
        $pdo->prepare("DELETE FROM ranks WHERE id = ? AND name != 'User'")->execute([$id]);
        $success_msg = "GRADE_DISSOUS_DU_SYSTÈME";
    }

    // 4. MODIFICATION STATS UTILISATEUR + VALIDATION
    if ($_POST['action'] === 'force_user_stats') {
        $target_user_id = (int)$_POST['target_user_id'];
        $new_rank_name = $_POST['new_rank_name'];
        $new_level = (int)$_POST['new_level'];
        $is_valid = isset($_POST['is_validated']) ? 1 : 0;
        
        // Update stats
        $pdo->prepare("UPDATE user_profiles SET level = ?, rank = ? WHERE user_id = ?")
            ->execute([$new_level, $new_rank_name, $target_user_id]);

        // Update validation
        $pdo->prepare("UPDATE users SET is_validated = ? WHERE id = ?")
            ->execute([$is_valid, $target_user_id]);

        $success_msg = "UNIT_STATS_ET_STATUT_SYNCHRONISÉS";
    }

    // 5. SUPPRESSION UTILISATEUR
    if ($_POST['action'] === 'delete_user') {
        $target_id = (int)$_POST['target_user_id'];
        $pdo->prepare("DELETE FROM user_profiles WHERE user_id = ?")->execute([$target_id]);
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$target_id]);
        $success_msg = "UNITÉ_EFFACÉE_DE_LA_GRILLE";
    }
}

$allRanks = getAllRanks($pdo);
$users = $pdo->query("SELECT u.id, u.username, u.is_validated, p.rank, p.level FROM users u JOIN user_profiles p ON u.id = p.user_id ORDER BY u.username ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>OVERLORD_CONTROL // OOB</title>
    <style>
        :root { --neon: #ff4444; --bg: #030304; --panel: #080809; --border: #1a1a1c; --safe: #00ff64; }
        body { background: var(--bg); color: #888; font-family: 'Segoe UI', sans-serif; margin: 0; padding: 0; display: flex; flex-direction: column; height: 100vh; }
        body::before { content: " "; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(rgba(18, 16, 16, 0) 50%, rgba(0, 0, 0, 0.1) 50%), linear-gradient(90deg, rgba(255, 0, 0, 0.02), rgba(0, 255, 0, 0.01), rgba(0, 0, 255, 0.02)); background-size: 100% 4px, 3px 100%; pointer-events: none; z-index: 100; }
        .top-bar { height: 45px; background: #000; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; padding: 0 25px; z-index: 50; }
        .main-layout { display: grid; grid-template-columns: 350px 1fr; flex: 1; gap: 1px; background: var(--border); overflow: hidden; }
        .sidebar, .content { background: var(--panel); padding: 30px; overflow-y: auto; }
        .section-header { font-size: 0.6rem; color: #333; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 25px; display: flex; align-items: center; gap: 8px; }
        .section-header::before { content: ""; width: 4px; height: 4px; background: var(--neon); border-radius: 50%; }
        input, select, button { background: #000; border: 1px solid var(--border); color: #eee; padding: 10px; border-radius: 6px; outline: none; }
        .btn-overlord { background: var(--neon); color: #000; border: none; padding: 15px; border-radius: 10px; font-weight: 900; cursor: pointer; text-transform: uppercase; font-size: 0.7rem; width: 100%; }
        .btn-mini { background: #111; color: #666; border: 1px solid var(--border); padding: 5px 10px; border-radius: 4px; font-size: 0.6rem; cursor: pointer; }
        .btn-danger { color: #ff4444; border-color: #441111; }
        .btn-danger:hover { background: #ff4444; color: #000; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; font-size: 0.55rem; color: #333; text-transform: uppercase; padding: 15px; border-bottom: 1px solid var(--border); }
        td { padding: 12px; border-bottom: 1px solid rgba(255,255,255,0.01); font-size: 0.8rem; }
        .status-msg { font-family: monospace; font-size: 0.7rem; margin-bottom: 20px; display: block; color: var(--safe); }
    </style>
</head>
<body>

    <div class="top-bar">
        <div style="font-size: 0.75rem; font-weight: 900; letter-spacing: 2px; color: var(--neon);">OVERLORD_CORE // ACCESS_LEVEL: ROOT</div>
        <a href="dashboard.php" style="color:#555; text-decoration:none; font-size:0.6rem; font-weight:bold; border:1px solid var(--border); padding:5px 12px; border-radius:4px;">ESC_EXIT</a>
    </div>

    <div class="main-layout">
        
        <div class="sidebar">
            <div class="section-header">RANK_FORGE</div>
            <?php if($success_msg): ?> <span class="status-msg">> <?= $success_msg ?></span> <?php endif; ?>

            <form method="POST" style="display:flex; flex-direction:column; gap:15px; margin-bottom:40px;">
                <input type="hidden" name="action" value="create_rank">
                <input type="text" name="rank_name" placeholder="NOM_DU_GRADE" required>
                <input type="number" name="min_lvl" value="1">
                <input type="color" name="rank_color" value="#ff4444" style="height:40px; width:100%; padding:2px;">
                <button type="submit" class="btn-overlord">FORGER_GRADE</button>
            </form>

            <div class="section-header">EXISTING_RANKS</div>
            <table>
                <?php foreach($allRanks as $rk): ?>
                <tr>
                    <td style="color:<?= $rk['color'] ?>; font-weight:bold;"><?= $rk['name'] ?></td>
                    <td style="font-family:monospace; font-size:0.7rem;">L.<?= $rk['min_lvl'] ?></td>
                    <td style="text-align:right;">
                        <?php if($rk['name'] != 'User'): ?>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('DISSOUDRE LE GRADE ?');">
                            <input type="hidden" name="action" value="delete_rank">
                            <input type="hidden" name="rank_id" value="<?= $rk['id'] ?>">
                            <button type="submit" class="btn-mini btn-danger">DEL</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <div class="content">
            <div class="section-header">UNIT_OPERATIONS_CENTER</div>
            <table>
                <thead>
                    <tr>
                        <th>STATUT</th>
                        <th>IDENTIFIANT</th>
                        <th>NIVEAU</th>
                        <th>GRADE_ATTRIBUÉ</th>
                        <th style="text-align:right;">ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($users as $u): ?>
                    <tr>
                        <form method="POST">
                            <input type="hidden" name="action" value="force_user_stats">
                            <input type="hidden" name="target_user_id" value="<?= $u['id'] ?>">
                            <td>
                                <input type="checkbox" name="is_validated" <?= $u['is_validated'] ? 'checked' : '' ?> onchange="this.form.submit()" style="accent-color:var(--safe);">
                            </td>
                            <td style="color:#fff; font-weight:bold;">@<?= htmlspecialchars($u['username']) ?></td>
                            <td>
                                <input type="number" name="new_level" value="<?= $u['level'] ?>" style="width:55px; font-family:monospace; color:var(--neon);">
                            </td>
                            <td>
                                <select name="new_rank_name" style="font-size:0.7rem;">
                                    <?php foreach($allRanks as $rk): ?>
                                        <option value="<?= $rk['name'] ?>" <?= ($u['rank'] == $rk['name']) ? 'selected' : '' ?>><?= strtoupper($rk['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td style="text-align:right;">
                                <button type="submit" class="btn-mini">SYNC</button>
                        </form>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('EFFACER L\'UNITÉ ?');">
                                    <input type="hidden" name="action" value="delete_user">
                                    <input type="hidden" name="target_user_id" value="<?= $u['id'] ?>">
                                    <button type="submit" class="btn-mini btn-danger">DELETE</button>
                                </form>
                            </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>