<?php
include '../db.php';
include '../includes/header.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<div class='text-red-600 text-center text-xl my-10'>Invalid household ID</div>";
    include '../includes/footer.php';
    exit;
}

$id = (int)$_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM households WHERE id = ?");
$stmt->execute([$id]);
$household = $stmt->fetch();

if (!$household) {
    echo "<div class='text-red-600 text-center text-xl my-10'>Household not found</div>";
    include '../includes/footer.php';
    exit;
}

$vstmt = $pdo->prepare("SELECT * FROM vehicles WHERE household_id = ? ORDER BY id");
$vstmt->execute([$id]);
$vehicles = $vstmt->fetchAll();
?>

<div class="mb-10">
    <div class="flex justify-between items-center">
        <h2 class="text-3xl font-bold text-green-800">Edit Household</h2>
        <a href="../actions/view.php?id=<?= $id ?>" class="text-green-700 hover:text-green-900 font-medium">&larr; Back to View</a>
    </div>
</div>

<form action="../actions/update_household.php" method="POST" class="bg-white p-8 rounded-xl shadow-lg border border-gray-200">
    <input type="hidden" name="id" value="<?= $id ?>">

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
            <input type="text" name="last_name" value="<?= htmlspecialchars($household['last_name']) ?>" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
            <input type="text" name="first_name" value="<?= htmlspecialchars($household['first_name']) ?>" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Middle Name</label>
            <input type="text" name="middle_name" value="<?= htmlspecialchars($household['middle_name'] ?? '') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Home Status</label>
            <div class="flex flex-wrap gap-4 mt-2">
                <?php foreach (['Owner', 'Renter', 'Member'] as $status): ?>
                    <label class="inline-flex items-center">
                        <input type="radio" name="home_status" value="<?= $status ?>" <?= $household['home_status'] === $status ? 'checked' : '' ?> class="form-radio text-green-600">
                        <span class="ml-2"><?= $status ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Block</label>
            <input type="text" name="block" value="<?= htmlspecialchars($household['block'] ?? '') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Lot</label>
            <input type="text" name="lot" value="<?= htmlspecialchars($household['lot'] ?? '') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
        </div>
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Street</label>
            <input type="text" name="street" value="<?= htmlspecialchars($household['street'] ?? '') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-6 mb-8">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Birthday</label>
            <input type="date" name="birthday" value="<?= $household['birthday'] ?? '' ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Age</label>
            <input type="number" name="age" min="0" value="<?= $household['age'] ?? '' ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
            <select name="gender" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
                <option value="">Select</option>
                <?php foreach (['Male', 'Female', 'Other'] as $g): ?>
                    <option value="<?= $g ?>" <?= $household['gender'] === $g ? 'selected' : '' ?>><?= $g ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Contact No.</label>
            <input type="tel" name="contact_no" value="<?= htmlspecialchars($household['contact_no'] ?? '') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Occupation</label>
            <input type="text" name="occupation" value="<?= htmlspecialchars($household['occupation'] ?? '') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">No. of pets</label>
            <input type="number" name="num_pets" min="0" value="<?= $household['num_pets'] ?? 0 ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
        </div>
    </div>

    <div class="border-t border-gray-200 pt-8">
        <h3 class="text-xl font-semibold text-green-800 mb-4">Vehicles</h3>
        
        <div id="vehicles-container" class="space-y-6">
            <?php if (empty($vehicles)): ?>
                <div class="vehicle-row grid grid-cols-1 md:grid-cols-5 gap-6">
                    <div><input type="text" name="vehicles[0][brand]" class="w-full px-4 py-2 border border-gray-300 rounded-lg"></div>
                    <div><input type="text" name="vehicles[0][type_model]" class="w-full px-4 py-2 border border-gray-300 rounded-lg"></div>
                    <div><input type="text" name="vehicles[0][color]" class="w-full px-4 py-2 border border-gray-300 rounded-lg"></div>
                    <div><input type="text" name="vehicles[0][plate_no]" class="w-full px-4 py-2 border border-gray-300 rounded-lg"></div>
                    <div class="flex items-end"><button type="button" class="remove-vehicle text-red-600 hover:text-red-800 font-medium">Remove</button></div>
                </div>
            <?php else: ?>
                <?php foreach ($vehicles as $index => $v): ?>
                    <div class="vehicle-row grid grid-cols-1 md:grid-cols-5 gap-6">
                        <div><input type="text" name="vehicles[<?= $index ?>][brand]" value="<?= htmlspecialchars($v['brand'] ?? '') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg"></div>
                        <div><input type="text" name="vehicles[<?= $index ?>][type_model]" value="<?= htmlspecialchars($v['type_model'] ?? '') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg"></div>
                        <div><input type="text" name="vehicles[<?= $index ?>][color]" value="<?= htmlspecialchars($v['color'] ?? '') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg"></div>
                        <div><input type="text" name="vehicles[<?= $index ?>][plate_no]" value="<?= htmlspecialchars($v['plate_no'] ?? '') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg"></div>
                        <div class="flex items-end"><button type="button" class="remove-vehicle text-red-600 hover:text-red-800 font-medium">Remove</button></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <button type="button" id="add-vehicle-btn" class="mt-4 inline-block bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-6 rounded-lg">
            + Add Vehicle
        </button>
    </div>

    <div class="mt-12 text-right">
        <a href="../actions/view.php?id=<?= $id ?>" class="inline-block bg-gray-500 hover:bg-gray-600 text-white font-medium py-3 px-8 rounded-lg mr-4">
            Cancel
        </a>
        <button type="submit" class="bg-green-700 hover:bg-green-800 text-white font-bold py-3 px-10 rounded-lg shadow-lg transition">
            Update Household Record
        </button>
    </div>
</form>

<script>
    let vehicleIndex = <?= count($vehicles) ?: 1 ?>;

    document.getElementById('add-vehicle-btn').addEventListener('click', function() {
        const container = document.getElementById('vehicles-container');
        const newRow = document.createElement('div');
        newRow.className = 'vehicle-row grid grid-cols-1 md:grid-cols-5 gap-6';
        newRow.innerHTML = `
            <div><input type="text" name="vehicles[${vehicleIndex}][brand]" class="w-full px-4 py-2 border border-gray-300 rounded-lg"></div>
            <div><input type="text" name="vehicles[${vehicleIndex}][type_model]" class="w-full px-4 py-2 border border-gray-300 rounded-lg"></div>
            <div><input type="text" name="vehicles[${vehicleIndex}][color]" class="w-full px-4 py-2 border border-gray-300 rounded-lg"></div>
            <div><input type="text" name="vehicles[${vehicleIndex}][plate_no]" class="w-full px-4 py-2 border border-gray-300 rounded-lg"></div>
            <div class="flex items-end"><button type="button" class="remove-vehicle text-red-600 hover:text-red-800 font-medium">Remove</button></div>
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