<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require 'config/database.php';
require 'includes/functions.php';

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$dir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
define('SITE_URL', $protocol . '://' . $host . $dir . '/');

header('Content-Type: application/xml; charset=utf-8');

$xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

function sm_url($loc, $changefreq = '', $priority = '0.5', $lastmod = '') {
    $xml  = '    <url>' . "\n";
    $xml .= '        <loc>' . htmlspecialchars($loc) . '</loc>' . "\n";
    if ($lastmod) $xml .= '        <lastmod>' . $lastmod . '</lastmod>' . "\n";
    if ($changefreq) $xml .= '        <changefreq>' . $changefreq . '</changefreq>' . "\n";
    $xml .= '        <priority>' . $priority . '</priority>' . "\n";
    $xml .= '    </url>' . "\n";
    return $xml;
}

$xml .= sm_url(SITE_URL . 'index.php', '', '1.0');
$xml .= sm_url(SITE_URL . 'blogs.php', 'weekly', '0.8');
$xml .= sm_url(SITE_URL . 'contact.php', 'monthly', '0.5');
$xml .= sm_url(SITE_URL . 'offers.php', 'monthly', '0.5');

if ($conn) {
    $tables = [
        ['table' => 'blog_posts', 'url' => 'blog/', 'changefreq' => 'weekly', 'priority' => '0.6', 'has_date' => true],
        ['table' => 'pages', 'url' => 'page/', 'changefreq' => 'monthly', 'priority' => '0.5', 'has_date' => false],
        ['table' => 'blog_categories', 'url' => 'category/', 'changefreq' => 'monthly', 'priority' => '0.5', 'has_date' => false],
    ];
    foreach ($tables as $t) {
        $cols = 'slug';
        if ($t['has_date']) $cols .= ', updated_at';
        $r = @$conn->query("SELECT $cols FROM `{$t['table']}` WHERE status = 1");
        if (!$r) continue;
        while ($row = $r->fetch_assoc()) {
            $lastmod = ($t['has_date'] && !empty($row['updated_at'])) ? date('c', strtotime($row['updated_at'])) : '';
            $xml .= sm_url(SITE_URL . $t['url'] . $row['slug'], $t['changefreq'], $t['priority'], $lastmod);
        }
    }
}

$xml .= '</urlset>';
echo $xml;
