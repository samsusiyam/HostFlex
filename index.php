<?php
// Check if installed; redirect to wizard if not
if (!file_exists(__DIR__ . '/config/database.php')) { header('Location: config/install.php'); exit; }
require_once 'config/database.php';
if (!$conn || !mysqli_ping($conn)) { header('Location: config/install.php'); exit; }
$r = mysqli_query($conn, "SHOW TABLES LIKE 'menu_items'");
if (!$r || mysqli_num_rows($r) == 0) { header('Location: config/install.php'); exit; }
require_once 'includes/functions.php'; checkMaintenance();

// Load homepage sections
$homepage_sections_raw = getSetting('homepage_sections');
$homepage_sections = [];
if ($homepage_sections_raw) {
    $decoded = json_decode($homepage_sections_raw, true);
    if (is_array($decoded)) $homepage_sections = $decoded;
}
if (empty($homepage_sections)) {
    $homepage_sections = [
        ['type' => 'hero', 'enabled' => '1', 'sort_order' => 1, 'content' => [
            'tagline' => getSetting('hero_tagline') ?: getSetting('site_tagline') ?: 'Fast & Reliable Web Hosting',
            'description' => getSetting('hero_description') ?: getSetting('site_description') ?: 'Experience premium hosting with exceptional performance.',
            'image' => getSetting('hero_image') ?: 'images/cloud.jpg',
            'button_text' => getSetting('hero_button_text') ?: 'Get Started',
            'button_url' => getSetting('hero_button_url') ?: getSetting('whmcs_domain_register_url'),
            'chat_text' => getSetting('hero_chat_text') ?: 'Live Chat',
            'chat_url' => getSetting('hero_chat_url') ?: 'javascript:void(Tawk_API.toggle())'
        ]],
        ['type' => 'domain_search', 'enabled' => '1', 'sort_order' => 2, 'content' => [
            'search_url' => getSetting('whmcs_domain_search_url') ?: 'https://my.hostnibo.com/domainchecker.php',
            'pricing' => [
                ['tld' => '.com', 'price' => '999'],
                ['tld' => '.online', 'price' => '455'],
                ['tld' => '.xyz', 'price' => '250']
            ]
        ]],
        ['type' => 'features', 'enabled' => getSetting('features_section_enabled') !== '' ? getSetting('features_section_enabled') : '1', 'sort_order' => 3, 'content' => [
            'heading' => getSetting('features_heading') ?: 'Why Choose Us',
            'cards' => []
        ]],
        ['type' => 'offers', 'enabled' => '1', 'sort_order' => 4, 'content' => []],
        ['type' => 'categories', 'enabled' => '1', 'sort_order' => 5, 'content' => []],
        ['type' => 'bottom_cta', 'enabled' => getSetting('bottom_cta_enabled') !== '' ? getSetting('bottom_cta_enabled') : '1', 'sort_order' => 6, 'content' => [
            'heading' => getSetting('bottom_cta_heading') ?: 'Do you have any questions? <br> We are always here to answer you',
            'description' => getSetting('bottom_cta_description') ?: "Get in touch with one of our specialists.",
            'image' => getSetting('bottom_cta_image') ?: 'images/tp.png'
        ]],
        ['type' => 'refund', 'enabled' => getSetting('refund_section_enabled') !== '' ? getSetting('refund_section_enabled') : '1', 'sort_order' => 7, 'content' => [
            'heading' => getSetting('refund_heading') ?: 'Enjoy peace of mind with our 7-Day Money Back Guarantee',
            'text' => getSetting('refund_text') ?: "If you're not satisfied with our hosting and if you are a new customer within the first 7 days, we'll refund your payment. Full details read terms",
            'image' => getSetting('refund_image') ?: 'images/refund.png'
        ]]
    ];
}

// Sort by sort_order
usort($homepage_sections, function($a, $b) {
    return ($a['sort_order'] ?? 0) - ($b['sort_order'] ?? 0);
});

