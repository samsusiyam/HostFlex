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

// Query all active domain pricing
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
<title>Domain Name Pricing - <?php echo htmlspecialchars($site_name); ?></title>
<?php echo renderSeoTags([
    'title' => 'Domain Name Pricing & TLD Registration - ' . $site_name,
    'description' => 'Search, register, and transfer domains with transparent pricing and no hidden fees. Explore prices for .com, .net, .org, .xyz, and hundreds more.',
    'keywords' => 'domain pricing, buy domain, domain registration, domain transfer, tld prices, cheap domains'
]); ?>
<style>
    /* Soft scrollbar for table */
    .overflow-x-auto::-webkit-scrollbar { height: 6px; }
    .overflow-x-auto::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 10px; }
    .overflow-x-auto::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    .overflow-x-auto::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
</style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased selection:bg-blue-600 selection:text-white">

<?php include "header.php"; ?>
<?php include "contact-btn.php"; ?>

<!-- ==================== HERO SECTION ==================== -->
<section class="relative bg-white border-b border-slate-200/70 overflow-hidden">
    <!-- Subtle background gradient -->
    <div class="absolute inset-0 bg-gradient-to-br from-blue-50/40 via-white to-indigo-50/30 pointer-events-none"></div>
    
    <div class="relative max-w-4xl mx-auto px-4 sm:px-6 py-14 md:py-20 text-center">
        
        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-blue-50 border border-blue-100 text-blue-700 text-xs font-semibold mb-5">
            <span class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse"></span>
            Transparent Domain Pricing
        </div>

        <h1 class="text-3xl sm:text-4xl md:text-5xl font-black text-slate-900 tracking-tight leading-tight">
            Find the perfect domain<br class="hidden sm:block"> for your business
        </h1>
        
        <p class="text-sm sm:text-base text-slate-600 mt-4 max-w-2xl mx-auto leading-relaxed">
            Search, register & transfer domains with clear pricing. No hidden fees, ever.
        </p>

        <!-- Search Box -->
        <div class="mt-9 max-w-2xl mx-auto">
            <form method="post" action="<?php echo htmlspecialchars($whmcs_search_url); ?>" 
                  class="group bg-white border border-slate-200 hover:border-slate-300 focus-within:!border-blue-500 focus-within:ring-4 focus-within:ring-blue-100/70 rounded-2xl p-1.5 sm:p-2 shadow-sm transition-all duration-200 flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                
                <div class="flex items-center gap-3 w-full px-3.5 py-2.5 sm:py-1.5">
                    <i class="fa-solid fa-magnifying-glass text-slate-400 text-sm shrink-0 group-focus-within:text-blue-500 transition"></i>
                    <input type="text" 
                           name="domain" 
                           placeholder="Search your domain (e.g. brandname.com)" 
                           required 
                           class="w-full text-sm sm:text-[15px] font-medium text-slate-900 placeholder:text-slate-400 bg-transparent border-none outline-none">
                </div>
                
                <button type="submit" 
                        class="w-full sm:w-auto px-6 py-3 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-bold text-sm rounded-xl transition shadow-sm flex items-center justify-center gap-2 shrink-0 cursor-pointer">
                    <span>Search Domain</span>
                    <i class="fa-solid fa-arrow-right text-xs opacity-90"></i>
                </button>
            </form>
        </div>

        <!-- Trust Points -->
        <div class="flex flex-wrap items-center justify-center gap-x-7 gap-y-3 mt-7 text-xs font-semibold text-slate-500">
            <span class="inline-flex items-center gap-2">
                <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                Instant Search
            </span>
            <span class="inline-flex items-center gap-2">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                Transparent Pricing
            </span>
            <span class="inline-flex items-center gap-2">
                <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                Easy Management
            </span>
        </div>
    </div>
</section>

