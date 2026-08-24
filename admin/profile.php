<?php
$page_title = 'Admin Profile & Security';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/totp.php';
checkAdminLogin();
ensure2FASchema();

$admin_id = (int)$_SESSION['admin_id'];
$msg = '';
$error = '';
$show_backup_codes = [];

$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $admin_id);
mysqli_stmt_execute($stmt);
$admin = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

// Handle 2FA Setup or Disable
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_info'])) {
        $email = sanitize(trim($_POST['email'] ?? ''));
        $username = sanitize(trim($_POST['username'] ?? ''));
        if ($email && $username) {
            $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
            mysqli_stmt_bind_param($stmt, "ssi", $username, $email, $admin_id);
            mysqli_stmt_execute($stmt);
            $check = mysqli_stmt_get_result($stmt);
            if (mysqli_num_rows($check) > 0) {
                $error = 'Username or email already taken!';
            } else {
                mysqli_stmt_close($stmt);
                $up = mysqli_prepare($conn, "UPDATE users SET username = ?, email = ? WHERE id = ?");
                mysqli_stmt_bind_param($up, "ssi", $username, $email, $admin_id);
                mysqli_stmt_execute($up);
                mysqli_stmt_close($up);
                $_SESSION['admin_username'] = $username;
                logActivity('Updated Profile', 'Username: ' . $username);
                header('Location: profile.php?s=1');
                exit;
            }
            mysqli_stmt_close($stmt);
        } else {
            $error = 'All fields are required!';
        }
    }

    if (isset($_POST['change_password'])) {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        if (!$current || !$new || !$confirm) {
            $error = 'All password fields are required!';
        } elseif ($new !== $confirm) {
            $error = 'New passwords do not match!';
        } elseif (strlen($new) < 6) {
            $error = 'Password must be at least 6 characters!';
        } elseif (!password_verify($current, $admin['password'])) {
            $error = 'Current password is incorrect!';
        } else {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $up = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE id = ?");
            mysqli_stmt_bind_param($up, "si", $hash, $admin_id);
            mysqli_stmt_execute($up);
            mysqli_stmt_close($up);
            logActivity('Changed Password', 'Admin ID: ' . $admin_id);
            header('Location: profile.php?s=2');
            exit;
        }
    }

    // Enable 2FA Verification
    if (isset($_POST['enable_2fa'])) {
        $code = trim($_POST['verify_code'] ?? '');
        $temp_secret = $_SESSION['temp_2fa_secret'] ?? '';

        if (!$temp_secret || !SimpleTOTP::verifyCode($temp_secret, $code)) {
            $error = 'Invalid 6-digit verification code. Please make sure your authenticator app time is synchronized.';
        } else {
            $backup_codes = SimpleTOTP::generateBackupCodes(8);
            $backup_json = mysqli_real_escape_string($conn, json_encode($backup_codes));
            $temp_secret_esc = mysqli_real_escape_string($conn, $temp_secret);

            mysqli_query($conn, "UPDATE users SET two_factor_enabled = 1, two_factor_secret = '$temp_secret_esc', two_factor_backup_codes = '$backup_json' WHERE id = $admin_id");
            unset($_SESSION['temp_2fa_secret']);
            logActivity('Enabled 2FA', 'Admin ID: ' . $admin_id);

            $_SESSION['new_backup_codes'] = $backup_codes;
            header('Location: profile.php?s=3');
            exit;
        }
    }

    // Disable 2FA
    if (isset($_POST['disable_2fa'])) {
        $password = $_POST['disable_password'] ?? '';
        if (!password_verify($password, $admin['password'])) {
            $error = 'Incorrect password! Cannot disable Two-Factor Authentication.';
        } else {
            mysqli_query($conn, "UPDATE users SET two_factor_enabled = 0, two_factor_secret = NULL, two_factor_backup_codes = NULL WHERE id = $admin_id");
            logActivity('Disabled 2FA', 'Admin ID: ' . $admin_id);
            header('Location: profile.php?s=4');
            exit;
        }
    }

    // Regenerate Backup Codes
    if (isset($_POST['regen_backup_codes'])) {
        $password = $_POST['regen_password'] ?? '';
        if (!password_verify($password, $admin['password'])) {
            $error = 'Incorrect password! Cannot regenerate backup codes.';
        } else {
            $backup_codes = SimpleTOTP::generateBackupCodes(8);
            $backup_json = mysqli_real_escape_string($conn, json_encode($backup_codes));
            mysqli_query($conn, "UPDATE users SET two_factor_backup_codes = '$backup_json' WHERE id = $admin_id");
            $_SESSION['new_backup_codes'] = $backup_codes;
            header('Location: profile.php?s=5');
            exit;
        }
    }
}

