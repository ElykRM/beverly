<?php
include '../db.php';
include '../includes/header.php';

// Handle search/filter
$status_filter = $_GET['status'] ?? 'ALL';
$name_search   = trim($_GET['name'] ?? '');

$query = "SELECT * FROM households WHERE 1=1";
$params = [];

if ($status_filter !== 'ALL') {
    $query .= " AND home_status = :status";
    $params[':status'] = $status_filter;
}

if ($name_search !== '') {
    $query .= " AND (last_name LIKE :name OR first_name LIKE :name OR middle_name LIKE :name)";
    $params[':name'] = "%$name_search%";
}

$query .= " ORDER BY last_name ASC, first_name ASC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$households = $stmt->fetchAll();

// Success message from add/edit/delete
$success_msg = $_GET['msg'] ?? '';
?>

<div class="mb-8">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-3xl font-bold text-green-800">Habitants / Residents</h2>
        <a href="../actions/add.php" class="bg-green-700 hover:bg-green-800 text-white font-medium py-2 px-6 rounded-lg shadow transition">
            + New Household
        </a>
    </div>

    <?php if ($success_msg): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-r-lg">
            <?= htmlspecialchars($success_msg) ?>
        </div>
    <?php endif; ?>

    <form method="GET" class="bg-white p-6 rounded-xl shadow-md border border-gray-200 mb-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Home Status</label>
                <select name="status" id="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
                    <option value="ALL" <?= $status_filter === 'ALL' ? 'selected' : '' ?>>ALL</option>
                    <option value="Owner" <?= $status_filter === 'Owner' ? 'selected' : '' ?>>Owner</option>
                    <option value="Renter" <?= $status_filter === 'Renter' ? 'selected' : '' ?>>Renter</option>
                    <option value="Member" <?= $status_filter === 'Member' ? 'selected' : '' ?>>Member</option>
                </select>
            </div>

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Search Name</label>
                <input type="text" name="name" id="name" value="<?= htmlspecialchars($name_search) ?>" 
                       placeholder="e.g. John or Doe" 
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
            </div>

            <div class="flex items-end gap-4">
                <button type="submit" class="bg-green-700 hover:bg-green-800 text-white font-medium py-2 px-6 rounded-lg transition">
                    Search
                </button>
                <a href="habitants.php" class="text-green-700 hover:text-green-900 underline">Clear Filters</a>
            </div>
        </div>
    </form>
</div>

<div class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-200">
    <?php if (empty($households)): ?>
        <div class="p-12 text-center text-gray-500">
            No records found. Try adjusting your filters or add a new household.
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Name</th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">First Name</th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Middle Name</th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Block</th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lot</th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Street</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($households as $h): ?>
                        <tr class="hover:bg-gray-50 transition-colors cursor-pointer" 
                            onclick="window.location.href='../actions/view.php?id=<?= $h['id'] ?>'">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <?= htmlspecialchars($h['last_name']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?= htmlspecialchars($h['first_name']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?= htmlspecialchars($h['middle_name'] ?? '-') ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?= htmlspecialchars($h['block'] ?? '-') ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?= htmlspecialchars($h['lot'] ?? '-') ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?= htmlspecialchars($h['street'] ?? '-') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>