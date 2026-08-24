<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
checkMaintenance();

ensureDomainPricingSchema();

$user_curr = getUserCurrency();
$currency_symbol = $user_curr['symbol'];

$whmcs_reg_url = getSetting('whmcs_domain_register_url') ?: (getSetting('whmcs_url') ? rtrim(getSetting('whmcs_url'), '/') . '/cart.php?a=add&domain=register' : '/contact');
$whmcs_tra_url = getSetting('whmcs_domain_transfer_url') ?: (getSetting('whmcs_url') ? rtrim(getSetting('whmcs_url'), '/') . '/cart.php?a=add&domain=transfer' : '/contact');
$whmcs_search_url = getSetting('whmcs_domain_search_url') ?: (getSetting('whmcs_url') ? rtrim(getSetting('whmcs_url'), '/') . '/domainchecker.php' : $whmcs_reg_url);

// Get all active domain pricing
$tlds_query = mysqli_query($conn, "SELECT * FROM domain_pricing WHERE status = 1 ORDER BY is_featured DESC, sort_order ASC, extension ASC");
$all_tlds = [];
while ($row = mysqli_fetch_assoc($tlds_query)) {
    $all_tlds[] = $row;
}

$site_name = getSetting('site_name') ?: 'Host Nibo';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include "cdnjs.php"; ?>
<title>Domain Pricing & Registration - <?php echo htmlspecialchars($site_name); ?></title>
<?php echo renderSeoTags([
    'title' => 'Domain Name Pricing & TLD Registration - ' . $site_name,
    'description' => 'Explore affordable domain registration, renewal, and transfer pricing for .com, .net, .org, .xyz, .ai, .tech, and more with instant setup.',
    'keywords' => 'domain pricing, buy domain, domain registration, cheap domain, tld prices, .com domain, domain transfer'
]); ?>
</head>
<body class="bg-[#fcfcfd] text-gray-800">

<?php include "header.php"; ?>
<?php include "contact-btn.php"; ?>

<!-- Hero Search Banner -->
<section class="relative bg-gradient-to-b from-blue-50/60 via-white to-transparent pt-12 pb-14 border-b border-gray-100">
    <div class="content text-center max-w-4xl mx-auto">
        <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-extrabold bg-blue-100 text-blue-700 mb-4 shadow-2xs">
            <i class="fa-solid fa-globe"></i> Transparent Domain Pricing
        </span>
        <h1 class="text-3xl sm:text-4xl md:text-5xl font-black text-gray-900 tracking-tight leading-tight">
            Find & Register Your <span class="text-blue-600">Dream Domain</span>
        </h1>
        <p class="text-sm sm:text-base text-gray-600 mt-3 max-w-2xl mx-auto leading-relaxed">
            Search hundreds of popular and niche domain extensions with transparent renewal fees, free DNS management, and instant activation.
        </p>

        <!-- Domain Search Box -->
        <div class="mt-8 max-w-2xl mx-auto">
            <form method="post" action="<?php echo htmlspecialchars($whmcs_search_url); ?>" class="flex flex-col sm:flex-row items-center gap-2 p-2 bg-white rounded-2xl border border-gray-200 shadow-xl focus-within:border-blue-500 transition">
                <div class="flex items-center gap-2.5 w-full px-3 py-1">
                    <i class="fa-solid fa-magnifying-glass text-gray-400 text-base"></i>
                    <input type="text" name="domain" placeholder="Enter your desired domain name (e.g. yourbrand.com)..." required class="w-full text-sm font-medium bg-transparent border-none outline-none text-gray-900 placeholder:text-gray-400">
                </div>
                <button type="submit" class="w-full sm:w-auto px-7 py-3.5 text-xs font-extrabold bg-blue-600 hover:bg-blue-700 text-white rounded-xl shadow-xs transition flex items-center justify-center gap-2 shrink-0 cursor-pointer">
                    <span>Search</span>
                    <i class="fa-solid fa-arrow-right text-[11px]"></i>
                </button>
            </form>
        </div>
    </div>
</section>