if (isset($_GET['s'])) {
    if ($_GET['s'] == 1) $msg = 'Profile details updated successfully!';
    elseif ($_GET['s'] == 2) $msg = 'Account password changed successfully!';
    elseif ($_GET['s'] == 3) $msg = 'Two-Factor Authentication is now ENABLED! Please save your emergency backup codes below.';
    elseif ($_GET['s'] == 4) $msg = 'Two-Factor Authentication has been DISABLED.';
    elseif ($_GET['s'] == 5) $msg = 'New backup recovery codes generated successfully!';
}

$admin = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id = $admin_id"));
$is_2fa_enabled = !empty($admin['two_factor_enabled']);

if (!$is_2fa_enabled) {
    if (empty($_SESSION['temp_2fa_secret'])) {
        $_SESSION['temp_2fa_secret'] = SimpleTOTP::generateSecret(16);
    }
    $temp_secret = $_SESSION['temp_2fa_secret'];
    $site_name = getSetting('site_name') ?: 'Host Nibo';
    $qr_code_url = SimpleTOTP::getQrCodeUrl($site_name, $admin['username'], $temp_secret, 220);
}

if (isset($_SESSION['new_backup_codes'])) {
    $show_backup_codes = $_SESSION['new_backup_codes'];
    unset($_SESSION['new_backup_codes']);
}
?>
<?php include 'header.php'; ?>

