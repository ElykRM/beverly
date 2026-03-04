<?php
include '../db.php';
include '../includes/header.php';

// Current year and range (default to current)
$currentYear = (int)date('Y');
$selectedYear = isset($_GET['year']) ? (int)$_GET['year'] : $currentYear;

// Limit year range
$minYear = $currentYear - 5;
$maxYear = $currentYear + 5;
if ($selectedYear < $minYear || $selectedYear > $maxYear) {
    $selectedYear = $currentYear;
}
$years = range($minYear, $maxYear);

// Months
$months = [1=>'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

// Fetch all households
$householdsStmt = $pdo->query("
    SELECT id, last_name, first_name, block, lot 
    FROM households 
    ORDER BY last_name, first_name
");
$households = $householdsStmt->fetchAll();

// Fetch payments that touch the selected year
$payments = [];
if (!empty($households)) {
    $pstmt = $pdo->prepare("
        SELECT household_id, period_year, period_month, period_to_year, period_to_month
        FROM payments
        WHERE (period_year = ? OR period_to_year = ? OR 
               (period_year <= ? AND (period_to_year IS NULL OR period_to_year >= ?)))
    ");
    $pstmt->execute([$selectedYear, $selectedYear, $selectedYear, $selectedYear]);
    $paymentsRaw = $pstmt->fetchAll();

    $paidMonths = [];
    foreach ($paymentsRaw as $p) {
        $hid = $p['household_id'];
        $startY = (int)$p['period_year'];
        $startM = (int)$p['period_month'];
        $endY   = $p['period_to_year'] !== null ? (int)$p['period_to_year'] : $startY;
        $endM   = $p['period_to_month'] !== null ? (int)$p['period_to_month'] : $startM;

        for ($y = $startY; $y <= $endY; $y++) {
            if ($y != $selectedYear) continue;
            $mFrom = ($y == $startY) ? $startM : 1;
            $mTo   = ($y == $endY)   ? $endM   : 12;
            for ($m = $mFrom; $m <= $mTo; $m++) {
                $paidMonths[$hid][$y . '-' . $m] = true;
            }
        }
    }
}
?>

<div class="mb-10">
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-6">
        <h2 class="text-3xl font-bold text-green-800">Monthly Dues Overview</h2>
        <a href="../pages/index.php" class="text-green-700 hover:text-green-900 font-medium flex items-center gap-2">
            ← Back to Menu
        </a>
    </div>

    <p class="text-gray-600 mb-6">View payment status across all households for the selected year.</p>

    <!-- Year selector -->
    <div class="bg-white p-6 rounded-xl shadow-md border border-gray-200 mb-10">
        <form method="GET" class="flex flex-col sm:flex-row sm:items-center gap-6">
            <label for="year" class="text-sm font-medium text-gray-700 whitespace-nowrap">Select Year:</label>
            <select name="year" id="year" 
                    class="w-full sm:w-48 px-4 py-3 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                    onchange="this.form.submit()">
                <?php foreach ($years as $y): ?>
                    <option value="<?= $y ?>" <?= $y == $selectedYear ? 'selected' : '' ?>>
                        <?= $y ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50 sticky top-0 z-10">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-r border-gray-200 min-w-[220px]">
                            Household
                        </th>
                        <?php foreach ($months as $num => $short): ?>
                            <th class="px-3 py-4 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <?= $short ?>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($households as $h): ?>
                        <?php
                        $hid = $h['id'];
                        $name = htmlspecialchars($h['last_name'] . ', ' . $h['first_name']);
                        $addr = $h['block'] && $h['lot'] ? "Block {$h['block']} Lot {$h['lot']}" : '—';
                        ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 border-r border-gray-200">
                                <div><?= $name ?></div>
                                <div class="text-xs text-gray-500"><?= $addr ?></div>
                            </td>
                            <?php for ($m = 1; $m <= 12; $m++): 
                                $key = "$selectedYear-$m";
                                $isPaid = isset($paidMonths[$hid][$key]);

                                $dueDate = new DateTime("$selectedYear-$m-01");
                                $today   = new DateTime();
                                $isOverdue = !$isPaid && $dueDate < $today;
                                $isFuture  = $dueDate > $today;

                                $class = 'bg-gray-100 text-gray-600 text-xs';
                                $text  = '—';

                                if ($isPaid) {
                                    $class = 'bg-green-100 text-green-800 font-medium';
                                    $text  = 'Paid';
                                } elseif ($isOverdue) {
                                    $class = 'bg-red-100 text-red-800 font-medium';
                                    $text  = 'Overdue';
                                } elseif ($isFuture) {
                                    $class = 'bg-gray-50 text-gray-500';
                                    $text  = 'Future';
                                }
                            ?>
                                <td class="px-3 py-4 text-center <?= $class ?>">
                                    <?= $text ?>
                                </td>
                            <?php endfor; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Legend -->
        <div class="p-6 bg-gray-50 border-t border-gray-200">
            <div class="flex flex-wrap gap-8 text-sm">
                <div class="flex items-center">
                    <span class="inline-block w-5 h-5 bg-green-100 border border-green-300 mr-2 rounded"></span> Paid
                </div>
                <div class="flex items-center">
                    <span class="inline-block w-5 h-5 bg-red-100 border border-red-300 mr-2 rounded"></span> Overdue
                </div>
                <div class="flex items-center">
                    <span class="inline-block w-5 h-5 bg-gray-50 border border-gray-300 mr-2 rounded"></span> Not paid / Future
                </div>
            </div>
        </div>
    </div>

    <!-- Optional action button (if you add new dues entry later) -->
    <!-- <div class="text-center sm:text-right mt-6">
        <a href="some-new-dues-page.php" class="inline-block bg-green-700 hover:bg-green-800 text-white font-medium py-3 px-8 rounded-lg shadow transition">
            + Record Bulk Dues
        </a>
    </div> -->
</div>

<?php include '../includes/footer.php'; ?>