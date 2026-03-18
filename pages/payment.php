<?php
include '../includes/auth.php';
include '../db.php';
include '../includes/header.php';

// Fetch households
$hstmt = $pdo->prepare("
    SELECT id, CONCAT(last_name, ', ', first_name, ' — ',
           COALESCE(CONCAT('Block ', block, ' Lot ', lot), 'No address')) AS display
    FROM households 
    ORDER BY last_name, first_name
");
$hstmt->execute();
$households = $hstmt->fetchAll();

$preselect_id = $_GET['household_id'] ?? null;

// Months & years
$months = [
    '01' => 'January', '02' => 'February', '03' => 'March', '04' => 'April',
    '05' => 'May', '06' => 'June', '07' => 'July', '08' => 'August',
    '09' => 'September', '10' => 'October', '11' => 'November', '12' => 'December'
];
$currentYear = (int)date('Y');
$years = range($currentYear - 5, $currentYear + 10);
?>

<div class="mb-10">
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-6">
        <h2 class="text-3xl font-bold text-green-800">Record Dues Payment</h2>
        <a href="../pages/index.php" class="text-green-700 hover:text-green-900 font-medium flex items-center gap-2">
            ← Back to Menu
        </a>
    </div>

    <p class="text-gray-600 mb-8">Pay for one month or a range of months. Yearly Promo automatically covers January–December.</p>

    <form action="../actions/save_payment.php" method="POST" class="bg-white p-8 rounded-xl shadow-lg border border-gray-200 max-w-4xl mx-auto" id="payment-form">

        <!-- Household -->
        <div class="mb-8">
            <label for="household_search" class="block text-sm font-medium text-gray-700 mb-2">Household</label>
            <div class="relative">
                <input 
                    type="text" 
                    id="household_search" 
                    placeholder="Search by name, block, or lot..." 
                    autocomplete="off"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
                <input type="hidden" name="household_id" id="household_id" required>
                <ul id="suggestions-list" class="absolute top-full left-0 right-0 mt-1 border border-gray-300 bg-white rounded-lg shadow-lg max-h-60 overflow-y-auto hidden z-50">
                </ul>
            </div>
            <p id="selection-note" class="text-xs text-gray-500 mt-1 hidden">Selected: <span id="selected-household"></span></p>
        </div>

        <!-- Payment type toggle -->
        <div class="mb-8">
            <label class="block text-sm font-medium text-gray-700 mb-2">Payment covers</label>
            <div class="flex gap-8">
                <label class="inline-flex items-center cursor-pointer">
                    <input type="radio" name="payment_type" value="single" checked class="form-radio text-green-600" id="type-single">
                    <span class="ml-2">Single month</span>
                </label>
                <label class="inline-flex items-center cursor-pointer">
                    <input type="radio" name="payment_type" value="range" class="form-radio text-green-600" id="type-range">
                    <span class="ml-2">Range of months</span>
                </label>
            </div>
        </div>

        <!-- Promo checkbox -->
        <div class="mb-8">
            <label class="inline-flex items-center cursor-pointer">
                <input type="checkbox" name="is_promo" id="is_promo" value="1" class="form-checkbox text-green-600 rounded">
                <span class="ml-2 text-sm font-medium text-gray-700">Apply Yearly Promo (₱1,000 for full year Jan–Dec)</span>
            </label>
            <p class="text-xs text-gray-500 mt-1">When checked, payment automatically covers the full selected year.</p>
        </div>

        <!-- Single month -->
        <div id="single-group" class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div>
                <label for="single_month" class="block text-sm font-medium text-gray-700 mb-2">Month</label>
                <select name="single_month" id="single_month" class="w-full px-4 py-3 border border-gray-300 rounded-lg">
                    <?php foreach ($months as $num => $name): ?>
                        <option value="<?= $num ?>"><?= $name ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="single_year" class="block text-sm font-medium text-gray-700 mb-2">Year</label>
                <select name="single_year" id="single_year" class="w-full px-4 py-3 border border-gray-300 rounded-lg">
                    <?php foreach ($years as $y): ?>
                        <option value="<?= $y ?>" <?= $y == $currentYear ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Range -->
        <div id="range-group" class="hidden grid-cols-1 md:grid-cols-2 gap-8 mb-8">
            <div class="border-r border-gray-200 pr-6">
                <h4 class="text-base font-medium text-gray-800 mb-4">From</h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <label for="from_month" class="block text-sm font-medium text-gray-700 mb-2">Month</label>
                        <select name="from_month" id="from_month" class="w-full px-4 py-3 border border-gray-300 rounded-lg">
                            <?php foreach ($months as $num => $name): ?>
                                <option value="<?= $num ?>"><?= $name ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="from_year" class="block text-sm font-medium text-gray-700 mb-2">Year</label>
                        <select name="from_year" id="from_year" class="w-full px-4 py-3 border border-gray-300 rounded-lg">
                            <?php foreach ($years as $y): ?>
                                <option value="<?= $y ?>" <?= $y == $currentYear ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div>
                <h4 class="text-base font-medium text-gray-800 mb-4">To</h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <label for="to_month" class="block text-sm font-medium text-gray-700 mb-2">Month</label>
                        <select name="to_month" id="to_month" class="w-full px-4 py-3 border border-gray-300 rounded-lg">
                            <?php foreach ($months as $num => $name): ?>
                                <option value="<?= $num ?>"><?= $name ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="to_year" class="block text-sm font-medium text-gray-700 mb-2">Year</label>
                        <select name="to_year" id="to_year" class="w-full px-4 py-3 border border-gray-300 rounded-lg">
                            <?php foreach ($years as $y): ?>
                                <option value="<?= $y ?>" <?= $y == $currentYear ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- OR, Amount, Remarks -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div>
                <label for="or_no" class="block text-sm font-medium text-gray-700 mb-2">OR Number</label>
                <input type="text" name="or_no" id="or_no" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
            </div>
            <div>
                <label for="amount" class="block text-sm font-medium text-gray-700 mb-2">Total Amount Paid (₱)</label>
                <input type="number" name="amount" id="amount" step="0.01" min="0" required 
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                       value="0.00">
                <p id="amount-note" class="text-xs text-gray-500 mt-1 hidden italic">
                    Locked to ₱1,000.00 for Yearly Promo (Jan–Dec)
                </p>
            </div>
            <div class="md:col-span-2">
                <label for="remarks" class="block text-sm font-medium text-gray-700 mb-2">Remarks (optional)</label>
                <textarea name="remarks" id="remarks" rows="3" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"></textarea>
            </div>
        </div>

        <!-- Submit -->
        <div class="text-right mt-12">
            <button type="submit" class="bg-green-700 hover:bg-green-800 text-white font-bold py-3 px-10 rounded-lg shadow-lg transition">
                Record Payment
            </button>
        </div>
    </form>
</div>

<script>
// Household search with suggestions
const households = <?= json_encode($households) ?>;
const searchInput = document.getElementById('household_search');
const suggestionsList = document.getElementById('suggestions-list');
const householdIdField = document.getElementById('household_id');
const selectionNote = document.getElementById('selection-note');
const selectedHouseholdSpan = document.getElementById('selected-household');

let selectedHouseholdData = null;

// Preselect if coming from view page
<?php if ($preselect_id): ?>
const preselectedHousehold = households.find(h => h.id == <?= $preselect_id ?>);
if (preselectedHousehold) {
    searchInput.value = preselectedHousehold.display;
    householdIdField.value = preselectedHousehold.id;
    selectedHouseholdData = preselectedHousehold;
    selectionNote.classList.remove('hidden');
    selectedHouseholdSpan.textContent = preselectedHousehold.display;
}
<?php endif; ?>

searchInput.addEventListener('input', (e) => {
    const query = e.target.value.toLowerCase().trim();
    
    if (query.length === 0) {
        suggestionsList.classList.add('hidden');
        householdIdField.value = '';
        selectionNote.classList.add('hidden');
        selectedHouseholdData = null;
        return;
    }

    const filtered = households.filter(h => 
        h.display.toLowerCase().includes(query)
    );

    if (filtered.length === 0) {
        suggestionsList.innerHTML = '<li class="px-4 py-2 text-gray-500 italic">No households found</li>';
        suggestionsList.classList.remove('hidden');
        householdIdField.value = '';
        selectionNote.classList.add('hidden');
        return;
    }

    suggestionsList.innerHTML = filtered.map(h => `
        <li class="px-4 py-3 hover:bg-green-50 cursor-pointer border-b border-gray-100 last:border-b-0 transition"
            data-id="${h.id}" data-display="${h.display}">
            ${h.display}
        </li>
    `).join('');

    suggestionsList.classList.remove('hidden');

    // Add click handlers to suggestions
    document.querySelectorAll('#suggestions-list li').forEach(item => {
        item.addEventListener('click', () => {
            const id = item.dataset.id;
            const display = item.dataset.display;
            
            searchInput.value = display;
            householdIdField.value = id;
            selectedHouseholdData = { id, display };
            suggestionsList.classList.add('hidden');
            
            selectionNote.classList.remove('hidden');
            selectedHouseholdSpan.textContent = display;
        });
    });
});

// Hide suggestions when clicking outside
document.addEventListener('click', (e) => {
    if (e.target !== searchInput && !suggestionsList.contains(e.target)) {
        suggestionsList.classList.add('hidden');
    }
});

// Toggle single vs range
const typeRadios = document.querySelectorAll('input[name="payment_type"]');
const singleGroup = document.getElementById('single-group');
const rangeGroup = document.getElementById('range-group');

typeRadios.forEach(radio => {
    radio.addEventListener('change', () => {
        const isRange = radio.value === 'range';
        singleGroup.classList.toggle('hidden', isRange);
        rangeGroup.classList.toggle('hidden', !isRange);
        updatePromoState();
    });
});

// Promo: force full year + lock amount with readonly
const promoCheckbox = document.getElementById('is_promo');
const amountInput = document.getElementById('amount');
const amountNote = document.getElementById('amount-note');
let originalAmount = amountInput.value || '0.00';

promoCheckbox.addEventListener('change', updatePromoState);

function updatePromoState() {
    const isPromo = promoCheckbox.checked;

    if (isPromo) {
        // Force full year range
        document.getElementById('type-range').checked = true;
        typeRadios.forEach(r => r.disabled = true);

        singleGroup.classList.add('hidden');
        rangeGroup.classList.remove('hidden');

        const yearValue = document.getElementById('single_year')?.value || 
                          document.getElementById('from_year')?.value || 
                          '<?= $currentYear ?>';

        document.getElementById('from_month').value = '01';
        document.getElementById('from_year').value = yearValue;
        document.getElementById('to_month').value = '12';
        document.getElementById('to_year').value = yearValue;

        document.querySelectorAll('#range-group select').forEach(sel => sel.disabled = true);

        // Lock amount visually & functionally
        originalAmount = amountInput.value;
        amountInput.value = '1000.00';
        amountInput.readOnly = true;
        amountInput.classList.add('bg-gray-100', 'cursor-not-allowed');
        amountNote.classList.remove('hidden');
    } else {
        // Restore normal
        typeRadios.forEach(r => r.disabled = false);
        document.querySelectorAll('#range-group select').forEach(sel => sel.disabled = false);

        amountInput.readOnly = false;
        amountInput.classList.remove('bg-gray-100', 'cursor-not-allowed');
        amountNote.classList.add('hidden');

        amountInput.value = originalAmount;
    }
}

// Extra safety: block submit if amount invalid
document.getElementById('payment-form').addEventListener('submit', function(e) {
    const amt = parseFloat(amountInput.value);
    if (isNaN(amt) || amt <= 0) {
        e.preventDefault();
        alert('Amount must be greater than zero.');
        amountInput.focus();
    }
});

// Initial state
updatePromoState();
</script>

<?php include '../includes/footer.php'; ?>