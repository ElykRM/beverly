<?php
include '../db.php';
include '../includes/header.php';

// Current date & periods
$today = new DateTime();
$currentYear  = (int)$today->format('Y');
$currentMonth = (int)$today->format('n');

$prevMonthDate = (clone $today)->modify('-1 month');
$prevYear  = (int)$prevMonthDate->format('Y');
$prevMonth = (int)$prevMonthDate->format('n');

// Full summary stats
$total_stmt = $pdo->query("SELECT COUNT(*) AS total FROM households");
$total = $total_stmt->fetch()['total'];

$status_stmt = $pdo->query("SELECT home_status, COUNT(*) AS count FROM households GROUP BY home_status");
$status_counts = [];
while ($row = $status_stmt->fetch(PDO::FETCH_ASSOC)) {
    $status_counts[$row['home_status']] = $row['count'];
}
$owners  = $status_counts['Owner']  ?? 0;
$renters = $status_counts['Renter'] ?? 0;
$members = $status_counts['Member'] ?? 0;

// Paid this month count
$paid_current_stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT household_id) AS count
    FROM payments p
    WHERE (p.period_year = :y AND p.period_month <= :m 
           AND (p.period_to_year IS NULL OR p.period_to_year > :y 
                OR (p.period_to_year = :y AND p.period_to_month >= :m)))
       OR (p.period_year < :y AND (p.period_to_year IS NULL OR p.period_to_year >= :y))
");
$paid_current_stmt->execute([':y' => $currentYear, ':m' => $currentMonth]);
$paid_this_month = $paid_current_stmt->fetch()['count'];

// Unpaid this month = total - paid this month
$unpaid_this_month = $total - $paid_this_month;

// Overdue (previous month unpaid)
$overdue_full_stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT h.id) AS count
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
$overdue_full_stmt->execute([':py' => $prevYear, ':pm' => $prevMonth]);
$total_overdue = $overdue_full_stmt->fetch()['count'];

