CREATE DATABASE IF NOT EXISTS hostnibo;
USE hostnibo;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO users (username, email, password) VALUES
('admin', 'admin@hostnibo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

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
('basic', '1 GB NVMe HOSTING', 'Perfect for starter websites', '1 GB NVMe HOSTING', 50, 500, '["1 GB NVMe Storage","50 GB Bandwidth","2 GB RAM","1 Core 100% CPU","100 Processes, I/O 10 MB/s, EP 20","5 Email Accounts","5 Subdomains & 5 Databases","5 FTP Accounts","Supports Perl, Ruby, Python, NodeJS","cPanel Control Panel","Daily Auto Backup","WordPress Optimized","LiteSpeed + LS Cache + HTTP/2","Free Website Migration","Automatic Free SSL Installation","CloudLinux Operating System","Live 24/7 Support"]', 'https://panel.shopnohost.com/index.php?rp=/store/w/1-gb-hosting', 0, 1),
('basic', '100 GB VIP HOSTING', 'Perfect for medium websites', '100 GB VIP HOSTING', 120, 1200, '["100 GB NVMe SSD Space","Unlimited Bandwidth","Unlimited Websites","Free SSL Certificate","1x3 Days Backup","LiteSpeed with LSCache","Antivirus (Imunify360)","cPanel Control Panel"]', 'https://panel.shopnohost.com/index.php?rp=/store/w/100-gb-hosting', 0, 2),
('basic', '150 GB VIP HOSTING', 'Perfect for ecommerce websites', '150 GB VIP HOSTING', 140, 1400, '["150 GB NVMe SSD Space","Unlimited Bandwidth","Unlimited Websites","Free SSL Certificate","1x3 Days Backup","LiteSpeed with LSCache","Antivirus (Imunify360)","cPanel Control Panel"]', 'https://panel.shopnohost.com/index.php?rp=/store/w/150-gb-hosting', 0, 3),
('basic', 'UNLIMITED STORAGE', 'Perfect for big size websites', 'UNLIMITED STORAGE HOSTING', 150, 1500, '["UNLIMITED NVMe SSD Space","Unlimited Bandwidth","Unlimited Websites","Free SSL Certificate","1x3 Days Backup","LiteSpeed with LSCache","Antivirus (Imunify360)","cPanel Control Panel"]', 'https://panel.shopnohost.com/index.php?rp=/store/w/u', 0, 4);

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
('Basic Web Hosting', 'Start your online journey with our affordable basic hosting plan', 'HOT DEAL', '৳50/mo', 'category.php?slug=basic', 'Learn More', 1),
('Turbo Web Hosting', 'Unleash your website potential with premium speed & performance', 'POPULAR', '৳500/mo', 'category.php?slug=turbo', 'Learn More', 2),
('BDIX VPS', 'Ultra-fast BDIX VPS for Bangladesh audience with local peering', 'BEST VALUE', '৳1000/mo', 'category.php?slug=vps', 'Learn More', 3);

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
('site_name', 'Host Nibo'),
('site_tagline', 'Empower Your Online Presence with Host Nibo'),
('site_description', 'Take your website to the next level with affordable and reliable hosting solutions from Host Nibo.'),
('site_email', 'support@hostnibo.com'),
('site_phone', '+880'),
('site_address', 'Bangladesh'),
('facebook_url', 'https://facebook.com/hostnibo.bd'),
('twitter_url', 'https://twitter.com'),
('linkedin_url', 'https://linkedin.com'),
('youtube_url', 'https://youtube.com'),
('whatsapp_number', ''),
('whatsapp_group', ''),
('telegram_link', ''),
('footer_copyright', 'Copyright 2025 Host Nibo. All rights reserved.'),
('currency_symbol', '৳'),
('meta_keywords', 'web hosting, bangladesh hosting, host nibo, domain registration'),
('meta_author', 'Host Nibo'),
('header_logo', 'images/bg.png'),
('footer_logo', 'images/logo-white.png'),
('footer_description', 'Navigating the Digital Landscape: Affordable and Secure Web Hosting Solutions with Host Nibo'),
('whmcs_domain_search_url', 'https://my.hostnibo.com/domainchecker.php'),
('whmcs_client_area_url', 'https://my.hostnibo.com/index.php?rp=/login'),
('whmcs_domain_pricing_url', 'https://my.hostnibo.com/index.php?rp=/domain/pricing'),
('whmcs_domain_register_url', 'https://my.hostnibo.com/cart.php?a=add&domain=register'),
('whmcs_domain_transfer_url', 'https://my.hostnibo.com/cart.php?a=add&domain=transfer'),
('whmcs_affiliate_url', 'https://my.hostnibo.com/affiliates.php'),
('popup_notice_title', '📢 নোটিশ'),
('popup_notice_message', '<p style="font-size:14px;color:#333;line-height:1.7;margin-bottom:12px;">হোস্টিং এর মেয়াদ শেষ হবার <span style="color:#0d6efd;font-weight:600;">৭ দিনের</span> মধ্যে <span style="color:#0d6efd;">রিনিউ</span> না করলে আপনার সার্ভারের সব তথ্য <span style="color:#dc3545;font-weight:700;">অটো ডিলিট</span> হয়ে যাবে।</p><p style="font-size:14px;color:#444;line-height:1.6;margin-bottom:14px;">রিনিউ করার পর <strong style="color:#0d6efd;">WHOIS</strong> এ গিয়ে <strong style="color:#0d6efd;">Expire Date</strong> সঠিকভাবে আপডেট হয়েছে কিনা চেক করুন।</p>'),
('contact_page_heading', 'Contact Us'),
('contact_page_subheading', 'We would love to hear from you. Get in touch with us.'),
('fab_enabled', '1'),
('fab_icon', '💬'),
('hero_tagline', 'Empower Your Online Presence with Host Nibo'),
('hero_description', 'Take your website to the next level with affordable and reliable hosting solutions from Host Nibo.'),
('hero_image', 'images/cloud.jpg'),
('hero_button_text', 'Get Started'),
('hero_chat_text', 'Live Chat'),
('features_section_enabled', '1'),
('features_heading', 'Why Choose Us'),
('features_data', 'images/icon/speedometer.png | Performance | We utilize BDIX-powered server to ensure top-notch performance, delivering lightning-fast page loads and unparalleled website speed.
images/icon/bar-chart.png | Reliability and uptime | Priyo Host delivers reliable web hosting with guaranteed 99.9% uptime, you can trust that your website will be up and running always.
images/icon/settings.png | Security | Our enterprise-grade security system ensures your website is protected, giving you peace of mind and safeguarding your brand and audience.
images/icon/control-panel.png | Control Panel | We provide cPanel control panel, which allows you to effortlessly establish and manage your website with ease.
images/icon/time-is-money.png | Money Guarantee | Confidently host your website with our lightning-fast hosting and enjoy a 7 day money-back guarantee for ultimate peace of mind.
images/icon/refresh.png | Scalability | We offer scalable hosting solutions with flexible resources and the ability to upgrade as needed, ensuring your site runs smoothly.
images/icon/lock.png | Free SSL Certificate | Secure your website with our complimentary SSL certificate, ensuring online transactions and sensitive information is protected.
images/icon/customer-service.png | Professional Support | Enjoy seamless 24/7 professional support in your local language. Our client success team is always ready to assist you.'),
('bottom_cta_enabled', '1'),
('bottom_cta_heading', 'Do you have any questions? <br> We are always here to answer you'),
('bottom_cta_description', 'Get in touch with one of our specialists. We will review your needs and offer Talk to one of our hosting specialists, who will review your needs and propose a hosting solution that will specifically suit the needs of your business.'),
('bottom_cta_image', 'images/tp.png'),
('refund_section_enabled', '1'),
('refund_heading', 'Enjoy peace of mind with our 7-Day Money Back Guarantee'),
('refund_text', 'If you are not satisfied with our hosting and if you are a new customer within the first 7 days, we will refund your payment. Full details read terms'),
('refund_image', 'images/refund.png'),
('popup_notice_enabled', '1'),
('popup_notice_bg_color', 'rgba(255,255,255,0.8)'),
('popup_notice_text_color', '#333'),
('social_buttons', '[{"name":"WhatsApp","icon":"💬","color":"#25D366","url":"https://wa.me/88016"},{"name":"Telegram","icon":"📨","color":"#0088cc","url":"https://t.me/"},{"name":"WhatsApp Group","icon":"👥","color":"#128C7E","url":"https://chat.whatsapp.com/"}]'),
('hero_button_url', ''),
('hero_chat_url', 'javascript:void(Tawk_API.toggle())'),
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
('Basic Web Hosting', 'basic', 'Great for initial steps before progressing to more advanced hosting plans.', 'images/s.png', 1),
('Turbo Web Hosting', 'turbo', 'Unleash your website\'s full potential with our Premium Hosting.', 'images/cloud-hosting.png', 2),
('SMM Web Hosting', 'smm', 'SMM Web Hosting for your social media marketing needs.', 'images/cpanel-reseller.png', 3),
('Ecommerce Web Hosting', 'ecom', 'Ecommerce hosting for your online business.', 'images/cpanel-reseller.png', 4),
('BDIX VPS', 'vps', 'Ultra-fast BDIX VPS for Bangladesh audience with local peering.', 'images/master.png', 5),
('KVM VPS', 'kvm', 'Take control with our KVM VPS solutions.', 'images/dc.png', 6);

CREATE TABLE pages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    slug VARCHAR(200) NOT NULL UNIQUE,
    content LONGTEXT,
    meta_description VARCHAR(500),
    meta_keywords VARCHAR(500),
    status BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO pages (title, slug, content, meta_description) VALUES
('Terms of Service', 'terms', '<h2>Terms of Service</h2><p>Your terms of service content goes here. You can edit this from the admin panel.</p>', 'Host Nibo Terms of Service'),
('Privacy Policy', 'privacy', '<h2>Privacy Policy</h2><p>Your privacy policy content goes here. You can edit this from the admin panel.</p>', 'Host Nibo Privacy Policy'),
('About Us', 'about', '<h2>About Host Nibo</h2><p>Your about us content goes here. You can edit this from the admin panel.</p>', 'About Host Nibo');

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
    sort_order INT DEFAULT 0,
    status BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

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

CREATE TABLE faqs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question VARCHAR(500) NOT NULL,
    answer TEXT,
    sort_order INT DEFAULT 0,
    status BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE partners (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    photo VARCHAR(255) DEFAULT '',
    sort_order INT DEFAULT 0,
    status BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

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
('Basic Web Hosting', 'category.php?slug=basic', 2, 'header', 3),
('Turbo Web Hosting', 'category.php?slug=turbo', 2, 'header', 4),
('SMM Web Hosting', 'category.php?slug=smm', 2, 'header', 5),
('Ecommerce Web Hosting', 'category.php?slug=ecom', 2, 'header', 6),
('VPS', '#', 0, 'header', 7),
('KVM VPS', 'category.php?slug=kvm', 7, 'header', 8),
('BDIX VPS', 'category.php?slug=vps', 7, 'header', 9),
('Offers', 'offers.php', 0, 'both', 10),
('Contact', 'contact.php', 0, 'both', 11),
('Terms of Service', 'page.php?slug=terms', 0, 'footer', 12),
('Privacy Policy', 'page.php?slug=privacy', 0, 'footer', 13),
('About Us', 'page.php?slug=about', 0, 'footer', 14);
