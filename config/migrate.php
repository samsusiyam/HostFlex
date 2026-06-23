<?php
require_once 'database.php';

$migrations = [
    "CREATE TABLE IF NOT EXISTS subscribers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(100) NOT NULL UNIQUE,
        name VARCHAR(100) DEFAULT '',
        status ENUM('active','unsubscribed') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS blog_categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        slug VARCHAR(100) NOT NULL UNIQUE,
        description TEXT,
        sort_order INT DEFAULT 0,
        status BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS blog_posts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        slug VARCHAR(255) NOT NULL UNIQUE,
        content LONGTEXT,
        excerpt TEXT,
        image VARCHAR(255),
        category_id INT DEFAULT NULL,
        author VARCHAR(100) DEFAULT '',
        status BOOLEAN DEFAULT TRUE,
        meta_description VARCHAR(500) DEFAULT '',
        meta_keywords VARCHAR(500) DEFAULT '',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES blog_categories(id) ON DELETE SET NULL
    )",
    "ALTER TABLE blog_posts ADD COLUMN IF NOT EXISTS meta_description VARCHAR(500) DEFAULT ''",
    "ALTER TABLE blog_posts ADD COLUMN IF NOT EXISTS meta_description VARCHAR(500) DEFAULT ''",
    "ALTER TABLE blog_posts ADD COLUMN IF NOT EXISTS meta_keywords VARCHAR(500) DEFAULT ''",
    "CREATE TABLE IF NOT EXISTS testimonials (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(200) NOT NULL,
        company VARCHAR(200) DEFAULT '',
        photo VARCHAR(255) DEFAULT '',
        rating DECIMAL(2,1) DEFAULT 5.0,
        review TEXT,
        sort_order INT DEFAULT 0,
        status BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS faqs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        question VARCHAR(500) NOT NULL,
        answer TEXT,
        sort_order INT DEFAULT 0,
        status BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS partners (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(200) NOT NULL,
        photo VARCHAR(255) DEFAULT '',
        sort_order INT DEFAULT 0,
        status BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "ALTER TABLE settings CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",
    "ALTER TABLE hosting_plans CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",
    "ALTER TABLE categories CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",
    "ALTER TABLE pages CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",
    "ALTER TABLE blog_posts CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
];

foreach ($migrations as $sql) {
    if (mysqli_query($conn, $sql)) {
        echo "OK: " . substr($sql, 0, 60) . "...<br>";
    } else {
        echo "ERROR: " . mysqli_error($conn) . "<br>";
    }
}

echo "<br>Migration complete! <a href='../admin/dashboard.php'>Go to Admin Panel</a>";
