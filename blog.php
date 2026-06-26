<?php require_once 'config/database.php'; require_once 'includes/functions.php'; checkMaintenance();

$slug = $_GET['slug'] ?? '';
if (!$slug) { header('Location: blogs.php'); exit; }

$slug_esc = mysqli_real_escape_string($conn, $slug);
$post = mysqli_fetch_assoc(mysqli_query($conn, "SELECT p.*, c.name as category_name, c.slug as category_slug FROM blog_posts p LEFT JOIN blog_categories c ON p.category_id = c.id WHERE p.slug = '$slug_esc' AND p.status = 1"));

if (!$post) { header('Location: blogs.php'); exit; }

$page_title = $post['title'];
$meta_desc = $post['meta_description'] ?: ($post['excerpt'] ?: substr(strip_tags($post['content']), 0, 160));
$meta_kw = $post['meta_keywords'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include "cdnjs.php"; ?>
<title><?php echo htmlspecialchars($post['title']); ?> - <?php echo escSetting('site_name'); ?></title>
<meta name="description" content="<?php echo htmlspecialchars($meta_desc); ?>">
<?php if ($meta_kw): ?><meta name="keywords" content="<?php echo htmlspecialchars($meta_kw); ?>"><?php endif; ?>
<meta property="og:title" content="<?php echo htmlspecialchars($post['title']); ?>">
<meta property="og:description" content="<?php echo htmlspecialchars($meta_desc); ?>">
<?php if ($post['image']): ?>
<meta property="og:image" content="<?php echo htmlspecialchars($post['image']); ?>">
<?php endif; ?>
</head>
<body>
<?php include "header.php"; ?>

<section class="section_gap bg-white">
<div class="content max-w-4xl mx-auto">
    <div class="mb-6">
        <?php if ($post['category_name']): ?>
        <a href="/category/<?php echo htmlspecialchars($post['category_slug']); ?>" class="text-xs text-blue-600 font-semibold uppercase tracking-wide"><?php echo htmlspecialchars($post['category_name']); ?></a>
        <?php endif; ?>
        <h1 class="text-3xl md:text-4xl font-bold mt-2 mb-3"><?php echo htmlspecialchars($post['title']); ?></h1>
        <div class="text-sm text-gray-500 flex items-center gap-3">
            <?php if ($post['author']): ?><span>By <?php echo htmlspecialchars($post['author']); ?></span><?php endif; ?>
            <span><?php echo date('F d, Y', strtotime($post['created_at'])); ?></span>
        </div>
    </div>
    <?php if ($post['image']): ?>
    <img src="<?php echo htmlspecialchars($post['image']); ?>" class="w-full max-h-[400px] object-cover rounded-xl mb-8" alt="<?php echo htmlspecialchars($post['title']); ?>">
    <?php endif; ?>
    <div class="blog-content text-gray-800 leading-relaxed">
        <?php echo $post['content']; ?>
    </div>
    <div class="mt-10 pt-6 border-t">
        <a href="blogs.php" class="text-blue-600 hover:underline"><i class="fa fa-arrow-left mr-1"></i> Back to Blog</a>
    </div>
</div>
</section>

<style>
.blog-content h2 { font-size: 1.5rem; font-weight: 700; margin-top: 1.5rem; margin-bottom: 0.75rem; }
.blog-content h3 { font-size: 1.25rem; font-weight: 600; margin-top: 1.25rem; margin-bottom: 0.5rem; }
.blog-content p { margin-bottom: 1rem; line-height: 1.75; color: #374151; }
.blog-content ul, .blog-content ol { margin-bottom: 1rem; padding-left: 1.5rem; }
.blog-content ul { list-style-type: disc; }
.blog-content ol { list-style-type: decimal; }
.blog-content li { margin-bottom: 0.25rem; line-height: 1.75; }
.blog-content img { max-width: 100%; height: auto; border-radius: 0.5rem; margin: 1rem 0; }
</style>

<?php include "footer.php"; ?>
</body>
</html>

