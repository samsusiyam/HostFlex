<?php
require 'config/database.php';
require 'includes/functions.php';

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$dir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
define('SITE_URL', $protocol . '://' . $host . $dir . '/');

header('Content-Type: application/xml; charset=utf-8');

$blogPosts = $conn->query("SELECT slug, updated_at FROM blog_posts WHERE status = 1");
$pages = $conn->query("SELECT slug FROM pages WHERE status = 1");
$categories = $conn->query("SELECT slug FROM blog_categories WHERE status = 1");

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc><?= SITE_URL ?>index.php</loc>
        <priority>1.0</priority>
    </url>
    <url>
        <loc><?= SITE_URL ?>blogs.php</loc>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
    <url>
        <loc><?= SITE_URL ?>contact.php</loc>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>
    <url>
        <loc><?= SITE_URL ?>offers.php</loc>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>
<?php while ($blog = $blogPosts->fetch_assoc()): ?>
    <url>
        <loc><?= SITE_URL ?>blog/<?= htmlspecialchars($blog['slug']) ?></loc>
        <lastmod><?= date('c', strtotime($blog['updated_at'])) ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.6</priority>
    </url>
<?php endwhile; ?>
<?php while ($page = $pages->fetch_assoc()): ?>
    <url>
        <loc><?= SITE_URL ?>page/<?= htmlspecialchars($page['slug']) ?></loc>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>
<?php endwhile; ?>
<?php while ($cat = $categories->fetch_assoc()): ?>
    <url>
        <loc><?= SITE_URL ?>category/<?= htmlspecialchars($cat['slug']) ?></loc>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>
<?php endwhile; ?>
</urlset>
