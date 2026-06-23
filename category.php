<?php require_once 'config/database.php'; require_once 'includes/functions.php'; checkMaintenance();

$slug = isset($_GET['slug']) ? sanitize($_GET['slug']) : '';
$category = getCategoryBySlug($slug);

if (!$category) {
    header('HTTP/1.0 404 Not Found');
    echo '<h1>404 - Category Not Found</h1>';
    exit;
}
?>
<html lang="en">
<head>
<?php include "cdnjs.php"; ?>
<title><?php echo htmlspecialchars($category['name']); ?> - <?php echo escSetting('site_name'); ?></title>
</head>
<body>
<?php include "header.php"; ?>
<?php include "contact-btn.php"; ?>
<div class="-z-50" style="background-image: url('img/7ee6a14d8e2-shutterstock_1394052911-scaled.html'); background-size: cover; background-position: center;">
<div class="bg-blue-600 bg-opacity-70">
<div class="space-y-16 content mx-auto py-16 lg:pt-20 lg:pb-20">
<div class="flex flex-col lg:flex-row items-center space-y-12 lg:space-y-0">
<div class="sm:w-2/3 text-left">
<h2 class="text-3xl md:text-4xl font-extrabold mb-4 text-white"><?php echo htmlspecialchars($category['name']); ?></h2>
<p class="text-lg md:text-xl font-medium text-gray-200"><?php echo htmlspecialchars($category['description']); ?></p>
</div>
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
<?php $plans = getPlans($category['slug']); ?>
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
<div class="mt-2 inline-flex rounded-full border-2 border-blue-600 overflow-hidden text-xs font-bold leading-none">
    <button type="button" class="billingToggle px-3 py-1.5 bg-blue-600 text-white" data-period="monthly" onclick="setBilling(this,'monthly')">Monthly</button>
    <button type="button" class="billingToggle px-3 py-1.5 text-blue-600" data-period="yearly" onclick="setBilling(this,'yearly')">Yearly</button>
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
function setBilling(btn, period) {
    var card = btn.closest('.overflow-hidden');
    var priceSpan = card.querySelector('.priceValue');
    var labelSpan = card.querySelector('.priceFor');
    card.querySelectorAll('.billingToggle').forEach(function(t) {
        t.classList.remove('bg-blue-600', 'text-white');
        t.classList.add('text-blue-600');
    });
    btn.classList.remove('text-blue-600');
    btn.classList.add('bg-blue-600', 'text-white');
    priceSpan.textContent = priceSpan.getAttribute('data-' + period);
    labelSpan.textContent = period === 'monthly' ? '/month' : '/year';
}
</script>
</div>
</section>
<?php include "footer.php"; ?>
<script src="../cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="../cdn.jsdelivr.net/npm/%40accessible360/accessible-slick%401.0.1/slick/slick.min.js"></script>
<script src="../cdn.jsdelivr.net/npm/%40fancyapps/fancybox%403.5.6/dist/jquery.fancybox.min.js"></script>
<script src="../unpkg.com/alpinejs%403.14.9/dist/cdn.min.js"></script>
<script src="../cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js"></script>
<script src="../unpkg.com/%40material-tailwind/html%403.0.0-beta.7/scripts/ripple.js"></script>
<script src="../unpkg.com/%40material-tailwind/html%402.0.0/scripts/collapse.js"></script>
<script src="../unpkg.com/%40material-tailwind/html%402.0.0/scripts/dialog.js"></script>
<script src="../unpkg.com/%40material-tailwind/html%402.0.0/scripts/dismissible.js"></script>
<script type="module" src="../unpkg.com/%40material-tailwind/html%402.0.0/scripts/popover.js"></script>
<script src="../unpkg.com/%40material-tailwind/html%402.0.0/scripts/tabs.js"></script>
<script type="module" src="../unpkg.com/%40material-tailwind/html%402.0.0/scripts/tooltip.js"></script>
<script src="../unpkg.com/tailwindcss%402.2.19/dist/tailwind.min.js"></script>
<script src="js/scroll.js"></script>
<script src="js/ns.js"></script>
<script src="js/ns-jquery.js"></script>
</body>
</html>

