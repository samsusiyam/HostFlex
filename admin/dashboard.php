<?php
$page_title = 'Dashboard';
require_once '../config/database.php';
require_once '../includes/functions.php';
checkAdminLogin();
checkPermission('dashboard', 'view');

$total_plans = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM hosting_plans"))['c'];
$active_plans = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM hosting_plans WHERE status=1"))['c'];
$total_offers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM offers"))['c'];
$total_contacts = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM contacts"))['c'];
$unread_contacts = getUnreadContacts();
$recent_contacts = mysqli_query($conn, "SELECT * FROM contacts ORDER BY created_at DESC LIMIT 5");
$total_blog = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM blog_posts"))['c'];
$total_subscribers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM subscribers"))['c'];
$total_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM users"))['c'];
$total_pages = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM pages"))['c'];
?>
<?php include 'header.php'; ?>
<div class="mb-8">
    <h1 class="text-2xl font-bold text-gray-800">Dashboard</h1>
    <p class="text-gray-500">Welcome back, <?php echo htmlspecialchars($_SESSION['admin_username'], ENT_QUOTES, 'UTF-8'); ?>!</p>
</div>
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow p-6 border-l-4 border-blue-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Total Plans</p>
                <h3 class="text-2xl font-bold"><?php echo $total_plans; ?></h3>
            </div>
            <div class="bg-blue-100 p-3 rounded-full"><i class="fa fa-server text-blue-600 text-xl"></i></div>
        </div>
        <p class="text-green-600 text-sm mt-2"><?php echo $active_plans; ?> active</p>
    </div>
    <div class="bg-white rounded-lg shadow p-6 border-l-4 border-green-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Active Offers</p>
                <h3 class="text-2xl font-bold"><?php echo $total_offers; ?></h3>
            </div>
            <div class="bg-green-100 p-3 rounded-full"><i class="fa fa-tags text-green-600 text-xl"></i></div>
        </div>
        <a href="offers.php" class="text-blue-600 text-sm mt-2 block">Manage Offers</a>
    </div>
    <div class="bg-white rounded-lg shadow p-6 border-l-4 border-yellow-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Total Messages</p>
                <h3 class="text-2xl font-bold"><?php echo $total_contacts; ?></h3>
            </div>
            <div class="bg-yellow-100 p-3 rounded-full"><i class="fa fa-envelope text-yellow-600 text-xl"></i></div>
        </div>
        <p class="text-red-600 text-sm mt-2"><?php echo $unread_contacts; ?> unread</p>
    </div>
    <div class="bg-white rounded-lg shadow p-6 border-l-4 border-purple-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Blog Posts</p>
                <h3 class="text-2xl font-bold"><?php echo $total_blog; ?></h3>
            </div>
            <div class="bg-purple-100 p-3 rounded-full"><i class="fa fa-blog text-purple-600 text-xl"></i></div>
        </div>
        <a href="blogs.php" class="text-blue-600 text-sm mt-2 block">Manage Blog</a>
    </div>
</div>
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow p-6 border-l-4 border-teal-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Subscribers</p>
                <h3 class="text-2xl font-bold"><?php echo $total_subscribers; ?></h3>
            </div>
            <div class="bg-teal-100 p-3 rounded-full"><i class="fa fa-envelope-open text-teal-600 text-xl"></i></div>
        </div>
        <a href="subscribers.php" class="text-blue-600 text-sm mt-2 block">Manage Subscribers</a>
    </div>
    <div class="bg-white rounded-lg shadow p-6 border-l-4 border-indigo-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">CMS Pages</p>
                <h3 class="text-2xl font-bold"><?php echo $total_pages; ?></h3>
            </div>
            <div class="bg-indigo-100 p-3 rounded-full"><i class="fa fa-file text-indigo-600 text-xl"></i></div>
        </div>
        <a href="pages.php" class="text-blue-600 text-sm mt-2 block">Manage Pages</a>
    </div>
    <div class="bg-white rounded-lg shadow p-6 border-l-4 border-pink-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Admin Users</p>
                <h3 class="text-2xl font-bold"><?php echo $total_users; ?></h3>
            </div>
            <div class="bg-pink-100 p-3 rounded-full"><i class="fa fa-users text-pink-600 text-xl"></i></div>
        </div>
        <a href="users.php" class="text-blue-600 text-sm mt-2 block">Manage Users</a>
    </div>
    <div class="bg-white rounded-lg shadow p-6 border-l-4 border-orange-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Active Offers</p>
                <h3 class="text-2xl font-bold"><?php echo $total_offers; ?></h3>
            </div>
            <div class="bg-orange-100 p-3 rounded-full"><i class="fa fa-tags text-orange-600 text-xl"></i></div>
        </div>
        <a href="offers.php" class="text-blue-600 text-sm mt-2 block">Manage Offers</a>
    </div>
