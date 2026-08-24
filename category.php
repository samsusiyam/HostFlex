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
        'priceCurrency' => getUserCurrency()['code'] ?? 'BDT',
        'offerCount' => 4,
        'availability' => 'https://schema.org/InStock'
    ]
];

$user_curr = getUserCurrency();
$curr_symbol = $user_curr['symbol'];
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
<h5 class="text-blue-600 font-bold tracking-wider uppercase text-xs">PRICING PLANS</h5>
<h2 class="text-3xl font-extrabold text-gray-900 mt-1">Choose the best plan</h2>
<p class="text-gray-600 mt-1 text-sm md:text-base">Honest and affordable pricing model to help you get started easily.</p>
</div>

<!-- Pricing Switcher Box -->
<div class="flex justify-center w-full md:w-auto">
<div class="pricing-toggle-box">
    <span class="toggle-label cursor-pointer" onclick="setPricingBilling(false)">Monthly</span>
    <label class="custom-switch">
        <input type="checkbox" id="pricingSwitch" onchange="toggleBillingSwitch(this.checked)">
        <span class="slider"></span>
    </label>
    <span class="toggle-label cursor-pointer" onclick="setPricingBilling(true)">Annually <span class="bg-emerald-100 text-emerald-700 text-[10px] font-bold px-2 py-0.5 rounded-full ml-1">SAVE</span></span>
</div>
</div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8 lg:gap-6 pt-4">
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

    // Determine initial display mode & data billing mode
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
    $badge_text = trim($plan['badge'] ?? '');
    $has_badge = ($is_popular || !empty($badge_text));
?>
<div class="plan-pricing-card rounded-2xl shadow-xl flex flex-col justify-between overflow-hidden bg-white transition-all duration-300 relative <?php echo $is_popular ? 'border-2 border-blue-600 ring-4 ring-blue-500/10 md:-translate-y-2 shadow-2xl z-10' : 'border border-gray-200 hover:border-blue-300 hover:shadow-2xl'; ?>" data-billing-mode="<?php echo $billing_mode; ?>">
    
    <?php if ($has_badge): ?>
    <div class="absolute -top-3.5 left-1/2 -translate-x-1/2 z-20">
        <span class="bg-gradient-to-r from-blue-600 via-indigo-600 to-blue-700 text-white font-extrabold text-[11px] uppercase tracking-wider px-4 py-1 rounded-full shadow-md border border-white/50 flex items-center gap-1.5 whitespace-nowrap">
            <i class="fa-solid fa-fire text-amber-300"></i> <?php echo htmlspecialchars($badge_text ?: 'Most Popular'); ?>
        </span>
    </div>
    <?php endif; ?>

    <div class="p-6 text-center rounded-t-2xl border-b border-gray-100 <?php echo $is_popular ? 'bg-blue-50/80 pt-8' : 'bg-gray-50/60 pt-6'; ?>">
        <h3 class="text-xl font-extrabold text-gray-900 mb-1"><?php echo htmlspecialchars($plan['name']); ?></h3>
        <p class="text-gray-500 text-xs font-medium mb-4"><?php echo htmlspecialchars($plan['subtitle'] ?: 'High performance & secure'); ?></p>
        
        <div class="flex items-baseline justify-center gap-1">
            <span class="text-blue-600 font-extrabold text-xl"><?php echo htmlspecialchars($curr_symbol); ?></span>
            <h4 class="text-3xl xl:text-4xl font-extrabold text-gray-900 tracking-tight">
                <span class="priceValue" data-monthly="<?php echo $conv_monthly; ?>" data-yearly="<?php echo $conv_yearly; ?>"><?php echo $display_price; ?></span>
            </h4>
            <span class="priceFor text-xs font-bold text-gray-500 uppercase"><?php echo $display_period; ?></span>
        </div>
    </div>

    <div class="p-6 space-y-4 text-gray-700 flex-grow bg-white">
        <div class="text-xs font-bold uppercase text-gray-400 tracking-wider mb-2">Key Features</div>
        <ul class="space-y-3 text-sm">
            <?php if ($features): foreach ($features as $feature): ?>
            <li class="flex items-center space-x-2.5">
                <i class="fa-solid fa-circle-check text-blue-600 text-sm flex-shrink-0"></i>
                <span class="text-gray-600 text-xs md:text-sm font-medium"><?php echo htmlspecialchars($feature); ?></span>
            </li>
            <?php endforeach; endif; ?>
        </ul>
    </div>

    <div class="p-6 pt-2 bg-white">
        <a href="<?php echo htmlspecialchars($plan['order_url'] ?: '#'); ?>" data-ripple-light="true" class="btn btn-blue w-full flex items-center justify-center gap-2 py-3 rounded-xl font-bold shadow-md transition hover:shadow-lg">
            <span>Order Now</span>
            <i class="fa-solid fa-arrow-right text-xs"></i>
        </a>
    </div>
</div>
<?php endwhile; ?>
<?php else: ?>
<div class="col-span-full text-center py-16 bg-white rounded-2xl border border-gray-200">
    <i class="fa-solid fa-box-open text-4xl text-gray-300 mb-3 block"></i>
    <p class="text-gray-500 text-base font-semibold">No hosting plans available in this category yet.</p>
</div>
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

<script>
function setPricingBilling(isAnnual) {
    var sw = document.getElementById('pricingSwitch');
    if (sw) {
        sw.checked = isAnnual;
        toggleBillingSwitch(isAnnual);
    }
}

function toggleBillingSwitch(isAnnual) {
    var cards = document.querySelectorAll('.plan-pricing-card');
    cards.forEach(function(card) {
        var mode = card.dataset.billingMode;
        var pVal = card.querySelector('.priceValue');
        var pFor = card.querySelector('.priceFor');
        if (!pVal || !pFor) return;

        if (mode === 'yearly-only') {
            pVal.innerHTML = pVal.dataset.yearly;
            pFor.innerHTML = ' /Year';
        } else if (mode === 'monthly-only') {
            pVal.innerHTML = pVal.dataset.monthly;
            pFor.innerHTML = ' /Month';
        } else {
            if (isAnnual) {
                pVal.innerHTML = pVal.dataset.yearly;
                pFor.innerHTML = ' /Year';
            } else {
                pVal.innerHTML = pVal.dataset.monthly;
                pFor.innerHTML = ' /Month';
            }
        }
    });
}
</script>
</body>
</html>
