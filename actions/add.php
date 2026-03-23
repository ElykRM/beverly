<?php
include '../includes/auth.php';
include '../includes/header.php';
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
                <input type="text" name="last_name" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                <input type="text" name="first_name" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Middle Name</label>
                <input type="text" name="middle_name" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
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
                <input type="tel" name="contact_no" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Occupation</label>
                <input type="text" name="occupation" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">No. of pets</label>
                <input type="number" name="num_pets" min="0" value="0" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
            </div>
        </div>

        <!-- Address -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Block</label>
                <input type="text" name="block" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Lot</label>
                <input type="text" name="lot" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Street</label>
                <input type="text" name="street" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
            </div>
        </div>
    </div>

    <!-- Additional Household Members -->
    <div class="border-t border-gray-200 pt-8 mb-12">
        <h3 class="text-xl font-semibold text-green-800 mb-4">Additional Household Members</h3>
        <p class="text-sm text-gray-600 mb-4">Add spouse, children, parents, tenants, etc. (optional)</p>
        
        <div id="members-container" class="space-y-6">
            <!-- Default block -->
            <div class="member-block border border-gray-200 rounded-lg p-6 bg-gray-50">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                        <input type="text" name="members[0][last_name]" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                        <input type="text" name="members[0][first_name]" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Middle Name</label>
                        <input type="text" name="members[0][middle_name]" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Relation</label>
                        <select name="members[0][relation]" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
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
                        <input type="date" name="members[0][birthday]" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
                        <select name="members[0][gender]" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
                            <option value="">Select</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Contact No.</label>
                        <input type="tel" name="members[0][contact_no]" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Occupation</label>
                        <input type="text" name="members[0][occupation]" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
                    </div>
                </div>

                <div class="mt-4 text-right">
                    <button type="button" class="remove-member text-red-600 hover:text-red-800 font-medium text-sm">
                        Remove this member
                    </button>
                </div>
            </div>
        </div>

        <button type="button" id="add-member-btn" class="mt-4 inline-block bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-5 rounded-lg text-sm">
            + Add Another Member
        </button>
    </div>

    <!-- Vehicles -->
    <div class="border-t border-gray-200 pt-8 mb-12">
        <div class="border border-gray-200 rounded-lg p-6 bg-gray-50">
            <h3 class="text-xl font-semibold text-green-800 mb-4">Vehicles</h3>
            
            <div id="vehicles-container" class="space-y-6">
                <div class="vehicle-row grid grid-cols-1 md:grid-cols-5 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Brand</label>
                        <input type="text" name="vehicles[0][brand]" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Type / Model</label>
                        <input type="text" name="vehicles[0][type_model]" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Color</label>
                        <input type="text" name="vehicles[0][color]" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Plate No.</label>
                        <input type="text" name="vehicles[0][plate_no]" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
                    </div>
                    <div class="flex items-end">
                        <button type="button" class="remove-vehicle text-red-600 hover:text-red-800 font-medium">Remove</button>
                    </div>
                </div>
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
let memberIndex = 1;
document.getElementById('add-member-btn').addEventListener('click', function() {
    const container = document.getElementById('members-container');
    const memberBlock = document.querySelector('.member-block').cloneNode(true);
    
    // Update all input names to use the new index
    memberBlock.querySelectorAll('input, select').forEach(field => {
        const oldName = field.getAttribute('name');
        if (oldName) {
            field.setAttribute('name', oldName.replace(/members\[\d+\]/, `members[${memberIndex}]`));
            field.value = '';
        }
    });
    
    container.appendChild(memberBlock);
    memberIndex++;
});

document.addEventListener('click', function(e) {
    if (e.target.classList.contains('remove-member')) {
        const block = e.target.closest('.member-block');
        if (document.querySelectorAll('.member-block').length > 1) {
            block.remove();
        }
    }
});

let vehicleIndex = 1;
document.getElementById('add-vehicle-btn').addEventListener('click', function() {
    const container = document.getElementById('vehicles-container');
    const vehicleRow = document.querySelector('.vehicle-row').cloneNode(true);
    
    // Update all input names to use the new index
    vehicleRow.querySelectorAll('input').forEach(field => {
        const oldName = field.getAttribute('name');
        if (oldName) {
            field.setAttribute('name', oldName.replace(/vehicles\[\d+\]/, `vehicles[${vehicleIndex}]`));
            field.value = '';
        }
    });
    
    container.appendChild(vehicleRow);
    vehicleIndex++;
});

document.addEventListener('click', function(e) {
    if (e.target.classList.contains('remove-vehicle')) {
        const row = e.target.closest('.vehicle-row');
        if (document.querySelectorAll('.vehicle-row').length > 1) {
            row.remove();
        }
    }
});
</script>

<?php include '../includes/footer.php'; ?>