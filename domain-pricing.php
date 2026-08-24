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
</head>
<body class="bg-[#f8fafc] text-slate-800 antialiased selection:bg-blue-600 selection:text-white">

<?php include "header.php"; ?>
<?php include "contact-btn.php"; ?>

<!-- Hero Section -->
<section class="bg-white border-b border-slate-200/80 py-12 md:py-16">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 text-center">
        
        <!-- Heading & Subtitle -->
        <h1 class="text-3xl sm:text-4xl md:text-5xl font-black text-slate-900 tracking-tight leading-tight">
            Find the perfect domain for your business
        </h1>
        <p class="text-sm sm:text-base text-slate-600 mt-3 max-w-2xl mx-auto leading-relaxed">
            Search, register, and transfer domains with transparent pricing and no hidden fees.
        </p>

        <!-- Prominent SaaS Domain Search Box -->
        <div class="mt-8 max-w-2xl mx-auto">
            <form method="post" action="<?php echo htmlspecialchars($whmcs_search_url); ?>" class="bg-white border border-slate-300 hover:border-slate-400 focus-within:!border-blue-600 focus-within:ring-4 focus-within:ring-blue-100 rounded-2xl p-2 shadow-sm transition flex flex-col sm:flex-row items-center gap-2">
                <div class="flex items-center gap-3 w-full px-3 py-1">
                    <i class="fa-solid fa-magnifying-glass text-slate-400 text-base shrink-0"></i>
                    <input type="text" name="domain" placeholder="Search your domain name (e.g. brandname.com)..." required class="w-full text-sm sm:text-base font-medium text-slate-900 placeholder:text-slate-400 bg-transparent border-none outline-none">
                </div>
                <button type="submit" class="w-full sm:w-auto px-7 py-3 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-bold text-sm rounded-xl transition shadow-xs flex items-center justify-center gap-2 shrink-0 cursor-pointer">
                    <span>Search</span>
                    <i class="fa-solid fa-arrow-right text-xs"></i>
                </button>
            </form>
        </div>

        <!-- Subtle Supporting Features -->
        <div class="flex flex-wrap items-center justify-center gap-6 sm:gap-8 mt-6 text-xs font-semibold text-slate-500">
            <span class="inline-flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-blue-600"></span>
                <span>Instant domain search</span>
            </span>
            <span class="inline-flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                <span>Transparent pricing</span>
            </span>
            <span class="inline-flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                <span>Easy domain management</span>
            </span>
        </div>

    </div>
</section>

