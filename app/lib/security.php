<?php
// app/lib/security.php

function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function validate_uploaded_file(array $file, array $allowedMime, int $maxSizeBytes): ?string {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return 'Erreur lors de l\'upload.';
    }
    if ($file['size'] > $maxSizeBytes) {
        return 'Fichier trop volumineux.';
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    if (!in_array($mime, $allowedMime, true)) {
        return 'Type de fichier non autorisé.';
    }
    return null;
}