<!-- ==================== PRICING SECTION ==================== -->
<section class="py-10 md:py-14">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 space-y-5">
        
        <!-- Filter Bar -->
        <div class="bg-white p-3 sm:p-3.5 rounded-2xl border border-slate-200 shadow-sm flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            
            <!-- Category Tabs -->
            <div class="flex flex-wrap items-center gap-1.5" id="tldFilterTabs">
                <button type="button" onclick="setTldCategory('all', this)" 
                        class="tld-tab-btn active px-3.5 py-1.5 sm:px-4 sm:py-2 text-xs font-bold rounded-xl transition bg-slate-900 text-white shadow-sm cursor-pointer">
                    All TLDs (<?php echo count($all_tlds); ?>)
                </button>
                <button type="button" onclick="setTldCategory('Popular', this)" 
                        class="tld-tab-btn px-3.5 py-1.5 sm:px-4 sm:py-2 text-xs font-bold rounded-xl transition bg-slate-100 text-slate-600 hover:bg-slate-200 hover:text-slate-900 cursor-pointer">
                    Popular
                </button>
                <button type="button" onclick="setTldCategory('Tech', this)" 
                        class="tld-tab-btn px-3.5 py-1.5 sm:px-4 sm:py-2 text-xs font-bold rounded-xl transition bg-slate-100 text-slate-600 hover:bg-slate-200 hover:text-slate-900 cursor-pointer">
                    Tech & Dev
                </button>
                <button type="button" onclick="setTldCategory('Business', this)" 
                        class="tld-tab-btn px-3.5 py-1.5 sm:px-4 sm:py-2 text-xs font-bold rounded-xl transition bg-slate-100 text-slate-600 hover:bg-slate-200 hover:text-slate-900 cursor-pointer">
                    Business
                </button>
                <button type="button" onclick="setTldCategory('Country', this)" 
                        class="tld-tab-btn px-3.5 py-1.5 sm:px-4 sm:py-2 text-xs font-bold rounded-xl transition bg-slate-100 text-slate-600 hover:bg-slate-200 hover:text-slate-900 cursor-pointer">
                    Country
                </button>
            </div>

            <!-- Live Search -->
            <div class="relative w-full md:w-60">
                <input type="text" 
                       id="tldLiveSearch" 
                       oninput="runTldFilter()" 
                       placeholder="Filter TLD..." 
                       class="w-full pl-3.5 pr-9 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-900 placeholder:text-slate-400 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition">
                <i class="fa-solid fa-filter absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
            </div>
        </div>

        <!-- Pricing Container -->
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
            
            <!-- Desktop / Tablet Table -->
            <div class="hidden sm:block overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-50/80 border-b border-slate-200 text-slate-500 font-extrabold uppercase text-[10px] tracking-wider">
                            <th class="py-4 px-5 md:px-6 w-[26%]">TLD</th>
                            <th class="py-4 px-5 md:px-6 w-[24%]">Registration</th>
                            <th class="py-4 px-5 md:px-6 w-[20%]">Renewal</th>
                            <th class="py-4 px-5 md:px-6 w-[15%]">Transfer</th>
                            <th class="py-4 px-5 md:px-6 w-[15%] text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700 font-medium" id="desktopTbody">
                        <?php if (!empty($all_tlds)): ?>
                        <?php foreach ($all_tlds as $tld): 
                            $reg_conv = convertPriceAmount($tld['register_price'], $user_curr);
                            $ren_conv = convertPriceAmount($tld['renew_price'], $user_curr);
                            $tra_conv = convertPriceAmount($tld['transfer_price'], $user_curr);
                            $has_promo = $tld['promo_price'] !== null && $tld['promo_price'] > 0;
                            $promo_conv = $has_promo ? convertPriceAmount($tld['promo_price'], $user_curr) : null;
                        ?>
                        <tr class="tld-table-row hover:bg-slate-50/60 transition-colors" 
                            data-category="<?php echo htmlspecialchars($tld['category']); ?>" 
                            data-ext="<?php echo htmlspecialchars(strtolower($tld['extension'])); ?>">
                            
                            <!-- TLD -->
                            <td class="py-4 px-5 md:px-6">
                                <div class="flex items-center gap-2.5">
                                    <span class="font-black text-[15px] text-slate-900 font-mono tracking-tight">
                                        <?php echo htmlspecialchars($tld['extension']); ?>
                                    </span>
                                    <?php if ($tld['is_popular']): ?>
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[9px] font-black bg-amber-50 text-amber-700 border border-amber-200/70 tracking-wide">
                                        POPULAR
                                    </span>
                                    <?php endif; ?>
                                    <?php if ($tld['is_promo']): ?>
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[9px] font-black bg-emerald-50 text-emerald-700 border border-emerald-200/70 tracking-wide">
                                        SALE
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </td>

                            <!-- Registration -->
                            <td class="py-4 px-5 md:px-6">
                                <?php if ($has_promo): ?>
                                <div>
                                    <div class="flex items-baseline gap-1.5">
                                        <span class="text-xs text-slate-400 font-semibold line-through">
                                            <?php echo $currency_symbol; ?><?php echo $reg_conv; ?>
                                        </span>
                                        <span class="text-[15px] font-black text-blue-600">
                                            <?php echo $currency_symbol; ?><?php echo $promo_conv; ?>
                                        </span>
                                    </div>
                                    <span class="text-[10px] font-bold text-emerald-600 block mt-0.5">First year promo</span>
                                </div>
                                <?php else: ?>
                                <div>
                                    <span class="text-[15px] font-black text-slate-900">
                                        <?php echo $currency_symbol; ?><?php echo $reg_conv; ?>
                                    </span>
                                    <span class="text-[10px] font-normal text-slate-400 block mt-0.5">1 Year</span>
                                </div>
                                <?php endif; ?>
                            </td>

                            <!-- Renewal -->
                            <td class="py-4 px-5 md:px-6">
                                <span class="text-sm font-bold text-slate-700">
                                    <?php echo $currency_symbol; ?><?php echo $ren_conv; ?>
                                    <span class="text-xs font-normal text-slate-400">/year</span>
                                </span>
                            </td>

                            <!-- Transfer -->
                            <td class="py-4 px-5 md:px-6">
                                <span class="text-sm font-bold text-slate-700">
                                    <?php echo $currency_symbol; ?><?php echo $tra_conv; ?>
                                </span>
                            </td>

                            <!-- Actions -->
                            <td class="py-4 px-5 md:px-6 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="<?php echo htmlspecialchars($whmcs_tra_url); ?>" 
                                       class="text-xs font-bold text-slate-600 hover:text-slate-900 hover:bg-slate-100 px-2.5 py-1.5 rounded-lg transition">
                                        Transfer
                                    </a>
                                    <a href="<?php echo htmlspecialchars($whmcs_reg_url); ?>" 
                                       class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white text-xs font-bold rounded-lg transition shadow-sm inline-flex items-center gap-1.5 cursor-pointer">
                                        <span>Register</span>
                                        <i class="fa-solid fa-chevron-right text-[9px]"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <tr>
                            <td colspan="5" class="py-16 text-center text-slate-400 font-medium">
                                No active domain pricing records available.
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Mobile Cards -->
            <div class="sm:hidden divide-y divide-slate-100" id="mobileCardsContainer">
                <?php if (!empty($all_tlds)): ?>
                <?php foreach ($all_tlds as $tld): 
                    $reg_conv = convertPriceAmount($tld['register_price'], $user_curr);
                    $ren_conv = convertPriceAmount($tld['renew_price'], $user_curr);
                    $tra_conv = convertPriceAmount($tld['transfer_price'], $user_curr);
                    $has_promo = $tld['promo_price'] !== null && $tld['promo_price'] > 0;
                    $promo_conv = $has_promo ? convertPriceAmount($tld['promo_price'], $user_curr) : null;
                ?>
                <div class="tld-card-row p-4 space-y-3.5" 
                     data-category="<?php echo htmlspecialchars($tld['category']); ?>" 
                     data-ext="<?php echo htmlspecialchars(strtolower($tld['extension'])); ?>">
                    
                    <!-- Header -->
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-black text-lg text-slate-900 font-mono">
                                <?php echo htmlspecialchars($tld['extension']); ?>
                            </span>
                            <?php if ($tld['is_popular']): ?>
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[9px] font-black bg-amber-50 text-amber-700 border border-amber-200/70">
                                POPULAR
                            </span>
                            <?php endif; ?>
                            <?php if ($tld['is_promo']): ?>
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[9px] font-black bg-emerald-50 text-emerald-700 border border-emerald-200/70">
                                SALE
                            </span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="text-right shrink-0">
                            <?php if ($has_promo): ?>
                            <div class="flex items-baseline gap-1 justify-end">
                                <span class="text-xs text-slate-400 font-semibold line-through">
                                    <?php echo $currency_symbol; ?><?php echo $reg_conv; ?>
                                </span>
                                <span class="text-base font-black text-blue-600">
                                    <?php echo $currency_symbol; ?><?php echo $promo_conv; ?>
                                </span>
                            </div>
                            <?php else: ?>
                            <span class="text-base font-black text-slate-900">
                                <?php echo $currency_symbol; ?><?php echo $reg_conv; ?>
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Price Info -->
                    <div class="grid grid-cols-2 gap-2 text-xs bg-slate-50 p-3 rounded-xl border border-slate-100">
                        <div>
                            <span class="text-[10px] uppercase font-bold text-slate-400 block mb-0.5">Renewal</span>
                            <span class="font-bold text-slate-700">
                                <?php echo $currency_symbol; ?><?php echo $ren_conv; ?> /yr
                            </span>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase font-bold text-slate-400 block mb-0.5">Transfer</span>
                            <span class="font-bold text-slate-700">
                                <?php echo $currency_symbol; ?><?php echo $tra_conv; ?>
                            </span>
                        </div>
                    </div>

                    <!-- Buttons -->
                    <div class="flex items-center gap-2">
                        <a href="<?php echo htmlspecialchars($whmcs_tra_url); ?>" 
                           class="w-1/2 py-2.5 text-center text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-xl transition">
                            Transfer
                        </a>
                        <a href="<?php echo htmlspecialchars($whmcs_reg_url); ?>" 
                           class="w-1/2 py-2.5 text-center text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-sm transition flex items-center justify-center gap-1.5 cursor-pointer">
                            <span>Register</span>
                            <i class="fa-solid fa-arrow-right text-[10px]"></i>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- No Results -->
            <div id="noResultsNotice" class="hidden py-16 text-center text-slate-400 font-medium text-sm">
                <i class="fa-solid fa-globe text-4xl mb-3 text-slate-300 block"></i>
                No domain extensions match your search.
            </div>
        </div>
    </div>
