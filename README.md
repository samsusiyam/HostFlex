# HostFlex - Hosting Management Panel

A full-featured hosting admin panel and website template built with PHP, MySQL, and Tailwind CSS.

## Features

- **Hosting Plans & Offers** — CRUD management with categories, pricing, and sort order
- **Homepage Editor** — Drag & drop section builder (Hero, Features, Blog, Testimonials, FAQ, Partners, CTAs)
- **Blog** — Full blog system with categories, TinyMCE editor, SEO fields, pagination
- **CMS Pages** — Manage About, Terms, Policy & custom pages with WYSIWYG editor
- **Menu Manager** — Drag & drop header menu builder
- **Contact Management** — Incoming messages with read/unread status
- **Newsletter** — Subscriber management with bulk email via SMTP
- **Email System** — SMTP settings, Contact Auto-Reply, admin email forwarding with Reply-To
- **Security** — Admin Users (roles: admin/editor/manager), Activity Logs, Login Logs, Roles & Permissions matrix
- **Database Backup & Restore** — One-click SQL dump download and restore
- **Integrations** — OneSignal, Tawk.to, Crisp Chat, Google reCAPTCHA, custom header/footer codes
- **Maintenance Mode** — Toggle with custom page, admin bypass
- **SEO** — Meta tags, sitemap.xml, SEO-friendly URLs via .htaccess
- **Auto-Update** — System update page that pulls releases from GitHub

## Requirements

- PHP 7.4+
- MySQL 5.7+ / MariaDB
- Apache with mod_rewrite (for SEO URLs)
- PHP extensions: mysqli, zip, openssl

## Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/samsusiyam/HostFlex.git
   ```

2. Import `database.sql` to your MySQL database

3. Configure database connection in `config/database.php`

4. Run installation:
   ```
   http://yourdomain.com/config/install.php
   ```

5. Run system migration:
   ```
   http://yourdomain.com/config/migrate-system.php
   ```

6. Login at `/admin/dashboard.php` (default: admin / password)

7. Configure settings: General → Logo & Branding → Homepage Editor → etc.

## Default Admin Login

- **URL**: `/admin/dashboard.php`
- **Username**: `admin`
- **Password**: `password`

## Directory Structure

```
├── admin/              # Admin panel pages
│   ├── dashboard.php
│   ├── blogs.php       # Blog management (TinyMCE)
│   ├── pages.php       # CMS Pages (TinyMCE)
│   ├── settings-*.php  # Settings sub-pages
│   ├── users.php       # Admin user management
│   ├── roles.php       # Roles & permissions
│   ├── email-templates.php
│   ├── settings-smtp.php
│   ├── update.php      # System auto-update
│   ├── database-backup.php
│   └── ...
├── config/
│   ├── database.php    # DB connection
│   ├── version.php     # Version tracking
│   ├── migrate.php     # Table migration
│   └── migrate-system.php
├── includes/
│   ├── functions.php   # Core functions
│   ├── mail.php        # SMTP mail wrapper
│   └── phpmailer/      # PHPMailer library
├── uploads/            # User uploads
├── index.php           # Frontend homepage
├── blogs.php           # Blog listing
├── blog.php            # Single blog post
├── page.php            # CMS page frontend
├── category.php        # Hosting category page
├── contact.php         # Contact form
├── sitemap.php         # XML sitemap
├── .htaccess           # SEO-friendly URLs
└── ...
```

## SEO URLs

The `.htaccess` file provides clean URLs:

| Old URL | New URL |
|---------|---------|
| `blog.php?slug=my-post` | `/blog/my-post` |
| `page.php?slug=about` | `/page/about-us` |
| `category.php?slug=hosting` | `/category/hosting` |
| `blogs.php` | `/blog` |

## Auto-Update System

When a new version is released on GitHub:

1. Go to **Security → System Update** in admin panel
2. Click **"Update to vX.X.X"**
3. The system downloads the release, preserves `config/database.php` and `uploads/`, runs migrations

To publish an update:
```bash
# Tag the release
git tag v1.1.0
git push origin v1.1.0
```
Then create a Release on GitHub from the tag.

## License

MIT License
