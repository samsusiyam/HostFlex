<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

session_start();

if (!tableExists('page_views')) {
    @mysqli_query($conn, "CREATE TABLE page_views (
        id INT AUTO_INCREMENT PRIMARY KEY,
        page_url VARCHAR(500) NOT NULL,
        page_title VARCHAR(255) DEFAULT '',
        referrer VARCHAR(500) DEFAULT '',
        ip_address VARCHAR(45) DEFAULT '',
        user_agent VARCHAR(500) DEFAULT '',
        visitor_id VARCHAR(64) DEFAULT '',
        country VARCHAR(100) DEFAULT '',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

$url = substr($_POST['url'] ?? '/', 0, 500);
$title = substr($_POST['t'] ?? '', 0, 255);
$referrer = substr($_POST['r'] ?? '', 0, 500);
$ip = getClientIP();
$ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);

$visitor_id = md5($ip . '|' . $ua);
$url_key = md5($url);
$today = date('Y-m-d');

if (!isset($_SESSION['pv'])) $_SESSION['pv'] = [];

if (isset($_SESSION['pv'][$url_key]) && $_SESSION['pv'][$url_key] === $today) {
    header('Content-Type: text/plain');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    header('Access-Control-Allow-Origin: *');
    echo 'ok';
    exit;
}

$url_esc = mysqli_real_escape_string($conn, $url);
$visitor_esc = mysqli_real_escape_string($conn, $visitor_id);

$existing = @mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM page_views WHERE page_url = '$url_esc' AND visitor_id = '$visitor_esc' AND DATE(created_at) = '$today' LIMIT 1"));
if ($existing) {
    $_SESSION['pv'][$url_key] = $today;
    header('Content-Type: text/plain');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    header('Access-Control-Allow-Origin: *');
    echo 'ok';
    exit;
}

$title = mysqli_real_escape_string($conn, $title);
$referrer = mysqli_real_escape_string($conn, $referrer);
$ip_esc = mysqli_real_escape_string($conn, $ip);
$ua_esc = mysqli_real_escape_string($conn, $ua);

@mysqli_query($conn, "INSERT INTO page_views (page_url, page_title, referrer, ip_address, user_agent, visitor_id) VALUES ('$url_esc', '$title', '$referrer', '$ip_esc', '$ua_esc', '$visitor_esc')");

$_SESSION['pv'][$url_key] = $today;

header('Content-Type: text/plain');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
header('Access-Control-Allow-Origin: *');
echo 'ok';