</div>
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-lg shadow">
        <div class="p-4 border-b flex justify-between items-center">
            <h2 class="font-semibold text-gray-700">Recent Messages</h2>
            <a href="contacts.php" class="text-blue-600 text-sm">View All</a>
        </div>
        <div class="p-4">
            <?php if (mysqli_num_rows($recent_contacts) > 0): ?>
                <?php while ($msg = mysqli_fetch_assoc($recent_contacts)): ?>
                    <div class="flex items-start space-x-3 py-3 border-b last:border-0">
                        <div class="bg-gray-200 rounded-full w-10 h-10 flex items-center justify-center text-gray-600 font-bold"><?php echo strtoupper(substr($msg['name'], 0, 1)); ?></div>
                        <div class="flex-1">
                            <div class="flex justify-between">
                                <h4 class="font-medium text-sm"><?php echo htmlspecialchars($msg['name']); ?></h4>
                                <span class="text-xs text-gray-400"><?php echo timeAgo($msg['created_at']); ?></span>
                            </div>
                            <p class="text-gray-500 text-sm truncate"><?php echo htmlspecialchars($msg['subject']); ?></p>
                            <?php if (!$msg['is_read']): ?>
                                <span class="bg-red-100 text-red-600 text-xs px-2 py-0.5 rounded">New</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="text-gray-400 text-center py-4">No messages yet</p>
            <?php endif; ?>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow">
        <div class="p-4 border-b">
            <h2 class="font-semibold text-gray-700">Quick Actions</h2>
        </div>
        <div class="p-4 space-y-3">
            <a href="plans.php?action=add" class="flex items-center space-x-3 p-3 bg-blue-50 rounded hover:bg-blue-100 transition">
                <i class="fa fa-plus-circle text-blue-600"></i>
                <span>Add New Hosting Plan</span>
            </a>
            <a href="offers.php?action=add" class="flex items-center space-x-3 p-3 bg-green-50 rounded hover:bg-green-100 transition">
                <i class="fa fa-plus-circle text-green-600"></i>
                <span>Add New Offer</span>
            </a>
            <a href="contacts.php" class="flex items-center space-x-3 p-3 bg-yellow-50 rounded hover:bg-yellow-100 transition">
                <i class="fa fa-inbox text-yellow-600"></i>
                <span>View Contact Messages <?php if ($unread_contacts > 0): ?>(<?php echo $unread_contacts; ?> unread)<?php endif; ?></span>
            </a>
            <a href="settings.php" class="flex items-center space-x-3 p-3 bg-purple-50 rounded hover:bg-purple-100 transition">
                <i class="fa fa-cog text-purple-600"></i>
                <span>Update Site Settings</span>
            </a>
        </div>
    </div>
</div>
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
    <div class="bg-white rounded-lg shadow">
        <div class="p-4 border-b flex justify-between items-center">
            <h2 class="font-semibold text-gray-700">Recent Activity</h2>
            <a href="activity-logs.php" class="text-blue-600 text-sm">View All</a>
        </div>
        <div class="p-4">
            <?php $recent_activities = mysqli_query($conn, "SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT 6"); ?>
            <?php if (mysqli_num_rows($recent_activities) > 0): ?>
                <?php while ($act = mysqli_fetch_assoc($recent_activities)): ?>
                <div class="flex items-start space-x-3 py-2 border-b last:border-0 text-sm">
                    <div class="bg-gray-100 rounded-full w-8 h-8 flex items-center justify-center text-gray-500"><i class="fa fa-history"></i></div>
                    <div class="flex-1">
                        <span class="font-medium"><?php echo htmlspecialchars($act['username']); ?></span>
                        <span class="text-gray-500"><?php echo htmlspecialchars($act['action']); ?></span>
                        <span class="text-gray-400 text-xs block"><?php echo timeAgo($act['created_at']); ?></span>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="text-gray-400 text-center py-4 text-sm">No activity yet.</p>
            <?php endif; ?>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow">
        <div class="p-4 border-b flex justify-between items-center">
            <h2 class="font-semibold text-gray-700">Recent Logins</h2>
            <a href="login-logs.php" class="text-blue-600 text-sm">View All</a>
        </div>
        <div class="p-4">
            <?php $recent_logins = mysqli_query($conn, "SELECT * FROM login_logs ORDER BY created_at DESC LIMIT 6"); ?>
            <?php if (mysqli_num_rows($recent_logins) > 0): ?>
                <?php while ($log = mysqli_fetch_assoc($recent_logins)): ?>
                <div class="flex items-start space-x-3 py-2 border-b last:border-0 text-sm">
                    <div class="bg-gray-100 rounded-full w-8 h-8 flex items-center justify-center">
                        <i class="fa fa-sign-in-alt <?php echo $log['status'] == 'success' ? 'text-green-600' : 'text-red-600'; ?>"></i>
                    </div>
                    <div class="flex-1">
                        <span class="font-medium"><?php echo htmlspecialchars($log['username']); ?></span>
                        <span class="text-xs px-1.5 py-0.5 rounded <?php echo $log['status'] == 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>"><?php echo ucfirst($log['status']); ?></span>
                        <span class="text-gray-400 text-xs block"><?php echo timeAgo($log['created_at']); ?></span>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="text-gray-400 text-center py-4 text-sm">No login activity yet.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include 'footer.php'; ?>
