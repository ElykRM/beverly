<?php
include '../db.php';
include '../includes/header.php';

$status_filter = $_GET['status'] ?? 'ALL';
$block_filter  = trim($_GET['block'] ?? '');
$lot_filter    = trim($_GET['lot'] ?? '');

$query = "SELECT * FROM households WHERE 1=1";
$params = [];

if ($status_filter !== 'ALL') {
    $query .= " AND home_status = :status";
    $params[':status'] = $status_filter;
}

if ($block_filter !== '') {
    $query .= " AND block = :block";
    $params[':block'] = $block_filter;
}

if ($lot_filter !== '') {
    $query .= " AND lot = :lot";
    $params[':lot'] = $lot_filter;
}

$query .= " ORDER BY last_name ASC, first_name ASC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$households = $stmt->fetchAll();

// Summary stats
$total = count($households);
$owners = 0; $renters = 0; $members = 0;
foreach ($households as $h) {
    if ($h['home_status'] === 'Owner') $owners++;
    if ($h['home_status'] === 'Renter') $renters++;
    if ($h['home_status'] === 'Member') $members++;
}
?>

<div class="mb-10">
    <h2 class="text-3xl font-bold text-green-800 mb-2">Reports</h2>
    <p class="text-gray-600 mb-6">Filter and view household statistics</p>
</div>

<form method="GET" class="bg-white p-6 rounded-xl shadow-md border border-gray-200 mb-8">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div>
            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <select name="status" id="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                <option value="ALL" <?= $status_filter === 'ALL' ? 'selected' : '' ?>>All</option>
                <option value="Owner" <?= $status_filter === 'Owner' ? 'selected' : '' ?>>Owner</option>
                <option value="Renter" <?= $status_filter === 'Renter' ? 'selected' : '' ?>>Renter</option>
                <option value="Member" <?= $status_filter === 'Member' ? 'selected' : '' ?>>Member</option>
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
        <div class="flex items-end gap-4">
            <button type="submit" class="bg-green-700 hover:bg-green-800 text-white font-medium py-2 px-6 rounded-lg transition">
                Filter
            </button>
            <a href="reports.php" class="text-green-700 hover:text-green-900 underline">Clear</a>
        </div>
    </div>
</form>

<!-- Summary Stats -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
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
</div>

<!-- Results Table -->
<?php if (empty($households)): ?>
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-6 rounded-r-lg">
        No households match the current filters.
    </div>
<?php else: ?>
    <div class="overflow-x-auto bg-white rounded-xl shadow-md border border-gray-200">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase">Block</th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase">Lot</th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase">Street</th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase">Contact</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php foreach ($households as $h): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap font-medium">
                            <?= htmlspecialchars($h['first_name'] . ' ' . $h['last_name']) ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($h['home_status']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($h['block'] ?? '-') ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($h['lot'] ?? '-') ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($h['street'] ?? '-') ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($h['contact_no'] ?? '-') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Export CSV Button -->
    <div class="mt-6 text-right">
        <a href="#" onclick="exportCSV()" class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-8 rounded-lg shadow transition">
            Export to CSV
        </a>
    </div>

    <script>
    function exportCSV() {
        const data = <?= json_encode($households) ?>;
        let csv = 'Name,Status,Block,Lot,Street,Contact\n';
        data.forEach(row => {
            csv += `"${row.first_name} ${row.last_name}","${row.home_status}","${row.block || ''}","${row.lot || ''}","${row.street || ''}","${row.contact_no || ''}"\n`;
        });

        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'households_report.csv';
        link.click();
    }
    </script>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>