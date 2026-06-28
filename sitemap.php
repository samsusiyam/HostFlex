<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Read DB credentials from database.php without triggering headers.php
$db_file = __DIR__ . '/config/database.php';
if (file_exists($db_file)) {
    $db_content = file_get_contents($db_file);
    preg_match_all("/\\$(db_\w+)\s*=\s*'([^']*)'/", $db_content, $m);
    $creds = [];
    for ($i = 0; $i < count($m[1]); $i++) {
        $creds[$m[1][$i]] = $m[2][$i];
    }
    $conn = @mysqli_connect(
        $creds['db_host'] ?? 'localhost',
        $creds['db_user'] ?? 'root',
        $creds['db_pass'] ?? '',
        $creds['db_name'] ?? 'hostflex'
    );
    if ($conn) mysqli_set_charset($conn, 'utf8mb4');
} else {
    $conn = null;
}

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$dir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
define('SITE_URL', $protocol . '://' . $host . $dir . '/');

header('Content-Type: application/xml; charset=utf-8');

function sm_url($loc, $changefreq = '', $priority = '0.5', $lastmod = '') {
    $out = '    <url>' . "\n";
    $out .= '        <loc>' . htmlspecialchars($loc) . '</loc>' . "\n";
    if ($lastmod) $out .= '        <lastmod>' . $lastmod . '</lastmod>' . "\n";
    if ($changefreq) $out .= '        <changefreq>' . $changefreq . '</changefreq>' . "\n";
    $out .= '        <priority>' . $priority . '</priority>' . "\n";
    $out .= '    </url>' . "\n";
    return $out;
}

$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

$xml .= sm_url(SITE_URL . 'index.php', '', '1.0');
$xml .= sm_url(SITE_URL . 'blogs.php', 'weekly', '0.8');
$xml .= sm_url(SITE_URL . 'contact.php', 'monthly', '0.5');
$xml .= sm_url(SITE_URL . 'offers.php', 'monthly', '0.5');

if ($conn) {
    $queries = [
        ['sql' => 'SELECT slug FROM blog_posts WHERE status = 1', 'url' => 'blog/', 'freq' => 'weekly', 'pri' => '0.6'],
        ['sql' => 'SELECT slug FROM pages WHERE status = 1', 'url' => 'page/', 'freq' => 'monthly', 'pri' => '0.5'],
        ['sql' => 'SELECT slug FROM blog_categories WHERE status = 1', 'url' => 'category/', 'freq' => 'monthly', 'pri' => '0.5'],
    ];
    foreach ($queries as $q) {
        try {
            $r = $conn->query($q['sql']);
        } catch (Throwable $e) {
            continue;
        }
        if (!$r) continue;
        while ($row = $r->fetch_assoc()) {
            $xml .= sm_url(SITE_URL . $q['url'] . $row['slug'], $q['freq'], $q['pri']);
        }
    }
    $conn->close();
}

$xml .= '</urlset>';
echo $xml;
