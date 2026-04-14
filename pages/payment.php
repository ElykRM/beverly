<?php
include '../includes/auth.php';
include '../db.php';
include '../includes/header.php';

// Admin-only page
require_admin();

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
$popup_error = isset($_GET['error']) ? trim((string)$_GET['error']) : '';

// Months & years
$months = [
    '01' => 'January', '02' => 'February', '03' => 'March', '04' => 'April',
    '05' => 'May', '06' => 'June', '07' => 'July', '08' => 'August',
    '09' => 'September', '10' => 'October', '11' => 'November', '12' => 'December'
];
$currentYear = (int)date('Y');
$years = range($currentYear - 10, $currentYear + 10);

// Fetch all exemptions for reference (will be filtered by JS on household select)
$exemptStmt = $pdo->prepare("
    SELECT household_id, exemption_year, exemption_month, exemption_to_year, exemption_to_month, reason
    FROM exemptions
    ORDER BY household_id, exemption_year DESC, exemption_month
");
$exemptStmt->execute();
$allExemptions = $exemptStmt->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC);
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

        <!-- Manage Exemptions -->
        <div class="mb-8" id="manage-exemption-section" style="display: none;">
            <button type="button" id="toggle-exemption-btn" class="w-full text-left px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-200 transition">
                <span class="text-sm font-medium text-gray-700">Manage Exemptions</span>
                <span id="toggle-text" class="text-xs text-gray-500 float-right">Show</span>
            </button>
        
            
            <div id="exemption-form" style="display: none; margin-top: 0.5rem;" class="border border-gray-300 rounded-lg p-4 bg-gray-50">
                <!-- Exemption Type Selection -->
                <div class="mb-4 p-3 bg-white border border-gray-200 rounded">
                    <p class="text-xs font-medium text-gray-700 mb-2">Exemption Type:</p>
                    <div class="flex gap-6 flex-wrap">
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="radio" name="exempt_type" value="year" checked class="form-radio text-gray-600" 
                                   onchange="updateExemptionTypeUI()">
                            <span class="ml-2 text-sm text-gray-700">Full Year</span>
                        </label>
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="radio" name="exempt_type" value="month" class="form-radio text-gray-600"
                                   onchange="updateExemptionTypeUI()">
                            <span class="ml-2 text-sm text-gray-700">Single Month</span>
                        </label>
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="radio" name="exempt_type" value="range" class="form-radio text-gray-600"
                                   onchange="updateExemptionTypeUI()">
                            <span class="ml-2 text-sm text-gray-700">Range</span>
                        </label>
                    </div>
                </div>
                
                <!-- Year Input (Full Year) -->
                <div id="exempt-year-inputs" class="flex gap-2 mb-3">
                    <select id="exemption_year_add" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        <option value="">Select year</option>
                    </select>
                </div>
                
                <!-- Month Input (Single Month) -->
                <div id="exempt-month-inputs" class="hidden flex gap-2 mb-3">
                    <select id="exemption_month_add" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        <option value="">Month</option>
                        <option value="1">January</option><option value="2">February</option><option value="3">March</option>
                        <option value="4">April</option><option value="5">May</option><option value="6">June</option>
                        <option value="7">July</option><option value="8">August</option><option value="9">September</option>
                        <option value="10">October</option><option value="11">November</option><option value="12">December</option>
                    </select>
                    <select id="exemption_year_single_add" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        <option value="">Year</option>
                    </select>
                </div>
                
                <!-- Range Input -->
                <div id="exempt-range-inputs" class="hidden flex gap-2 mb-3">
                    <div class="flex gap-2 items-center">
                        <span class="text-xs text-gray-600">From:</span>
                        <select id="exemption_from_month_add" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            <option value="">Month</option>
                            <option value="1">January</option><option value="2">February</option><option value="3">March</option>
                            <option value="4">April</option><option value="5">May</option><option value="6">June</option>
                            <option value="7">July</option><option value="8">August</option><option value="9">September</option>
                            <option value="10">October</option><option value="11">November</option><option value="12">December</option>
                        </select>
                        <select id="exemption_from_year_add" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            <option value="">Year</option>
                        </select>
                    </div>
                    <div class="flex gap-2 items-center">
                        <span class="text-xs text-gray-600">To:</span>
                        <select id="exemption_to_month_add" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            <option value="">Month</option>
                            <option value="1">January</option><option value="2">February</option><option value="3">March</option>
                            <option value="4">April</option><option value="5">May</option><option value="6">June</option>
                            <option value="7">July</option><option value="8">August</option><option value="9">September</option>
                            <option value="10">October</option><option value="11">November</option><option value="12">December</option>
                        </select>
                        <select id="exemption_to_year_add" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            <option value="">Year</option>
                        </select>
                    </div>
                </div>
                
                <!-- Reason + Add button -->
                <div class="flex gap-2 mb-3">
                    <input type="text" id="exemption_reason" placeholder="Reason (optional)" class="px-3 py-2 border border-gray-300 rounded-lg text-sm flex-1">
                    <button type="button" id="add-exemption-btn" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg text-sm shadow-md">
                        Add
                    </button>
                </div>
                
                <!-- Current Exemptions List -->
                <div class="mt-4 pt-4 border-t border-gray-300">
                    <p class="text-xs font-medium text-gray-700 mb-2">Active Exemptions:</p>
                    <div id="current-exemptions" class="space-y-2">
                        <!-- Populated by JS -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Hidden field for active exemption (auto-populated on submission) -->
        <input type="hidden" name="exemption_year" id="exemption_year" value="">

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
const allExemptions = <?= json_encode($allExemptions) ?>;
const currentYear = <?= $currentYear ?>;
const years = Array.from({length: 21}, (_, i) => currentYear - 10 + i);
const searchInput = document.getElementById('household_search');
const suggestionsList = document.getElementById('suggestions-list');
const householdIdField = document.getElementById('household_id');
const selectionNote = document.getElementById('selection-note');
const selectedHouseholdSpan = document.getElementById('selected-household');

