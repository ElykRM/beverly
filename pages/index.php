<?php
include '../db.php';
include '../includes/header.php';

// 1. Total residents (households)
$total_residents_stmt = $pdo->query("SELECT COUNT(*) AS total FROM households");
$total_residents = $total_residents_stmt->fetch()['total'];

// 2. Total paid this month (households that have paid current month)
$currentYear  = (int)date('Y');
$currentMonth = (int)date('n');

$paid_stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT household_id) AS total_paid
    FROM payments p
    WHERE (p.period_year = :y AND p.period_month <= :m 
           AND (p.period_to_year IS NULL OR p.period_to_year > :y 
                OR (p.period_to_year = :y AND p.period_to_month >= :m)))
       OR (p.period_year < :y AND (p.period_to_year IS NULL OR p.period_to_year >= :y))
");
$paid_stmt->execute([':y' => $currentYear, ':m' => $currentMonth]);
$total_paid = $paid_stmt->fetch()['total_paid'];

// 3. Total unpaid this month (total households - total paid)
$total_unpaid = $total_residents - $total_paid;
?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-4xl font-bold text-green-800 mb-8 text-center">Dashboard</h1>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <a href="./habitants.php" class="bg-white p-6 rounded-xl shadow border border-gray-200 hover:scale-105 transition-all block">
            <h3 class="text-xl font-semibold text-green-800 mb-2">Habitants / Residents</h3>
            <p class="text-gray-600 mb-6">Manage households members.</p>
            <div class="text-2xl font-bold text-green-700 mt-4"><?= number_format($total_residents) ?></div>
            <p class="text-gray-600">Total Residents</p>
            <span class="text-sm text-green-600 hover:text-green-900 inline-block">View All →</span>
        </a>
        <a href="./dues.php" class="bg-white p-6 rounded-xl shadow border border-gray-200 hover:scale-105 transition-all block">
            <h3 class="text-xl font-semibold text-green-800 mb-2">Monthly Dues</h3>
            <p class="text-gray-600 mb-6">View monthly payment status</p>
            <div class="text-2xl font-bold text-green-700 mt-4"><?= number_format($total_paid) ?></div>
            <p class="text-gray-600">Total Paid</p>
            <span class="text-sm text-green-600 hover:text-green-900 inline-block">View Dues →</span>
        </a>
        <a href="./reports.php" class="bg-white p-6 rounded-xl shadow border border-gray-200 hover:scale-105 transition-all block">
            <h3 class="text-xl font-semibold text-green-800 mb-2">Reports</h3>
            <p class="text-gray-600 mb-6">Overview, filters, export</p>
            <div class="text-2xl font-bold text-red-600 mt-4">
                    <?= number_format($total_unpaid) ?>
            </div>
            <p class="text-gray-600 ">Total Unpaid:</p>
            <span class="text-sm text-green-600 hover:text-green-900 inline-block">View Reports →</span>
        </a>
        <a href="./payment.php" class="bg-white p-6 rounded-xl shadow border border-gray-200 hover:scale-105 transition-all block">
            <h3 class="text-xl font-semibold text-green-800 mb-2">Record Payment</h3>
            <p class="text-gray-600">Add new dues payment</p>
        </a>
        <!-- Add more quick links if needed -->
    </div>
</div>

<?php include '../includes/footer.php'; ?>