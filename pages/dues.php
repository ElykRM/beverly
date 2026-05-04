<?php
include '../includes/auth.php';
include '../db.php';
include '../config.php';
include '../includes/header.php';

// Current year and range
$currentYear = (int)date('Y');
$selectedYear = isset($_GET['year']) ? (int)$_GET['year'] : $currentYear;

// Validate selected year is within allowed range
$minYear = START_YEAR;
$maxYear = $currentYear + 5;
if ($selectedYear < $minYear || $selectedYear > $maxYear) {
    $selectedYear = $currentYear;
}
$years = getYearRange();

// Get short month names
$months = getShortMonthNames();

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

// Build monthly data + totals
$monthlyData = [];
$monthlyTotals = array_fill(1, 12, 0.00); // total paid per month
$totalUnpaidAll = 0.00; // grand total unpaid for year

foreach ($paymentsRaw as $p) {
    $hid = $p['household_id'];
    $startY = (int)$p['period_year'];
    $startM = (int)$p['period_month'];
    $endY   = $p['period_to_year'] !== null ? (int)$p['period_to_year'] : $startY;
    $endM   = $p['period_to_month'] !== null ? (int)$p['period_to_month'] : $startM;
    $totalAmount = (float)$p['amount'];
    $isPromo = (int)$p['is_promo'];

    // For promo: divide by PROMO_EFFECTIVE_MONTHS (Jan–Oct), Nov–Dec = Promo tag only
    $effectiveMonths = $isPromo ? PROMO_EFFECTIVE_MONTHS : 12;

    $monthCount = 0;
    for ($y = $startY; $y <= $endY; $y++) {
        $mFrom = ($y == $startY) ? $startM : 1;
        $mTo   = ($y == $endY)   ? $endM   : 12;
        $monthCount += $mTo - $mFrom + 1;
    }

    $divisor = $isPromo ? min(PROMO_EFFECTIVE_MONTHS, $monthCount) : $monthCount;
    $perMonth = $divisor > 0 ? $totalAmount / $divisor : 0;

    for ($y = $startY; $y <= $endY; $y++) {
        if ($y != $selectedYear) continue;
        $mFrom = ($y == $startY) ? $startM : 1;
        $mTo   = ($y == $endY)   ? $endM   : 12;
        for ($m = $mFrom; $m <= $mTo; $m++) {
            $key = "$y-$m";
            $monthlyData[$hid][$key] = [
                'amount'   => round($perMonth, 2),
                'is_promo' => $isPromo,
                'month_num' => $m
            ];

            // Add to monthly total (only if not promo Nov-Dec)
            if (!$isPromo || $m <= PROMO_EFFECTIVE_MONTHS) {
                $monthlyTotals[$m] += round($perMonth, 2);
            }
        }
    }
}