$currency_symbol = escSetting('currency_symbol') ?: 'TK.';
$pricing_url = getSetting('whmcs_domain_pricing_url');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include "cdnjs.php"; ?>
<title><?php echo escSetting('site_name'); ?></title>
</head>
<body>

<?php include "header.php"; ?>
<?php include "contact-btn.php"; ?>

<main>

<?php foreach ($homepage_sections as $section):
    if (($section['enabled'] ?? '1') !== '1') continue;
    $c = $section['content'] ?? [];
    $type = $section['type'];
?>

<?php if ($type === 'hero'): ?>
<!-- Hero Section -->
<section class="py-16 bg-white">
<div class="content grid grid-cols-1 lg:grid-cols-2">
<div class="flex flex-col justify-center gap-6 md:gap-12">
<h1 class="flex flex-col gap-2 text-[36px] font-extrabold capitalize leading-[45px] xl:text-[46px]"><span class="text-[#111827]"><?php echo htmlspecialchars($c['tagline'] ?? ''); ?></span></h1>
<p class="md:max-w-[600px] lg:pr-12"><?php echo htmlspecialchars($c['description'] ?? ''); ?></p>
<div class="flex w-fit gap-x-2">
<a href="<?php echo htmlspecialchars($c['button_url'] ?: escSetting('whmcs_domain_register_url')); ?>" data-ripple-light="true" class="btn !px-8 btn-purple"> <?php echo htmlspecialchars($c['button_text'] ?? 'Get Started'); ?> <i class="fa fa-arrow-right"></i> </a>
<a href="<?php echo htmlspecialchars($c['chat_url'] ?: 'javascript:void(Tawk_API.toggle());'); ?>" data-ripple-light="true" class="btn btn-blue !px-8"> <i class="fa fa-envelope"></i> <?php echo htmlspecialchars($c['chat_text'] ?? 'Live Chat'); ?></a>
</div>
</div>
<?php $hero_img = $c['image'] ?? 'images/cloud.jpg'; $hero_webp = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $hero_img); ?>
<div class="hidden lg:block px-6"><picture style="display:block;aspect-ratio:4/3"><source srcset="<?php echo htmlspecialchars($hero_webp); ?>" type="image/webp"><img src="<?php echo htmlspecialchars($hero_img); ?>" alt="<?php echo escSetting('site_name'); ?> Hero" width="800" height="600" style="width:100%;height:100%;object-fit:contain" fetchpriority="high"></picture></div>
</div>
</div>

<?php elseif ($type === 'domain_search'): ?>
<!-- Domain Search -->
<div class="content mt-32 mb-10">
<div class="flex flex-col justify-between gap-12 rounded-xl bg-blue-50 py-8 px-4 shadow-xl dark:bg-gray-800 sm:gap-8 sm:px-6 2xl:flex-row">
<form method="get" action="<?php echo htmlspecialchars(getSetting('whmcs_domain_search_url') ?: 'https://my.hostnibo.com/domainchecker.php'); ?>" class="flex w-auto">
<input name="domain" placeholder="Search domain name..." class="input !py-3 lg:!w-[500px] h-[53px]" type="search" />
<div class="ml-2 w-fit">
<button type="submit" size="custom" class="h-[53px] btn btn-blue" aria-label="Search domain">
<i class="fa fa-search"></i> <span class="hidden sm:block">Search</span>
</button>
</div>
</form>
<div class="flex justify-between gap-4 md:gap-8">
<?php $pricing_items = $c['pricing'] ?? []; if (!empty($pricing_items)): foreach ($pricing_items as $item): ?>
<a href="<?php echo $pricing_url; ?>" class="mx-auto mt-2 flex items-center justify-center sm:mt-0 sm:mr-0"><span class="text-sm dark:text-gray-100 sm:text-base font-bold"><?php echo htmlspecialchars($item['tld'] ?? ''); ?> <span class="ml-1"><?php echo $currency_symbol; ?><?php echo htmlspecialchars($item['price'] ?? ''); ?></span></span></a>
<?php endforeach; else: ?>
<a href="<?php echo $pricing_url; ?>" class="mx-auto mt-2 flex items-center justify-center sm:mt-0 sm:mr-0"><span class="text-sm dark:text-gray-100 sm:text-base font-bold">.com <span class="ml-1"><?php echo $currency_symbol; ?>999</span></span></a>
<a href="<?php echo $pricing_url; ?>" class="mx-auto mt-2 flex items-center justify-center sm:mt-0 sm:mr-0"><span class="text-sm dark:text-gray-100 sm:text-base font-bold">.online <span class="ml-1"><?php echo $currency_symbol; ?>455</span></span></a>
<a href="<?php echo $pricing_url; ?>" class="mx-auto mt-2 flex items-center justify-center sm:mt-0 sm:mr-0"><span class="text-sm dark:text-gray-100 sm:text-base font-bold">.xyz <span class="ml-1"><?php echo $currency_symbol; ?>250</span></span></a>
<?php endif; ?>
</div>
</div>
</div>
</section>

