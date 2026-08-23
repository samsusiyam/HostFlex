<?php 
require_once 'config/database.php'; 
require_once 'includes/functions.php'; 
checkMaintenance();
ensureBlogSchema();

$site_name = getSetting('site_name') ?: 'Host Nibo';
$cat_slug = $_GET['category'] ?? '';
$search_q = trim($_GET['search'] ?? '');

$current_cat = null;
if ($cat_slug) {
    $cat_slug_esc = mysqli_real_escape_string($conn, $cat_slug);
    $cat_res = mysqli_query($conn, "SELECT * FROM blog_categories WHERE slug = '$cat_slug_esc' AND status = 1 LIMIT 1");
    if ($cat_res && mysqli_num_rows($cat_res) > 0) {
        $current_cat = mysqli_fetch_assoc($cat_res);
    }
}

// Page title and SEO metadata
if ($current_cat) {
    $page_title = htmlspecialchars($current_cat['name']) . ' - Blog - ' . $site_name;
    $meta_desc = $current_cat['description'] ?: ("Articles and tutorials about " . $current_cat['name'] . " on " . $site_name);
} elseif ($search_q) {
    $page_title = 'Search: ' . htmlspecialchars($search_q) . ' - Blog - ' . $site_name;
    $meta_desc = 'Search results for ' . htmlspecialchars($search_q) . ' on ' . $site_name . ' blog.';
} else {
    $page_title = 'Blog & Resources - ' . $site_name;
    $meta_desc = 'Read our latest blog posts, tutorials, web hosting tips, server security guides and updates.';
}

// Query conditions
$where = "WHERE p.status = 1";
if ($current_cat) {
    $cat_id = (int)$current_cat['id'];
    $where .= " AND p.category_id = $cat_id";
} elseif ($cat_slug) {
    $cat_slug_esc = mysqli_real_escape_string($conn, $cat_slug);
    $where .= " AND c.slug = '$cat_slug_esc'";
}
if ($search_q) {
    $search_esc = mysqli_real_escape_string($conn, $search_q);
    $where .= " AND (p.title LIKE '%$search_esc%' OR p.content LIKE '%$search_esc%' OR p.excerpt LIKE '%$search_esc%')";
}

$page = max(1, (int)($_GET['p'] ?? 1));
$per_page = 8;
$offset = ($page - 1) * $per_page;

$total_res = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM blog_posts p LEFT JOIN blog_categories c ON p.category_id = c.id $where"));
$total = (int)($total_res['c'] ?? 0);
$pages = ceil($total / $per_page);

$posts = mysqli_query($conn, "SELECT p.*, c.name as category_name, c.slug as category_slug FROM blog_posts p LEFT JOIN blog_categories c ON p.category_id = c.id $where ORDER BY p.created_at DESC LIMIT $per_page OFFSET $offset");

// All Categories for filter pills and sidebar
$all_categories = [];
$cat_query = mysqli_query($conn, "SELECT c.*, COUNT(p.id) as post_count FROM blog_categories c LEFT JOIN blog_posts p ON c.id = p.category_id AND p.status = 1 WHERE c.status = 1 GROUP BY c.id ORDER BY c.name ASC");
while ($c = mysqli_fetch_assoc($cat_query)) {
    $all_categories[] = $c;
}
$total_all_posts = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM blog_posts WHERE status = 1"))['c'] ?? 0);

