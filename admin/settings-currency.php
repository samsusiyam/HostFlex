<?php
$page_title = 'Multi-Currency & Exchange Rates';
require_once '../config/database.php';
require_once '../includes/functions.php';
checkAdminRole(['admin']);

$error = '';
$success = '';

// Load current configuration
$multi_enabled = getSetting('multi_currency_enabled') !== '0';
$base_currency = getSetting('base_currency') ?: 'BDT';
$currencies = getCurrenciesList();

// Handle AJAX Quick Toggle Currency Status
if (isset($_POST['ajax_toggle_currency'])) {
    header('Content-Type: application/json');
    $code = strtoupper(trim($_POST['code'] ?? ''));
    if (isset($currencies[$code])) {
        $currencies[$code]['enabled'] = (int)($currencies[$code]['enabled'] ?? 1) === 1 ? 0 : 1;
        $json = json_encode($currencies, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $json_esc = mysqli_real_escape_string($conn, $json);
        
        $chk = mysqli_query($conn, "SELECT id FROM settings WHERE setting_key = 'currencies_config'");
        if (mysqli_num_rows($chk) > 0) {
            mysqli_query($conn, "UPDATE settings SET setting_value = '$json_esc' WHERE setting_key = 'currencies_config'");
        } else {
            mysqli_query($conn, "INSERT INTO settings (setting_key, setting_value) VALUES ('currencies_config', '$json_esc')");
        }
        logActivity('Toggled Currency Status', "$code -> " . ($currencies[$code]['enabled'] ? 'Enabled' : 'Disabled'));
        echo json_encode(['success' => true, 'new_status' => $currencies[$code]['enabled']]);
        exit;
    }
    echo json_encode(['success' => false]);
    exit;
}

// Handle Form Submission (Save Settings / Rates / Add / Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_currencies'])) {
    $multi_enabled = isset($_POST['multi_currency_enabled']) ? '1' : '0';
    $base_currency = strtoupper(trim($_POST['base_currency'] ?? 'BDT'));
    
    // Process updated currencies from table
    $updated_currencies = [];
    $codes = $_POST['curr_code'] ?? [];
    $symbols = $_POST['curr_symbol'] ?? [];
    $names = $_POST['curr_name'] ?? [];
    $rates = $_POST['curr_rate'] ?? [];
    $enabled_flags = $_POST['curr_enabled'] ?? [];

    foreach ($codes as $idx => $raw_code) {
        $c_code = strtoupper(trim($raw_code));
        if (empty($c_code)) continue;
        
        $c_symbol = trim($symbols[$idx] ?? '$');
        $c_name = trim($names[$idx] ?? $c_code);
        $c_rate = (float)($rates[$idx] ?? 1.0);
        if ($c_rate <= 0) $c_rate = 1.0;
        
        // Base currency must always have rate 1.0 and be enabled
        if ($c_code === $base_currency) {
            $c_rate = 1.0;
            $c_enabled = 1;
        } else {
            $c_enabled = isset($enabled_flags[$c_code]) ? 1 : 0;
        }

        $updated_currencies[$c_code] = [
            'code' => $c_code,
            'symbol' => $c_symbol,
            'name' => $c_name,
            'rate' => $c_rate,
            'enabled' => $c_enabled
        ];
    }

    // Check if adding new custom currency
    $new_code = strtoupper(trim($_POST['new_code'] ?? ''));
    if (!empty($new_code)) {
        $new_symbol = trim($_POST['new_symbol'] ?? '$');
        $new_name = trim($_POST['new_name'] ?? $new_code);
        $new_rate = (float)($_POST['new_rate'] ?? 1.0);
        if ($new_rate <= 0) $new_rate = 1.0;

        $updated_currencies[$new_code] = [
            'code' => $new_code,
            'symbol' => $new_symbol,
            'name' => $new_name,
            'rate' => $new_rate,
            'enabled' => 1
        ];
    }

    // Save to database
    $currencies_json = json_encode($updated_currencies, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    $currencies_esc = mysqli_real_escape_string($conn, $currencies_json);

    // Save multi_currency_enabled
    mysqli_query($conn, "INSERT INTO settings (setting_key, setting_value) VALUES ('multi_currency_enabled', '$multi_enabled') ON DUPLICATE KEY UPDATE setting_value = '$multi_enabled'");
    
    // Save base_currency
    mysqli_query($conn, "INSERT INTO settings (setting_key, setting_value) VALUES ('base_currency', '$base_currency') ON DUPLICATE KEY UPDATE setting_value = '$base_currency'");
    
    // Save primary currency_symbol
    $base_sym = $updated_currencies[$base_currency]['symbol'] ?? '৳';
    mysqli_query($conn, "INSERT INTO settings (setting_key, setting_value) VALUES ('currency_symbol', '$base_sym') ON DUPLICATE KEY UPDATE setting_value = '$base_sym'");

    // Save currencies_config
    mysqli_query($conn, "INSERT INTO settings (setting_key, setting_value) VALUES ('currencies_config', '$currencies_esc') ON DUPLICATE KEY UPDATE setting_value = '$currencies_esc'");

    logActivity('Updated Currency Settings', 'Multi-currency rates & base currency updated');
    header('Location: settings-currency.php?s=1');
    exit;
}

// Handle 1-Click Standard Preset Reset
if (isset($_GET['reset_presets']) && $_GET['reset_presets'] === '1') {
    $defaults = getDefaultCurrencies();
    $json = json_encode($defaults, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    $json_esc = mysqli_real_escape_string($conn, $json);
    mysqli_query($conn, "INSERT INTO settings (setting_key, setting_value) VALUES ('currencies_config', '$json_esc') ON DUPLICATE KEY UPDATE setting_value = '$json_esc'");
    logActivity('Reset Currency Presets', 'Restored default exchange rates');
    header('Location: settings-currency.php?s=2');
    exit;
}

$success_msg = '';
if (isset($_GET['s'])) {
    if ($_GET['s'] === '1') $success_msg = 'Currency configuration and exchange rates saved successfully!';
    if ($_GET['s'] === '2') $success_msg = 'Default currency presets restored successfully!';
}
?>
<?php include 'header.php'; ?>

<div class="space-y-6">

    <!-- Header Banner -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-6 rounded-2xl border border-gray-200/80 shadow-xs">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="p-2 bg-emerald-50 text-emerald-600 rounded-lg text-sm"><i class="fa-solid fa-coins"></i></span>
                <h1 class="text-2xl font-bold text-gray-900">Multi-Currency & Exchange Rates</h1>
            </div>
            <p class="text-xs text-gray-500">Configure multi-currency conversion, dynamic frontend currency switchers, and real-time exchange rates.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="settings-currency.php?reset_presets=1" onclick="return confirm('Restore default standard exchange rate presets?')" class="px-3.5 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold rounded-xl transition flex items-center gap-1.5">
                <i class="fa-solid fa-rotate-left"></i> Restore Standard Presets
            </a>
            <button type="button" onclick="openAddCurrencyModal()" class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fa-solid fa-plus"></i> Add New Currency
            </button>
        </div>
    </div>

    <!-- Alert -->
    <?php if ($success_msg): ?>
    <div class="p-4 rounded-xl text-xs font-semibold flex items-center justify-between bg-emerald-50 text-emerald-800 border border-emerald-200">
        <div class="flex items-center gap-2">
            <i class="fa-solid fa-circle-check text-emerald-600 text-sm"></i>
            <span><?php echo htmlspecialchars($success_msg); ?></span>
        </div>
        <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700 cursor-pointer"><i class="fa-solid fa-xmark text-sm"></i></button>
    </div>
    <?php endif; ?>

    <form method="POST" class="space-y-6">

        <!-- Global Settings Card -->
        <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs p-6">
            <div class="flex items-center gap-2 pb-4 border-b border-gray-100 mb-6">
                <span class="p-2 bg-blue-50 text-blue-600 rounded-lg text-xs"><i class="fa-solid fa-sliders"></i></span>
                <h2 class="text-sm font-bold text-gray-900">General Currency Controls</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Multi Currency Switch -->
                <div class="p-4 rounded-xl bg-gray-50 border border-gray-200/70 flex items-center justify-between">
                    <div>
                        <h4 class="font-bold text-xs text-gray-900 mb-1">Enable Multi-Currency on Frontend</h4>
                        <p class="text-[11px] text-gray-500">Shows currency switcher dropdown in header and recalculates prices live across plans and domain search.</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="multi_currency_enabled" value="1" <?php echo $multi_enabled ? 'checked' : ''; ?> class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                    </label>
                </div>

                <!-- Base Currency Selection -->
                <div class="p-4 rounded-xl bg-gray-50 border border-gray-200/70 flex items-center justify-between">
                    <div>
                        <h4 class="font-bold text-xs text-gray-900 mb-1">Store Base Currency</h4>
                        <p class="text-[11px] text-gray-500">The primary currency in which all hosting plan prices are entered in the admin panel.</p>
                    </div>
                    <select name="base_currency" class="bg-white border border-gray-300 rounded-xl px-3 py-2 text-xs font-bold text-gray-800 outline-none focus:ring-1 focus:ring-blue-500">
                        <?php foreach ($currencies as $c): ?>
                        <option value="<?php echo htmlspecialchars($c['code']); ?>" <?php echo $base_currency === $c['code'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($c['code']); ?> (<?php echo htmlspecialchars($c['symbol']); ?>) - <?php echo htmlspecialchars($c['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- Currencies Table Card -->
        <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs overflow-hidden">
            <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-gray-900">Configured Currencies & Conversion Rates</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Rates represent: <strong>1 <?php echo htmlspecialchars($base_currency); ?> = X Target Currency</strong>.</p>
                </div>
                <span class="text-xs bg-blue-50 text-blue-700 font-bold px-3 py-1 rounded-full border border-blue-200">
                    <?php echo count($currencies); ?> Currencies Configured
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-gray-600">
                    <thead class="bg-gray-50/75 border-b border-gray-200/80 text-gray-700 font-bold uppercase tracking-wider text-[11px]">
                        <tr>
                            <th class="py-3.5 px-6">Currency</th>
                            <th class="py-3.5 px-4">Symbol</th>
                            <th class="py-3.5 px-4">Name</th>
                            <th class="py-3.5 px-4">Rate (1 <?php echo htmlspecialchars($base_currency); ?> =)</th>
                            <th class="py-3.5 px-4">Quick Converter Preview</th>
                            <th class="py-3.5 px-4 text-center">Status</th>
                            <th class="py-3.5 px-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($currencies as $c): 
                            $is_base = ($c['code'] === $base_currency);
                            $enabled = !empty($c['enabled']);
                        ?>
                        <tr class="hover:bg-gray-50/50 transition">
                            <td class="py-3.5 px-6 font-bold text-gray-900">
                                <div class="flex items-center gap-2">
                                    <span class="w-8 h-8 rounded-lg bg-gray-100 border border-gray-200 flex items-center justify-center font-extrabold text-xs text-gray-700">
                                        <?php echo htmlspecialchars($c['symbol']); ?>
                                    </span>
                                    <div>
                                        <div class="flex items-center gap-1.5">
                                            <input type="hidden" name="curr_code[]" value="<?php echo htmlspecialchars($c['code']); ?>">
                                            <span class="font-extrabold text-xs text-gray-900"><?php echo htmlspecialchars($c['code']); ?></span>
                                            <?php if ($is_base): ?>
                                            <span class="text-[10px] font-bold bg-amber-100 text-amber-800 px-2 py-0.5 rounded-full">Base Currency</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3.5 px-4">
                                <input type="text" name="curr_symbol[]" value="<?php echo htmlspecialchars($c['symbol']); ?>" class="w-16 border border-gray-200 rounded-lg px-2.5 py-1.5 text-xs text-center font-bold text-gray-800 focus:border-blue-500 outline-none">
                            </td>
                            <td class="py-3.5 px-4">
                                <input type="text" name="curr_name[]" value="<?php echo htmlspecialchars($c['name']); ?>" class="w-44 border border-gray-200 rounded-lg px-2.5 py-1.5 text-xs text-gray-800 focus:border-blue-500 outline-none">
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-1.5">
                                    <input type="number" step="0.000001" min="0.000001" name="curr_rate[]" value="<?php echo (float)$c['rate']; ?>" <?php echo $is_base ? 'readonly' : ''; ?> class="w-28 border border-gray-200 rounded-lg px-2.5 py-1.5 text-xs font-mono font-bold text-gray-800 focus:border-blue-500 outline-none <?php echo $is_base ? 'bg-gray-100 text-gray-500' : ''; ?>">
                                </div>
                            </td>
                            <td class="py-3.5 px-4 font-mono font-semibold text-gray-700 text-xs">
                                <?php 
                                    $sample_base = 1000;
                                    $sample_conv = convertPriceAmount($sample_base, $c);
                                    echo htmlspecialchars($c['symbol']) . ' ' . $sample_conv . ' <span class="text-[10px] text-gray-400 font-sans font-normal">(' . $base_currency . ' ' . $sample_base . ')</span>';
                                ?>
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="curr_enabled[<?php echo $c['code']; ?>]" value="1" <?php echo $enabled ? 'checked' : ''; ?> <?php echo $is_base ? 'disabled' : ''; ?> onchange="ajaxToggleCurrency('<?php echo $c['code']; ?>', this)" class="sr-only peer">
                                    <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-600 <?php echo $is_base ? 'opacity-60 cursor-not-allowed' : ''; ?>"></div>
                                </label>
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <?php if (!$is_base && !in_array($c['code'], ['BDT', 'USD', 'EUR', 'GBP', 'INR'])): ?>
                                <button type="button" onclick="this.closest('tr').remove()" class="p-1.5 text-rose-500 hover:text-rose-700 hover:bg-rose-50 rounded-lg transition" title="Delete Currency">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                                <?php else: ?>
                                <span class="text-gray-300 text-xs">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="p-6 bg-gray-50/50 border-t border-gray-100 flex items-center justify-between">
                <p class="text-xs text-gray-500">Changes to exchange rates take effect instantly on all frontend plan cards, domain lookups, and pricing tables.</p>
                <button type="submit" name="save_currencies" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2.5 px-6 rounded-xl shadow-xs transition flex items-center gap-2 text-xs cursor-pointer">
                    <i class="fa-solid fa-floppy-disk"></i> Save Currency Settings & Rates
                </button>
            </div>
        </div>

    </form>

</div>

<!-- Add Currency Modal -->
<div id="addCurrencyModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl shadow-2xl border border-gray-200 max-w-md w-full p-6 space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-gray-100">
            <h3 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                <span class="p-1.5 bg-emerald-50 text-emerald-600 rounded-lg text-xs"><i class="fa-solid fa-plus"></i></span>
                Add New Currency
            </h3>
            <button onclick="closeAddCurrencyModal()" class="text-gray-400 hover:text-gray-600 cursor-pointer"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <form method="POST" class="space-y-4 text-xs">
            <div>
                <label class="block font-bold text-gray-700 mb-1">Currency Code (ISO 3-Letter)</label>
                <input type="text" name="new_code" required placeholder="e.g. AUD, CAD, JPY" maxlength="5" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs font-bold uppercase focus:ring-1 focus:ring-emerald-500 outline-none">
            </div>
            <div>
                <label class="block font-bold text-gray-700 mb-1">Currency Symbol</label>
                <input type="text" name="new_symbol" required placeholder="e.g. A$, CA$, ¥" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs font-bold focus:ring-1 focus:ring-emerald-500 outline-none">
            </div>
            <div>
                <label class="block font-bold text-gray-700 mb-1">Currency Name</label>
                <input type="text" name="new_name" required placeholder="e.g. Australian Dollar" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-emerald-500 outline-none">
            </div>
            <div>
                <label class="block font-bold text-gray-700 mb-1">Exchange Rate (1 <?php echo htmlspecialchars($base_currency); ?> = X)</label>
                <input type="number" step="0.000001" min="0.000001" name="new_rate" required placeholder="e.g. 0.0125" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs font-mono font-bold focus:ring-1 focus:ring-emerald-500 outline-none">
            </div>

            <!-- Include existing currencies hidden fields so they are preserved -->
            <?php foreach ($currencies as $c): ?>
            <input type="hidden" name="curr_code[]" value="<?php echo htmlspecialchars($c['code']); ?>">
            <input type="hidden" name="curr_symbol[]" value="<?php echo htmlspecialchars($c['symbol']); ?>">
            <input type="hidden" name="curr_name[]" value="<?php echo htmlspecialchars($c['name']); ?>">
            <input type="hidden" name="curr_rate[]" value="<?php echo (float)$c['rate']; ?>">
            <?php if (!empty($c['enabled'])): ?><input type="hidden" name="curr_enabled[<?php echo $c['code']; ?>]" value="1"><?php endif; ?>
            <?php endforeach; ?>
            <input type="hidden" name="multi_currency_enabled" value="<?php echo $multi_enabled ? '1' : '0'; ?>">
            <input type="hidden" name="base_currency" value="<?php echo htmlspecialchars($base_currency); ?>">

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-gray-100">
                <button type="button" onclick="closeAddCurrencyModal()" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-xl text-xs transition cursor-pointer">Cancel</button>
                <button type="submit" name="save_currencies" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs transition shadow-xs cursor-pointer">Add Currency</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddCurrencyModal() {
    document.getElementById('addCurrencyModal').classList.remove('hidden');
}
function closeAddCurrencyModal() {
    document.getElementById('addCurrencyModal').classList.add('hidden');
}

function ajaxToggleCurrency(code, checkbox) {
    var formData = new FormData();
    formData.append('ajax_toggle_currency', '1');
    formData.append('code', code);

    fetch('settings-currency.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        if (!res.success) {
            checkbox.checked = !checkbox.checked;
            alert('Failed to update currency status.');
        }
    })
    .catch(() => {
        checkbox.checked = !checkbox.checked;
        alert('Connection error.');
    });
}
</script>

<?php include 'footer.php'; ?>
