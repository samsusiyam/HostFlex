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

<div class="bg-gradient-to-r from-blue-600 to-blue-700 py-16">
<div class="content">
    <?php $breadcrumbs = [['label' => 'Blog', 'url' => '/blogs.php'], ['label' => $blog_cat['name']]]; include __DIR__ . '/breadcrumb.php'; ?>
    <h1 class="text-3xl md:text-4xl font-extrabold mb-4 text-white"><?php echo htmlspecialchars($blog_cat['name']); ?></h1>
    <?php if ($blog_cat['description']): ?>
    <p class="text-lg text-gray-100 max-w-2xl"><?php echo htmlspecialchars($blog_cat['description']); ?></p>
    <?php endif; ?>
    <div class="flex items-center gap-4 mt-4 text-sm text-blue-100">
        <span><i class="fa fa-file-alt mr-1"></i><?php echo $post_count; ?> <?php echo $post_count === 1 ? 'article' : 'articles'; ?></span>
    </div>
</div>
</div>

<section class="section_gap">
<div class="content">
<?php if (!empty($posts)): ?>
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
<?php foreach ($posts as $post): ?>
<a href="/blog.php?slug=<?php echo htmlspecialchars($post['slug']); ?>" class="group bg-white dark:bg-gray-800 rounded-xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden border border-gray-100 dark:border-gray-700">
    <div class="relative overflow-hidden">
        <?php if ($post['image']): ?>
        <img src="../<?php echo htmlspecialchars($post['image']); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>" class="w-full h-52 object-cover group-hover:scale-105 transition duration-500" loading="lazy">
        <?php else: ?>
        <div class="w-full h-52 bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center"><i class="fa fa-blog text-white text-4xl opacity-50"></i></div>
        <?php endif; ?>
        <div class="absolute top-3 left-3">
            <span class="bg-blue-600 text-white text-xs font-semibold px-2.5 py-1 rounded-full shadow"><?php echo date('M d', strtotime($post['created_at'])); ?></span>
        </div>
    </div>
    <div class="p-5">
        <h3 class="font-bold text-gray-800 dark:text-gray-100 mb-2 group-hover:text-blue-600 transition line-clamp-2 leading-snug"><?php echo htmlspecialchars($post['title']); ?></h3>
        <p class="text-gray-500 dark:text-gray-400 text-sm line-clamp-3 leading-relaxed mb-4"><?php echo htmlspecialchars($post['excerpt'] ?: strip_tags(substr($post['content'], 0, 180))); ?></p>
        <div class="flex items-center justify-between pt-3 border-t border-gray-100 dark:border-gray-700">
            <div class="flex items-center gap-2 text-xs text-gray-400">
                <?php if ($post['author']): ?><span class="flex items-center"><i class="fa fa-user-circle mr-1"></i><?php echo htmlspecialchars($post['author']); ?></span><?php endif; ?>
            </div>
            <span class="text-xs text-blue-600 font-medium group-hover:translate-x-1 transition-transform inline-flex items-center gap-1">Read <i class="fa fa-arrow-right text-[10px]"></i></span>
        </div>
    </div>
</a>
<?php endforeach; ?>
</div>
<?php else: ?>
<div class="text-center py-16">
    <div class="w-16 h-16 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mx-auto mb-4"><i class="fa fa-newspaper text-gray-300 text-2xl"></i></div>
    <p class="text-gray-400 text-lg">No posts in this category yet.</p>
    <a href="/blogs.php" class="text-blue-600 hover:underline text-sm mt-2 inline-block"><i class="fa fa-arrow-left mr-1"></i>Back to Blog</a>
</div>
<?php endif; ?>
</div>
</section>

<?php include "footer.php"; ?>
</body>
</html>
