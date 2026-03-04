<?php
include '../db.php';
include '../includes/header.php';

// Current date for overdue logic
$today = new DateTime();
$current_year_month = $today->format('Y-m');
$previous_month = (clone $today)->modify('-1 month')->format('Y-m');

// Summary stats
$total_stmt = $pdo->query("SELECT COUNT(*) AS total FROM households");
$total = $total_stmt->fetch()['total'];

$status_stmt = $pdo->query("
    SELECT home_status, COUNT(*) AS count 
    FROM households 
    GROUP BY home_status
");
$status_counts = [];
while ($row = $status_stmt->fetch()) {
    $status_counts[$row['home_status']] = $row['count'];
}
$owners = $status_counts['Owner'] ?? 0;
$renters = $status_counts['Renter'] ?? 0;
$members = $status_counts['Member'] ?? 0;

// Paid this month
$paid_stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT household_id) AS count 
    FROM payments 
    WHERE payment_period = ?
");
$paid_stmt->execute([$current_year_month]);
$paid_this_month = $paid_stmt->fetch()['count'];

// Overdue & Unpaid
$unpaid_overdue_query = "
    SELECT h.id
    FROM households h
    LEFT JOIN payments p ON h.id = p.household_id AND p.payment_period = ?
    WHERE p.id IS NULL
";
$uo_stmt = $pdo->prepare($unpaid_overdue_query);
$uo_stmt->execute([$current_year_month]);
$unpaid_or_overdue = $uo_stmt->fetchAll(PDO::FETCH_COLUMN);

$overdue = 0;
$unpaid = 0;
foreach ($unpaid_or_overdue as $hid) {
    $prev_stmt = $pdo->prepare("SELECT 1 FROM payments WHERE household_id = ? AND payment_period = ?");
    $prev_stmt->execute([$hid, $previous_month]);
    if ($prev_stmt->fetch()) {
        $unpaid++;
    } else {
        $overdue++;
    }
}

// Filters
$status_filter = $_GET['status'] ?? 'ALL';
$block_filter  = trim($_GET['block'] ?? '');
$lot_filter    = trim($_GET['lot'] ?? '');
$name_search   = trim($_GET['name'] ?? '');
$dues_filter   = $_GET['dues_status'] ?? 'ALL';

$query = "SELECT h.* FROM households h";
$where = " WHERE 1=1";
$params = [];

if ($status_filter !== 'ALL') {
    $where .= " AND h.home_status = :status";
    $params[':status'] = $status_filter;
}
if ($block_filter !== '') {
    $where .= " AND h.block = :block";
    $params[':block'] = $block_filter;
}
if ($lot_filter !== '') {
    $where .= " AND h.lot = :lot";
    $params[':lot'] = $lot_filter;
}
if ($name_search !== '') {
    $where .= " AND (h.last_name LIKE :name OR h.first_name LIKE :name OR h.middle_name LIKE :name)";
    $params[':name'] = "%$name_search%";
}

if ($dues_filter !== 'ALL') {
    $query .= " LEFT JOIN payments p ON h.id = p.household_id AND p.payment_period = :current_period";
    $params[':current_period'] = $current_year_month;

    if ($dues_filter === 'Paid') {
        $where .= " AND p.id IS NOT NULL";
    } elseif ($dues_filter === 'Unpaid' || $dues_filter === 'Overdue') {
        $where .= " AND p.id IS NULL";
        if ($dues_filter === 'Overdue') {
            $query .= " LEFT JOIN payments p_prev ON h.id = p_prev.household_id AND p_prev.payment_period = :prev_period";
            $params[':prev_period'] = $previous_month;
            $where .= " AND p_prev.id IS NULL";
        }
    }
}

$query .= $where . " ORDER BY h.last_name, h.first_name";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$households = $stmt->fetchAll();

// Compute dues status
$results_with_status = [];
foreach ($households as $h) {
    $h['dues_status'] = 'Unpaid';
    $h['status_class'] = 'bg-yellow-100 text-yellow-800';

    $pstmt = $pdo->prepare("SELECT 1 FROM payments WHERE household_id = ? AND payment_period = ?");
    $pstmt->execute([$h['id'], $current_year_month]);
    if ($pstmt->fetch()) {
        $h['dues_status'] = 'Paid';
        $h['status_class'] = 'bg-green-100 text-green-800';
    } else {
        $prev_stmt = $pdo->prepare("SELECT 1 FROM payments WHERE household_id = ? AND payment_period = ?");
        $prev_stmt->execute([$h['id'], $previous_month]);
        if (!$prev_stmt->fetch()) {
            $h['dues_status'] = 'Overdue';
            $h['status_class'] = 'bg-red-100 text-red-800';
        }
    }
    $results_with_status[] = $h;
}
?>

<div class="mb-10">
    <h2 class="text-3xl font-bold text-green-800 mb-2">Reports</h2>
    <p class="text-gray-600 mb-6">Household overview and filtered list</p>
</div>

