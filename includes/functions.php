<?php
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']);
}

function checkAdminLogin() {
    if (!isAdminLoggedIn()) {
        header('Location: index.php');
        exit;
    }
}

function ensure2FASchema() {
    global $conn;
    static $checked = false;
    if ($checked || !$conn) return;
    $checked = true;
    
    $res = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'two_factor_enabled'");
    if ($res && mysqli_num_rows($res) === 0) {
        mysqli_query($conn, "ALTER TABLE users 
            ADD COLUMN two_factor_enabled TINYINT(1) DEFAULT 0,
            ADD COLUMN two_factor_secret VARCHAR(100) NULL,
            ADD COLUMN two_factor_backup_codes TEXT NULL");
    }
}

function ensureBlogSchema() {
    global $conn;
    static $blog_checked = false;
    if ($blog_checked || !$conn) return;
    $blog_checked = true;
    
    $res = mysqli_query($conn, "SHOW COLUMNS FROM blog_posts LIKE 'show_featured_image'");
    if ($res && mysqli_num_rows($res) === 0) {
        mysqli_query($conn, "ALTER TABLE blog_posts ADD COLUMN show_featured_image TINYINT(1) DEFAULT 1");
    }
    $res = mysqli_query($conn, "SHOW COLUMNS FROM blog_posts LIKE 'show_toc'");
    if ($res && mysqli_num_rows($res) === 0) {
        mysqli_query($conn, "ALTER TABLE blog_posts ADD COLUMN show_toc TINYINT(1) DEFAULT 1");
    }
    $res = mysqli_query($conn, "SHOW COLUMNS FROM blog_posts LIKE 'show_author'");
    if ($res && mysqli_num_rows($res) === 0) {
        mysqli_query($conn, "ALTER TABLE blog_posts ADD COLUMN show_author TINYINT(1) DEFAULT 1");
    }
}

function getReadingTime($content) {
    $word_count = str_word_count(strip_tags((string)$content));
    $minutes = ceil($word_count / 200);
    return max(1, (int)$minutes);
}

function generateBlogTOC(&$content) {
    $toc = [];
    $index = 1;
    $content = preg_replace_callback('/<h([23])([^>]*)>(.*?)<\/h\1>/is', function($matches) use (&$toc, &$index) {
        $level = (int)$matches[1];
        $attrs = $matches[2];
        $raw_title = html_entity_decode(strip_tags($matches[3]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $title = trim(preg_replace('/\s+/', ' ', $raw_title));
        
        // Skip empty or trivial headings
        if (empty($title) || strlen($title) < 2) {
            return $matches[0];
        }
        
        $id = 'section-' . $index . '-' . preg_replace('/[^a-z0-9]+/i', '-', strtolower($title));
        $id = trim($id, '-');
        if (empty($id)) {
            $id = 'section-' . $index;
        }
        
        $toc[] = [
            'level' => $level,
            'title' => $title,
            'id' => $id
        ];
        $index++;
        
        $clean_attrs = preg_replace('/\bid=[\'"][^\'"]*[\'"]/i', '', $attrs);
        return "<h{$level}{$clean_attrs} id=\"{$id}\">{$matches[3]}</h{$level}>";
    }, (string)$content);
    
    return $toc;
}

function checkAdminAccessSlug() {
    $custom_slug = trim(getSetting('admin_access_slug') ?: '');
    if (empty($custom_slug)) {
        return true;
    }
    
    if (isAdminLoggedIn()) {
        return true;
    }
    
    $given_access = $_GET['access'] ?? ($_POST['access'] ?? '');
    if (!empty($given_access) && hash_equals($custom_slug, (string)$given_access)) {
        return true;
    }
    
    // Redirect unauthorized access directly to homepage without loops
    header('Location: /');
    exit;
}

function getAdminRole() {
    return $_SESSION['admin_role'] ?? 'admin';
}

function hasRole($roles) {
    if (!isAdminLoggedIn()) return false;
    $currentRole = getAdminRole();
    if (is_string($roles)) {
        $roles = [$roles];
    }
    return in_array($currentRole, $roles);
}

function checkAdminRole($allowed_roles) {
    checkAdminLogin();
    if (!hasRole($allowed_roles)) {
        http_response_code(403);
        die('<!DOCTYPE html><html><head><title>403 Forbidden</title><script src="https://cdn.tailwindcss.com"></script></head><body class="bg-gray-100 flex items-center justify-center min-h-screen"><div class="bg-white p-8 rounded-lg shadow text-center max-w-md"><h1 class="text-3xl font-bold text-red-600 mb-2">403 Access Denied</h1><p class="text-gray-600 mb-4">You do not have permission to access this page.</p><a href="dashboard.php" class="bg-blue-600 text-white px-4 py-2 rounded">Back to Dashboard</a></div></body></html>');
    }
}

function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function getSetting($key) {
    global $conn;
    $stmt = mysqli_prepare($conn, "SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $key);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($result)) {
            mysqli_stmt_close($stmt);
            return $row['setting_value'];
        }
        mysqli_stmt_close($stmt);
    }
    return '';
}

function getPlans($category = null) {
    global $conn;
    if ($category) {
        $stmt = mysqli_prepare($conn, "SELECT * FROM hosting_plans WHERE status = 1 AND category = ? ORDER BY sort_order ASC");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $category);
            mysqli_stmt_execute($stmt);
            return mysqli_stmt_get_result($stmt);
        }
    }
    $query = "SELECT * FROM hosting_plans WHERE status = 1 ORDER BY sort_order ASC";
    return mysqli_query($conn, $query);
}

