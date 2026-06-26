<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$q = mysqli_query($conn, "SELECT id, photo FROM partners WHERE photo != ''");
$count = 0;
while ($row = mysqli_fetch_assoc($q)) {
    $path = __DIR__ . '/../' . $row['photo'];
    if (!file_exists($path)) continue;
    if (resizeImage($path, $path, 400, 128)) {
        $count++;
        echo "Resized: {$row['photo']}\n";
    }
}
echo "Done. $count images resized.\n";
