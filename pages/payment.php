<?php
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
    <h2 class="text-3xl font-bold text-green-800 mb-2">Record Dues Payment</h2>
    <p class="text-gray-600">Pay for one month or a range of months at once.</p>
</div>

<form action="../actions/save_payment.php" method="POST" class="bg-white p-8 rounded-xl shadow-lg border border-gray-200 max-w-4xl mx-auto">

    <!-- Household -->
    <div class="mb-8">
        <label for="household_id" class="block text-sm font-medium text-gray-700 mb-2">Household</label>
        <select name="household_id" id="household_id" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
            <option value="">— Select household —</option>
            <?php foreach ($households as $h): ?>
                <option value="<?= $h['id'] ?>" <?= $preselect_id == $h['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($h['display']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Payment type toggle -->
    <div class="mb-8">
        <label class="block text-sm font-medium text-gray-700 mb-2">Payment covers</label>
        <div class="flex gap-8">
            <label class="inline-flex items-center cursor-pointer">
                <input type="radio" name="payment_type" value="single" checked class="form-radio text-green-600">
                <span class="ml-2">Single month</span>
            </label>
            <label class="inline-flex items-center cursor-pointer">
                <input type="radio" name="payment_type" value="range" class="form-radio text-green-600">
                <span class="ml-2">Range of months</span>
            </label>
        </div>
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
    <div id="range-group" class="hidden grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
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
            <input type="number" name="amount" id="amount" step="0.01" min="0" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
        </div>
        <div class="md:col-span-2">
            <label for="remarks" class="block text-sm font-medium text-gray-700 mb-2">Remarks (optional)</label>
            <textarea name="remarks" id="remarks" rows="3" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"></textarea>
        </div>
    </div>

    <div class="text-right">
        <button type="submit" class="bg-green-700 hover:bg-green-800 text-white font-bold py-3 px-10 rounded-lg shadow transition">
            Record Payment
        </button>
    </div>
</form>

<script>
// Toggle single vs range
document.querySelectorAll('input[name="payment_type"]').forEach(radio => {
    radio.addEventListener('change', () => {
        const isRange = radio.value === 'range';
        document.getElementById('single-group').classList.toggle('hidden', isRange);
        document.getElementById('range-group').classList.toggle('hidden', !isRange);
    });
});
</script>

<?php include '../includes/footer.php'; ?>