function getActiveOffers() {
    global $conn;
    $query = "SELECT * FROM offers WHERE status = 1 ORDER BY sort_order ASC";
    return mysqli_query($conn, $query);
}

function getUnreadContacts() {
    global $conn;
    $query = "SELECT COUNT(*) as count FROM contacts WHERE is_read = 0";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    return $row['count'] ?? 0;
}

function sanitize($data) {
    global $conn;
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return mysqli_real_escape_string($conn, trim(strip_tags((string)$data)));
}

function timeAgo($timestamp) {
    $time = strtotime($timestamp);
    $diff = time() - $time;
    
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . ' minutes ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 2592000) return floor($diff / 86400) . ' days ago';
    return date('d M Y', $time);
}

function getCategories($status = true) {
    global $conn;
    $where = $status ? "WHERE status = 1" : "";
    $query = "SELECT * FROM categories $where ORDER BY sort_order ASC";
    return mysqli_query($conn, $query);
}

function getCategoryBySlug($slug) {
    global $conn;
    $stmt = mysqli_prepare($conn, "SELECT * FROM categories WHERE slug = ? AND status = 1 LIMIT 1");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $slug);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);
        return $row;
    }
    return null;
}

function getPageBySlug($slug) {
    global $conn;
    $stmt = mysqli_prepare($conn, "SELECT * FROM pages WHERE slug = ? AND status = 1 LIMIT 1");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $slug);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);
        return $row;
    }
    return null;
}

function formatCleanUrl($url) {
    if (empty($url)) return $url;
    $trimmed = trim($url);
    if (preg_match('/^category\.php\?slug=([a-zA-Z0-9_-]+)$/i', $trimmed, $m)) {
        return '/category/' . $m[1];
    }
    if (preg_match('/^blog\.php\?slug=([a-zA-Z0-9_-]+)$/i', $trimmed, $m)) {
        return '/blog/' . $m[1];
    }
    if (preg_match('/^blogs\.php\?category=([a-zA-Z0-9_-]+)$/i', $trimmed, $m)) {
        return '/blog/category/' . $m[1];
    }
    if ($trimmed === 'blogs.php' || $trimmed === '/blogs.php' || $trimmed === 'blogs') {
        return '/blog';
    }
    if ($trimmed === 'contact.php' || $trimmed === '/contact.php' || $trimmed === 'contact') {
        return '/contact';
    }
    if ($trimmed === 'offers.php' || $trimmed === '/offers.php' || $trimmed === 'offers') {
        return '/offers';
    }
    if ($trimmed === 'index.php' || $trimmed === '/index.php') {
        return '/';
    }
    if (preg_match('/^page\.php\?slug=([a-zA-Z0-9_-]+)$/i', $trimmed, $m)) {
        return '/page/' . $m[1];
    }
    return $url;
}