<?php elseif ($type === 'categories'): ?>
<!-- Categories Section -->
<?php $cat_count = (int)($c['count'] ?? 4); $cat_heading = $c['heading'] ?? 'Our Hosting Plans'; ?>
<?php $categories = mysqli_query($conn, "SELECT * FROM categories WHERE status = 1 ORDER BY sort_order ASC LIMIT $cat_count"); ?>
<?php if (mysqli_num_rows($categories) > 0): ?>
<section class="section_gap bg-[#f8f7f7]">
<div class="content">
<?php if ($cat_heading): ?><div class="mb-10 text-center"><h2 class="text-3xl font-bold"><?php echo htmlspecialchars($cat_heading); ?></h2></div><?php endif; ?>
<div class="grid grid-cols-1 gap-8 xsm:grid-cols-2 lg:grid-cols-<?php echo min(4, $cat_count); ?>">
<?php while ($cat = mysqli_fetch_assoc($categories)):
    $min_price = mysqli_fetch_assoc(mysqli_query($conn, "SELECT MIN(monthly_price) as min_price FROM hosting_plans WHERE category = '{$cat['slug']}' AND status = 1"));
?>
<div class="bg-white border border-blue-600 overflow-hidden rounded-xl shadow-xl">
<div class="flex flex-col items-center justify-center">
<div class="px-14 py-8"><?php $cat_img = $cat['image'] ?: 'images/s.png'; $cat_webp = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $cat_img); ?>
<picture><source srcset="<?php echo htmlspecialchars($cat_webp); ?>" type="image/webp"><img src="<?php echo htmlspecialchars($cat_img); ?>" class="h-24 sm:h-20" alt="<?php echo htmlspecialchars($cat['name']); ?>" width="96" height="96" loading="lazy"></picture></div>
<h1 class="mb-4 px-8 text-xl font-bold text-black"><?php echo htmlspecialchars($cat['name']); ?></h1>
<p class="text-md px-8 text-center font-normal text-gray-900"><?php echo htmlspecialchars($cat['description']); ?></p>
<?php if ($min_price && $min_price['min_price']): ?>
<span class="mt-4 w-[80%] rounded-md p-2 text-center dark:bg-gray-700">
<p class="text-gray-800">Starting at</p>
<p class="text-xl font-bold text-black"><?php echo $currency_symbol; ?> <?php echo $min_price['min_price']; ?> <span class="text-lg font-normal">/mo</span></p>
</span>
<?php endif; ?>
</div>
<div class="p-4"><a data-ripple-dark="true" href="/category/<?php echo $cat['slug']; ?>" class="btn btn-blue">Get started</a></div>
</div>
<?php endwhile; ?>
</div>
</div>
</section>
<?php endif; ?>

