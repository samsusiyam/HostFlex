<?php require_once 'config/database.php'; require_once 'includes/functions.php'; checkMaintenance();

$slug = isset($_GET['slug']) ? sanitize($_GET['slug']) : '';

$blog_cat = null;
$r = @$conn->query("SELECT * FROM blog_categories WHERE slug = '" . mysqli_real_escape_string($conn, $slug) . "' AND status = 1 LIMIT 1");
if ($r && mysqli_num_rows($r) > 0) {
    $blog_cat = mysqli_fetch_assoc($r);
}

$hosting_cat = getCategoryBySlug($slug);

if (!$blog_cat && !$hosting_cat) {
    header('HTTP/1.0 404 Not Found');
    include __DIR__ . '/404.php';
    exit;
}

if ($blog_cat) {
    $page_title = $blog_cat['name'] . ' - Blog';
    $post_count_q = @$conn->query("SELECT COUNT(*) as c FROM blog_posts WHERE status = 1 AND (category_id = {$blog_cat['id']} OR EXISTS (SELECT 1 FROM blog_post_categories WHERE post_id = blog_posts.id AND category_id = {$blog_cat['id']}))");
    $post_count = ($post_count_q && $row = mysqli_fetch_assoc($post_count_q)) ? $row['c'] : 0;

    $posts_q = @$conn->query("SELECT DISTINCT p.* FROM blog_posts p LEFT JOIN blog_post_categories bpc ON p.id = bpc.post_id WHERE p.status = 1 AND (p.category_id = {$blog_cat['id']} OR bpc.category_id = {$blog_cat['id']}) ORDER BY p.created_at DESC");
    $posts = [];
    if ($posts_q) { while ($row = mysqli_fetch_assoc($posts_q)) { $posts[] = $row; } }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include "cdnjs.php"; ?>
<title><?php echo htmlspecialchars($blog_cat ? $blog_cat['name'] : $hosting_cat['name']); ?> - <?php echo escSetting('site_name'); ?></title>
</head>
<body>
<?php include "header.php"; ?>
<?php include "contact-btn.php"; ?>

<?php if ($blog_cat): ?>
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
    <img src="../<?php echo htmlspecialchars($post['image']); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>" class="w-full h-48 object-cover group-hover:scale-105 transition duration-300">
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

<?php else: ?>
<div class="bg-blue-600 bg-opacity-70">
<div class="space-y-16 content mx-auto py-16 lg:pt-20 lg:pb-20">
<?php $breadcrumbs = [['label' => $hosting_cat['name']]]; include __DIR__ . '/breadcrumb.php'; ?>
<div class="flex flex-col lg:flex-row items-center space-y-12 lg:space-y-0">
<div class="sm:w-2/3 text-left">
<h2 class="text-3xl md:text-4xl font-extrabold mb-4 text-white"><?php echo htmlspecialchars($hosting_cat['name']); ?></h2>
<p class="text-lg md:text-xl font-medium text-gray-200"><?php echo htmlspecialchars($hosting_cat['description']); ?></p>
</div>
</div>
</div>
</div>

<section class="section_gap">
<div class="content">
<div class="mb-12 flex flex-col gap-2">
<h5 class="text-blue-600">PRICING PLANS</h5>
<h2 class="text-black">Choose the best plan</h2>
<p>Honest and affordable pricing model to help you get started easily.</p>
</div>
<?php $sym = htmlspecialchars(getSetting('currency_symbol') ?: 'TK.', ENT_QUOTES, 'UTF-8'); ?>
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-12 lg:gap-8">
<?php $plans = getPlans($hosting_cat['slug']); ?>
<?php if (mysqli_num_rows($plans) > 0): ?>
<?php while ($plan = mysqli_fetch_assoc($plans)):
    $features = json_decode($plan['features'], true);
    $has_monthly = $plan['monthly_price'] > 0;
    $has_yearly = $plan['yearly_price'] > 0;
    $both = $has_monthly && $has_yearly;
    $default_price = $has_monthly ? $plan['monthly_price'] : $plan['yearly_price'];
    $default_label = $has_monthly ? '/month' : '/year';
?>
<div class="rounded-lg shadow-xl flex flex-col overflow-hidden" style="background-image: url('images/hosting-bg.html'); background-size: contain; background-repeat: no-repeat; background-position: top;">
<div class="p-5 text-center rounded-t-lg border-b border-white bg-opacity-95 overflow-hidden bg-blue-100">
<span class="inline-block text-sm uppercase tracking-wider font-semibold px-3 py-1 bg-gray-500 bg-opacity-50 text-white rounded-full mb-4"><?php echo htmlspecialchars($plan['badge'] ?: $plan['name']); ?></span>
<div class="flex flex-col gap-1 mb-1 justify-center items-center">
<h3 class="text-xl xl:text-2xl font-extrabold"><?php echo $sym; ?> <span data-monthly="<?php echo $plan['monthly_price']; ?>" data-yearly="<?php echo $plan['yearly_price']; ?>" class="priceValue"><?php echo $default_price; ?></span></h3>
<span class="priceFor text-sm font-semibold mt-1"><?php echo $default_label; ?></span>
<?php if ($both): ?>
<style>
.billing-toggle{position:relative;display:inline-flex;align-items:center;cursor:pointer}
.billing-toggle input{position:absolute;opacity:0;width:0;height:0}
.billing-toggle .slider{position:relative;width:48px;height:24px;background:#d1d5db;border-radius:9999px;transition:background .3s;flex-shrink:0}
.billing-toggle .slider::before{content:"";position:absolute;left:2px;top:2px;width:20px;height:20px;background:#fff;border-radius:50%;transition:transform .3s;box-shadow:0 2px 4px rgba(0,0,0,.2)}
.billing-toggle input:checked + .slider{background:#2563eb}
.billing-toggle input:checked + .slider::before{transform:translateX(24px)}
</style>
<div class="mt-3 flex items-center justify-center gap-2">
    <span class="billingLabel text-sm font-bold text-blue-700 cursor-pointer leading-none" data-period="monthly" onclick="toggleBilling(this, 'monthly')">Monthly</span>
    <label class="billing-toggle leading-none">
        <input type="checkbox" class="billingCheck" onchange="toggleBilling(this)">
        <span class="slider"></span>
    </label>
    <span class="billingLabel text-sm font-bold text-gray-400 cursor-pointer leading-none" data-period="yearly" onclick="toggleBilling(this, 'yearly')">Yearly</span>
</div>
<?php endif; ?>
</div>
<p class="text-gray-700 text-sm font-medium"><?php echo htmlspecialchars($plan['subtitle']); ?></p>
</div>
<div class="p-8 lg:p-8 space-y-5 lg:space-y-6 text-gray-700 flex-grow bg-white">
<ul class="space-y-3 text-sm lg:text-base">
<?php if ($features): foreach ($features as $feature): ?>
<li class="flex items-center space-x-2"><i class="fa fa-check-square text-green-600"></i><span><?php echo htmlspecialchars($feature); ?></span></li>
<?php endforeach; endif; ?>
</ul>
</div>
<div class="px-4 pb-4 bg-white">
<a href="<?php echo $plan['order_url'] ?: '#'; ?>" data-ripple-light="true" class="btn btn-blue">Order Now</a>
</div>
</div>
<?php endwhile; ?>
<?php else: ?>
<div class="col-span-full text-center py-12"><p class="text-gray-400 text-lg">No plans available in this category yet.</p></div>
<?php endif; ?>
</div>
<script>
function toggleBilling(el, period) {
    var card = el.closest('.overflow-hidden');
    var priceSpan = card.querySelector('.priceValue');
    var labelSpan = card.querySelector('.priceFor');
    var labels = card.querySelectorAll('.billingLabel');
    var input = card.querySelector('.billingCheck');
    if (!period) { period = input.checked ? 'yearly' : 'monthly'; }
    labels.forEach(function(l) {
        l.classList.remove('text-blue-700', 'text-gray-400');
        if (l.getAttribute('data-period') === period) { l.classList.add('text-blue-700'); }
        else { l.classList.add('text-gray-400'); }
    });
    if (period === 'yearly') { input.checked = true; } else { input.checked = false; }
    priceSpan.textContent = priceSpan.getAttribute('data-' + period);
    labelSpan.textContent = period === 'monthly' ? '/month' : '/year';
}
</script>
</div>
</section>
<?php endif; ?>

<?php include "footer.php"; ?>
</body>
</html>
