<?php
$dir = __DIR__ . '/../storage/uploads/avatars/';
echo "Chemin testé : " . realpath($dir) . "<br>";
if (is_dir($dir)) {
    echo "Le dossier existe.<br>";
    if (is_writable($dir)) {
        echo "✅ PHP PEUT écrire dans ce dossier !";
    } else {
        echo "❌ PHP NE PEUT PAS écrire dans ce dossier.";
    }
} else {
    echo "❌ Le dossier n'existe pas à ce chemin.";
}