<?php
$page_title = 'Admin Profile';
require_once '../config/database.php';
require_once '../includes/functions.php';
checkAdminLogin();

$admin_id = $_SESSION['admin_id'];
$msg = '';
$error = '';

$admin = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id = $admin_id"));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCSRFToken($_POST['csrf_token'] ?? '');
    if (isset($_POST['update_info'])) {
        $email = sanitize($_POST['email'] ?? '');
        $username = sanitize($_POST['username'] ?? '');
        if ($email && $username) {
            $check = mysqli_query($conn, "SELECT id FROM users WHERE (username = '$username' OR email = '$email') AND id != $admin_id");
            if (mysqli_num_rows($check) > 0) {
                $error = 'Username or email already taken!';
            } else {
                mysqli_query($conn, "UPDATE users SET username = '$username', email = '$email' WHERE id = $admin_id");
                header('Location: profile.php?s=1');
                exit;
            }
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
            mysqli_query($conn, "UPDATE users SET password = '$hash' WHERE id = $admin_id");
            header('Location: profile.php?s=2');
            exit;
        }
    }
}

if (isset($_GET['s'])) {
    if ($_GET['s'] == 1) $msg = 'Profile updated!';
    elseif ($_GET['s'] == 2) $msg = 'Password changed!';
}
$admin = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id = $admin_id"));
?>
<?php include 'header.php'; ?>
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">Admin Profile</h1>
    <p class="text-gray-500 dark:text-gray-400">Manage your account information and password</p>
</div>

<?php if ($msg): ?><div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 dark:bg-green-900/30 dark:border-green-700 dark:text-green-300"><?php echo $msg; ?></div><?php endif; ?>
<?php if ($error): ?><div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 dark:bg-red-900/30 dark:border-red-700 dark:text-red-300"><?php echo $error; ?></div><?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-lg shadow p-6 dark:bg-gray-800">
        <h2 class="text-lg font-semibold mb-4 flex items-center"><i class="fa fa-user text-blue-600 mr-2 dark:text-blue-400"></i> Account Info</h2>
        <form method="POST">
            <?= csrfField() ?>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-gray-200">Username</label>
                    <input type="text" name="username" value="<?php echo htmlspecialchars($admin['username']); ?>" required class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-gray-200">Email</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($admin['email']); ?>" required class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-gray-200">Member Since</label>
                    <p class="text-gray-500 text-sm dark:text-gray-400"><?php echo date('d M Y, g:i a', strtotime($admin['created_at'])); ?></p>
                </div>
                <button type="submit" name="update_info" class="bg-blue-600 text-white px-5 py-2 rounded hover:bg-blue-700 dark:hover:bg-blue-600"><i class="fa fa-save mr-1"></i> Update Info</button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow p-6 dark:bg-gray-800">
        <h2 class="text-lg font-semibold mb-4 flex items-center"><i class="fa fa-lock text-green-600 mr-2 dark:text-green-400"></i> Change Password</h2>
        <form method="POST">
            <?= csrfField() ?>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-gray-200">Current Password</label>
                    <input type="password" name="current_password" required class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-gray-200">New Password</label>
                    <input type="password" name="new_password" required class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600" oninput="checkPwdStrength(this, 'pwdStrength')">
                    <div id="pwdStrength" class="text-xs mt-1 h-4"></div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-gray-200">Confirm New Password</label>
                    <input type="password" name="confirm_password" required class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600">
                </div>
                <button type="submit" name="change_password" class="bg-green-600 text-white px-5 py-2 rounded hover:bg-green-700 dark:hover:bg-green-600"><i class="fa fa-key mr-1"></i> Change Password</button>
            </div>
        </form>
    </div>
</div>
<script>
function checkPwdStrength(el, targetId) {
    var p = el.value, s = 0, t = document.getElementById(targetId);
    if (!p) { t.innerHTML = ''; return; }
    if (p.length >= 8) s++;
    if (p.length >= 12) s++;
    if (/[A-Z]/.test(p)) s++;
    if (/[0-9]/.test(p)) s++;
    if (/[^A-Za-z0-9]/.test(p)) s++;
    var labels = ['<span class="text-red-500">Very Weak</span>','<span class="text-red-400">Weak</span>','<span class="text-yellow-500">Fair</span>','<span class="text-yellow-400">Good</span>','<span class="text-green-500">Strong</span>','<span class="text-green-400">Very Strong</span>'];
    t.innerHTML = labels[Math.min(s, 5)];
}
</script>
<?php include 'footer.php'; ?>
