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

$user_curr = getUserCurrency();
$curr_symbol = $user_curr['symbol'];

$product_schema = [
    '@context' => 'https://schema.org',
    '@type' => 'Product',
    'name' => $category['name'] . ' Hosting',
    'description' => $cat_desc,
    'offers' => [
        '@type' => 'AggregateOffer',
        'priceCurrency' => $user_curr['code'] ?? 'BDT',
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
<div class="page-hero">
<div class="content mx-auto">
<div class="sm:w-3/4 text-left">
<h1><?php echo htmlspecialchars($category['name']); ?></h1>
<p><?php echo htmlspecialchars($category['description']); ?></p>
</div>
</div>
</div>

<section class="section_gap">
<div class="content">
<div class="mb-12 flex flex-col md:flex-row md:items-end justify-between items-center text-center md:text-left gap-6">
<div>
<h5 class="text-blue-600 font-semibold tracking-wider uppercase text-sm">PRICING PLANS</h5>
<h2 class="text-3xl font-bold text-gray-900 mt-1">Choose the best plan</h2>
<p class="text-gray-600 mt-1">Honest and affordable pricing model to help you get started easily.</p>
</div>

<!-- Pricing Switcher Box matching reference & Currency Selector -->
<?php 
$active_currencies = getActiveCurrencies();
$multi_curr_enabled = isMultiCurrencyEnabled() && count($active_currencies) > 1;
?>
<div class="flex flex-col sm:flex-row items-center justify-center md:justify-end gap-3 w-full md:w-auto">
    <div class="pricing-toggle-box">
        <span class="toggle-label" onclick="document.getElementById('pricingSwitch').checked=false; toggleBillingSwitch(false);">Monthly</span>
        <label class="custom-switch">
            <input type="checkbox" id="pricingSwitch" onchange="toggleBillingSwitch(this.checked)">
            <span class="slider"></span>
        </label>
        <span class="toggle-label" onclick="document.getElementById('pricingSwitch').checked=true; toggleBillingSwitch(true);">Annually</span>
    </div>

    <?php if ($multi_curr_enabled): ?>
    <div class="inline-flex items-center gap-2 bg-white border border-gray-200 rounded-full px-3.5 py-2 shadow-xs text-xs font-bold text-gray-700">
        <span class="text-gray-400 uppercase text-[10px] tracking-wider flex items-center gap-1"><i class="fa-solid fa-coins text-blue-600"></i> Currency:</span>
        <select onchange="window.location.href=this.value" class="bg-transparent font-extrabold text-blue-600 focus:outline-none cursor-pointer pr-1">
            <?php foreach ($active_currencies as $c_code => $c_item): ?>
            <option value="<?php echo htmlspecialchars(getCurrencySwitchUrl($c_code)); ?>" <?php echo $user_curr['code'] === $c_code ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($c_item['symbol'] . ' ' . $c_code); ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>
</div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-12 lg:gap-8">
<?php $plans = getPlans($category['slug']); ?>
<?php if (mysqli_num_rows($plans) > 0): ?>
<?php while ($plan = mysqli_fetch_assoc($plans)): 
    $features = json_decode($plan['features'], true);
    $monthly_raw = (float)($plan['monthly_price'] ?? 0);
    $yearly_raw = (float)($plan['yearly_price'] ?? 0);
    $has_monthly = ($monthly_raw > 0);
    $has_yearly = ($yearly_raw > 0);

    $conv_monthly = convertPriceAmount($monthly_raw, $user_curr);
    $conv_yearly = convertPriceAmount($yearly_raw, $user_curr);

    if ($has_monthly && $has_yearly) {
        $billing_mode = 'both';
        $display_price = $conv_monthly;
        $display_period = ' /Month';
    } elseif (!$has_monthly && $has_yearly) {
        // Direct yearly select when monthly is not configured
        $billing_mode = 'yearly-only';
        $display_price = $conv_yearly;
        $display_period = ' /Year';
    } elseif ($has_monthly && !$has_yearly) {
        $billing_mode = 'monthly-only';
        $display_price = $conv_monthly;
        $display_period = ' /Month';
    } else {
        $billing_mode = 'both';
        $display_price = '0';
        $display_period = ' /Month';
    }

    $is_popular = !empty($plan['is_popular']) && (int)$plan['is_popular'] === 1;
    $badge_raw = trim($plan['badge'] ?? '');
    if ($is_popular && empty($badge_raw)) {
        $badge_text = 'Popular';
    } elseif (!empty($badge_raw)) {
        $badge_text = $badge_raw;
    } else {
        $badge_text = $plan['name'];
    }
    $badge_bg_class = $is_popular ? 'bg-blue-600 text-white shadow-xs' : 'bg-gray-500 bg-opacity-50 text-white';
?>
<div class="rounded-lg shadow-xl flex flex-col overflow-hidden bg-white border plan-pricing-card" data-billing-mode="<?php echo $billing_mode; ?>">
<div class="p-5 text-center rounded-t-lg border-b border-white bg-opacity-95 overflow-hidden bg-blue-100">
<span class="inline-block text-sm uppercase tracking-wider font-semibold px-3 py-1 <?php echo $badge_bg_class; ?> rounded-full mb-4"><?php echo htmlspecialchars($badge_text); ?></span>
<div class="flex gap-1 mb-1 justify-center items-center">
<h3 class="text-xl xl:text-2xl font-extrabold"><?php echo htmlspecialchars($curr_symbol); ?> <span data-monthly="<?php echo $conv_monthly; ?>" data-yearly="<?php echo $conv_yearly; ?>" class="priceValue"><?php echo $display_price; ?></span></h3>
<span class="priceFor text-sm font-semibold mt-1"><?php echo $display_period; ?></span>
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
<a href="<?php echo htmlspecialchars($plan['order_url'] ?: '#'); ?>" data-ripple-light="true" class="btn btn-blue">Order Now</a>
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
<script src="/js/scroll.js"></script>
<script src="/js/ns.js"></script>
<script src="/js/ns-jquery.js"></script>
</body>
</html>