// Recent Posts for sidebar
$recent_posts_res = mysqli_query($conn, "SELECT p.*, c.name as category_name, c.slug as category_slug FROM blog_posts p LEFT JOIN blog_categories c ON p.category_id = c.id WHERE p.status = 1 ORDER BY p.created_at DESC LIMIT 4");
$recent_posts = [];
while ($rp = mysqli_fetch_assoc($recent_posts_res)) {
    $recent_posts[] = $rp;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include "cdnjs.php"; ?>
<title><?php echo $page_title; ?></title>
<?php echo renderSeoTags([
    'title' => $page_title,
    'description' => $meta_desc
]); ?>
</head>
<body class="bg-gray-50/50">
<?php include "header.php"; ?>
<?php include "contact-btn.php"; ?>

<!-- Hero Header Section -->
<div class="page-hero">
    <div class="content mx-auto">
        <div class="flex flex-col md:flex-row items-center justify-between gap-6">
            <div class="text-center md:text-left">
                <!-- Breadcrumb -->
                <nav class="flex items-center justify-center md:justify-start gap-2 text-xs text-blue-200 mb-3 font-medium">
                    <a href="/" class="hover:text-white transition flex items-center gap-1"><i class="fa-solid fa-house"></i> Home</a>
                    <span>/</span>
                    <a href="/blog" class="hover:text-white transition">Blog</a>
                    <?php if ($current_cat): ?>
                    <span>/</span>
                    <span class="text-white font-bold truncate max-w-xs"><?php echo htmlspecialchars($current_cat['name']); ?></span>
                    <?php elseif ($search_q): ?>
                    <span>/</span>
                    <span class="text-white font-bold">Search</span>
                    <?php endif; ?>
                </nav>

                <h1 class="text-3xl md:text-4xl lg:text-5xl font-extrabold text-white mb-2.5 tracking-tight">
                    <?php 
                    if ($current_cat) {
                        echo htmlspecialchars($current_cat['name']);
                    } elseif ($search_q) {
                        echo 'Search Results';
                    } else {
                        echo 'Blog & Insights';
                    }
                    ?>
                </h1>
                <p class="text-blue-100 text-sm md:text-base max-w-xl leading-relaxed">
                    <?php 
                    if ($current_cat && !empty($current_cat['description'])) {
                        echo htmlspecialchars($current_cat['description']);
                    } elseif ($search_q) {
                        echo 'Showing articles matching keyword: "' . htmlspecialchars($search_q) . '"';
                    } else {
                        echo 'Expert hosting guides, server management tutorials, and tips to grow your website.';
                    }
                    ?>
                </p>
            </div>

            <!-- Search Form in Hero -->
            <div class="w-full md:w-80 shrink-0">
                <form method="GET" action="/blog" class="relative">
                    <?php if ($cat_slug): ?><input type="hidden" name="category" value="<?php echo htmlspecialchars($cat_slug); ?>"><?php endif; ?>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search_q); ?>" placeholder="Search articles..." class="w-full bg-white/10 backdrop-blur-md border border-white/25 text-white placeholder-blue-200 rounded-full px-5 py-3 pr-12 text-sm focus:outline-none focus:bg-white focus:text-gray-900 focus:placeholder-gray-400 shadow-sm transition">
                    <button type="submit" class="absolute right-1.5 top-1.5 bottom-1.5 w-9 bg-white text-blue-600 rounded-full flex items-center justify-center hover:bg-blue-50 transition shadow-xs">
                        <i class="fa-solid fa-magnifying-glass text-xs"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<section class="py-8 md:py-12">