<?php elseif ($type === 'features'): ?>
<!-- Features Section -->
<?php
$features_heading = $c['heading'] ?? '';
$cards = $c['cards'] ?? [];
if (empty($cards)) {
    $features_data_raw = getSetting('features_data');
    if (trim($features_data_raw ?? '') !== '') {
        $lines = explode("\n", $features_data_raw);
        foreach ($lines as $line) {
            $parts = explode('|', $line);
            if (count($parts) >= 3) {
                $cards[] = ['icon' => trim($parts[0]), 'title' => trim($parts[1]), 'desc' => trim($parts[2])];
            }
        }
    }
}
if (empty($cards)) {
    $cards = [
        ['icon' => 'images/icon/speedometer.png', 'title' => 'Performance', 'desc' => 'We utilize BDIX-powered server to ensure top-notch performance, delivering lightning-fast page loads and unparalleled website speed.'],
        ['icon' => 'images/icon/bar-chart.png', 'title' => 'Reliability and uptime', 'desc' => 'Priyo Host delivers reliable web hosting with guaranteed 99.9% uptime, you can trust that your website will be up and running always.'],
        ['icon' => 'images/icon/settings.png', 'title' => 'Security', 'desc' => 'Our enterprise-grade security system ensures your website is protected, giving you peace of mind and safeguarding your brand and audience.'],
        ['icon' => 'images/icon/control-panel.png', 'title' => 'Control Panel', 'desc' => 'We provide cPanel control panel, which allows you to effortlessly establish and manage your website with ease.'],
        ['icon' => 'images/icon/time-is-money.png', 'title' => 'Money Guarantee', 'desc' => 'Confidently host your website with our lightning-fast hosting and enjoy a 7 day money-back guarantee for ultimate peace of mind.'],
        ['icon' => 'images/icon/refresh.png', 'title' => 'Scalability', 'desc' => 'We offer scalable hosting solutions with flexible resources and the ability to upgrade as needed, ensuring your site runs smoothly.'],
        ['icon' => 'images/icon/lock.png', 'title' => 'Free SSL Certificate', 'desc' => 'Secure your website with our complimentary SSL certificate, ensuring online transactions and sensitive information is protected.'],
        ['icon' => 'images/icon/customer-service.png', 'title' => 'Professional Support', 'desc' => 'Enjoy seamless 24/7 professional support in your local language. Our client success team is always ready to assist you.']
    ];
}
?>
<section class="section_gap bg-white">
<div class="content">
<?php if ($features_heading): ?>
<div class="mb-10 text-center"><h2 class="text-3xl font-bold"><?php echo htmlspecialchars($features_heading); ?></h2></div>
<?php endif; ?>
<div class="grid grid-cols-1 gap-4 xsm:grid-cols-2 md:gap-8 lg:grid-cols-4">
<?php foreach ($cards as $feature): ?>
<div class="flex flex-col gap-3 rounded border p-6 shadow">
<?php $ficon = $feature['icon'] ?? 'images/icon/speedometer.png'; $fwebp = preg_replace('/\.png$/i', '.webp', $ficon); ?>
<picture><source srcset="<?php echo htmlspecialchars($fwebp); ?>" type="image/webp"><img class="h-[65px] w-[65px]" src="<?php echo htmlspecialchars($ficon); ?>" alt="<?php echo htmlspecialchars($feature['title']); ?>" width="65" height="65" loading="lazy" style="aspect-ratio:1"></picture>
<h3><?php echo htmlspecialchars($feature['title']); ?></h3>
<p><?php echo htmlspecialchars($feature['desc'] ?? $feature['description'] ?? ''); ?></p>
</div>
<?php endforeach; ?>
</div>
</div>
</section>

