<?php
$page_title = 'Admin Users';
require_once '../config/database.php';
require_once '../includes/functions.php';
checkAdminRole(['admin']);

$msg = '';
$error = '';

// Handle Delete via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user_id'])) {
    $id = (int)$_POST['delete_user_id'];
    if ($id == $_SESSION['admin_id']) {
        $error = 'You cannot delete your own logged-in admin account!';
    } else {
        $stmt = mysqli_prepare($conn, "SELECT username, email FROM users WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $del = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        $del_stmt = mysqli_prepare($conn, "DELETE FROM users WHERE id = ?");
        mysqli_stmt_bind_param($del_stmt, "i", $id);
        mysqli_stmt_execute($del_stmt);
        mysqli_stmt_close($del_stmt);

        logActivity('Deleted User', ($del['username'] ?? 'Unknown') . ' (ID: ' . $id . ')');
        $msg = 'User account deleted successfully.';
    }
}

// Handle Add / Edit via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_user'])) {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = in_array($_POST['role'] ?? '', ['admin', 'editor', 'manager']) ? $_POST['role'] : 'editor';
    $status = isset($_POST['status']) ? 1 : 0;
    $edit_id = (int)($_POST['user_id'] ?? 0);

    if (!$username || !$email) {
        $error = 'Username and email are required!';
    } else {
        if ($edit_id) {
            $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ? AND id != ?");
            mysqli_stmt_bind_param($stmt, "si", $username, $edit_id);
        } else {
            $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ?");
            mysqli_stmt_bind_param($stmt, "s", $username);
        }
        mysqli_stmt_execute($stmt);
        $check = mysqli_stmt_get_result($stmt);
        if (mysqli_num_rows($check) > 0) {
            $error = 'Username already exists! Choose a unique username.';
            mysqli_stmt_close($stmt);
        } else {
            mysqli_stmt_close($stmt);
            if ($edit_id) {
                if ($password) {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $up = mysqli_prepare($conn, "UPDATE users SET username=?, email=?, password=?, role=?, status=? WHERE id=?");
                    mysqli_stmt_bind_param($up, "ssssii", $username, $email, $hash, $role, $status, $edit_id);
                } else {
                    $up = mysqli_prepare($conn, "UPDATE users SET username=?, email=?, role=?, status=? WHERE id=?");
                    mysqli_stmt_bind_param($up, "sssii", $username, $email, $role, $status, $edit_id);
                }
                mysqli_stmt_execute($up);
                mysqli_stmt_close($up);
                logActivity('Updated User', $username . ' (ID: ' . $edit_id . ')');
                $msg = 'User account "' . htmlspecialchars($username) . '" updated successfully!';
            } else {
                if (!$password) {
                    $error = 'Password is required for new accounts!';
                } else {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $ins = mysqli_prepare($conn, "INSERT INTO users (username, email, password, role, status) VALUES (?, ?, ?, ?, ?)");
                    mysqli_stmt_bind_param($ins, "ssssi", $username, $email, $hash, $role, $status);
                    mysqli_stmt_execute($ins);
                    mysqli_stmt_close($ins);
                    logActivity('Created User', $username);
                    $msg = 'New user "' . htmlspecialchars($username) . '" created successfully!';
                }
            }
        }
    }
}

$users = mysqli_query($conn, "SELECT * FROM users ORDER BY id ASC");
$total_users = mysqli_num_rows($users);
?>
<?php include 'header.php'; ?>

