CREATE DATABASE IF NOT EXISTS hostflex DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hostflex;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO users (username, email, password) VALUES
('admin', 'admin@hostflex.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

CREATE TABLE hosting_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category VARCHAR(50) NOT NULL,
    name VARCHAR(100) NOT NULL,
    subtitle VARCHAR(200),
    badge VARCHAR(100),
    monthly_price DECIMAL(10,2),
    yearly_price DECIMAL(10,2),
    features TEXT,
    order_url VARCHAR(500),
    is_popular BOOLEAN DEFAULT FALSE,
    sort_order INT DEFAULT 0,
    status BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO hosting_plans (category, name, subtitle, badge, monthly_price, yearly_price, features, order_url, is_popular, sort_order) VALUES
('shared', 'Starter', 'Perfect for personal websites', 'Starter', 3.99, 39.99, '["1 Website","10 GB SSD Storage","50 GB Bandwidth","Free SSL Certificate","1 Email Account","Daily Backup"]', '#', 0, 1),
('shared', 'Business', 'Perfect for small businesses', 'Popular', 7.99, 79.99, '["10 Websites","50 GB SSD Storage","200 GB Bandwidth","Free SSL Certificate","10 Email Accounts","Daily Backup","Free Domain"]', '#', 1, 2),
('shared', 'Professional', 'Perfect for growing businesses', 'Professional', 14.99, 149.99, '["Unlimited Websites","100 GB SSD Storage","Unlimited Bandwidth","Free SSL Certificate","Unlimited Email","Daily Backup","Free Domain","Priority Support"]', '#', 0, 3),
('vps', 'VPS 2GB', 'Entry level VPS', 'VPS', 19.99, 199.99, '["2 GB RAM","2 CPU Cores","50 GB NVMe SSD","2 TB Bandwidth","Full Root Access","1 Dedicated IP"]', '#', 0, 4),
('vps', 'VPS 4GB', 'Mid range VPS', 'Recommended', 34.99, 349.99, '["4 GB RAM","4 CPU Cores","100 GB NVMe SSD","4 TB Bandwidth","Full Root Access","1 Dedicated IP"]', '#', 1, 5),
('vps', 'VPS 8GB', 'High performance VPS', 'High Performance', 64.99, 649.99, '["8 GB RAM","6 CPU Cores","200 GB NVMe SSD","8 TB Bandwidth","Full Root Access","2 Dedicated IPs"]', '#', 0, 6);

CREATE TABLE offers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    badge VARCHAR(100),
    price_label VARCHAR(100),
    link_url VARCHAR(500),
    link_text VARCHAR(100) DEFAULT 'Get Started',
    image VARCHAR(255),
    sort_order INT DEFAULT 0,
    status BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO offers (title, description, badge, price_label, link_url, link_text, sort_order) VALUES
('Shared Hosting', 'Start your online journey with our affordable shared hosting plans', 'HOT DEAL', '$3.99/mo', 'category.php?slug=shared', 'Learn More', 1),
('VPS Servers', 'Unleash your website potential with premium VPS performance', 'POPULAR', '$19.99/mo', 'category.php?slug=vps', 'Learn More', 2),
('Dedicated Servers', 'Enterprise-grade dedicated servers for mission-critical applications', 'BEST VALUE', '$99.99/mo', 'category.php?slug=dedicated', 'View Plans', 3);

CREATE TABLE contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    file VARCHAR(255),
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL
);

