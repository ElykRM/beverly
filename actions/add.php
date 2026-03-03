<?php include '../includes/header.php'; ?>

<div class="mb-10">
    <h2 class="text-3xl font-bold text-green-800 mb-2">New Household / Member Registration</h2>
    <p class="text-gray-600 mb-8">Enter details for the primary household member. Vehicles can be added below.</p>
</div>

<form action="../actions/save_household.php" method="POST" class="bg-white p-8 rounded-xl shadow-lg border border-gray-200">

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
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
                <label class="inline-flex items-center">
                    <input type="radio" name="home_status" value="Member" class="form-radio text-green-600">
                    <span class="ml-2">Member</span>
                </label>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
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

    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-6 mb-8">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Birthday</label>
            <input type="date" name="birthday" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Age</label>
            <input type="number" name="age" min="0" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
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

    <div class="border-t border-gray-200 pt-8">
        <h3 class="text-xl font-semibold text-green-800 mb-4">Vehicles</h3>
        
        <div id="vehicles-container" class="space-y-6">
            <div class="vehicle-row grid grid-cols-1 md:grid-cols-5 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Brand</label>
                    <input type="text" name="vehicles[0][brand]" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Type / Model</label>
                    <input type="text" name="vehicles[0][type_model]" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Color</label>
                    <input type="text" name="vehicles[0][color]" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Plate No.</label>
                    <input type="text" name="vehicles[0][plate_no]" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
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

    <div class="mt-12 text-right">
        <button type="submit" class="bg-green-700 hover:bg-green-800 text-white font-bold py-3 px-10 rounded-lg shadow-lg transition">
            Save Household Record
        </button>
    </div>
</form>

<script>
    let vehicleIndex = 1;

    document.getElementById('add-vehicle-btn').addEventListener('click', function() {
        const container = document.getElementById('vehicles-container');
        const newRow = document.createElement('div');
        newRow.className = 'vehicle-row grid grid-cols-1 md:grid-cols-5 gap-6';
        newRow.innerHTML = `
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Brand</label>
                <input type="text" name="vehicles[${vehicleIndex}][brand]" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Type / Model</label>
                <input type="text" name="vehicles[${vehicleIndex}][type_model]" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Color</label>
                <input type="text" name="vehicles[${vehicleIndex}][color]" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Plate No.</label>
                <input type="text" name="vehicles[${vehicleIndex}][plate_no]" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
            </div>
            <div class="flex items-end">
                <button type="button" class="remove-vehicle text-red-600 hover:text-red-800 font-medium">Remove</button>
            </div>
        `;
        container.appendChild(newRow);
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