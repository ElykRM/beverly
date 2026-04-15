<?php
include '../includes/auth.php';
include '../includes/header.php';

// Admin-only page
require_admin();
?>

<div class="mb-10">
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-6">
        <h2 class="text-3xl font-bold text-green-800">New Household Registration</h2>
        <a href="../pages/index.php" class="text-green-700 hover:text-green-900 font-medium flex items-center gap-2">
            ← Back to Menu
        </a>
    </div>

    <p class="text-gray-600 mb-8">Enter details for the primary household member. Additional members and vehicles can be added below.</p>
</div>

<form action="../actions/save_household.php" method="POST" class="bg-white p-8 rounded-xl shadow-lg border border-gray-200">

    <!-- Primary Household Member & Address -->
    <div class="border border-gray-200 rounded-lg p-6 bg-gray-50 mb-12">
        <h3 class="text-lg font-semibold text-green-800 mb-4">Primary Household Member & Address</h3>
        
        <!-- Names & Status -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                <input type="text" name="last_name" placeholder="e.g. Dela Cruz" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                <input type="text" name="first_name" placeholder="e.g. Juan" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Middle Name</label>
                <input type="text" name="middle_name" placeholder="e.g. Santos" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Home Status</label>
                <div class="flex flex-wrap gap-4 mt-2">
                    <label class="inline-flex items-center">
                        <input type="radio" name="home_status" value="Owner" checked class="form-radio text-green-600">
                        <span class="ml-2">Owner</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="radio" name="home_status" value="Renter" class="form-radio text-green-600">
                        <span class="ml-2">Renter</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Personal Info -->
        <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Birthday</label>
                <input type="date" name="birthday" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
                <select name="gender" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
                    <option value="">Select</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Contact No.</label>
                <input type="tel" name="contact_no" placeholder="e.g. 09171234567" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Occupation</label>
                <input type="text" name="occupation" placeholder="e.g. Teacher" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">No. of pets</label>
                <input type="number" name="num_pets" min="0" value="0" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
            </div>
        </div>

        <!-- Address -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Block</label>
                <input type="text" name="block" placeholder="e.g. 3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Lot</label>
                <input type="text" name="lot" placeholder="e.g. 15" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Street</label>
                <input type="text" name="street" placeholder="e.g. Sampaguita St." class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
            </div>
        </div>

        <!-- Move-in/Move-out Dates -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date of move-in</label>
                <input type="date" name="move_in_date" id="move_in_date" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
                <p class="text-xs text-gray-500 mt-1">Ex: April 6, 2026</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Move-out status</label>
                <select name="move_out_status" id="move_out_status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
                    <option value="current" selected>Currently living here</option>
                    <option value="moved">Moved out - Select date</option>
                </select>
            </div>
            <div id="move_out_date_group" class="hidden">
                <label class="block text-sm font-medium text-gray-700 mb-1">Date of move-out</label>
                <input type="date" name="move_out_date" id="move_out_date" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
            </div>
        </div>
    </div>

    <!-- Additional Household Members -->
    <div class="border-t border-gray-200 pt-8 mb-12">
        <h3 class="text-xl font-semibold text-green-800 mb-4">Additional Household Members</h3>
        <p class="text-sm text-gray-600 mb-4">Add spouse, children, parents, tenants, etc. (optional)</p>
        
        <div id="members-container" class="space-y-6">
            <!-- Members will be added here -->
        </div>

        <button type="button" id="add-member-btn" class="mt-4 inline-block bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-5 rounded-lg text-sm">
            + Add Another Member
        </button>
    </div>

    <!-- Vehicles -->
    <div class="border-t border-gray-200 pt-8 mb-12">
        <div class="border border-gray-200 rounded-lg p-6 bg-gray-50">
            <h3 class="text-xl font-semibold text-green-800 mb-4">Vehicles</h3>
            <p class="text-sm text-gray-600 mb-4">Add or edit Vehicles</p>
            
            <div id="vehicles-container" class="space-y-6">
                <!-- Vehicles will be added here -->
            </div>

            <button type="button" id="add-vehicle-btn" class="mt-4 inline-block bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-6 rounded-lg">
                + Add Vehicle
            </button>
        </div>
    </div>

<div class="mt-12 flex flex-col sm:flex-row justify-end gap-4">
    <a href="../pages/habitants.php" class="inline-block bg-gray-500 hover:bg-gray-600 text-white font-medium py-3 px-10 rounded-lg shadow transition text-center">
        Cancel
    </a>
    <button type="submit" class="bg-green-700 hover:bg-green-800 text-white font-bold py-3 px-10 rounded-lg shadow-lg transition">
        Save Household Record
    </button>
</div>


<script>
// Additional Members - Dynamic rows
let memberIndex = 0;

