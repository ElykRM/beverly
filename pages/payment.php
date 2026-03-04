<?php
include '../db.php';
include '../includes/header.php';

// Fetch all households for dropdown
$hstmt = $pdo->prepare("
    SELECT id, CONCAT(first_name, ' ', last_name, ' (Block ', block, ' Lot ', lot, ')') AS display_name
    FROM households 
    ORDER BY last_name, first_name
");
$hstmt->execute();
$households = $hstmt->fetchAll();

// Pre-fill if coming from view.php
$pre_household_id = $_GET['household_id'] ?? null;

// Year range: current -5 to +5
$current_year = (int)date('Y');
$years = range($current_year - 5, $current_year + 5);
$months = [
    '01' => 'January', '02' => 'February', '03' => 'March', '04' => 'April',
    '05' => 'May', '06' => 'June', '07' => 'July', '08' => 'August',
    '09' => 'September', '10' => 'October', '11' => 'November', '12' => 'December'
];
?>

<div class="mb-10">
    <h2 class="text-3xl font-bold text-green-800 mb-2">Log New Payment / Dues</h2>
    <p class="text-gray-600">Record a payment for a household (single month).</p>
</div>

<form action="../actions/save_payment.php" method="POST" class="bg-white p-8 rounded-xl shadow-lg border border-gray-200 max-w-3xl mx-auto">

    <!-- Household -->
    <div class="mb-8">
        <label for="household_id" class="block text-sm font-medium text-gray-700 mb-1">Select Household</label>
        <select name="household_id" id="household_id" required 
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500 text-gray-900">
            <option value="">-- Choose a household --</option>
            <?php foreach ($households as $h): ?>
                <option value="<?= $h['id'] ?>" <?= $pre_household_id == $h['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($h['display_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Payment Period: Month + Year -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Month</label>
            <select name="month" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
                <option value="">-- Select Month --</option>
                <?php foreach ($months as $num => $name): ?>
                    <option value="<?= $num ?>"><?= $name ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Year</label>
            <select name="year" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
                <option value="">-- Select Year --</option>
                <?php foreach ($years as $y): ?>
                    <option value="<?= $y ?>" <?= $y == $current_year ? 'selected' : '' ?>><?= $y ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <!-- Amount, OR, Remarks -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Official Receipt No. (OR No.)</label>
            <input type="text" name="or_no" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Amount (₱)</label>
            <input type="number" name="amount" step="0.01" min="0" required 
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
        </div>
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Remarks / Notes</label>
            <textarea name="remarks" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"></textarea>
        </div>
    </div>

    <div class="text-right">
        <button type="submit" class="bg-green-700 hover:bg-green-800 text-white font-bold py-3 px-10 rounded-lg shadow-lg transition">
            Record Payment
        </button>
    </div>
</form>

<?php include '../includes/footer.php'; ?>