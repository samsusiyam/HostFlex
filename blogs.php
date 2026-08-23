<?php require_once 'config/database.php'; require_once 'includes/functions.php'; checkMaintenance();
$page_title = 'Blog';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include "cdnjs.php"; ?>
<title>Blog - <?php echo htmlspecialchars(getSetting('site_name') ?: 'Host Nibo'); ?></title>
<?php echo renderSeoTags([
    'title' => 'Blog - ' . (getSetting('site_name') ?: 'Host Nibo'),
    'description' => 'Read our latest blog posts about web hosting, WordPress, cloud servers, tips and updates.'
]); ?>
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
            <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden shadow-sm hover:shadow-md hover:-translate-y-1 transition duration-200 flex flex-col justify-between">
                <div>
                    <?php if (!empty($post['image'])): ?>
                    <a href="<?php echo getBlogPostUrl($post); ?>" class="block overflow-hidden h-48 bg-gray-100">
                        <img src="<?php echo htmlspecialchars(getImageUrl($post['image'])); ?>" class="w-full h-full object-cover hover:scale-105 transition duration-300" alt="<?php echo htmlspecialchars($post['title']); ?>" loading="lazy">
                    </a>
                    <?php else: ?>
                    <a href="<?php echo getBlogPostUrl($post); ?>" class="block overflow-hidden h-48 blog-thumb-placeholder">
                        <i class="fa-solid fa-newspaper opacity-75"></i>
                    </a>
                    <?php endif; ?>
                    <div class="p-5">
                        <div class="flex items-center justify-between gap-2 mb-2">
                            <?php if ($post['category_name']): ?>
                            <a href="/blog/category/<?php echo urlencode($post['category_slug']); ?>" class="text-[11px] text-blue-600 font-bold uppercase tracking-wider bg-blue-50 px-2.5 py-0.5 rounded-full border border-blue-100 hover:bg-blue-600 hover:text-white transition"><?php echo htmlspecialchars($post['category_name']); ?></a>
                            <?php else: ?>
                            <span class="text-[11px] text-gray-400">Articles</span>
                            <?php endif; ?>
                            <span class="text-[11px] text-gray-400 font-medium"><i class="fa fa-clock mr-1 text-[10px]"></i><?php echo getReadingTime($post['content']); ?> min</span>
                        </div>
                        <h3 class="text-base md:text-lg font-bold mb-2 leading-snug"><a href="<?php echo getBlogPostUrl($post); ?>" class="text-gray-900 hover:text-blue-600 transition line-clamp-2"><?php echo htmlspecialchars($post['title']); ?></a></h3>
                        <p class="text-xs text-gray-500 line-clamp-3 leading-relaxed"><?php echo htmlspecialchars($post['excerpt'] ?: substr(strip_tags($post['content']), 0, 150) . '...'); ?></p>
                    </div>
                </div>
                <div class="px-5 pb-5 pt-2 flex items-center justify-between text-xs text-gray-400 border-t border-gray-100">
                    <span class="font-medium text-gray-500"><?php echo date('d M Y', strtotime($post['created_at'])); ?></span>
                    <a href="<?php echo getBlogPostUrl($post); ?>" class="text-blue-600 hover:text-blue-800 font-bold flex items-center gap-1">Read More &rarr;</a>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <?php if ($pages > 1): ?>
        <div class="flex justify-center mt-8 gap-1">
            <?php for ($i = 1; $i <= $pages; $i++): ?>
            <a href="?p=<?php echo $i; ?><?php echo $cat_slug ? '&category='.urlencode($cat_slug) : ''; ?><?php echo $search_q ? '&search='.urlencode($search_q) : ''; ?>" class="px-3 py-1.5 rounded text-sm <?php echo $i == $page ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
        <?php else: ?>
        <div class="bg-white border rounded-xl p-12 text-center text-gray-500">
            <i class="fa fa-newspaper text-5xl text-gray-300 mb-4 block"></i>
            <h3 class="text-xl font-bold text-gray-700 mb-2">No Posts Found</h3>
            <p class="text-gray-500 mb-4">No blog posts matched your criteria.</p>
            <a href="/blog" class="btn btn-blue inline-block">View All Posts</a>
        </div>
        <?php endif; ?>
    </div>

    <!-- Sidebar -->
    <div class="w-full lg:w-80 space-y-6">
        <!-- Search -->
        <div class="bg-white border rounded-xl p-6 shadow-sm">
            <h3 class="font-bold text-gray-900 mb-3">Search</h3>
            <form method="GET" action="/blog">
                <?php if ($cat_slug): ?><input type="hidden" name="category" value="<?php echo htmlspecialchars($cat_slug); ?>"><?php endif; ?>
                <div class="flex gap-2">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search_q); ?>" placeholder="Search posts..." class="flex-1 border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700 transition"><i class="fa fa-search"></i></button>
                </div>
            </form>
        </div>

        <!-- Categories -->
        <div class="bg-white border rounded-xl p-6 shadow-sm">
            <h3 class="font-bold text-gray-900 mb-3">Categories</h3>
            <div class="space-y-2">
                <a href="/blog" class="block text-sm <?php echo empty($cat_slug) ? 'text-blue-600 font-medium' : 'text-gray-600 hover:text-blue-600'; ?>">
                    All Posts
                </a>
                <?php
                $all_cats = mysqli_query($conn, "SELECT * FROM blog_categories WHERE status = 1 ORDER BY name");
                while ($cat = mysqli_fetch_assoc($all_cats)):
                    $count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM blog_posts WHERE category_id = {$cat['id']} AND status = 1"));
                ?>
                <a href="/blog/category/<?php echo urlencode($cat['slug']); ?>" class="block text-sm <?php echo $cat_slug === $cat['slug'] ? 'text-blue-600 font-medium' : 'text-gray-600 hover:text-blue-600'; ?>">
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@accessible360/accessible-slick@1.0.1/slick/slick.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fancyapps/fancybox@3.5.6/dist/jquery.fancybox.min.js"></script>
<script src="https://unpkg.com/alpinejs@3.14.9/dist/cdn.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js"></script>
<script src="https://unpkg.com/@material-tailwind/html@3.0.0-beta.7/scripts/ripple.js"></script>
<script src="https://unpkg.com/@material-tailwind/html@2.0.0/scripts/collapse.js"></script>
<script src="https://unpkg.com/@material-tailwind/html@2.0.0/scripts/dialog.js"></script>
<script src="https://unpkg.com/@material-tailwind/html@2.0.0/scripts/dismissible.js"></script>
<script type="module" src="https://unpkg.com/@material-tailwind/html@2.0.0/scripts/popover.js"></script>
<script src="https://unpkg.com/@material-tailwind/html@2.0.0/scripts/tabs.js"></script>
<script type="module" src="https://unpkg.com/@material-tailwind/html@2.0.0/scripts/tooltip.js"></script>
<script src="/js/scroll.js"></script>
<script src="/js/ns.js"></script>
<script src="/js/ns-jquery.js"></script>
</body>
</html>
