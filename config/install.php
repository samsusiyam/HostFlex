<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Redirect if fully installed (skip during step 3 = installation in progress)
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
if ($step !== 3) {
    $dbFile = __DIR__ . '/database.php';
    if (file_exists($dbFile)) {
        @include_once $dbFile;
        if (!empty($conn) && @mysqli_ping($conn)) {
            $required = ['users', 'menu_items', 'settings', 'hosting_plans', 'pages'];
            $allExist = true;
            foreach ($required as $tbl) {
                $r = mysqli_query($conn, "SHOW TABLES LIKE '$tbl'");
                if (!$r || mysqli_num_rows($r) == 0) { $allExist = false; break; }
            }
            if ($allExist) {
                header('Location: ../index.php');
                exit;
            }
        }
    }
}

$step = max(1, min(3, $step));
$error = '';
$success = '';

function testDbConnection($host, $user, $pass, $name) {
    $conn = @mysqli_connect($host, $user, $pass);
    if (!$conn) return 'Could not connect to MySQL: ' . mysqli_connect_error();
    if (!@mysqli_select_db($conn, $name)) return 'Database "' . htmlspecialchars($name) . '" not found. Create it first and try again.';
    $r = @mysqli_query($conn, 'SELECT 1');
    if (!$r) return 'Database connected but query failed: ' . mysqli_error($conn);
    $r = @mysqli_query($conn, "SHOW TABLES");
    if ($r && mysqli_num_rows($r) > 0) {
        return 'Database exists and has ' . mysqli_num_rows($r) . ' existing table(s). Installation will add missing tables.';
    }
    mysqli_close($conn);
    return 'OK';
}

function writeDatabaseConfig($host, $user, $pass, $name) {
    $content = '<?php' . "\n";
    $content .= 'session_start();' . "\n\n";
    $content .= '$db_host = ' . var_export($host, true) . ";\n";
    $content .= '$db_user = ' . var_export($user, true) . ";\n";
    $content .= '$db_pass = ' . var_export($pass, true) . ";\n";
    $content .= '$db_name = ' . var_export($name, true) . ";\n\n";
    $content .= 'try {' . "\n";
    $content .= '    $conn = @mysqli_connect($db_host, $db_user, $db_pass, $db_name);' . "\n";
    $content .= '    if ($conn) {' . "\n";
    $content .= '        mysqli_set_charset($conn, "utf8");' . "\n";
    $content .= '    }' . "\n";
    $content .= '} catch (Throwable $e) {' . "\n";
    $content .= '    $conn = null;' . "\n";
    $content .= '}' . "\n";
    return file_put_contents(__DIR__ . '/database.php', $content);
}

function getDbConn() {
    include __DIR__ . '/database.php';
    global $conn;
    return $conn;
}

function runQueries($conn, $sql, &$log) {
    if (!$conn) { $log[] = 'ERROR: No database connection.'; return; }
    $sql = preg_replace('/^CREATE DATABASE.*$/m', '', $sql);
    $sql = preg_replace('/^USE .*$/m', '', $sql);
    $queries = preg_split('/;\s*\n/', $sql);
    foreach ($queries as $q) {
        $q = trim($q);
        if (empty($q)) continue;
        if (@mysqli_query($conn, $q)) {
            $short = substr($q, 0, 70);
            $log[] = 'OK: ' . $short . '...';
        } else {
            $err = mysqli_error($conn);
            if (stripos($err, 'already exists') !== false || stripos($err, 'Duplicate') !== false) {
                $log[] = 'SKIP (exists): ' . substr($q, 0, 60) . '...';
            } else {
                $log[] = 'ERROR: ' . $err;
            }
        }
    }
}

if ($step === 2 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = trim($_POST['db_host'] ?? 'localhost');
    $user = trim($_POST['db_user'] ?? 'root');
    $pass = $_POST['db_pass'] ?? '';
    $name = trim($_POST['db_name'] ?? '');

    $test = testDbConnection($host, $user, $pass, $name);
    if (strpos($test, 'OK') === false && strpos($test, 'existing table') === false) {
        $error = $test;
        $step = 1;
    } else {
        if (!writeDatabaseConfig($host, $user, $pass, $name)) {
            $error = 'Cannot write config/database.php. Check directory permissions.';
            $step = 1;
        } else {
            // Verify the written config by making a fresh direct connection
            $v = @mysqli_connect($host, $user, $pass, $name);
            if (!$v || !@mysqli_ping($v)) {
                $error = 'Database config written but connection failed. Check credentials and try again.';
                $step = 1;
                @unlink(__DIR__ . '/database.php');
            } else {
                mysqli_close($v);
            }
        }
    }
}