<div class="space-y-6">
    
    <!-- Page Header Banner -->
    <div class="bg-white p-6 rounded-2xl border border-gray-200/80 shadow-xs flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white font-bold flex items-center justify-center text-xl shadow-md shadow-blue-500/20 shrink-0">
                <?php echo strtoupper(substr($admin['username'] ?? 'A', 0, 2)); ?>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($admin['username']); ?></h1>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200 uppercase tracking-wider">
                        <?php echo htmlspecialchars($admin['role'] ?? 'Administrator'); ?>
                    </span>
                </div>
                <p class="text-xs text-gray-500 mt-0.5"><?php echo htmlspecialchars($admin['email']); ?> • Member since <?php echo date('M Y', strtotime($admin['created_at'])); ?></p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold <?php echo $is_2fa_enabled ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200'; ?>">
                <i class="fa-solid <?php echo $is_2fa_enabled ? 'fa-shield-check text-emerald-500' : 'fa-triangle-exclamation text-amber-500'; ?>"></i>
                <span>2FA: <?php echo $is_2fa_enabled ? 'Active' : 'Disabled'; ?></span>
            </span>
        </div>
    </div>

    <!-- Alert Notifications -->
    <?php if ($msg): ?>
    <div class="p-4 rounded-xl text-xs font-semibold flex items-center justify-between bg-emerald-50 text-emerald-800 border border-emerald-200 shadow-xs">
        <div class="flex items-center gap-2">
            <i class="fa-solid fa-circle-check text-emerald-600 text-sm"></i>
            <span><?php echo htmlspecialchars($msg); ?></span>
        </div>
        <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700 cursor-pointer"><i class="fa-solid fa-xmark text-sm"></i></button>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="p-4 rounded-xl text-xs font-semibold flex items-center justify-between bg-rose-50 text-rose-800 border border-rose-200 shadow-xs">
        <div class="flex items-center gap-2">
            <i class="fa-solid fa-triangle-exclamation text-rose-600 text-sm"></i>
            <span><?php echo htmlspecialchars($error); ?></span>
        </div>
        <button onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700 cursor-pointer"><i class="fa-solid fa-xmark text-sm"></i></button>
    </div>
    <?php endif; ?>

    <!-- Newly Generated Backup Codes Highlight Box -->
    <?php if (!empty($show_backup_codes)): ?>
    <div class="bg-amber-50/90 border-2 border-amber-400/80 rounded-2xl p-6 shadow-sm animate-in fade-in duration-200">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-3">
            <h3 class="text-sm font-bold text-amber-950 flex items-center gap-2">
                <i class="fa-solid fa-key text-amber-600 text-base"></i> Emergency Backup Recovery Codes
            </h3>
            <div class="flex items-center gap-2">
                <button type="button" onclick="copyAllBackupCodes()" class="bg-white border border-amber-300 hover:bg-amber-100 text-amber-900 text-xs font-bold px-3 py-1.5 rounded-lg transition shadow-xs flex items-center gap-1.5 cursor-pointer">
                    <i class="fa-regular fa-copy"></i> Copy All
                </button>
                <button type="button" onclick="downloadBackupCodes()" class="bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold px-3 py-1.5 rounded-lg transition shadow-xs flex items-center gap-1.5 cursor-pointer">
                    <i class="fa-solid fa-download"></i> Download .txt
                </button>
            </div>
        </div>
        <p class="text-xs text-amber-800 mb-4 leading-relaxed">
            Store these 8 single-use recovery codes in a secure location (e.g. password manager). If you lose your phone or authenticator app, each code can unlock your account once.
        </p>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 bg-white p-4 rounded-xl border border-amber-200 font-mono text-center font-bold text-gray-800 text-xs sm:text-sm shadow-inner" id="backupCodesGrid">
            <?php foreach ($show_backup_codes as $bcode): ?>
            <div class="p-2 bg-gray-50 border border-gray-200 rounded-lg select-all"><?php echo htmlspecialchars($bcode); ?></div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- 3 Core Security Panels -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- 1. Account Details -->
        <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs p-6 flex flex-col justify-between">
            <div>
                <div class="flex items-center gap-2 mb-4 pb-3 border-b border-gray-100">
                    <span class="p-2 bg-blue-50 text-blue-600 rounded-lg text-xs"><i class="fa-solid fa-user-pen"></i></span>
                    <h2 class="text-sm font-bold text-gray-900">Profile Details</h2>
                </div>
                <form method="POST" class="space-y-4 text-xs">
                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Username</label>
                        <input type="text" name="username" value="<?php echo htmlspecialchars($admin['username']); ?>" required class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none">
                    </div>
                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Email Address</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($admin['email']); ?>" required class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none">
                    </div>
                    <div class="pt-2">
                        <button type="submit" name="update_info" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-4 rounded-xl shadow-xs transition flex items-center justify-center gap-2 text-xs cursor-pointer">
                            <i class="fa-solid fa-floppy-disk"></i> Update Info
                        </button>
                    </div>
                </form>
            </div>
            <div class="mt-4 pt-3 border-t border-gray-100 text-[11px] text-gray-400 text-center">
                User ID #<?php echo $admin['id']; ?> • Account Active
            </div>
        </div>

        <!-- 2. Change Password -->
        <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs p-6 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-gray-100">
                    <div class="flex items-center gap-2">
                        <span class="p-2 bg-emerald-50 text-emerald-600 rounded-lg text-xs"><i class="fa-solid fa-lock"></i></span>
                        <h2 class="text-sm font-bold text-gray-900">Change Password</h2>
                    </div>
                    <button type="button" onclick="generateProfilePassword()" class="text-[11px] font-bold text-blue-600 hover:text-blue-800 transition cursor-pointer flex items-center gap-1">
                        <i class="fa-solid fa-wand-magic-sparkles"></i> Generate
                    </button>
                </div>
                <form method="POST" class="space-y-3.5 text-xs">
                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Current Password</label>
                        <input type="password" name="current_password" required placeholder="••••••••" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none">
                    </div>
                    <div>
                        <label class="block font-bold text-gray-700 mb-1">New Password</label>
                        <div class="relative">
                            <input type="password" name="new_password" id="newPassInput" required placeholder="Minimum 6 characters" class="w-full border border-gray-300 rounded-xl px-3 pr-8 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none" onkeyup="checkPassStrength(this.value)">
                            <button type="button" onclick="togglePassField('newPassInput', this)" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 text-xs cursor-pointer" tabindex="-1">
                                <i class="fa-regular fa-eye"></i>
                            </button>
                        </div>
                        <div class="w-full bg-gray-100 h-1 rounded-full mt-1.5 overflow-hidden">
                            <div id="passStrengthBar" class="h-full bg-gray-300 transition-all duration-300" style="width: 0%;"></div>
                        </div>
                    </div>
                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Confirm New Password</label>
                        <input type="password" name="confirm_password" id="confirmPassInput" required placeholder="Re-enter new password" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none">
                    </div>
                    <div class="pt-2">
                        <button type="submit" name="change_password" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2.5 px-4 rounded-xl shadow-xs transition flex items-center justify-center gap-2 text-xs cursor-pointer">
                            <i class="fa-solid fa-key"></i> Change Password
                        </button>
                    </div>
                </form>
            </div>
            <div class="mt-4 pt-3 border-t border-gray-100 text-[11px] text-gray-400 text-center">
                Securely encrypted using bcrypt
            </div>
        </div>

        <!-- 3. Two-Factor Authentication (2FA) Hub -->
        <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs p-6 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-gray-100">
                    <div class="flex items-center gap-2">
                        <span class="p-2 bg-purple-50 text-purple-600 rounded-lg text-xs"><i class="fa-solid fa-shield-halved"></i></span>
                        <h2 class="text-sm font-bold text-gray-900">2-Factor Auth</h2>
                    </div>
                    <?php if ($is_2fa_enabled): ?>
                    <span class="text-[10px] bg-emerald-50 text-emerald-700 font-bold px-2 py-0.5 rounded-full border border-emerald-200">Protected</span>
                    <?php else: ?>
                    <span class="text-[10px] bg-gray-100 text-gray-500 font-bold px-2 py-0.5 rounded-full">Inactive</span>
                    <?php endif; ?>
                </div>

                <?php if ($is_2fa_enabled): ?>
                <!-- 2FA Active View -->
                <div class="space-y-4 text-xs">
                    <div class="p-3.5 bg-emerald-50/70 border border-emerald-200 rounded-xl text-emerald-800">
                        <div class="font-bold flex items-center gap-1.5 mb-1">
                            <i class="fa-solid fa-circle-check text-emerald-600"></i> TOTP Protection Active
                        </div>
                        <p class="text-[11px] text-emerald-700 leading-relaxed">
                            Your admin account is fortified. An authenticator code is mandatory at every login.
                        </p>
                    </div>

                    <!-- Actions -->
                    <div class="space-y-2 pt-2">
                        <button type="button" onclick="openRegenModal()" class="w-full bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 font-bold py-2 px-3 rounded-xl transition flex items-center justify-center gap-1.5 text-xs cursor-pointer">
                            <i class="fa-solid fa-rotate"></i> Regenerate Backup Codes
                        </button>
                        <button type="button" onclick="openDisable2FAModal()" class="w-full bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 font-bold py-2 px-3 rounded-xl transition flex items-center justify-center gap-1.5 text-xs cursor-pointer">
                            <i class="fa-solid fa-ban"></i> Disable 2FA
                        </button>
                    </div>
                </div>

                <?php else: ?>
                <!-- 2FA Setup View -->
                <div class="space-y-3.5 text-xs">
                    <p class="text-gray-500 leading-relaxed">
                        Scan this QR code with Google Authenticator, Authy, or Microsoft Authenticator:
                    </p>
                    <div class="flex justify-center bg-gray-50 p-2.5 rounded-xl border border-gray-200">
                        <img src="<?php echo htmlspecialchars($qr_code_url); ?>" alt="2FA QR Code" class="w-36 h-36 rounded-lg">
                    </div>
                    <div class="bg-gray-50 p-2 rounded-xl border border-gray-200 text-center">
                        <span class="text-[10px] text-gray-400 font-bold uppercase block">Manual Secret Key</span>
                        <code class="font-mono text-xs text-blue-700 font-bold select-all"><?php echo htmlspecialchars($temp_secret); ?></code>
                    </div>

                    <form method="POST" class="space-y-2 pt-1">
                        <label class="block font-bold text-gray-700">Enter 6-Digit App Code to Activate</label>
                        <div class="flex gap-2">
                            <input type="text" name="verify_code" maxlength="6" pattern="[0-9]*" placeholder="123456" required class="flex-1 border border-gray-300 rounded-xl px-3 py-2 text-center font-mono font-bold tracking-widest text-sm focus:ring-1 focus:ring-purple-500 focus:border-purple-500 outline-none">
                            <button type="submit" name="enable_2fa" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-xl font-bold transition text-xs shadow-xs cursor-pointer">
                                Activate
                            </button>
                        </div>
                    </form>
                </div>
                <?php endif; ?>
            </div>
            <div class="mt-4 pt-3 border-t border-gray-100 text-[11px] text-gray-400 text-center">
                RFC 6238 TOTP Standard
            </div>
        </div>

    </div>

