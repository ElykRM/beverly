<?php
include '../db.php';
include '../includes/header.php';

$pre_household_id = $_GET['household_id'] ?? null;
$pre_name = '';

if ($pre_household_id) {
    $stmt = $pdo->prepare("SELECT CONCAT(first_name, ' ', last_name) AS full_name FROM households WHERE id = ?");
    $stmt->execute([$pre_household_id]);
    $row = $stmt->fetch();
    $pre_name = $row ? $row['full_name'] : '';
}
?>

<div class="mb-10">
    <h2 class="text-3xl font-bold text-green-800 mb-2">Log New Payment / Dues</h2>
    <p class="text-gray-600">Record a payment for a household.</p>
</div>

<form action="../actions/save_payment.php" method="POST" class="bg-white p-8 rounded-xl shadow-lg border border-gray-200 max-w-3xl mx-auto">
    <div class="mb-8">
        <label class="block text-sm font-medium text-gray-700 mb-1">Household (Name or ID)</label>
        <input type="text" name="household_search" id="household_search" value="<?= htmlspecialchars($pre_name) ?>" 
               placeholder="Search name or select from list" required
               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
        <input type="hidden" name="household_id" id="household_id" value="<?= htmlspecialchars($pre_household_id ?? '') ?>" required>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Official Receipt No. (OR No.)</label>
            <input type="text" name="or_no" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Payment Period (e.g. March 2026 or Jan-Mar)</label>
            <input type="text" name="payment_period" required placeholder="March 2026" 
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
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