document.getElementById('add-member-btn').addEventListener('click', function() {
    const container = document.getElementById('members-container');
    const newBlock = document.createElement('div');
    newBlock.className = 'member-block border border-gray-200 rounded-lg p-6 bg-gray-50';
    newBlock.innerHTML = `
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                <input type="text" name="members[${memberIndex}][last_name]" placeholder="e.g. Dela Cruz" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                <input type="text" name="members[${memberIndex}][first_name]" placeholder="e.g. Juan" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Middle Name</label>
                <input type="text" name="members[${memberIndex}][middle_name]" placeholder="e.g. Santos" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Relation</label>
                <select name="members[${memberIndex}][relation]" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
                    <option value="">Select</option>
                    <option value="Spouse">Spouse</option>
                    <option value="Child">Child</option>
                    <option value="Parent">Parent</option>
                    <option value="Sibling">Sibling</option>
                    <option value="Tenant">Tenant</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Birthday</label>
                <input type="date" name="members[${memberIndex}][birthday]" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
                <select name="members[${memberIndex}][gender]" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
                    <option value="">Select</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Contact No.</label>
                <input type="tel" name="members[${memberIndex}][contact_no]" placeholder="e.g. 09171234567" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Occupation</label>
                <input type="text" name="members[${memberIndex}][occupation]" placeholder="e.g. Teacher" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
            </div>
        </div>

        <div class="mt-4 text-right">
            <button type="button" class="remove-member text-red-600 hover:text-red-800 font-medium text-sm">
                Remove 
            </button>
        </div>
    `;
    container.appendChild(newBlock);
    memberIndex++;
});

// Remove member - allows removing all
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('remove-member')) {
        const block = e.target.closest('.member-block');
        showDeleteConfirmModal('Are you sure you want to remove this member?', () => {
            block.remove();
        });
    }
});

// Vehicles - Dynamic rows
let vehicleIndex = 0;

document.getElementById('add-vehicle-btn').addEventListener('click', function() {
    const container = document.getElementById('vehicles-container');
    const newRow = document.createElement('div');
    newRow.className = 'vehicle-row grid grid-cols-1 md:grid-cols-5 gap-6';
    newRow.innerHTML = `
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Brand</label>
            <input type="text" name="vehicles[${vehicleIndex}][brand]" placeholder="e.g. Toyota" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Type / Model</label>
            <input type="text" name="vehicles[${vehicleIndex}][type_model]" placeholder="e.g. Vios" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Color</label>
            <input type="text" name="vehicles[${vehicleIndex}][color]" placeholder="e.g. White" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Plate No.</label>
            <input type="text" name="vehicles[${vehicleIndex}][plate_no]" placeholder="e.g. ABC 1234" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
        </div>
        <div class="flex items-end">
            <button type="button" class="remove-vehicle text-red-600 hover:text-red-800 font-medium">Remove</button>
        </div>
    `;
    container.appendChild(newRow);
    vehicleIndex++;
});

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
        textAlign: 'left',
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

// Remove vehicle - allows removing all
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('remove-vehicle')) {
        const row = e.target.closest('.vehicle-row');
        showDeleteConfirmModal('Are you sure you want to remove this vehicle?', () => {
            row.remove();
        });
    }
});

// Modal popup helper
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
        textAlign: 'left',
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

// Validate form before submit - warn about incomplete entries
const householdForm = document.querySelector('form');

householdForm.addEventListener('submit', function(e) {
    const incompleteMembers = [];
    const incompleteVehicles = [];

    // Check members for incomplete entries
    document.querySelectorAll('.member-block').forEach((block, idx) => {
        const firstName = block.querySelector('[name*="[first_name]"]')?.value.trim() || '';
        const lastName = block.querySelector('[name*="[last_name]"]')?.value.trim() || '';
        const relation = block.querySelector('[name*="[relation]"]')?.value.trim() || '';
        const hasAnyData = firstName || lastName || relation;

        if (hasAnyData && !firstName && !lastName) {
            incompleteMembers.push(`Member: At least a first or last name is required.`);
        } else if (hasAnyData && !relation && (firstName || lastName)) {
            incompleteMembers.push(`Member: Please select a relation to save member information.`);
        }
    });

    // Check vehicles for incomplete entries
    document.querySelectorAll('.vehicle-row').forEach((row, idx) => {
        const brand = row.querySelector('[name*="[brand]"]')?.value.trim() || '';
        const typeModel = row.querySelector('[name*="[type_model]"]')?.value.trim() || '';
        const color = row.querySelector('[name*="[color]"]')?.value.trim() || '';
        const plateNo = row.querySelector('[name*="[plate_no]"]')?.value.trim() || '';
        const hasAnyData = brand || typeModel || color || plateNo;

        if (hasAnyData && !plateNo) {
            incompleteVehicles.push(`Vehicle: Plate number is required to save vehicle information.`);
        }
    });

    // Show notice if there are incomplete entries
    if (incompleteMembers.length || incompleteVehicles.length) {
        const warnings = [
            ...incompleteMembers,
            ...incompleteVehicles
        ];

        const warningLines = warnings.map((warning, index) => `${index + 1}. ${warning}`).join('\n');
        const fullMessage = 'Please complete the following entries or remove them:\n\n' + warningLines;
        
        e.preventDefault();
        showNoticeModal(fullMessage);
    }
});

// Handle move-out status dropdown
const moveOutStatusSelect = document.getElementById('move_out_status');
const moveOutDateGroup = document.getElementById('move_out_date_group');
const moveOutDateField = document.getElementById('move_out_date');

if (moveOutStatusSelect) {
    moveOutStatusSelect.addEventListener('change', function() {
        if (this.value === 'current') {
            moveOutDateGroup.classList.add('hidden');
            moveOutDateField.value = '';
        } else {
            moveOutDateGroup.classList.remove('hidden');
            moveOutDateField.focus();
        }
    });
}
</script>

<?php include '../includes/footer.php'; ?>