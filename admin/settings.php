<?php
$page_title = 'Settings Hub';
require_once '../config/database.php';
require_once '../includes/functions.php';
checkAdminLogin();
?>
<?php include 'header.php'; ?>

<div class="space-y-6">
    
    <!-- Page Header & Search -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-6 rounded-2xl border border-gray-200/80 shadow-xs">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="p-2 bg-blue-50 text-blue-600 rounded-lg text-sm"><i class="fa-solid fa-sliders"></i></span>
                <h1 class="text-2xl font-bold text-gray-900">Settings & Configuration</h1>
            </div>
            <p class="text-xs text-gray-500">Centralized control center for branding, integrations, SEO, communications, and system security.</p>
        </div>
        <div class="relative w-full sm:w-72">
            <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
            <input type="text" id="settingsSearchInput" onkeyup="filterSettingsCards(this.value)" placeholder="Search any setting..." class="w-full bg-gray-50 border border-gray-200 rounded-xl pl-9 pr-3 py-2 text-xs text-gray-800 focus:bg-white focus:outline-none focus:border-blue-600 transition">
        </div>
    </div>

    <!-- Settings Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4" id="settingsGrid">
        
        <!-- 1. General -->
        <a href="settings-general.php" class="setting-card bg-white p-5 rounded-2xl border border-gray-200/80 shadow-xs hover:shadow-md hover:border-blue-300 transition group flex flex-col justify-between" data-name="general site name tagline currency contact email phone">
            <div>
                <div class="w-11 h-11 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg mb-3 group-hover:scale-110 transition">
                    <i class="fa-solid fa-globe"></i>
                </div>
                <h3 class="font-bold text-sm text-gray-900 group-hover:text-blue-600 transition">General Settings</h3>
                <p class="text-xs text-gray-500 mt-1 line-clamp-2">Site title, description, phone, email address, and currency symbol configuration.</p>
            </div>
            <div class="mt-4 pt-3 border-t border-gray-100 flex items-center justify-between text-xs text-blue-600 font-semibold">
                <span>Configure</span>
                <i class="fa-solid fa-arrow-right text-[10px] group-hover:translate-x-1 transition"></i>
            </div>
        </a>

        <!-- 2. Branding & Logos -->
        <a href="settings-branding.php" class="setting-card bg-white p-5 rounded-2xl border border-gray-200/80 shadow-xs hover:shadow-md hover:border-emerald-300 transition group flex flex-col justify-between" data-name="branding logo favicon dark light theme header footer logo">
            <div>
                <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg mb-3 group-hover:scale-110 transition">
                    <i class="fa-solid fa-palette"></i>
                </div>
                <h3 class="font-bold text-sm text-gray-900 group-hover:text-emerald-600 transition">Logo & Branding</h3>
                <p class="text-xs text-gray-500 mt-1 line-clamp-2">Header logo, footer logo, favicon (.ico/.png), and branding preview.</p>
            </div>
            <div class="mt-4 pt-3 border-t border-gray-100 flex items-center justify-between text-xs text-emerald-600 font-semibold">
                <span>Configure</span>
                <i class="fa-solid fa-arrow-right text-[10px] group-hover:translate-x-1 transition"></i>
            </div>
        </a>

        <!-- 3. Homepage Editor -->
        <a href="settings-homepage.php" class="setting-card bg-white p-5 rounded-2xl border border-gray-200/80 shadow-xs hover:shadow-md hover:border-purple-300 transition group flex flex-col justify-between" data-name="homepage hero features cta sections sort order home banner">
            <div>
                <div class="w-11 h-11 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-lg mb-3 group-hover:scale-110 transition">
                    <i class="fa-solid fa-house-laptop"></i>
                </div>
                <h3 class="font-bold text-sm text-gray-900 group-hover:text-purple-600 transition">Homepage Customizer</h3>
                <p class="text-xs text-gray-500 mt-1 line-clamp-2">Customize hero banner, features grid, promo section, and CTA banners.</p>
            </div>
            <div class="mt-4 pt-3 border-t border-gray-100 flex items-center justify-between text-xs text-purple-600 font-semibold">
                <span>Configure</span>
                <i class="fa-solid fa-arrow-right text-[10px] group-hover:translate-x-1 transition"></i>
            </div>
        </a>

        <!-- 4. WHMCS Integration -->
        <a href="settings-whmcs.php" class="setting-card bg-white p-5 rounded-2xl border border-gray-200/80 shadow-xs hover:shadow-md hover:border-indigo-300 transition group flex flex-col justify-between" data-name="whmcs billing client area cart register login domain check">
            <div>
                <div class="w-11 h-11 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-lg mb-3 group-hover:scale-110 transition">
                    <i class="fa-solid fa-link"></i>
                </div>
                <h3 class="font-bold text-sm text-gray-900 group-hover:text-indigo-600 transition">WHMCS Integration</h3>
                <p class="text-xs text-gray-500 mt-1 line-clamp-2">Client login, shopping cart, registration, and domain lookup WHMCS URLs.</p>
            </div>
            <div class="mt-4 pt-3 border-t border-gray-100 flex items-center justify-between text-xs text-indigo-600 font-semibold">
                <span>Configure</span>
                <i class="fa-solid fa-arrow-right text-[10px] group-hover:translate-x-1 transition"></i>
            </div>
        </a>

        <!-- 5. SMTP Mail Server -->
        <a href="settings-smtp.php" class="setting-card bg-white p-5 rounded-2xl border border-gray-200/80 shadow-xs hover:shadow-md hover:border-amber-300 transition group flex flex-col justify-between" data-name="smtp email mail server gmail mailgun cpanel test email">
            <div>
                <div class="w-11 h-11 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg mb-3 group-hover:scale-110 transition">
                    <i class="fa-solid fa-envelope-circle-check"></i>
                </div>
                <h3 class="font-bold text-sm text-gray-900 group-hover:text-amber-600 transition">SMTP Mail Server</h3>
                <p class="text-xs text-gray-500 mt-1 line-clamp-2">Configure outgoing SMTP host, port, authentication, and dispatch test emails.</p>
            </div>
            <div class="mt-4 pt-3 border-t border-gray-100 flex items-center justify-between text-xs text-amber-600 font-semibold">
                <span>Configure</span>
                <i class="fa-solid fa-arrow-right text-[10px] group-hover:translate-x-1 transition"></i>
            </div>
        </a>

        <!-- 6. Notice Popup & Social FAB -->
        <a href="settings-popup.php" class="setting-card bg-white p-5 rounded-2xl border border-gray-200/80 shadow-xs hover:shadow-md hover:border-yellow-300 transition group flex flex-col justify-between" data-name="popup modal notice banner floating fab whatsapp telegram social">
            <div>
                <div class="w-11 h-11 rounded-xl bg-yellow-50 text-yellow-600 flex items-center justify-center text-lg mb-3 group-hover:scale-110 transition">
                    <i class="fa-solid fa-bell"></i>
                </div>
                <h3 class="font-bold text-sm text-gray-900 group-hover:text-yellow-600 transition">Popup Notice & FAB</h3>
                <p class="text-xs text-gray-500 mt-1 line-clamp-2">Promotional popups, WhatsApp/Telegram floating action buttons.</p>
            </div>
            <div class="mt-4 pt-3 border-t border-gray-100 flex items-center justify-between text-xs text-yellow-600 font-semibold">
                <span>Configure</span>
                <i class="fa-solid fa-arrow-right text-[10px] group-hover:translate-x-1 transition"></i>
            </div>
        </a>

        <!-- 7. SEO & Schemas -->
        <a href="settings-seo.php" class="setting-card bg-white p-5 rounded-2xl border border-gray-200/80 shadow-xs hover:shadow-md hover:border-slate-300 transition group flex flex-col justify-between" data-name="seo meta title description keywords google opengraph sitemap">
            <div>
                <div class="w-11 h-11 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center text-lg mb-3 group-hover:scale-110 transition">
                    <i class="fa-solid fa-magnifying-glass-chart"></i>
                </div>
                <h3 class="font-bold text-sm text-gray-900 group-hover:text-slate-800 transition">Global SEO & Meta</h3>
                <p class="text-xs text-gray-500 mt-1 line-clamp-2">Global meta title, description, keywords, OpenGraph images, and search previews.</p>
            </div>
            <div class="mt-4 pt-3 border-t border-gray-100 flex items-center justify-between text-xs text-slate-700 font-semibold">
                <span>Configure</span>
                <i class="fa-solid fa-arrow-right text-[10px] group-hover:translate-x-1 transition"></i>
            </div>
        </a>

        <!-- 8. Integrations & Chat -->
        <a href="settings-integrations.php" class="setting-card bg-white p-5 rounded-2xl border border-gray-200/80 shadow-xs hover:shadow-md hover:border-teal-300 transition group flex flex-col justify-between" data-name="integrations analytics pixel ga4 tawk crisp onesignal recaptcha">
            <div>
                <div class="w-11 h-11 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center text-lg mb-3 group-hover:scale-110 transition">
                    <i class="fa-solid fa-puzzle-piece"></i>
                </div>
                <h3 class="font-bold text-sm text-gray-900 group-hover:text-teal-600 transition">Integrations & Scripts</h3>
                <p class="text-xs text-gray-500 mt-1 line-clamp-2">Google Analytics 4, FB Pixel, Tawk.to, Crisp live chat, and reCAPTCHA keys.</p>
            </div>
            <div class="mt-4 pt-3 border-t border-gray-100 flex items-center justify-between text-xs text-teal-600 font-semibold">
                <span>Configure</span>
                <i class="fa-solid fa-arrow-right text-[10px] group-hover:translate-x-1 transition"></i>
            </div>
        </a>

        <!-- 9. Security & Access Slug -->
        <a href="settings-security.php" class="setting-card bg-white p-5 rounded-2xl border border-gray-200/80 shadow-xs hover:shadow-md hover:border-rose-300 transition group flex flex-col justify-between" data-name="security access slug secret url login protection lock">
            <div>
                <div class="w-11 h-11 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-lg mb-3 group-hover:scale-110 transition">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <h3 class="font-bold text-sm text-gray-900 group-hover:text-rose-600 transition">Security & Admin Slug</h3>
                <p class="text-xs text-gray-500 mt-1 line-clamp-2">Secret access token parameter, IP access rules, and brute-force defenses.</p>
            </div>
            <div class="mt-4 pt-3 border-t border-gray-100 flex items-center justify-between text-xs text-rose-600 font-semibold">
                <span>Configure</span>
                <i class="fa-solid fa-arrow-right text-[10px] group-hover:translate-x-1 transition"></i>
            </div>
        </a>

        <!-- 10. Maintenance Mode -->
        <a href="settings-maintenance.php" class="setting-card bg-white p-5 rounded-2xl border border-gray-200/80 shadow-xs hover:shadow-md hover:border-orange-300 transition group flex flex-col justify-between" data-name="maintenance mode offline under construction lock">
            <div>
                <div class="w-11 h-11 rounded-xl bg-orange-50 text-orange-600 flex items-center justify-center text-lg mb-3 group-hover:scale-110 transition">
                    <i class="fa-solid fa-screwdriver-wrench"></i>
                </div>
                <h3 class="font-bold text-sm text-gray-900 group-hover:text-orange-600 transition">Maintenance Mode</h3>
                <p class="text-xs text-gray-500 mt-1 line-clamp-2">Toggle site offline for upgrades with customized maintenance message and timer.</p>
            </div>
            <div class="mt-4 pt-3 border-t border-gray-100 flex items-center justify-between text-xs text-orange-600 font-semibold">
                <span>Configure</span>
                <i class="fa-solid fa-arrow-right text-[10px] group-hover:translate-x-1 transition"></i>
            </div>
        </a>

        <!-- 11. Blog Permalinks -->
        <a href="settings-permalinks.php" class="setting-card bg-white p-5 rounded-2xl border border-gray-200/80 shadow-xs hover:shadow-md hover:border-cyan-300 transition group flex flex-col justify-between" data-name="permalinks blog url structure routing slug post_name">
            <div>
                <div class="w-11 h-11 rounded-xl bg-cyan-50 text-cyan-600 flex items-center justify-center text-lg mb-3 group-hover:scale-110 transition">
                    <i class="fa-solid fa-route"></i>
                </div>
                <h3 class="font-bold text-sm text-gray-900 group-hover:text-cyan-600 transition">Blog Permalinks</h3>
                <p class="text-xs text-gray-500 mt-1 line-clamp-2">Configure URL structure format for blog posts and article routing.</p>
            </div>
            <div class="mt-4 pt-3 border-t border-gray-100 flex items-center justify-between text-xs text-cyan-600 font-semibold">
                <span>Configure</span>
                <i class="fa-solid fa-arrow-right text-[10px] group-hover:translate-x-1 transition"></i>
            </div>
        </a>

        <!-- 12. Contact Page -->
        <a href="settings-contact.php" class="setting-card bg-white p-5 rounded-2xl border border-gray-200/80 shadow-xs hover:shadow-md hover:border-pink-300 transition group flex flex-col justify-between" data-name="contact page heading subheading recaptcha inquiry">
            <div>
                <div class="w-11 h-11 rounded-xl bg-pink-50 text-pink-600 flex items-center justify-center text-lg mb-3 group-hover:scale-110 transition">
                    <i class="fa-solid fa-headset"></i>
                </div>
                <h3 class="font-bold text-sm text-gray-900 group-hover:text-pink-600 transition">Contact Page</h3>
                <p class="text-xs text-gray-500 mt-1 line-clamp-2">Contact heading, subtitle text, and form protection options.</p>
            </div>
            <div class="mt-4 pt-3 border-t border-gray-100 flex items-center justify-between text-xs text-pink-600 font-semibold">
                <span>Configure</span>
                <i class="fa-solid fa-arrow-right text-[10px] group-hover:translate-x-1 transition"></i>
            </div>
        </a>

        <!-- 13. Footer Settings -->
        <a href="settings-footer.php" class="setting-card bg-white p-5 rounded-2xl border border-gray-200/80 shadow-xs hover:shadow-md hover:border-blue-300 transition group flex flex-col justify-between" data-name="footer copyright description links">
            <div>
                <div class="w-11 h-11 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg mb-3 group-hover:scale-110 transition">
                    <i class="fa-solid fa-shoe-prints"></i>
                </div>
                <h3 class="font-bold text-sm text-gray-900 group-hover:text-blue-600 transition">Footer Content</h3>
                <p class="text-xs text-gray-500 mt-1 line-clamp-2">Copyright notices, description text, and footer quick links.</p>
            </div>
            <div class="mt-4 pt-3 border-t border-gray-100 flex items-center justify-between text-xs text-blue-600 font-semibold">
                <span>Configure</span>
                <i class="fa-solid fa-arrow-right text-[10px] group-hover:translate-x-1 transition"></i>
            </div>
        </a>

    </div>

</div>

<script>
function filterSettingsCards(q) {
    q = q.trim().toLowerCase();
    document.querySelectorAll('.setting-card').forEach(card => {
        var name = card.dataset.name || '';
        if (!q || name.includes(q)) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
}
</script>

<?php include 'footer.php'; ?>
