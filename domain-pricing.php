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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Domain Name Pricing - <?php echo htmlspecialchars($site_name); ?></title>
    <?php include "cdnjs.php"; ?>
    <?php echo renderSeoTags([
        'title' => 'Domain Name Pricing & TLD Registration - ' . $site_name,
        'description' => 'Search, register & transfer domains with clear pricing. No hidden fees, ever.',
        'keywords' => 'domain pricing, buy domain, domain registration, domain transfer, tld prices, cheap domains'
    ]); ?>
    
    <style>
        .domain-page-wrap {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f8fafc;
            color: #1e293b;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }

        /* ========== HERO ========== */
        .hero {
            background: white;
            border-bottom: 1px solid #e2e8f0;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(239, 246, 255, 0.5) 0%, white 50%, rgba(238, 242, 255, 0.3) 100%);
            pointer-events: none;
        }

        .hero-inner {
            position: relative;
            max-width: 900px;
            margin: 0 auto;
            padding: 56px 20px;
            text-align: center;
        }

        .badge-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #eff6ff;
            border: 1px solid #dbeafe;
            color: #1d4ed8;
            font-size: 12px;
            font-weight: 600;
            padding: 6px 14px;
            border-radius: 999px;
            margin-bottom: 20px;
        }

        .badge-dot {
            width: 6px;
            height: 6px;
            background: #3b82f6;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .hero h1 {
            font-size: 2.25rem;
            font-weight: 900;
            color: #0f172a;
            letter-spacing: -0.03em;
            line-height: 1.2;
            margin-bottom: 16px;
        }

        .hero p {
            font-size: 15px;
            color: #64748b;
            max-width: 540px;
            margin: 0 auto 36px;
        }

        /* Search Box */
        .search-form {
            display: flex;
            flex-direction: column;
            gap: 10px;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 8px;
            max-width: 560px;
            margin: 0 auto;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            transition: all 0.2s;
        }

        .search-form:focus-within {
            border-color: #3b82f6;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.12);
        }

        .search-input-wrap {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
            flex: 1;
        }

        .search-input-wrap i {
            color: #94a3b8;
            font-size: 15px;
        }

        .search-form input {
            width: 100%;
            border: none;
            outline: none;
            font-size: 15px;
            font-weight: 500;
            color: #0f172a;
            background: transparent;
        }

        .search-form input::placeholder {
            color: #94a3b8;
        }

        .search-btn {
            background: #2563eb;
            color: white;
            border: none;
            font-weight: 700;
            font-size: 14px;
            padding: 14px 24px;
            border-radius: 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: background 0.2s;
        }

        .search-btn:hover {
            background: #1d4ed8;
        }

        .trust-points {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px 28px;
            margin-top: 28px;
            font-size: 12px;
            font-weight: 600;
            color: #64748b;
        }

        .trust-points span {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .trust-points .dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
        }

        .dot-blue { background: #3b82f6; }
        .dot-green { background: #10b981; }
        .dot-indigo { background: #6366f1; }

        /* ========== PRICING SECTION ========== */
        .pricing-section {
            padding: 40px 0 56px;
        }

        .pricing-container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 0 16px;
        }

        /* Filter Bar */
        .filter-bar {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 14px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 20px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.04);
        }

        .tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }

        .tab-btn {
            padding: 8px 16px;
            font-size: 12px;
            font-weight: 700;
            border: none;
            border-radius: 10px;
            background: #f1f5f9;
            color: #475569;
            cursor: pointer;
            transition: all 0.2s;
        }

        .tab-btn:hover {
            background: #e2e8f0;
            color: #0f172a;
        }

        .tab-btn.active {
            background: #0f172a;
            color: white;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }

        .filter-search {
            position: relative;
            width: 100%;
        }

        .filter-search input {
            width: 100%;
            padding: 10px 36px 10px 14px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 500;
            outline: none;
            transition: all 0.2s;
        }

        .filter-search input:focus {
            background: white;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .filter-search i {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 12px;
            pointer-events: none;
        }

        /* Table Container */
        .table-wrap {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 1px 2px rgba(0,0,0,0.04);
        }

        /* Desktop Table */
        .desktop-table {
            display: none;
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .desktop-table thead {
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }

        .desktop-table th {
            padding: 16px 20px;
            text-align: left;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
        }

        .desktop-table th:last-child {
            text-align: right;
        }

        .desktop-table td {
            padding: 16px 20px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }

        .desktop-table tbody tr:hover {
            background: #f8fafc;
        }

        .desktop-table tbody tr:last-child td {
            border-bottom: none;
        }

        .tld-name {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 15px;
            font-weight: 900;
            color: #0f172a;
        }

        .badge-popular {
            display: inline-flex;
            padding: 2px 7px;
            font-size: 9px;
            font-weight: 800;
            background: #fffbeb;
            color: #b45309;
            border: 1px solid #fde68a;
            border-radius: 6px;
            letter-spacing: 0.03em;
            margin-left: 8px;
        }

        .badge-sale {
            display: inline-flex;
            padding: 2px 7px;
            font-size: 9px;
            font-weight: 800;
            background: #ecfdf5;
            color: #047857;
            border: 1px solid #a7f3d0;
            border-radius: 6px;
            letter-spacing: 0.03em;
            margin-left: 6px;
        }

        .price-old {
            font-size: 12px;
            color: #94a3b8;
            text-decoration: line-through;
            font-weight: 600;
            margin-right: 6px;
        }

        .price-new {
            font-size: 15px;
            font-weight: 900;
            color: #2563eb;
        }

        .price-normal {
            font-size: 15px;
            font-weight: 900;
            color: #0f172a;
        }

        .price-note {
            font-size: 10px;
            color: #10b981;
            font-weight: 700;
            display: block;
            margin-top: 2px;
        }

        .price-sub {
            font-size: 10px;
            color: #94a3b8;
            display: block;
            margin-top: 2px;
        }

        .price-text {
            font-size: 14px;
            font-weight: 700;
            color: #334155;
        }

        .price-text span {
            font-size: 12px;
            font-weight: 400;
            color: #94a3b8;
        }

        .actions {
            display: flex;
            justify-content: flex-end;
            gap: 6px;
        }

        .btn-transfer {
            font-size: 12px;
            font-weight: 700;
            color: #475569;
            padding: 7px 12px;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.15s;
        }

        .btn-transfer:hover {
            background: #f1f5f9;
            color: #0f172a;
        }

        .btn-register {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #2563eb;
            color: white;
            font-size: 12px;
            font-weight: 700;
            padding: 7px 14px;
            border-radius: 8px;
            text-decoration: none;
            transition: background 0.15s;
        }

        .btn-register:hover {
            background: #1d4ed8;
        }

        /* Mobile Cards */
        .mobile-cards {
            display: block;
        }

        .mobile-card {
            padding: 18px 16px;
            border-bottom: 1px solid #f1f5f9;
        }

        .mobile-card:last-child {
            border-bottom: none;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 14px;
        }

        .card-tld {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 6px;
        }

        .card-tld .tld-name {
            font-size: 17px;
        }

        .card-price {
            text-align: right;
            flex-shrink: 0;
        }

        .card-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            background: #f8fafc;
            border: 1px solid #f1f5f9;
            border-radius: 12px;
            padding: 12px;
            margin-bottom: 14px;
            font-size: 13px;
        }

        .card-info label {
            display: block;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            color: #94a3b8;
            margin-bottom: 2px;
        }

        .card-info span {
            font-weight: 700;
            color: #334155;
        }

        .card-actions {
            display: flex;
            gap: 8px;
        }

        .card-actions a {
            flex: 1;
            text-align: center;
            padding: 11px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.15s;
        }

        .card-actions .transfer {
            background: #f1f5f9;
            color: #334155;
        }

        .card-actions .transfer:hover {
            background: #e2e8f0;
        }

        .card-actions .register {
            background: #2563eb;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .card-actions .register:hover {
            background: #1d4ed8;
        }

        /* No Results */
        .no-results {
            display: none;
            padding: 60px 20px;
            text-align: center;
            color: #94a3b8;
            font-size: 14px;
        }

        .no-results i {
            font-size: 36px;
            color: #cbd5e1;
            margin-bottom: 12px;
            display: block;
        }

        /* ========== BENEFITS ========== */
        .benefits {
            background: white;
            border-top: 1px solid #e2e8f0;
            padding: 48px 0;
        }

        .benefits-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 16px;
        }

        .benefit-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 24px;
            transition: all 0.2s;
        }

        .benefit-card:hover {
            border-color: #bfdbfe;
            box-shadow: 0 4px 12px rgba(0,0,0,0.04);
        }

        .benefit-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            margin-bottom: 16px;
        }

        .icon-blue { background: #eff6ff; color: #2563eb; }
        .icon-green { background: #ecfdf5; color: #059669; }
        .icon-indigo { background: #eef2ff; color: #4f46e5; }

        .benefit-card h3 {
            font-size: 14px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 6px;
        }

        .benefit-card p {
            font-size: 13px;
            color: #64748b;
            line-height: 1.55;
        }

        /* ========== RESPONSIVE ========== */
        @media (min-width: 640px) {
            .hero h1 {
                font-size: 2.75rem;
            }

            .search-form {
                flex-direction: row;
                align-items: center;
                padding: 6px;
            }

            .search-btn {
                width: auto;
                padding: 12px 24px;
            }

            .desktop-table {
                display: table;
            }

            .mobile-cards {
                display: none;
            }

            .filter-bar {
                flex-direction: row;
                align-items: center;
                justify-content: space-between;
            }

            .filter-search {
                width: 240px;
            }
        }

        @media (min-width: 768px) {
            .hero-inner {
                padding: 72px 24px;
            }

            .benefits-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (min-width: 1024px) {
            .hero h1 {
                font-size: 3rem;
            }
        }
    </style>
</head>
<body class="domain-page-wrap">

<?php include "header.php"; ?>
<?php include "contact-btn.php"; ?>

<!-- HERO -->
<section class="hero">
    <div class="hero-inner">
        <div class="badge-pill">
            <span class="badge-dot"></span>
            Transparent Domain Pricing
        </div>

        <h1>Find the perfect domain<br>for your business</h1>
        <p>Search, register & transfer domains with clear pricing. No hidden fees, ever.</p>

        <form class="search-form" method="post" action="<?php echo htmlspecialchars($whmcs_search_url); ?>">
            <div class="search-input-wrap">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" name="domain" placeholder="Search your domain (e.g. brandname.com)" required>
            </div>
            <button type="submit" class="search-btn">
                Search Domain
                <i class="fa-solid fa-arrow-right" style="font-size:11px;"></i>
            </button>
        </form>

        <div class="trust-points">
            <span><span class="dot dot-blue"></span> Instant Search</span>
            <span><span class="dot dot-green"></span> Transparent Pricing</span>
            <span><span class="dot dot-indigo"></span> Easy Management</span>
        </div>
    </div>
</section>

<!-- PRICING -->
<section class="pricing-section">
    <div class="pricing-container">
        
        <!-- Filter -->
        <div class="filter-bar">
            <div class="tabs" id="tldFilterTabs">
                <button type="button" class="tab-btn active" onclick="setTldCategory('all', this)">All TLDs (<?php echo count($all_tlds); ?>)</button>
                <button type="button" class="tab-btn" onclick="setTldCategory('Popular', this)">Popular</button>
                <button type="button" class="tab-btn" onclick="setTldCategory('Tech', this)">Tech & Dev</button>
                <button type="button" class="tab-btn" onclick="setTldCategory('Business', this)">Business</button>
                <button type="button" class="tab-btn" onclick="setTldCategory('Country', this)">Country</button>
            </div>

            <div class="filter-search">
                <input type="text" id="tldLiveSearch" oninput="runTldFilter()" placeholder="Filter TLD...">
                <i class="fa-solid fa-filter"></i>
            </div>
        </div>

        <!-- Table / Cards -->
        <div class="table-wrap">
            
            <!-- Desktop Table -->
            <table class="desktop-table">
                <thead>
                    <tr>
                        <th style="width:26%">TLD</th>
                        <th style="width:24%">Registration</th>
                        <th style="width:20%">Renewal</th>
                        <th style="width:15%">Transfer</th>
                        <th style="width:15%; text-align:right">Action</th>
                    </tr>
                </thead>
                <tbody id="desktopTbody">
                    <?php if (!empty($all_tlds)): ?>
                    <?php foreach ($all_tlds as $tld): 
                        $reg_conv = convertPriceAmount($tld['register_price'], $user_curr);
                        $ren_conv = convertPriceAmount($tld['renew_price'], $user_curr);
                        $tra_conv = convertPriceAmount($tld['transfer_price'], $user_curr);
                        $has_promo = $tld['promo_price'] !== null && $tld['promo_price'] > 0;
                        $promo_conv = $has_promo ? convertPriceAmount($tld['promo_price'], $user_curr) : null;
                    ?>
                    <tr class="tld-table-row" data-category="<?php echo htmlspecialchars($tld['category']); ?>" data-ext="<?php echo htmlspecialchars(strtolower($tld['extension'])); ?>">
                        <td>
                            <span class="tld-name"><?php echo htmlspecialchars($tld['extension']); ?></span>
                            <?php if ($tld['is_popular']): ?>
                            <span class="badge-popular">POPULAR</span>
                            <?php endif; ?>
                            <?php if ($tld['is_promo']): ?>
                            <span class="badge-sale">SALE</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($has_promo): ?>
                            <span class="price-old"><?php echo $currency_symbol; ?><?php echo $reg_conv; ?></span>
                            <span class="price-new"><?php echo $currency_symbol; ?><?php echo $promo_conv; ?></span>
                            <span class="price-note">First year promo</span>
                            <?php else: ?>
                            <span class="price-normal"><?php echo $currency_symbol; ?><?php echo $reg_conv; ?></span>
                            <span class="price-sub">1 Year</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="price-text"><?php echo $currency_symbol; ?><?php echo $ren_conv; ?><span>/year</span></span></td>
                        <td><span class="price-text"><?php echo $currency_symbol; ?><?php echo $tra_conv; ?></span></td>
                        <td>
                            <div class="actions">
                                <a href="<?php echo htmlspecialchars($whmcs_tra_url); ?>" class="btn-transfer">Transfer</a>
                                <a href="<?php echo htmlspecialchars($whmcs_reg_url); ?>" class="btn-register">Register <i class="fa-solid fa-chevron-right" style="font-size:9px;"></i></a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="5" style="padding:48px 20px; text-align:center; color:#94a3b8;">
                            No active domain pricing records available.
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Mobile Cards -->
            <div class="mobile-cards" id="mobileCardsContainer">
                <?php if (!empty($all_tlds)): ?>
                <?php foreach ($all_tlds as $tld): 
                    $reg_conv = convertPriceAmount($tld['register_price'], $user_curr);
                    $ren_conv = convertPriceAmount($tld['renew_price'], $user_curr);
                    $tra_conv = convertPriceAmount($tld['transfer_price'], $user_curr);
                    $has_promo = $tld['promo_price'] !== null && $tld['promo_price'] > 0;
                    $promo_conv = $has_promo ? convertPriceAmount($tld['promo_price'], $user_curr) : null;
                ?>
                <div class="mobile-card tld-card-row" data-category="<?php echo htmlspecialchars($tld['category']); ?>" data-ext="<?php echo htmlspecialchars(strtolower($tld['extension'])); ?>">
                    <div class="card-header">
                        <div class="card-tld">
                            <span class="tld-name"><?php echo htmlspecialchars($tld['extension']); ?></span>
                            <?php if ($tld['is_popular']): ?>
                            <span class="badge-popular">POPULAR</span>
                            <?php endif; ?>
                            <?php if ($tld['is_promo']): ?>
                            <span class="badge-sale">SALE</span>
                            <?php endif; ?>
                        </div>
                        <div class="card-price">
                            <?php if ($has_promo): ?>
                            <span class="price-old"><?php echo $currency_symbol; ?><?php echo $reg_conv; ?></span>
                            <span class="price-new"><?php echo $currency_symbol; ?><?php echo $promo_conv; ?></span>
                            <?php else: ?>
                            <span class="price-normal"><?php echo $currency_symbol; ?><?php echo $reg_conv; ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-info">
                        <div>
                            <label>Renewal</label>
                            <span><?php echo $currency_symbol; ?><?php echo $ren_conv; ?> /yr</span>
                        </div>
                        <div>
                            <label>Transfer</label>
                            <span><?php echo $currency_symbol; ?><?php echo $tra_conv; ?></span>
                        </div>
                    </div>
                    <div class="card-actions">
                        <a href="<?php echo htmlspecialchars($whmcs_tra_url); ?>" class="transfer">Transfer</a>
                        <a href="<?php echo htmlspecialchars($whmcs_reg_url); ?>" class="register">Register <i class="fa-solid fa-arrow-right" style="font-size:10px;"></i></a>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="no-results" id="noResultsNotice">
                <i class="fa-solid fa-globe"></i>
                No domain extensions match your search.
            </div>
        </div>
    </div>
</section>

<!-- BENEFITS -->
<section class="benefits">
    <div class="pricing-container">
        <div class="benefits-grid">
            <div class="benefit-card">
                <div class="benefit-icon icon-blue">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <h3>Free WHOIS Privacy</h3>
                <p>Protect your personal contact details from public lookups and spam at no extra cost.</p>
            </div>
            <div class="benefit-card">
                <div class="benefit-icon icon-green">
                    <i class="fa-solid fa-tag"></i>
                </div>
                <h3>Transparent Pricing</h3>
                <p>No hidden fees, surprise price hikes, or unexpected renewal charges down the road.</p>
            </div>
            <div class="benefit-card">
                <div class="benefit-icon icon-indigo">
                    <i class="fa-solid fa-sliders"></i>
                </div>
                <h3>Easy Domain Management</h3>
                <p>Full DNS control, nameservers, transfers, and email forwarding from one simple dashboard.</p>
            </div>
        </div>
    </div>
</section>

<script>
    let activeCategory = 'all';

    function setTldCategory(cat, btn) {
        activeCategory = cat;
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        runTldFilter();
    }

    function runTldFilter() {
        const search = (document.getElementById('tldLiveSearch').value || '').trim().toLowerCase();
        const rows = document.querySelectorAll('.tld-table-row, .tld-card-row');
        let visible = 0;

        rows.forEach(row => {
            const cat = row.getAttribute('data-category') || '';
            const ext = row.getAttribute('data-ext') || '';
            const matchCat = activeCategory === 'all' || cat === activeCategory;
            const matchSearch = !search || ext.includes(search) || cat.toLowerCase().includes(search);

            if (matchCat && matchSearch) {
                row.style.display = '';
                visible++;
            } else {
                row.style.display = 'none';
            }
        });

        const notice = document.getElementById('noResultsNotice');
        if (notice) {
            notice.style.display = (visible === 0 && rows.length > 0) ? 'block' : 'none';
        }
    }
</script>

<?php include "footer.php"; ?>
</body>
</html>
