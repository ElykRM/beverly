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

// Filters
$status_filter = $_GET['status'] ?? 'ALL';
$block_filter  = trim($_GET['block'] ?? '');
$lot_filter    = trim($_GET['lot'] ?? '');
$name_search   = trim($_GET['name'] ?? '');
$dues_filter   = $_GET['dues_status'] ?? 'ALL';

// Build filtered query
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
    if ($dues_filter === 'Paid') {
        $where .= " AND EXISTS (
            SELECT 1 FROM payments p 
            WHERE h.id = p.household_id 
              AND ((p.period_year = :cy AND p.period_month <= :cm 
                    AND (p.period_to_year IS NULL OR p.period_to_year > :cy 
                         OR (p.period_to_year = :cy AND p.period_to_month >= :cm)))
                 OR (p.period_year < :cy AND (p.period_to_year IS NULL OR p.period_to_year >= :cy)))
        )";
        $params[':cy'] = $currentYear;
        $params[':cm'] = $currentMonth;
    } else {
        $where .= " AND NOT EXISTS (
            SELECT 1 FROM payments p 
            WHERE h.id = p.household_id 
              AND ((p.period_year = :cy AND p.period_month <= :cm 
                    AND (p.period_to_year IS NULL OR p.period_to_year > :cy 
                         OR (p.period_to_year = :cy AND p.period_to_month >= :cm)))
                 OR (p.period_year < :cy AND (p.period_to_year IS NULL OR p.period_to_year >= :cy)))
        )";
        $params[':cy'] = $currentYear;
        $params[':cm'] = $currentMonth;

        if ($dues_filter === 'Overdue') {
            $where .= " AND NOT EXISTS (
                SELECT 1 FROM payments p 
                WHERE h.id = p.household_id 
                  AND ((p.period_year = :py AND p.period_month <= :pm 
                        AND (p.period_to_year IS NULL OR p.period_to_year > :py 
                             OR (p.period_to_year = :py AND p.period_to_month >= :pm)))
                     OR (p.period_year < :py AND (p.period_to_year IS NULL OR p.period_to_year >= :py)))
            )";
            $params[':py'] = $prevYear;
            $params[':pm'] = $prevMonth;
        }
    }
}

$query .= $where . " ORDER BY h.last_name, h.first_name";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$households = $stmt->fetchAll();