<?php elseif ($type === 'offers'): ?>
<!-- Offers Section -->
<?php $offers = getActiveOffers(); ?>
<?php if (mysqli_num_rows($offers) > 0): ?>
<section class="section_gap bg-white">
<div class="content">
<div class="flex items-center justify-between">
<div class="mb-12 flex flex-col gap-2">
<h5 class="text-blue-600">HOT DEALS</h5>
<h2 class="text-black">Latest Deals &amp; Promos</h2>
<p>Unlock more possibilities at a fraction of the cost with our best deals</p>
</div>
<div class="w-fit pt-6 ml-auto">
<a href="offers.php" data-ripple-light="true" class="btn btn-blue">View More <span class="pl-2 transition-all group-hover:-mr-1"><i class="fa-solid fa-arrow-right"></i></span></a>
</div>
</div>
<div class="grid grid-cols-1 gap-4 xsm:grid-cols-2 md:gap-8 lg:grid-cols-4">
<?php while ($offer = mysqli_fetch_assoc($offers)): ?>
<div class="rounded-lg border bg-white shadow p-6">
<?php if ($offer['badge']): ?><span class="inline-block bg-red-500 text-white text-xs px-2 py-1 rounded mb-2"><?php echo htmlspecialchars($offer['badge']); ?></span><?php endif; ?>
<h3 class="text-lg font-bold"><?php echo htmlspecialchars($offer['title']); ?></h3>
<p class="text-gray-600 text-sm mt-2"><?php echo htmlspecialchars($offer['description']); ?></p>
<?php if ($offer['price_label']): ?><p class="text-xl font-bold text-blue-600 mt-3"><?php echo htmlspecialchars($offer['price_label']); ?></p><?php endif; ?>
<a href="<?php echo $offer['link_url'] ?: '#'; ?>" class="btn btn-blue mt-4 inline-block"><?php echo htmlspecialchars($offer['link_text'] ?: 'Learn More'); ?></a>
</div>
<?php endwhile; ?>
</div>
</div>
</section>
<?php endif; ?>

<?php elseif ($type === 'bottom_cta'): ?>
<!-- Bottom CTA Section -->
<div class="bg-gray-50 dark:bg-gray-900">
<div class="max-w-7xl mx-auto px-6 py-12 pt-14 lg:py-4 border-t dark:border-gray-700">
<div class="sm:flex items-center rounded-xl py-2 sm:pt-16 sm:pb-20 sm:mt-8">
<div class="sm:flex-1 md:w-7/12">
<h2 class="text-3xl md:text-4xl font-extrabold dark:text-gray-100"><?php echo $c['heading'] ?? ''; ?></h2>
<p class="mt-10 text-lg pr-0 sm:pr-8 dark:text-gray-400"><?php echo $c['description'] ?? ''; ?></p>
<div class="mt-4 grid grid-cols-1 sm:grid-cols-4 gap-6 py-10">
<div class="bg-white shadow-lg rounded-xl overflow-hidden">
<div class="flex justify-center px-4 py-5"><img src="images/svg/LiveChat.svg" alt class="w-12 h-14" width="48" height="56" loading="lazy"></div>
<a href="javascript:void(Tawk_API.toggle())"><h1 class="text-md text-gray-100 text-center bg-blue-600 py-3 px-4 font-semibold">Live Chat</h1></a>
</div>
<div class="bg-white shadow-lg rounded-xl overflow-hidden">
<div class="flex justify-center px-4 py-5"><img src="images/svg/EmailSupport.svg" alt class="w-12 h-14" width="48" height="56" loading="lazy"></div>
<a href="contact.php"><h1 class="text-md text-gray-100 text-center bg-purple-600 py-3 px-4 font-semibold">Email Support</h1></a>
</div>
<div class="bg-white shadow-lg rounded-xl overflow-hidden">
<div class="flex justify-center px-4 py-5"><img src="images/svg/Helpdesk.svg" alt class="w-12 h-14" width="48" height="56" loading="lazy"></div>
<a href="contact.php"><h1 class="text-md text-gray-100 text-center bg-indigo-600 py-3 px-4 font-semibold">24x7 Helpdesk</h1></a>
</div>
</div>
</div>
<div class="flex md:w-5/12 justify-end items-center max-lg:hidden">
<?php $cta_img = $c['image'] ?? 'images/tp.png'; $cta_webp = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $cta_img); ?>
<picture><source srcset="<?php echo htmlspecialchars($cta_webp); ?>" type="image/webp"><img src="<?php echo htmlspecialchars($cta_img); ?>" alt="Hosting illustration" class="h-full mt-12 sm:mt-0" width="500" height="400" loading="lazy"></picture>
</div>
</div>
</div>
</div>

