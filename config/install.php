<?php
require_once 'database.php';

$sql = file_get_contents(__DIR__ . '/../database.sql');
$queries = explode(';', $sql);

foreach ($queries as $query) {
    $query = trim($query);
    if (!empty($query)) {
        mysqli_query($conn, $query);
    }
}

echo "Installation completed successfully! <a href='../admin/dashboard.php'>Go to Admin Panel</a>";
echo "<br><br>Default login: <strong>admin</strong> / <strong>password</strong>";