</div>

<!-- ==========================================
     POPUP MODAL: REGENERATE BACKUP CODES
=============================================== -->
<div id="regenModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden border border-gray-100 animate-in fade-in duration-200">
        <form method="POST">
            <div class="p-6 text-center">
                <div class="w-14 h-14 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-2xl mx-auto mb-4">
                    <i class="fa-solid fa-key"></i>
                </div>
                <h3 class="text-base font-bold text-gray-900 mb-1">Regenerate Backup Codes</h3>
                <p class="text-xs text-gray-500 mb-4">Previous backup recovery codes will immediately expire. Confirm with your password:</p>
                <input type="password" name="regen_password" required placeholder="Enter your current password" class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-xs text-center focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 outline-none mb-2">
            </div>
            <div class="flex items-center justify-end gap-2 px-6 py-3.5 border-t bg-gray-50">
                <button type="button" onclick="closeRegenModal()" class="px-4 py-2 text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-xl font-bold transition text-xs cursor-pointer">Cancel</button>
                <button type="submit" name="regen_backup_codes" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold transition text-xs flex items-center gap-1.5 shadow-xs cursor-pointer">
                    <i class="fa-solid fa-rotate"></i> Generate New Codes
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ==========================================
     POPUP MODAL: DISABLE 2FA
