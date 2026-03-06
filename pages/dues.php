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

    <!-- Filters + Search -->
    <div class="bg-white p-6 rounded-xl shadow-md border border-gray-200 mb-10">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Year -->
            <div>
                <label for="year" class="block text-sm font-medium text-gray-700 mb-1">Select Year</label>
                <form method="GET" id="year-form">
                    <select name="year" id="year" 
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                            onchange="document.getElementById('year-form').submit()">
                        <?php foreach ($years as $y): ?>
                            <option value="<?= $y ?>" <?= $y == $selectedYear ? 'selected' : '' ?>>
                                <?= $y ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>

            <!-- Search -->
            <div>
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <input type="text" id="search" placeholder="Name, block, lot..." 
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
            </div>

            <!-- Status filter -->
            <div>
                <label for="status-filter" class="block text-sm font-medium text-gray-700 mb-1">Status Filter</label>
                <select id="status-filter" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
                    <option value="all">All Households</option>
                    <option value="paid">Paid Only</option>
                    <option value="unpaid">Unpaid / Overdue</option>
                    <option value="overdue">Overdue Only</option>
                </select>
            </div>
        </div>

        <div class="mt-6">
            <button id="clear-filters" class="text-green-700 hover:text-green-900 underline font-medium">
                Clear Filters
            </button>
        </div>
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
                        $rowText = strtolower($name . ' ' . $addr);
                        ?>
                        <tr class="hover:bg-gray-50 transition-colors cursor-pointer household-row"
                            data-search="<?= $rowText ?>"
                            data-has-paid="false"
                            data-has-overdue="false">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 border-r border-gray-200">
                                <div><?= $name ?></div>
                                <div class="text-xs text-gray-500"><?= $addr ?></div>
                            </td>
                            <?php 
                            $hasPaid = false;
                            $hasOverdue = false;
                            for ($m = 1; $m <= 12; $m++): 
                                $key = "$selectedYear-$m";
                                $monthData = $monthlyData[$hid][$key] ?? null;
                                $isPaid = $monthData !== null;
                                $amount = $monthData['amount'] ?? 0;
                                $isPromo = $monthData['is_promo'] ?? 0;

                                $dueDate = new DateTime("$selectedYear-$m-01");
                                $today   = new DateTime();
                                $isOverdue = !$isPaid && $dueDate < $today;
                                $isFuture  = $dueDate > $today;

                                if ($isPaid) $hasPaid = true;
                                if ($isOverdue) $hasOverdue = true;

                                $class = 'bg-gray-100 text-gray-600 text-xs';
                                $display = '—';

                                if ($isPaid) {
                                    $class = 'bg-green-100 text-green-800 font-medium text-xs';
                                    $display = $isPromo ? '<span class="inline-block bg-purple-100 text-purple-800 px-2 py-1 rounded-full text-xs font-bold">Promo</span>' : '₱' . number_format($amount, 2);
                                } elseif ($isOverdue) {
                                    $class = 'bg-red-100 text-red-800 font-medium text-xs';
                                    $display = 'Overdue';
                                } elseif ($isFuture) {
                                    $class = 'bg-gray-50 text-gray-500 text-xs';
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
                    <span class="inline-block w-5 h-5 bg-green-600 border border-green-300 mr-2 rounded"></span> Paid (amount or Promo)
                </div>
                <div class="flex items-center">
                    <span class="inline-block w-5 h-5 bg-red-600 border border-red-300 mr-2 rounded"></span> Overdue
                </div>
                <div class="flex items-center">
                    <span class="inline-block w-5 h-5 bg-gray-600 border border-gray-300 mr-2 rounded"></span> Not paid / Future
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Client-side search + filter + pagination
const searchInput   = document.getElementById('search');
const statusFilter  = document.getElementById('status-filter');
const clearBtn      = document.getElementById('clear-filters');
const rows          = Array.from(document.querySelectorAll('.household-row'));
const noResults     = document.getElementById('no-results');
const prevBtn       = document.getElementById('prev-page');
const nextBtn       = document.getElementById('next-page');
const pageNumbers   = document.getElementById('page-numbers');
const showingCount  = document.getElementById('showing-count');
const totalFilteredEl = document.getElementById('total-filtered');

let currentPage = 1;
const perPage = 10;

function filterAndPaginate() {
    const searchText = (searchInput.value || '').toLowerCase().trim();
    const statusVal  = statusFilter.value;

    const visibleRows = rows.filter(row => {
        const matchesSearch = row.dataset.search.includes(searchText);

        let matchesStatus = true;
        if (statusVal !== 'all') {
            const cells = row.querySelectorAll('td:not(:first-child)');
            const hasPaid = Array.from(cells).some(td => td.textContent.includes('₱') || td.textContent.includes('Promo'));
            const hasOverdue = Array.from(cells).some(td => td.textContent.includes('Overdue'));

            if (statusVal === 'paid')    matchesStatus = hasPaid;
            if (statusVal === 'unpaid')  matchesStatus = !hasPaid;
            if (statusVal === 'overdue') matchesStatus = hasOverdue;
        }

        return matchesSearch && matchesStatus;
    });

    // Update counts
    totalFilteredEl.textContent = visibleRows.length;
    showingCount.textContent = Math.min(visibleRows.length, perPage);

    // Hide all rows first
    rows.forEach(r => r.style.display = 'none');

    // Show current page
    const start = (currentPage - 1) * perPage;
    const end   = start + perPage;
    const pageRows = visibleRows.slice(start, end);

    pageRows.forEach(row => row.style.display = '');

    noResults.style.display = visibleRows.length === 0 ? '' : 'none';

    const totalPages = Math.ceil(visibleRows.length / perPage) || 1;
    currentPage = Math.min(currentPage, totalPages);

    prevBtn.disabled = currentPage <= 1;
    nextBtn.disabled = currentPage >= totalPages;

    // Rebuild page numbers
    pageNumbers.innerHTML = '';
    for (let i = 1; i <= totalPages; i++) {
        const btn = document.createElement('button');
        btn.textContent = i;
        btn.className = `px-3 py-1 rounded-lg text-sm font-medium ${
            i === currentPage ? 'bg-green-600 text-white' : 'bg-gray-200 hover:bg-gray-300 text-gray-700'
        }`;
        btn.onclick = () => {
            currentPage = i;
            filterAndPaginate();
        };
        pageNumbers.appendChild(btn);
    }
}

// Event listeners
searchInput.addEventListener('input', () => { currentPage = 1; filterAndPaginate(); });
statusFilter.addEventListener('change', () => { currentPage = 1; filterAndPaginate(); });

clearBtn.addEventListener('click', () => {
    searchInput.value = '';
    statusFilter.value = 'all';
    currentPage = 1;
    filterAndPaginate();
});

prevBtn.addEventListener('click', () => {
    if (currentPage > 1) {
        currentPage--;
        filterAndPaginate();
    }
});

nextBtn.addEventListener('click', () => {
    const visibleRows = Array.from(rows).filter(r => r.style.display !== 'none');
    const totalPages = Math.ceil(visibleRows.length / perPage);
    if (currentPage < totalPages) {
        currentPage++;
        filterAndPaginate();
    }
});

// Initial load
filterAndPaginate();
</script>

<?php include '../includes/footer.php'; ?>