<div class="space-y-6">
    
    <!-- Page Header & Action Bar -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-6 rounded-2xl border border-gray-200/80 shadow-xs">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="p-2 bg-blue-50 text-blue-600 rounded-lg text-sm"><i class="fa-solid fa-users-gear"></i></span>
                <h1 class="text-2xl font-bold text-gray-900">Admin Staff & Users</h1>
            </div>
            <p class="text-xs text-gray-500">Manage administrator, manager, and editor accounts with role-based access.</p>
        </div>
        <div class="flex items-center gap-3">
            <button type="button" onclick="openAddUserModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-2 shadow-xs cursor-pointer">
                <i class="fa-solid fa-plus"></i> Add New User
            </button>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if ($msg): ?>
    <div class="p-4 rounded-xl text-xs font-semibold flex items-center justify-between bg-emerald-50 text-emerald-800 border border-emerald-200">
        <div class="flex items-center gap-2">
            <i class="fa-solid fa-circle-check text-emerald-600 text-sm"></i>
            <span><?php echo htmlspecialchars($msg); ?></span>
        </div>
        <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700 cursor-pointer"><i class="fa-solid fa-xmark text-sm"></i></button>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="p-4 rounded-xl text-xs font-semibold flex items-center justify-between bg-red-50 text-red-800 border border-red-200">
        <div class="flex items-center gap-2">
            <i class="fa-solid fa-circle-exclamation text-red-600 text-sm"></i>
            <span><?php echo htmlspecialchars($error); ?></span>
        </div>
        <button onclick="this.parentElement.remove()" class="text-red-500 hover:text-red-700 cursor-pointer"><i class="fa-solid fa-xmark text-sm"></i></button>
    </div>
    <?php endif; ?>

    <!-- Users Table -->
    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs overflow-hidden">
        <div class="p-4 border-b border-gray-100 flex items-center justify-between">
            <div class="text-xs text-gray-500 font-semibold">
                Total Staff Accounts: <strong class="text-gray-900"><?php echo $total_users; ?></strong>
            </div>
            <div class="relative w-64">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                <input type="text" id="userSearchInput" onkeyup="filterUserRows(this.value)" placeholder="Search user or email..." class="w-full bg-gray-50 border border-gray-200 rounded-xl pl-8 pr-3 py-1.5 text-xs text-gray-800 focus:bg-white focus:outline-none focus:border-blue-600 transition">
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50/70 border-b border-gray-200 text-xs font-bold text-gray-700 uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-3.5">User</th>
                        <th class="px-4 py-3.5">Assigned Role</th>
                        <th class="px-4 py-3.5">Two-Factor Auth</th>
                        <th class="px-4 py-3.5">Status</th>
                        <th class="px-4 py-3.5">Created Date</th>
                        <th class="px-4 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-xs">
                    <?php while ($u = mysqli_fetch_assoc($users)): 
                        $u_clean = $u;
                        unset($u_clean['password'], $u_clean['two_factor_secret'], $u_clean['two_factor_backup_codes']);
                        $u_json = htmlspecialchars(json_encode($u_clean), ENT_QUOTES, 'UTF-8');
                        $is_self = ($u['id'] == $_SESSION['admin_id']);
                    ?>
                    <tr class="user-row hover:bg-blue-50/20 transition" data-name="<?php echo strtolower($u['username'] . ' ' . $u['email'] . ' ' . $u['role']); ?>">
                        <td class="px-4 py-3.5">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 font-bold flex items-center justify-center text-xs border border-blue-100 shrink-0">
                                    <?php echo strtoupper(substr($u['username'], 0, 2)); ?>
                                </div>
                                <div>
                                    <div class="font-bold text-gray-900 flex items-center gap-1.5">
                                        <span><?php echo htmlspecialchars($u['username']); ?></span>
                                        <?php if ($is_self): ?>
                                        <span class="text-[10px] font-extrabold bg-blue-100 text-blue-700 px-1.5 py-0.2 rounded border border-blue-200">YOU</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-[11px] text-gray-400 font-normal"><?php echo htmlspecialchars($u['email']); ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3.5">
                            <?php if ($u['role'] === 'admin'): ?>
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-200">
                                <i class="fa-solid fa-shield-halved text-[9px]"></i> Administrator
                            </span>
                            <?php elseif ($u['role'] === 'manager'): ?>
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                <i class="fa-solid fa-user-tie text-[9px]"></i> Manager
                            </span>
                            <?php else: ?>
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                <i class="fa-solid fa-pen-nib text-[9px]"></i> Editor
                            </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3.5">
                            <?php if (!empty($u['two_factor_enabled'])): ?>
                            <span class="inline-flex items-center gap-1 text-[11px] text-emerald-600 font-semibold">
                                <i class="fa-solid fa-lock text-xs"></i> 2FA Enabled
                            </span>
                            <?php else: ?>
                            <span class="text-[11px] text-gray-400">Disabled</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3.5">
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold <?php echo ($u['status'] ?? 1) ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200'; ?>">
                                <span class="w-1.5 h-1.5 rounded-full <?php echo ($u['status'] ?? 1) ? 'bg-emerald-500' : 'bg-rose-500'; ?>"></span>
                                <?php echo ($u['status'] ?? 1) ? 'Active' : 'Suspended'; ?>
                            </span>
                        </td>
                        <td class="px-4 py-3.5 text-gray-500">
                            <?php echo date('d M Y', strtotime($u['created_at'])); ?>
                        </td>
                        <td class="px-4 py-3.5 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button type="button" onclick='openEditUserModal(<?php echo $u_json; ?>)' class="p-1.5 bg-gray-50 hover:bg-blue-50 text-blue-600 rounded-lg border border-gray-200 hover:border-blue-200 transition cursor-pointer" title="Edit User">
                                    <i class="fa-solid fa-pen text-xs"></i>
                                </button>
                                <?php if (!$is_self): ?>
                                <button type="button" onclick="openDeleteUserModal(<?php echo $u['id']; ?>, '<?php echo addslashes($u['username']); ?>', '<?php echo addslashes($u['role']); ?>')" class="p-1.5 bg-gray-50 hover:bg-red-50 text-red-600 rounded-lg border border-gray-200 hover:border-red-200 transition cursor-pointer" title="Delete User">
                                    <i class="fa-solid fa-trash-can text-xs"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- ==========================================
     POPUP MODAL: ADD / EDIT USER
