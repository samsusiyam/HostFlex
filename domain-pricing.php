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
<title>Domain Pricing - <?php echo htmlspecialchars($site_name); ?></title>
<?php echo renderSeoTags([
    'title' => 'Domain Name Pricing - ' . $site_name,
    'description' => 'Simple, transparent domain registration, renewal, and transfer pricing with free DNS management and WHOIS privacy.',
    'keywords' => 'domain pricing, buy domain, domain registration, tld prices, cheap domains'
]); ?>
<style>
.btn-primary-sm {
    background-color: #2563eb;
    color: #ffffff;
    padding: 0.4rem 0.9rem;
    border-radius: 0.5rem;
    font-size: 0.75rem;
    font-weight: 700;
    transition: all 0.15s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.25rem;
}
.btn-primary-sm:hover {
    background-color: #1d4ed8;
    color: #ffffff;
}
.btn-light-sm {
    background-color: #f3f4f6;
    color: #374151;
    padding: 0.4rem 0.75rem;
    border-radius: 0.5rem;
    font-size: 0.75rem;
    font-weight: 600;
    transition: all 0.15s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.btn-light-sm:hover {
    background-color: #e5e7eb;
    color: #111827;
}
</style>
</head>
<body class="bg-[#fafafa] text-gray-800">

<?php include "header.php"; ?>
<?php include "contact-btn.php"; ?>

<!-- Minimal Clean Hero -->
<section class="py-12 md:py-16 bg-white border-b border-gray-100">
    <div class="content max-w-4xl mx-auto text-center px-4">
        <h1 class="text-2xl sm:text-3xl md:text-4xl font-extrabold text-gray-900 tracking-tight">
            Domain Name Pricing
        </h1>
        <p class="text-xs sm:text-sm text-gray-500 mt-2 max-w-xl mx-auto leading-relaxed">
            Search, register, and transfer domain names with transparent pricing and no hidden fees.
        </p>

        <!-- Simple Search Bar -->
        <div class="mt-6 max-w-xl mx-auto">
            <form method="post" action="<?php echo htmlspecialchars($whmcs_search_url); ?>" class="flex items-center gap-2 p-1.5 bg-gray-50 border border-gray-200 rounded-xl focus-within:border-blue-500 focus-within:bg-white transition shadow-2xs">
                <div class="flex items-center gap-2 flex-grow px-3">
                    <i class="fa-solid fa-search text-gray-400 text-xs"></i>
                    <input type="text" name="domain" placeholder="Search domain (e.g. mywebsite.com)..." required class="w-full text-xs sm:text-sm bg-transparent border-none outline-none text-gray-900 placeholder:text-gray-400 font-medium">
                </div>
                <button type="submit" class="btn-primary-sm shrink-0 cursor-pointer !py-2.5 !px-5">
                    <span>Search</span>
                </button>
            </form>
        </div>
    </div>
</section>

<!-- Pricing Section -->
<section class="py-10">
    <div class="content max-w-5xl mx-auto px-4 space-y-4">
        
        <!-- Filter Controls -->
        <div class="flex flex-col sm:flex-row items-center justify-between gap-3 bg-white p-3 rounded-xl border border-gray-200">
            <!-- Category Pills -->
            <div class="flex flex-wrap items-center gap-1.5 w-full sm:w-auto" id="domainCategoryTabs">
                <button type="button" onclick="filterDomainCat('all', this)" class="dom-cat-btn active px-3 py-1.5 text-xs font-bold rounded-lg transition bg-blue-600 text-white cursor-pointer">
                    All (<?php echo count($all_tlds); ?>)
                </button>
                <button type="button" onclick="filterDomainCat('Popular', this)" class="dom-cat-btn px-3 py-1.5 text-xs font-bold rounded-lg transition bg-gray-50 text-gray-600 hover:bg-gray-100 cursor-pointer">
                    Popular
                </button>
                <button type="button" onclick="filterDomainCat('Tech', this)" class="dom-cat-btn px-3 py-1.5 text-xs font-bold rounded-lg transition bg-gray-50 text-gray-600 hover:bg-gray-100 cursor-pointer">
                    Tech & Dev
                </button>
                <button type="button" onclick="filterDomainCat('Business', this)" class="dom-cat-btn px-3 py-1.5 text-xs font-bold rounded-lg transition bg-gray-50 text-gray-600 hover:bg-gray-100 cursor-pointer">
                    Business
                </button>
                <button type="button" onclick="filterDomainCat('Country', this)" class="dom-cat-btn px-3 py-1.5 text-xs font-bold rounded-lg transition bg-gray-50 text-gray-600 hover:bg-gray-100 cursor-pointer">
                    Country
                </button>
            </div>

            <!-- Instant Filter Search -->
            <div class="relative w-full sm:w-56">
                <input type="text" id="domainSearchFilter" oninput="applyDomainFilter()" placeholder="Filter TLD (e.g. .com)..." class="w-full pl-8 pr-3 py-1.5 bg-gray-50 border border-gray-200 rounded-lg text-xs font-medium focus:bg-white focus:border-blue-500 focus:outline-none">
                <i class="fa-solid fa-filter absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-[10px]"></i>
            </div>
        </div>

        <!-- Clean Modern Table -->
        <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-2xs">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-gray-50/80 border-b border-gray-200 text-gray-500 font-bold uppercase text-[10px] tracking-wider">
                            <th class="py-3 px-4">TLD</th>
                            <th class="py-3 px-4">Register</th>
                            <th class="py-3 px-4">Renew</th>
                            <th class="py-3 px-4">Transfer</th>
                            <th class="py-3 px-4 text-right">Action</th>
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
                        <tr class="dom-row hover:bg-blue-50/30 transition" data-cat="<?php echo htmlspecialchars($tld['category']); ?>" data-ext="<?php echo htmlspecialchars(strtolower($tld['extension'])); ?>">
                            <!-- TLD Name + Sub-badge -->
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-2">
                                    <span class="font-extrabold text-sm text-gray-900 font-mono"><?php echo htmlspecialchars($tld['extension']); ?></span>
                                    <?php if ($tld['is_popular']): ?>
                                    <span class="text-[9px] font-bold text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded border border-amber-100">Popular</span>
                                    <?php endif; ?>
                                    <?php if ($tld['is_promo']): ?>
                                    <span class="text-[9px] font-bold text-rose-700 bg-rose-50 px-1.5 py-0.5 rounded border border-rose-100">Sale</span>
                                    <?php endif; ?>
                                </div>
                            </td>

                            <!-- Register Price -->
                            <td class="py-3.5 px-4">
                                <?php if ($has_promo): ?>
                                <div class="flex items-center gap-1.5">
                                    <span class="text-xs text-gray-400 font-semibold line-through"><?php echo $currency_symbol; ?><?php echo $reg_conv; ?></span>
                                    <span class="text-sm font-extrabold text-blue-600"><?php echo $currency_symbol; ?><?php echo $promo_conv; ?></span>
                                </div>
                                <?php else: ?>
                                <span class="text-sm font-extrabold text-gray-900"><?php echo $currency_symbol; ?><?php echo $reg_conv; ?></span>
                                <?php endif; ?>
                            </td>

                            <!-- Renew Price -->
                            <td class="py-3.5 px-4">
                                <span class="text-xs font-semibold text-gray-600"><?php echo $currency_symbol; ?><?php echo $ren_conv; ?> <span class="text-[10px] text-gray-400 font-normal">/yr</span></span>
                            </td>

                            <!-- Transfer Price -->
                            <td class="py-3.5 px-4">
                                <span class="text-xs font-semibold text-gray-600"><?php echo $currency_symbol; ?><?php echo $tra_conv; ?></span>
                            </td>

                            <!-- Actions -->
                            <td class="py-3.5 px-4 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="<?php echo htmlspecialchars($whmcs_tra_url); ?>" class="btn-light-sm">
                                        Transfer
                                    </a>
                                    <a href="<?php echo htmlspecialchars($whmcs_reg_url); ?>" class="btn-primary-sm">
                                        Register
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <tr>
                            <td colspan="5" class="py-8 text-center text-gray-400 text-xs">
                                No active domain extensions found.
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<!-- Simple Feature List -->
<section class="py-10 bg-white border-t border-gray-100">
    <div class="content max-w-5xl mx-auto px-4">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 text-center sm:text-left">
            <div class="p-4 rounded-xl bg-gray-50 border border-gray-100 space-y-1.5">
                <div class="text-blue-600 text-base mb-1"><i class="fa-solid fa-shield-halved"></i></div>
                <h3 class="text-xs font-bold text-gray-900">Free WHOIS Privacy</h3>
                <p class="text-[11px] text-gray-500">Protect your personal contact details from public lookups and spam.</p>
            </div>
            <div class="p-4 rounded-xl bg-gray-50 border border-gray-100 space-y-1.5">
                <div class="text-blue-600 text-base mb-1"><i class="fa-solid fa-bolt"></i></div>
                <h3 class="text-xs font-bold text-gray-900">DNS Management</h3>
                <p class="text-[11px] text-gray-500">Full control over A, CNAME, MX, TXT records with fast propagation.</p>
            </div>
            <div class="p-4 rounded-xl bg-gray-50 border border-gray-100 space-y-1.5">
                <div class="text-blue-600 text-base mb-1"><i class="fa-solid fa-headset"></i></div>
                <h3 class="text-xs font-bold text-gray-900">24/7 Expert Support</h3>
                <p class="text-[11px] text-gray-500">Our technical team is ready to help you with domain setup anytime.</p>
            </div>
        </div>
    </div>
</section>

<script>
var activeCat = 'all';

function filterDomainCat(cat, btn) {
    activeCat = cat;
    document.querySelectorAll('.dom-cat-btn').forEach(function(b) {
        b.className = 'dom-cat-btn px-3 py-1.5 text-xs font-bold rounded-lg transition bg-gray-50 text-gray-600 hover:bg-gray-100 cursor-pointer';
    });
    btn.className = 'dom-cat-btn active px-3 py-1.5 text-xs font-bold rounded-lg transition bg-blue-600 text-white cursor-pointer';
    applyDomainFilter();
}

function applyDomainFilter() {
    var search = (document.getElementById('domainSearchFilter').value || '').trim().toLowerCase();
    var rows = document.querySelectorAll('.dom-row');

    rows.forEach(function(r) {
        var cat = r.getAttribute('data-cat') || '';
        var ext = r.getAttribute('data-ext') || '';

        var matchesCat = (activeCat === 'all' || cat === activeCat);
        var matchesSearch = (search === '' || ext.indexOf(search) !== -1 || cat.toLowerCase().indexOf(search) !== -1);

        if (matchesCat && matchesSearch) {
            r.style.display = '';
        } else {
            r.style.display = 'none';
        }
    });
}
</script>

<?php include "footer.php"; ?>
</body>
</html>
