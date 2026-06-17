<?php
session_start();

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'hostflex';

try {
    $conn = @mysqli_connect($db_host, $db_user, $db_pass, $db_name);
    if ($conn) {
        mysqli_set_charset($conn, "utf8");
    }
} catch (Throwable $e) {
    $conn = null;
}
