<?php require_once 'config/database.php'; require_once 'includes/functions.php'; checkMaintenance();
if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'offers.php') !== false) {
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: /offers");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include "cdnjs.php"; ?>
<title>Special Hosting Offers & Deals - <?php echo htmlspecialchars(getSetting('site_name') ?: 'Host Nibo'); ?></title>
<?php echo renderSeoTags([
    'title' => 'Special Hosting Offers & Deals - ' . (getSetting('site_name') ?: 'Host Nibo'),
    'description' => 'Save big on web hosting, cloud servers, and domain names with our exclusive limited-time promo deals.'
]); ?>
</head>
<body>
<?php include "header.php"; ?>
<?php include "contact-btn.php"; ?>
<div class="page-hero">
<div class="content mx-auto">
<div class="sm:w-3/4 text-left">
<h1>Hot Deals &amp; Offers</h1>
<p>Unlock more possibilities at a fraction of the cost with our best deals</p>
</div>
</div>
</div>
<section class="section_gap">
<div class="content">
<div class="mb-12 flex flex-col gap-2">
<h5 class="text-blue-600">HOT DEALS</h5>
<h2 class="text-black">Latest Deals &amp; Promos</h2>
<p>Check out our latest offers and save big on your hosting needs</p>
</div>
<?php $all_offers = getActiveOffers(); ?>
<?php if (mysqli_num_rows($all_offers) > 0): ?>
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
<?php while ($offer = mysqli_fetch_assoc($all_offers)): ?>
<div class="bg-white rounded-xl shadow-lg border overflow-hidden hover:shadow-xl transition">
<?php if ($offer['badge']): ?><div class="bg-red-500 text-white text-center text-sm font-bold py-2 uppercase tracking-wider"><?php echo htmlspecialchars($offer['badge']); ?></div><?php endif; ?>
<div class="p-8">
<h3 class="text-2xl font-bold text-gray-800 mb-3"><?php echo htmlspecialchars($offer['title']); ?></h3>
<p class="text-gray-600 mb-4"><?php echo htmlspecialchars($offer['description']); ?></p>
<?php if ($offer['price_label']): ?><p class="text-3xl font-bold text-blue-600 mb-6"><?php echo htmlspecialchars($offer['price_label']); ?></p><?php endif; ?>
<a href="<?php echo $offer['link_url'] ?: '#'; ?>" class="btn btn-blue"><?php echo htmlspecialchars($offer['link_text'] ?: 'Learn More'); ?></a>
</div>
</div>
<?php endwhile; ?>
</div>
<?php else: ?>
<div class="text-center py-12"><p class="text-gray-400 text-lg">No offers available at the moment. Check back later!</p></div>
<?php endif; ?>
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

