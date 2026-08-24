<?php
$page_title = 'Frequently Asked Questions (FAQs)';
require_once '../config/database.php';
require_once '../includes/functions.php';
checkAdminLogin();

$msg = '';
$error = '';

// Handle Delete via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_faq_id'])) {
    $id = (int)$_POST['delete_faq_id'];
    $del = mysqli_fetch_assoc(mysqli_query($conn, "SELECT question FROM faqs WHERE id = $id"));
    if (mysqli_query($conn, "DELETE FROM faqs WHERE id = $id")) {
        logActivity('Deleted FAQ', ($del['question'] ?? 'Unknown') . ' (ID: ' . $id . ')');
        $msg = 'FAQ item deleted successfully.';
    } else {
        $error = 'Database error: ' . mysqli_error($conn);
    }
}

// Handle Add / Edit via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_faq'])) {
    $question = sanitize($_POST['question'] ?? '');
    $answer = $_POST['answer'] ?? '';
    $sort_order = (int)($_POST['sort_order'] ?? 0);
    $edit_id = (int)($_POST['faq_id'] ?? 0);

    if (!$question || !$answer) {
        $error = 'Both Question and Answer are required!';
    } else {
        $answer_esc = mysqli_real_escape_string($conn, $answer);
        if ($edit_id > 0) {
            if (mysqli_query($conn, "UPDATE faqs SET question='$question', answer='$answer_esc', sort_order=$sort_order WHERE id=$edit_id")) {
                logActivity('Updated FAQ', $question . ' (ID: ' . $edit_id . ')');
                $msg = 'FAQ updated successfully!';
            } else {
                $error = 'Database error: ' . mysqli_error($conn);
            }
        } else {
            if (!$sort_order) {
                $max = mysqli_fetch_assoc(mysqli_query($conn, "SELECT MAX(sort_order) as m FROM faqs"));
                $sort_order = ($max['m'] ?? 0) + 1;
            }
            if (mysqli_query($conn, "INSERT INTO faqs (question, answer, sort_order) VALUES ('$question', '$answer_esc', $sort_order)")) {
                logActivity('Created FAQ', $question);
                $msg = 'New FAQ created successfully!';
            } else {
                $error = 'Database error: ' . mysqli_error($conn);
            }
        }
    }
}

$items = mysqli_query($conn, "SELECT * FROM faqs ORDER BY sort_order ASC, id ASC");
$total_faqs = mysqli_num_rows($items);
?>
<?php include 'header.php'; ?>