=============================================== -->
<div id="userModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4 overflow-y-auto">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden border border-gray-100 my-8 animate-in fade-in duration-200">
        
        <!-- Modal Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b bg-gray-50/70">
            <div class="flex items-center gap-2">
                <span class="p-2 bg-blue-100 text-blue-700 rounded-lg text-xs" id="userModalIcon"><i class="fa-solid fa-plus"></i></span>
                <h3 class="text-sm font-bold text-gray-900" id="userModalTitle">Add New Staff User</h3>
            </div>
            <button type="button" onclick="closeUserModal()" class="text-gray-400 hover:text-gray-600 p-1 rounded-lg transition cursor-pointer">
                <i class="fa-solid fa-xmark text-base"></i>
            </button>
        </div>

        <!-- Modal Form Body -->
        <form method="POST" id="userModalForm">
            <input type="hidden" name="save_user" value="1">
            <input type="hidden" name="user_id" id="user_id" value="">

            <div class="p-6 space-y-4 text-xs max-h-[75vh] overflow-y-auto">
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Username <span class="text-red-500">*</span></label>
                        <input type="text" name="username" id="user_username" required placeholder="e.g. john_admin" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none">
                    </div>

                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Email Address <span class="text-red-500">*</span></label>
                        <input type="email" name="email" id="user_email" required placeholder="john@example.com" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none">
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-gray-700 mb-1">Role & Permissions <span class="text-red-500">*</span></label>
                    <select name="role" id="user_role" required class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none bg-white">
                        <option value="admin">Administrator (Full Unrestricted Access)</option>
                        <option value="manager">Manager (Operations, Hosting Plans & Inquiries)</option>
                        <option value="editor" selected>Editor (Content, Blog, FAQs & Media)</option>
                    </select>
                </div>

                <!-- Password with generator -->
                <div class="bg-gray-50/60 p-4 rounded-xl border border-gray-200 space-y-2">
                    <div class="flex items-center justify-between">
                        <label class="font-bold text-gray-700" id="passwordLabel">Password <span class="text-red-500">*</span></label>
                        <button type="button" onclick="generateRandomPassword()" class="text-blue-600 hover:text-blue-800 font-bold text-[11px] flex items-center gap-1 cursor-pointer">
                            <i class="fa-solid fa-key"></i> Generate Password
                        </button>
                    </div>
                    <div class="relative">
                        <input type="password" name="password" id="user_password" placeholder="Enter secure password..." class="w-full border border-gray-300 rounded-xl pl-3 pr-10 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none bg-white font-mono">
                        <button type="button" onclick="togglePasswordVisibility()" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 cursor-pointer">
                            <i class="fa-solid fa-eye" id="passEyeIcon"></i>
                        </button>
                    </div>
                    <p class="text-[11px] text-gray-400" id="passwordHelpText">Minimum 8 characters with letters, numbers, and symbols.</p>
                </div>

                <div class="pt-2 border-t border-gray-100">
                    <label class="flex items-center gap-2 cursor-pointer select-none font-semibold text-gray-700">
                        <input type="checkbox" name="status" id="user_status" value="1" checked class="rounded text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                        <span><i class="fa-solid fa-circle-check text-emerald-500 mr-1"></i> Active (User can log into Admin Panel)</span>
                    </label>
                </div>

            </div>

            <!-- Modal Footer -->
            <div class="flex items-center justify-end gap-2 px-6 py-4 border-t bg-gray-50">
                <button type="button" onclick="closeUserModal()" class="px-4 py-2 text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-xl font-bold transition text-xs cursor-pointer">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold transition text-xs flex items-center gap-1.5 shadow-xs cursor-pointer">
                    <i class="fa-solid fa-floppy-disk"></i> Save User
                </button>
            </div>
        </form>

    </div>
</div>

