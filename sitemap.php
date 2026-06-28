<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require 'config/database.php';
require 'includes/functions.php';

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$dir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
define('SITE_URL', $protocol . '://' . $host . $dir . '/');

header('Content-Type: application/xml; charset=utf-8');

$urls = [
    ['loc' => SITE_URL . 'index.php', 'priority' => '1.0'],
    ['loc' => SITE_URL . 'blogs.php', 'changefreq' => 'weekly', 'priority' => '0.8'],
    ['loc' => SITE_URL . 'contact.php', 'changefreq' => 'monthly', 'priority' => '0.5'],
    ['loc' => SITE_URL . 'offers.php', 'changefreq' => 'monthly', 'priority' => '0.5'],
];

if ($conn) {
    $r = @$conn->query("SELECT slug, updated_at FROM blog_posts WHERE status = 1");
    if ($r) { while ($blog = $r->fetch_assoc()) {
        $urls[] = ['loc' => SITE_URL . 'blog/' . $blog['slug'], 'lastmod' => date('c', strtotime($blog['updated_at'])), 'changefreq' => 'weekly', 'priority' => '0.6'];
    }}
    $r = @$conn->query("SELECT slug FROM pages WHERE status = 1");
    if ($r) { while ($page = $r->fetch_assoc()) {
        $urls[] = ['loc' => SITE_URL . 'page/' . $page['slug'], 'changefreq' => 'monthly', 'priority' => '0.5'];
    }}
    $r = @$conn->query("SELECT slug FROM blog_categories WHERE status = 1");
    if ($r) { while ($cat = $r->fetch_assoc()) {
        $urls[] = ['loc' => SITE_URL . 'category/' . $cat['slug'], 'changefreq' => 'monthly', 'priority' => '0.5'];
    }}
}

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach ($urls as $u): ?>
    <url>
        <loc><?= htmlspecialchars($u['loc']) ?></loc>
<?php if (!empty($u['lastmod'])): ?>
        <lastmod><?= $u['lastmod'] ?></lastmod>
<?php endif; ?>
<?php if (!empty($u['changefreq'])): ?>
        <changefreq><?= $u['changefreq'] ?></changefreq>
<?php endif; ?>
        <priority><?= $u['priority'] ?></priority>
    </url>
<?php endforeach; ?>
</urlset>