<div class="space-y-6">
    
    <!-- Page Header & Action Bar -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-6 rounded-2xl border border-gray-200/80 shadow-xs">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="p-2 bg-blue-50 text-blue-600 rounded-lg text-sm"><i class="fa-solid fa-circle-question"></i></span>
                <h1 class="text-2xl font-bold text-gray-900">Frequently Asked Questions</h1>
            </div>
            <p class="text-xs text-gray-500">Manage FAQ accordions displayed on your homepage, service pages, and footer.</p>
        </div>
        <div class="flex items-center gap-3">
            <button type="button" onclick="openAddFaqModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-2 shadow-xs cursor-pointer">
                <i class="fa-solid fa-plus"></i> Add New FAQ
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

    <!-- FAQ List Table -->
    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs overflow-hidden">
        <div class="p-4 border-b border-gray-100 flex items-center justify-between">
            <div class="text-xs text-gray-500 font-semibold">
                Total Questions: <strong class="text-gray-900"><?php echo $total_faqs; ?></strong>
            </div>
            <div class="relative w-64">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                <input type="text" id="faqSearchInput" onkeyup="filterFaqRows(this.value)" placeholder="Search questions..." class="w-full bg-gray-50 border border-gray-200 rounded-xl pl-8 pr-3 py-1.5 text-xs text-gray-800 focus:bg-white focus:outline-none focus:border-blue-600 transition">
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50/70 border-b border-gray-200 text-xs font-bold text-gray-700 uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-3.5 w-12 text-center">Order</th>
                        <th class="px-4 py-3.5 w-1/3">Question</th>
                        <th class="px-4 py-3.5">Answer Summary</th>
                        <th class="px-4 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-xs">
                    <?php if ($total_faqs > 0): while ($row = mysqli_fetch_assoc($items)): 
                        $faq_json = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                    ?>
                    <tr class="faq-row hover:bg-blue-50/20 transition" data-name="<?php echo strtolower($row['question'] . ' ' . strip_tags($row['answer'])); ?>">
                        <td class="px-4 py-3.5 text-center font-bold text-gray-400">
                            <?php echo $row['sort_order']; ?>
                        </td>
                        <td class="px-4 py-3.5 font-bold text-gray-900">
                            <div class="flex items-start gap-2">
                                <i class="fa-solid fa-circle-question text-blue-500 text-xs mt-0.5 shrink-0"></i>
                                <span><?php echo htmlspecialchars($row['question']); ?></span>
                            </div>
                        </td>
                        <td class="px-4 py-3.5 text-gray-500">
                            <div class="line-clamp-2 max-w-xl">
                                <?php echo htmlspecialchars(strip_tags($row['answer'])); ?>
                            </div>
                        </td>
                        <td class="px-4 py-3.5 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button type="button" onclick='openEditFaqModal(<?php echo $faq_json; ?>)' class="p-1.5 bg-gray-50 hover:bg-blue-50 text-blue-600 rounded-lg border border-gray-200 hover:border-blue-200 transition cursor-pointer" title="Edit FAQ">
                                    <i class="fa-solid fa-pen text-xs"></i>
                                </button>
                                <button type="button" onclick="openDeleteFaqModal(<?php echo $row['id']; ?>, '<?php echo addslashes($row['question']); ?>')" class="p-1.5 bg-gray-50 hover:bg-red-50 text-red-600 rounded-lg border border-gray-200 hover:border-red-200 transition cursor-pointer" title="Delete FAQ">
                                    <i class="fa-solid fa-trash-can text-xs"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr>
                        <td colspan="4" class="px-4 py-16 text-center text-gray-400">
                            <i class="fa-solid fa-circle-question text-4xl text-gray-300 mb-2 block"></i>
                            <p class="font-bold text-gray-700">No FAQ questions created yet</p>
                            <p class="text-[11px] text-gray-400 mt-0.5">Click "Add New FAQ" to post common customer questions.</p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- ==========================================
     POPUP MODAL: ADD / EDIT FAQ
=============================================== -->
<div id="faqModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4 overflow-y-auto">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-xl overflow-hidden border border-gray-100 my-8 animate-in fade-in duration-200">
        
        <!-- Modal Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b bg-gray-50/70">
            <div class="flex items-center gap-2">
                <span class="p-2 bg-blue-100 text-blue-700 rounded-lg text-xs" id="faqModalIcon"><i class="fa-solid fa-plus"></i></span>
                <h3 class="text-sm font-bold text-gray-900" id="faqModalTitle">Add New FAQ</h3>
            </div>
            <button type="button" onclick="closeFaqModal()" class="text-gray-400 hover:text-gray-600 p-1 rounded-lg transition cursor-pointer">
                <i class="fa-solid fa-xmark text-base"></i>
            </button>
        </div>

        <!-- Modal Form Body -->
        <form method="POST" id="faqModalForm">
            <input type="hidden" name="save_faq" value="1">
            <input type="hidden" name="faq_id" id="faq_id" value="">

            <div class="p-6 space-y-4 text-xs max-h-[75vh] overflow-y-auto">
                
                <div>
                    <label class="block font-bold text-gray-700 mb-1">Question <span class="text-red-500">*</span></label>
                    <input type="text" name="question" id="faq_question" required placeholder="e.g. How long does domain activation take?" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>

                <div>
                    <label class="block font-bold text-gray-700 mb-1">Answer <span class="text-red-500">*</span></label>
                    <textarea name="answer" id="faq_answer" rows="6" required placeholder="Provide a detailed and helpful response..." class="w-full border border-gray-300 rounded-xl p-3 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none"></textarea>
                    <p class="text-[11px] text-gray-400 mt-1">HTML formatting and line breaks are supported.</p>
                </div>

                <div>
                    <label class="block font-bold text-gray-700 mb-1">Sort Order</label>
                    <input type="number" name="sort_order" id="faq_sort_order" value="0" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>

            </div>

            <!-- Modal Footer -->
            <div class="flex items-center justify-end gap-2 px-6 py-4 border-t bg-gray-50">
                <button type="button" onclick="closeFaqModal()" class="px-4 py-2 text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-xl font-bold transition text-xs cursor-pointer">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold transition text-xs flex items-center gap-1.5 shadow-xs cursor-pointer">
                    <i class="fa-solid fa-floppy-disk"></i> Save FAQ
                </button>
            </div>
        </form>

    </div>