<?php elseif ($type === 'refund'): ?>
<!-- Refund Section -->
<section class="section_gap">
<div class="content">
<div class="w-fit mx-auto">
<div class="mb-12 flex flex-col gap-2">
<?php $ref_img = $c['image'] ?? 'images/refund.png'; $ref_webp = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $ref_img); ?>
<picture><source srcset="<?php echo htmlspecialchars($ref_webp); ?>" type="image/webp"><img src="<?php echo htmlspecialchars($ref_img); ?>" alt="Refund Guarantee" width="350" height="350" loading="lazy"></picture>
<h2 class="text-black text-2xl font-bold"><?php echo htmlspecialchars($c['heading'] ?? ''); ?></h2>
<p><?php echo htmlspecialchars($c['text'] ?? ''); ?></p>
</div>
</div>
</div>
</section>

<?php elseif ($type === 'blog'): ?>
<?php
$blog_count = (int)($c['count'] ?? 3);
$blog_heading = $c['heading'] ?? 'Latest Blog';
$blog_posts = mysqli_query($conn, "SELECT p.*, c.name as category_name, c.slug as category_slug FROM blog_posts p LEFT JOIN blog_categories c ON p.category_id = c.id WHERE p.status = 1 ORDER BY p.created_at DESC LIMIT $blog_count");
if (mysqli_num_rows($blog_posts) > 0):
?>
<section class="section_gap bg-white">
<div class="content">
<div class="mb-10 text-center">
    <h2 class="text-3xl font-bold"><?php echo htmlspecialchars($blog_heading); ?></h2>
</div>
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-<?php echo min(3, $blog_count); ?> gap-6">
    <?php while ($bpost = mysqli_fetch_assoc($blog_posts)): ?>
    <div class="bg-white border rounded-xl overflow-hidden shadow hover:shadow-lg transition">
        <?php if ($bpost['image']): ?>
        <a href="blog.php?slug=<?php echo htmlspecialchars($bpost['slug']); ?>">
        <img src="<?php echo htmlspecialchars($bpost['image']); ?>" class="w-full h-48 object-cover" alt="<?php echo htmlspecialchars($bpost['title']); ?>" width="400" height="192"></a>
        <?php endif; ?>
        <div class="p-4">
            <p class="text-xs text-gray-500"><?php echo date('F d, Y', strtotime($bpost['created_at'])); ?></p>
            <h3 class="text-lg font-bold mt-1 mb-2"><a href="blog.php?slug=<?php echo htmlspecialchars($bpost['slug']); ?>" class="text-gray-900 hover:text-blue-600"><?php echo htmlspecialchars($bpost['title']); ?></a></h3>
            <p class="text-sm text-gray-500 mb-3"><?php echo htmlspecialchars($bpost['excerpt'] ?: substr(strip_tags($bpost['content']), 0, 150) . '...'); ?></p>
            <div class="text-xs text-gray-400">
                <span><?php echo date('d M Y', strtotime($bpost['created_at'])); ?></span>
            </div>
        </div>
    </div>
    <?php endwhile; ?>
</div>
<div class="text-center mt-8">
    <a href="blogs.php" class="btn btn-blue">View All Posts</a>
</div>
</div>
</section>
<?php endif; ?>