if ($step === 3 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $site_name = trim($_POST['site_name'] ?? 'HostFlex');
    $site_tagline = trim($_POST['site_tagline'] ?? 'Fast & Reliable Web Hosting');
    $site_description = trim($_POST['site_description'] ?? 'Take your website to the next level with affordable and reliable hosting solutions.');
    $site_email = trim($_POST['site_email'] ?? 'support@hostflex.com');
    $admin_user = trim($_POST['admin_user'] ?? 'admin');
    $admin_email = trim($_POST['admin_email'] ?? 'admin@hostflex.com');
    $admin_pass = $_POST['admin_pass'] ?? '';
    $admin_pass2 = $_POST['admin_pass2'] ?? '';

    if (!$admin_pass || $admin_pass !== $admin_pass2) $error = 'Passwords do not match or are empty.';
    elseif (strlen($admin_pass) < 4) $error = 'Password must be at least 4 characters.';
    else {
        $conn = getDbConn();
        if (!$conn || !@mysqli_ping($conn)) $error = 'Database connection failed. Please go back to Step 1 and verify credentials.';
        else {
        $log = [];
        $sql_schema = file_get_contents(__DIR__ . '/../database.sql');
        runQueries($conn, $sql_schema, $log);

        $hashed = password_hash($admin_pass, PASSWORD_DEFAULT);
        $admin_user_e = mysqli_real_escape_string($conn, $admin_user);
        $admin_email_e = mysqli_real_escape_string($conn, $admin_email);
        mysqli_query($conn, "UPDATE users SET username='$admin_user_e', email='$admin_email_e', password='$hashed' WHERE username='admin'");

        $settings_map = [
            'site_name' => $site_name,
            'site_tagline' => $site_tagline,
            'site_description' => $site_description,
            'site_email' => $site_email,
            'footer_copyright' => 'Copyright ' . date('Y') . ' ' . $site_name . '. All rights reserved.',
            'meta_author' => $site_name,
            'footer_description' => 'Premium web hosting solutions from ' . $site_name,
        ];
        foreach ($settings_map as $key => $val) {
            $ve = mysqli_real_escape_string($conn, $val);
            mysqli_query($conn, "UPDATE settings SET setting_value='$ve' WHERE setting_key='$key'");
        }

        $migrate_sql = [
            "ALTER TABLE users ADD COLUMN role ENUM('admin','editor','manager') NOT NULL DEFAULT 'admin' AFTER password",
            "ALTER TABLE users ADD COLUMN status TINYINT(1) NOT NULL DEFAULT 1 AFTER role",
        ];
        foreach ($migrate_sql as $q) {
            try {
                @mysqli_query($conn, $q);
            } catch (Throwable $e) {}
        }

        $log[] = 'Admin user created: ' . htmlspecialchars($admin_user);
        $log[] = 'Installation complete!';
        $success = implode("\n", $log);
        }
    }
}