function getMenuItems($location = 'header') {
    global $conn;
    $loc_esc = mysqli_real_escape_string($conn, $location);
    $query = "SELECT * FROM menu_items WHERE status = 1 AND (location = '$loc_esc' OR location = 'both') ORDER BY sort_order ASC";
    $result = mysqli_query($conn, $query);
    $items = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $row['url'] = formatCleanUrl($row['url']);
        $items[] = $row;
    }
    return $items;
}

function buildMenuTree($items, $parent_id = 0) {
    $tree = [];
    foreach ($items as $item) {
        if ($item['parent_id'] == $parent_id) {
            $children = buildMenuTree($items, $item['id']);
            if ($children) {
                $item['children'] = $children;
            }
            $tree[] = $item;
        }
    }
    return $tree;
}

function getClientIP() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $candidate = trim($ips[0]);
        if (filter_var($candidate, FILTER_VALIDATE_IP)) {
            $ip = $candidate;
        }
    } elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
        $candidate = trim($_SERVER['HTTP_X_REAL_IP']);
        if (filter_var($candidate, FILTER_VALIDATE_IP)) {
            $ip = $candidate;
        }
    }
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
}

function tableExists($table) {
    global $conn;
    $table_esc = mysqli_real_escape_string($conn, $table);
    $r = mysqli_query($conn, "SHOW TABLES LIKE '$table_esc'");
    return mysqli_num_rows($r) > 0;
}

function logActivity($action, $details = '') {
    global $conn;
    if (!tableExists('activity_logs')) return;
    $user_id = (int)($_SESSION['admin_id'] ?? 0);
    $username = $_SESSION['admin_username'] ?? 'System';
    $ip = getClientIP();
    $stmt = mysqli_prepare($conn, "INSERT INTO activity_logs (user_id, username, action, details, ip_address) VALUES (?, ?, ?, ?, ?)");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "issss", $user_id, $username, $action, $details, $ip);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

function isMaintenanceMode() {
    $mode = getSetting('maintenance_mode');
    return $mode === '1';
}

function checkMaintenance() {
    if (isMaintenanceMode() && !isset($_SESSION['admin_id'])) {
        while (ob_get_level()) ob_end_clean();
        $title = getSetting('maintenance_title') ?: 'Under Maintenance';
        $heading = getSetting('maintenance_heading') ?: "We'll be back soon!";
        $message = getSetting('maintenance_message') ?: 'Our website is currently undergoing scheduled maintenance. Please check back later.';
        http_response_code(503);
        ?><!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title><?php echo htmlspecialchars($title); ?></title><script src="https://cdn.tailwindcss.com"></script><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css"></head><body class="bg-gray-100 min-h-screen flex items-center justify-center"><div class="text-center max-w-lg mx-auto p-8"><div class="text-6xl text-yellow-500 mb-6"><i class="fas fa-tools"></i></div><h1 class="text-3xl font-bold text-gray-800 mb-4"><?php echo htmlspecialchars($heading); ?></h1><p class="text-gray-600 text-lg"><?php echo htmlspecialchars($message); ?></p></div></body></html><?php
        exit;
    }
}

function renderMenu($items, $is_mobile = false) {
    $html = '';
    foreach ($items as $item) {
        $has_children = isset($item['children']) && !empty($item['children']);
        $url = htmlspecialchars($item['url']);
        $label = htmlspecialchars($item['label']);
        
        if ($has_children) {
            $html .= '<div class="group relative z-50 flex h-[80px] cursor-pointer items-center gap-1">';
            $html .= '<span class="font-medium hover:text-blue-600">' . $label . '</span>';
            $html .= '<small class="text-xs ml-1"><i class="fa fa-chevron-down"></i></small>';
            $html .= '<div class="absolute top-full hidden flex-col border-t-transparent bg-white text-sm shadow group-hover:flex">';
            foreach ($item['children'] as $child) {
                $child_url = htmlspecialchars($child['url']);
                $child_label = htmlspecialchars($child['label']);
                $html .= '<a href="' . $child_url . '" class="whitespace-nowrap border-b px-4 py-2 hover:text-blue-600">' . $child_label . '</a>';
            }
            $html .= '</div></div>';
        } else {
            $html .= '<a href="' . $url . '" class="font-medium hover:text-blue-600">' . $label . '</a>';
        }
    }
    return $html;
}

