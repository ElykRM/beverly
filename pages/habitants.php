<?php
include '../db.php';
include '../includes/header.php';

// Fetch ALL households once (client-side filtering)
$stmt = $pdo->query("
    SELECT id, last_name, first_name, middle_name, home_status, 
           block, lot, street
    FROM households 
    ORDER BY last_name ASC, first_name ASC
");
$households = $stmt->fetchAll();

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
        <?php if (empty($households)): ?>
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
                        <?php foreach ($households as $h): ?>
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
        <?php endif; ?>
    </div>

    <!-- New Household button moved below the table -->
    <div class="text-center sm:text-right mt-6">
        <a href="../actions/add.php" class="inline-block bg-green-700 hover:bg-green-800 text-white font-medium py-3 px-8 rounded-lg shadow transition">
            + New Household
        </a>
    </div>
</div>

<script>
// Real-time filtering (status fixed)
const statusFilter = document.getElementById('status-filter');
const nameFilter   = document.getElementById('name-filter');
const clearBtn     = document.getElementById('clear-filters');
const rows         = document.querySelectorAll('.household-row');
const noResults    = document.getElementById('no-results');

function filterTable() {
    const statusValue = (statusFilter.value || 'ALL').toUpperCase().trim();
    const nameValue   = (nameFilter.value || '').toLowerCase().trim();

    let visibleCount = 0;

    rows.forEach(row => {
        const rowStatus = (row.dataset.status || '').toUpperCase().trim();
        const fullName  = [
            row.dataset.lastname   || '',
            row.dataset.firstname  || '',
            row.dataset.middlename || ''
        ].join(' ').toLowerCase().trim();

        const address = [
            row.querySelector('td:nth-child(5)')?.textContent || '',
            row.querySelector('td:nth-child(6)')?.textContent || '',
            row.querySelector('td:nth-child(7)')?.textContent || ''
        ].join(' ').toLowerCase().trim();

        const searchText = fullName + ' ' + address;

        const matchStatus = (statusValue === 'ALL' || rowStatus === statusValue);
        const matchName   = searchText.includes(nameValue);

        const shouldShow = matchStatus && matchName;

        row.style.display = shouldShow ? '' : 'none';

        if (shouldShow) visibleCount++;
    });

    if (noResults) {
        noResults.style.display = visibleCount === 0 ? '' : 'none';
    }
}

statusFilter.addEventListener('change', filterTable);
nameFilter.addEventListener('input',   filterTable);

clearBtn.addEventListener('click', () => {
    statusFilter.value = 'ALL';
    nameFilter.value   = '';
    filterTable();
});

filterTable();
</script>

<?php include '../includes/footer.php'; ?>