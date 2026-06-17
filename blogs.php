<?php require_once 'config/database.php'; require_once 'includes/functions.php'; checkMaintenance();
$page_title = 'Blog';
?>
<html lang="en">
<head>
<?php include "cdnjs.php"; ?>
<title>Blog - <?php echo getSetting('site_name'); ?></title>
<meta name="description" content="Read our latest blog posts about web hosting, technology and more">
</head>
<body>
<?php include "header.php"; ?>

<section class="section_gap bg-white">
<div class="content">
<div class="mb-10 text-center">
    <h1 class="text-3xl font-bold">Our Blog</h1>
    <p class="text-gray-500 mt-2">Latest news, tips and updates</p>
</div>

<?php
$cat_slug = $_GET['category'] ?? '';
$search_q = trim($_GET['search'] ?? '');
$where = "WHERE p.status = 1";
$params = [];
if ($cat_slug) {
    $cat_slug_esc = mysqli_real_escape_string($conn, $cat_slug);
    $where .= " AND c.slug = '$cat_slug_esc'";
}
if ($search_q) {
    $search_esc = mysqli_real_escape_string($conn, $search_q);
    $where .= " AND (p.title LIKE '%$search_esc%' OR p.content LIKE '%$search_esc%')";
}

$page = max(1, (int)($_GET['p'] ?? 1));
$per_page = 9;
$offset = ($page - 1) * $per_page;
$total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM blog_posts p LEFT JOIN blog_categories c ON p.category_id = c.id $where"))['c'];
$pages = ceil($total / $per_page);

$posts = mysqli_query($conn, "SELECT p.*, c.name as category_name, c.slug as category_slug FROM blog_posts p LEFT JOIN blog_categories c ON p.category_id = c.id $where ORDER BY p.created_at DESC LIMIT $per_page OFFSET $offset");
$categories = mysqli_query($conn, "SELECT * FROM blog_categories WHERE status = 1 ORDER BY name");
?>

<div class="flex flex-col lg:flex-row gap-8">
    <div class="flex-1">
        <?php if (mysqli_num_rows($posts) > 0): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php while ($post = mysqli_fetch_assoc($posts)): ?>
            <div class="bg-white border rounded-xl overflow-hidden shadow hover:shadow-lg transition">
                <?php if ($post['image']): ?>
<a href="blog.php?slug=<?php echo htmlspecialchars($post['slug']); ?>">
                    <img src="<?php echo htmlspecialchars($post['image']); ?>" class="w-full h-48 object-cover" alt="<?php echo htmlspecialchars($post['title']); ?>">
                </a>
                <?php endif; ?>
                <div class="p-5">
                    <?php if ($post['category_name']): ?>
                    <a href="category.php?slug=<?php echo htmlspecialchars($post['category_slug']); ?>" class="text-xs text-blue-600 font-semibold uppercase tracking-wide"><?php echo htmlspecialchars($post['category_name']); ?></a>
                    <?php endif; ?>
                    <h3 class="text-lg font-bold mt-1 mb-2"><a href="blog.php?slug=<?php echo htmlspecialchars($post['slug']); ?>" class="text-gray-900 hover:text-blue-600"><?php echo htmlspecialchars($post['title']); ?></a></h3>
                    <p class="text-sm text-gray-500 mb-3"><?php echo htmlspecialchars($post['excerpt'] ?: substr(strip_tags($post['content']), 0, 150) . '...'); ?></p>
                    <div class="flex items-center justify-between text-xs text-gray-400">
                        <span><?php echo $post['author'] ? htmlspecialchars($post['author']) . ' • ' : ''; ?><?php echo date('d M Y', strtotime($post['created_at'])); ?></span>
                        <a href="blog.php?slug=<?php echo htmlspecialchars($post['slug']); ?>" class="text-blue-600 hover:underline">Read More</a>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <?php if ($pages > 1): ?>
        <div class="flex justify-center mt-8 gap-1">
            <?php for ($i = 1; $i <= $pages; $i++): ?>
            <a href="?p=<?php echo $i; ?><?php echo $cat_slug ? '&category='.urlencode($cat_slug) : ''; ?>" class="px-3 py-1.5 rounded text-sm <?php echo $i == $page ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
        <?php else: ?>
        <div class="text-center py-16 text-gray-400">
            <i class="fa fa-newspaper text-5xl mb-4"></i>
            <p class="text-lg">No blog posts found.</p>
        </div>
        <?php endif; ?>
    </div>
    <div class="lg:w-72">
        <div class="bg-white border rounded-xl p-5 sticky top-24">
            <h3 class="font-semibold mb-4">Categories</h3>
            <div class="space-y-2">
                <a href="blogs.php" class="block text-sm <?php echo !$cat_slug ? 'text-blue-600 font-medium' : 'text-gray-600 hover:text-blue-600'; ?>">All Categories</a>
                <?php while ($cat = mysqli_fetch_assoc($categories)):
                    $count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM blog_posts WHERE category_id = {$cat['id']} AND status = 1"));
                ?>
                <a href="/category/<?php echo htmlspecialchars($cat['slug']); ?>" class="block text-sm <?php echo $cat_slug === $cat['slug'] ? 'text-blue-600 font-medium' : 'text-gray-600 hover:text-blue-600'; ?>">
                    <?php echo htmlspecialchars($cat['name']); ?> (<?php echo $count['c']; ?>)
                </a>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
</div>
</div>
</section>

<?php include "footer.php"; ?>
<script src="../cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="../cdn.jsdelivr.net/npm/%40accessible360/accessible-slick%401.0.1/slick/slick.min.js"></script>
<script src="../cdn.jsdelivr.net/npm/%40fancyapps/fancybox%403.5.6/dist/jquery.fancybox.min.js"></script>
<script src="../unpkg.com/alpinejs%403.14.9/dist/cdn.min.js"></script>
<script src="../cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js"></script>
<script src="../unpkg.com/%40material-tailwind/html%403.0.0-beta.7/scripts/ripple.js"></script>
<script src="../unpkg.com/%40material-tailwind/html%402.0.0/scripts/collapse.js"></script>
<script src="../unpkg.com/%40material-tailwind/html%402.0.0/scripts/dialog.js"></script>
<script src="../unpkg.com/%40material-tailwind/html%402.0.0/scripts/dismissible.js"></script>
<script type="module" src="../unpkg.com/%40material-tailwind/html%402.0.0/scripts/popover.js"></script>
<script src="../unpkg.com/%40material-tailwind/html%402.0.0/scripts/tabs.js"></script>
<script type="module" src="../unpkg.com/%40material-tailwind/html%402.0.0/scripts/tooltip.js"></script>
<script src="../unpkg.com/tailwindcss%402.2.19/dist/tailwind.min.js"></script>
<script src="js/scroll.js"></script>
<script src="js/ns.js"></script>
<script src="js/ns-jquery.js"></script>
</body>
</html>

