<?php
include '../includes/auth.php';
include '../db.php';
include '../includes/header.php';

// Admin-only page
require_admin();

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

// Existing additional members
$mstmt = $pdo->prepare("SELECT * FROM household_members WHERE household_id = ? ORDER BY id");
$mstmt->execute([$id]);
$members = $mstmt->fetchAll();

// Existing vehicles
$vstmt = $pdo->prepare("SELECT * FROM vehicles WHERE household_id = ? ORDER BY id");
$vstmt->execute([$id]);
$vehicles = $vstmt->fetchAll();
?>

<div class="mb-10">
    <div class="flex justify-between items-center">
        <h2 class="text-3xl font-bold text-green-800">Edit Household</h2>
        <a href="view.php?id=<?= $id ?>" class="text-green-700 hover:text-green-900 font-medium">&larr; Back to View</a>
    </div>
</div>

<form action="../actions/update_household.php" method="POST" class="bg-white p-8 rounded-xl shadow-lg border border-gray-200">
    <input type="hidden" name="id" value="<?= $id ?>">

    <!-- Primary Member & Address -->
    <div class="member-block border border-gray-200 rounded-lg p-6 bg-gray-50 mb-12">
        <h3 class="text-lg font-semibold text-green-800 mb-4">Primary Household Member & Address</h3>
        
        <!-- Row 1: Names & Status -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
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
                    <?php foreach (['Owner', 'Renter'] as $status): ?>
                        <label class="inline-flex items-center">
                            <input type="radio" name="home_status" value="<?= $status ?>" <?= $household['home_status'] === $status ? 'checked' : '' ?> class="form-radio text-green-600">
                            <span class="ml-2"><?= $status ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Row 2: Personal Info -->
        <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Birthday</label>
                <input type="date" name="birthday" value="<?= htmlspecialchars($household['birthday'] ?? '') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
                <select name="gender" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
                    <option value="">Select</option>
                    <?php 
                    $currentGender = $household['gender'] ?? ''; 
                    foreach (['Male', 'Female', 'Other'] as $g): ?>
                        <option value="<?= $g ?>" <?= $currentGender === $g ? 'selected' : '' ?>><?= $g ?></option>
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

        <!-- Row 3: Address -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
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

        <!-- Row 4: Move-in/Move-out Dates -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date of move-in</label>
                <input type="date" name="move_in_date" id="move_in_date" value="<?= htmlspecialchars($household['move_in_date'] ?? '') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
                <p class="text-xs text-gray-500 mt-1">Ex: April 6, 2026</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Move-out status</label>
                <select name="move_out_status" id="move_out_status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
                    <option value="current" <?= empty($household['move_out_date']) ? 'selected' : '' ?>>Currently living here</option>
                    <option value="moved" <?= !empty($household['move_out_date']) ? 'selected' : '' ?>>Moved out - Select date</option>
                </select>
            </div>
            <div id="move_out_date_group" class="<?= empty($household['move_out_date']) ? 'hidden' : '' ?>">
                <label class="block text-sm font-medium text-gray-700 mb-1">Date of move-out</label>
                <input type="date" name="move_out_date" id="move_out_date" value="<?= htmlspecialchars($household['move_out_date'] ?? '') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
            </div>
        </div>

        <!-- Membership (optional) -->
        <div class="mt-8">
            <h4 class="text-base font-semibold text-green-800 mb-4">Membership (Optional)</h4>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date of membership</label>
                    <input type="date" name="membership_date" value="<?= htmlspecialchars($household['membership_date'] ?? '') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">OR No. / INV No.</label>
                    <input type="text" name="membership_or_no" value="<?= htmlspecialchars($household['membership_or_no'] ?? '') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500" placeholder="e.g. 00004567">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fee (₱)</label>
                    <input type="number" name="membership_fee" step="0.01" min="0" value="<?= htmlspecialchars($household['membership_fee'] ?? '') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500" placeholder="e.g. 100.00">
                </div>
            </div>
        </div>
    </div>

    <!-- Additional Household Members -->
    <div class="border-t border-gray-200 pt-8 mb-12">
        <h3 class="text-xl font-semibold text-green-800 mb-4">Additional Household Members</h3>
        <p class="text-sm text-gray-600 mb-4">Add or edit spouse, children, parents, tenants, etc.</p>
        
        <div id="members-container" class="space-y-6">
            <?php if (empty($members)): ?>
                <div class="member-block border border-gray-200 rounded-lg p-6 bg-gray-50">
                    <!-- Row 1: Names -->
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

                    <!-- Row 2: Other details -->
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
                                <?php 
                                $currentGender = ''; // default for new member
                                foreach (['Male', 'Female', 'Other'] as $g): ?>
                                    <option value="<?= $g ?>" <?= $currentGender === $g ? 'selected' : '' ?>><?= $g ?></option>
                                <?php endforeach; ?>
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

                    <!-- Remove button -->
                    <div class="mt-4 text-right">
                        <button type="button" class="remove-member text-red-600 hover:text-red-800 font-medium text-sm">
                            Remove
                        </button>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($members as $index => $m): ?>
                    <div class="member-block border border-gray-200 rounded-lg p-6 bg-gray-50">
                        <!-- Row 1: Names -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                                <input type="text" name="members[<?= $index ?>][last_name]" value="<?= htmlspecialchars($m['last_name'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                                <input type="text" name="members[<?= $index ?>][first_name]" value="<?= htmlspecialchars($m['first_name'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Middle Name</label>
                                <input type="text" name="members[<?= $index ?>][middle_name]" value="<?= htmlspecialchars($m['middle_name'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
                            </div>
                        </div>

                        <!-- Row 2: Other details -->
                        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Relation</label>
                                <select name="members[<?= $index ?>][relation]" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
                                    <option value="">Select</option>
                                    <?php foreach (['Spouse', 'Child', 'Parent', 'Sibling', 'Tenant', 'Other'] as $rel): ?>
                                        <option value="<?= $rel ?>" <?= ($m['relation'] ?? '') === $rel ? 'selected' : '' ?>><?= $rel ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Birthday</label>
                                <input type="date" name="members[<?= $index ?>][birthday]" value="<?= htmlspecialchars($m['birthday'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
                                <select name="members[<?= $index ?>][gender]" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
                                    <option value="">Select</option>
                                    <?php 
                                    $currentGender = $m['gender'] ?? ''; 
                                    foreach (['Male', 'Female', 'Other'] as $g): ?>
                                        <option value="<?= $g ?>" <?= $currentGender === $g ? 'selected' : '' ?>><?= $g ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Contact No.</label>
                                <input type="tel" name="members[<?= $index ?>][contact_no]" value="<?= htmlspecialchars($m['contact_no'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Occupation</label>
                                <input type="text" name="members[<?= $index ?>][occupation]" value="<?= htmlspecialchars($m['occupation'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
                            </div>
                        </div>

                        <!-- Remove button -->
                        <div class="mt-4 text-right">
                            <button type="button" class="remove-member text-red-600 hover:text-red-800 font-medium text-sm">
                                Remove
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <button type="button" id="add-member-btn" class="mt-4 inline-block bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-5 rounded-lg text-sm">
            + Add Another Member
        </button>
    </div>

    <!-- Vehicles -->
    <div class="border-t border-gray-200 pt-8">
        <div class="vehicles-block border border-gray-200 rounded-lg p-6 bg-gray-50">
            <h3 class="text-xl font-semibold text-green-800 mb-4">Vehicles</h3>
            
            <div id="vehicles-container" class="space-y-6">
                <?php if (empty($vehicles)): ?>
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
                <?php else: ?>
                    <?php foreach ($vehicles as $index => $v): ?>
                        <div class="vehicle-row grid grid-cols-1 md:grid-cols-5 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Brand</label>
                                <input type="text" name="vehicles[<?= $index ?>][brand]" value="<?= htmlspecialchars($v['brand'] ?? '') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Type / Model</label>
                                <input type="text" name="vehicles[<?= $index ?>][type_model]" value="<?= htmlspecialchars($v['type_model'] ?? '') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Color</label>
                                <input type="text" name="vehicles[<?= $index ?>][color]" value="<?= htmlspecialchars($v['color'] ?? '') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Plate No.</label>
                                <input type="text" name="vehicles[<?= $index ?>][plate_no]" value="<?= htmlspecialchars($v['plate_no'] ?? '') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
                            </div>
                            <div class="flex items-end">
                                <button type="button" class="remove-vehicle text-red-600 hover:text-red-800 font-medium">Remove</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <button type="button" id="add-vehicle-btn" class="mt-4 inline-block bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-6 rounded-lg">
                + Add Vehicle
            </button>
        </div>
    </div>

    <div class="mt-12 text-right">
        <a href="view.php?id=<?= $id ?>" class="inline-block bg-gray-500 hover:bg-gray-600 text-white font-medium py-3 px-8 rounded-lg mr-4">
            Cancel
        </a>
        <button type="submit" class="bg-green-700 hover:bg-green-800 text-white font-bold py-3 px-10 rounded-lg shadow-lg transition">
            Update Household Record
        </button>
    </div>
</form>

<script>
// Additional Members - Dynamic rows
let memberIndex = <?= count($members) ?: 1 ?>;

document.getElementById('add-member-btn').addEventListener('click', function() {
    const container = document.getElementById('members-container');
    const newBlock = document.createElement('div');
    newBlock.className = 'member-block border border-gray-200 rounded-lg p-6 bg-gray-50';
    newBlock.innerHTML = `
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                <input type="text" name="members[${memberIndex}][last_name]" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                <input type="text" name="members[${memberIndex}][first_name]" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Middle Name</label>
                <input type="text" name="members[${memberIndex}][middle_name]" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
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
                    <?php 
                    $currentGender = ''; // default for new
                    foreach (['Male', 'Female', 'Other'] as $g): ?>
                        <option value="<?= $g ?>" <?= $currentGender === $g ? 'selected' : '' ?>><?= $g ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Contact No.</label>
                <input type="tel" name="members[${memberIndex}][contact_no]" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Occupation</label>
                <input type="text" name="members[${memberIndex}][occupation]" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
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

// Remove member
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('remove-member')) {
        const block = e.target.closest('.member-block');
        showDeleteConfirmModal('Are you sure you want to remove this member?', () => {
            block.remove();
        });
    }
});

// Vehicles - Dynamic rows
let vehicleIndex = <?= count($vehicles) ?: 1 ?>;

document.getElementById('add-vehicle-btn').addEventListener('click', function() {
    const container = document.getElementById('vehicles-container');
    const newRow = document.createElement('div');
    newRow.className = 'vehicle-row grid grid-cols-1 md:grid-cols-5 gap-6';
    newRow.innerHTML = `
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Brand</label>
            <input type="text" name="vehicles[${vehicleIndex}][brand]" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Type / Model</label>
            <input type="text" name="vehicles[${vehicleIndex}][type_model]" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Color</label>
            <input type="text" name="vehicles[${vehicleIndex}][color]" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Plate No.</label>
            <input type="text" name="vehicles[${vehicleIndex}][plate_no]" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
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
        showDeleteConfirmModal('Are you sure you want to remove this vehicle?', () => {
            row.remove();
        });
    }
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