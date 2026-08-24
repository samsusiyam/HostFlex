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
$client_ip = getClientIP();
$failed_count = 0;
if (tableExists('login_logs')) {
    $recent_fails = mysqli_query($conn, "SELECT COUNT(*) as c FROM login_logs WHERE ip_address = '$client_ip' AND status = 'failed' AND created_at >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
    $failed_count = (int)(mysqli_fetch_assoc($recent_fails)['c'] ?? 0);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = !empty($_POST['remember_me']);
    
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
                    $_SESSION['2fa_pending_remember'] = $remember;
                    logLoginAttempt($username, '2fa_prompt');
                    header('Location: two-factor.php');
                    exit;
                }

                session_regenerate_id(true);
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['admin_username'] = $user['username'];
                $_SESSION['admin_role'] = $user['role'] ?? 'admin';
                
                if ($remember) {
                    // Set secure session cookie lifetime to 30 days
                    $cookie_params = session_get_cookie_params();
                    setcookie(session_name(), session_id(), time() + (86400 * 30), $cookie_params['path'], $cookie_params['domain'], $cookie_params['secure'], $cookie_params['httponly']);
                }

                logLoginAttempt($username, 'success');
                header('Location: dashboard.php');
                exit;
            }
        }
        mysqli_stmt_close($stmt);
    }
    logLoginAttempt($username, 'failed');
    $failed_count++;
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
    <style>
        .login-bg {
            background-color: #0f172a;
            background-image: 
                radial-gradient(at 0% 0%, rgba(37, 99, 235, 0.18) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(99, 102, 241, 0.15) 0px, transparent 50%);
        }
    </style>
</head>
<body class="login-bg min-h-screen flex items-center justify-center p-4 selection:bg-blue-600 selection:text-white">

    <div class="w-full max-w-md my-8">
        
        <!-- Login Card -->
        <div class="bg-white/95 backdrop-blur-md p-6 sm:p-8 rounded-3xl shadow-2xl border border-gray-100/80 animate-in fade-in zoom-in duration-200">
            
            <!-- Branding Header -->
            <div class="text-center mb-6">
                <div class="inline-block p-3 rounded-2xl bg-blue-50/80 mb-3 border border-blue-100/60 shadow-xs">
                    <img src="/<?php echo ltrim($logo, '/'); ?>" class="h-10 mx-auto object-contain" alt="<?php echo htmlspecialchars($site_name); ?>" onerror="this.src='/images/bg.png'">
                </div>
                <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Admin Authentication</h1>
                <p class="text-gray-500 text-xs mt-1">Authorized personnel only • Secure portal</p>
            </div>
            
            <!-- Logout Notice -->
            <?php if (isset($_GET['logged_out'])): ?>
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl text-xs font-semibold mb-5 flex items-center gap-2 shadow-xs">
                <i class="fa-solid fa-circle-check text-emerald-600 text-sm"></i>
                <span>You have been safely logged out.</span>
            </div>
            <?php endif; ?>

            <!-- Error Notice -->
            <?php if ($error): ?>
            <div class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-xl text-xs font-semibold mb-5 flex items-center justify-between shadow-xs">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-triangle-exclamation text-rose-600 text-sm"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
                <?php if ($failed_count >= 3): ?>
                <span class="text-[10px] bg-rose-200/80 text-rose-900 px-2 py-0.5 rounded-full font-bold"><?php echo $failed_count; ?> failed</span>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form method="POST" id="loginForm" class="space-y-4">
                <?php if (!empty($_GET['access']) || !empty($_POST['access'])): ?>
                <input type="hidden" name="access" value="<?php echo htmlspecialchars($_GET['access'] ?? ($_POST['access'] ?? '')); ?>">
                <?php endif; ?>

                <div>
                    <label class="block text-gray-700 text-xs font-bold uppercase tracking-wider mb-1.5">Username</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400 text-xs">
                            <i class="fa-solid fa-user"></i>
                        </span>
                        <input type="text" name="username" required autofocus placeholder="Enter your username"
                               class="w-full pl-9 pr-3.5 py-2.5 bg-gray-50/50 border border-gray-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition text-xs sm:text-sm text-gray-900 font-medium">
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-gray-700 text-xs font-bold uppercase tracking-wider">Password</label>
                    </div>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400 text-xs">
                            <i class="fa-solid fa-lock"></i>
                        </span>
                        <input type="password" name="password" id="loginPassword" required placeholder="••••••••"
                               class="w-full pl-9 pr-10 py-2.5 bg-gray-50/50 border border-gray-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition text-xs sm:text-sm text-gray-900">
                        <button type="button" onclick="togglePasswordVisibility()" class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-gray-400 hover:text-gray-600 text-xs cursor-pointer" tabindex="-1" title="Show / Hide Password">
                            <i class="fa-regular fa-eye" id="passwordEyeIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-1 text-xs">
                    <label class="flex items-center gap-2 cursor-pointer select-none text-gray-600 font-medium">
                        <input type="checkbox" name="remember_me" value="1" class="rounded text-blue-600 focus:ring-blue-500 w-4 h-4 border-gray-300">
                        <span>Remember my session</span>
                    </label>
                    <span class="text-[11px] text-gray-400 flex items-center gap-1">
                        <i class="fa-solid fa-shield-halved text-emerald-500"></i> SSL 256-bit
                    </span>
                </div>

                <div class="pt-2">
                    <button type="submit" id="loginSubmitBtn" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-xl shadow-md shadow-blue-500/20 transition flex items-center justify-center gap-2 text-xs sm:text-sm cursor-pointer">
                        <i class="fa-solid fa-right-to-bracket"></i>
                        <span>Authenticate & Enter</span>
                    </button>
                </div>
            </form>

            <div class="mt-6 pt-4 border-t border-gray-100 flex items-center justify-between text-xs text-gray-500">
                <a href="/" class="hover:text-blue-600 transition inline-flex items-center gap-1 font-medium">
                    <i class="fa-solid fa-arrow-left text-[10px]"></i> Main Website
                </a>
                <span class="text-gray-400 text-[11px]">Host Nibo v2.5</span>
            </div>

        </div>

        <p class="text-center text-gray-400 text-[11px] mt-4">
            Protected by Host Nibo Security Guard & TOTP Multi-factor Authentication
        </p>

    </div>

    <script>
    function togglePasswordVisibility() {
        var input = document.getElementById('loginPassword');
        var icon = document.getElementById('passwordEyeIcon');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }

    document.getElementById('loginForm').addEventListener('submit', function() {
        var btn = document.getElementById('loginSubmitBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Authenticating...';
    });
    </script>
</body>
</html>
