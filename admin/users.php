<?php
$page_title = 'Admin Users';
require_once '../config/database.php';
require_once '../includes/functions.php';
checkAdminLogin();
checkPermission('users', 'view');

$msg = '';
$error = '';

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    checkPermission('users', 'delete');
    $id = (int)$_GET['delete'];
    if ($id == $_SESSION['admin_id']) {
        $error = 'You cannot delete your own account!';
    } else {
        $del = mysqli_fetch_assoc(mysqli_query($conn, "SELECT username FROM users WHERE id = $id"));
        mysqli_query($conn, "DELETE FROM users WHERE id = $id");
        logActivity('Deleted User', ($del['username'] ?? 'Unknown') . ' (ID: ' . $id . ')');
        $msg = 'User deleted!';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_user'])) {
    validateCSRFToken($_POST['csrf_token'] ?? '');
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $role = sanitize($_POST['role']);
    $status = isset($_POST['status']) ? 1 : 0;
    $edit_id = (int)($_POST['edit_id'] ?? 0);

    if (!$username || !$email) {
        $error = 'Username and email are required!';
    } else {
        $check = mysqli_query($conn, "SELECT id FROM users WHERE username='$username'" . ($edit_id ? " AND id!=$edit_id" : ""));
        if (mysqli_num_rows($check) > 0) {
            $error = 'Username already exists!';
        } else {
            if ($edit_id) {
                checkPermission('users', 'edit');
                $sql = "UPDATE users SET username='$username', email='$email', role='$role', status=$status WHERE id=$edit_id";
                if ($password) {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $sql = "UPDATE users SET username='$username', email='$email', password='$hash', role='$role', status=$status WHERE id=$edit_id";
                }
                mysqli_query($conn, $sql);
                logActivity('Updated User', $username . ' (ID: ' . $edit_id . ')');
                $msg = 'User updated!';
            } else {
                checkPermission('users', 'create');
                if (!$password) {
                    $error = 'Password is required for new users!';
                } else {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    mysqli_query($conn, "INSERT INTO users (username, email, password, role, status) VALUES ('$username', '$email', '$hash', '$role', $status)");
                    logActivity('Created User', $username);
                    $msg = 'User created!';
                }
            }
        }
    }
}

$edit_user = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id = " . (int)$_GET['edit']));
}

$users = mysqli_query($conn, "SELECT * FROM users ORDER BY created_at DESC");
?>
<?php include 'header.php'; ?>
<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">Admin Users</h1>
        <p class="text-gray-500 dark:text-gray-400">Manage admin panel users and roles</p>
    </div>
    <a href="?edit=0" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 dark:hover:bg-green-600 text-sm <?php echo $edit_user ? 'hidden' : ''; ?>"><i class="fa fa-plus mr-1"></i> New User</a>
</div>
<?php if ($msg): ?><div class="bg-green-100 border border-green-400 text-green-700 dark:bg-green-900/30 dark:border-green-700 dark:text-green-300 px-4 py-3 rounded mb-4"><?php echo $msg; ?></div><?php endif; ?>
<?php if ($error): ?><div class="bg-red-100 border border-red-400 text-red-700 dark:bg-red-900/30 dark:border-red-700 dark:text-red-300 px-4 py-3 rounded mb-4"><?php echo $error; ?></div><?php endif; ?>