<div class="content">

    <!-- Category Filter Bar (Horizontal Scrollable Pills for Mobile & Desktop) -->
    <div class="blog-category-nav">
        <a href="/blog<?php echo $search_q ? '?search='.urlencode($search_q) : ''; ?>" class="blog-pill <?php echo empty($cat_slug) ? 'active' : ''; ?>">
            <span>All Articles</span>
            <span class="pill-count"><?php echo $total_all_posts; ?></span>
        </a>
        <?php foreach ($all_categories as $cat): ?>
        <a href="/blog/category/<?php echo urlencode($cat['slug']); ?><?php echo $search_q ? '?search='.urlencode($search_q) : ''; ?>" class="blog-pill <?php echo $cat_slug === $cat['slug'] ? 'active' : ''; ?>">
            <span><?php echo htmlspecialchars($cat['name']); ?></span>
            <span class="pill-count"><?php echo $cat['post_count']; ?></span>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Active Search or Filter Notice -->
    <?php if ($search_q || $cat_slug): ?>
    <div class="flex items-center justify-between bg-blue-50/70 border border-blue-100 rounded-xl px-4 py-2.5 mb-6 text-xs text-blue-900">
        <div class="flex items-center gap-2">
            <i class="fa-solid fa-filter text-blue-600"></i>
            <span>
                Filter: 
                <?php if ($cat_slug && $current_cat): ?>Category <strong>"<?php echo htmlspecialchars($current_cat['name']); ?>"</strong><?php endif; ?>
                <?php if ($search_q): ?><?php echo $cat_slug ? ' + ' : ''; ?>Search <strong>"<?php echo htmlspecialchars($search_q); ?>"</strong><?php endif; ?>
                (<?php echo $total; ?> found)
            </span>
        </div>
        <a href="/blog" class="text-blue-600 hover:text-blue-800 font-bold flex items-center gap-1 hover:underline">
            <i class="fa-solid fa-xmark"></i> Clear Filters
        </a>
    </div>
    <?php endif; ?>

    <!-- Main Content & Sidebar Layout -->
    <div class="flex flex-col lg:flex-row gap-8 lg:gap-10 items-start">
        
        <!-- Articles Grid (Left 2/3) -->
        <div class="flex-1 min-w-0 w-full">
            <?php if (mysqli_num_rows($posts) > 0): ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <?php while ($post = mysqli_fetch_assoc($posts)): ?>
                <article class="blog-card group">
                    <div>
                        <div class="blog-card-img-box">
                            <?php if (!empty($post['image'])): ?>
                            <a href="<?php echo getBlogPostUrl($post); ?>" class="block w-full h-full">
                                <img src="<?php echo htmlspecialchars(getImageUrl($post['image'])); ?>" class="blog-card-img" alt="<?php echo htmlspecialchars($post['title']); ?>" loading="lazy">
                            </a>
                            <?php else: ?>
                            <a href="<?php echo getBlogPostUrl($post); ?>" class="blog-thumb-placeholder block w-full h-full">
                                <i class="fa-solid fa-newspaper opacity-75"></i>
                            </a>
                            <?php endif; ?>

                            <?php if (!empty($post['category_name'])): ?>
                            <a href="/blog/category/<?php echo urlencode($post['category_slug']); ?>" class="blog-card-badge">
                                <?php echo htmlspecialchars($post['category_name']); ?>
                            </a>
                            <?php endif; ?>
                        </div>

                        <div class="blog-card-body">
                            <div class="blog-card-meta">
                                <span class="flex items-center gap-1"><i class="fa-regular fa-calendar"></i> <?php echo date('M d, Y', strtotime($post['created_at'])); ?></span>
                                <span>•</span>
                                <span class="flex items-center gap-1 text-blue-600 font-semibold"><i class="fa-regular fa-clock"></i> <?php echo getReadingTime($post['content']); ?> min</span>
                            </div>
                            
                            <h3 class="blog-card-title">
                                <a href="<?php echo getBlogPostUrl($post); ?>" class="text-gray-900 group-hover:text-blue-600 transition">
                                    <?php echo htmlspecialchars($post['title']); ?>
                                </a>
                            </h3>

                            <p class="blog-card-excerpt">
                                <?php echo htmlspecialchars($post['excerpt'] ?: substr(strip_tags($post['content']), 0, 140) . '...'); ?>
                            </p>
                        </div>
                    </div>

                    <div class="blog-card-footer">
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 rounded-full bg-blue-600 text-white font-bold flex items-center justify-center text-[10px]">
                                <?php echo strtoupper(substr($post['author'] ?: 'A', 0, 1)); ?>
                            </div>
                            <span class="text-xs font-semibold text-gray-700"><?php echo htmlspecialchars($post['author'] ?: 'Admin'); ?></span>
                        </div>
                        <a href="<?php echo getBlogPostUrl($post); ?>" class="text-blue-600 font-bold hover:text-blue-800 flex items-center gap-1 group-hover:translate-x-1 transition-transform">
                            Read More <i class="fa-solid fa-arrow-right text-[10px]"></i>
                        </a>
                    </div>
                </article>
                <?php endwhile; ?>
            </div>

            <!-- Numbered Pagination -->
            <?php if ($pages > 1): ?>
            <div class="flex justify-center items-center mt-10 gap-1.5 flex-wrap">
                <?php if ($page > 1): ?>
                <a href="?p=<?php echo ($page - 1); ?><?php echo $cat_slug ? '&category='.urlencode($cat_slug) : ''; ?><?php echo $search_q ? '&search='.urlencode($search_q) : ''; ?>" class="px-3.5 py-2 rounded-xl text-xs font-bold bg-white border border-gray-200 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition shadow-xs">
                    <i class="fa-solid fa-chevron-left text-[10px] mr-1"></i> Prev
                </a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $pages; $i++): ?>
                <a href="?p=<?php echo $i; ?><?php echo $cat_slug ? '&category='.urlencode($cat_slug) : ''; ?><?php echo $search_q ? '&search='.urlencode($search_q) : ''; ?>" class="w-9 h-9 flex items-center justify-center rounded-xl text-xs font-bold transition <?php echo $i == $page ? 'bg-blue-600 text-white shadow-md shadow-blue-500/30' : 'bg-white border border-gray-200 text-gray-700 hover:bg-gray-50'; ?>">
                    <?php echo $i; ?>
                </a>
                <?php endfor; ?>

                <?php if ($page < $pages): ?>
                <a href="?p=<?php echo ($page + 1); ?><?php echo $cat_slug ? '&category='.urlencode($cat_slug) : ''; ?><?php echo $search_q ? '&search='.urlencode($search_q) : ''; ?>" class="px-3.5 py-2 rounded-xl text-xs font-bold bg-white border border-gray-200 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition shadow-xs">
                    Next <i class="fa-solid fa-chevron-right text-[10px] ml-1"></i>
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php else: ?>
            <!-- Empty State -->
            <div class="bg-white border border-gray-200 rounded-2xl p-12 text-center shadow-xs">
                <div class="w-16 h-16 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </div>
                <h3 class="text-xl font-extrabold text-gray-900 mb-2">No Articles Found</h3>
                <p class="text-sm text-gray-500 max-w-sm mx-auto mb-6">We couldn't find any articles matching your search. Try searching with different keywords or browse all categories.</p>
                <a href="/blog" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-bold px-6 py-2.5 rounded-xl text-sm transition shadow-sm">
                    <i class="fa-solid fa-arrow-left text-xs"></i> View All Articles
                </a>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar Widgets (Right 1/3) -->
        <aside class="w-full lg:w-80 space-y-6 shrink-0 lg:sticky lg:top-24">
            
            <!-- Widget 1: Categories List -->
            <div class="blog-sidebar-widget">
                <h3 class="blog-widget-title">
                    <i class="fa-solid fa-folder-open text-blue-600"></i> Categories
                </h3>
                <div class="space-y-1.5">
                    <a href="/blog" class="flex items-center justify-between px-3 py-2 rounded-lg text-xs font-semibold transition <?php echo empty($cat_slug) ? 'bg-blue-50 text-blue-700 font-bold' : 'text-gray-600 hover:bg-gray-50 hover:text-blue-600'; ?>">
                        <span class="flex items-center gap-2"><i class="fa-solid fa-layer-group text-[10px] text-gray-400"></i> All Categories</span>
                        <span class="text-[11px] px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 font-medium"><?php echo $total_all_posts; ?></span>
                    </a>
                    <?php foreach ($all_categories as $cat): ?>
                    <a href="/blog/category/<?php echo urlencode($cat['slug']); ?>" class="flex items-center justify-between px-3 py-2 rounded-lg text-xs font-semibold transition <?php echo $cat_slug === $cat['slug'] ? 'bg-blue-50 text-blue-700 font-bold' : 'text-gray-600 hover:bg-gray-50 hover:text-blue-600'; ?>">
                        <span class="flex items-center gap-2"><i class="fa-solid fa-angle-right text-[10px] text-gray-400"></i> <?php echo htmlspecialchars($cat['name']); ?></span>
                        <span class="text-[11px] px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 font-medium"><?php echo $cat['post_count']; ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Widget 2: Recent Posts -->
            <?php if (!empty($recent_posts)): ?>
            <div class="blog-sidebar-widget">
                <h3 class="blog-widget-title">
                    <i class="fa-solid fa-bolt text-amber-500"></i> Recent Articles
                </h3>
                <div class="blog-recent-list">
                    <?php foreach ($recent_posts as $rp): ?>
                    <a href="<?php echo getBlogPostUrl($rp); ?>" class="blog-recent-item group">
                        <?php if (!empty($rp['image'])): ?>
                        <img src="<?php echo htmlspecialchars(getImageUrl($rp['image'])); ?>" class="blog-recent-thumb" alt="" loading="lazy">
                        <?php else: ?>
                        <div class="blog-recent-thumb blog-thumb-placeholder text-base">
                            <i class="fa-solid fa-newspaper opacity-75"></i>
                        </div>
                        <?php endif; ?>
                        <div class="blog-recent-info">
                            <h4 class="blog-recent-title">
                                <?php echo htmlspecialchars($rp['title']); ?>
                            </h4>
                            <div class="blog-recent-date">
                                <i class="fa-regular fa-calendar text-[10px] mr-1"></i> <?php echo date('M d, Y', strtotime($rp['created_at'])); ?>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Widget 3: NVMe Cloud Hosting Promo Card -->
            <div class="blog-promo-card">
                <div class="w-12 h-12 rounded-2xl bg-white/10 border border-white/20 flex items-center justify-center mx-auto mb-3 text-xl text-yellow-300">
                    <i class="fa-solid fa-rocket"></i>
                </div>
                <h4 class="text-base font-extrabold text-white mb-1.5">Lightning Fast NVMe Hosting</h4>
                <p class="text-xs text-blue-100 leading-relaxed mb-0">
                    Experience up to 20x faster page loading speeds with 99.9% guaranteed uptime & 24/7 expert support.
                </p>
                <a href="/offers" class="blog-promo-btn">
                    Explore Hosting Plans &rarr;
                </a>
            </div>

        </aside>

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