<!-- Summary Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6 mb-10">
    <div class="bg-white p-6 rounded-xl shadow-md border border-gray-200 text-center">
        <p class="text-sm text-gray-600">Total Households</p>
        <p class="text-4xl font-bold text-green-800"><?= $total ?></p>
    </div>
    <div class="bg-white p-6 rounded-xl shadow-md border border-gray-200 text-center">
        <p class="text-sm text-gray-600">Owners</p>
        <p class="text-4xl font-bold text-green-800"><?= $owners ?></p>
    </div>
    <div class="bg-white p-6 rounded-xl shadow-md border border-gray-200 text-center">
        <p class="text-sm text-gray-600">Renters</p>
        <p class="text-4xl font-bold text-green-800"><?= $renters ?></p>
    </div>
    <div class="bg-white p-6 rounded-xl shadow-md border border-gray-200 text-center">
        <p class="text-sm text-gray-600">Members</p>
        <p class="text-4xl font-bold text-green-800"><?= $members ?></p>
    </div>
    <div class="bg-white p-6 rounded-xl shadow-md border border-gray-200 text-center">
        <p class="text-sm text-gray-600">Overdue</p>
        <p class="text-4xl font-bold text-red-600"><?= $overdue ?></p>
    </div>
</div>

<!-- Filters -->
<form method="GET" class="bg-white p-6 rounded-xl shadow-md border border-gray-200 mb-8">
    <div class="grid grid-cols-1 md:grid-cols-5 gap-6">
        <div>
            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <select name="status" id="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                <option value="ALL" <?= $status_filter === 'ALL' ? 'selected' : '' ?>>All</option>
                <option value="Owner" <?= $status_filter === 'Owner' ? 'selected' : '' ?>>Owner</option>
                <option value="Renter" <?= $status_filter === 'Renter' ? 'selected' : '' ?>>Renter</option>
                <option value="Member" <?= $status_filter === 'Member' ? 'selected' : '' ?>>Member</option>
            </select>
        </div>
        <div>
            <label for="block" class="block text-sm font-medium text-gray-700 mb-1">Block</label>
            <input type="text" name="block" id="block" value="<?= htmlspecialchars($block_filter) ?>" 
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg">
        </div>
        <div>
            <label for="lot" class="block text-sm font-medium text-gray-700 mb-1">Lot</label>
            <input type="text" name="lot" id="lot" value="<?= htmlspecialchars($lot_filter) ?>" 
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg">
        </div>
        <div>
            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Name Search</label>
            <input type="text" name="name" id="name" value="<?= htmlspecialchars($name_search) ?>" 
                   placeholder="e.g. John" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
        </div>
        <div>
            <label for="dues_status" class="block text-sm font-medium text-gray-700 mb-1">Dues Status</label>
            <select name="dues_status" id="dues_status" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                <option value="ALL" <?= $dues_filter === 'ALL' ? 'selected' : '' ?>>All</option>
                <option value="Paid" <?= $dues_filter === 'Paid' ? 'selected' : '' ?>>Paid</option>
                <option value="Unpaid" <?= $dues_filter === 'Unpaid' ? 'selected' : '' ?>>Unpaid</option>
                <option value="Overdue" <?= $dues_filter === 'Overdue' ? 'selected' : '' ?>>Overdue</option>
            </select>
        </div>
    </div>

    <div class="mt-6 flex gap-4 justify-end">
        <button type="submit" class="bg-green-700 hover:bg-green-800 text-white font-medium py-2 px-6 rounded-lg transition">
            Filter
        </button>
        <a href="reports.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium py-2 px-6 rounded-lg transition">
            Clear
        </a>
    </div>
</form>

<!-- Results Table -->
<?php if (empty($results_with_status)): ?>
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-6 rounded-r-lg">
        No households match the current filters.
    </div>
<?php else: ?>
    <div class="overflow-x-auto bg-white rounded-xl shadow-md border border-gray-200">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase">Block</th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase">Lot</th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase">Street</th>
                    <th class="px-6 py-4 text-center text-xs font-medium text-gray-500 uppercase">Dues Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php foreach ($results_with_status as $h): ?>
                    <tr class="hover:bg-gray-50 transition-colors cursor-pointer" 
                        onclick="window.location.href='../actions/view.php?id=<?= $h['id'] ?>'">
                        <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">
                            <?= htmlspecialchars($h['first_name'] . ' ' . $h['last_name']) ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($h['home_status']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($h['block'] ?? '-') ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($h['lot'] ?? '-') ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($h['street'] ?? '-') ?></td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center justify-center px-3 py-1 rounded-full text-sm font-medium min-w-[90px] <?= $h['status_class'] ?>">
                                <?= $h['dues_status'] ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Export -->
    <div class="mt-6 text-right">
        <button onclick="exportCSV()" class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-8 rounded-lg shadow transition">
            Export Filtered List to CSV
        </button>
    </div>

    <script>
    function exportCSV() {
        const data = <?= json_encode($results_with_status) ?>;
        let csv = 'Name,Status,Block,Lot,Street,Dues Status\n';
        data.forEach(row => {
            csv += `"${row.first_name} ${row.last_name}","${row.home_status}","${row.block || ''}","${row.lot || ''}","${row.street || ''}","${row.dues_status}"\n`;
        });

        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'hoa_households_report.csv';
        link.click();
    }
    </script>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>