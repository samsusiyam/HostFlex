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
    $key = mysqli_real_escape_string($conn, $key);
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
        $cat = mysqli_real_escape_string($conn, $category);
        $where .= " AND category = '$cat'";
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
    $slug = mysqli_real_escape_string($conn, $slug);
    $query = "SELECT * FROM categories WHERE slug = '$slug' AND status = 1 LIMIT 1";
    $result = mysqli_query($conn, $query);
    return mysqli_fetch_assoc($result);
}

function getPageBySlug($slug) {
    global $conn;
    $slug = mysqli_real_escape_string($conn, $slug);
    $query = "SELECT * FROM pages WHERE slug = '$slug' AND status = 1 LIMIT 1";
    $result = mysqli_query($conn, $query);
    return mysqli_fetch_assoc($result);
}

function getMenuItems($location = 'header') {
    global $conn;
    $location = mysqli_real_escape_string($conn, $location);
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

function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        die('Invalid or expired form token. Please go back and try again.');
    }
}

function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . generateCSRFToken() . '">';
}

function escSetting($key) {
    return htmlspecialchars(getSetting($key), ENT_QUOTES, 'UTF-8');
}

function validateImageUpload($file, $allowed_exts = ['jpg','jpeg','png','gif','webp','svg']) {
    if ($file['error'] !== UPLOAD_ERR_OK) return 'Upload error';
    if ($file['size'] > MAX_UPLOAD_SIZE) return 'File too large. Maximum ' . (MAX_UPLOAD_SIZE / 1024 / 1024) . 'MB allowed.';
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed_exts)) return 'Invalid file extension';
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    $allowed_mime = ['image/jpeg','image/png','image/gif','image/webp','image/svg+xml','image/x-icon','image/vnd.microsoft.icon'];
    if (!in_array($mime, $allowed_mime)) return 'Invalid file content (MIME type mismatch)';
    return true;
}

function resizeImage($src, $dst, $max_w, $max_h) {
    list($w, $h, $type) = @getimagesize($src);
    if (!$w || !$h) return false;
    if ($w <= $max_w && $h <= $max_h) return copy($src, $dst);
    $ratio = min($max_w / $w, $max_h / $h);
    $nw = round($w * $ratio);
    $nh = round($h * $ratio);
    $src_img = null;
    switch ($type) {
        case IMAGETYPE_JPEG: $src_img = @imagecreatefromjpeg($src); break;
        case IMAGETYPE_PNG:  $src_img = @imagecreatefrompng($src);  break;
        case IMAGETYPE_WEBP: $src_img = @imagecreatefromwebp($src); break;
        case IMAGETYPE_GIF:  $src_img = @imagecreatefromgif($src);  break;
        default: return false;
    }
    if (!$src_img) return false;
    $dst_img = imagecreatetruecolor($nw, $nh);
    imagealphablending($dst_img, false);
    imagesavealpha($dst_img, true);
    imagecopyresampled($dst_img, $src_img, 0, 0, 0, 0, $nw, $nh, $w, $h);
    $ext = strtolower(pathinfo($dst, PATHINFO_EXTENSION));
    $ok = false;
    if ($ext === 'webp') $ok = imagewebp($dst_img, $dst, 80);
    elseif ($ext === 'png') $ok = imagepng($dst_img, $dst, 8);
    elseif ($ext === 'jpg' || $ext === 'jpeg') $ok = imagejpeg($dst_img, $dst, 85);
    elseif ($ext === 'gif') $ok = imagegif($dst_img, $dst);
    else $ok = imagepng($dst_img, $dst, 8);
    imagedestroy($src_img);
    imagedestroy($dst_img);
    return $ok;
}

function isBannedIP() {
    global $conn;
    if (!tableExists('login_logs')) return false;
    $ip = mysqli_real_escape_string($conn, getClientIP());
    $window = date('Y-m-d H:i:s', strtotime('-15 minutes'));
    $q = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM login_logs WHERE ip_address = '$ip' AND status = 'failed' AND created_at >= '$window'");
    $r = mysqli_fetch_assoc($q);
    return ($r['cnt'] ?? 0) >= 5;
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
    $table = mysqli_real_escape_string($conn, $table);
    $r = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    return mysqli_num_rows($r) > 0;
}

define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024);

function sanitizeSimple($data) {
    global $conn;
    return mysqli_real_escape_string($conn, trim($data));
}

function getUserRole($user_id) {
    global $conn;
    $uid = (int)$user_id;
    $r = mysqli_query($conn, "SELECT role FROM users WHERE id = $uid");
    $row = mysqli_fetch_assoc($r);
    return $row['role'] ?? 'editor';
}

function hasPermission($section, $action = 'view') {
    $role = getUserRole($_SESSION['admin_id'] ?? 0);
    if ($role === 'admin') return true;
    $raw = getSetting('admin_permissions');
    $perms = $raw ? json_decode($raw, true) : [];
    return isset($perms[$role][$section][$action]) && $perms[$role][$section][$action] == 1;
}

function checkPermission($section, $action = 'view') {
    if (!hasPermission($section, $action)) {
        die('Access denied: you do not have permission for this action.');
    }
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