<!-- Main Pricing Hub Section -->
<section class="py-12">
    <div class="content max-w-6xl mx-auto space-y-6">
        
        <!-- Controls: Category Filter Tabs & Instant Filter Search -->
        <div class="flex flex-col md:flex-row items-center justify-between gap-4 bg-white p-4 rounded-2xl border border-gray-200 shadow-2xs">
            <!-- Filter Tabs -->
            <div class="flex flex-wrap items-center gap-2 w-full md:w-auto" id="categoryFilterTabs">
                <button type="button" onclick="filterCategory('all', this)" class="category-btn active px-4 py-2 text-xs font-extrabold rounded-xl transition bg-blue-600 text-white shadow-2xs cursor-pointer">
                    All TLDs (<?php echo count($all_tlds); ?>)
                </button>
                <button type="button" onclick="filterCategory('Popular', this)" class="category-btn px-4 py-2 text-xs font-extrabold rounded-xl transition bg-gray-50 text-gray-700 hover:bg-gray-100 cursor-pointer">
                    🔥 Popular
                </button>
                <button type="button" onclick="filterCategory('Tech', this)" class="category-btn px-4 py-2 text-xs font-extrabold rounded-xl transition bg-gray-50 text-gray-700 hover:bg-gray-100 cursor-pointer">
                    💻 Tech & Dev
                </button>
                <button type="button" onclick="filterCategory('Business', this)" class="category-btn px-4 py-2 text-xs font-extrabold rounded-xl transition bg-gray-50 text-gray-700 hover:bg-gray-100 cursor-pointer">
                    💼 Business
                </button>
                <button type="button" onclick="filterCategory('Country', this)" class="category-btn px-4 py-2 text-xs font-extrabold rounded-xl transition bg-gray-50 text-gray-700 hover:bg-gray-100 cursor-pointer">
                    🌐 Country (ccTLD)
                </button>
            </div>

            <!-- Instant Search Input -->
            <div class="relative w-full md:w-72">
                <i class="fa-solid fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                <input type="text" id="tldSearchInput" oninput="filterTldTable()" placeholder="Quick filter (e.g. .com, .ai)..." class="w-full pl-9 pr-3.5 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs font-medium focus:bg-white focus:border-blue-500 focus:outline-none transition">
            </div>
        </div>

        <!-- Pricing Table Container -->
        <div class="bg-white border border-gray-200 rounded-2xl shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse" id="domainPricingTable">
                    <thead>
                        <tr class="bg-gray-50/80 border-b border-gray-200 text-gray-500 font-extrabold uppercase text-[10px] tracking-wider">
                            <th class="py-4 px-5">TLD Extension</th>
                            <th class="py-4 px-5">Category</th>
                            <th class="py-4 px-5">Register (1 Year)</th>
                            <th class="py-4 px-5">Renewal Price</th>
                            <th class="py-4 px-5">Transfer Price</th>
                            <th class="py-4 px-5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 font-medium text-gray-700">
                        <?php if (!empty($all_tlds)): ?>
                        <?php foreach ($all_tlds as $tld): 
                            $reg_conv = convertPriceAmount($tld['register_price'], $user_curr);
                            $ren_conv = convertPriceAmount($tld['renew_price'], $user_curr);
                            $tra_conv = convertPriceAmount($tld['transfer_price'], $user_curr);
                            $has_promo = $tld['promo_price'] !== null && $tld['promo_price'] > 0;
                            $promo_conv = $has_promo ? convertPriceAmount($tld['promo_price'], $user_curr) : null;
                        ?>
                        <tr class="tld-row hover:bg-blue-50/40 transition" data-category="<?php echo htmlspecialchars($tld['category']); ?>" data-extension="<?php echo htmlspecialchars(strtolower($tld['extension'])); ?>">
                            <!-- TLD Extension + Badges -->
                            <td class="py-4 px-5">
                                <div class="flex items-center gap-2">
                                    <span class="font-mono text-base font-black text-blue-600 bg-blue-50 px-2.5 py-1 rounded-xl border border-blue-100"><?php echo htmlspecialchars($tld['extension']); ?></span>
                                    <?php if ($tld['is_popular']): ?>
                                    <span class="px-2 py-0.5 text-[10px] font-extrabold bg-amber-100 text-amber-800 rounded-md">🔥 Hot</span>
                                    <?php endif; ?>
                                    <?php if ($tld['is_promo']): ?>
                                    <span class="px-2 py-0.5 text-[10px] font-extrabold bg-rose-100 text-rose-700 rounded-md">Promo</span>
                                    <?php endif; ?>
                                </div>
                            </td>

                            <!-- Category -->
                            <td class="py-4 px-5">
                                <span class="px-2.5 py-1 bg-gray-100 text-gray-700 text-[11px] font-bold rounded-lg"><?php echo htmlspecialchars($tld['category']); ?></span>
                            </td>

                            <!-- Register Price -->
                            <td class="py-4 px-5">
                                <?php if ($has_promo): ?>
                                <div class="flex items-center gap-1.5">
                                    <span class="text-xs text-gray-400 font-bold line-through"><?php echo $currency_symbol; ?><?php echo $reg_conv; ?></span>
                                    <span class="text-base font-black text-rose-600"><?php echo $currency_symbol; ?><?php echo $promo_conv; ?></span>
                                    <span class="text-[10px] font-bold text-rose-500 bg-rose-50 px-1 rounded">Sale</span>
                                </div>
                                <?php else: ?>
                                <span class="text-base font-black text-gray-900"><?php echo $currency_symbol; ?><?php echo $reg_conv; ?></span>
                                <?php endif; ?>
                            </td>

                            <!-- Renewal Price -->
                            <td class="py-4 px-5">
                                <span class="text-xs font-bold text-gray-700"><?php echo $currency_symbol; ?><?php echo $ren_conv; ?> <span class="text-gray-400 text-[10px] font-normal">/yr</span></span>
                            </td>

                            <!-- Transfer Price -->
                            <td class="py-4 px-5">
                                <span class="text-xs font-bold text-gray-700"><?php echo $currency_symbol; ?><?php echo $tra_conv; ?></span>
                            </td>

                            <!-- Actions -->
                            <td class="py-4 px-5 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="<?php echo htmlspecialchars($whmcs_tra_url); ?>" class="px-3 py-1.5 text-xs font-bold bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl transition">
                                        Transfer
                                    </a>
                                    <a href="<?php echo htmlspecialchars($whmcs_reg_url); ?>" class="px-4 py-1.5 text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white rounded-xl shadow-2xs transition flex items-center gap-1.5">
                                        <span>Register</span>
                                        <i class="fa-solid fa-chevron-right text-[9px]"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <tr>
                            <td colspan="6" class="py-12 text-center text-gray-400">
                                <i class="fa-solid fa-globe text-3xl mb-2 text-gray-300 block"></i>
                                No active domain pricing records available right now.
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<!-- Features Included with Every Domain -->
<section class="py-14 bg-white border-t border-gray-100">
    <div class="content max-w-6xl mx-auto">
        <div class="text-center max-w-2xl mx-auto mb-10">
            <h2 class="text-2xl sm:text-3xl font-extrabold text-gray-900">Everything You Need Included Free</h2>
            <p class="text-xs sm:text-sm text-gray-500 mt-2">Every domain registered with us comes packed with industry-leading tools and protection at no hidden charge.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="p-5 rounded-2xl bg-gray-50/80 border border-gray-100 space-y-2.5">
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg font-bold">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <h3 class="text-sm font-extrabold text-gray-900">Free WHOIS Privacy</h3>
                <p class="text-xs text-gray-600 leading-relaxed">Keep your personal contact info, email, and phone number protected from spammers and marketers forever.</p>
            </div>

            <div class="p-5 rounded-2xl bg-gray-50/80 border border-gray-100 space-y-2.5">
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg font-bold">
                    <i class="fa-solid fa-bolt"></i>
                </div>
                <h3 class="text-sm font-extrabold text-gray-900">Instant DNS Management</h3>
                <p class="text-xs text-gray-600 leading-relaxed">Easily create and manage A, CNAME, MX, TXT, and SRV records in real-time with zero propagation lag.</p>
            </div>

            <div class="p-5 rounded-2xl bg-gray-50/80 border border-gray-100 space-y-2.5">
                <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-lg font-bold">
                    <i class="fa-solid fa-lock"></i>
                </div>
                <h3 class="text-sm font-extrabold text-gray-900">Domain Theft Protection</h3>
                <p class="text-xs text-gray-600 leading-relaxed">Lock your domain to prevent unauthorized transfers and secure it with two-factor authentication.</p>
            </div>

            <div class="p-5 rounded-2xl bg-gray-50/80 border border-gray-100 space-y-2.5">
                <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-lg font-bold">
                    <i class="fa-solid fa-envelope-open-text"></i>
                </div>
                <h3 class="text-sm font-extrabold text-gray-900">Email Forwarding</h3>
                <p class="text-xs text-gray-600 leading-relaxed">Create professional aliases like info@yourbrand.com and forward incoming mail directly to your inbox.</p>
            </div>

            <div class="p-5 rounded-2xl bg-gray-50/80 border border-gray-100 space-y-2.5">
                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg font-bold">
                    <i class="fa-solid fa-arrow-right-arrow-left"></i>
                </div>
                <h3 class="text-sm font-extrabold text-gray-900">Simple Domain Transfer</h3>
                <p class="text-xs text-gray-600 leading-relaxed">Seamlessly transfer existing domains to us without any downtime, plus get +1 year renewal included.</p>
            </div>

            <div class="p-5 rounded-2xl bg-gray-50/80 border border-gray-100 space-y-2.5">
                <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-lg font-bold">
                    <i class="fa-solid fa-headset"></i>
                </div>
                <h3 class="text-sm font-extrabold text-gray-900">24/7 Expert Support</h3>
                <p class="text-xs text-gray-600 leading-relaxed">Our experienced domain support engineers are ready to assist you round the clock via live chat and ticket.</p>
            </div>
        </div>
    </div>
