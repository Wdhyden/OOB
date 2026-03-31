<?php
// public/admin.php
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/lib/auth.php';

// Sécurité : Vérifie si l'utilisateur est admin
check_admin();

$user_id = $_SESSION['user_id'];
$success_msg = "";

// --- LOGIQUE DES ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['target_id'])) {
    $target_id = (int)$_POST['target_id'];

    try {
        switch ($_POST['action']) {
            case 'approve':
                $pdo->prepare("UPDATE users SET is_pending = 0 WHERE id = ?")->execute([$target_id]);
                $success_msg = "Accès autorisé pour le sujet #$target_id.";
                break;

            case 'make_vip':
                $pdo->prepare("UPDATE users SET role = 'vip' WHERE id = ?")->execute([$target_id]);
                $success_msg = "Sujet #$target_id promu au rang VIP.";
                break;

            case 'make_user':
                $pdo->prepare("UPDATE users SET role = 'user' WHERE id = ?")->execute([$target_id]);
                $success_msg = "Sujet #$target_id rétrogradé au rang USER.";
                break;

            case 'terminate':
                // Empêche de se supprimer soi-même
                if ($target_id !== $user_id) {
                    $pdo->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'")->execute([$target_id]);
                    $success_msg = "Sujet #$target_id effacé des archives.";
                }
                break;
        }
    } catch (Exception $e) {
        $error_msg = "Erreur système : " . $e->getMessage();
    }
}

// --- RÉCUPÉRATION DE LA LISTE DES MEMBRES ---
$users = $pdo->query("
    SELECT u.id, u.username, u.role, u.is_pending, u.created_at, p.nickname_color 
    FROM users u 
    JOIN user_profiles p ON u.id = p.user_id 
    ORDER BY u.is_pending DESC, u.created_at DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>OOB // OVERLORD PANEL</title>
    <style>
        :root {
            --bg: #050506;
            --neon: #ff4444; /* Rouge pour l'admin */
            --vip: #00d4ff;  /* Bleu pour le VIP */
            --border: rgba(255, 255, 255, 0.08);
        }

        body { background: var(--bg); color: #eee; font-family: 'Segoe UI', sans-serif; padding: 40px; margin: 0; }
        .container { max-width: 1100px; margin: 0 auto; }

        header { border-bottom: 1px solid var(--border); padding-bottom: 20px; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; }
        h1 { letter-spacing: 5px; text-transform: uppercase; font-size: 1.2rem; color: var(--neon); margin: 0; }
        .btn-back { color: #555; text-decoration: none; font-size: 0.8rem; font-weight: bold; }
        .btn-back:hover { color: #fff; }

        .alert { background: rgba(0, 255, 100, 0.1); border: 1px solid #00ff64; color: #00ff64; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem; }

        /* Table Design */
        table { width: 100%; border-collapse: collapse; background: rgba(255,255,255,0.02); border-radius: 12px; overflow: hidden; }
        th { background: rgba(255,255,255,0.03); text-align: left; padding: 15px; font-size: 0.7rem; color: #444; text-transform: uppercase; }
        td { padding: 15px; border-bottom: 1px solid var(--border); font-size: 0.9rem; }

        /* Badges */
        .badge { font-size: 0.65rem; font-weight: 900; padding: 3px 8px; border-radius: 4px; text-transform: uppercase; }
        .bg-pending { background: rgba(255, 170, 0, 0.1); color: #ffaa00; border: 1px solid #ffaa00; }
        .bg-vip { background: rgba(0, 212, 255, 0.1); color: var(--vip); border: 1px solid var(--vip); }
        .bg-admin { background: rgba(255, 0, 0, 0.1); color: var(--neon); border: 1px solid var(--neon); }

        /* Buttons */
        .btn { padding: 6px 12px; border-radius: 5px; border: none; font-size: 0.7rem; font-weight: bold; cursor: pointer; transition: 0.2s; }
        .btn-approve { background: #00ff64; color: #000; }
        .btn-promote { background: var(--vip); color: #000; }
        .btn-demote { background: #555; color: #fff; }
        .btn-delete { background: transparent; border: 1px solid #ff4444; color: #ff4444; }
        .btn-delete:hover { background: #ff4444; color: #fff; }

        .actions-flex { display: flex; gap: 8px; }
    </style>
</head>
<body>

<div class="container">
    <header>
        <h1>OVERLORD_CONTROL_PANEL</h1>
        <a href="dashboard.php" class="btn-back">← RETOUR_HUB</a>
    </header>

    <?php if($success_msg): ?> <div class="alert"><?= $success_msg ?></div> <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>Sujet</th>
                <th>Statut / Rôle</th>
                <th>Rejoint le</th>
                <th>Actions de Contrôle</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($users as $u): ?>
            <tr>
                <td style="font-weight:bold; color:<?= $u['nickname_color'] ?>">
                    <?= htmlspecialchars($u['username']) ?>
                    <?php if($u['id'] == $user_id): ?> <small style="color:#555">(Moi)</small> <?php endif; ?>
                </td>

                <td>
                    <?php if($u['is_pending']): ?>
                        <span class="badge bg-pending">En Attente</span>
                    <?php else: ?>
                        <span class="badge bg-<?= $u['role'] ?>"><?= $u['role'] ?></span>
                    <?php endif; ?>
                </td>

                <td style="color:#444; font-size:0.8rem;"><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>

                <td class="actions-flex">
                    <?php if($u['is_pending']): ?>
                        <form method="POST">
                            <input type="hidden" name="target_id" value="<?= $u['id'] ?>">
                            <button type="submit" name="action" value="approve" class="btn btn-approve">Autoriser</button>
                        </form>
                    <?php endif; ?>

                    <?php if($u['id'] != $user_id): ?>
                        <?php if($u['role'] === 'user'): ?>
                            <form method="POST">
                                <input type="hidden" name="target_id" value="<?= $u['id'] ?>">
                                <button type="submit" name="action" value="make_vip" class="btn btn-promote">Promouvoir VIP</button>
                            </form>
                        <?php elseif($u['role'] === 'vip'): ?>
                            <form method="POST">
                                <input type="hidden" name="target_id" value="<?= $u['id'] ?>">
                                <button type="submit" name="action" value="make_user" class="btn btn-demote">Rétrograder</button>
                            </form>
                        <?php endif; ?>

                        <?php if($u['role'] !== 'admin'): ?>
                            <form method="POST" onsubmit="return confirm('Confirmer l\'effacement définitif du sujet ?');">
                                <input type="hidden" name="target_id" value="<?= $u['id'] ?>">
                                <button type="submit" name="action" value="terminate" class="btn btn-delete">Supprimer</button>
                            </form>
                        <?php endif; ?>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

</body>
</html>