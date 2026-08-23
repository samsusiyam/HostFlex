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

<main class="flex-grow flex items-center justify-center py-16 px-4">
    <div class="max-w-4xl mx-auto text-center w-full">
        <!-- 404 Visual Indicator -->
        <div class="inline-flex items-center gap-2 bg-blue-50 border border-blue-200 text-blue-700 text-xs md:text-sm font-bold px-4 py-1.5 rounded-full uppercase tracking-wider mb-6">
            <i class="fa fa-triangle-exclamation"></i> Error 404
        </div>
        
        <h1 class="text-4xl md:text-6xl font-black text-gray-900 mb-4 tracking-tight">
            Page Not Found
        </h1>
        
        <p class="text-gray-600 text-base md:text-lg mb-8 max-w-xl mx-auto leading-relaxed">
            Oops! The page you were looking for doesn't exist, was moved, or is temporarily unavailable. Let's get you back on track!
        </p>

        <!-- CTA Buttons -->
        <div class="flex flex-wrap gap-4 justify-center mb-12">
            <a href="/" class="btn btn-blue px-7 py-3 rounded-xl font-bold shadow-md hover:shadow-lg transition flex items-center gap-2">
                <i class="fa fa-home"></i> Back to Homepage
            </a>
            <a href="/contact" class="btn bg-white border border-gray-300 text-gray-700 px-7 py-3 rounded-xl font-bold hover:bg-gray-50 transition flex items-center gap-2">
                <i class="fa fa-headset text-blue-600"></i> Contact Support
            </a>
        </div>

        <!-- Quick Destination Cards -->
        <div class="border-t border-gray-200 pt-10 text-left">
            <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-5 text-center">Popular Destinations</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <a href="/category/basic" class="quick-link-card">
                    <div class="quick-link-icon bg-blue-100 text-blue-600">
                        <i class="fa fa-server"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-gray-900 text-sm">Web Hosting</h4>
                        <p class="text-xs text-gray-500">Fast & affordable cPanel plans</p>
                    </div>
                </a>
                <a href="/category/vps" class="quick-link-card">
                    <div class="quick-link-icon bg-purple-100 text-purple-600">
                        <i class="fa fa-cloud"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-gray-900 text-sm">VPS Hosting</h4>
                        <p class="text-xs text-gray-500">High-performance BDIX VPS</p>
                    </div>
                </a>
                <a href="/offers" class="quick-link-card">
                    <div class="quick-link-icon bg-red-100 text-red-600">
                        <i class="fa fa-tag"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-gray-900 text-sm">Special Deals</h4>
                        <p class="text-xs text-gray-500">Limited-time promotional discounts</p>
                    </div>
                </a>
                <a href="/blog" class="quick-link-card">
                    <div class="quick-link-icon bg-emerald-100 text-emerald-600">
                        <i class="fa fa-newspaper"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-gray-900 text-sm">Our Blog</h4>
                        <p class="text-xs text-gray-500">Hosting tutorials & tech updates</p>
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
