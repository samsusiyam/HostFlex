<?php
require_once 'database.php';

$migrations = [
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS role ENUM('admin','editor','manager') NOT NULL DEFAULT 'admin' AFTER password",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS status TINYINT(1) NOT NULL DEFAULT 1 AFTER role",
    "CREATE TABLE IF NOT EXISTS activity_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        username VARCHAR(100) NOT NULL,
        action VARCHAR(255) NOT NULL,
        details TEXT,
        ip_address VARCHAR(45) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS login_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(100) NOT NULL,
        status ENUM('success','failed') NOT NULL,
        ip_address VARCHAR(45) NOT NULL,
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )"
];

echo "<h3>Running migrations...</h3><pre>";
foreach ($migrations as $sql) {
    $sql_clean = str_replace("IF NOT EXISTS", "", $sql);
    try {
        if (@mysqli_query($conn, $sql_clean) || (strpos(mysqli_error($conn), 'Duplicate') !== false) || (strpos(mysqli_error($conn), 'already exists') !== false)) {
            echo "OK: " . substr($sql, 0, 60) . "...\n";
        } else {
            echo "ERROR: " . mysqli_error($conn) . "\n";
        }
    } catch (Throwable $e) {
        echo "SKIP (exists): " . substr($sql, 0, 60) . "...\n";
    }
}

$default_settings = [
    'header_code' => '',
    'footer_code' => '',
    'onesignal_app_id' => '',
    'tawkto_widget_id' => '',
    'crisp_website_id' => '',
    'recaptcha_site_key' => '',
    'recaptcha_secret_key' => '',
    'recaptcha_enabled' => '0',
    'maintenance_mode' => '0',
    'maintenance_title' => 'Under Maintenance',
    'maintenance_heading' => 'We\'ll be back soon!',
    'maintenance_message' => 'Our website is currently undergoing scheduled maintenance. Please check back later.'
];

echo "\n<h3>Seeding settings...</h3><pre>";
foreach ($default_settings as $key => $value) {
    $check = mysqli_query($conn, "SELECT id FROM settings WHERE setting_key = '$key'");
    if (mysqli_num_rows($check) == 0) {
        $val = mysqli_real_escape_string($conn, $value);
        mysqli_query($conn, "INSERT INTO settings (setting_key, setting_value) VALUES ('$key', '$val')");
        echo "OK: $key\n";
    } else {
        echo "SKIP (exists): $key\n";
    }
}

echo "</pre><p><strong>Migration complete!</strong></p>";
echo '<a href="../admin/dashboard.php">Go to Admin Panel</a>';