let selectedHouseholdData = null;
let currentExemptionIndex = null; // Track currently applied exemption

// Function to populate year dropdown for adding exemptions
function populateYearDropdown() {
    const dropdown = document.getElementById('exemption_year_add');
    const singleDropDown = document.getElementById('exemption_year_single_add');
    const fromDropdown = document.getElementById('exemption_from_year_add');
    const toDropdown = document.getElementById('exemption_to_year_add');
    const yearsHtml = years.map(year => `<option value="${year}">${year}</option>`).join('');
    
    dropdown.innerHTML = '<option value="">Select year</option>' + yearsHtml;
    singleDropDown.innerHTML = '<option value="">Year</option>' + yearsHtml;
    fromDropdown.innerHTML = '<option value="">Year</option>' + yearsHtml;
    toDropdown.innerHTML = '<option value="">Year</option>' + yearsHtml;
}

// Update UI based on exemption type selection
function updateExemptionTypeUI() {
    const type = document.querySelector('input[name="exempt_type"]:checked').value;
    document.getElementById('exempt-year-inputs').style.display = type === 'year' ? 'flex' : 'none';
    document.getElementById('exempt-month-inputs').style.display = type === 'month' ? 'flex' : 'none';
    document.getElementById('exempt-range-inputs').style.display = type === 'range' ? 'flex' : 'none';
}

// Preselect if coming from view page
<?php if ($preselect_id): ?>
const preselectedHousehold = households.find(h => h.id == <?= $preselect_id ?>);
if (preselectedHousehold) {
    searchInput.value = preselectedHousehold.display;
    householdIdField.value = preselectedHousehold.id;
    selectedHouseholdData = preselectedHousehold;
    selectionNote.classList.remove('hidden');
    selectedHouseholdSpan.textContent = preselectedHousehold.display;
    updateExemptionDisplay(preselectedHousehold.id);
}
<?php endif; ?>

searchInput.addEventListener('input', (e) => {
    const query = e.target.value.toLowerCase().trim();
    
    if (query.length === 0) {
        suggestionsList.classList.add('hidden');
        householdIdField.value = '';
        selectionNote.classList.add('hidden');
        document.getElementById('manage-exemption-section').style.display = 'none';
        currentExemptionIndex = null;
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
        document.getElementById('manage-exemption-section').style.display = 'none';
        currentExemptionIndex = null;
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
            
            updateExemptionDisplay(id);
        });
    });
});

// Hide suggestions when clicking outside
document.addEventListener('click', (e) => {
    if (e.target !== searchInput && !suggestionsList.contains(e.target)) {
        suggestionsList.classList.add('hidden');
    }
});

