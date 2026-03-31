<?php
// app/ajax/chat_send.php - VERSION AVEC PIÈCES JOINTES
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../lib/security.php';

header('Content-Type: application/json');

$user_id = current_user_id();
$room_id = (int)($_POST['room_id'] ?? 0);
$content = trim($_POST['content'] ?? '');
$type = $_POST['type'] ?? 'text';
$reply_to_id = (int)($_POST['reply_to_id'] ?? 0);

if ($room_id <= 0) {
    echo json_encode(['ok' => false, 'error' => 'Salon requis']);
    exit;
}

// Traitement fichier uploadé
$file_id = null;
if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = __DIR__ . '/../../storage/uploads/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
    
    $allowed_mimes = ['image/jpeg','image/png','image/gif','image/webp','audio/mpeg','video/mp4','application/pdf'];
    $max_size = 8 * 1024 * 1024; // 8MB
    
    $error = validate_uploaded_file($_FILES['file'], $allowed_mimes, $max_size);
    if ($error) {
        echo json_encode(['ok' => false, 'error' => $error]);
        exit;
    }
    
    $ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
    $stored_name = uniqid('chat_', true) . '.' . strtolower($ext);
    $target_path = $upload_dir . $stored_name;
    
    if (move_uploaded_file($_FILES['file']['tmp_name'], $target_path)) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($target_path);
        
        $stmt = $pdo->prepare("INSERT INTO files (user_id, original_name, stored_name, mime_type, size_bytes, context) 
                             VALUES (?, ?, ?, ?, ?, 'chat')");
        $stmt->execute([
            $user_id, $_FILES['file']['name'], $stored_name, $mime, $_FILES['file']['size']
        ]);
        $file_id = $pdo->lastInsertId();
        
        $type = match(true) {
            str_starts_with($mime, 'image/') => 'image',
            str_starts_with($mime, 'video/') => 'video',
            str_starts_with($mime, 'audio/') => 'audio',
            default => 'file'
        };
        $content = null; // Pas de texte pour fichier
    }
}

$stmt = $pdo->prepare("INSERT INTO messages (room_id, user_id, content, message_type, file_id, reply_to_id) 
                       VALUES (?, ?, ?, ?, ?, ?)");
$stmt->execute([$room_id, $user_id, $content ?: null, $type, $file_id ?: null, $reply_to_id ?: null]);

echo json_encode(['ok' => true, 'id' => $pdo->lastInsertId()]);
?>
