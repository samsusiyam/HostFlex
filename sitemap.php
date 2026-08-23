<?php
require 'config/database.php';
require 'includes/functions.php';

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$dir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
define('SITE_URL', $protocol . '://' . $host . $dir . '/');

header('Content-Type: application/xml; charset=utf-8');

$blogPosts = $conn->query("SELECT p.id, p.slug, p.created_at, c.slug as category_slug FROM blog_posts p LEFT JOIN blog_categories c ON p.category_id = c.id WHERE p.status = 1");
$pages = $conn->query("SELECT slug FROM pages WHERE status = 1");
$categories = $conn->query("SELECT slug FROM categories WHERE status = 1");
$blogCategories = $conn->query("SELECT slug FROM blog_categories WHERE status = 1");

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc><?= SITE_URL ?></loc>
        <priority>1.0</priority>
    </url>
    <url>
        <loc><?= SITE_URL ?>blog</loc>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
    <url>
        <loc><?= SITE_URL ?>contact</loc>
        <changefreq>monthly</changefreq>
        <priority>0.6</priority>
    </url>
    <url>
        <loc><?= SITE_URL ?>offers</loc>
        <changefreq>weekly</changefreq>
        <priority>0.7</priority>
    </url>
<?php if ($blogPosts): while ($blog = $blogPosts->fetch_assoc()): ?>
    <url>
        <loc><?= rtrim(SITE_URL, '/') . getBlogPostUrl($blog) ?></loc>
        <lastmod><?= date('c', strtotime($blog['created_at'])) ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.6</priority>
    </url>
<?php endwhile; endif; ?>
<?php if ($pages): while ($page = $pages->fetch_assoc()): ?>
    <url>
        <loc><?= SITE_URL ?>page/<?= htmlspecialchars($page['slug']) ?></loc>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>
<?php endwhile; endif; ?>
<?php if ($categories): while ($cat = $categories->fetch_assoc()): ?>
    <url>
        <loc><?= SITE_URL ?>category/<?= htmlspecialchars($cat['slug']) ?></loc>
        <changefreq>monthly</changefreq>
        <priority>0.7</priority>
    </url>
<?php endwhile; endif; ?>
<?php if ($blogCategories): while ($bcat = $blogCategories->fetch_assoc()): ?>
    <url>
        <loc><?= SITE_URL ?>blog/category/<?= htmlspecialchars($bcat['slug']) ?></loc>
        <changefreq>weekly</changefreq>
        <priority>0.6</priority>
    </url>
<?php endwhile; endif; ?>
</urlset>
