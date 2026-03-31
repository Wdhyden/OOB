<?php
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/lib/auth.php';
require_once __DIR__ . '/../app/lib/ranks.php';

// Sécurité : Vérification Admin
check_auth();
check_admin();

$success_msg = "";
$error_msg = "";

// --- TRAITEMENT DES ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // 1. CRÉATION D'UN RANG
    if ($_POST['action'] === 'create_rank') {
        $name = htmlspecialchars($_POST['rank_name']);
        $color = $_POST['rank_color'];
        $color_two = !empty($_POST['rank_color_two']) ? $_POST['rank_color_two'] : null;
        $is_animated = isset($_POST['is_animated']) ? 1 : 0;

        try {
            $stmt = $pdo->prepare("INSERT INTO ranks (name, color, color_two, icon, is_animated) VALUES (?, ?, ?, '', ?)");
            $stmt->execute([$name, $color, $color_two, $is_animated]);
            $success_msg = "Le rang '$name' a été forgé avec succès.";
        } catch (Exception $e) {
            $error_msg = "Erreur : Ce nom de rang existe déjà ou la base est mal configurée.";
        }
    }

    // 2. SUPPRESSION D'UN RANG
    if ($_POST['action'] === 'delete_rank') {
        $rank_id = (int)$_POST['rank_id'];
        
        $stmt = $pdo->prepare("SELECT name FROM ranks WHERE id = ?");
        $stmt->execute([$rank_id]);
        $rName = $stmt->fetchColumn();

        if ($rName && $rName !== 'User') {
            $pdo->prepare("UPDATE user_profiles SET rank = 'User' WHERE rank = ?")->execute([$rName]);
            $pdo->prepare("DELETE FROM ranks WHERE id = ?")->execute([$rank_id]);
            $success_msg = "Rang supprimé. Les membres rattachés sont repassés en 'User'.";
        }
    }

    // 3. ATTRIBUTION DU RANG À UN UTILISATEUR
    if ($_POST['action'] === 'update_user_rank') {
        $user_id = (int)$_POST['target_id'];
        $new_rank = $_POST['new_rank'];
        $pdo->prepare("UPDATE user_profiles SET rank = ? WHERE user_id = ?")->execute([$new_rank, $user_id]);
        $success_msg = "Grade mis à jour pour l'utilisateur.";
    }
}