// Compute display status
$results_with_status = [];
foreach ($households as $h) {
    $hid = $h['id'];

    $cstmt = $pdo->prepare("
        SELECT 1 FROM payments 
        WHERE household_id = :hid 
          AND ((period_year = :y AND period_month <= :m 
                AND (period_to_year IS NULL OR period_to_year > :y 
                     OR (period_to_year = :y AND period_to_month >= :m)))
             OR (period_year < :y AND (period_to_year IS NULL OR period_to_year >= :y)))
    ");
    $cstmt->execute([':hid' => $hid, ':y' => $currentYear, ':m' => $currentMonth]);
    $is_paid_current = $cstmt->fetch() !== false;

    $pstmt = $pdo->prepare("
        SELECT 1 FROM payments 
        WHERE household_id = :hid 
          AND ((period_year = :y AND period_month <= :m 
                AND (period_to_year IS NULL OR period_to_year > :y 
                     OR (period_to_year = :y AND period_to_month >= :m)))
             OR (period_year < :y AND (period_to_year IS NULL OR period_to_year >= :y)))
    ");
    $pstmt->execute([':hid' => $hid, ':y' => $prevYear, ':m' => $prevMonth]);
    $is_paid_prev = $pstmt->fetch() !== false;

    $h['dues_status'] = 'Unpaid';
    $h['status_class'] = 'bg-yellow-100 text-yellow-800';

    if ($is_paid_current) {
        $h['dues_status'] = 'Paid';
        $h['status_class'] = 'bg-green-100 text-green-800';
    } elseif (!$is_paid_prev) {
        $h['dues_status'] = 'Overdue';
        $h['status_class'] = 'bg-red-100 text-red-800';
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

    <p class="text-gray-600 mb-6">Household overview and filtered dues status</p>

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
            <p class="text-sm text-gray-600">Overdue (prev month unpaid)</p>
            <p class="text-4xl font-bold text-red-600"><?= $total_overdue ?></p>
        </div>
    </div>

    <!-- Filters -->
    <form method="GET" class="bg-white p-6 rounded-xl shadow-md border border-gray-200 mb-8">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-6">
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" id="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    <option value="ALL" <?= $status_filter === 'ALL' ? 'selected' : '' ?>>All</option>
                    <option value="Owner"   <?= $status_filter === 'Owner'   ? 'selected' : '' ?>>Owner</option>
                    <option value="Renter"  <?= $status_filter === 'Renter'  ? 'selected' : '' ?>>Renter</option>
                    <option value="Member"  <?= $status_filter === 'Member'  ? 'selected' : '' ?>>Member</option>
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
                       placeholder="e.g. John Doe" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
            </div>
            <div>
                <label for="dues_status" class="block text-sm font-medium text-gray-700 mb-1">Dues Status</label>
                <select name="dues_status" id="dues_status" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    <option value="ALL"     <?= $dues_filter === 'ALL'     ? 'selected' : '' ?>>All</option>
                    <option value="Paid"    <?= $dues_filter === 'Paid'    ? 'selected' : '' ?>>Paid</option>
                    <option value="Unpaid"  <?= $dues_filter === 'Unpaid'  ? 'selected' : '' ?>>Unpaid</option>
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

    <!-- Results Table with Pagination -->
    <?php if (empty($results_with_status)): ?>
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-6 rounded-r-lg">
            No households match the current filters.
        </div>
    <?php else: ?>
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
                        <tr class="hover:bg-gray-50 transition-colors cursor-pointer household-row"
                            data-name="<?= strtolower($h['first_name'] . ' ' . $h['last_name'] . ' ' . ($h['block'] ?? '') . ' ' . ($h['lot'] ?? '')) ?>"
                            onclick="window.location.href='view.php?id=<?= $h['id'] ?>'">
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
                <button id="prev-page" class="px-4 py-1 bg-gray-200 hover:bg-gray-300 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed" disabled>Previous</button>
                <div id="page-numbers" class="flex gap-2"></div>
                <button id="next-page" class="px-4 py-1 bg-gray-200 hover:bg-gray-300 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed" disabled>Next</button>
            </div>
        </div>

<!-- Export + Script -->
<?php if (!empty($results_with_status)): ?>
    <div class="mt-6 text-right">
        <button onclick="exportCSV()" class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-8 rounded-lg shadow transition">
            Export Filtered List to CSV
        </button>
    </div>

    <script>
    function exportCSV() {
        try {
            // Get data from PHP → already JSON-encoded
            const rawData = <?= json_encode($results_with_status, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?> || [];

            if (!Array.isArray(rawData) || rawData.length === 0) {
                alert('No data to export. Try adjusting filters.');
                return;
            }

            // Build CSV
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

            // Create blob and download
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
<?php endif; ?>

    <?php endif; ?>
</div>

<script>
// Client-side pagination + filtering for reports.php
const statusFilter = document.getElementById('status-filter') || null;
const nameFilter   = document.getElementById('name-filter') || null;
const duesFilter   = document.getElementById('dues_status') || null;
const rows         = Array.from(document.querySelectorAll('.household-row'));
const noResults    = document.getElementById('no-results');
const prevBtn      = document.getElementById('prev-page');
const nextBtn      = document.getElementById('next-page');
const pageNumbers  = document.getElementById('page-numbers');
const showingCount = document.getElementById('showing-count');
const totalFilteredEl = document.getElementById('total-filtered');

let currentPage = 1;
const perPage = 10;

function getVisibleRows() {
    let visible = rows;

    // Apply name search if input exists
    if (nameFilter) {
        const nameValue = nameFilter.value.toLowerCase().trim();
        if (nameValue) {
            visible = visible.filter(row => {
                const text = row.textContent.toLowerCase();
                return text.includes(nameValue);
            });
        }
    }

    // Apply dues status filter (already done server-side, but double-check client-side if needed)
    // You can add client-side dues filter here later if you want

    return visible;
}

function updateTable() {
    const visibleRows = getVisibleRows();

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
    const visibleRows = getVisibleRows();
    const totalPages = Math.ceil(visibleRows.length / perPage);
    if (currentPage < totalPages) {
        currentPage++;
        updateTable();
    }
});

// Re-run on filter change (if client-side filters added later)
if (nameFilter) nameFilter.addEventListener('input', () => { currentPage = 1; updateTable(); });
if (duesFilter) duesFilter.addEventListener('change', () => { currentPage = 1; updateTable(); });

// Initial load
updateTable();
</script>

<?php include '../includes/footer.php'; ?>