</div>

<!-- ==========================================
     POPUP MODAL: DELETE CONFIRMATION
=============================================== -->
<div id="deleteFaqModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden border border-gray-100 animate-in fade-in duration-200">
        <form method="POST">
            <input type="hidden" name="delete_faq_id" id="delete_faq_id_input" value="">
            
            <div class="p-6 text-center">
                <div class="w-14 h-14 rounded-2xl bg-red-50 text-red-600 flex items-center justify-center text-2xl mx-auto mb-4">
                    <i class="fa-solid fa-trash-can"></i>
                </div>
                <h3 class="text-base font-bold text-gray-900 mb-1">Delete FAQ Question?</h3>
                <p class="text-xs text-gray-500 mb-4">Are you sure you want to delete this FAQ item? It will be removed from your website accordions.</p>
                
                <div class="bg-gray-50 p-3 rounded-xl border border-gray-200 text-xs text-left mb-2">
                    <div class="font-bold text-gray-900" id="deleteFaqQuestion">Question Text</div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 px-6 py-3.5 border-t bg-gray-50">
                <button type="button" onclick="closeDeleteFaqModal()" class="px-4 py-2 text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-xl font-bold transition text-xs cursor-pointer">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-red-600 hover:bg-red-700 text-white rounded-xl font-bold transition text-xs flex items-center gap-1.5 shadow-xs cursor-pointer">
                    <i class="fa-solid fa-trash-can"></i> Yes, Delete FAQ
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddFaqModal() {
    document.getElementById('faqModalTitle').innerText = 'Add New FAQ';
    document.getElementById('faqModalIcon').innerHTML = '<i class="fa-solid fa-plus"></i>';
    document.getElementById('faq_id').value = '';
    document.getElementById('faq_question').value = '';
    document.getElementById('faq_answer').value = '';
    document.getElementById('faq_sort_order').value = '0';

    document.getElementById('faqModal').classList.remove('hidden');
}

function openEditFaqModal(faq) {
    document.getElementById('faqModalTitle').innerText = 'Edit FAQ';
    document.getElementById('faqModalIcon').innerHTML = '<i class="fa-solid fa-pen"></i>';
    document.getElementById('faq_id').value = faq.id;
    document.getElementById('faq_question').value = faq.question;
    document.getElementById('faq_answer').value = faq.answer;
    document.getElementById('faq_sort_order').value = faq.sort_order || 0;

    document.getElementById('faqModal').classList.remove('hidden');
}

function closeFaqModal() {
    document.getElementById('faqModal').classList.add('hidden');
}

function openDeleteFaqModal(id, question) {
    document.getElementById('delete_faq_id_input').value = id;
    document.getElementById('deleteFaqQuestion').innerText = question;
    document.getElementById('deleteFaqModal').classList.remove('hidden');
}

function closeDeleteFaqModal() {
    document.getElementById('deleteFaqModal').classList.add('hidden');
}

function filterFaqRows(q) {
    q = q.trim().toLowerCase();
    document.querySelectorAll('.faq-row').forEach(row => {
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
        closeFaqModal();
        closeDeleteFaqModal();
    }
});
</script>

<?php include 'footer.php'; ?>