<?php if ($edit_user !== null || isset($_GET['edit'])): ?>
<?php $eu = $edit_user; $is_new = !$eu; $eu = $eu ?: ['id'=>0,'username'=>'','email'=>'','role'=>'editor','status'=>1]; $eu['role'] ??= 'editor'; $eu['status'] ??= 1; ?>
<div class="bg-white rounded-lg shadow p-6 mb-6 dark:bg-gray-800">
    <h2 class="text-lg font-semibold mb-4"><?php echo $is_new ? 'New User' : 'Edit User'; ?></h2>
    <form method="POST">
        <?= csrfField() ?>
        <input type="hidden" name="edit_id" value="<?php echo $eu['id']; ?>">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 max-w-2xl">
            <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Username</label><input type="text" name="username" value="<?php echo htmlspecialchars($eu['username']); ?>" required class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600"></div>
            <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Email</label><input type="email" name="email" value="<?php echo htmlspecialchars($eu['email']); ?>" required class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600"></div>
            <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Password <?php echo $is_new ? '' : '(leave blank to keep)'; ?></label><input type="password" name="password" class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600" <?php echo $is_new ? 'required' : ''; ?>></div>
            <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Role</label>
                <select name="role" class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600">
                    <option value="admin" <?php echo ($eu['role']??'') == 'admin' ? 'selected' : ''; ?>>Admin</option>
                    <option value="editor" <?php echo ($eu['role']??'') == 'editor' ? 'selected' : ''; ?>>Editor</option>
                    <option value="manager" <?php echo ($eu['role']??'') == 'manager' ? 'selected' : ''; ?>>Manager</option>
                </select>
            </div>
            <div class="flex items-center gap-2">
                <input type="checkbox" name="status" value="1" id="userStatus" <?php echo $eu['status'] ? 'checked' : ''; ?> class="h-5 w-5 text-blue-600 border rounded dark:border-gray-600">
                <label for="userStatus" class="text-sm font-medium text-gray-700 dark:text-gray-200">Active</label>
            </div>
        </div>
        <button type="submit" name="save_user" class="mt-4 bg-blue-600 text-white px-5 py-2 rounded hover:bg-blue-700 dark:hover:bg-blue-600"><i class="fa fa-save mr-1"></i> <?php echo $is_new ? 'Create User' : 'Update User'; ?></button>
    </form>
</div>
<?php endif; ?>

<div class="bg-white rounded-lg shadow overflow-hidden dark:bg-gray-800">
    <table class="w-full">
        <thead class="bg-gray-50 border-b dark:bg-gray-700 dark:border-gray-600">
            <tr>
                <th class="text-left px-4 py-3 text-sm font-semibold text-gray-600 dark:text-gray-300">Username</th>
                <th class="text-left px-4 py-3 text-sm font-semibold text-gray-600 dark:text-gray-300">Email</th>
                <th class="text-left px-4 py-3 text-sm font-semibold text-gray-600 dark:text-gray-300">Role</th>
                <th class="text-left px-4 py-3 text-sm font-semibold text-gray-600 dark:text-gray-300">Status</th>
                <th class="text-left px-4 py-3 text-sm font-semibold text-gray-600 dark:text-gray-300">Created</th>
                <th class="text-right px-4 py-3 text-sm font-semibold text-gray-600 dark:text-gray-300">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y dark:divide-gray-600">
            <?php while ($u = mysqli_fetch_assoc($users)): ?>
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                <td class="px-4 py-3 text-sm font-medium"><?php echo htmlspecialchars($u['username']); ?></td>
                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars($u['email']); ?></td>
                <td class="px-4 py-3">
                    <span class="text-xs px-2 py-1 rounded-full <?php echo ($u['role']??'') == 'admin' ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300' : (($u['role']??'') == 'manager' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'); ?>"><?php echo ucfirst($u['role']??'editor'); ?></span>
                </td>
                <td class="px-4 py-3">
                    <span class="text-xs px-2 py-1 rounded-full <?php echo ($u['status']??1) ? 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300' : 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300'; ?>"><?php echo ($u['status']??1) ? 'Active' : 'Inactive'; ?></span>
                </td>
                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400"><?php echo date('d M Y', strtotime($u['created_at'])); ?></td>
                <td class="px-4 py-3 text-right">
                    <a href="?edit=<?php echo $u['id']; ?>" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 mr-2"><i class="fa fa-edit"></i></a>
                    <?php if ($u['id'] != $_SESSION['admin_id']): ?>
                    <a href="?delete=<?php echo $u['id']; ?>" onclick="return confirm('Delete this user?')" class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"><i class="fa fa-trash"></i></a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
<?php include 'footer.php'; ?>
