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
$logo = getSetting('header_logo') ?: 'images/bg.png';

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
            
            // Handle Remember Me if checked on login page
            if (!empty($_SESSION['2fa_pending_remember'])) {
                $cookie_params = session_get_cookie_params();
                setcookie(session_name(), session_id(), time() + (86400 * 30), $cookie_params['path'], $cookie_params['domain'], $cookie_params['secure'], $cookie_params['httponly']);
            }

            session_regenerate_id(true);
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_username'] = $user['username'];
            $_SESSION['admin_role'] = $user['role'] ?? 'admin';
            unset($_SESSION['2fa_pending_admin_id']);
            unset($_SESSION['2fa_pending_remember']);
            
            logActivity('2FA Login with Backup Code', 'User: ' . $user['username']);
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Invalid emergency backup code. Please check and try again.';
        }
    } else {
        // Collect 6 individual digits or combined otp_code
        $otp = trim($_POST['otp_code'] ?? '');
        if (empty($otp) && isset($_POST['otp_digit']) && is_array($_POST['otp_digit'])) {
            $otp = implode('', array_map('trim', $_POST['otp_digit']));
        }
        
        $secret = $user['two_factor_secret'] ?? '';
        
        if (SimpleTOTP::verifyCode($secret, $otp)) {
            if (!empty($_SESSION['2fa_pending_remember'])) {
                $cookie_params = session_get_cookie_params();
                setcookie(session_name(), session_id(), time() + (86400 * 30), $cookie_params['path'], $cookie_params['domain'], $cookie_params['secure'], $cookie_params['httponly']);
            }

            session_regenerate_id(true);
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_username'] = $user['username'];
            $_SESSION['admin_role'] = $user['role'] ?? 'admin';
            unset($_SESSION['2fa_pending_admin_id']);
            unset($_SESSION['2fa_pending_remember']);
            
            logActivity('2FA Login Verified', 'User: ' . $user['username']);
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Invalid 6-digit authentication code. Please check the current code on your authenticator app.';
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
        
        <div class="bg-white/95 backdrop-blur-md rounded-3xl shadow-2xl overflow-hidden border border-gray-100/80 animate-in fade-in zoom-in duration-200">
            
            <!-- Card Header -->
            <div class="bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 p-6 sm:p-7 text-center text-white relative">
                <div class="w-14 h-14 bg-white/20 rounded-2xl flex items-center justify-center mx-auto mb-3 text-2xl backdrop-blur-xs border border-white/30 shadow-inner">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <h1 class="text-xl font-bold">Two-Factor Authentication</h1>
                <p class="text-xs text-blue-100 mt-1">Verifying identity for: <strong class="text-white bg-white/20 px-2 py-0.5 rounded-full"><?php echo htmlspecialchars($user['username']); ?></strong></p>
            </div>

            <div class="p-6 sm:p-8">
                
                <!-- Error Alert -->
                <?php if ($error): ?>
                <div class="bg-rose-50 border border-rose-200 text-rose-800 p-3.5 rounded-xl text-xs font-semibold mb-5 flex items-center gap-2 shadow-xs">
                    <i class="fa-solid fa-triangle-exclamation text-rose-600 text-sm"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
                <?php endif; ?>

                <!-- TOTP 6-Digit App Mode (Default) -->
                <div id="totpSection">
                    <p class="text-xs text-gray-500 mb-6 text-center leading-relaxed">
                        Enter the 6-digit verification code from your authenticator app (Google Authenticator, Authy, Microsoft Authenticator).
                    </p>

                    <form method="POST" id="totpForm" class="space-y-6">
                        <input type="hidden" name="auth_type" value="totp">
                        <input type="hidden" name="otp_code" id="hiddenOtpCode" value="">

                        <!-- 6 Individual Digit Inputs with Auto-jump & Paste handling -->
                        <div class="flex justify-center items-center gap-2 sm:gap-2.5" id="otpContainer">
                            <?php for ($i = 0; $i < 6; $i++): ?>
                            <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]*" class="otp-box w-11 h-13 sm:w-12 sm:h-14 text-center text-2xl font-bold font-mono bg-gray-50 border-2 border-gray-200 rounded-xl focus:bg-white focus:border-blue-600 focus:outline-none focus:ring-4 focus:ring-blue-500/10 transition" data-index="<?php echo $i; ?>" required>
                            <?php endfor; ?>
                        </div>

                        <button type="submit" id="totpSubmitBtn" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-xl shadow-md shadow-blue-500/20 transition flex items-center justify-center gap-2 text-xs sm:text-sm cursor-pointer">
                            <i class="fa-solid fa-circle-check"></i>
                            <span>Verify Code & Login</span>
                        </button>
                    </form>

                    <div class="mt-6 pt-4 border-t border-gray-100 text-center">
                        <button type="button" onclick="toggleAuthMode('backup')" class="text-xs font-semibold text-blue-600 hover:text-blue-800 transition inline-flex items-center gap-1.5 cursor-pointer">
                            <i class="fa-solid fa-key"></i> Lost access to phone? Use Backup Code
                        </button>
                    </div>
                </div>

                <!-- Emergency Backup Code Mode -->
                <div id="backupSection" class="hidden">
                    <p class="text-xs text-gray-500 mb-6 text-center leading-relaxed">
                        Enter one of your 8-character single-use recovery backup codes generated during 2FA setup.
                    </p>

                    <form method="POST" id="backupForm" class="space-y-5">
                        <input type="hidden" name="auth_type" value="backup">
                        
                        <div>
                            <label class="block text-gray-700 text-xs font-bold uppercase tracking-wider mb-1.5 text-center">Recovery Backup Code</label>
                            <input type="text" name="backup_code" maxlength="10" placeholder="e.g. 4F8A1B2C" required
                                   class="w-full text-center text-lg uppercase font-mono tracking-widest py-3 px-4 bg-gray-50 border-2 border-gray-200 rounded-xl focus:bg-white focus:border-purple-600 focus:outline-none focus:ring-4 focus:ring-purple-500/10 transition">
                        </div>

                        <button type="submit" class="w-full bg-purple-600 hover:bg-purple-700 text-white font-bold py-3 px-4 rounded-xl shadow-md shadow-purple-500/20 transition flex items-center justify-center gap-2 text-xs sm:text-sm cursor-pointer">
                            <i class="fa-solid fa-unlock-keyhole"></i>
                            <span>Unlock with Backup Code</span>
                        </button>
                    </form>

                    <div class="mt-6 pt-4 border-t border-gray-100 text-center">
                        <button type="button" onclick="toggleAuthMode('totp')" class="text-xs font-semibold text-blue-600 hover:text-blue-800 transition inline-flex items-center gap-1.5 cursor-pointer">
                            <i class="fa-solid fa-mobile-screen"></i> Return to Authenticator App Code
                        </button>
                    </div>
                </div>

                <div class="mt-4 text-center">
                    <a href="logout.php" class="text-xs text-gray-400 hover:text-gray-600 transition inline-flex items-center gap-1">
                        <i class="fa-solid fa-arrow-left text-[10px]"></i> Cancel and return to Login
                    </a>
                </div>

            </div>

        </div>

    </div>

    <script>
    const otpBoxes = document.querySelectorAll('.otp-box');
    const hiddenOtp = document.getElementById('hiddenOtpCode');
    const totpForm = document.getElementById('totpForm');

    otpBoxes.forEach((box, index) => {
        // Auto-focus first box
        if (index === 0) box.focus();

        box.addEventListener('input', (e) => {
            const val = e.target.value;
            // Only allow digits
            if (!/^\d*$/.test(val)) {
                e.target.value = '';
                return;
            }
            if (val.length === 1) {
                if (index < otpBoxes.length - 1) {
                    otpBoxes[index + 1].focus();
                } else {
                    checkAndAutoSubmit();
                }
            }
        });

        box.addEventListener('keydown', (e) => {
            if (e.key === 'Backspace' && !e.target.value && index > 0) {
                otpBoxes[index - 1].focus();
            }
        });

        // Paste support for full 6 digits
        box.addEventListener('paste', (e) => {
            e.preventDefault();
            const pasteData = (e.clipboardData || window.clipboardData).getData('text').trim();
            if (/^\d{6}$/.test(pasteData)) {
                pasteData.split('').forEach((char, i) => {
                    if (otpBoxes[i]) otpBoxes[i].value = char;
                });
                otpBoxes[5].focus();
                checkAndAutoSubmit();
            }
        });
    });

    function checkAndAutoSubmit() {
        let code = '';
        otpBoxes.forEach(b => code += b.value);
        if (code.length === 6) {
            hiddenOtp.value = code;
            document.getElementById('totpSubmitBtn').disabled = true;
            document.getElementById('totpSubmitBtn').innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Verifying...';
            totpForm.submit();
        }
    }

    totpForm.addEventListener('submit', function(e) {
        let code = '';
        otpBoxes.forEach(b => code += b.value);
        hiddenOtp.value = code;
    });

    function toggleAuthMode(mode) {
        const totpSec = document.getElementById('totpSection');
        const backupSec = document.getElementById('backupSection');
        if (mode === 'backup') {
            totpSec.classList.add('hidden');
            backupSec.classList.remove('hidden');
        } else {
            backupSec.classList.add('hidden');
            totpSec.classList.remove('hidden');
            if (otpBoxes[0]) otpBoxes[0].focus();
        }
    }
    </script>
</body>
</html>
