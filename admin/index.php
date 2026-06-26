<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

if (isAdminLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

function logLoginAttempt($username, $status) {
    global $conn;
    if (!tableExists('login_logs')) return;
    $u = mysqli_real_escape_string($conn, $username);
    $s = $status === 'success' ? 'success' : 'failed';
    $ip = mysqli_real_escape_string($conn, $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    $ua = mysqli_real_escape_string($conn, $_SERVER['HTTP_USER_AGENT'] ?? '');
    @mysqli_query($conn, "INSERT INTO login_logs (username, status, ip_address, user_agent) VALUES ('$u', '$s', '$ip', '$ua')");
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isBannedIP()) {
        $error = 'Too many failed attempts. Please try again after 15 minutes.';
    } else {
        $username = sanitize($_POST['username']);
        $password = $_POST['password'];

        $query = "SELECT * FROM users WHERE username = '$username' AND status = 1 LIMIT 1";
        $result = mysqli_query($conn, $query);

        if ($user = mysqli_fetch_assoc($result)) {
            if (password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['admin_username'] = $user['username'];
                logLoginAttempt($username, 'success');
                header('Location: dashboard.php');
                exit;
            }
        }
        logLoginAttempt($username, 'failed');
        $error = 'Invalid username or password!';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?php echo escSetting('site_name'); ?></title>
    <link rel="shortcut icon" href="../<?php echo htmlspecialchars(escSetting('favicon') ?: 'images/favicon.ico'); ?>" type="image/x-icon" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-blue-600 to-indigo-800 min-h-screen flex items-center justify-center p-4">
<div class="w-full max-w-md">
    <div class="bg-white rounded-2xl shadow-2xl p-8">
        <div class="text-center mb-8">
            <img src="../<?php echo htmlspecialchars(getSetting('header_logo') ?: 'images/bg.webp'); ?>" class="h-12 mx-auto mb-4" alt="<?php echo escSetting('site_name'); ?>">
            <h2 class="text-2xl font-bold text-gray-800">Admin Login</h2>
            <p class="text-gray-500"><?php echo escSetting('site_name'); ?> Management Panel</p>
            </div>
            
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Username</label>
                    <input type="text" name="username" required class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:border-blue-500">
                </div>
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Password</label>
                    <input type="password" name="password" required class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:border-blue-500">
                </div>
                <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700 transition">Login</button>
            </form>
        </div>
    </div>
</body>
</html>
