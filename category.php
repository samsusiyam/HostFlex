<?php require_once 'config/database.php'; require_once 'includes/functions.php'; checkMaintenance();

$slug = isset($_GET['slug']) ? sanitize($_GET['slug']) : '';

if (strpos($_SERVER['REQUEST_URI'] ?? '', 'category.php') !== false && !empty($slug)) {
    header('HTTP/1.1 301 Moved Permanently');
    header('Location: /category/' . urlencode($slug));
    exit;
}

$category = getCategoryBySlug($slug);

if (!$category) {
    include '404.php';
    exit;
}

$site_name = getSetting('site_name') ?: 'Host Nibo';
$cat_title = htmlspecialchars($category['name']) . ' - ' . $site_name;
$cat_desc = $category['description'] ?: ('High performance ' . $category['name'] . ' hosting plans with 99.9% uptime guarantee.');

$product_schema = [
    '@context' => 'https://schema.org',
    '@type' => 'Product',
    'name' => $category['name'] . ' Hosting',
    'description' => $cat_desc,
    'offers' => [
        '@type' => 'AggregateOffer',
        'priceCurrency' => 'BDT',
        'offerCount' => 4,
        'availability' => 'https://schema.org/InStock'
    ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include "cdnjs.php"; ?>
<title><?php echo $cat_title; ?></title>
<?php echo renderSeoTags([
    'title' => $cat_title,
    'description' => $cat_desc,
    'schema' => $product_schema
]); ?>
</head>
<body>
<?php include "header.php"; ?>
<?php include "contact-btn.php"; ?>
<div class="bg-gradient-to-r from-blue-700 to-indigo-800 text-white">
<div class="space-y-16 content mx-auto py-16 lg:pt-20 lg:pb-20">
<div class="flex flex-col lg:flex-row items-center space-y-12 lg:space-y-0">
<div class="sm:w-2/3 text-left">
<h1 class="text-3xl md:text-4xl font-extrabold mb-4 text-white"><?php echo htmlspecialchars($category['name']); ?></h1>
<p class="text-lg md:text-xl font-medium text-blue-100"><?php echo htmlspecialchars($category['description']); ?></p>
</div>
</div>
</div>
</div>

<section class="section_gap">
<div class="content">
<div class="mb-12 flex flex-col md:flex-row md:items-end md:justify-between gap-6">
<div>
<h5 class="text-blue-600 font-semibold tracking-wider uppercase text-sm">PRICING PLANS</h5>
<h2 class="text-3xl font-bold text-gray-900 mt-1">Choose the best plan</h2>
<p class="text-gray-600 mt-1">Honest and affordable pricing model to help you get started easily.</p>
</div>

<!-- Modern Pricing Switcher -->
<div class="billing-switcher-container">
    <button type="button" class="billing-tab active" data-cycle="monthly" onclick="switchBilling('monthly')">
        Monthly
    </button>
    <button type="button" class="billing-tab" data-cycle="yearly" onclick="switchBilling('yearly')">
        Yearly <span class="billing-save-badge">Save 20%</span>
    </button>
</div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-12 lg:gap-8">
<?php $plans = getPlans($category['slug']); ?>
<?php if (mysqli_num_rows($plans) > 0): ?>
<?php while ($plan = mysqli_fetch_assoc($plans)): ?>
<?php $features = json_decode($plan['features'], true); ?>
<div class="rounded-lg shadow-xl flex flex-col overflow-hidden bg-white border">
<div class="p-5 text-center rounded-t-lg border-b border-white bg-opacity-95 overflow-hidden bg-blue-100">
<span class="inline-block text-sm uppercase tracking-wider font-semibold px-3 py-1 bg-gray-500 bg-opacity-50 text-white rounded-full mb-4"><?php echo htmlspecialchars($plan['badge'] ?: $plan['name']); ?></span>
<div class="flex gap-1 mb-1 justify-center items-center">
<h3 class="text-xl xl:text-2xl font-extrabold"><?php echo getSetting('currency_symbol'); ?> <span data-monthly="<?php echo $plan['monthly_price']; ?>" data-yearly="<?php echo $plan['yearly_price']; ?>" class="priceValue"><?php echo $plan['monthly_price']; ?></span></h3>
<span class="priceFor text-sm font-semibold mt-1"> /Month</span>
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

