<?php
$page_title = 'Roles & Permissions';
require_once '../config/database.php';
require_once '../includes/functions.php';
checkAdminLogin();

$msg = '';
$error = '';

$perm_key = 'admin_permissions';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_perms'])) {
    checkPermission('roles', 'edit');
    validateCSRFToken($_POST['csrf_token'] ?? '');
    $perm_data = [];
    $roles = ['admin', 'editor', 'manager'];
    $sections = ['dashboard', 'plans', 'offers', 'categories', 'pages', 'menus', 'contacts', 'subscribers', 'testimonials', 'faqs', 'partners', 'settings', 'blog', 'users', 'roles', 'logs', 'backup', 'file_manager'];
    $actions = ['view', 'create', 'edit', 'delete'];

    foreach ($roles as $role) {
        $perm_data[$role] = [];
        foreach ($sections as $sec) {
            foreach ($actions as $act) {
                $key = "perm_{$role}_{$sec}_{$act}";
                $perm_data[$role][$sec][$act] = isset($_POST[$key]) ? 1 : 0;
            }
        }
    }

    $json = mysqli_real_escape_string($conn, json_encode($perm_data));
    $check = mysqli_query($conn, "SELECT id FROM settings WHERE setting_key='$perm_key'");
    if (mysqli_num_rows($check) > 0) {
        mysqli_query($conn, "UPDATE settings SET setting_value='$json' WHERE setting_key='$perm_key'");
    } else {
        mysqli_query($conn, "INSERT INTO settings (setting_key, setting_value) VALUES ('$perm_key', '$json')");
    }
    $msg = 'Permissions updated!';
}

$raw = getSetting($perm_key);
$permissions = $raw ? json_decode($raw, true) : [];

$roles = ['admin', 'editor', 'manager'];
$sections = ['dashboard', 'plans', 'offers', 'categories', 'pages', 'menus', 'contacts', 'subscribers', 'testimonials', 'faqs', 'partners', 'settings', 'blog', 'users', 'roles', 'logs', 'backup', 'file_manager'];
$section_labels = ['dashboard' => 'Dashboard', 'plans' => 'Hosting Plans', 'offers' => 'Offers', 'categories' => 'Categories', 'pages' => 'CMS Pages', 'menus' => 'Menu Manager', 'contacts' => 'Contacts', 'subscribers' => 'Subscribers', 'testimonials' => 'Testimonials', 'faqs' => 'FAQs', 'partners' => 'Partners', 'settings' => 'Settings', 'blog' => 'Blog', 'users' => 'Admin Users', 'roles' => 'Roles', 'logs' => 'Activity Logs', 'backup' => 'Database Backup', 'file_manager' => 'File Manager'];
?>
<?php include 'header.php'; ?>
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Roles & Permissions</h1>
    <p class="text-gray-500">Define what each user role can access</p>
</div>
<?php if ($msg): ?><div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4"><?php echo $msg; ?></div><?php endif; ?>
<?php if ($error): ?><div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4"><?php echo $error; ?></div><?php endif; ?>

<form method="POST">
<?= csrfField() ?>
<div class="bg-white rounded-lg shadow overflow-hidden mb-6">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 border-b">
                    <th class="text-left px-4 py-3 font-semibold text-gray-600 w-48">Section</th>
                    <?php foreach ($roles as $role): ?>
                    <th class="text-center px-2 py-3 font-semibold text-gray-600 capitalize"><?php echo $role; ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody class="divide-y">
                <?php foreach ($sections as $sec): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium text-gray-700"><?php echo $section_labels[$sec]; ?></td>
                    <?php foreach ($roles as $role): ?>
                    <td class="px-2 py-3 text-center">
                        <?php if ($role === 'admin'): ?>
                        <span class="text-xs text-gray-400">Full Access</span>
                        <?php else: ?>
                        <div class="flex flex-wrap justify-center gap-1">
                            <?php foreach (['view','create','edit','delete'] as $act): ?>
                            <label class="text-xs flex items-center gap-0.5 cursor-pointer" title="<?php echo ucfirst($act); ?>">
                                <input type="checkbox" name="perm_<?php echo $role; ?>_<?php echo $sec; ?>_<?php echo $act; ?>" value="1" <?php echo ($permissions[$role][$sec][$act] ?? 0) ? 'checked' : ''; ?> class="rounded">
                                <span class="hidden sm:inline text-gray-500"><?php echo substr($act, 0, 1); ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<button type="submit" name="save_perms" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700"><i class="fa fa-save mr-1"></i> Save Permissions</button>
</form>
<?php include 'footer.php'; ?>
