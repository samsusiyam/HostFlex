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
    <div class="flex items-center justify-between bg-blue-50 border border-blue-100 rounded-xl px-4 py-2.5 mb-6 text-xs text-blue-900 max-w-4xl mx-auto">
        <div class="flex items-center gap-2 flex-wrap">
            <i class="fa-solid fa-filter text-blue-600"></i>
            <span>
                Showing posts 
                <?php if ($current_cat): ?>in <strong><?php echo htmlspecialchars($current_cat['name']); ?></strong><?php endif; ?>
                <?php if ($search_q): ?>for <strong>"<?php echo htmlspecialchars($search_q); ?>"</strong><?php endif; ?>
            </span>
        </div>
        <a href="/blog" class="text-blue-600 hover:text-blue-800 font-bold flex items-center gap-1 hover:underline ml-2">
            <i class="fa-solid fa-xmark"></i> Clear
        </a>
    </div>
    <?php endif; ?>

    <!-- Main Content & Sidebar Grid -->
    <div class="flex flex-col lg:flex-row gap-8">
        
        <!-- Posts Column (Left) -->
        <div class="flex-1 min-w-0">
            <?php if (mysqli_num_rows($posts) > 0): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php while ($post = mysqli_fetch_assoc($posts)): ?>
                <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden shadow-xs hover:shadow-md hover:-translate-y-1 transition-all duration-200 flex flex-col justify-between">
                    <div>
                        <?php if (!empty($post['image'])): ?>
                        <a href="<?php echo getBlogPostUrl($post); ?>" class="block overflow-hidden h-44 sm:h-48 bg-gray-100 relative group">
                            <img src="<?php echo htmlspecialchars(getImageUrl($post['image'])); ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-300" alt="<?php echo htmlspecialchars($post['title']); ?>" loading="lazy">
                        </a>
                        <?php else: ?>
                        <a href="<?php echo getBlogPostUrl($post); ?>" class="block overflow-hidden h-44 sm:h-48 blog-thumb-placeholder">
                            <i class="fa-solid fa-newspaper opacity-75"></i>
                        </a>
                        <?php endif; ?>

                        <div class="p-4 sm:p-5">
                            <div class="flex items-center justify-between gap-2 mb-2.5">
                                <?php if (!empty($post['category_name'])): ?>
                                <a href="/blog/category/<?php echo urlencode($post['category_slug']); ?>" class="text-[11px] text-blue-600 font-bold uppercase tracking-wider bg-blue-50 px-2.5 py-0.5 rounded-full border border-blue-100 hover:bg-blue-600 hover:text-white transition">
                                    <?php echo htmlspecialchars($post['category_name']); ?>
                                </a>
                                <?php else: ?>
                                <span class="text-[11px] text-gray-400 font-medium">Article</span>
                                <?php endif; ?>
                                <span class="text-[11px] text-gray-400 font-medium flex items-center gap-1">
                                    <i class="fa-regular fa-clock text-[10px]"></i> <?php echo getReadingTime($post['content']); ?> min
                                </span>
                            </div>

                            <h3 class="text-base font-bold mb-2 leading-snug">
                                <a href="<?php echo getBlogPostUrl($post); ?>" class="text-gray-900 hover:text-blue-600 transition line-clamp-2">
                                    <?php echo htmlspecialchars($post['title']); ?>
                                </a>
                            </h3>
                            
                            <p class="text-xs text-gray-500 line-clamp-2 leading-relaxed">
                                <?php echo htmlspecialchars($post['excerpt'] ?: substr(strip_tags($post['content']), 0, 130) . '...'); ?>
                            </p>
                        </div>
                    </div>

                    <div class="px-4 sm:px-5 pb-4 pt-2.5 flex items-center justify-between text-xs text-gray-400 border-t border-gray-100 mt-auto">
                        <span class="font-medium text-gray-500 flex items-center gap-1">
                            <i class="fa-regular fa-calendar text-[10px] text-gray-400"></i> <?php echo date('d M Y', strtotime($post['created_at'])); ?>
                        </span>
                        <a href="<?php echo getBlogPostUrl($post); ?>" class="text-blue-600 hover:text-blue-800 font-bold flex items-center gap-1">
                            Read More <i class="fa-solid fa-arrow-right text-[10px]"></i>
                        </a>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>

            <!-- Numbered Pagination -->
            <?php if ($pages > 1): ?>
            <div class="flex justify-center items-center mt-10 gap-1.5 flex-wrap">
                <?php if ($page > 1): ?>
                <a href="?p=<?php echo ($page - 1); ?><?php echo $cat_slug ? '&category='.urlencode($cat_slug) : ''; ?><?php echo $search_q ? '&search='.urlencode($search_q) : ''; ?>" class="px-3.5 py-1.5 rounded-lg text-xs font-semibold bg-gray-100 hover:bg-gray-200 text-gray-700 transition">
                    &larr; Prev
                </a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $pages; $i++): ?>
                <a href="?p=<?php echo $i; ?><?php echo $cat_slug ? '&category='.urlencode($cat_slug) : ''; ?><?php echo $search_q ? '&search='.urlencode($search_q) : ''; ?>" class="w-8 h-8 flex items-center justify-center rounded-lg text-xs font-bold transition <?php echo $i == $page ? 'bg-blue-600 text-white shadow-xs' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                    <?php echo $i; ?>
                </a>
                <?php endfor; ?>

                <?php if ($page < $pages): ?>
                <a href="?p=<?php echo ($page + 1); ?><?php echo $cat_slug ? '&category='.urlencode($cat_slug) : ''; ?><?php echo $search_q ? '&search='.urlencode($search_q) : ''; ?>" class="px-3.5 py-1.5 rounded-lg text-xs font-semibold bg-gray-100 hover:bg-gray-200 text-gray-700 transition">
                    Next &rarr;
                </a>
                <?php endif; ?>
            </div>
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
        </div>

        <!-- Sidebar (Right) -->
        <div class="w-full lg:w-80 space-y-6">
            
            <!-- Modern Search Widget -->
            <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-xs">
                <h3 class="text-sm font-bold text-gray-900 mb-3 flex items-center gap-2">
                    <i class="fa-solid fa-magnifying-glass text-blue-600 text-xs"></i> Search Blog
                </h3>
                <form method="GET" action="/blog">
                    <?php if ($cat_slug): ?><input type="hidden" name="category" value="<?php echo htmlspecialchars($cat_slug); ?>"><?php endif; ?>
                    <div class="relative flex items-center">
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search_q); ?>" placeholder="Search posts..." class="w-full border border-gray-300 rounded-xl pl-3.5 pr-10 py-2.5 text-xs text-gray-800 focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600 transition">
                        <button type="submit" class="absolute right-1.5 bg-blue-600 hover:bg-blue-700 text-white w-7 h-7 rounded-lg flex items-center justify-center text-xs transition">
                            <i class="fa-solid fa-arrow-right"></i>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Categories Widget -->
            <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-xs">
                <h3 class="text-sm font-bold text-gray-900 mb-3 flex items-center gap-2">
                    <i class="fa-solid fa-folder-open text-blue-600 text-xs"></i> Categories
                </h3>
                <div class="space-y-1">
                    <a href="/blog" class="flex items-center justify-between px-3 py-2 rounded-lg text-xs font-semibold transition <?php echo empty($cat_slug) ? 'bg-blue-50 text-blue-700 font-bold' : 'text-gray-600 hover:bg-gray-50 hover:text-blue-600'; ?>">
                        <span>All Posts</span>
                        <span class="text-[11px] px-2 py-0.5 rounded-full bg-gray-100 text-gray-600"><?php echo $total_all_posts; ?></span>
                    </a>
                    <?php while ($cat = mysqli_fetch_assoc($categories)): ?>
                    <a href="/blog/category/<?php echo urlencode($cat['slug']); ?>" class="flex items-center justify-between px-3 py-2 rounded-lg text-xs font-semibold transition <?php echo $cat_slug === $cat['slug'] ? 'bg-blue-50 text-blue-700 font-bold' : 'text-gray-600 hover:bg-gray-50 hover:text-blue-600'; ?>">
                        <span><?php echo htmlspecialchars($cat['name']); ?></span>
                        <span class="text-[11px] px-2 py-0.5 rounded-full bg-gray-100 text-gray-600"><?php echo $cat['post_count']; ?></span>
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
