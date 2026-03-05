<?php
include '../db.php';
include '../includes/header.php';

// Pagination settings
$perPage = 10; // ← Change here if you want more/less per page

// Fetch ALL households once (JS will handle filtering + pagination)
$stmt = $pdo->query("
    SELECT id, last_name, first_name, middle_name, home_status, 
           block, lot, street
    FROM households 
    ORDER BY last_name ASC, first_name ASC
");
$allHouseholds = $stmt->fetchAll();

$success_msg = $_GET['msg'] ?? '';
?>

<div class="mb-8">
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-6">
        <h2 class="text-3xl font-bold text-green-800">Habitants / Residents</h2>
        <a href="../pages/index.php" class="text-green-700 hover:text-green-900 font-medium flex items-center gap-2">
            ← Back to Menu
        </a>
    </div>

    <?php if ($success_msg): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-r-lg">
            <?= htmlspecialchars($success_msg) ?>
        </div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="bg-white p-6 rounded-xl shadow-md border border-gray-200 mb-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label for="status-filter" class="block text-sm font-medium text-gray-700 mb-1">Home Status</label>
                <select id="status-filter" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
                    <option value="ALL">All Statuses</option>
                    <option value="Owner">Owner</option>
                    <option value="Renter">Renter</option>
                    <option value="Member">Member</option>
                </select>
            </div>

            <div>
                <label for="name-filter" class="block text-sm font-medium text-gray-700 mb-1">Search Name / Address</label>
                <input type="text" id="name-filter" placeholder="e.g. John Doe" 
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
            </div>

            <div class="flex items-end gap-4">
                <button id="clear-filters" class="text-green-700 hover:text-green-900 underline font-medium">
                    Clear Filters
                </button>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-200 mb-8">
        <?php if (empty($allHouseholds)): ?>
            <div class="p-12 text-center text-gray-500">
                No households found in the database.
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table id="households-table" class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Name</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">First Name</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Middle Name</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Block</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lot</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Street</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200" id="table-body">
                        <?php foreach ($allHouseholds as $h): ?>
                            <tr class="hover:bg-gray-50 transition-colors cursor-pointer household-row"
                                data-lastname="<?= htmlspecialchars(strtolower($h['last_name'] ?? '')) ?>"
                                data-firstname="<?= htmlspecialchars(strtolower($h['first_name'] ?? '')) ?>"
                                data-middlename="<?= htmlspecialchars(strtolower($h['middle_name'] ?? '')) ?>"
                                data-status="<?= htmlspecialchars($h['home_status']) ?>"
                                onclick="window.location.href='../actions/view.php?id=<?= $h['id'] ?>'">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?= htmlspecialchars($h['last_name']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= htmlspecialchars($h['first_name']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= htmlspecialchars($h['middle_name'] ?: '-') ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= htmlspecialchars($h['home_status']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= htmlspecialchars($h['block'] ?: '-') ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= htmlspecialchars($h['lot'] ?: '-') ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= htmlspecialchars($h['street'] ?: '-') ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr id="no-results" style="display: none;">
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500 italic">
                                No households match the current filters.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination controls -->
            <div id="pagination" class="p-6 bg-gray-50 border-t border-gray-200 flex flex-col sm:flex-row justify-between items-center gap-4">
                <div class="text-sm text-gray-600">
                    Showing <span id="showing-count">0</span> of <span id="total-filtered">0</span> households
                </div>
                <div class="flex gap-2 items-center">
                    <button id="prev-page" class="px-3 py-1 bg-gray-200 hover:bg-gray-300 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed" disabled>Previous</button>
                    <div id="page-numbers" class="flex gap-2"></div>
                    <button id="next-page" class="px-3 py-1 bg-gray-200 hover:bg-gray-300 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed" disabled>Next</button>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

        <!-- New Household button at bottom -->
        <div class="text-center sm:text-right mt-6">
            <a href="../actions/add.php" class="inline-block bg-green-700 hover:bg-green-800 text-white font-medium py-3 px-8 rounded-lg shadow transition">
                + New Household
            </a>
        </div>

<script>
// Client-side filtering + pagination
const statusFilter = document.getElementById('status-filter');
const nameFilter   = document.getElementById('name-filter');
const clearBtn     = document.getElementById('clear-filters');
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
    const statusValue = (statusFilter.value || 'ALL').toUpperCase().trim();
    const nameValue   = (nameFilter.value || '').toLowerCase().trim();

    return rows.filter(row => {
        const rowStatus = (row.dataset.status || '').toUpperCase().trim();
        const fullName  = [
            row.dataset.lastname   || '',
            row.dataset.firstname  || '',
            row.dataset.middlename || ''
        ].join(' ').toLowerCase().trim();

        const address = [
            row.querySelector('td:nth-child(5)')?.textContent?.toLowerCase() || '',
            row.querySelector('td:nth-child(6)')?.textContent?.toLowerCase() || '',
            row.querySelector('td:nth-child(7)')?.textContent?.toLowerCase() || ''
        ].join(' ');

        const searchText = fullName + ' ' + address;

        const matchStatus = (statusValue === 'ALL' || rowStatus === statusValue);
        const matchName   = searchText.includes(nameValue);

        return matchStatus && matchName;
    });
}

function updateTable() {
    const visibleRows = getVisibleRows();

    // Update counts
    totalFilteredEl.textContent = visibleRows.length;
    showingCount.textContent = Math.min(visibleRows.length, perPage);

    // Hide all rows
    rows.forEach(r => r.style.display = 'none');

    // Show current page
    const start = (currentPage - 1) * perPage;
    const end   = start + perPage;
    const pageRows = visibleRows.slice(start, end);

    pageRows.forEach(row => row.style.display = '');

    // No results message
    noResults.style.display = visibleRows.length === 0 ? '' : 'none';

    // Pagination logic
    const totalPages = Math.ceil(visibleRows.length / perPage) || 1;
    currentPage = Math.min(currentPage, totalPages); // clamp

    prevBtn.disabled = currentPage <= 1;
    nextBtn.disabled = currentPage >= totalPages || totalPages === 0;

    // Page numbers
    pageNumbers.innerHTML = '';
    for (let i = 1; i <= totalPages; i++) {
        const btn = document.createElement('button');
        btn.textContent = i;
        btn.className = `px-3 py-1 rounded-lg text-sm font-medium ${
            i === currentPage 
                ? 'bg-green-600 text-white' 
                : 'bg-gray-200 hover:bg-gray-300 text-gray-700'
        }`;
        btn.onclick = () => {
            currentPage = i;
            updateTable();
        };
        pageNumbers.appendChild(btn);
    }
}

// Event listeners
statusFilter.addEventListener('change', () => { currentPage = 1; updateTable(); });
nameFilter.addEventListener('input',   () => { currentPage = 1; updateTable(); });

clearBtn.addEventListener('click', () => {
    statusFilter.value = 'ALL';
    nameFilter.value = '';
    currentPage = 1;
    updateTable();
});

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

// Initial load
updateTable();
</script>

<?php include '../includes/footer.php'; ?>