</section>

<!-- ==================== BENEFITS SECTION ==================== -->
<section class="py-12 md:py-16 bg-white border-t border-slate-200/70">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            
            <div class="p-6 rounded-2xl bg-slate-50 border border-slate-200/80 hover:border-blue-200 hover:shadow-sm transition-all duration-200">
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg mb-4">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <h3 class="text-sm font-extrabold text-slate-900 mb-1.5">Free WHOIS Privacy</h3>
                <p class="text-xs text-slate-600 leading-relaxed">
                    Protect your personal contact details from public lookups and spam at no extra cost.
                </p>
            </div>

            <div class="p-6 rounded-2xl bg-slate-50 border border-slate-200/80 hover:border-emerald-200 hover:shadow-sm transition-all duration-200">
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg mb-4">
                    <i class="fa-solid fa-tag"></i>
                </div>
                <h3 class="text-sm font-extrabold text-slate-900 mb-1.5">Transparent Pricing</h3>
                <p class="text-xs text-slate-600 leading-relaxed">
                    No hidden fees, surprise price hikes, or unexpected renewal charges down the road.
                </p>
            </div>

            <div class="p-6 rounded-2xl bg-slate-50 border border-slate-200/80 hover:border-indigo-200 hover:shadow-sm transition-all duration-200">
                <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-lg mb-4">
                    <i class="fa-solid fa-sliders"></i>
                </div>
                <h3 class="text-sm font-extrabold text-slate-900 mb-1.5">Easy Domain Management</h3>
                <p class="text-xs text-slate-600 leading-relaxed">
                    Full DNS control, nameservers, transfers, and email forwarding from one simple dashboard.
                </p>
            </div>

        </div>
    </div>