</section>

<!-- Domain FAQ Accordion -->
<section class="py-14 bg-gray-50/60 border-t border-gray-100">
    <div class="content max-w-4xl mx-auto space-y-6">
        <div class="text-center mb-8">
            <h2 class="text-2xl sm:text-3xl font-extrabold text-gray-900">Frequently Asked Questions</h2>
            <p class="text-xs sm:text-sm text-gray-500 mt-1">Everything you need to know about domain registration, renewal, and management.</p>
        </div>

        <div class="space-y-3">
            <details class="group bg-white p-5 rounded-2xl border border-gray-200 shadow-2xs open:border-blue-500 transition">
                <summary class="flex items-center justify-between font-extrabold text-sm text-gray-900 cursor-pointer list-none">
                    <span>How long does it take for my domain to become active?</span>
                    <i class="fa-solid fa-chevron-down text-xs text-gray-400 group-open:rotate-180 transition"></i>
                </summary>
                <p class="text-xs text-gray-600 mt-3 leading-relaxed">
                    Domain registrations are processed instantly right after payment completion. DNS changes and nameserver propagation typically take between a few minutes to 2 hours worldwide.
                </p>
            </details>

            <details class="group bg-white p-5 rounded-2xl border border-gray-200 shadow-2xs open:border-blue-500 transition">
                <summary class="flex items-center justify-between font-extrabold text-sm text-gray-900 cursor-pointer list-none">
                    <span>Can I transfer my existing domain to Host Nibo?</span>
                    <i class="fa-solid fa-chevron-down text-xs text-gray-400 group-open:rotate-180 transition"></i>
                </summary>
                <p class="text-xs text-gray-600 mt-3 leading-relaxed">
                    Yes! Transferring is simple. Unlock your domain at your current registrar, obtain your EPP transfer code, and submit the transfer on our website. Your domain will be extended by 1 full year upon transfer completion.
                </p>
            </details>

            <details class="group bg-white p-5 rounded-2xl border border-gray-200 shadow-2xs open:border-blue-500 transition">
                <summary class="flex items-center justify-between font-extrabold text-sm text-gray-900 cursor-pointer list-none">
                    <span>Will my domain renewal price change unexpectedly?</span>
                    <i class="fa-solid fa-chevron-down text-xs text-gray-400 group-open:rotate-180 transition"></i>
                </summary>
                <p class="text-xs text-gray-600 mt-3 leading-relaxed">
                    We believe in 100% pricing transparency. The standard renewal fees listed on our domain pricing table are clear and upfront with no hidden renewal traps.
                </p>
            </details>
        </div>
    </div>