=============================================== -->
<div id="disable2FAModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden border border-gray-100 animate-in fade-in duration-200">
        <form method="POST">
            <div class="p-6 text-center">
                <div class="w-14 h-14 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center text-2xl mx-auto mb-4">
                    <i class="fa-solid fa-shield-slash"></i>
                </div>
                <h3 class="text-base font-bold text-gray-900 mb-1">Disable 2-Factor Authentication?</h3>
                <p class="text-xs text-rose-600 mb-4">Warning: Your account will be less secure against unauthorized access. Enter your password to proceed:</p>
                <input type="password" name="disable_password" required placeholder="Enter your current password" class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-xs text-center focus:ring-1 focus:ring-rose-500 focus:border-rose-500 outline-none mb-2">
            </div>
            <div class="flex items-center justify-end gap-2 px-6 py-3.5 border-t bg-gray-50">
                <button type="button" onclick="closeDisable2FAModal()" class="px-4 py-2 text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-xl font-bold transition text-xs cursor-pointer">Cancel</button>
                <button type="submit" name="disable_2fa" class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl font-bold transition text-xs flex items-center gap-1.5 shadow-xs cursor-pointer">
                    <i class="fa-solid fa-ban"></i> Yes, Disable 2FA
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function generateProfilePassword() {
    var chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%&*_-";
    var pass = "";
    for (var i = 0; i < 14; i++) {
        pass += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    document.getElementById('newPassInput').value = pass;
    document.getElementById('newPassInput').type = 'text';
    document.getElementById('confirmPassInput').value = pass;
    checkPassStrength(pass);
}

function checkPassStrength(val) {
    var bar = document.getElementById('passStrengthBar');
    var score = 0;
    if (val.length >= 6) score += 25;
    if (val.length >= 10) score += 25;
    if (/[A-Z]/.test(val) && /[a-z]/.test(val)) score += 25;
    if (/[0-9]/.test(val) && /[^A-Za-z0-9]/.test(val)) score += 25;

    bar.style.width = score + '%';
    if (score <= 25) {
        bar.className = 'h-full bg-rose-500 transition-all duration-300';
    } else if (score <= 50) {
        bar.className = 'h-full bg-amber-500 transition-all duration-300';
    } else if (score <= 75) {
        bar.className = 'h-full bg-blue-500 transition-all duration-300';
    } else {
        bar.className = 'h-full bg-emerald-500 transition-all duration-300';
    }
}

function togglePassField(inputId, btn) {
    var input = document.getElementById(inputId);
    var icon = btn.querySelector('i');
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

function copyAllBackupCodes() {
    var grid = document.getElementById('backupCodesGrid');
    var codes = Array.from(grid.children).map(c => c.innerText.trim()).join('\n');
    navigator.clipboard.writeText(codes).then(() => {
        alert('All 8 recovery backup codes copied to clipboard!');
    });
}

function downloadBackupCodes() {
    var grid = document.getElementById('backupCodesGrid');
    var codes = Array.from(grid.children).map(c => c.innerText.trim()).join('\n');
    var text = "HOST NIBO - 2FA EMERGENCY BACKUP CODES\nGenerated: " + new Date().toISOString() + "\n\n" + codes + "\n\nKeep these codes safe. Each code can only be used once.";
    var blob = new Blob([text], { type: "text/plain;charset=utf-8" });
    var a = document.createElement("a");
    a.href = URL.createObjectURL(blob);
    a.download = "hostnibo-2fa-backup-codes.txt";
    a.click();
}

function openRegenModal() {
    document.getElementById('regenModal').classList.remove('hidden');
}

function closeRegenModal() {
    document.getElementById('regenModal').classList.add('hidden');
}

function openDisable2FAModal() {
    document.getElementById('disable2FAModal').classList.remove('hidden');
}

function closeDisable2FAModal() {
    document.getElementById('disable2FAModal').classList.add('hidden');
}

// Keyboard ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeRegenModal();
        closeDisable2FAModal();
    }
});
</script>

<?php include 'footer.php'; ?>
