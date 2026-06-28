<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

if (!tableExists('page_views')) {
    @mysqli_query($conn, "CREATE TABLE page_views (
        id INT AUTO_INCREMENT PRIMARY KEY,
        page_url VARCHAR(500) NOT NULL,
        page_title VARCHAR(255) DEFAULT '',
        referrer VARCHAR(500) DEFAULT '',
        ip_address VARCHAR(45) DEFAULT '',
        user_agent VARCHAR(500) DEFAULT '',
        country VARCHAR(100) DEFAULT '',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

$url = substr($_POST['url'] ?? '/', 0, 500);
$title = substr($_POST['t'] ?? '', 0, 255);
$referrer = substr($_POST['r'] ?? '', 0, 500);
$ip = getClientIP();
$ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);

$url = mysqli_real_escape_string($conn, $url);
$title = mysqli_real_escape_string($conn, $title);
$referrer = mysqli_real_escape_string($conn, $referrer);
$ip = mysqli_real_escape_string($conn, $ip);
$ua = mysqli_real_escape_string($conn, $ua);

@mysqli_query($conn, "INSERT INTO page_views (page_url, page_title, referrer, ip_address, user_agent) VALUES ('$url', '$title', '$referrer', '$ip', '$ua')");

header('Content-Type: text/plain');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
header('Access-Control-Allow-Origin: *');
echo 'ok';
