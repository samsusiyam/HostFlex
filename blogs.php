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
    $page_title = 'Blog - ' . $site_name;
    $meta_desc = 'Read our latest blog posts about web hosting, WordPress, cloud servers, tips and updates.';
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
$per_page = 9;
$offset = ($page - 1) * $per_page;

$total_res = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM blog_posts p LEFT JOIN blog_categories c ON p.category_id = c.id $where"));
$total = (int)($total_res['c'] ?? 0);
$pages = ceil($total / $per_page);

$posts = mysqli_query($conn, "SELECT p.*, c.name as category_name, c.slug as category_slug FROM blog_posts p LEFT JOIN blog_categories c ON p.category_id = c.id $where ORDER BY p.created_at DESC LIMIT $per_page OFFSET $offset");
$categories = mysqli_query($conn, "SELECT c.*, COUNT(p.id) as post_count FROM blog_categories c LEFT JOIN blog_posts p ON c.id = p.category_id AND p.status = 1 WHERE c.status = 1 GROUP BY c.id ORDER BY c.name ASC");
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
<body>
<?php include "header.php"; ?>
<?php include "contact-btn.php"; ?>

<section class="section_gap bg-white">
<div class="content">
    
    <!-- Page Header (Clean & Centered) -->
    <div class="mb-8 md:mb-12 text-center">
        <h1 class="text-3xl md:text-4xl font-extrabold text-gray-900 tracking-tight">
            <?php 
            if ($current_cat) {
                echo htmlspecialchars($current_cat['name']);
            } elseif ($search_q) {
                echo 'Search Results';
            } else {
                echo 'Our Blog';
            }
            ?>
        </h1>
        <p class="text-gray-500 mt-2 text-sm md:text-base max-w-lg mx-auto">
            <?php 
            if ($current_cat && !empty($current_cat['description'])) {
                echo htmlspecialchars($current_cat['description']);
            } elseif ($search_q) {
                echo 'Articles matching "' . htmlspecialchars($search_q) . '" (' . $total . ' found)';
            } else {
                echo 'Latest news, tutorials, and web hosting updates';
            }
            ?>
        </p>
    </div>

    <!-- Active Filter Notice -->
    <?php if ($search_q || $cat_slug): ?>
    <div class="flex items-center justify-between bg-blue-50 border border-blue-100 rounded-xl px-4 py-2.5 mb-6 text-xs text-blue-900">
        <div class="flex items-center gap-2 flex-wrap">
            <i class="fa-solid fa-filter text-blue-600"></i>
            <span>
                Showing posts 
                <?php if ($current_cat): ?>in <strong><?php echo htmlspecialchars($current_cat['name']); ?></strong><?php endif; ?>
                <?php if ($search_q): ?>for <strong>"<?php echo htmlspecialchars($search_q); ?>"</strong><?php endif; ?>
                (<?php echo $total; ?> found)
            </span>
        </div>
        <a href="/blog" class="text-blue-600 hover:text-blue-800 font-bold flex items-center gap-1 hover:underline ml-2">
            <i class="fa-solid fa-xmark"></i> Clear Filters
        </a>
    </div>
    <?php endif; ?>

    <!-- Mobile Top Search Widget (Visible on Mobile Only) -->
    <div class="blog-mobile-search">
        <div class="blog-search-card">
            <form method="GET" action="/blog" class="blog-search-form">
                <?php if ($cat_slug): ?><input type="hidden" name="category" value="<?php echo htmlspecialchars($cat_slug); ?>"><?php endif; ?>
                <div class="blog-search-input-wrapper">
                    <i class="fa-solid fa-magnifying-glass blog-search-icon"></i>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search_q); ?>" placeholder="Search posts..." class="blog-search-input" autocomplete="off">
                </div>
                <button type="submit" class="blog-search-submit">
                    <span>Search</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Main Content & Sidebar Layout -->
    <div class="blog-layout-wrapper">
        
        <!-- Posts Column (Left) -->
        <main class="blog-main-column">
            <?php if (mysqli_num_rows($posts) > 0): ?>
            <div class="blog-grid">
                <?php while ($post = mysqli_fetch_assoc($posts)): ?>
                <article class="blog-card">
                    <div>
                        <div class="blog-card-thumb-wrap">
                            <?php if (!empty($post['image'])): ?>
                            <a href="<?php echo getBlogPostUrl($post); ?>" class="block w-full h-full">
                                <img src="<?php echo htmlspecialchars(getImageUrl($post['image'])); ?>" class="blog-card-thumb" alt="<?php echo htmlspecialchars($post['title']); ?>" loading="lazy">
                            </a>
                            <?php else: ?>
                            <a href="<?php echo getBlogPostUrl($post); ?>" class="blog-thumb-placeholder block w-full h-full" style="height:100%;">
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
                                <span class="flex items-center gap-1">
                                    <i class="fa-regular fa-calendar text-[10px] text-gray-400"></i> <?php echo date('d M Y', strtotime($post['created_at'])); ?>
                                </span>
                                <span class="flex items-center gap-1 text-blue-600 font-semibold">
                                    <i class="fa-regular fa-clock text-[10px]"></i> <?php echo getReadingTime($post['content']); ?> min
                                </span>
                            </div>

                            <h3 class="blog-card-title">
                                <a href="<?php echo getBlogPostUrl($post); ?>">
                                    <?php echo htmlspecialchars($post['title']); ?>
                                </a>
                            </h3>
                            
                            <p class="blog-card-excerpt">
                                <?php echo htmlspecialchars($post['excerpt'] ?: substr(strip_tags($post['content']), 0, 130) . '...'); ?>
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
                        <a href="<?php echo getBlogPostUrl($post); ?>" class="blog-card-readmore">
                            Read More <i class="fa-solid fa-arrow-right text-[10px]"></i>
                        </a>
                    </div>
                </article>
                <?php endwhile; ?>
            </div>

            <!-- Numbered Pagination -->
            <?php if ($pages > 1): ?>
            <nav class="blog-pagination" aria-label="Blog Pagination">
                <?php if ($page > 1): ?>
                <a href="?p=<?php echo ($page - 1); ?><?php echo $cat_slug ? '&category='.urlencode($cat_slug) : ''; ?><?php echo $search_q ? '&search='.urlencode($search_q) : ''; ?>" class="blog-page-btn">
                    &larr; Prev
                </a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $pages; $i++): ?>
                <a href="?p=<?php echo $i; ?><?php echo $cat_slug ? '&category='.urlencode($cat_slug) : ''; ?><?php echo $search_q ? '&search='.urlencode($search_q) : ''; ?>" class="blog-page-btn <?php echo $i == $page ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
                <?php endfor; ?>

                <?php if ($page < $pages): ?>
                <a href="?p=<?php echo ($page + 1); ?><?php echo $cat_slug ? '&category='.urlencode($cat_slug) : ''; ?><?php echo $search_q ? '&search='.urlencode($search_q) : ''; ?>" class="blog-page-btn">
                    Next &rarr;
                </a>
                <?php endif; ?>
            </nav>
            <?php endif; ?>

            <?php else: ?>
            <!-- No Posts Found -->
            <div class="bg-white border border-gray-200 rounded-2xl p-10 sm:p-14 text-center text-gray-500">
                <i class="fa-solid fa-newspaper text-5xl text-gray-300 mb-3 block"></i>
                <h3 class="text-xl font-bold text-gray-800 mb-1">No Posts Found</h3>
                <p class="text-sm text-gray-500 mb-5">No blog posts matched your criteria.</p>
                <a href="/blog" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold px-5 py-2.5 rounded-lg text-xs transition">
                    View All Posts
                </a>
            </div>
            <?php endif; ?>
        </main>

        <!-- Sidebar (Right) -->
        <aside class="blog-sidebar-column">
            
            <!-- Desktop Search Widget (Hidden on Mobile) -->
            <div class="blog-desktop-search">
                <div class="blog-search-card">
                    <h3 class="blog-widget-title">
                        <i class="fa-solid fa-magnifying-glass text-blue-600 text-xs"></i> Search Blog
                    </h3>
                    <form method="GET" action="/blog" class="blog-search-form">
                        <?php if ($cat_slug): ?><input type="hidden" name="category" value="<?php echo htmlspecialchars($cat_slug); ?>"><?php endif; ?>
                        <div class="blog-search-input-wrapper">
                            <i class="fa-solid fa-magnifying-glass blog-search-icon"></i>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search_q); ?>" placeholder="Search posts..." class="blog-search-input" autocomplete="off">
                        </div>
                        <button type="submit" class="blog-search-submit">
                            <span>Search</span>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Categories Widget -->
            <div class="blog-sidebar-widget">
                <h3 class="blog-widget-title">
                    <i class="fa-solid fa-folder-open text-blue-600 text-xs"></i> Categories
                </h3>
                <div class="blog-cat-list">
                    <a href="/blog" class="blog-cat-link <?php echo empty($cat_slug) ? 'active' : ''; ?>">
                        <span>All Posts</span>
                        <span class="blog-cat-count"><?php echo $total_all_posts; ?></span>
                    </a>
                    <?php while ($cat = mysqli_fetch_assoc($categories)): ?>
                    <a href="/blog/category/<?php echo urlencode($cat['slug']); ?>" class="blog-cat-link <?php echo $cat_slug === $cat['slug'] ? 'active' : ''; ?>">
                        <span><?php echo htmlspecialchars($cat['name']); ?></span>
                        <span class="blog-cat-count"><?php echo $cat['post_count']; ?></span>
                    </a>
                    <?php endwhile; ?>
                </div>
            </div>

            <!-- Recent Posts Widget -->
            <?php if (!empty($recent_posts)): ?>
            <div class="blog-sidebar-widget">
                <h3 class="blog-widget-title">
                    <i class="fa-solid fa-bolt text-amber-500 text-xs"></i> Recent Articles
                </h3>
                <div class="blog-recent-list">
                    <?php foreach ($recent_posts as $rp): ?>
                    <a href="<?php echo getBlogPostUrl($rp); ?>" class="blog-recent-item">
                        <?php if (!empty($rp['image'])): ?>
                        <img src="<?php echo htmlspecialchars(getImageUrl($rp['image'])); ?>" class="blog-recent-thumb" alt="" loading="lazy">
                        <?php else: ?>
                        <div class="blog-recent-thumb blog-thumb-placeholder" style="height:60px;width:60px;font-size:16px;">
                            <i class="fa-solid fa-newspaper opacity-75"></i>
                        </div>
                        <?php endif; ?>
                        <div class="blog-recent-info">
                            <h4 class="blog-recent-title">
                                <?php echo htmlspecialchars($rp['title']); ?>
                            </h4>
                            <div class="blog-recent-date">
                                <i class="fa-regular fa-calendar text-[10px] mr-1"></i> <?php echo date('d M Y', strtotime($rp['created_at'])); ?>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Hosting Promo CTA Card -->
            <div class="blog-promo-card">
                <div class="w-12 h-12 rounded-2xl bg-white/10 border border-white/20 flex items-center justify-center mx-auto mb-3 text-xl text-yellow-300">
                    <i class="fa-solid fa-rocket"></i>
                </div>
                <h4 class="text-base font-extrabold text-white mb-1.5">Fast NVMe Hosting</h4>
                <p class="text-xs text-blue-100 leading-relaxed mb-0">
                    Get premium cloud hosting with 99.9% uptime guarantee & 24/7 support.
                </p>
                <a href="/offers" class="blog-promo-btn">
                    Explore Plans &rarr;
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