// Update exemption display for selected household
function updateExemptionDisplay(householdId) {
    const exemptions = allExemptions[householdId] || [];
    const manageSection = document.getElementById('manage-exemption-section');
    const currentExemptionsDiv = document.getElementById('current-exemptions');
    
    if (!manageSection) {
        console.error('Manage exemption section not found');
        return;
    }
    
    // Show manage section
    manageSection.style.display = 'block';
    populateYearDropdown();
    updateExemptionTypeUI();
    
    // Helper to format exemption display
    function formatExemptionLabel(ex) {
        const monthNames = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        
        if (!ex.exemption_month) {
            // Full year
            return `Year ${ex.exemption_year}`;
        } else if (!ex.exemption_to_year) {
            // Single month
            return `${monthNames[ex.exemption_month]} ${ex.exemption_year}`;
        } else {
            // Range
            return `${monthNames[ex.exemption_month]} ${ex.exemption_year} — ${monthNames[ex.exemption_to_month]} ${ex.exemption_to_year}`;
        }
    }
    
    // Show current exemptions with delete buttons
    if (exemptions.length === 0) {
        currentExemptionsDiv.innerHTML = '<p class="text-xs text-gray-500 italic">No active exemptions</p>';
    } else {
        currentExemptionsDiv.innerHTML = exemptions.map((ex, idx) => `
            <div class="flex items-center justify-between bg-blue-50 p-3 rounded border border-blue-200 text-sm">
                <span class="text-gray-800">${formatExemptionLabel(ex)} ${ex.reason ? `<span class="text-gray-500">- ${ex.reason}</span>` : ''} ${idx === currentExemptionIndex ? '<span class="ml-2 bg-green-500 text-white px-2 py-1 rounded text-xs font-bold">ACTIVE</span>' : ''}</span>
                <button type="button" class="text-red-600 hover:text-red-800 font-medium text-sm data-index="${idx}">
                    Remove
                </button>
            </div>
        `).join('');
    }
    
    // Add delete handlers
    document.querySelectorAll('.delete-exemption-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const idx = btn.dataset.index;
            const ex = exemptions[idx];
            deleteExemption(householdId, ex);
        });
    });
}

// Add new exemption
function addExemption() {
    const householdId = document.getElementById('household_id').value;
    const exemptType = document.querySelector('input[name="exempt_type"]:checked').value;
    const reason = document.getElementById('exemption_reason').value;
    
    if (!householdId) {
        showNoticeModal('Please select a household');
        return;
    }
    
    let year, month, toYear, toMonth;
    
    if (exemptType === 'year') {
        year = document.getElementById('exemption_year_add').value;
        if (!year) {
            showNoticeModal('Please select a year');
            return;
        }
        month = null;
        toYear = null;
        toMonth = null;
    } else if (exemptType === 'month') {
        month = document.getElementById('exemption_month_add').value;
        year = document.getElementById('exemption_year_single_add').value;
        if (!month || !year) {
            showNoticeModal('Please select month and year');
            return;
        }
        toYear = null;
        toMonth = null;
    } else if (exemptType === 'range') {
        const fromMonth = document.getElementById('exemption_from_month_add').value;
        const fromYear = document.getElementById('exemption_from_year_add').value;
        toMonth = document.getElementById('exemption_to_month_add').value;
        toYear = document.getElementById('exemption_to_year_add').value;
        
        if (!fromMonth || !fromYear || !toMonth || !toYear) {
            showNoticeModal('Please select all date fields for range');
            return;
        }
        
        year = fromYear;
        month = fromMonth;
    }
    
    const payload = {
        action: 'add',
        household_id: parseInt(householdId),
        exemption_year: parseInt(year),
        exemption_month: month ? parseInt(month) : '',
        exemption_to_year: toYear ? parseInt(toYear) : '',
        exemption_to_month: toMonth ? parseInt(toMonth) : '',
        reason: reason
    };
    
    fetch('/beverly/actions/manage_exemption.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: new URLSearchParams(payload)
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) {
            throw new Error(data.error || 'Could not add exemption');
        }
        
        // Add to allExemptions locally
        if (!allExemptions[householdId]) {
            allExemptions[householdId] = [];
        }
        allExemptions[householdId].push({ 
            exemption_year: year, 
            exemption_month: month,
            exemption_to_year: toYear,
            exemption_to_month: toMonth,
            reason: reason 
        });
        
        // Set this new exemption as active (last index)
        currentExemptionIndex = allExemptions[householdId].length - 1;
        
        // Refresh display and clear inputs
        updateExemptionDisplay(householdId);
        document.getElementById('exemption_year_add').value = '';
        document.getElementById('exemption_month_add').value = '';
        document.getElementById('exemption_year_single_add').value = '';
        document.getElementById('exemption_from_month_add').value = '';
        document.getElementById('exemption_from_year_add').value = '';
        document.getElementById('exemption_to_month_add').value = '';
        document.getElementById('exemption_to_year_add').value = '';
        document.getElementById('exemption_reason').value = '';
    })
    .catch(err => showNoticeModal(err.message));
}

