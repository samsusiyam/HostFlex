<?php
/**
 * Auto Backup Script
 * Run via cron: php /home/hostnibo/public_html/auto-backup.php
 * Or trigger from admin panel: auto-backup.php?token=YOUR_SECRET_TOKEN
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$backup_token = getSetting('backup_cron_token') ?: 'hostnibo-backup-' . md5($db_name);
$token = $_GET['token'] ?? '';

if (php_sapi_name() !== 'cli' && $token !== $backup_token) {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

if (!$conn) {
    echo "Database connection failed\n";
    exit;
}

$backup_dir = __DIR__ . '/backups';
if (!is_dir($backup_dir)) {
    mkdir($backup_dir, 0755, true);
}

$max_backups = 7;
$date = date('Y-m-d_H-i-s');
$filename = "backup-$date.sql";
$filepath = "$backup_dir/$filename";

$output = "-- " . (getSetting('site_name') ?: 'HostNibo') . " Auto Backup\n";
$output .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";

$tables = [];
$result = mysqli_query($conn, "SHOW TABLES");
while ($row = mysqli_fetch_array($result)) {
    $tables[] = $row[0];
}

foreach ($tables as $table) {
    $create = mysqli_fetch_assoc(mysqli_query($conn, "SHOW CREATE TABLE `$table`"));
    $output .= "-- Table: $table\n";
    $output .= "DROP TABLE IF EXISTS `$table`;\n";
    $output .= $create['Create Table'] . ";\n\n";

    $rows = mysqli_query($conn, "SELECT * FROM `$table`");
    while ($row = mysqli_fetch_assoc($rows)) {
        $vals = [];
        foreach ($row as $v) {
            $vals[] = $v === null ? 'NULL' : "'" . mysqli_real_escape_string($conn, $v) . "'";
        }
        $output .= "INSERT INTO `$table` VALUES (" . implode(',', $vals) . ");\n";
    }
    $output .= "\n";
}

file_put_contents($filepath, $output);

$files = glob("$backup_dir/backup-*.sql");
if (count($files) > $max_backups) {
    usort($files, function($a, $b) { return filemtime($a) - filemtime($b); });
    $to_delete = array_slice($files, 0, count($files) - $max_backups);
    foreach ($to_delete as $f) {
        unlink($f);
    }
}

$size = filesize($filepath);
echo "Backup completed: $filename (" . number_format($size / 1024, 1) . " KB)\n";
echo "Tables: " . count($tables) . "\n";
