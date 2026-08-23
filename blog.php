<?php 
require_once 'config/database.php'; 
require_once 'includes/functions.php'; 
checkMaintenance();
ensureBlogSchema();

$slug = $_GET['slug'] ?? '';
if (!$slug) { header('Location: /blog'); exit; }

if (strpos($_SERVER['REQUEST_URI'] ?? '', 'blog.php') !== false && !empty($slug)) {
    header('HTTP/1.1 301 Moved Permanently');
    header('Location: /blog/' . urlencode($slug));
    exit;
}

$slug_esc = mysqli_real_escape_string($conn, $slug);
if (is_numeric($slug)) {
    $post = mysqli_fetch_assoc(mysqli_query($conn, "SELECT p.*, c.name as category_name, c.slug as category_slug FROM blog_posts p LEFT JOIN blog_categories c ON p.category_id = c.id WHERE (p.id = " . (int)$slug . " OR p.slug = '$slug_esc') AND p.status = 1"));
} else {
    $post = mysqli_fetch_assoc(mysqli_query($conn, "SELECT p.*, c.name as category_name, c.slug as category_slug FROM blog_posts p LEFT JOIN blog_categories c ON p.category_id = c.id WHERE p.slug = '$slug_esc' AND p.status = 1"));
}

if (!$post) { include '404.php'; exit; }

$page_title = $post['title'];
$meta_desc = $post['meta_description'] ?: ($post['excerpt'] ?: substr(strip_tags($post['content']), 0, 160));
$meta_kw = $post['meta_keywords'] ?? '';
$site_name = getSetting('site_name') ?: 'Host Nibo';
$canonical_path = getBlogPostUrl($post);
$current_url = rtrim(getSiteUrl(), '/') . $canonical_path;

// Reading Time Calculation
$reading_time = getReadingTime($post['content']);
$word_count = str_word_count(strip_tags((string)$post['content']));

// Table of Contents Generation
$post_content = $post['content'];
$toc = generateBlogTOC($post_content);

// Related posts query
$cat_id = (int)$post['category_id'];
$curr_id = (int)$post['id'];
$related_posts_res = mysqli_query($conn, "SELECT p.*, c.name as category_name, c.slug as category_slug FROM blog_posts p LEFT JOIN blog_categories c ON p.category_id = c.id WHERE p.status = 1 AND p.id != $curr_id " . ($cat_id > 0 ? "AND p.category_id = $cat_id " : "") . "ORDER BY p.created_at DESC LIMIT 3");
if (mysqli_num_rows($related_posts_res) === 0) {
    $related_posts_res = mysqli_query($conn, "SELECT p.*, c.name as category_name, c.slug as category_slug FROM blog_posts p LEFT JOIN blog_categories c ON p.category_id = c.id WHERE p.status = 1 AND p.id != $curr_id ORDER BY p.created_at DESC LIMIT 3");
}
$related_posts = [];
while ($rp = mysqli_fetch_assoc($related_posts_res)) {
    $related_posts[] = $rp;
}

// Prev and Next posts
$prev_post = mysqli_fetch_assoc(mysqli_query($conn, "SELECT p.id, p.title, p.slug, c.slug as category_slug FROM blog_posts p LEFT JOIN blog_categories c ON p.category_id = c.id WHERE p.status = 1 AND p.id < $curr_id ORDER BY p.id DESC LIMIT 1"));
$next_post = mysqli_fetch_assoc(mysqli_query($conn, "SELECT p.id, p.title, p.slug, c.slug as category_slug FROM blog_posts p LEFT JOIN blog_categories c ON p.category_id = c.id WHERE p.status = 1 AND p.id > $curr_id ORDER BY p.id ASC LIMIT 1"));

