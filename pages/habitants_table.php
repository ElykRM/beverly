<?php
include '../includes/auth.php';
include '../db.php';

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
?>

<?php if (empty($households)): ?>
    <div class="p-12 text-center text-gray-500">
        No records found. Try adjusting your filters.
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
                        onclick="window.location='../actions/view.php?id=<?= $h['id'] ?>'">
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