<!-- ==========================================
     POPUP MODAL: DELETE CONFIRMATION
=============================================== -->
<div id="deleteUserModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden border border-gray-100 animate-in fade-in duration-200">
        <form method="POST">
            <input type="hidden" name="delete_user_id" id="delete_user_id_input" value="">
            
            <div class="p-6 text-center">
                <div class="w-14 h-14 rounded-2xl bg-red-50 text-red-600 flex items-center justify-center text-2xl mx-auto mb-4">
                    <i class="fa-solid fa-trash-can"></i>
                </div>
                <h3 class="text-base font-bold text-gray-900 mb-1">Delete Staff Account?</h3>
                <p class="text-xs text-gray-500 mb-4">Are you sure you want to permanently delete this user account? They will lose all panel access immediately.</p>
                
                <div class="bg-gray-50 p-3 rounded-xl border border-gray-200 text-xs text-left mb-2">
                    <div class="font-bold text-gray-900" id="deleteUsername">Username</div>
                    <div class="text-gray-500 mt-0.5 capitalize" id="deleteUserRole">Role: Editor</div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 px-6 py-3.5 border-t bg-gray-50">
                <button type="button" onclick="closeDeleteUserModal()" class="px-4 py-2 text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-xl font-bold transition text-xs cursor-pointer">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-red-600 hover:bg-red-700 text-white rounded-xl font-bold transition text-xs flex items-center gap-1.5 shadow-xs cursor-pointer">
                    <i class="fa-solid fa-trash-can"></i> Yes, Delete User
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddUserModal() {
    document.getElementById('userModalTitle').innerText = 'Add New Staff User';
    document.getElementById('userModalIcon').innerHTML = '<i class="fa-solid fa-plus"></i>';
    document.getElementById('user_id').value = '';
    document.getElementById('user_username').value = '';
    document.getElementById('user_email').value = '';
    document.getElementById('user_role').value = 'editor';
    document.getElementById('user_password').value = '';
    document.getElementById('user_password').required = true;
    document.getElementById('passwordLabel').innerHTML = 'Password <span class="text-red-500">*</span>';
    document.getElementById('passwordHelpText').innerText = 'Minimum 8 characters with letters, numbers, and symbols.';
    document.getElementById('user_status').checked = true;

    document.getElementById('userModal').classList.remove('hidden');
}

function openEditUserModal(user) {
    document.getElementById('userModalTitle').innerText = 'Edit User: ' + user.username;
    document.getElementById('userModalIcon').innerHTML = '<i class="fa-solid fa-pen"></i>';
    document.getElementById('user_id').value = user.id;
    document.getElementById('user_username').value = user.username;
    document.getElementById('user_email').value = user.email;
    document.getElementById('user_role').value = user.role || 'editor';
    document.getElementById('user_password').value = '';
    document.getElementById('user_password').required = false;
    document.getElementById('passwordLabel').innerHTML = 'Password <span class="text-gray-400 font-normal">(Leave blank to keep existing)</span>';
    document.getElementById('passwordHelpText').innerText = 'Only enter a new password if you wish to reset it.';
    document.getElementById('user_status').checked = (parseInt(user.status) === 1);

    document.getElementById('userModal').classList.remove('hidden');
}

function closeUserModal() {
    document.getElementById('userModal').classList.add('hidden');
}

function generateRandomPassword() {
    var chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789!@#$%^&*';
    var pass = '';
    for (var i = 0; i < 14; i++) {
        pass += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    var input = document.getElementById('user_password');
    input.value = pass;
    input.type = 'text';
    document.getElementById('passEyeIcon').classList.remove('fa-eye');
    document.getElementById('passEyeIcon').classList.add('fa-eye-slash');
}

function togglePasswordVisibility() {
    var input = document.getElementById('user_password');
    var icon = document.getElementById('passEyeIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

function openDeleteUserModal(id, username, role) {
    document.getElementById('delete_user_id_input').value = id;
    document.getElementById('deleteUsername').innerText = username;
    document.getElementById('deleteUserRole').innerText = 'Assigned Role: ' + role;
    document.getElementById('deleteUserModal').classList.remove('hidden');
}

function closeDeleteUserModal() {
    document.getElementById('deleteUserModal').classList.add('hidden');
}

function filterUserRows(q) {
    q = q.trim().toLowerCase();
    document.querySelectorAll('.user-row').forEach(row => {
        var text = row.dataset.name || '';
        if (!q || text.includes(q)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// Keyboard ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeUserModal();
        closeDeleteUserModal();
    }
});
</script>

<?php include 'footer.php'; ?>
