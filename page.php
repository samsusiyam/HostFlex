<?php require_once 'config/database.php'; require_once 'includes/functions.php'; checkMaintenance();

$slug = isset($_GET['slug']) ? sanitize($_GET['slug']) : '';
$page = getPageBySlug($slug);

if (!$page) {
    header('HTTP/1.0 404 Not Found');
    echo '<h1>404 - Page Not Found</h1>';
    exit;
}
?>
<html lang="en">
<head>
<?php include "cdnjs.php"; ?>
<title><?php echo htmlspecialchars($page['title']); ?> - <?php echo getSetting('site_name'); ?></title>
<meta name="description" content="<?php echo htmlspecialchars($page['meta_description']); ?>">
<meta name="keywords" content="<?php echo htmlspecialchars($page['meta_keywords']); ?>">
</head>
<body>
<?php include "header.php"; ?>
<?php include "contact-btn.php"; ?>
<section class="section_gap">
<div class="content">
<div class="mb-8">
<h1 class="text-3xl md:text-4xl font-extrabold text-gray-800"><?php echo htmlspecialchars($page['title']); ?></h1>
</div>
<div class="prose max-w-none text-gray-700 leading-relaxed">
<?php echo $page['content']; ?>
</div>
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