<!-- Pricing Section -->
<section class="py-10 md:py-14">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 space-y-6">
        
        <!-- Filter Navigation & Search Area -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white p-3.5 sm:p-4 rounded-2xl border border-slate-200 shadow-xs">
            
            <!-- Segmented Category Chips -->
            <div class="flex flex-wrap items-center gap-1.5 sm:gap-2 w-full md:w-auto" id="tldFilterTabs">
                <button type="button" onclick="setTldCategory('all', this)" class="tld-tab-btn active px-3.5 py-1.5 sm:px-4 sm:py-2 text-xs font-bold rounded-xl transition bg-slate-900 text-white shadow-xs cursor-pointer">
                    All TLDs (<?php echo count($all_tlds); ?>)
                </button>
                <button type="button" onclick="setTldCategory('Popular', this)" class="tld-tab-btn px-3.5 py-1.5 sm:px-4 sm:py-2 text-xs font-bold rounded-xl transition bg-slate-100 text-slate-700 hover:bg-slate-200 hover:text-slate-900 cursor-pointer">
                    Popular
                </button>
                <button type="button" onclick="setTldCategory('Tech', this)" class="tld-tab-btn px-3.5 py-1.5 sm:px-4 sm:py-2 text-xs font-bold rounded-xl transition bg-slate-100 text-slate-700 hover:bg-slate-200 hover:text-slate-900 cursor-pointer">
                    Tech & Dev
                </button>
                <button type="button" onclick="setTldCategory('Business', this)" class="tld-tab-btn px-3.5 py-1.5 sm:px-4 sm:py-2 text-xs font-bold rounded-xl transition bg-slate-100 text-slate-700 hover:bg-slate-200 hover:text-slate-900 cursor-pointer">
                    Business
                </button>
                <button type="button" onclick="setTldCategory('Country', this)" class="tld-tab-btn px-3.5 py-1.5 sm:px-4 sm:py-2 text-xs font-bold rounded-xl transition bg-slate-100 text-slate-700 hover:bg-slate-200 hover:text-slate-900 cursor-pointer">
                    Country
                </button>
            </div>

            <!-- Search / Filter TLD input -->
            <div class="relative w-full md:w-64">
                <input type="text" id="tldLiveSearch" oninput="runTldFilter()" placeholder="Search / filter TLD..." class="w-full pl-3.5 pr-9 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-900 placeholder:text-slate-400 focus:bg-white focus:border-blue-600 focus:outline-none transition">
                <i class="fa-solid fa-filter absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
            </div>
        </div>

        <!-- Desktop & Tablet Pricing Table Container -->
        <div class="bg-white border border-slate-200 rounded-2xl shadow-xs overflow-hidden">
            
            <!-- Desktop/Tablet View (Hidden on mobile < 640px) -->
            <div class="hidden sm:block overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 font-extrabold uppercase text-[10px] tracking-wider">
                            <th class="py-4 px-6 w-[26%]">TLD</th>
                            <th class="py-4 px-6 w-[24%]">Registration</th>
                            <th class="py-4 px-6 w-[20%]">Renewal</th>
                            <th class="py-4 px-6 w-[15%]">Transfer</th>
                            <th class="py-4 px-6 w-[15%] text-right">Action</th>
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
                        <tr class="tld-table-row hover:bg-slate-50/70 transition-colors" data-category="<?php echo htmlspecialchars($tld['category']); ?>" data-ext="<?php echo htmlspecialchars(strtolower($tld['extension'])); ?>">
                            
                            <!-- TLD Extension & Badges -->
                            <td class="py-4 px-6">
                                <div class="flex items-center gap-2.5">
                                    <span class="font-black text-base text-slate-900 font-mono tracking-tight"><?php echo htmlspecialchars($tld['extension']); ?></span>
                                    <?php if ($tld['is_popular']): ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-black bg-amber-50 text-amber-800 border border-amber-200/80 tracking-wide uppercase">POPULAR</span>
                                    <?php endif; ?>
                                    <?php if ($tld['is_promo']): ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-black bg-emerald-50 text-emerald-800 border border-emerald-200/80 tracking-wide uppercase">SALE</span>
                                    <?php endif; ?>
                                </div>
                            </td>

                            <!-- Registration Price -->
                            <td class="py-4 px-6">
                                <?php if ($has_promo): ?>
                                <div>
                                    <div class="flex items-baseline gap-1.5">
                                        <span class="text-xs text-slate-400 font-semibold line-through"><?php echo $currency_symbol; ?><?php echo $reg_conv; ?></span>
                                        <span class="text-base font-black text-blue-600"><?php echo $currency_symbol; ?><?php echo $promo_conv; ?></span>
                                    </div>
                                    <span class="text-[11px] font-bold text-emerald-600 block mt-0.5">First year promo</span>
                                </div>
                                <?php else: ?>
                                <div>
                                    <span class="text-base font-black text-slate-900"><?php echo $currency_symbol; ?><?php echo $reg_conv; ?></span>
                                    <span class="text-[11px] font-normal text-slate-400 block mt-0.5">1 Year</span>
                                </div>
                                <?php endif; ?>
                            </td>

                            <!-- Renewal Price -->
                            <td class="py-4 px-6">
                                <span class="text-sm font-bold text-slate-700"><?php echo $currency_symbol; ?><?php echo $ren_conv; ?><span class="text-xs font-normal text-slate-400">/year</span></span>
                            </td>

                            <!-- Transfer Price -->
                            <td class="py-4 px-6">
                                <span class="text-sm font-bold text-slate-700"><?php echo $currency_symbol; ?><?php echo $tra_conv; ?></span>
                            </td>

                            <!-- Actions -->
                            <td class="py-4 px-6 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="<?php echo htmlspecialchars($whmcs_tra_url); ?>" class="text-xs font-bold text-slate-600 hover:text-slate-900 hover:bg-slate-100 px-2.5 py-1.5 rounded-lg transition">
                                        Transfer
                                    </a>
                                    <a href="<?php echo htmlspecialchars($whmcs_reg_url); ?>" class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white text-xs font-bold rounded-lg transition shadow-2xs inline-flex items-center gap-1.5">
                                        <span>Register</span>
                                        <i class="fa-solid fa-chevron-right text-[9px]"></i>
                                    </a>
                                </div>
                            </td>

                        </tr>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <tr>
                            <td colspan="5" class="py-12 text-center text-slate-400 font-medium">
                                No active domain pricing records available.
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Mobile Card Layout (Visible only on < 640px) -->
            <div class="sm:hidden divide-y divide-slate-100" id="mobileCardsContainer">
                <?php if (!empty($all_tlds)): ?>
                <?php foreach ($all_tlds as $tld): 
                    $reg_conv = convertPriceAmount($tld['register_price'], $user_curr);
                    $ren_conv = convertPriceAmount($tld['renew_price'], $user_curr);
                    $tra_conv = convertPriceAmount($tld['transfer_price'], $user_curr);
                    $has_promo = $tld['promo_price'] !== null && $tld['promo_price'] > 0;
                    $promo_conv = $has_promo ? convertPriceAmount($tld['promo_price'], $user_curr) : null;
                ?>
                <div class="tld-card-row p-4 space-y-3" data-category="<?php echo htmlspecialchars($tld['category']); ?>" data-ext="<?php echo htmlspecialchars(strtolower($tld['extension'])); ?>">
                    
                    <!-- Top: Extension & Badges -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="font-black text-lg text-slate-900 font-mono"><?php echo htmlspecialchars($tld['extension']); ?></span>
                            <?php if ($tld['is_popular']): ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[9px] font-black bg-amber-50 text-amber-800 border border-amber-200/80 tracking-wide uppercase">POPULAR</span>
                            <?php endif; ?>
                            <?php if ($tld['is_promo']): ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[9px] font-black bg-emerald-50 text-emerald-800 border border-emerald-200/80 tracking-wide uppercase">SALE</span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Register Price in Header -->
                        <div class="text-right">
                            <?php if ($has_promo): ?>
                            <div class="flex items-baseline gap-1 justify-end">
                                <span class="text-xs text-slate-400 font-semibold line-through"><?php echo $currency_symbol; ?><?php echo $reg_conv; ?></span>
                                <span class="text-base font-black text-blue-600"><?php echo $currency_symbol; ?><?php echo $promo_conv; ?></span>
                            </div>
                            <?php else: ?>
                            <span class="text-base font-black text-slate-900"><?php echo $currency_symbol; ?><?php echo $reg_conv; ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Middle: Renewal & Transfer info -->
                    <div class="grid grid-cols-2 gap-2 text-xs bg-slate-50 p-2.5 rounded-xl border border-slate-100">
                        <div>
                            <span class="text-[10px] uppercase font-bold text-slate-400 block">Renewal</span>
                            <span class="font-bold text-slate-700"><?php echo $currency_symbol; ?><?php echo $ren_conv; ?> /yr</span>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase font-bold text-slate-400 block">Transfer</span>
                            <span class="font-bold text-slate-700"><?php echo $currency_symbol; ?><?php echo $tra_conv; ?></span>
                        </div>
                    </div>

                    <!-- Bottom: Action Buttons -->
                    <div class="flex items-center gap-2 pt-1">
                        <a href="<?php echo htmlspecialchars($whmcs_tra_url); ?>" class="w-1/2 py-2 text-center text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-xl transition">
                            Transfer
                        </a>
                        <a href="<?php echo htmlspecialchars($whmcs_reg_url); ?>" class="w-1/2 py-2 text-center text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-2xs transition flex items-center justify-center gap-1.5">
                            <span>Register</span>
                            <i class="fa-solid fa-arrow-right text-[10px]"></i>
                        </a>
                    </div>

                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- No results message -->
            <div id="noResultsNotice" class="hidden py-12 text-center text-slate-400 font-medium text-xs">
                <i class="fa-solid fa-globe text-3xl mb-2 text-slate-300 block"></i>
                No domain extensions match your search criteria.
            </div>

        </div>

    </div>