// Delete exemption
function deleteExemption(householdId, exemData) {
    showDeleteConfirmModal('Remove this exemption?', () => {
        fetch('/beverly/actions/manage_exemption.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: new URLSearchParams({
                action: 'delete',
                household_id: householdId,
                exemption_year: exemData.exemption_year,
                exemption_month: exemData.exemption_month || '',
                exemption_to_year: exemData.exemption_to_year || '',
                exemption_to_month: exemData.exemption_to_month || ''
            })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                // Remove from allExemptions locally
                if (allExemptions[householdId]) {
                    allExemptions[householdId] = allExemptions[householdId].filter(ex => 
                        !(ex.exemption_year == exemData.exemption_year && 
                          ex.exemption_month == exemData.exemption_month &&
                          ex.exemption_to_year == exemData.exemption_to_year &&
                          ex.exemption_to_month == exemData.exemption_to_month)
                    );
                    
                    // Clear active exemption if we deleted it or list is now empty
                    if (currentExemptionIndex !== null && currentExemptionIndex >= allExemptions[householdId].length) {
                        currentExemptionIndex = null;
                    }
                }
                
                // Refresh display
                updateExemptionDisplay(householdId);
            } else {
                showNoticeModal('Error: ' + (data.error || 'Could not delete exemption'));
            }
        })
        .catch(err => showNoticeModal('Network error'));
    });
}

// Event listener for add exemption button
document.addEventListener('DOMContentLoaded', function() {
    // Initialize year dropdowns
    populateYearDropdown();
    
    const addBtn = document.getElementById('add-exemption-btn');
    if (addBtn) {
        addBtn.addEventListener('click', (e) => {
            e.preventDefault();
            addExemption();
        });
    }
    
    // Household selection handler
    const householdSelect = document.getElementById('household');
    if (householdSelect) {
        householdSelect.addEventListener('change', (e) => {
            const id = e.target.value;
            if (id) {
                updateExemptionDisplay(id);
            }
        });
    }

    // Toggle exemption form visibility
    const toggleBtn = document.getElementById('toggle-exemption-btn');
    if (toggleBtn) {
        toggleBtn.addEventListener('click', (e) => {
            e.preventDefault();
            const form = document.getElementById('exemption-form');
            const text = document.getElementById('toggle-text');
            
            if (form.style.display === 'none') {
                form.style.display = 'block';
                text.textContent = 'Hide';
            } else {
                form.style.display = 'none';
                text.textContent = 'Show';
            }
        });
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
        // Restore normal - FULLY enable EVERYTHING
        console.log('Unlocking promo state');
        
        // Enable all payment type radios
        typeRadios.forEach(r => {
            r.disabled = false;
            console.log('Enabled radio:', r.value);
        });
        
        // Enable ALL selects on the page (belt and suspenders)
        document.querySelectorAll('select').forEach(sel => sel.disabled = false);
        document.querySelectorAll('input[type="text"]').forEach(inp => inp.disabled = false);

        // Unlock amount field
        amountInput.readOnly = false;
        amountInput.classList.remove('bg-gray-100', 'cursor-not-allowed');
        amountNote.classList.add('hidden');
        amountInput.value = originalAmount;

        // Restore single-group visibility  
        document.getElementById('type-single').checked = true;
        singleGroup.classList.remove('hidden');
        rangeGroup.classList.add('hidden');
        
        console.log('Promo unlocked successfully');
    }
}

// Extra safety: block submit if amount invalid
const paymentForm = document.getElementById('payment-form');

// Error styling handled by showNoticeModal

paymentForm.addEventListener('submit', async function(e) {
    // Set the active exemption value in the hidden field
    if (currentExemptionIndex !== null) {
        document.getElementById('exemption_year').value = currentExemptionIndex;
    } else {
        document.getElementById('exemption_year').value = '';
    }
    
    const amt = parseFloat(amountInput.value);
    if (isNaN(amt) || amt <= 0) {
        e.preventDefault();
        showNoticeModal('Amount must be greater than zero.');
        return;
    }

    if (!householdIdField.value) {
        e.preventDefault();
        showNoticeModal('Please select a household from the list.');
        return;
    }

    e.preventDefault();

    try {
        const response = await fetch(paymentForm.action, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: new FormData(paymentForm)
        });

        const payload = await response.json().catch(() => ({}));

        if (!response.ok || !payload.success) {
            showNoticeModal(payload.error || 'Unable to record payment.');
            return;
        }

        window.location.href = payload.redirect || '../pages/payment.php';
    } catch (err) {
        showNoticeModal('Network error. Please try again.');
    }
});

