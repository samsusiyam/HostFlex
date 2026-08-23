<?php require_once 'config/database.php'; require_once 'includes/functions.php'; checkMaintenance();

$slug = $_GET['slug'] ?? '';
if (!$slug) { header('Location: /blog'); exit; }

if (strpos($_SERVER['REQUEST_URI'] ?? '', 'blog.php') !== false && !empty($slug)) {
    header('HTTP/1.1 301 Moved Permanently');
    header('Location: /blog/' . urlencode($slug));
    exit;
}

$slug_esc = mysqli_real_escape_string($conn, $slug);
$post = mysqli_fetch_assoc(mysqli_query($conn, "SELECT p.*, c.name as category_name, c.slug as category_slug FROM blog_posts p LEFT JOIN blog_categories c ON p.category_id = c.id WHERE p.slug = '$slug_esc' AND p.status = 1"));

if (!$post) { include '404.php'; exit; }

$page_title = $post['title'];
$meta_desc = $post['meta_description'] ?: ($post['excerpt'] ?: substr(strip_tags($post['content']), 0, 160));
$meta_kw = $post['meta_keywords'] ?? '';
$site_name = getSetting('site_name') ?: 'Host Nibo';

$blog_schema = [
    '@context' => 'https://schema.org',
    '@type' => 'BlogPosting',
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
<body>
<?php include "header.php"; ?>

<section class="section_gap bg-white">
<div class="content max-w-4xl mx-auto">
    <div class="mb-6">
        <?php if ($post['category_name']): ?>
        <a href="blogs.php?category=<?php echo urlencode($post['category_slug']); ?>" class="text-xs text-blue-600 font-semibold uppercase tracking-wide"><?php echo htmlspecialchars($post['category_name']); ?></a>
        <?php endif; ?>
        <h1 class="text-3xl md:text-4xl font-bold mt-2 mb-3"><?php echo htmlspecialchars($post['title']); ?></h1>
        <div class="text-sm text-gray-500 flex items-center gap-3">
            <?php if ($post['author']): ?><span>By <?php echo htmlspecialchars($post['author']); ?></span><?php endif; ?>
            <span><?php echo date('F d, Y', strtotime($post['created_at'])); ?></span>
        </div>
    </div>
    <?php if ($post['image']): ?>
    <img src="<?php echo htmlspecialchars(getImageUrl($post['image'])); ?>" class="w-full max-h-[400px] object-cover rounded-xl mb-8" alt="<?php echo htmlspecialchars($post['title']); ?>">
    <?php endif; ?>
    <div class="prose max-w-none text-gray-800 leading-relaxed">
        <?php echo $post['content']; ?>
    </div>
    <div class="mt-10 pt-6 border-t">
        <a href="/blog" class="text-blue-600 hover:underline"><i class="fa fa-arrow-left mr-1"></i> Back to Blog</a>
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