</section>

<!-- Trust / Benefits Section -->
<section class="py-12 bg-white border-t border-slate-200/80">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            
            <!-- Benefit 1 -->
            <div class="p-6 rounded-2xl bg-[#f8fafc] border border-slate-200/80 space-y-2">
                <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-base font-bold mb-3">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <h3 class="text-sm font-extrabold text-slate-900">Free WHOIS Privacy</h3>
                <p class="text-xs text-slate-600 leading-relaxed">
                    Protect your personal contact details from public lookups and spam at no extra cost.
                </p>
            </div>

            <!-- Benefit 2 -->
            <div class="p-6 rounded-2xl bg-[#f8fafc] border border-slate-200/80 space-y-2">
                <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-base font-bold mb-3">
                    <i class="fa-solid fa-tag"></i>
                </div>
                <h3 class="text-sm font-extrabold text-slate-900">Transparent Pricing</h3>
                <p class="text-xs text-slate-600 leading-relaxed">
                    No hidden fees, surprise price hikes, or unexpected renewal charges down the road.
                </p>
            </div>

            <!-- Benefit 3 -->
            <div class="p-6 rounded-2xl bg-[#f8fafc] border border-slate-200/80 space-y-2">
                <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-base font-bold mb-3">
                    <i class="fa-solid fa-sliders"></i>
                </div>
                <h3 class="text-sm font-extrabold text-slate-900">Easy Domain Management</h3>
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
    
    // Update active state on tab buttons
    document.querySelectorAll('.tld-tab-btn').forEach(function(b) {
        b.className = 'tld-tab-btn px-3.5 py-1.5 sm:px-4 sm:py-2 text-xs font-bold rounded-xl transition bg-slate-100 text-slate-700 hover:bg-slate-200 hover:text-slate-900 cursor-pointer';
    });
    btn.className = 'tld-tab-btn active px-3.5 py-1.5 sm:px-4 sm:py-2 text-xs font-bold rounded-xl transition bg-slate-900 text-white shadow-xs cursor-pointer';
    
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