function getImageUrl($path) {
    if (empty($path)) return '';
    $path = trim($path);
    if (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0 || strpos($path, '//') === 0) {
        return $path;
    }
    return '/' . ltrim($path, '/');
}

function getSiteUrl() {
    $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $dir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
    $dir = preg_replace('#/admin$#i', '', $dir);
    return $proto . '://' . $host . ($dir ? $dir : '') . '/';
}

function getBlogPostUrl($post) {
    if (is_numeric($post)) {
        global $conn;
        $post = mysqli_fetch_assoc(mysqli_query($conn, "SELECT p.*, c.slug as category_slug FROM blog_posts p LEFT JOIN blog_categories c ON p.category_id = c.id WHERE p.id = " . (int)$post));
    }
    if (!$post) return '/blog';
    
    $structure = getSetting('blog_permalink_structure') ?: 'post_name';
    $cat_slug = !empty($post['category_slug']) ? $post['category_slug'] : 'uncategorized';
    $post_slug = $post['slug'] ?? '';
    $post_id = $post['id'] ?? 0;
    
    switch ($structure) {
        case 'category_post_name':
            return "/blog/{$cat_slug}/{$post_slug}";
        case 'category_post_id':
            return "/blog/{$cat_slug}/{$post_id}";
        case 'post_id':
            return "/blog/{$post_id}";
        case 'post_name':
        default:
            return "/blog/{$post_slug}";
    }
}

function getCanonicalUrl() {
    $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $dir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
    $dir = preg_replace('#/admin$#i', '', $dir);
    $base = $proto . '://' . $host . ($dir ? $dir : '');
    
    if (isset($_GET['slug'])) {
        $slug = urlencode($_GET['slug']);
        $script = basename($_SERVER['SCRIPT_NAME'] ?? '');
        if ($script === 'category.php') return $base . '/category/' . $slug;
        if ($script === 'blog.php') return $base . '/blog/' . $slug;
        if ($script === 'page.php') return $base . '/page/' . $slug;
    }
    if (isset($_GET['category'])) {
        return $base . '/blog/category/' . urlencode($_GET['category']);
    }
    
    $clean_uri = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
    if ($clean_uri === '/index.php') $clean_uri = '/';
    if ($clean_uri === '/blogs.php') $clean_uri = '/blog';
    if ($clean_uri === '/contact.php') $clean_uri = '/contact';
    if ($clean_uri === '/offers.php') $clean_uri = '/offers';
    return $proto . '://' . $host . $clean_uri;
}

