<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

ensure2FASchema();
checkAdminAccessSlug();

if (isAdminLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

function logLoginAttempt($username, $status) {
    global $conn;
    if (!tableExists('login_logs')) return;
    $s = $status;
    $ip = getClientIP();
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $stmt = mysqli_prepare($conn, "INSERT INTO login_logs (username, status, ip_address, user_agent) VALUES (?, ?, ?, ?)");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ssss", $username, $s, $ip, $ua);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE username = ? AND status = 1 LIMIT 1");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($user = mysqli_fetch_assoc($result)) {
            if (password_verify($password, $user['password'])) {
                // Check if Two-Factor Authentication is enabled
                if (!empty($user['two_factor_enabled'])) {
                    $_SESSION['2fa_pending_admin_id'] = $user['id'];
                    logLoginAttempt($username, '2fa_prompt');
                    header('Location: two-factor.php');
                    exit;
                }

                session_regenerate_id(true);
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['admin_username'] = $user['username'];
                $_SESSION['admin_role'] = $user['role'] ?? 'admin';
                logLoginAttempt($username, 'success');
                header('Location: dashboard.php');
                exit;
            }
        }
        mysqli_stmt_close($stmt);
    }
    logLoginAttempt($username, 'failed');
    $error = 'Invalid username or password!';
}

$site_name = getSetting('site_name') ?: 'Host Nibo';
$favicon = getSetting('favicon') ?: 'images/favicon.ico';
$logo = getSetting('header_logo') ?: 'images/bg.png';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?php echo htmlspecialchars($site_name); ?></title>
    <meta name="robots" content="noindex, nofollow, noarchive, nosnippet" />
    <link rel="shortcut icon" href="/<?php echo ltrim($favicon, '/'); ?>" type="image/x-icon" />
    <link rel="icon" href="/<?php echo ltrim($favicon, '/'); ?>" type="image/x-icon" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white p-8 rounded-2xl shadow-xl w-full max-w-md border border-gray-200">
        <div class="text-center mb-8">
            <img src="/<?php echo ltrim($logo, '/'); ?>" class="h-12 mx-auto mb-4 object-contain" alt="<?php echo htmlspecialchars($site_name); ?>">
            <h1 class="text-2xl font-bold text-gray-900">Admin Login</h1>
            <p class="text-gray-500 text-sm mt-1"><?php echo htmlspecialchars($site_name); ?> Management Panel</p>
        </div>
        
        <?php if ($error): ?>
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded text-sm mb-5 flex items-center gap-2">
                <i class="fa fa-circle-exclamation text-red-500"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-4">
                <label class="block text-gray-700 text-xs font-bold uppercase tracking-wider mb-2">Username</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                        <i class="fa fa-user"></i>
                    </span>
                    <input type="text" name="username" required autofocus placeholder="Admin username"
                           class="w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-xl focus:outline-none focus:border-blue-600 transition text-sm">
                </div>
            </div>
            <div class="mb-6">
                <label class="block text-gray-700 text-xs font-bold uppercase tracking-wider mb-2">Password</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                        <i class="fa fa-lock"></i>
                    </span>
                    <input type="password" name="password" required placeholder="••••••••"
                           class="w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-xl focus:outline-none focus:border-blue-600 transition text-sm">
                </div>
            </div>
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-xl shadow-md transition duration-200 flex items-center justify-center gap-2">
                <i class="fa fa-right-to-bracket"></i> Login to Dashboard
            </button>
        </form>

        <div class="mt-6 pt-4 border-t text-center">
            <a href="/" class="text-xs text-gray-500 hover:text-blue-600">
                <i class="fa fa-arrow-left mr-1"></i> Back to Main Website
            </a>
        </div>
    </div>
</body>
</html>