$blog_schema = [
    '@context' => 'https://schema.org',
    '@type' => 'BlogPosting',
    'mainEntityOfPage' => [
        '@type' => 'WebPage',
        '@id' => $current_url
    ],
    'headline' => $post['title'],
    'description' => $meta_desc,
    'datePublished' => date('c', strtotime($post['created_at'])),
    'author' => [
        '@type' => 'Person',
        'name' => $post['author'] ?: $site_name
    ],
    'publisher' => [
        '@type' => 'Organization',
        'name' => $site_name,
        'logo' => [
            '@type' => 'ImageObject',
            'url' => getSiteUrl() . (getSetting('header_logo') ?: 'images/bg.png')
        ]
    ]
];
if (!empty($post['image'])) {
    $blog_schema['image'] = getSiteUrl() . ltrim($post['image'], '/');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include "cdnjs.php"; ?>
<title><?php echo htmlspecialchars($post['title']); ?> - <?php echo $site_name; ?></title>
<?php echo renderSeoTags([
    'title' => htmlspecialchars($post['title']) . ' - ' . $site_name,
    'description' => $meta_desc,
    'keywords' => $meta_kw,
    'type' => 'article',
    'image' => $post['image'] ?? '',
    'schema' => $blog_schema
]); ?>
</head>
<body class="bg-gray-50 text-gray-800">
<?php include "header.php"; ?>
<?php include "contact-btn.php"; ?>

<main class="py-8 md:py-14">
<div class="max-w-4xl mx-auto px-4 sm:px-6">
    
    <!-- Breadcrumb Navigation -->
    <nav class="flex items-center gap-2 text-xs text-gray-500 mb-6 font-medium flex-wrap">
        <a href="/" class="hover:text-blue-600 transition flex items-center gap-1"><i class="fa-solid fa-house"></i> Home</a>
        <span>/</span>
        <a href="/blog" class="hover:text-blue-600 transition">Blog</a>
        <?php if ($post['category_name']): ?>
        <span>/</span>
        <a href="/blog/category/<?php echo urlencode($post['category_slug']); ?>" class="hover:text-blue-600 transition"><?php echo htmlspecialchars($post['category_name']); ?></a>
        <?php endif; ?>
        <span>/</span>
        <span class="text-gray-700 truncate max-w-[200px] sm:max-w-xs"><?php echo htmlspecialchars($post['title']); ?></span>
    </nav>

    <!-- Main White Article Box -->
    <article class="bg-white rounded-2xl border border-gray-200/80 shadow-sm p-6 sm:p-10 md:p-12 mb-8">
        
        <!-- Post Header -->
        <header class="mb-8 pb-6 border-b border-gray-100">
            <?php if ($post['category_name']): ?>
            <a href="/blog/category/<?php echo urlencode($post['category_slug']); ?>" class="inline-flex items-center gap-1.5 bg-blue-50 text-blue-700 text-xs font-bold uppercase tracking-wider px-3 py-1 rounded-full border border-blue-100 hover:bg-blue-600 hover:text-white transition mb-3">
                <i class="fa-solid fa-tag text-[10px]"></i> <?php echo htmlspecialchars($post['category_name']); ?>
            </a>
            <?php endif; ?>

            <h1 class="text-2xl sm:text-3xl md:text-4xl font-extrabold text-gray-900 leading-tight mb-4 tracking-tight">
                <?php echo htmlspecialchars($post['title']); ?>
            </h1>

            <!-- Author & Metadata Bar -->
            <div class="flex flex-wrap items-center gap-4 text-xs md:text-sm text-gray-500 pt-2">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full bg-blue-600 text-white font-bold flex items-center justify-center text-xs shadow-xs">
                        <?php echo strtoupper(substr($post['author'] ?: 'A', 0, 1)); ?>
                    </div>
                    <span class="font-semibold text-gray-900"><?php echo htmlspecialchars($post['author'] ?: 'Admin'); ?></span>
                </div>
                <span class="text-gray-300">•</span>
                <span class="flex items-center gap-1.5">
                    <i class="fa-regular fa-calendar text-gray-400"></i>
                    <?php echo date('F d, Y', strtotime($post['created_at'])); ?>
                </span>
                <span class="text-gray-300">•</span>
                <span class="flex items-center gap-1.5 text-blue-600 font-semibold">
                    <i class="fa-regular fa-clock"></i>
                    <?php echo $reading_time; ?> min read
                </span>
            </div>
        </header>

        <!-- Unboxed Hero Featured Image (Shows only if enabled) -->
        <?php if (!empty($post['image']) && ($post['show_featured_image'] ?? 1) == 1): ?>
        <div class="blog-featured-image-wrapper">
            <img src="<?php echo htmlspecialchars(getImageUrl($post['image'])); ?>" class="blog-featured-img" alt="<?php echo htmlspecialchars($post['title']); ?>" loading="eager">
        </div>
        <?php endif; ?>

        <!-- Table of Contents (If article has 2+ valid headings) -->
        <?php if (count($toc) >= 2): ?>
        <div class="blog-toc-box">
            <div class="flex items-center justify-between cursor-pointer" onclick="$('#tocContent').slideToggle(200)">
                <h3 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                    <i class="fa-solid fa-list-ul text-blue-600"></i> Table of Contents
                </h3>
                <span class="text-xs text-blue-600 font-semibold">[Show / Hide]</span>
            </div>
            <div id="tocContent">
                <ul class="blog-toc-list">
                    <?php foreach ($toc as $item): ?>
                    <li class="blog-toc-item level-<?php echo $item['level']; ?>">
                        <a href="#<?php echo $item['id']; ?>" class="blog-toc-link">
                            <i class="fa-solid fa-angle-right text-[10px] text-gray-400"></i>
                            <span><?php echo htmlspecialchars($item['title']); ?></span>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>

        <!-- Main Article Content -->
        <div class="blog-article-content">
            <?php echo $post_content; ?>
        </div>

        <!-- Social Share Bar -->
        <div class="p-5 bg-gray-50/80 rounded-xl border border-gray-200 mt-10 mb-8 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-share-nodes text-blue-600 text-base"></i>
                <span class="font-bold text-gray-900 text-sm">Share this article:</span>
            </div>
            <div class="flex items-center gap-2.5 flex-wrap">
                <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($current_url); ?>" target="_blank" rel="noopener" class="share-btn share-facebook" title="Share on Facebook">
                    <i class="fa-brands fa-facebook-f"></i>
                </a>
                <a href="https://twitter.com/intent/tweet?text=<?php echo urlencode($post['title']); ?>&url=<?php echo urlencode($current_url); ?>" target="_blank" rel="noopener" class="share-btn share-twitter" title="Share on X / Twitter">
                    <i class="fa-brands fa-x-twitter"></i>
                </a>
                <a href="https://api.whatsapp.com/send?text=<?php echo urlencode($post['title'] . ' ' . $current_url); ?>" target="_blank" rel="noopener" class="share-btn share-whatsapp" title="Share on WhatsApp">
                    <i class="fa-brands fa-whatsapp"></i>
                </a>
                <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo urlencode($current_url); ?>" target="_blank" rel="noopener" class="share-btn share-linkedin" title="Share on LinkedIn">
                    <i class="fa-brands fa-linkedin-in"></i>
                </a>
                <button type="button" onclick="navigator.clipboard.writeText(window.location.href); alert('Article link copied to clipboard!');" class="share-btn share-copy" title="Copy Link">
                    <i class="fa-solid fa-link"></i>
                </button>
            </div>
        </div>

        <!-- Author Profile Box -->
        <div class="p-6 bg-gray-50/60 rounded-xl border border-gray-200 flex flex-col sm:flex-row items-center sm:items-start gap-4 text-center sm:text-left">
            <div class="w-14 h-14 rounded-full bg-blue-600 text-white font-black text-xl flex items-center justify-center shrink-0 shadow-sm">
                <?php echo strtoupper(substr($post['author'] ?: 'A', 0, 1)); ?>
            </div>
            <div class="flex-1">
                <span class="text-[11px] font-bold text-blue-600 uppercase tracking-wider block">Written By</span>
                <h4 class="text-base font-bold text-gray-900 mt-0.5"><?php echo htmlspecialchars($post['author'] ?: 'Admin'); ?></h4>
                <p class="text-xs text-gray-600 mt-1 leading-relaxed">
                    Hosting expert and contributor at <?php echo htmlspecialchars($site_name); ?>. Specializing in high-performance cloud hosting, server security, and web optimization tutorials.
                </p>
            </div>
        </div>

    </article>

    <!-- Previous / Next Navigation -->
    <?php if ($prev_post || $next_post): ?>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-10">
        <?php if ($prev_post): ?>
        <a href="<?php echo getBlogPostUrl($prev_post); ?>" class="p-4 bg-white hover:bg-blue-50/50 rounded-xl border border-gray-200 shadow-xs transition group flex flex-col justify-center">
            <span class="text-xs font-semibold text-gray-400 group-hover:text-blue-600 flex items-center gap-1 mb-1">
                <i class="fa-solid fa-arrow-left text-[10px]"></i> Previous Article
            </span>
            <span class="font-bold text-gray-900 text-sm group-hover:text-blue-600 transition line-clamp-1">
                <?php echo htmlspecialchars($prev_post['title']); ?>
            </span>
        </a>
        <?php else: ?><div></div><?php endif; ?>

        <?php if ($next_post): ?>
        <a href="<?php echo getBlogPostUrl($next_post); ?>" class="p-4 bg-white hover:bg-blue-50/50 rounded-xl border border-gray-200 shadow-xs transition group flex flex-col justify-center text-right sm:text-right">
            <span class="text-xs font-semibold text-gray-400 group-hover:text-blue-600 flex items-center justify-end gap-1 mb-1">
                Next Article <i class="fa-solid fa-arrow-right text-[10px]"></i>
            </span>
            <span class="font-bold text-gray-900 text-sm group-hover:text-blue-600 transition line-clamp-1">
                <?php echo htmlspecialchars($next_post['title']); ?>
            </span>
        </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Related Articles Section -->
    <?php if (!empty($related_posts)): ?>
    <div class="pt-4 mb-10">
        <div class="flex items-center justify-between mb-5">
            <div>
                <h3 class="text-xl font-bold text-gray-900">Related Articles</h3>
                <p class="text-xs text-gray-500 mt-0.5">Explore more articles and tutorials you might like</p>
            </div>
            <a href="/blog" class="text-xs font-bold text-blue-600 hover:underline flex items-center gap-1">View All <i class="fa-solid fa-arrow-right text-[10px]"></i></a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            <?php foreach ($related_posts as $rp): ?>
            <a href="<?php echo getBlogPostUrl($rp); ?>" class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-xs hover:shadow-md hover:-translate-y-1 transition duration-200 group flex flex-col justify-between">
                <div>
                    <?php if (!empty($rp['image'])): ?>
                    <div class="h-36 overflow-hidden bg-gray-100">
                        <img src="<?php echo htmlspecialchars(getImageUrl($rp['image'])); ?>" alt="" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                    </div>
                    <?php else: ?>
                    <div class="h-36 blog-thumb-placeholder">
                        <i class="fa-solid fa-newspaper opacity-75"></i>
                    </div>
                    <?php endif; ?>
                    <div class="p-4">
                        <?php if (!empty($rp['category_name'])): ?>
                        <span class="text-[10px] font-bold text-blue-600 uppercase tracking-wider mb-1 block">
                            <?php echo htmlspecialchars($rp['category_name']); ?>
                        </span>
                        <?php endif; ?>
                        <h4 class="font-bold text-gray-900 text-sm group-hover:text-blue-600 transition line-clamp-2 leading-snug">
                            <?php echo htmlspecialchars($rp['title']); ?>
                        </h4>
                    </div>
                </div>
                <div class="px-4 pb-4 pt-1 flex items-center justify-between text-[11px] text-gray-400 font-medium border-t border-gray-100 mt-2">
                    <span><?php echo date('M d, Y', strtotime($rp['created_at'])); ?></span>
                    <span><i class="fa-regular fa-clock mr-0.5 text-[10px]"></i> <?php echo getReadingTime($rp['content']); ?> min</span>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

</div>
</main>

<?php include "footer.php"; ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://unpkg.com/alpinejs@3.14.9/dist/cdn.min.js"></script>
<script src="/js/scroll.js"></script>
<script src="/js/ns.js"></script>
</body>
</html>