function renderSeoTags($options = []) {
    $site_name = getSetting('site_name') ?: 'Host Nibo';
    $title = !empty($options['title']) ? $options['title'] : ($site_name . ' - ' . (getSetting('site_tagline') ?: 'Fast & Reliable Web Hosting'));
    $description = !empty($options['description']) ? $options['description'] : (getSetting('site_description') ?: 'Fast and affordable web hosting solutions for personal and business websites.');
    $keywords = !empty($options['keywords']) ? $options['keywords'] : getSetting('site_keywords');
    $canonical = !empty($options['canonical']) ? $options['canonical'] : getCanonicalUrl();
    $type = !empty($options['type']) ? $options['type'] : 'website';
    $image = !empty($options['image']) ? $options['image'] : (getSiteUrl() . (getSetting('header_logo') ?: 'images/bg.png'));
    if (!filter_var($image, FILTER_VALIDATE_URL)) {
        $image = getSiteUrl() . ltrim($image, '/');
    }

    $html = "\n";
    if ($description) $html .= '<meta name="description" content="' . htmlspecialchars($description) . '" />' . "\n";
    if ($keywords) $html .= '<meta name="keywords" content="' . htmlspecialchars($keywords) . '" />' . "\n";
    $html .= '<link rel="canonical" href="' . htmlspecialchars($canonical) . '" />' . "\n";
    
    // Open Graph
    $html .= '<meta property="og:locale" content="en_US" />' . "\n";
    $html .= '<meta property="og:type" content="' . htmlspecialchars($type) . '" />' . "\n";
    $html .= '<meta property="og:site_name" content="' . htmlspecialchars($site_name) . '" />' . "\n";
    $html .= '<meta property="og:title" content="' . htmlspecialchars($title) . '" />' . "\n";
    $html .= '<meta property="og:description" content="' . htmlspecialchars($description) . '" />' . "\n";
    $html .= '<meta property="og:url" content="' . htmlspecialchars($canonical) . '" />' . "\n";
    $html .= '<meta property="og:image" content="' . htmlspecialchars($image) . '" />' . "\n";

    // Twitter Cards
    $html .= '<meta name="twitter:card" content="summary_large_image" />' . "\n";
    $html .= '<meta name="twitter:title" content="' . htmlspecialchars($title) . '" />' . "\n";
    $html .= '<meta name="twitter:description" content="' . htmlspecialchars($description) . '" />' . "\n";
    $html .= '<meta name="twitter:image" content="' . htmlspecialchars($image) . '" />' . "\n";

    // JSON-LD Schemas
    $schemas = [];
    
    // Base Organization Schema
    $schemas[] = [
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => $site_name,
        'url' => getSiteUrl(),
        'logo' => getSiteUrl() . (getSetting('header_logo') ?: 'images/bg.png')
    ];

    // Base WebSite Schema
    $schemas[] = [
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        'name' => $site_name,
        'url' => getSiteUrl(),
        'potentialAction' => [
            '@type' => 'SearchAction',
            'target' => getSiteUrl() . 'blog?search={search_term_string}',
            'query-input' => 'required name=search_term_string'
        ]
    ];

    // Custom Schema (if provided, e.g. Product, BlogPosting, FAQPage)
    if (!empty($options['schema']) && is_array($options['schema'])) {
        $schemas[] = $options['schema'];
    }

    foreach ($schemas as $schema) {
        $html .= '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
    }

    return $html;
}

// ==========================================
// MULTI-CURRENCY CONVERSION SYSTEM
// ==========================================

function getDefaultCurrencies() {
    return [
        'BDT' => ['code' => 'BDT', 'symbol' => '৳', 'name' => 'Bangladeshi Taka', 'rate' => 1.0, 'enabled' => 1],
        'USD' => ['code' => 'USD', 'symbol' => '$', 'name' => 'US Dollar', 'rate' => 0.00833, 'enabled' => 1],
        'EUR' => ['code' => 'EUR', 'symbol' => '€', 'name' => 'Euro', 'rate' => 0.00780, 'enabled' => 1],
        'GBP' => ['code' => 'GBP', 'symbol' => '£', 'name' => 'British Pound', 'rate' => 0.00660, 'enabled' => 1],
        'INR' => ['code' => 'INR', 'symbol' => '₹', 'name' => 'Indian Rupee', 'rate' => 0.72000, 'enabled' => 1],
        'AED' => ['code' => 'AED', 'symbol' => 'AED', 'name' => 'UAE Dirham', 'rate' => 0.03060, 'enabled' => 0],
        'SAR' => ['code' => 'SAR', 'symbol' => 'SAR', 'name' => 'Saudi Riyal', 'rate' => 0.03120, 'enabled' => 0],
        'CAD' => ['code' => 'CAD', 'symbol' => 'CA$', 'name' => 'Canadian Dollar', 'rate' => 0.01180, 'enabled' => 0]
    ];
}

function getCurrenciesList() {
    $raw = getSetting('currencies_config');
    if (!empty($raw)) {
        $decoded = json_decode($raw, true);
        if (is_array($decoded) && !empty($decoded)) {
            return $decoded;
        }
    }
    return getDefaultCurrencies();
}