<?php elseif ($type === 'testimonials'): ?>
<?php
$t_count = (int)($c['count'] ?? 4);
$t_heading = $c['heading'] ?? 'What Our Clients Say';
$testimonials = mysqli_query($conn, "SELECT * FROM testimonials WHERE status = 1 ORDER BY sort_order ASC LIMIT $t_count");
if (mysqli_num_rows($testimonials) > 0):
?>
<section class="section_gap bg-[#f8f7f7]">
<div class="content">
<div class="mb-10 text-center"><h2 class="text-3xl font-bold"><?php echo htmlspecialchars($t_heading); ?></h2></div>
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-<?php echo min(4, $t_count); ?> gap-6">
    <?php while ($t = mysqli_fetch_assoc($testimonials)): ?>
    <div class="bg-white rounded-xl shadow p-6 border">
        <div class="flex items-center gap-3 mb-3">
            <?php if ($t['photo']): ?><img src="<?php echo htmlspecialchars($t['photo']); ?>" class="w-12 h-12 rounded-full object-cover" width="48" height="48"><?php endif; ?>
            <div>
                <p class="font-semibold"><?php echo htmlspecialchars($t['name']); ?></p>
                <?php if ($t['company']): ?><p class="text-sm text-gray-500"><?php echo htmlspecialchars($t['company']); ?></p><?php endif; ?>
            </div>
        </div>
        <div class="text-yellow-500 text-sm mb-2"><?php echo str_repeat('★', (int)$t['rating']) . str_repeat('☆', 5-(int)$t['rating']); ?></div>
        <p class="text-gray-600 text-sm italic">"<?php echo htmlspecialchars($t['review']); ?>"</p>
    </div>
    <?php endwhile; ?>
</div>
</div>
</section>
<?php endif; ?>

<?php elseif ($type === 'faqs'): ?>
<?php
$faq_heading = $c['heading'] ?? 'Frequently Asked Questions';
$faqs = mysqli_query($conn, "SELECT * FROM faqs WHERE status = 1 ORDER BY sort_order ASC");
if (mysqli_num_rows($faqs) > 0):
?>
<section class="section_gap bg-white">
<div class="content max-w-4xl mx-auto">
<div class="mb-10 text-center"><h2 class="text-3xl font-bold"><?php echo htmlspecialchars($faq_heading); ?></h2></div>
<div class="space-y-4">
    <?php while ($faq = mysqli_fetch_assoc($faqs)): ?>
    <div class="border rounded-lg overflow-hidden">
        <button onclick="this.nextElementSibling.classList.toggle('hidden');this.querySelector('i').classList.toggle('fa-chevron-down');this.querySelector('i').classList.toggle('fa-chevron-up')" class="w-full flex items-center justify-between px-5 py-4 text-left font-medium bg-gray-50 hover:bg-gray-100 transition">
            <span><?php echo htmlspecialchars($faq['question']); ?></span>
            <i class="fa fa-chevron-down text-gray-400 text-sm"></i>
        </button>
        <div class="px-5 py-4 hidden border-t text-gray-600"><?php echo $faq['answer']; ?></div>
    </div>
    <?php endwhile; ?>
</div>
</div>
</section>
<?php endif; ?>

<?php elseif ($type === 'partners'): ?>
<?php
$p_heading = $c['heading'] ?? 'Our Partners';
$partners = mysqli_query($conn, "SELECT * FROM partners WHERE status = 1 ORDER BY sort_order ASC");
if (mysqli_num_rows($partners) > 0):
?>
<section class="section_gap bg-[#f8f7f7]">
<div class="content">
<div class="mb-10 text-center"><h2 class="text-3xl font-bold"><?php echo htmlspecialchars($p_heading); ?></h2></div>
<div class="flex flex-wrap items-center justify-center gap-8">
    <?php while ($p = mysqli_fetch_assoc($partners)): ?>
    <div class="bg-white rounded-xl shadow px-6 py-5 flex items-center justify-center h-28 w-52">
        <?php if ($p['photo']): ?>
        <img src="<?php echo htmlspecialchars($p['photo']); ?>" style="max-height:64px;max-width:100%;object-fit:contain;" alt="<?php echo htmlspecialchars($p['name']); ?>" width="200" height="64" loading="lazy">
        <?php else: ?>
        <span class="font-semibold text-gray-500"><?php echo htmlspecialchars($p['name']); ?></span>
        <?php endif; ?>
    </div>
    <?php endwhile; ?>
</div>
</div>
</section>
<?php endif; ?>

<?php elseif ($type === 'custom_html'): ?>
<?php echo $c['html'] ?? ''; ?>

<?php endif; ?>
<?php endforeach; ?>

</main>

<?php include "footer.php"; ?>

</body>
</html>

