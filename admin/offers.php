<?php
$page_title = 'Manage Offers';
require_once '../config/database.php';
require_once '../includes/functions.php';
checkAdminLogin();

$error = '';
$success = '';

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM offers WHERE id = $id");
    $success = 'Offer deleted successfully!';
    header('Location: offers.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $badge = sanitize($_POST['badge']);
    $price_label = sanitize($_POST['price_label']);
    $link_url = sanitize($_POST['link_url']);
    $link_text = sanitize($_POST['link_text']);
    $sort_order = (int)$_POST['sort_order'];
    $status = isset($_POST['status']) ? 1 : 0;

    if (isset($_POST['id']) && !empty($_POST['id'])) {
        $id = (int)$_POST['id'];
        $query = "UPDATE offers SET title='$title', description='$description', badge='$badge', price_label='$price_label', link_url='$link_url', link_text='$link_text', sort_order=$sort_order, status=$status WHERE id=$id";
        mysqli_query($conn, $query);
        $success = 'Offer updated successfully!';
    } else {
        $query = "INSERT INTO offers (title, description, badge, price_label, link_url, link_text, sort_order, status) VALUES ('$title', '$description', '$badge', '$price_label', '$link_url', '$link_text', $sort_order, $status)";
        mysqli_query($conn, $query);
        $success = 'Offer added successfully!';
    }
}

$edit_offer = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $result = mysqli_query($conn, "SELECT * FROM offers WHERE id = $id");
    $edit_offer = mysqli_fetch_assoc($result);
}

$offers = mysqli_query($conn, "SELECT * FROM offers ORDER BY sort_order ASC");
?>
<?php include 'header.php'; ?>
<div class="mb-6 flex justify-between items-center">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Offers & Deals</h1>
        <p class="text-gray-500">Manage promotional offers and deals</p>
    </div>
    <a href="?action=add" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"><i class="fa fa-plus"></i> Add Offer</a>
</div>

<?php if ($success): ?><div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4"><?php echo $success; ?></div><?php endif; ?>

<?php if (isset($_GET['action']) && $_GET['action'] === 'add' || $edit_offer): ?>
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <h2 class="text-lg font-semibold mb-4"><?php echo $edit_offer ? 'Edit Offer' : 'Add New Offer'; ?></h2>
    <form method="POST">
        <?php if ($edit_offer): ?><input type="hidden" name="id" value="<?php echo $edit_offer['id']; ?>"><?php endif; ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                <input type="text" name="title" value="<?php echo $edit_offer ? htmlspecialchars($edit_offer['title']) : ''; ?>" required class="w-full border rounded px-3 py-2">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea name="description" rows="3" class="w-full border rounded px-3 py-2"><?php echo $edit_offer ? htmlspecialchars($edit_offer['description']) : ''; ?></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Badge</label>
                <input type="text" name="badge" value="<?php echo $edit_offer ? htmlspecialchars($edit_offer['badge']) : ''; ?>" class="w-full border rounded px-3 py-2" placeholder="HOT, POPULAR, etc.">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Price Label</label>
                <input type="text" name="price_label" value="<?php echo $edit_offer ? htmlspecialchars($edit_offer['price_label']) : ''; ?>" class="w-full border rounded px-3 py-2" placeholder="৳50/mo">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Link URL</label>
                <input type="text" name="link_url" value="<?php echo $edit_offer ? htmlspecialchars($edit_offer['link_url']) : ''; ?>" class="w-full border rounded px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Link Text</label>
                <input type="text" name="link_text" value="<?php echo $edit_offer ? htmlspecialchars($edit_offer['link_text']) : 'Learn More'; ?>" class="w-full border rounded px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Sort Order</label>
                <input type="number" name="sort_order" value="<?php echo $edit_offer ? $edit_offer['sort_order'] : '0'; ?>" class="w-full border rounded px-3 py-2">
            </div>
            <div class="flex items-center">
                <label class="flex items-center"><input type="checkbox" name="status" value="1" <?php echo (!$edit_offer || $edit_offer['status']) ? 'checked' : ''; ?> class="mr-2"> Active</label>
            </div>
        </div>
        <div class="mt-4">
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700"><?php echo $edit_offer ? 'Update Offer' : 'Add Offer'; ?></button>
            <a href="offers.php" class="ml-2 text-gray-600 px-4 py-2 border rounded hover:bg-gray-50">Cancel</a>
        </div>
    </form>
</div>
<?php endif; ?>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="min-w-full">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Badge</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            <?php while ($offer = mysqli_fetch_assoc($offers)): ?>
            <tr>
                <td class="px-6 py-4 text-sm"><?php echo $offer['sort_order']; ?></td>
                <td class="px-6 py-4 text-sm font-medium"><?php echo htmlspecialchars($offer['title']); ?></td>
                <td class="px-6 py-4 text-sm"><span class="bg-red-100 text-red-600 px-2 py-0.5 rounded text-xs"><?php echo htmlspecialchars($offer['badge']); ?></span></td>
                <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars($offer['price_label']); ?></td>
                <td class="px-6 py-4 text-sm"><?php echo $offer['status'] ? '<span class="text-green-600 font-medium">Active</span>' : '<span class="text-red-600 font-medium">Inactive</span>'; ?></td>
                <td class="px-6 py-4 text-sm space-x-2">
                    <a href="?edit=<?php echo $offer['id']; ?>" class="text-blue-600 hover:text-blue-800"><i class="fa fa-edit"></i></a>
                    <a href="?delete=<?php echo $offer['id']; ?>" onclick="return confirm('Delete this offer?')" class="text-red-600 hover:text-red-800"><i class="fa fa-trash"></i></a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
<?php include 'footer.php'; ?>