// Récupération des données
$users = $pdo->query("SELECT u.id, u.username, p.rank FROM users u JOIN user_profiles p ON u.id = p.user_id ORDER BY u.username ASC")->fetchAll();
$allRanks = getAllRanks($pdo);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>OOB // OVERLORD_PANEL</title>
    <style>
        :root { --neon: #ff4444; --bg: #050506; --panel: rgba(255,255,255,0.02); --border: #222; }
        body { background: var(--bg); color: #eee; font-family: 'Segoe UI', sans-serif; padding: 40px; margin: 0; }
        h1 { letter-spacing: 5px; text-transform: uppercase; border-left: 4px solid var(--neon); padding-left: 15px; font-size: 1.5rem; margin-bottom: 40px; }
        h2 { font-size: 0.8rem; color: #555; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 20px; }
        
        .container { max-width: 1100px; margin: 0 auto; }
        .section { background: var(--panel); border: 1px solid var(--border); padding: 25px; border-radius: 15px; margin-bottom: 30px; }
        
        input, select, button { background: #000; border: 1px solid #333; color: #fff; padding: 10px; border-radius: 5px; outline: none; }
        button { background: var(--neon); border: none; font-weight: bold; cursor: pointer; transition: 0.3s; }
        button:hover { opacity: 0.8; box-shadow: 0 0 15px rgba(255,68,68,0.3); }
        button.del { background: transparent; border: 1px solid var(--neon); color: var(--neon); padding: 5px 10px; font-size: 0.7rem; }

        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; font-size: 0.7rem; color: #444; text-transform: uppercase; padding: 10px; }
        td { padding: 15px 10px; border-bottom: 1px solid #111; vertical-align: middle; }

        .msg { padding: 15px; border-radius: 10px; margin-bottom: 20px; font-size: 0.9rem; font-weight: bold; }
        .success { background: rgba(0,255,100,0.1); color: #00ff64; border: 1px solid #00ff64; }
        .error { background: rgba(255,0,0,0.1); color: #ff4444; border: 1px solid #ff4444; }
    </style>
</head>
<body>

<div class="container">
    <h1>OVERLORD_PANEL</h1>

    <?php if($success_msg): ?> <div class="msg success"><?= $success_msg ?></div> <?php endif; ?>
    <?php if($error_msg): ?> <div class="msg error"><?= $error_msg ?></div> <?php endif; ?>

    <div class="section">
        <h2>+ FORGER_UN_NOUVEAU_RANG</h2>
        <form method="POST" style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr auto; gap: 15px; align-items: end;">
            <input type="hidden" name="action" value="create_rank">
            
            <div>
                <label style="display:block; font-size:0.6rem; margin-bottom:5px;">NOM DU RANG</label>
                <input type="text" name="rank_name" placeholder="ex: Figma Lover" required style="width:100%;">
            </div>
            <div>
                <label style="display:block; font-size:0.6rem; margin-bottom:5px;">COULEUR 1 (PRINCIPALE)</label>
                <input type="color" name="rank_color" value="#ff4444" style="width:100%; height:40px; cursor:pointer;">
            </div>
            <div>
                <label style="display:block; font-size:0.6rem; margin-bottom:5px;">COULEUR 2 (BICO / OPTION)</label>
                <input type="color" name="rank_color_two" value="#00f2ff" style="width:100%; height:40px; cursor:pointer;">
            </div>
            <div style="padding-bottom:12px; text-align:center;">
                <label style="font-size:0.7rem; cursor:pointer;"><input type="checkbox" name="is_animated"> ANIMATION_RGB</label>
            </div>
            <button type="submit" style="height:42px;">CRÉER</button>
        </form>
    </div>

    <div class="section">
        <h2>RANGS_CONFIGURÉS</h2>
        <table>
            <thead>
                <tr><th>Nom du Grade</th><th>Propriétés</th><th>Action</th></tr>
            </thead>
            <tbody>
                <?php foreach($allRanks as $rk): ?>
                <tr>
                    <td>
                        <span style="color: <?= $rk['color'] ?>; font-weight: bold; text-transform: uppercase; letter-spacing: 1px;">
                            <?= htmlspecialchars($rk['name']) ?>
                        </span>
                    </td>
                    <td style="font-size: 0.8rem; color: #666;">
                        <?= $rk['is_animated'] ? '? Animé' : '? Statique' ?>
                        <?= !empty($rk['color_two']) ? ' | ?? Bicolore' : ' | ?? Monochrome' ?>
                    </td>
                    <td>
                        <?php if($rk['name'] !== 'User'): ?>
                        <form method="POST" onsubmit="return confirm('Supprimer ce grade ?');">
                            <input type="hidden" name="action" value="delete_rank">
                            <input type="hidden" name="rank_id" value="<?= $rk['id'] ?>">
                            <button type="submit" class="del">SUPPRIMER</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2>ATTRIBUTION_AUX_MEMBRES</h2>
        <table>
            <thead>
                <tr><th>Utilisateur</th><th>Rang Actuel</th><th>Modifier Grade</th></tr>
            </thead>
            <tbody>
                <?php foreach($users as $u): ?>
                <tr>
                    <td style="font-weight:bold; letter-spacing:0.5px;"><?= htmlspecialchars($u['username']) ?></td>
                    <td>
                        <span style="font-size:0.75rem; color:#888; background:rgba(255,255,255,0.05); padding:4px 8px; border-radius:4px;">
                            <?= htmlspecialchars($u['rank']) ?>
                        </span>
                    </td>
                    <td>
                        <form method="POST">
                            <input type="hidden" name="action" value="update_user_rank">
                            <input type="hidden" name="target_id" value="<?= $u['id'] ?>">
                            <select name="new_rank" onchange="this.form.submit()" style="width:100%; cursor:pointer;">
                                <?php foreach($allRanks as $rk): ?>
                                    <option value="<?= $rk['name'] ?>" <?= ($u['rank'] == $rk['name']) ? 'selected' : '' ?>>
                                        <?= $rk['name'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
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