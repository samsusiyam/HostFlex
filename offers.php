<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
checkMaintenance();
$site_name = escSetting('site_name') ?: 'HostNibo';
$offers_meta = getSetting('offers_meta_description') ?: "Check out our latest hosting offers and deals at $site_name. Save big on web hosting, domain registration, VPS, and dedicated server plans with exclusive discounts.";
$offers_kw = getSetting('meta_keywords') ?: 'hosting offers, deals, discounts, web hosting sale, domain offers, VPS deals';
$skip_default_meta = true;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include "cdnjs.php"; ?>
<title>Hosting Offers & Deals - <?php echo $site_name; ?> Exclusive Discounts</title>
<meta name="description" content="<?php echo htmlspecialchars($offers_meta); ?>">
<meta name="keywords" content="<?php echo htmlspecialchars($offers_kw); ?>">
</head>
<body>
<?php include "header.php"; ?>
<?php include "contact-btn.php"; ?>
<div class="-z-50" style="background-image: url('img/7ee6a14d8e2-shutterstock_1394052911-scaled.html'); background-size: cover; background-position: center;">
<div class="bg-blue-600 bg-opacity-70">
<div class="space-y-16 content mx-auto py-16 lg:pt-20 lg:pb-20">
<?php $breadcrumbs = [['label' => 'Offers']]; include __DIR__ . '/breadcrumb.php'; ?>
<div class="flex flex-col lg:flex-row items-center space-y-12 lg:space-y-0">
<div class="sm:w-2/3 text-left">
<h1 class="text-3xl md:text-4xl font-extrabold mb-4 text-white">Hot Deals &amp; Offers</h1>
<p class="text-lg md:text-xl font-medium text-gray-200">Unlock more possibilities at a fraction of the cost with our best deals</p>
</div>
</div>
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
</body>
</html>

