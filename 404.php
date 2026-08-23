<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
http_response_code(404);
$site_name = getSetting('site_name') ?: 'Host Nibo';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include "cdnjs.php"; ?>
<title>404 - Page Not Found | <?php echo htmlspecialchars($site_name); ?></title>
<?php echo renderSeoTags(['title' => "404 - Page Not Found | $site_name", 'description' => 'The page you are looking for does not exist or has been moved.']); ?>
</head>
<body class="bg-gray-50 flex flex-col min-h-screen">
<?php include "header.php"; ?>

<main class="flex-grow flex items-center justify-center py-20 px-4">
    <div class="max-w-2xl mx-auto text-center">
        <div class="relative mb-6">
            <h1 class="text-8xl md:text-9xl font-extrabold text-blue-600 opacity-20 select-none">404</h1>
            <div class="absolute inset-0 flex items-center justify-center">
                <i class="fa fa-exclamation-triangle text-5xl md:text-6xl text-blue-600"></i>
            </div>
        </div>
        <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Page Not Found</h2>
        <p class="text-gray-600 text-lg mb-8 max-w-md mx-auto">
            Oops! The page you are looking for might have been removed, had its name changed, or is temporarily unavailable.
        </p>
        <div class="flex flex-wrap gap-4 justify-center">
            <a href="index.php" class="btn btn-blue px-6 py-3 rounded-lg font-medium shadow hover:shadow-lg transition">
                <i class="fa fa-home mr-2"></i> Return Home
            </a>
            <a href="blogs.php" class="btn bg-gray-200 text-gray-800 px-6 py-3 rounded-lg font-medium hover:bg-gray-300 transition">
                <i class="fa fa-newspaper mr-2"></i> Visit Blog
            </a>
            <a href="contact.php" class="btn bg-white border border-gray-300 text-gray-700 px-6 py-3 rounded-lg font-medium hover:bg-gray-50 transition">
                <i class="fa fa-headset mr-2"></i> Contact Support
            </a>
        </div>
    </div>
</main>

<?php include "footer.php"; ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://unpkg.com/alpinejs@3.14.9/dist/cdn.min.js"></script>
<script src="/js/scroll.js"></script>
<script src="/js/ns.js"></script>
</body>
</html>
