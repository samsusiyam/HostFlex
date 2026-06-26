<?php
$page_title = 'Settings';
require_once '../config/database.php';
require_once '../includes/functions.php';
checkAdminLogin();
checkPermission('settings', 'edit');
?>
<?php include 'header.php'; ?>
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800"><i class="fa fa-cog text-blue-600 mr-2"></i> Settings</h1>
    <p class="text-gray-500">Manage your website configuration</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <a href="settings-general.php" class="bg-white rounded-lg shadow p-6 border-l-4 border-blue-500 hover:shadow-lg transition hover:-translate-y-0.5">
        <div class="flex items-center gap-4">
            <div class="bg-blue-100 p-3 rounded-lg"><i class="fa fa-globe text-blue-600 text-xl"></i></div>
            <div><h3 class="font-semibold text-gray-800">General</h3><p class="text-sm text-gray-500">Site name, tagline, contact info</p></div>
        </div>
    </a>
    <a href="settings-branding.php" class="bg-white rounded-lg shadow p-6 border-l-4 border-green-500 hover:shadow-lg transition hover:-translate-y-0.5">
        <div class="flex items-center gap-4">
            <div class="bg-green-100 p-3 rounded-lg"><i class="fa fa-image text-green-600 text-xl"></i></div>
            <div><h3 class="font-semibold text-gray-800">Logo & Branding</h3><p class="text-sm text-gray-500">Header/footer logos, description</p></div>
        </div>
    </a>
    <a href="settings-homepage.php" class="bg-white rounded-lg shadow p-6 border-l-4 border-purple-500 hover:shadow-lg transition hover:-translate-y-0.5">
        <div class="flex items-center gap-4">
            <div class="bg-purple-100 p-3 rounded-lg"><i class="fa fa-home text-purple-600 text-xl"></i></div>
            <div><h3 class="font-semibold text-gray-800">Homepage Editor</h3><p class="text-sm text-gray-500">Hero, features, CTA sections</p></div>
        </div>
    </a>
    <a href="settings-whmcs.php" class="bg-white rounded-lg shadow p-6 border-l-4 border-indigo-500 hover:shadow-lg transition hover:-translate-y-0.5">
        <div class="flex items-center gap-4">
            <div class="bg-indigo-100 p-3 rounded-lg"><i class="fa fa-link text-indigo-600 text-xl"></i></div>
            <div><h3 class="font-semibold text-gray-800">WHMCS Integration</h3><p class="text-sm text-gray-500">Domain & client area URLs</p></div>
        </div>
    </a>
    <a href="settings-popup.php" class="bg-white rounded-lg shadow p-6 border-l-4 border-yellow-500 hover:shadow-lg transition hover:-translate-y-0.5">
        <div class="flex items-center gap-4">
            <div class="bg-yellow-100 p-3 rounded-lg"><i class="fa fa-bell text-yellow-600 text-xl"></i></div>
            <div><h3 class="font-semibold text-gray-800">Popup & Social</h3><p class="text-sm text-gray-500">Notice, WhatsApp, Telegram, FAB</p></div>
        </div>
    </a>
    <a href="settings-contact.php" class="bg-white rounded-lg shadow p-6 border-l-4 border-pink-500 hover:shadow-lg transition hover:-translate-y-0.5">
        <div class="flex items-center gap-4">
            <div class="bg-pink-100 p-3 rounded-lg"><i class="fa fa-envelope text-pink-600 text-xl"></i></div>
            <div><h3 class="font-semibold text-gray-800">Contact Page</h3><p class="text-sm text-gray-500">Heading, subheading text</p></div>
        </div>
    </a>
    <a href="settings-seo.php" class="bg-white rounded-lg shadow p-6 border-l-4 border-gray-500 hover:shadow-lg transition hover:-translate-y-0.5">
        <div class="flex items-center gap-4">
            <div class="bg-gray-100 p-3 rounded-lg"><i class="fa fa-search text-gray-600 text-xl"></i></div>
            <div><h3 class="font-semibold text-gray-800">SEO</h3><p class="text-sm text-gray-500">Meta keywords, author</p></div>
        </div>
    </a>
    <a href="settings-integrations.php" class="bg-white rounded-lg shadow p-6 border-l-4 border-teal-500 hover:shadow-lg transition hover:-translate-y-0.5">
        <div class="flex items-center gap-4">
            <div class="bg-teal-100 p-3 rounded-lg"><i class="fa fa-puzzle-piece text-teal-600 text-xl"></i></div>
            <div><h3 class="font-semibold text-gray-800">Integrations</h3><p class="text-sm text-gray-500">Custom code, OneSignal, Tawk.to, Crisp, reCAPTCHA</p></div>
        </div>
    </a>
    <a href="settings-maintenance.php" class="bg-white rounded-lg shadow p-6 border-l-4 border-orange-500 hover:shadow-lg transition hover:-translate-y-0.5">
        <div class="flex items-center gap-4">
            <div class="bg-orange-100 p-3 rounded-lg"><i class="fa fa-shield-alt text-orange-600 text-xl"></i></div>
            <div><h3 class="font-semibold text-gray-800">Maintenance</h3><p class="text-sm text-gray-500">Maintenance mode, title, message</p></div>
        </div>
    </a>
    <a href="settings-footer.php" class="bg-white rounded-lg shadow p-6 border-l-4 border-cyan-500 hover:shadow-lg transition hover:-translate-y-0.5">
        <div class="flex items-center gap-4">
            <div class="bg-cyan-100 p-3 rounded-lg"><i class="fa fa-shoe-prints text-cyan-600 text-xl"></i></div>
            <div><h3 class="font-semibold text-gray-800">Footer</h3><p class="text-sm text-gray-500">Copyright, footer description</p></div>
        </div>
    </a>
</div>
<?php include 'footer.php'; ?>