</section>

<script>
var activeCategory = 'all';

function setTldCategory(cat, btn) {
    activeCategory = cat;
    
    document.querySelectorAll('.tld-tab-btn').forEach(function(b) {
        b.className = 'tld-tab-btn px-3.5 py-1.5 sm:px-4 sm:py-2 text-xs font-bold rounded-xl transition bg-slate-100 text-slate-600 hover:bg-slate-200 hover:text-slate-900 cursor-pointer';
    });
    btn.className = 'tld-tab-btn active px-3.5 py-1.5 sm:px-4 sm:py-2 text-xs font-bold rounded-xl transition bg-slate-900 text-white shadow-sm cursor-pointer';
    
    runTldFilter();
}

function runTldFilter() {
    var search = (document.getElementById('tldLiveSearch').value || '').trim().toLowerCase();
    
    var desktopRows = document.querySelectorAll('.tld-table-row');
    var mobileCards = document.querySelectorAll('.tld-card-row');
    var visibleCount = 0;

    desktopRows.forEach(function(row) {
        var cat = row.getAttribute('data-category') || '';
        var ext = row.getAttribute('data-ext') || '';

        var matchesCat = (activeCategory === 'all' || cat === activeCategory);
        var matchesSearch = (search === '' || ext.indexOf(search) !== -1 || cat.toLowerCase().indexOf(search) !== -1);

        if (matchesCat && matchesSearch) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    mobileCards.forEach(function(card) {
        var cat = card.getAttribute('data-category') || '';
        var ext = card.getAttribute('data-ext') || '';

        var matchesCat = (activeCategory === 'all' || cat === activeCategory);
        var matchesSearch = (search === '' || ext.indexOf(search) !== -1 || cat.toLowerCase().indexOf(search) !== -1);

        if (matchesCat && matchesSearch) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });

    var notice = document.getElementById('noResultsNotice');
    if (notice) {
        if (visibleCount === 0 && (desktopRows.length > 0 || mobileCards.length > 0)) {
            notice.classList.remove('hidden');
        } else {
            notice.classList.add('hidden');
        }
    }
}
</script>

<?php include "footer.php"; ?>
</body>
</html>