</section>

<script>
var currentCategory = 'all';

function filterCategory(cat, btn) {
    currentCategory = cat;
    
    // Update active tab styling
    document.querySelectorAll('.category-btn').forEach(function(b) {
        b.className = 'category-btn px-4 py-2 text-xs font-extrabold rounded-xl transition bg-gray-50 text-gray-700 hover:bg-gray-100 cursor-pointer';
    });
    btn.className = 'category-btn active px-4 py-2 text-xs font-extrabold rounded-xl transition bg-blue-600 text-white shadow-2xs cursor-pointer';

    filterTldTable();
}

function filterTldTable() {
    var searchVal = (document.getElementById('tldSearchInput').value || '').trim().toLowerCase();
    var rows = document.querySelectorAll('.tld-row');

    rows.forEach(function(row) {
        var rowCat = row.getAttribute('data-category') || '';
        var rowExt = row.getAttribute('data-extension') || '';

        var matchesCat = (currentCategory === 'all' || rowCat === currentCategory);
        var matchesSearch = (searchVal === '' || rowExt.indexOf(searchVal) !== -1 || rowCat.toLowerCase().indexOf(searchVal) !== -1);

        if (matchesCat && matchesSearch) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}
</script>

<?php include "footer.php"; ?>
</body>
</html>
