<?php
include '../db.php';
include '../includes/header.php';

$current_year = date('Y');
$selected_year = $_GET['year'] ?? $current_year;
$household_id = $_GET['household_id'] ?? null;

$where = "WHERE YEAR(p.paid_at) = :year";
$params = [':year' => $selected_year];

if ($household_id && is_numeric($household_id)) {
    $where .= " AND p.household_id = :hid";
    $params[':hid'] = $household_id;

    $hstmt = $pdo->prepare("SELECT CONCAT(first_name, ' ', last_name) AS full_name FROM households WHERE id = ?");
    $hstmt->execute([$household_id]);
    $hh = $hstmt->fetch();
    $title = $hh ? "Monthly Dues - " . $hh['full_name'] : "Monthly Dues";
} else {
    $title = "Monthly Dues Overview - All Households";
}

$query = "
    SELECT p.*, h.last_name, h.first_name,
           DATE_FORMAT(p.paid_at, '%Y-%m') AS paid_month
    FROM payments p
    JOIN households h ON p.household_id = h.id
    $where
    ORDER BY h.last_name, h.first_name, p.paid_at
";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$payments = $stmt->fetchAll();

// Group by household and month
$dues_data = [];
$household_totals = [];
foreach ($payments as $p) {
    $name = $p['last_name'] . ', ' . $p['first_name'];
    $month = (int)date('n', strtotime($p['paid_month']));
    $dues_data[$name][$month] = $p;
    $household_totals[$name] = ($household_totals[$name] ?? 0) + $p['amount'];
}
?>

<div class="mb-10">
    <h2 class="text-3xl font-bold text-green-800 mb-2"><?= htmlspecialchars($title) ?></h2>
    <p class="text-gray-600 mb-4">Showing dues for year <?= $selected_year ?></p>

    <!-- Year selector -->
    <div class="mb-6">
        <form method="GET" class="inline-flex items-center gap-4">
            <label class="text-sm font-medium text-gray-700">Year:</label>
            <select name="year" onchange="this.form.submit()" class="px-4 py-2 border border-gray-300 rounded-lg">
                <?php for ($y = $current_year - 5; $y <= $current_year + 1; $y++): ?>
                    <option value="<?= $y ?>" <?= $selected_year == $y ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
            <?php if ($household_id): ?>
                <input type="hidden" name="household_id" value="<?= $household_id ?>">
            <?php endif; ?>
        </form>
    </div>

    <?php if (empty($dues_data)): ?>
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-6 rounded-r-lg">
            No payments recorded for <?= $selected_year ?>. <?= $household_id ? 'This household has no dues history yet.' : 'Try adding some payments.' ?>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto bg-white rounded-xl shadow-md border border-gray-200">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase sticky left-0 bg-gray-50 z-10">Household</th>
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <th class="px-4 py-4 text-center text-xs font-medium text-gray-500 uppercase"><?= date('M', mktime(0,0,0,$m,1,$selected_year)) ?></th>
                        <?php endfor; ?>
                        <th class="px-6 py-4 text-center text-xs font-medium text-gray-500 uppercase">Total Paid</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($dues_data as $name => $months): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900 sticky left-0 bg-white z-10">
                                <?= htmlspecialchars($name) ?>
                            </td>
                            <?php 
                            $total = 0;
                            for ($m = 1; $m <= 12; $m++): 
                                $paid = $months[$m] ?? null;
                                $total += $paid ? $paid['amount'] : 0;
                            ?>
                                <td class="px-4 py-4 text-center text-sm <?= $paid ? 'text-green-600 font-medium' : 'text-red-500' ?>">
                                    <?= $paid ? '₱' . number_format($paid['amount'], 2) . '<br><small>' . ($paid['or_no'] ?? '') . '</small>' : 'Unpaid' ?>
                                </td>
                            <?php endfor; ?>
                            <td class="px-6 py-4 text-center font-bold text-green-800">
                                ₱<?= number_format($total, 2) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>


</div>

<?php include '../includes/footer.php'; ?>