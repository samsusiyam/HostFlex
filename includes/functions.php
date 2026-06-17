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

function getSetting($key) {
    global $conn;
    $query = "SELECT setting_value FROM settings WHERE setting_key = '$key'";
    $result = mysqli_query($conn, $query);
    if ($row = mysqli_fetch_assoc($result)) {
        return $row['setting_value'];
    }
    return '';
}

function getPlans($category = null) {
    global $conn;
    $where = "WHERE status = 1";
    if ($category) {
        $where .= " AND category = '$category'";
    }
    $query = "SELECT * FROM hosting_plans $where ORDER BY sort_order ASC";
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
    return $row['count'];
}

function sanitize($data) {
    global $conn;
    return mysqli_real_escape_string($conn, htmlspecialchars(strip_tags(trim($data))));
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
    $query = "SELECT * FROM categories WHERE slug = '$slug' AND status = 1 LIMIT 1";
    $result = mysqli_query($conn, $query);
    return mysqli_fetch_assoc($result);
}

function getPageBySlug($slug) {
    global $conn;
    $query = "SELECT * FROM pages WHERE slug = '$slug' AND status = 1 LIMIT 1";
    $result = mysqli_query($conn, $query);
    return mysqli_fetch_assoc($result);
}

function getMenuItems($location = 'header') {
    global $conn;
    $query = "SELECT * FROM menu_items WHERE status = 1 AND (location = '$location' OR location = 'both') ORDER BY sort_order ASC";
    $result = mysqli_query($conn, $query);
    $items = [];
    while ($row = mysqli_fetch_assoc($result)) {
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
        $ip = trim($ips[0]);
    } elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
        $ip = $_SERVER['HTTP_X_REAL_IP'];
    }
    return $ip;
}

function tableExists($table) {
    global $conn;
    $r = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    return mysqli_num_rows($r) > 0;
}

function logActivity($action, $details = '') {
    global $conn;
    if (!tableExists('activity_logs')) return;
    $user_id = (int)($_SESSION['admin_id'] ?? 0);
    $username = mysqli_real_escape_string($conn, $_SESSION['admin_username'] ?? 'System');
    $action = mysqli_real_escape_string($conn, $action);
    $details = mysqli_real_escape_string($conn, $details);
    $ip = getClientIP();
    @mysqli_query($conn, "INSERT INTO activity_logs (user_id, username, action, details, ip_address) VALUES ($user_id, '$username', '$action', '$details', '$ip')");
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
