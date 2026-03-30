<?php
include '../db.php';
include '../includes/header.php';

// 1. Total households
$total_residents_stmt = $pdo->query("SELECT COUNT(*) AS total FROM households");
$total_residents = $total_residents_stmt->fetch()['total'];

// Current period
$currentYear  = (int)date('Y');
$currentMonth = (int)date('n');

// Previous period (for true overdue)
$prevMonthDate = (clone (new DateTime()))->modify('-1 month');
$prevYear  = (int)$prevMonthDate->format('Y');
$prevMonth = (int)$prevMonthDate->format('n');

// 2. Households paid for current month (covers current month or overlapping range)
$paid_stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT household_id) AS total_paid
    FROM payments p
    WHERE (p.period_year = :y AND p.period_month <= :m
           AND (p.period_to_year IS NULL OR p.period_to_year > :y
                OR (p.period_to_year = :y AND p.period_to_month >= :m)))
       OR (p.period_year < :y AND (p.period_to_year IS NULL OR p.period_to_year >= :y))
");
$paid_stmt->execute([':y' => $currentYear, ':m' => $currentMonth]);
$total_paid_current = $paid_stmt->fetch()['total_paid'];

// Unpaid this month = total - paid current
$total_unpaid_current = $total_residents - $total_paid_current;

// 3. True overdue: households that did NOT pay for previous month
$overdue_stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT h.id) AS total_overdue
    FROM households h
    WHERE NOT EXISTS (
        SELECT 1 FROM payments p
        WHERE h.id = p.household_id
          AND ((p.period_year = :py AND p.period_month <= :pm
                AND (p.period_to_year IS NULL OR p.period_to_year > :py
                     OR (p.period_to_year = :py AND p.period_to_month >= :pm)))
             OR (p.period_year < :py AND (p.period_to_year IS NULL OR p.period_to_year >= :py)))
    )
");
$overdue_stmt->execute([':py' => $prevYear, ':pm' => $prevMonth]);
$total_overdue = $overdue_stmt->fetch()['total_overdue'];
?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-4xl font-bold text-green-800 mb-8 text-center">Dashboard</h1>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <a href="./habitants.php" class="bg-white p-6 rounded-xl shadow border border-gray-200 hover:scale-105 transition-all block">
            <h3 class="text-xl font-semibold text-green-800 mb-2">Habitants / Residents</h3>
            <p class="text-gray-600 mb-6">Manage households & members</p>
            <div class="text-2xl font-bold text-green-700 mt-4"><?= number_format($total_residents) ?></div>
            <p class="text-gray-600">Total Households</p>
            <span class="text-sm text-green-600 hover:text-green-900 inline-block">View All -></span>
        </a>

        <a href="./dues.php" class="bg-white p-6 rounded-xl shadow border border-gray-200 hover:scale-105 transition-all block">
            <h3 class="text-xl font-semibold text-green-800 mb-2">Monthly Dues</h3>
            <p class="text-gray-600 mb-6">View monthly payment status</p>
            <div class="text-2xl font-bold text-green-700 mt-4"><?= number_format($total_paid_current) ?></div>
            <p class="text-gray-600">Total Paid</p>
            <span class="text-sm text-green-600 hover:text-green-900 inline-block">View Dues -></span>
        </a>

        <a href="./reports.php" class="bg-white p-6 rounded-xl shadow border border-gray-200 hover:scale-105 transition-all block">
            <h3 class="text-xl font-semibold text-green-800 mb-2">Reports</h3>
            <p class="text-gray-600 mb-6">Overview, filters, export</p>
            <div class="text-2xl font-bold text-yellow-800 mt-4"><?= number_format($total_unpaid_current) ?></div>
            <p class="text-gray-600">Total Unpaid</p>
            <span class="text-sm text-green-600 hover:text-green-900 inline-block">View Reports -></span>
        </a>

        <a href="./payment.php" class="bg-white p-6 rounded-xl shadow border border-gray-200 hover:scale-105 transition-all block">
            <h3 class="text-xl font-semibold text-green-800 mb-2">Record Payment</h3>
            <p class="text-gray-600 mb-6">Add new dues payment</p>
            <div class="text-2xl font-bold text-red-600 mt-4"><?= number_format($total_overdue) ?></div>
            <p class="text-gray-600">Total Overdue</p>
            <span class="text-sm text-green-600 hover:text-green-900 inline-block">Log Payments -></span>
        </a>
    </div>
</div>

<?php include '../includes/footer.php'; ?>