<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/totp.php';

ensure2FASchema();

// Ensure there is a pending 2FA login session
if (!isset($_SESSION['2fa_pending_admin_id'])) {
    header('Location: index.php');
    exit;
}

$pending_id = (int)$_SESSION['2fa_pending_admin_id'];
$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ? AND status = 1 LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $pending_id);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$user || empty($user['two_factor_enabled'])) {
    // If 2FA not required, login directly
    $_SESSION['admin_id'] = $user['id'];
    $_SESSION['admin_username'] = $user['username'];
    $_SESSION['admin_role'] = $user['role'] ?? 'admin';
    unset($_SESSION['2fa_pending_admin_id']);
    header('Location: dashboard.php');
    exit;
}

$error = '';
$site_name = getSetting('site_name') ?: 'Host Nibo';
$favicon = getSetting('favicon') ?: 'images/favicon.ico';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $auth_type = $_POST['auth_type'] ?? 'totp';
    
    if ($auth_type === 'backup') {
        $backup_code = strtoupper(trim($_POST['backup_code'] ?? ''));
        $stored_codes = json_decode($user['two_factor_backup_codes'] ?? '[]', true) ?: [];
        
        $code_index = array_search($backup_code, $stored_codes);
        if ($code_index !== false) {
            // Remove used backup code from database
            unset($stored_codes[$code_index]);
            $updated_codes_json = mysqli_real_escape_string($conn, json_encode(array_values($stored_codes)));
            mysqli_query($conn, "UPDATE users SET two_factor_backup_codes = '$updated_codes_json' WHERE id = $pending_id");
            
            // Login successful
            session_regenerate_id(true);
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_username'] = $user['username'];
            $_SESSION['admin_role'] = $user['role'] ?? 'admin';
            unset($_SESSION['2fa_pending_admin_id']);
            
            logActivity('2FA Login with Backup Code', 'User: ' . $user['username']);
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Invalid emergency backup code!';
        }
    } else {
        $otp = trim($_POST['otp_code'] ?? '');
        $secret = $user['two_factor_secret'] ?? '';
        
        if (SimpleTOTP::verifyCode($secret, $otp)) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_username'] = $user['username'];
            $_SESSION['admin_role'] = $user['role'] ?? 'admin';
            unset($_SESSION['2fa_pending_admin_id']);
            
            logActivity('2FA Login Verified', 'User: ' . $user['username']);
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Invalid 6-digit authentication code. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>2FA Verification - <?php echo htmlspecialchars($site_name); ?> Admin</title>
    <meta name="robots" content="noindex, nofollow, noarchive, nosnippet" />
    <link rel="shortcut icon" href="/<?php echo ltrim($favicon, '/'); ?>" type="image/x-icon" />
    <link rel="icon" href="/<?php echo ltrim($favicon, '/'); ?>" type="image/x-icon" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-200">
        <div class="bg-gradient-to-r from-blue-600 to-indigo-700 p-6 text-center text-white">
            <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-3 text-2xl backdrop-blur-sm">
                <i class="fa fa-shield-halved"></i>
            </div>
            <h1 class="text-xl font-bold">Two-Factor Authentication</h1>
            <p class="text-xs text-blue-100 mt-1">Protecting account: <strong class="text-white"><?php echo htmlspecialchars($user['username']); ?></strong></p>
        </div>

        <div class="p-6 md:p-8">
            <?php if ($error): ?>
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-3.5 rounded text-sm mb-5 flex items-center gap-2">
                <i class="fa fa-circle-exclamation text-red-500"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
            <?php endif; ?>

            <!-- TOTP Form (Default) -->
            <div id="totpSection">
                <p class="text-sm text-gray-600 mb-6 text-center">
                    Enter the 6-digit verification code generated by your Authenticator app (Google Authenticator, Authy, Microsoft Authenticator).
                </p>
                <form method="POST">
                    <input type="hidden" name="auth_type" value="totp">
                    <div class="mb-6">
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-2 text-center">6-Digit Security Code</label>
                        <input type="text" name="otp_code" maxlength="6" autofocus autocomplete="one-time-code" pattern="[0-9]*" inputmode="numeric" placeholder="123456" required
                               class="w-full text-center text-3xl tracking-[0.5em] font-mono py-3 px-4 border-2 border-gray-300 rounded-xl focus:border-blue-600 focus:outline-none transition">
                    </div>
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-xl shadow-md transition duration-200 flex items-center justify-center gap-2">
                        <i class="fa fa-check"></i> Verify & Login
                    </button>
                </form>
                <div class="mt-6 pt-4 border-t border-gray-100 text-center">
                    <button type="button" onclick="toggleAuthMode('backup')" class="text-xs font-semibold text-blue-600 hover:underline">
                        <i class="fa fa-key mr-1"></i> Don't have your phone? Use Backup Code
                    </button>
                </div>
            </div>

            <!-- Backup Code Form (Alternate) -->
            <div id="backupSection" class="hidden">
                <p class="text-sm text-gray-600 mb-6 text-center">
                    Enter one of your 8-character emergency backup recovery codes.
                </p>
                <form method="POST">
                    <input type="hidden" name="auth_type" value="backup">
                    <div class="mb-6">
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-2 text-center">Emergency Recovery Code</label>
                        <input type="text" name="backup_code" maxlength="10" placeholder="e.g. 4F8A1B2C" required
                               class="w-full text-center text-xl uppercase font-mono py-3 px-4 border-2 border-gray-300 rounded-xl focus:border-blue-600 focus:outline-none transition">
                    </div>
                    <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-4 rounded-xl shadow-md transition duration-200 flex items-center justify-center gap-2">
                        <i class="fa fa-unlock"></i> Unlock with Backup Code
                    </button>
                </form>
                <div class="mt-6 pt-4 border-t border-gray-100 text-center">
                    <button type="button" onclick="toggleAuthMode('totp')" class="text-xs font-semibold text-blue-600 hover:underline">
                        <i class="fa fa-mobile-screen mr-1"></i> Use Authenticator App Code
                    </button>
                </div>
            </div>

            <div class="mt-4 text-center">
                <a href="logout.php" class="text-xs text-gray-400 hover:text-gray-600">
                    <i class="fa fa-arrow-left mr-1"></i> Cancel and return to Login
                </a>
            </div>
        </div>
    </div>

    <script>
        function toggleAuthMode(mode) {
            const totpSec = document.getElementById('totpSection');
            const backupSec = document.getElementById('backupSection');
            if (mode === 'backup') {
                totpSec.classList.add('hidden');
                backupSec.classList.remove('hidden');
            } else {
                backupSec.classList.add('hidden');
                totpSec.classList.remove('hidden');
            }
        }
    </script>
</body>
</html>