function getActiveCurrencies() {
    $all = getCurrenciesList();
    $active = [];
    foreach ($all as $code => $cur) {
        if (!empty($cur['enabled']) && $cur['enabled'] == 1) {
            $active[$code] = $cur;
        }
    }
    if (empty($active)) {
        $base = getSetting('base_currency') ?: 'BDT';
        $sym = getSetting('currency_symbol') ?: '৳';
        $active[$base] = ['code' => $base, 'symbol' => $sym, 'name' => $base, 'rate' => 1.0, 'enabled' => 1];
    }
    return $active;
}

function isMultiCurrencyEnabled() {
    $val = getSetting('multi_currency_enabled');
    return ($val === '' || $val === '1' || $val === 1);
}

function getUserCurrency() {
    $active = getActiveCurrencies();
    
    // Check if currency switch request in URL
    if (isset($_GET['currency'])) {
        $req_curr = strtoupper(trim($_GET['currency']));
        if (isset($active[$req_curr])) {
            $_SESSION['user_currency'] = $req_curr;
            setcookie('user_currency', $req_curr, time() + (86400 * 30), '/');
            return $active[$req_curr];
        }
    }
    
    // Check session
    if (!empty($_SESSION['user_currency']) && isset($active[$_SESSION['user_currency']])) {
        return $active[$_SESSION['user_currency']];
    }
    
    // Check cookie
    if (!empty($_COOKIE['user_currency']) && isset($active[$_COOKIE['user_currency']])) {
        $_SESSION['user_currency'] = $_COOKIE['user_currency'];
        return $active[$_COOKIE['user_currency']];
    }
    
    // Fallback to base currency
    $base = strtoupper(getSetting('base_currency') ?: 'BDT');
    if (isset($active[$base])) {
        return $active[$base];
    }
    
    // Return first active currency
    $first = reset($active);
    return $first ?: ['code' => 'BDT', 'symbol' => '৳', 'name' => 'Bangladeshi Taka', 'rate' => 1.0, 'enabled' => 1];
}

function convertPriceAmount($base_amount, $target_currency = null) {
    if ($base_amount === null || $base_amount === '' || !is_numeric($base_amount)) {
        return 0;
    }
    $curr = $target_currency ?: getUserCurrency();
    $rate = (float)($curr['rate'] ?? 1.0);
    $converted = (float)$base_amount * $rate;
    
    $code = $curr['code'] ?? 'BDT';
    if ($code === 'BDT' || $code === 'INR') {
        return round($converted);
    } else {
        return (fmod($converted, 1) === 0.0) ? round($converted) : number_format($converted, 2, '.', '');
    }
}

function formatCurrencyPrice($base_amount, $target_currency = null) {
    $curr = $target_currency ?: getUserCurrency();
    $converted = convertPriceAmount($base_amount, $curr);
    $sym = $curr['symbol'] ?? '৳';
    return $sym . ' ' . $converted;
}

function getCurrencySwitchUrl($code) {
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $parts = parse_url($uri);
    $path = $parts['path'] ?? '/';
    $query_params = [];
    if (!empty($parts['query'])) {
        parse_str($parts['query'], $query_params);
    }
    $query_params['currency'] = strtoupper(trim($code));
    return $path . '?' . http_build_query($query_params);
}

function fetchLiveExchangeRates($base = null) {
    if (!$base) {
        $base = strtoupper(getSetting('base_currency') ?: 'BDT');
    }
    
    $urls = [
        "https://open.er-api.com/v6/latest/$base",
        "https://api.exchangerate-api.com/v4/latest/$base"
    ];
    
    foreach ($urls as $url) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 6);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'HostNibo-CurrencySync/1.0');
        $res = curl_exec($ch);
        curl_close($ch);
        
        if ($res) {
            $data = json_decode($res, true);
            if (isset($data['rates']) && is_array($data['rates'])) {
                return [
                    'success' => true,
                    'base' => $base,
                    'rates' => $data['rates'],
                    'time' => $data['time_last_update_utc'] ?? date('Y-m-d H:i:s')
                ];
            }
        }
    }
    
    return ['success' => false, 'error' => 'Unable to connect to live exchange rates service.'];
}