// Initial state
updatePromoState();

// Modal popup for delete confirmations
function showDeleteConfirmModal(message, onConfirm) {
    const existing = document.getElementById('modal-overlay');
    if (existing) existing.remove();

    const overlay = document.createElement('div');
    overlay.id = 'modal-overlay';
    Object.assign(overlay.style, {
        position: 'fixed',
        inset: '0',
        background: 'rgba(0, 0, 0, 0.4)',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        padding: '1rem',
        zIndex: '9999'
    });

    const box = document.createElement('div');
    box.className = 'bg-white rounded-xl shadow-2xl border border-gray-200 p-6 text-center';
    Object.assign(box.style, { width: '100%', maxWidth: '28rem' });
    box.innerHTML = `
        <h3 class="text-lg font-bold text-gray-800 mb-3">Confirm Delete</h3>
        <p class="text-sm text-gray-700 mb-6"></p>
        <div style="display: flex; justify-content: center; gap: 0.75rem; flex-wrap: wrap;">
            <button type="button" class="modal-confirm" style="background-color: #b91c1c; color: white; font-weight: 500; padding: 0.5rem 1.25rem; border-radius: 0.5rem; border: none; cursor: pointer;">Delete</button>
            <button type="button" class="modal-cancel" style="background-color: #6b7280; color: white; font-weight: 500; padding: 0.5rem 1.25rem; border-radius: 0.5rem; border: none; cursor: pointer;">Cancel</button>
        </div>
    `;

    const messageEl = box.querySelector('p');
    messageEl.textContent = message;
    Object.assign(messageEl.style, {
        whiteSpace: 'pre-line',
        textAlign: 'center',
        lineHeight: '1.5'
    });
    const confirmBtn = box.querySelector('.modal-confirm');
    const cancelBtn = box.querySelector('.modal-cancel');

    confirmBtn.addEventListener('click', () => {
        overlay.remove();
        onConfirm();
    });

    cancelBtn.addEventListener('click', () => overlay.remove());
    overlay.addEventListener('click', (ev) => {
        if (ev.target === overlay) overlay.remove();
    });

    overlay.appendChild(box);
    document.body.appendChild(overlay);
    confirmBtn.focus();
}

// Modal popup for notices
function showNoticeModal(message) {
    const existing = document.getElementById('modal-overlay');
    if (existing) existing.remove();

    const overlay = document.createElement('div');
    overlay.id = 'modal-overlay';
    Object.assign(overlay.style, {
        position: 'fixed',
        inset: '0',
        background: 'rgba(0, 0, 0, 0.4)',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        padding: '1rem',
        zIndex: '9999'
    });

    const box = document.createElement('div');
    box.className = 'bg-white rounded-xl shadow-2xl border border-gray-200 p-6 text-center';
    Object.assign(box.style, { width: '100%', maxWidth: '28rem' });
    box.innerHTML = `
        <h3 class="text-lg font-bold text-gray-800 mb-3">Notice</h3>
        <p class="text-sm text-gray-700 mb-6"></p>
        <div class="flex justify-center">
            <button type="button" class="modal-ok bg-green-700 hover:bg-green-800 text-white font-medium py-2 px-6 rounded-lg">OK</button>
        </div>
    `;

    const messageEl = box.querySelector('p');
    messageEl.textContent = message;
    Object.assign(messageEl.style, {
        whiteSpace: 'pre-line',
        textAlign: 'center',
        lineHeight: '1.5'
    });
    const okBtn = box.querySelector('.modal-ok');

    okBtn.addEventListener('click', () => {
        overlay.remove();
    });
    overlay.addEventListener('click', (ev) => {
        if (ev.target === overlay) overlay.remove();
    });

    overlay.appendChild(box);
    document.body.appendChild(overlay);
    okBtn.focus();
}

<?php if ($popup_error !== ''): ?>
showNoticeModal(<?= json_encode($popup_error) ?>);
<?php endif; ?>
</script>

<?php include '../includes/footer.php'; ?>