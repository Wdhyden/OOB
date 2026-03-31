<?php
// public/profile.php
require_once __DIR__ . '/../app/lib/auth.php';
require_login();
require_once __DIR__ . '/../app/config/database.php';

$user_id = (int)($_GET['id'] ?? current_user_id());
$current_user_id = current_user_id();
$is_owner = $user_id === $current_user_id;
$role = $_SESSION['role'];

// Charger profil
$stmt = $pdo->prepare("SELECT u.*, up.*,
                              TIMESTAMPDIFF(YEAR, NOW(), u.created_at) * -1 as account_age
                       FROM users u 
                       LEFT JOIN user_profiles up ON up.user_id = u.id 
                       WHERE u.id = ? AND u.is_banned = 0");
$stmt->execute([$user_id]);
$profile = $stmt->fetch();

if (!$profile) {
    header('Location: /dashboard.php');
    exit;
}

// Édition profil (propriétaire ou admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($is_owner || $role === 'admin')) {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $bio = trim($_POST['bio']);
    $region = trim($_POST['region']);
    $color = $_POST['nickname_color'] ?? '#ff6fd8';
    
    // Avatar
    $avatar_path = $profile['avatar_path'];
    if (!empty($_FILES['avatar']['name'])) {
        $error = validate_uploaded_file($_FILES['avatar'], 
            ['image/jpeg','image/png','image/gif'], 2*1024*1024);
        if (!$error) {
            $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
            $stored = uniqid('avatar_', true) . '.' . $ext;
            $target = __DIR__ . '/../storage/uploads/avatars/' . $stored;
            mkdir(dirname($target), 0755, true);
            move_uploaded_file($_FILES['avatar']['tmp_name'], $target);
            $avatar_path = 'uploads/avatars/' . $stored;
        }
    }
    
    $stmt = $pdo->prepare("UPDATE user_profiles SET 
        first_name = ?, last_name = ?, bio = ?, region = ?, avatar_path = ?, nickname_color = ?
        WHERE user_id = ?");
    $stmt->execute([$first_name, $last_name, $bio, $region, $avatar_path, $color, $user_id]);
    
    header('Location: /profile.php?id=' . $user_id);
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Profil <?= e($profile['username']) ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="theme-dark">
<div class="profile-container">
    <div class="profile-header">
        <div class="profile-avatar-large" style="background-image: url('<?= e($profile['avatar_path'] ?? '/assets/img/default-avatar.png') ?>')"></div>
        <div class="profile-info">
            <h1 style="color: <?= e($profile['nickname_color'] ?? '#ff6fd8') ?>"><?= e($profile['username']) ?></h1>
            <div class="profile-meta">
                <span class="role-badge role-<?= $profile['role'] ?>"><?= ucfirst($profile['role']) ?></span>
                <span><?= $profile['account_age'] ?> jours</span>
                <span><?= $profile['region'] ?></span>
                <?php if ($profile['political_view']): ?>
                    <span class="political-badge"><?= e($profile['political_view']) ?></span>
                <?php endif; ?>
            </div>
        </div>
        <?php if ($is_owner): ?>
            <a href="#edit-profile" class="btn-primary">✏️ Modifier</a>
        <?php elseif ($role === 'admin'): ?>
            <a href="/admin/" class="btn-primary">Gérer</a>
        <?php endif; ?>
    </div>

    <div class="profile-content">
        <div class="profile-section">
            <h3>À propos</h3>
            <p><?= nl2br(e($profile['bio'] ?: 'Aucune biographie.')) ?></p>
        </div>
        
        <div class="profile-stats">
            <div class="stat-item">
                <div class="stat-number"><?= number_format($profile['message_count']) ?></div>
                <div>Messages</div>
            </div>
            <span>·</span>
            <div class="stat-item">
                <div>En ligne</div>
                <div class="status-<?= $profile['online_status'] ?>"><?= ucfirst($profile['online_status']) ?></div>
            </div>
        </div>
    </div>
</div>

<?php if ($is_owner): ?>
<!-- Formulaire édition (modal) -->
<div id="edit-profile" class="modal">
    <div class="modal-content">
        <form method="post" enctype="multipart/form-data">
            <h3>Modifier profil</h3>
            
            <div class="input-group">
                <label>Nom</label>
                <input type="text" name="last_name" value="<?= e($profile['last_name']) ?>">
            </div>
            
            <div class="input-group">
                <label>Prénom</label>
                <input type="text" name="first_name" value="<?= e($profile['first_name']) ?>">
            </div>
            
            <div class="input-group">
                <label>Biographie</label>
                <textarea name="bio"><?= e($profile['bio']) ?></textarea>
            </div>
            
            <div class="input-group">
                <label>Région</label>
                <input type="text" name="region" value="<?= e($profile['region']) ?>">
            </div>
            
            <div class="input-group">
                <label>Couleur pseudo</label>
                <input type="color" name="nickname_color" value="<?= e($profile['nickname_color']) ?>">
            </div>
            
            <div class="input-group">
                <label>Nouvelle photo</label>
                <input type="file" name="avatar" accept="image/*">
            </div>
            
            <div class="modal-actions">
                <button type="submit" class="btn-primary">Sauvegarder</button>
                <button type="button" class="btn-cancel" onclick="closeModal()">Annuler</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script src="/assets/js/theme.js"></script>
<script src="/assets/js/profile.js"></script>
</body>
</html>
