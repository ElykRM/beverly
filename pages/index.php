<?php
include '../includes/auth.php';
include '../db.php';
include '../includes/header.php';

// 1. Total households (primary members + household members)
$total_households_stmt = $pdo->query("
    SELECT 
        (SELECT COUNT(*) FROM households) +
        (SELECT COUNT(*) FROM household_members) AS total
");
$total_households = $total_households_stmt->fetch()['total'];

// 2. Total Owner households
$total_owners_stmt = $pdo->query("SELECT COUNT(*) AS total FROM households WHERE home_status = 'Owner'");
$total_owners = $total_owners_stmt->fetch()['total'];

// 3. Total Renter households
$total_renters_stmt = $pdo->query("SELECT COUNT(*) AS total FROM households WHERE home_status = 'Renter'");
$total_renters = $total_renters_stmt->fetch()['total'];
?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-4xl font-bold text-green-800 mb-8 text-center">Dashboard</h1>
    
    <!-- Navigation Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 <?php if (is_viewer()): ?>lg:grid-cols-3<?php else: ?>lg:grid-cols-4<?php endif; ?> gap-6 mb-8">
        <!-- Total Residents -->
        <a href="./habitants.php" class="bg-white p-6 rounded-xl shadow border border-gray-200 hover:scale-105 transition-all block">
            <h3 class="text-xl font-semibold text-green-800 mb-2">Habitants / Residents</h3>
            <p class="text-gray-600 mb-6">Manage households & members</p>
            <span class="text-sm text-green-600 hover:text-green-900 inline-block">View All →</span>
        </a>

        <!-- Paid This Month -->
        <a href="./dues.php" class="bg-white p-6 rounded-xl shadow border border-gray-200 hover:scale-105 transition-all block">
            <h3 class="text-xl font-semibold text-green-800 mb-2">Monthly Dues</h3>
            <p class="text-gray-600 mb-6">View monthly payment status</p>
            <span class="text-sm text-green-600 hover:text-green-900 inline-block">View Dues →</span>
        </a>

        <!-- Reports -->
        <a href="./reports.php" class="bg-white p-6 rounded-xl shadow border border-gray-200 hover:scale-105 transition-all block">
            <h3 class="text-xl font-semibold text-green-800 mb-2">Reports</h3>
            <p class="text-gray-600 mb-6">Overview, filters, export</p>
            <span class="text-sm text-green-600 hover:text-green-900 inline-block">View Reports →</span>
        </a>

        <!-- Record Payment (Admin Only) -->
        <?php if (is_admin()): ?>
        <a href="../pages/payment.php" class="bg-white p-6 rounded-xl shadow border border-gray-200 hover:scale-105 transition-all block">
            <h3 class="text-xl font-semibold text-green-800 mb-2">Record Payment</h3>
            <p class="text-gray-600 mb-6">Add new dues payment</p>
            <span class="text-sm text-green-600 hover:text-green-900 inline-block">Log Payments →</span>
        </a>
        <?php endif; ?>
    </div>

    <!-- Statistics Row -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Total Residents -->
        <div class="bg-white p-6 rounded-xl shadow border border-gray-200">
            <p class="text-sm text-gray-600 mb-1">Total Residents</p>
            <div class="text-3xl font-bold text-green-700"><?= number_format($total_households) ?></div>
        </div>

        <!-- Total Owner -->
        <div class="bg-white p-6 rounded-xl shadow border border-gray-200">
            <p class="text-sm text-gray-600 mb-1">Total Owner</p>
            <div class="text-3xl font-bold text-green-700"><?= number_format($total_owners) ?></div>
        </div>

        <!-- Total Renter -->
        <div class="bg-white p-6 rounded-xl shadow border border-gray-200">
            <p class="text-sm text-gray-600 mb-1">Total Renter</p>
            <div class="text-3xl font-bold text-green-700"><?= number_format($total_renters) ?></div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>