// Calculate total unpaid per household and grand total unpaid
$householdUnpaid = [];
foreach ($households as $h) {
    $hid = $h['id'];
    $unpaid = 0.00;
    for ($m = 1; $m <= 12; $m++) {
        $key = "$selectedYear-$m";
        if (!isset($monthlyData[$hid][$key])) {
            $dueForMonth = getDueAmount($selectedYear, $m);
            $unpaid += $dueForMonth;
        }
    }
    $householdUnpaid[$hid] = $unpaid;
    $totalUnpaidAll += $unpaid;
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
        <form method="GET" id="year-form">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Year -->
            <div>
                <label for="year" class="block text-sm font-medium text-gray-700 mb-1">Select Year</label>
                    <select name="year" id="year" 
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                            onchange="document.getElementById('year-form').submit()">
                        <?php foreach ($years as $y): ?>
                            <option value="<?= $y ?>" <?= $y == $selectedYear ? 'selected' : '' ?>>
                                <?= $y ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
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
                    <option value="paid">Paid Only (incl. Promo)</option>
                    <option value="unpaid">Unpaid / Current Due</option>
                    <option value="overdue">Overdue (Past Months)</option>
                </select>
            </div>
        </div>

        <div class="mt-6 flex gap-4 justify-end">
            <button id="clear-filters" class="text-green-700 hover:text-green-900 underline font-medium">
                Clear Filters
            </button>
        </div>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto lg:overflow-x-visible">
            <table id="dues-table" class="w-full divide-y divide-gray-200">
                <thead class="bg-gray-50 sticky top-0 z-10">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-r border-gray-200">
                            Household
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-r border-gray-200">
                            Total Unpaid
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
                        $unpaidTotal = $householdUnpaid[$hid] ?? 0.00;
                        ?>
                        <tr class="hover:bg-gray-50 transition-colors cursor-pointer household-row"
                            data-search="<?= $rowText ?>"
                            onclick="window.location.href='../actions/view.php?id=<?= $hid ?>&referrer=dues'">
                            <td class="px-6 py-4 whitespace-normal break-words text-sm font-medium text-gray-900 border-r border-gray-200">
                                <div><?= $name ?></div>
                                <div class="text-xs text-gray-500"><?= $addr ?></div>
                            </td>
                            <td class="px-6 py-4 text-center text-sm font-bold text-red-600 border-r border-gray-200 whitespace-normal">
                                ₱<?= number_format($unpaidTotal, 2) ?>
                            </td>
                            <?php 
                            $hasPaid = false;
                            $hasOverdue = false;
                            $hasUnpaidCurrent = false;
                            for ($m = 1; $m <= 12; $m++): 
                                $key = "$selectedYear-$m";
                                $monthData = $monthlyData[$hid][$key] ?? null;
                                $isPaid = $monthData !== null;
                                $amount = $monthData['amount'] ?? 0;
                                $isPromo = $monthData['is_promo'] ?? 0;
                                $monthNum = $monthData['month_num'] ?? $m;

                                $dueDate = new DateTime("$selectedYear-$m-01");
                                $today   = new DateTime();
                                $isOverdue = !$isPaid && $dueDate < $today && $dueDate->format('Y-m') < date('Y-m');
                                $isCurrent = $dueDate->format('Y-m') === date('Y-m');
                                $isFuture  = $dueDate > $today;

                                if ($isPaid) $hasPaid = true;
                                if ($isOverdue) $hasOverdue = true;
                                if ($isCurrent && !$isPaid) $hasUnpaidCurrent = true;

                                $class = 'bg-gray-100 text-gray-600 text-xs';
                                $display = '—';

                                if ($isPaid) {
                                    $class = 'bg-green-100 text-green-800 font-medium text-s';
                                    if ($isPromo) {
                                        if ($monthNum <= PROMO_EFFECTIVE_MONTHS) {
                                            $display = '₱' . number_format($amount, 2);
                                        } else {
                                            $display = '<span class="inline-block bg-purple-100 text-purple-800 px-2 py-1 rounded-full text-xs font-bold">Promo</span>';
                                        }
                                    } else {
                                        $display = '₱' . number_format($amount, 2);
                                    }
                                } elseif ($isOverdue) {
                                    $class = 'bg-red-100 text-red-800 font-medium text-xs';
                                    $display = '';
                                } elseif ($isCurrent && !$isPaid) {
                                    $class = 'bg-yellow-100 text-yellow-800 font-medium text-xs';
                                    $display = '';
                                } elseif ($isFuture) {
                                    $class = 'bg-gray-50 text-gray-500 text-xs';
                                    $display = '';
                                }
                            ?>
                                <td class="px-3 py-4 text-center <?= $class ?> whitespace-normal break-words">
                                    <?= $display ?>
                                </td>
                            <?php endfor; ?>
                        </tr>
                    <?php endforeach; ?>

                    <!-- Total Paid Row -->
                    <tr class="bg-gray-100 font-bold">
                        <td class="px-6 py-4 text-right text-sm text-gray-900 border-r border-gray-200">
                            TOTAL PAID
                        </td>
                        <td class="px-6 py-4 text-center text-sm text-red-600 border-r border-gray-200">
                            ₱<?= number_format($totalUnpaidAll, 2) ?> (Unpaid)
                        </td>
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <td class="px-3 py-4 text-center text-sm text-green-800">
                                ₱<?= number_format($monthlyTotals[$m], 2) ?>
                            </td>
                        <?php endfor; ?>
                    </tr>

                    <tr id="no-results" style="display: none;">
                        <td colspan="<?= count($months) + 2 ?>" class="px-6 py-12 text-center text-gray-500 italic">
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
                    <span class="inline-block w-5 h-5 bg-red-600 border border-red-300 mr-2 rounded"></span> Overdue (past months)
                </div>
                <div class="flex items-center">
                    <span class="inline-block w-5 h-5 bg-yellow-600 border border-yellow-300 mr-2 rounded"></span> Unpaid (current month)
                </div>
                <div class="flex items-center">
                    <span class="inline-block w-5 h-5 bg-gray-600 border border-gray-300 mr-2 rounded"></span> Future
                </div>
            </div>
        </div>
    </div>

    <!-- Export to Excel -->
    <?php if (!empty($households)): ?>
        <div class="mt-6 text-right">
            <button onclick="exportExcel()" class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-8 rounded-lg shadow transition">
                Export All Households to Excel (.xlsx)
            </button>
        </div>
    <?php endif; ?>
</div>

<script>
// Modal popup for messages
function showPopupMessage(message) {
    const existing = document.getElementById('popup-overlay');
    if (existing) existing.remove();

    const overlay = document.createElement('div');
    overlay.id = 'popup-overlay';
    Object.assign(overlay.style, {
        position: 'fixed',
        inset: '0',
        background: 'rgba(0, 0, 0, 0.4)',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        padding: '1rem',
        zIndex: '9999'
    });

    const box = document.createElement('div');
    box.className = 'bg-white rounded-xl shadow-2xl border border-gray-200 p-6 text-center';
    Object.assign(box.style, { width: '100%', maxWidth: '28rem' });
    box.innerHTML = `
        <h3 class="text-lg font-bold text-gray-800 mb-3">Notice</h3>
        <p class="text-sm text-gray-700 mb-6"></p>
        <button type="button" class="modal-ok bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-6 rounded-lg">OK</button>
    `;

    const messageEl = box.querySelector('p');
    messageEl.textContent = message;
    Object.assign(messageEl.style, {
        whiteSpace: 'pre-line',
        textAlign: 'left',
        lineHeight: '1.5'
    });
    const okBtn = box.querySelector('.modal-ok');

    okBtn.addEventListener('click', () => overlay.remove());
    overlay.addEventListener('click', (ev) => {
        if (ev.target === overlay) overlay.remove();
    });

    overlay.appendChild(box);
    document.body.appendChild(overlay);
    okBtn.focus();
}

// Client-side search + filter + pagination (unchanged)
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
const perPage = <?= PAGINATION_PER_PAGE ?>;

function filterAndPaginate() {
    const searchText = (searchInput.value || '').toLowerCase().trim();
    const statusVal  = statusFilter.value;

    const visibleRows = rows.filter(row => {
        const matchesSearch = row.dataset.search.includes(searchText);

        let matchesStatus = true;
        if (statusVal !== 'all') {
            const cells = row.querySelectorAll('td:not(:first-child):not(:nth-child(2))');
            const hasPaid = Array.from(cells).some(td => 
                td.innerHTML.includes('₱') || td.innerHTML.includes('Promo')
            );
            const hasOverdue = Array.from(cells).some(td => 
                td.className.includes('bg-red-100')
            );
            const hasUnpaid = Array.from(cells).some(td => 
                td.className.includes('bg-yellow-100')
            );

            if (statusVal === 'paid')    matchesStatus = hasPaid;
            if (statusVal === 'unpaid')  matchesStatus = hasUnpaid || (!hasPaid && !hasOverdue);
            if (statusVal === 'overdue') matchesStatus = hasOverdue;
        }

        return matchesSearch && matchesStatus;
    });

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
            filterAndPaginate();
        };
        pageNumbers.appendChild(btn);
    }
}

