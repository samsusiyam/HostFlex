<?php
$page_title = 'Admin Profile & Security';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/totp.php';
checkAdminLogin();
ensure2FASchema();

$admin_id = $_SESSION['admin_id'];
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
        $email = trim($_POST['email'] ?? '');
        $username = trim($_POST['username'] ?? '');
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
    if ($_GET['s'] == 1) $msg = 'Profile updated successfully!';
    elseif ($_GET['s'] == 2) $msg = 'Password changed successfully!';
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
    $qr_code_url = SimpleTOTP::getQrCodeUrl($site_name, $admin['username'], $temp_secret, 200);
}

if (isset($_SESSION['new_backup_codes'])) {
    $show_backup_codes = $_SESSION['new_backup_codes'];
    unset($_SESSION['new_backup_codes']);
}
?>
<?php include 'header.php'; ?>
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Admin Profile & Security</h1>
    <p class="text-gray-500">Manage your credentials, password, and Two-Factor Authentication (2FA)</p>
</div>

<?php if ($msg): ?><div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4"><?php echo $msg; ?></div><?php endif; ?>
<?php if ($error): ?><div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4"><?php echo $error; ?></div><?php endif; ?>

<?php if (!empty($show_backup_codes)): ?>
<div class="bg-yellow-50 border-2 border-yellow-400 rounded-xl p-6 mb-6 shadow-sm">
    <h3 class="text-lg font-bold text-yellow-800 flex items-center gap-2 mb-2">
        <i class="fa fa-key text-yellow-600"></i> Save Your Emergency Backup Codes
    </h3>
    <p class="text-sm text-yellow-700 mb-4">
        Keep these 8 single-use recovery codes in a safe place. If you lose access to your authenticator app, you can use these to unlock your account. Each code can only be used once.
    </p>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 bg-white p-4 rounded-lg border border-yellow-200 font-mono text-center font-bold text-gray-800 text-base">
        <?php foreach ($show_backup_codes as $bcode): ?>
        <div class="p-2 bg-gray-50 border rounded"><?php echo htmlspecialchars($bcode); ?></div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Account Info -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold mb-4 flex items-center"><i class="fa fa-user text-blue-600 mr-2"></i> Account Info</h2>
        <form method="POST">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                    <input type="text" name="username" value="<?php echo htmlspecialchars($admin['username']); ?>" required class="w-full border rounded px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($admin['email']); ?>" required class="w-full border rounded px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Member Since</label>
                    <p class="text-gray-500 text-sm"><?php echo date('d M Y, g:i a', strtotime($admin['created_at'])); ?></p>
                </div>
                <button type="submit" name="update_info" class="bg-blue-600 text-white px-5 py-2 rounded hover:bg-blue-700 w-full"><i class="fa fa-save mr-1"></i> Update Info</button>
            </div>
        </form>
    </div>

    <!-- Change Password -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold mb-4 flex items-center"><i class="fa fa-lock text-green-600 mr-2"></i> Change Password</h2>
        <form method="POST">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
                    <input type="password" name="current_password" required class="w-full border rounded px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                    <input type="password" name="new_password" required class="w-full border rounded px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                    <input type="password" name="confirm_password" required class="w-full border rounded px-3 py-2">
                </div>
                <button type="submit" name="change_password" class="bg-green-600 text-white px-5 py-2 rounded hover:bg-green-700 w-full"><i class="fa fa-key mr-1"></i> Change Password</button>
            </div>
        </form>
    </div>

    <!-- Two-Factor Authentication (2FA) -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold mb-4 flex items-center justify-between">
            <span class="flex items-center"><i class="fa fa-shield-halved text-purple-600 mr-2"></i> 2-Factor Auth</span>
            <?php if ($is_2fa_enabled): ?>
            <span class="text-xs bg-green-100 text-green-700 font-bold px-2.5 py-1 rounded-full"><i class="fa fa-check-circle"></i> Active</span>
            <?php else: ?>
            <span class="text-xs bg-gray-100 text-gray-500 font-bold px-2.5 py-1 rounded-full">Disabled</span>
            <?php endif; ?>
        </h2>

        <?php if ($is_2fa_enabled): ?>
        <div class="space-y-4">
            <div class="p-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-800">
                <i class="fa fa-shield-check text-green-600 mr-1"></i> Your account is securely protected with TOTP Two-Factor Authentication.
            </div>

            <!-- Regenerate Backup Codes -->
            <form method="POST" onsubmit="return confirm('Regenerate emergency backup recovery codes? Previous codes will become invalid.')" class="border-t pt-3">
                <label class="block text-xs font-bold text-gray-700 mb-1">Regenerate Backup Codes</label>
                <div class="flex gap-2">
                    <input type="password" name="regen_password" placeholder="Current Password" required class="flex-1 border rounded px-3 py-1.5 text-sm">
                    <button type="submit" name="regen_backup_codes" class="bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1.5 rounded text-sm whitespace-nowrap">
                        <i class="fa fa-sync-alt"></i> Generate
                    </button>
                </div>
            </form>

            <!-- Disable 2FA Form -->
            <form method="POST" onsubmit="return confirm('Are you sure you want to disable Two-Factor Authentication? Your account will be less secure.')" class="border-t pt-3">
                <label class="block text-xs font-bold text-red-600 mb-1">Disable 2FA</label>
                <div class="flex gap-2">
                    <input type="password" name="disable_password" placeholder="Confirm Password" required class="flex-1 border rounded px-3 py-1.5 text-sm">
                    <button type="submit" name="disable_2fa" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1.5 rounded text-sm whitespace-nowrap">
                        <i class="fa fa-ban"></i> Disable
                    </button>
                </div>
            </form>
        </div>

        <?php else: ?>
        <!-- Setup 2FA Form -->
        <div class="space-y-4">
            <p class="text-xs text-gray-600">Scan this QR code with Google Authenticator, Authy, or Microsoft Authenticator app:</p>
            <div class="flex justify-center bg-gray-50 p-3 rounded-lg border">
                <img src="<?php echo htmlspecialchars($qr_code_url); ?>" alt="2FA QR Code" class="w-40 h-40">
            </div>
            <div class="bg-gray-100 p-2 rounded text-center">
                <span class="text-[10px] text-gray-500 uppercase block font-bold">Manual Entry Key</span>
                <code class="font-mono text-xs text-blue-700 font-bold select-all"><?php echo htmlspecialchars($temp_secret); ?></code>
            </div>

            <form method="POST" class="space-y-2">
                <label class="block text-xs font-bold text-gray-700">Enter 6-Digit App Code to Activate</label>
                <div class="flex gap-2">
                    <input type="text" name="verify_code" maxlength="6" pattern="[0-9]*" placeholder="123456" required class="flex-1 border rounded px-3 py-2 text-center font-mono font-bold tracking-widest text-lg">
                    <button type="submit" name="enable_2fa" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded font-semibold text-sm">
                        <i class="fa fa-check"></i> Activate
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php include 'footer.php'; ?>
