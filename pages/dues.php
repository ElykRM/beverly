<?php
include '../db.php';
include '../includes/header.php';

// Current year and range
$currentYear = (int)date('Y');
$selectedYear = isset($_GET['year']) ? (int)$_GET['year'] : $currentYear;

$minYear = $currentYear - 5;
$maxYear = $currentYear + 5;
if ($selectedYear < $minYear || $selectedYear > $maxYear) {
    $selectedYear = $currentYear;
}
$years = range($minYear, $maxYear);

// Months
$months = [1=>'Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

// Fetch all households
$householdsStmt = $pdo->query("
    SELECT id, last_name, first_name, block, lot 
    FROM households 
    ORDER BY last_name, first_name
");
$households = $householdsStmt->fetchAll();

// Fetch payments with is_promo
$paymentsRaw = [];
if (!empty($households)) {
    $pstmt = $pdo->prepare("
        SELECT household_id, period_year, period_month, period_to_year, period_to_month, amount, is_promo
        FROM payments
        WHERE (period_year = ? OR period_to_year = ? OR 
               (period_year <= ? AND (period_to_year IS NULL OR period_to_year >= ?)))
    ");
    $pstmt->execute([$selectedYear, $selectedYear, $selectedYear, $selectedYear]);
    $paymentsRaw = $pstmt->fetchAll();
}

// Build monthly data
$monthlyData = [];
foreach ($paymentsRaw as $p) {
    $hid = $p['household_id'];
    $startY = (int)$p['period_year'];
    $startM = (int)$p['period_month'];
    $endY   = $p['period_to_year'] !== null ? (int)$p['period_to_year'] : $startY;
    $endM   = $p['period_to_month'] !== null ? (int)$p['period_to_month'] : $startM;
    $totalAmount = (float)$p['amount'];
    $isPromo = (int)$p['is_promo'];

    $monthCount = 0;
    for ($y = $startY; $y <= $endY; $y++) {
        $mFrom = ($y == $startY) ? $startM : 1;
        $mTo   = ($y == $endY)   ? $endM   : 12;
        $monthCount += $mTo - $mFrom + 1;
    }

    $perMonth = $monthCount > 0 ? $totalAmount / $monthCount : 0;

    for ($y = $startY; $y <= $endY; $y++) {
        if ($y != $selectedYear) continue;
        $mFrom = ($y == $startY) ? $startM : 1;
        $mTo   = ($y == $endY)   ? $endM   : 12;
        for ($m = $mFrom; $m <= $mTo; $m++) {
            $key = "$y-$m";
            $monthlyData[$hid][$key] = [
                'amount'   => round($perMonth, 2),
                'is_promo' => $isPromo
            ];
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

    <p class="text-gray-600 mb-6">View payment status and amounts per month for the selected year.</p>

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
            <table id="dues-table" class="min-w-full divide-y divide-gray-200">
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
                <tbody class="bg-white divide-y divide-gray-200" id="table-body">
                    <?php foreach ($households as $h): ?>
                        <?php
                        $hid = $h['id'];
                        $name = htmlspecialchars($h['last_name'] . ', ' . $h['first_name']);
                        $addr = $h['block'] && $h['lot'] ? "Block {$h['block']} Lot {$h['lot']}" : '—';
                        ?>
                        <tr class="hover:bg-gray-50 transition-colors household-row"
                            data-name="<?= strtolower($name . ' ' . $addr) ?>"
                            '">
                            <td class="px-6 py-4 whitespace-nowrap text-medium font-medium text-gray-900 border-r border-gray-200">
                                <div><?= $name ?></div>
                                <div class="text-xs text-gray-500"><?= $addr ?></div>
                            </td>
                            <?php for ($m = 1; $m <= 12; $m++): 
                                $key = "$selectedYear-$m";
                                $monthData = $monthlyData[$hid][$key] ?? null;
                                $isPaid = $monthData !== null;
                                $amount = $monthData['amount'] ?? 0;
                                $isPromo = $monthData['is_promo'] ?? 0;

                                $dueDate = new DateTime("$selectedYear-$m-01");
                                $today   = new DateTime();
                                $isOverdue = !$isPaid && $dueDate < $today;
                                $isFuture  = $dueDate > $today;

                                $class = 'bg-gray-100 text-gray-600 text-xs';
                                $display = '—';

                                if ($isPaid) {
                                    $class = 'bg-green-100 text-green-800 text-medium font-medium';
                                    $display = $isPromo ? '<span class=" text-purple-700">Promo</span>' : '₱' . number_format($amount, 2);
                                } elseif ($isOverdue) {
                                    $class = 'bg-red-100 text-red-800 text-medium font-medium';
                                    $display = 'Overdue';
                                } elseif ($isFuture) {
                                    $class = 'bg-gray-50 text-gray-500 text-medium font-medium';
                                    $display = 'Future';
                                }
                            ?>
                                <td class="px-3 py-4 text-center <?= $class ?>">
                                    <?= $display ?>
                                </td>
                            <?php endfor; ?>
                        </tr>
                    <?php endforeach; ?>
                    <tr id="no-results" style="display: none;">
                        <td colspan="<?= count($months) + 1 ?>" class="px-6 py-12 text-center text-gray-500 italic">
                            No households match the current filters.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div id="pagination" class="p-6 bg-gray-50 border-t border-gray-200 flex flex-col sm:flex-row justify-between items-center gap-4">
            <div class="text-sm text-gray-600">
                Showing <span id="showing-count">0</span> of <span id="total-filtered">0</span> households
            </div>
            <div class="flex gap-2 items-center">
                <button id="prev-page" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed" disabled>Previous</button>
                <div id="page-numbers" class="flex gap-2"></div>
                <button id="next-page" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed" disabled>Next</button>
            </div>
        </div>

        <!-- Legend -->
        <div class="p-6 bg-gray-50 border-t border-gray-200">
            <div class="flex flex-wrap gap-8 text-sm">
                <div class="flex items-center">
                    <span class="inline-block w-5 h-5 bg-green-100 border border-green-300 mr-2 rounded"></span> Paid (amount or Promo)
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
</div>

<script>
// Pagination for dues.php
const rows         = Array.from(document.querySelectorAll('.household-row'));
const noResults    = document.getElementById('no-results');
const prevBtn      = document.getElementById('prev-page');
const nextBtn      = document.getElementById('next-page');
const pageNumbers  = document.getElementById('page-numbers');
const showingCount = document.getElementById('showing-count');
const totalFilteredEl = document.getElementById('total-filtered');

let currentPage = 1;
const perPage = 10;

function updateTable() {
    const visibleRows = rows; // no additional client filter

    totalFilteredEl.textContent = visibleRows.length;
    showingCount.textContent = Math.min(visibleRows.length, perPage);

    rows.forEach(r => r.style.display = 'none');

    const start = (currentPage - 1) * perPage;
    const end   = start + perPage;
    const pageRows = visibleRows.slice(start, end);

    pageRows.forEach(row => row.style.display = '');

    noResults.style.display = visibleRows.length === 0 ? '' : 'none';

    const totalPages = Math.ceil(visibleRows.length / perPage) || 1;
    currentPage = Math.min(currentPage, totalPages);

    prevBtn.disabled = currentPage <= 1;
    nextBtn.disabled = currentPage >= totalPages;

    pageNumbers.innerHTML = '';
    for (let i = 1; i <= totalPages; i++) {
        const btn = document.createElement('button');
        btn.textContent = i;
        btn.className = `px-3 py-1 rounded-lg text-sm font-medium ${
            i === currentPage ? 'bg-green-600 text-white' : 'bg-gray-200 hover:bg-gray-300 text-gray-700'
        }`;
        btn.onclick = () => {
            currentPage = i;
            updateTable();
        };
        pageNumbers.appendChild(btn);
    }
}

prevBtn.addEventListener('click', () => {
    if (currentPage > 1) {
        currentPage--;
        updateTable();
    }
});

nextBtn.addEventListener('click', () => {
    const totalPages = Math.ceil(rows.length / perPage);
    if (currentPage < totalPages) {
        currentPage++;
        updateTable();
    }
});

updateTable();
</script>

<?php include '../includes/footer.php'; ?>