INSERT INTO settings (setting_key, setting_value) VALUES
('site_name', 'HostFlex'),
('site_tagline', 'Fast & Reliable Web Hosting'),
('site_description', 'Take your website to the next level with affordable and reliable hosting solutions from HostFlex.'),
('site_email', 'support@hostflex.com'),
('site_phone', '+1 (555) 123-4567'),
('site_address', '123 Hosting Lane, Suite 100, San Francisco, CA 94102'),
('facebook_url', 'https://facebook.com'),
('twitter_url', 'https://twitter.com'),
('linkedin_url', 'https://linkedin.com'),
('youtube_url', 'https://youtube.com'),
('whatsapp_number', ''),
('whatsapp_group', ''),
('telegram_link', ''),
('footer_copyright', 'Copyright 2026 HostFlex. All rights reserved.'),
('currency_symbol', '$'),
('meta_keywords', 'web hosting, domain registration, vps, dedicated servers, hosting provider'),
('meta_author', 'HostFlex'),
('header_logo', 'images/bg.png'),
('footer_logo', 'images/logo-white.png'),
('footer_description', 'Navigating the Digital Landscape: Affordable and Secure Web Hosting Solutions with HostFlex'),
('whmcs_domain_search_url', '#'),
('whmcs_client_area_url', '#'),
('whmcs_domain_pricing_url', '#'),
('whmcs_domain_register_url', '#'),
('whmcs_domain_transfer_url', '#'),
('whmcs_affiliate_url', '#'),
('popup_notice_title', 'Notice'),
('popup_notice_message', '<p style="font-size:14px;color:#333;line-height:1.7;">Welcome to HostFlex! We offer premium hosting solutions with 24/7 support. Check out our latest plans and offers.</p>'),
('contact_page_heading', 'Contact Us'),
('contact_page_subheading', 'We would love to hear from you. Get in touch with us.'),
('fab_enabled', '1'),
('fab_icon', '💬'),
('hero_tagline', 'Fast & Reliable Web Hosting'),
('hero_description', 'Take your website to the next level with affordable and reliable hosting solutions from HostFlex.'),
('hero_image', 'images/cloud.jpg'),
('hero_button_text', 'Get Started'),
('hero_chat_text', 'Live Chat'),
('features_section_enabled', '1'),
('features_heading', 'Why Choose Us'),
('features_data', 'images/icon/speedometer.png | Performance | We utilize high-performance servers to ensure lightning-fast page loads and unparalleled website speed.
images/icon/bar-chart.png | Reliability | We deliver reliable web hosting with guaranteed 99.9% uptime.
images/icon/settings.png | Security | Our enterprise-grade security ensures your website is always protected.
images/icon/control-panel.png | Control Panel | cPanel control panel for effortless website management.
images/icon/time-is-money.png | Money Back Guarantee | 30-day money-back guarantee for ultimate peace of mind.
images/icon/refresh.png | Scalability | Flexible hosting solutions that grow with your business.
images/icon/lock.png | Free SSL | Secure your website with complimentary SSL certificates.
images/icon/customer-service.png | 24/7 Support | Professional support team always ready to assist you.'),
('bottom_cta_enabled', '1'),
('bottom_cta_heading', 'Have questions? <br> We are here to help'),
('bottom_cta_description', 'Talk to one of our hosting specialists who will review your needs and propose the perfect hosting solution for your business.'),
('bottom_cta_image', 'images/tp.png'),
('refund_section_enabled', '1'),
('refund_heading', 'Enjoy peace of mind with our 30-Day Money Back Guarantee'),
('refund_text', 'If you are not satisfied with our hosting and you are a new customer within the first 30 days, we will refund your payment. Full details read terms.'),
('refund_image', 'images/refund.png'),
('popup_notice_enabled', '1'),
('popup_notice_bg_color', 'rgba(255,255,255,0.8)'),
('popup_notice_text_color', '#333'),
('social_buttons', '[{"name":"WhatsApp","icon":"💬","color":"#25D366","url":"https://wa.me/1234567890"},{"name":"Telegram","icon":"📨","color":"#0088cc","url":"https://t.me/"},{"name":"Facebook","icon":"👍","color":"#1877F2","url":"https://facebook.com"}]'),
('hero_button_url', ''),
('hero_chat_url', 'javascript:void(0)'),
('homepage_sections', '');

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    image VARCHAR(255),
    sort_order INT DEFAULT 0,
    status BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO categories (name, slug, description, image, sort_order) VALUES
('Shared Hosting', 'shared', 'Great for personal websites and small businesses starting their online journey.', 'images/s.png', 1),
('VPS Hosting', 'vps', 'Virtual private servers with dedicated resources and full root access.', 'images/cloud-hosting.png', 2),
('Dedicated Servers', 'dedicated', 'Enterprise-grade dedicated servers for high-traffic and mission-critical applications.', 'images/master.png', 3),
('Cloud Hosting', 'cloud', 'Scalable cloud hosting solutions with pay-as-you-go pricing.', 'images/dc.png', 4);

CREATE TABLE pages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    meta_title VARCHAR(255) DEFAULT '',
    slug VARCHAR(200) NOT NULL UNIQUE,
    content LONGTEXT,
    meta_description VARCHAR(500),
    meta_keywords VARCHAR(500),
    status BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO pages (title, slug, content, meta_description) VALUES
('Terms of Service', 'terms', '<h2>Terms of Service</h2><p>Welcome to HostFlex. By using our services, you agree to these terms. Please read them carefully.</p><h3>Service Usage</h3><p>You agree to use our hosting services in compliance with all applicable laws and regulations.</p><h3>Payment Terms</h3><p>All services are billed in advance on a monthly or annual basis as selected during signup.</p><h3>Cancellation</h3><p>You may cancel your service at any time. Refunds are provided according to our refund policy.</p>', 'HostFlex Terms of Service'),
('Privacy Policy', 'privacy', '<h2>Privacy Policy</h2><p>Your privacy is important to us. This policy outlines how we collect, use, and protect your information.</p><h3>Information We Collect</h3><p>We collect information you provide when signing up for our services, including name, email address, and payment information.</p><h3>How We Use Your Information</h3><p>We use your information to provide and improve our services, process payments, and communicate with you.</p><h3>Data Protection</h3><p>We implement industry-standard security measures to protect your personal information.</p>', 'HostFlex Privacy Policy'),
('About Us', 'about', '<h2>About HostFlex</h2><p>HostFlex is a leading web hosting provider dedicated to delivering fast, reliable, and affordable hosting solutions.</p><p>Founded with a mission to empower businesses and individuals to establish a strong online presence, we offer a range of hosting services including shared hosting, VPS, dedicated servers, and cloud solutions.</p><p>Our team of experienced professionals is available 24/7 to ensure your websites run smoothly and securely.</p>', 'About HostFlex - Premium Web Hosting Provider');

CREATE TABLE subscribers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL UNIQUE,
    name VARCHAR(100) DEFAULT '',
    status ENUM('active','unsubscribed') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE blog_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    meta_title VARCHAR(255) DEFAULT '',
    meta_description TEXT,
    meta_keywords VARCHAR(500),
    sort_order INT DEFAULT 0,
    status BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO blog_categories (name, slug, description, sort_order) VALUES
('Web Hosting', 'web-hosting', 'Tips and guides about web hosting', 1),
('WordPress', 'wordpress', 'WordPress tutorials and best practices', 2),
('Security', 'security', 'Website security tips and news', 3);

CREATE TABLE blog_posts (
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
);

INSERT INTO blog_posts (title, slug, content, excerpt, image, category_id, author, meta_description) VALUES
('Getting Started with Web Hosting', 'getting-started-web-hosting', '<h2>Getting Started with Web Hosting</h2><p>Choosing the right web hosting provider is one of the most important decisions you will make for your online presence. Whether you are launching a personal blog, an e-commerce store, or a corporate website, your hosting provider affects your site performance, security, and reliability.</p><h3>Types of Web Hosting</h3><p>There are several types of web hosting available: Shared hosting is the most affordable option where multiple websites share a single server. VPS hosting provides dedicated resources within a shared environment. Dedicated servers give you an entire server to yourself.</p><h3>What to Look For</h3><p>When choosing a hosting provider, consider uptime guarantees, customer support quality, scalability options, security features, and pricing.</p>', 'Learn the basics of web hosting and how to choose the right plan for your website.', 'images/blog-1.jpg', 1, 'Admin', 'Getting started with web hosting - a comprehensive guide for beginners'),
('Top 10 WordPress Security Tips', 'wordpress-security-tips', '<h2>Top 10 WordPress Security Tips</h2><p>WordPress powers over 40% of websites on the internet, making it a common target for hackers. Follow these essential security tips to keep your WordPress site safe.</p><h3>1. Keep Everything Updated</h3><p>Always keep your WordPress core, themes, and plugins updated to the latest versions.</p><h3>2. Use Strong Passwords</h3><p>Use complex passwords and consider implementing two-factor authentication.</p><h3>3. Install a Security Plugin</h3><p>Security plugins like Wordfence or Sucuri can help protect your site from threats.</p>', 'Protect your WordPress site with these essential security best practices.', 'images/blog-2.jpg', 2, 'Admin', 'WordPress security tips to protect your website from hackers and malware'),
('Why Choose VPS Hosting', 'why-choose-vps-hosting', '<h2>Why Choose VPS Hosting?</h2><p>Virtual Private Server (VPS) hosting offers the perfect balance between affordability and performance. Unlike shared hosting, VPS gives you dedicated resources and more control over your server environment.</p><h3>Benefits of VPS Hosting</h3><ul><li>Dedicated CPU and RAM resources</li><li>Root access for complete control</li><li>Better performance and stability</li><li>Scalable resources as you grow</li><li>Improved security isolation</li></ul><p>VPS hosting is ideal for growing websites that have outgrown shared hosting but are not yet ready for a dedicated server.</p>', 'Discover the benefits of VPS hosting and why it might be right for your website.', 'images/blog-3.jpg', 3, 'Admin', 'Why VPS hosting is the right choice for growing websites and businesses');

CREATE TABLE testimonials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    company VARCHAR(200) DEFAULT '',
    photo VARCHAR(255) DEFAULT '',
    rating DECIMAL(2,1) DEFAULT 5.0,
    review TEXT,
    sort_order INT DEFAULT 0,
    status BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO testimonials (name, company, photo, rating, review, sort_order) VALUES
('Sarah Johnson', 'TechStart Inc.', '', 5.0, 'HostFlex has been amazing for our business. The migration was seamless and our site loads incredibly fast. Support team is always responsive.', 1),
('Michael Chen', 'EcomPlus Store', '', 5.0, 'We switched to HostFlex for our e-commerce platform and saw a 40% improvement in page load times. Highly recommended!', 2),
('Emily Rodriguez', 'Creative Agency', '', 4.5, 'The VPS hosting plans are excellent value for money. Full root access gives us the control we need for our client projects.', 3);

CREATE TABLE faqs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question VARCHAR(500) NOT NULL,
    answer TEXT,
    sort_order INT DEFAULT 0,
    status BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO faqs (question, answer, sort_order) VALUES
('What types of hosting do you offer?', 'We offer shared hosting, VPS hosting, dedicated servers, and cloud hosting solutions. Each plan is designed to meet different needs and budgets.', 1),
('How do I migrate my website to HostFlex?', 'Our team provides free website migration for all new customers. Simply contact our support team and we will handle the entire migration process for you.', 2),
('What is your uptime guarantee?', 'We guarantee 99.9% uptime for all our hosting plans. If we fall below this guarantee, you receive credit towards your next billing cycle.', 3),
('Can I upgrade my plan later?', 'Yes, you can upgrade your hosting plan at any time. Our team will assist with the migration to ensure zero downtime during the upgrade.', 4);

CREATE TABLE partners (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    photo VARCHAR(255) DEFAULT '',
    sort_order INT DEFAULT 0,
    status BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO partners (name, photo, sort_order) VALUES
('Cloudflare', 'images/partner-1.png', 1),
('cPanel', 'images/partner-2.png', 2);

CREATE TABLE menu_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    label VARCHAR(100) NOT NULL,
    url VARCHAR(500) NOT NULL,
    parent_id INT DEFAULT 0,
    location ENUM('header','footer','both') DEFAULT 'header',
    sort_order INT DEFAULT 0,
    status BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO menu_items (label, url, parent_id, location, sort_order) VALUES
('Home', 'index.php', 0, 'header', 1),
('Hosting', '#', 0, 'header', 2),
('Shared Hosting', 'category.php?slug=shared', 2, 'header', 3),
('VPS Hosting', 'category.php?slug=vps', 2, 'header', 4),
('Dedicated Servers', 'category.php?slug=dedicated', 2, 'header', 5),
('Cloud Hosting', 'category.php?slug=cloud', 2, 'header', 6),
('Blog', 'blogs.php', 0, 'header', 7),
('Offers', 'offers.php', 0, 'both', 8),
('Contact', 'contact.php', 0, 'both', 9),
('Terms of Service', 'page.php?slug=terms', 0, 'footer', 10),
('Privacy Policy', 'page.php?slug=privacy', 0, 'footer', 11),
('About Us', 'page.php?slug=about', 0, 'footer', 12);
