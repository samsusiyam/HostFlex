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
<meta name="robots" content="noindex, follow" />
<?php echo renderSeoTags(['title' => "404 - Page Not Found | $site_name", 'description' => 'The page you are looking for does not exist or has been moved.']); ?>
</head>
<body class="bg-gray-50 flex flex-col min-h-screen">
<?php include "header.php"; ?>
<?php include "contact-btn.php"; ?>

<!-- Full Width Header Hero matching category, offers, and other pages -->
<div class="page-hero">
    <div class="content mx-auto">
        <div class="sm:w-3/4 text-left">
            <div class="inline-flex items-center gap-2 bg-white/20 text-white text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wider mb-3">
                <i class="fa fa-triangle-exclamation text-yellow-300"></i> Error 404
            </div>
            <h1>Page Not Found</h1>
            <p>The page you are looking for might have been moved, renamed, or is temporarily unavailable.</p>
        </div>
    </div>
</div>

<main class="flex-grow py-14 px-4 bg-gray-50">
    <div class="content mx-auto max-w-5xl">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8 md:p-12 text-center mb-10">
            <div class="w-16 h-16 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center text-3xl mx-auto mb-4">
                <i class="fa fa-compass"></i>
            </div>
            <h2 class="text-2xl md:text-3xl font-extrabold text-gray-900 mb-3">Let's get you back on track</h2>
            <p class="text-gray-600 max-w-lg mx-auto mb-8 text-base">
                You can return to the home page, explore our hosting solutions below, or get in touch with our 24/7 technical support team.
            </p>

            <!-- Compact, Centered Action Buttons -->
            <div class="flex flex-wrap items-center justify-center gap-4">
                <a href="/" class="btn-404-primary">
                    <i class="fa fa-home"></i> Back to Homepage
                </a>
                <a href="/contact" class="btn-404-secondary">
                    <i class="fa fa-headset text-blue-600"></i> Contact Support
                </a>
            </div>
        </div>

        <!-- Popular Destinations Grid -->
        <div>
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-xl font-bold text-gray-900">Popular Destinations</h3>
                    <p class="text-sm text-gray-500">Quickly find what you need from these popular sections</p>
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <a href="/category/basic" class="quick-link-card group">
                    <div class="quick-link-icon bg-blue-50 text-blue-600 group-hover:bg-blue-600 group-hover:text-white transition">
                        <i class="fa fa-server"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-gray-900 text-sm group-hover:text-blue-600 transition">Web Hosting</h4>
                        <p class="text-xs text-gray-500 mt-0.5">High-speed NVMe cPanel plans</p>
                    </div>
                </a>
                <a href="/category/vps" class="quick-link-card group">
                    <div class="quick-link-icon bg-purple-50 text-purple-600 group-hover:bg-purple-600 group-hover:text-white transition">
                        <i class="fa fa-cloud"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-gray-900 text-sm group-hover:text-purple-600 transition">VPS Hosting</h4>
                        <p class="text-xs text-gray-500 mt-0.5">High-performance BDIX servers</p>
                    </div>
                </a>
                <a href="/offers" class="quick-link-card group">
                    <div class="quick-link-icon bg-red-50 text-red-600 group-hover:bg-red-600 group-hover:text-white transition">
                        <i class="fa fa-tags"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-gray-900 text-sm group-hover:text-red-600 transition">Special Deals</h4>
                        <p class="text-xs text-gray-500 mt-0.5">Exclusive promo discounts</p>
                    </div>
                </a>
                <a href="/blog" class="quick-link-card group">
                    <div class="quick-link-icon bg-emerald-50 text-emerald-600 group-hover:bg-emerald-600 group-hover:text-white transition">
                        <i class="fa fa-newspaper"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-gray-900 text-sm group-hover:text-emerald-600 transition">Tech Blog</h4>
                        <p class="text-xs text-gray-500 mt-0.5">Web tips & server tutorials</p>
                    </div>
                </a>
            </div>
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