// Fetch all households once (client-side filtering)
$householdsStmt = $pdo->query("
    SELECT id, last_name, first_name, middle_name, home_status, block, lot, street 
    FROM households 
    ORDER BY last_name, first_name
");
$households = $householdsStmt->fetchAll();

// Fetch all relevant payments once
$paymentsStmt = $pdo->prepare("
    SELECT household_id, period_year, period_month, period_to_year, period_to_month 
    FROM payments
    WHERE period_year <= :max_year AND (period_to_year IS NULL OR period_to_year >= :min_year)
");
$paymentsStmt->execute([':max_year' => $currentYear, ':min_year' => $prevYear]);
$allPayments = $paymentsStmt->fetchAll();

// Compute dues status for each household
$results_with_status = [];
foreach ($households as $h) {
    $hid = $h['id'];
    $currentCovered = false;
    $previousCovered = false;

    foreach ($allPayments as $p) {
        if ($p['household_id'] != $hid) continue;

        $startY = (int)$p['period_year'];
        $startM = (int)$p['period_month'];
        $endY   = $p['period_to_year'] !== null ? (int)$p['period_to_year'] : $startY;
        $endM   = $p['period_to_month'] !== null ? (int)$p['period_to_month'] : $startM;

        // Current month check
        if ($currentYear >= $startY && $currentYear <= $endY) {
            $mStart = ($currentYear == $startY) ? $startM : 1;
            $mEnd   = ($currentYear == $endY)   ? $endM   : 12;
            if ($currentMonth >= $mStart && $currentMonth <= $mEnd) {
                $currentCovered = true;
            }
        }

        // Previous month check
        if ($prevYear >= $startY && $prevYear <= $endY) {
            $mStart = ($prevYear == $startY) ? $startM : 1;
            $mEnd   = ($prevYear == $endY)   ? $endM   : 12;
            if ($prevMonth >= $mStart && $prevMonth <= $mEnd) {
                $previousCovered = true;
            }
        }
    }

    if ($currentCovered) {
        $h['dues_status'] = 'Paid';
        $h['status_class'] = 'bg-green-100 text-green-800';
    } else {
        $h['dues_status'] = 'Unpaid';
        $h['status_class'] = 'bg-yellow-100 text-yellow-800';

        if (!$previousCovered) {
            $h['dues_status'] = 'Overdue';
            $h['status_class'] = 'bg-red-100 text-red-800';
        }
    }

    $results_with_status[] = $h;
}
?>

<div class="mb-10">
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-6">
        <h2 class="text-3xl font-bold text-green-800">Reports</h2>
        <a href="../pages/index.php" class="text-green-700 hover:text-green-900 font-medium flex items-center gap-2">
            ← Back to Menu
        </a>
    </div>

    <p class="text-gray-600 mb-6">Household overview and dues status</p>

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
            <p class="text-sm text-gray-600">Unpaid This Month</p>
            <p class="text-4xl font-bold text-yellow-800"><?= $unpaid_this_month ?></p>
        </div>
        <div class="bg-white p-6 rounded-xl shadow-md border border-gray-200 text-center">
            <p class="text-sm text-gray-600">Overdue (prev month unpaid)</p>
            <p class="text-4xl font-bold text-red-600"><?= $total_overdue ?></p>
        </div>
    </div>

    <!-- Filters – instant client-side -->
    <div class="bg-white p-6 rounded-xl shadow-md border border-gray-200 mb-8">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-6">
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select id="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
                    <option value="ALL">All</option>
                    <option value="Owner">Owner</option>
                    <option value="Renter">Renter</option>
                    <option value="Member">Member</option>
                </select>
            </div>
            <div>
                <label for="block" class="block text-sm font-medium text-gray-700 mb-1">Block</label>
                <input type="text" id="block" placeholder="e.g. 5" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
            </div>
            <div>
                <label for="lot" class="block text-sm font-medium text-gray-700 mb-1">Lot</label>
                <input type="text" id="lot" placeholder="e.g. 12" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
            </div>
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Name Search</label>
                <input type="text" id="name" placeholder="e.g. John Doe" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
            </div>
            <div>
                <label for="dues_status" class="block text-sm font-medium text-gray-700 mb-1">Dues Status</label>
                <select id="dues_status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
                    <option value="ALL">All</option>
                    <option value="Paid">Paid</option>
                    <option value="Unpaid">Unpaid</option>
                    <option value="Overdue">Overdue</option>
                </select>
            </div>
        </div>

            <div class="mt-6 flex gap-4 justify-end">
                <button id="clear-filters" class="text-green-700 hover:text-green-900 underline font-medium">
                    Clear Filters
                </button>
            </div>
    </div>

    <!-- Results Table -->
    <div class="overflow-x-auto bg-white rounded-xl shadow-md border border-gray-200">
        <table id="reports-table" class="min-w-full divide-y divide-gray-200">
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
            <tbody class="divide-y divide-gray-200" id="table-body">
                <?php foreach ($results_with_status as $h): ?>
                    <?php
                    $fullName = $h['first_name'] . ' ' . $h['last_name'];
                    $searchText = strtolower($fullName . ' ' . ($h['block'] ?? '') . ' ' . ($h['lot'] ?? '') . ' ' . ($h['street'] ?? ''));
                    ?>
                    <tr class="hover:bg-gray-50 transition-colors cursor-pointer household-row"
                        data-search="<?= $searchText ?>"
                        data-home-status="<?= $h['home_status'] ?>"
                        data-dues-status="<?= $h['dues_status'] ?>"
                        data-block="<?= htmlspecialchars($h['block'] ?? '') ?>"
                        data-lot="<?= htmlspecialchars($h['lot'] ?? '') ?>"
                        onclick="window.location.href='../actions/view.php?id=<?= $h['id'] ?>'">
                        <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">
                            <?= htmlspecialchars($fullName) ?>
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
                <tr id="no-results" style="display: none;">
                    <td colspan="6" class="px-6 py-12 text-center text-gray-500 italic">
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

    <!-- Export -->
    <?php if (!empty($results_with_status)): ?>
        <div class="mt-6 text-right">
            <button onclick="exportCSV()" class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-8 rounded-lg shadow transition">
                Export Filtered List to CSV
            </button>
        </div>
    <?php endif; ?>
</div>

<script>
// Client-side filtering + pagination
const rows = Array.from(document.querySelectorAll('.household-row'));
const noResults = document.getElementById('no-results');
const prevBtn = document.getElementById('prev-page');
const nextBtn = document.getElementById('next-page');
const pageNumbers = document.getElementById('page-numbers');
const showingCount = document.getElementById('showing-count');
const totalFilteredEl = document.getElementById('total-filtered');

const statusFilter = document.getElementById('status');
const blockFilter = document.getElementById('block');
const lotFilter = document.getElementById('lot');
const nameFilter = document.getElementById('name');
const duesFilter = document.getElementById('dues_status');
const clearBtn = document.getElementById('clear-filters');

let currentPage = 1;
const perPage = 10;

function filterAndPaginate() {
    const statusVal = statusFilter.value;
    const blockVal = blockFilter.value.trim().toLowerCase();
    const lotVal = lotFilter.value.trim().toLowerCase();
    const nameVal = nameFilter.value.trim().toLowerCase();
    const duesVal = duesFilter.value;

    const visibleRows = rows.filter(row => {
        const matchesStatus = statusVal === 'ALL' || row.dataset.homeStatus === statusVal;
        const matchesBlock  = !blockVal || row.dataset.block.toLowerCase().includes(blockVal);
        const matchesLot    = !lotVal   || row.dataset.lot.toLowerCase().includes(lotVal);
        const matchesName   = !nameVal  || row.dataset.search.includes(nameVal);
        const matchesDues   = duesVal === 'ALL' || row.dataset.duesStatus === duesVal;

        return matchesStatus && matchesBlock && matchesLot && matchesName && matchesDues;
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

// Instant filtering events
statusFilter.addEventListener('change', () => { currentPage = 1; filterAndPaginate(); });
blockFilter.addEventListener('input', () => { currentPage = 1; filterAndPaginate(); });
lotFilter.addEventListener('input', () => { currentPage = 1; filterAndPaginate(); });
nameFilter.addEventListener('input', () => { currentPage = 1; filterAndPaginate(); });
duesFilter.addEventListener('change', () => { currentPage = 1; filterAndPaginate(); });

clearBtn.addEventListener('click', () => {
    statusFilter.value = 'ALL';
    blockFilter.value = '';
    lotFilter.value = '';
    nameFilter.value = '';
    duesFilter.value = 'ALL';
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
    const visibleRows = rows.filter(r => r.style.display !== 'none');
    const totalPages = Math.ceil(visibleRows.length / perPage);
    if (currentPage < totalPages) {
        currentPage++;
        filterAndPaginate();
    }
});

// Initial load
filterAndPaginate();
</script>

<script>
// Export CSV
function exportCSV() {
    try {
        const rawData = <?= json_encode($results_with_status, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?> || [];

        if (!Array.isArray(rawData) || rawData.length === 0) {
            alert('No data to export. Try adjusting filters.');
            return;
        }

        let csv = 'Name,Status,Block,Lot,Street,Dues Status\n';
        rawData.forEach(row => {
            const name   = `"${(row.first_name + ' ' + row.last_name).replace(/"/g, '""')}"`;
            const status = `"${(row.home_status || '').replace(/"/g, '""')}"`;
            const block  = `"${(row.block || '').replace(/"/g, '""')}"`;
            const lot    = `"${(row.lot || '').replace(/"/g, '""')}"`;
            const street = `"${(row.street || '').replace(/"/g, '""')}"`;
            const dues   = `"${(row.dues_status || '').replace(/"/g, '""')}"`;

            csv += `${name},${status},${block},${lot},${street},${dues}\n`;
        });

        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `hoa_report_${new Date().toISOString().slice(0,10)}.csv`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
    } catch (err) {
        console.error('CSV Export failed:', err);
        alert('Export failed. Check browser console (F12) for details.');
    }
}
</script>

<?php include '../includes/footer.php'; ?>