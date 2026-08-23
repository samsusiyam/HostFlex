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
    $s = $status === 'success' ? 'success' : 'failed';
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Host Nibo</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center">
        <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-md">
            <div class="text-center mb-8">
                <img src="../images/bg.png" class="h-12 mx-auto mb-4" alt="Host Nibo">
                <h1 class="text-2xl font-bold text-gray-800">Admin Login</h1>
                <p class="text-gray-500">Host Nibo Management Panel</p>
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
