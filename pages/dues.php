<?php
include '../db.php';
include '../includes/header.php';

$current_year = date('Y');
$household_id = $_GET['household_id'] ?? null;

if ($household_id && is_numeric($household_id)) {
    $stmt = $pdo->prepare("SELECT CONCAT(first_name, ' ', last_name) AS full_name FROM households WHERE id = ?");
    $stmt->execute([$household_id]);
    $hh = $stmt->fetch();
    $title = $hh ? "Monthly Dues - " . $hh['full_name'] : "Monthly Dues";
} else {
    $title = "Monthly Dues Overview";
    $household_id = null;
}

$query = "
    SELECT p.*, h.last_name, h.first_name,
           DATE_FORMAT(p.paid_at, '%Y-%m') AS paid_month
    FROM payments p
    JOIN households h ON p.household_id = h.id
    WHERE YEAR(p.paid_at) = :year
";
$params = [':year' => $current_year];

if ($household_id) {
    $query .= " AND p.household_id = :hid";
    $params[':hid'] = $household_id;
}

$query .= " ORDER BY h.last_name, h.first_name, p.paid_at";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$payments = $stmt->fetchAll();

$dues_data = [];
foreach ($payments as $p) {
    $name = $p['last_name'] . ', ' . $p['first_name'];
    $month = date('n', strtotime($p['paid_month']));
    $dues_data[$name][$month] = $p;
}
?>

<div class="mb-10">
    <h2 class="text-3xl font-bold text-green-800 mb-2"><?= htmlspecialchars($title) ?></h2>
    <p class="text-gray-600 mb-6">Showing dues for <?= $current_year ?>. <?= $household_id ? 'Single household view.' : 'All households.' ?></p>

    <?php if (empty($dues_data)): ?>
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-6 rounded-r-lg">
            No payments recorded for <?= $current_year ?> yet.
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 bg-white rounded-xl shadow-md border border-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase">Household</th>
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <th class="px-4 py-4 text-center text-xs font-medium text-gray-500 uppercase"><?= date('M', mktime(0,0,0,$m,1)) ?></th>
                        <?php endfor; ?>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($dues_data as $name => $months): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">
                                <?= htmlspecialchars($name) ?>
                            </td>
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <td class="px-4 py-4 text-center text-sm <?= isset($months[$m]) ? 'text-green-600' : 'text-red-500' ?>">
                                    <?= isset($months[$m]) ? '₱' . number_format($months[$m]['amount'], 2) : 'Unpaid' ?>
                                </td>
                            <?php endfor; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <div class="mt-8 text-center space-x-4">
        <a href="dues.php" class="inline-block bg-gray-600 hover:bg-gray-700 text-white font-medium py-3 px-8 rounded-lg shadow">
            View All Households
        </a>
        <a href="habitants.php" class="inline-block bg-green-700 hover:bg-green-800 text-white font-medium py-3 px-8 rounded-lg shadow">
            Back to Residents
        </a>
    </div>
</div>

<?php include '../includes/footer.php'; ?>