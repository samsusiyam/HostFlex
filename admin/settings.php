<?php
$page_title = 'Settings';
require_once '../config/database.php';
require_once '../includes/functions.php';
checkAdminLogin();
checkPermission('settings', 'edit');
?>
<?php include 'header.php'; ?>
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100"><i class="fa fa-cog text-blue-600 dark:text-blue-400 mr-2"></i> Settings</h1>
    <p class="text-gray-500 dark:text-gray-400">Manage your website configuration</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <a href="settings-general.php" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border-l-4 border-blue-500 hover:shadow-lg transition hover:-translate-y-0.5">
        <div class="flex items-center gap-4">
            <div class="bg-blue-100 dark:bg-blue-900/30 p-3 rounded-lg"><i class="fa fa-globe text-blue-600 dark:text-blue-400 text-xl"></i></div>
            <div><h3 class="font-semibold text-gray-800 dark:text-gray-200">General</h3><p class="text-sm text-gray-500 dark:text-gray-400">Site name, tagline, contact info</p></div>
        </div>
    </a>
    <a href="settings-branding.php" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border-l-4 border-green-500 hover:shadow-lg transition hover:-translate-y-0.5">
        <div class="flex items-center gap-4">
            <div class="bg-green-100 dark:bg-green-900/30 p-3 rounded-lg"><i class="fa fa-image text-green-600 dark:text-green-400 text-xl"></i></div>
            <div><h3 class="font-semibold text-gray-800 dark:text-gray-200">Logo & Branding</h3><p class="text-sm text-gray-500 dark:text-gray-400">Header/footer logos, description</p></div>
        </div>
    </a>
    <a href="settings-homepage.php" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border-l-4 border-purple-500 hover:shadow-lg transition hover:-translate-y-0.5">
        <div class="flex items-center gap-4">
            <div class="bg-purple-100 dark:bg-purple-900/30 p-3 rounded-lg"><i class="fa fa-home text-purple-600 dark:text-purple-400 text-xl"></i></div>
            <div><h3 class="font-semibold text-gray-800 dark:text-gray-200">Homepage Editor</h3><p class="text-sm text-gray-500 dark:text-gray-400">Hero, features, CTA sections</p></div>
        </div>
    </a>
    <a href="settings-whmcs.php" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border-l-4 border-indigo-500 hover:shadow-lg transition hover:-translate-y-0.5">
        <div class="flex items-center gap-4">
            <div class="bg-indigo-100 dark:bg-indigo-900/30 p-3 rounded-lg"><i class="fa fa-link text-indigo-600 dark:text-indigo-400 text-xl"></i></div>
            <div><h3 class="font-semibold text-gray-800 dark:text-gray-200">WHMCS Integration</h3><p class="text-sm text-gray-500 dark:text-gray-400">Domain & client area URLs</p></div>
        </div>
    </a>
    <a href="settings-popup.php" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border-l-4 border-yellow-500 hover:shadow-lg transition hover:-translate-y-0.5">
        <div class="flex items-center gap-4">
            <div class="bg-yellow-100 dark:bg-yellow-900/30 p-3 rounded-lg"><i class="fa fa-bell text-yellow-600 dark:text-yellow-400 text-xl"></i></div>
            <div><h3 class="font-semibold text-gray-800 dark:text-gray-200">Popup & Social</h3><p class="text-sm text-gray-500 dark:text-gray-400">Notice, WhatsApp, Telegram, FAB</p></div>
        </div>
    </a>
    <a href="settings-contact.php" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border-l-4 border-pink-500 hover:shadow-lg transition hover:-translate-y-0.5">
        <div class="flex items-center gap-4">
            <div class="bg-pink-100 dark:bg-pink-900/30 p-3 rounded-lg"><i class="fa fa-envelope text-pink-600 dark:text-pink-400 text-xl"></i></div>
            <div><h3 class="font-semibold text-gray-800 dark:text-gray-200">Contact Page</h3><p class="text-sm text-gray-500 dark:text-gray-400">Heading, subheading text</p></div>
        </div>
    </a>
    <a href="settings-seo.php" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border-l-4 border-gray-500 hover:shadow-lg transition hover:-translate-y-0.5">
        <div class="flex items-center gap-4">
            <div class="bg-gray-100 dark:bg-gray-700 p-3 rounded-lg"><i class="fa fa-search text-gray-600 dark:text-gray-400 text-xl"></i></div>
            <div><h3 class="font-semibold text-gray-800 dark:text-gray-200">SEO</h3><p class="text-sm text-gray-500 dark:text-gray-400">Meta keywords, author</p></div>
        </div>
    </a>
    <a href="settings-integrations.php" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border-l-4 border-teal-500 hover:shadow-lg transition hover:-translate-y-0.5">
        <div class="flex items-center gap-4">
            <div class="bg-teal-100 dark:bg-teal-900/30 p-3 rounded-lg"><i class="fa fa-puzzle-piece text-teal-600 dark:text-teal-400 text-xl"></i></div>
            <div><h3 class="font-semibold text-gray-800 dark:text-gray-200">Integrations</h3><p class="text-sm text-gray-500 dark:text-gray-400">Custom code, OneSignal, Tawk.to, Crisp, reCAPTCHA</p></div>
        </div>
    </a>
    <a href="settings-maintenance.php" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border-l-4 border-orange-500 hover:shadow-lg transition hover:-translate-y-0.5">
        <div class="flex items-center gap-4">
            <div class="bg-orange-100 dark:bg-orange-900/30 p-3 rounded-lg"><i class="fa fa-shield-alt text-orange-600 dark:text-orange-400 text-xl"></i></div>
            <div><h3 class="font-semibold text-gray-800 dark:text-gray-200">Maintenance</h3><p class="text-sm text-gray-500 dark:text-gray-400">Maintenance mode, title, message</p></div>
        </div>
    </a>
    <a href="settings-footer.php" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border-l-4 border-cyan-500 hover:shadow-lg transition hover:-translate-y-0.5">
        <div class="flex items-center gap-4">
            <div class="bg-cyan-100 dark:bg-cyan-900/30 p-3 rounded-lg"><i class="fa fa-shoe-prints text-cyan-600 dark:text-cyan-400 text-xl"></i></div>
            <div><h3 class="font-semibold text-gray-800 dark:text-gray-200">Footer</h3><p class="text-sm text-gray-500 dark:text-gray-400">Copyright, footer description</p></div>
        </div>
    </a>
</div>
<?php include 'footer.php'; ?>