// Export to Excel (server-side, all households for selected year)
function exportExcel() {
    const selectedYear = document.getElementById('year').value;
    const statusVal = document.getElementById('status-filter').value;

    fetch('../actions/export_dues_excel.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ year: selectedYear, status: statusVal })
    })
    .then(response => {
        if (response.status === 400) {
            // Error response (no data matches filter)
            return response.json().then(data => {
                showPopupMessage('No data to export: ' + (data.error || 'No households match the current filters.'));
                return null;
            });
        }
        if (!response.ok) throw new Error('Network response was not ok');
        return response.blob();
    })
    .then(blob => {
        if (!blob) return; // Error case, already handled
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = statusVal === 'all' ? `dues_overview_${selectedYear}.xlsx` : `dues_overview_${selectedYear}_${statusVal}.xlsx`;
        document.body.appendChild(a);
        a.click();
        a.remove();
        window.URL.revokeObjectURL(url);
    })
    .catch(err => {
        console.error('Export failed:', err);
        showPopupMessage('Export failed. Check console (F12) for details.');
    });
}

// Event listeners (unchanged)
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
    const searchText = (searchInput.value || '').toLowerCase().trim();
    const statusVal  = statusFilter.value;

    const visibleRows = rows.filter(row => {
        const matchesSearch = row.dataset.search.includes(searchText);

        let matchesStatus = true;
        if (statusVal !== 'all') {
            const cells = row.querySelectorAll('td:not(:first-child):not(:nth-child(2))');
            const hasPaid = Array.from(cells).some(td => 
                td.innerHTML.includes('₱') || td.innerHTML.includes('Promo')
            );
            const hasOverdue = Array.from(cells).some(td => td.textContent.includes('Overdue'));
            const hasUnpaid = Array.from(cells).some(td => td.textContent.includes('Unpaid'));

            if (statusVal === 'paid')    matchesStatus = hasPaid;
            if (statusVal === 'unpaid')  matchesStatus = hasUnpaid || (!hasPaid && !hasOverdue);
            if (statusVal === 'overdue') matchesStatus = hasOverdue;
        }

        return matchesSearch && matchesStatus;
    });

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