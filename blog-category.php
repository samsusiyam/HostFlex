<?php require_once 'config/database.php'; require_once 'includes/functions.php'; checkMaintenance();

$slug = isset($_GET['slug']) ? sanitize($_GET['slug']) : '';

$blog_cat = null;
$r = @$conn->query("SELECT * FROM blog_categories WHERE slug = '" . mysqli_real_escape_string($conn, $slug) . "' AND status = 1 LIMIT 1");
if ($r && mysqli_num_rows($r) > 0) {
    $blog_cat = mysqli_fetch_assoc($r);
}

if (!$blog_cat) {
    header('HTTP/1.0 404 Not Found');
    include __DIR__ . '/404.php';
    exit;
}

$page_title = $blog_cat['name'];
$meta_desc = $blog_cat['meta_description'] ?? ($blog_cat['description'] ?: '');
$meta_kw = $blog_cat['meta_keywords'] ?? '';

$post_count_q = @$conn->query("SELECT COUNT(*) as c FROM blog_posts WHERE status = 1 AND (category_id = {$blog_cat['id']} OR EXISTS (SELECT 1 FROM blog_post_categories WHERE post_id = blog_posts.id AND category_id = {$blog_cat['id']}))");
$post_count = ($post_count_q && $row = mysqli_fetch_assoc($post_count_q)) ? $row['c'] : 0;

$posts_q = @$conn->query("SELECT DISTINCT p.* FROM blog_posts p LEFT JOIN blog_post_categories bpc ON p.id = bpc.post_id WHERE p.status = 1 AND (p.category_id = {$blog_cat['id']} OR bpc.category_id = {$blog_cat['id']}) ORDER BY p.created_at DESC");
$posts = [];
if ($posts_q) { while ($row = mysqli_fetch_assoc($posts_q)) { $posts[] = $row; } }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include "cdnjs.php"; ?>
<title><?php echo htmlspecialchars($blog_cat['name']); ?> - <?php echo escSetting('site_name'); ?></title>
<?php if ($meta_desc): ?><meta name="description" content="<?php echo htmlspecialchars($meta_desc); ?>"><?php endif; ?>
<?php if ($meta_kw): ?><meta name="keywords" content="<?php echo htmlspecialchars($meta_kw); ?>"><?php endif; ?>
<meta property="og:title" content="<?php echo htmlspecialchars($blog_cat['name'] . ' - ' . escSetting('site_name')); ?>">
<?php if ($meta_desc): ?><meta property="og:description" content="<?php echo htmlspecialchars($meta_desc); ?>"><?php endif; ?>
</head>
<body>
<?php include "header.php"; ?>
<?php include "contact-btn.php"; ?>

<div class="bg-blue-600 bg-opacity-90 py-16">
<div class="content">
    <?php $breadcrumbs = [['label' => 'Blog', 'url' => '/blogs.php'], ['label' => $blog_cat['name']]]; include __DIR__ . '/breadcrumb.php'; ?>
    <h2 class="text-3xl md:text-4xl font-extrabold mb-4 text-white"><?php echo htmlspecialchars($blog_cat['name']); ?></h2>
    <?php if ($blog_cat['description']): ?>
    <p class="text-lg text-gray-200"><?php echo htmlspecialchars($blog_cat['description']); ?></p>
    <?php endif; ?>
    <p class="text-gray-300 mt-2"><?php echo $post_count; ?> <?php echo $post_count === 1 ? 'post' : 'posts'; ?></p>
</div>
</div>

<section class="section_gap">
<div class="content">
<?php if (!empty($posts)): ?>
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
<?php foreach ($posts as $post): ?>
<a href="/blog.php?slug=<?php echo htmlspecialchars($post['slug']); ?>" class="bg-white dark:bg-gray-800 rounded-xl shadow hover:shadow-lg transition overflow-hidden group">
    <?php if ($post['image']): ?>
    <img src="../<?php echo htmlspecialchars($post['image']); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>" class="w-full h-48 object-cover group-hover:scale-105 transition duration-300" loading="lazy">
    <?php else: ?>
    <div class="w-full h-48 bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center"><i class="fa fa-blog text-white text-4xl opacity-50"></i></div>
    <?php endif; ?>
    <div class="p-5">
        <h3 class="font-bold text-gray-800 dark:text-gray-100 mb-2 group-hover:text-blue-600 transition"><?php echo htmlspecialchars($post['title']); ?></h3>
        <p class="text-gray-500 dark:text-gray-400 text-sm line-clamp-2"><?php echo htmlspecialchars($post['excerpt'] ?: strip_tags(substr($post['content'], 0, 150))); ?></p>
        <div class="flex items-center justify-between mt-4 text-xs text-gray-400">
            <?php if ($post['author']): ?><span><i class="fa fa-user mr-1"></i><?php echo htmlspecialchars($post['author']); ?></span><?php endif; ?>
            <span><?php echo date('d M Y', strtotime($post['created_at'])); ?></span>
        </div>
    </div>
</a>
<?php endforeach; ?>
</div>
<?php else: ?>
<div class="text-center py-16"><p class="text-gray-400 text-lg">No posts in this category yet.</p></div>
<?php endif; ?>
</div>
</section>

<?php include "footer.php"; ?>
</body>
</html>
