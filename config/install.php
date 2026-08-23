<?php
require_once 'database.php';
require_once '../includes/functions.php';

$lock_file = __DIR__ . '/installed.lock';
if (file_exists($lock_file) && !isAdminLoggedIn()) {
    http_response_code(403);
    die('<h3>Access Denied</h3><p>Installation is already locked. Please log in as administrator to re-run installation.</p><p><a href="../admin/index.php">Go to Login</a></p>');
}

$sql = file_get_contents(__DIR__ . '/../database.sql');
$queries = explode(';', $sql);

foreach ($queries as $query) {
    $query = trim($query);
    if (!empty($query)) {
        mysqli_query($conn, $query);
    }
}

file_put_contents($lock_file, 'Installed on ' . date('Y-m-d H:i:s'));

echo "Installation completed successfully! <a href='../admin/dashboard.php'>Go to Admin Panel</a>";
echo "<br><br>Default login: <strong>admin</strong> / <strong>password</strong>";
