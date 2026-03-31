<?php
// public/drive_debug.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>🔍 DEBUG DRIVE</h2>";
echo "<pre>";
print_r($_FILES);
echo "</pre>";

if (isset($_FILES['file'])) {
    echo "ERREUR: " . $_FILES['file']['error'] . "<br>";
    echo "NOM: " . $_FILES['file']['name'] . "<br>";
    echo "TAILLE: " . $_FILES['file']['size'] . "<br>";
    echo "TMP: " . $_FILES['file']['tmp_name'] . "<br>";
}
?>
<form method="POST" enctype="multipart/form-data">
    <input type="file" name="file" required>
    <button>TEST UPLOAD</button>
</form>