$title = $step === 1 ? 'Database Setup' : ($step === 2 ? 'Site Configuration' : 'Installing...');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Install - <?php echo $title; ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen flex items-center justify-center p-4">
<div class="w-full max-w-2xl bg-white rounded-2xl shadow-xl overflow-hidden">
    <div class="bg-gradient-to-r from-blue-600 to-indigo-700 px-8 py-6 text-center">
        <h1 class="text-2xl font-bold text-white">HostFlex Setup Wizard</h1>
        <p class="text-blue-200 mt-1 text-sm">Configure your hosting panel</p>
    </div>

    <div class="px-8 py-2 bg-gray-50 border-b flex items-center justify-between text-xs text-gray-500">
        <span class="<?php echo $step >= 1 ? 'text-blue-600 font-semibold' : ''; ?>"><i class="fas fa-database mr-1"></i> Database</span>
        <span class="text-gray-300"><i class="fas fa-chevron-right"></i></span>
        <span class="<?php echo $step >= 2 ? 'text-blue-600 font-semibold' : ''; ?>"><i class="fas fa-cog mr-1"></i> Site &amp; Admin</span>
        <span class="text-gray-300"><i class="fas fa-chevron-right"></i></span>
        <span class="<?php echo $step >= 3 ? 'text-blue-600 font-semibold' : ''; ?>"><i class="fas fa-check-circle mr-1"></i> Install</span>
    </div>

    <?php if ($error): ?>
    <div class="mx-8 mt-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm"><i class="fas fa-exclamation-triangle mr-2"></i> <?php echo $error; ?></div>
    <?php endif; ?>

    <div class="p-8">
        <?php if ($step === 1): ?>
        <form method="post" action="?step=2" class="space-y-4">
            <h2 class="text-lg font-semibold text-gray-800 mb-4"><i class="fas fa-database text-blue-600 mr-2"></i>Database Connection</h2>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Database Host</label>
                <input type="text" name="db_host" value="localhost" required class="w-full border rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                    <input type="text" name="db_user" value="root" required class="w-full border rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <input type="password" name="db_pass" class="w-full border rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Database Name</label>
                <input type="text" name="db_name" value="hostflex" required class="w-full border rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                <p class="text-xs text-gray-400 mt-1">Create the database in your MySQL server first, then enter its name here.</p>
            </div>
            <div class="flex justify-end pt-4">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-6 py-2.5 rounded-lg transition">
                    Next Step <i class="fas fa-arrow-right ml-2"></i>
                </button>
            </div>
        </form>

        <?php elseif ($step === 2): ?>
        <form method="post" action="?step=3" class="space-y-4">
            <h2 class="text-lg font-semibold text-gray-800 mb-4"><i class="fas fa-globe text-blue-600 mr-2"></i>Site Information</h2>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Site Name</label>
                    <input type="text" name="site_name" value="HostFlex" required class="w-full border rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Site Email</label>
                    <input type="email" name="site_email" value="support@hostflex.com" required class="w-full border rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tagline</label>
                <input type="text" name="site_tagline" value="Fast & Reliable Web Hosting" class="w-full border rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea name="site_description" rows="2" class="w-full border rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">Take your website to the next level with affordable and reliable hosting solutions.</textarea>
            </div>

            <hr class="my-6">

            <h2 class="text-lg font-semibold text-gray-800 mb-4"><i class="fas fa-user-shield text-blue-600 mr-2"></i>Admin Account</h2>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                    <input type="text" name="admin_user" value="admin" required class="w-full border rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="admin_email" value="admin@hostflex.com" required class="w-full border rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <input type="password" name="admin_pass" required minlength="4" class="w-full border rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                    <input type="password" name="admin_pass2" required minlength="4" class="w-full border rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
            </div>
            <div class="flex justify-between pt-4">
                <a href="?step=1" class="text-gray-500 hover:text-gray-700 font-medium px-4 py-2.5"><i class="fas fa-arrow-left mr-2"></i> Back</a>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-6 py-2.5 rounded-lg transition" onclick="this.disabled=true;this.innerHTML='Installing... <i class=\'fas fa-spinner fa-spin ml-2\'></i>';this.form.submit();">
                    Install <i class="fas fa-rocket ml-2"></i>
                </button>
            </div>
        </form>

        <?php elseif ($step === 3): ?>
            <?php if ($success): ?>
            <div class="text-center py-4">
                <div class="text-5xl text-green-500 mb-4"><i class="fas fa-check-circle"></i></div>
                <h2 class="text-2xl font-bold text-gray-800 mb-2">Installation Complete!</h2>
                <pre class="text-left text-sm bg-gray-50 rounded-lg p-4 max-h-60 overflow-y-auto text-gray-600 mb-6"><?php echo htmlspecialchars($success); ?></pre>
                <a href="../admin/index.php" class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-medium px-8 py-3 rounded-lg transition">
                    <i class="fas fa-sign-in-alt mr-2"></i> Go to Admin Login
                </a>
            </div>
            <?php else: ?>
            <div class="text-center py-8">
                <div class="text-5xl text-red-500 mb-4"><i class="fas fa-exclamation-circle"></i></div>
                <h2 class="text-xl font-bold text-gray-800 mb-2">Installation Failed</h2>
                <p class="text-gray-500 mb-4"><?php echo htmlspecialchars($error ?: 'Unknown error occurred.'); ?></p>
                <a href="?step=2" class="text-blue-600 hover:underline font-medium"><i class="fas fa-arrow-left mr-2"></i> Go Back</a>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <div class="px-8 py-4 bg-gray-50 border-t text-center text-xs text-gray-400">
        Powered by <a href="https://hostnibo.com" class="text-blue-600 hover:underline font-medium">HostNibo</a>
    </div>
</div>
</body>
</html>
