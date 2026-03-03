<?php
include '../db.php';
include '../includes/header.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<div class='text-red-600 text-center text-xl my-10'>Invalid household ID</div>";
    include '../includes/footer.php';
    exit;
}

$id = (int)$_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM households WHERE id = ?");
$stmt->execute([$id]);
$household = $stmt->fetch();

if (!$household) {
    echo "<div class='text-red-600 text-center text-xl my-10'>Household not found</div>";
    include '../includes/footer.php';
    exit;
}

$vstmt = $pdo->prepare("SELECT * FROM vehicles WHERE household_id = ? ORDER BY id");
$vstmt->execute([$id]);
$vehicles = $vstmt->fetchAll();

$success_msg = $_GET['msg'] ?? '';
?>

<div class="mb-10">
    <div class="flex justify-between items-center">
        <h2 class="text-3xl font-bold text-green-800">Household Details</h2>
        <a href="habitants.php" class="text-green-700 hover:text-green-900 font-medium">&larr; Back to List</a>
    </div>
</div>

<?php if ($success_msg): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-r-lg">
        <?= htmlspecialchars($success_msg) ?>
    </div>
<?php endif; ?>

<div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
    <div class="p-8">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-10">
            <div>
                <label class="block text-sm font-medium text-gray-500">Name</label>
                <p class="mt-1 text-lg font-semibold">
                    <?= htmlspecialchars($household['last_name'] . ', ' . $household['first_name'] . ' ' . ($household['middle_name'] ?? '')) ?>
                </p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-500">Home Status</label>
                <p class="mt-1 text-lg"><?= htmlspecialchars($household['home_status']) ?></p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-500">Address</label>
                <p class="mt-1 text-lg">
                    <?= htmlspecialchars(($household['block'] ? 'Block ' . $household['block'] : '') . 
                                       ($household['lot'] ? ' Lot ' . $household['lot'] : '') . 
                                       ($household['street'] ? ' ' . $household['street'] : '')) ?><br>
                    <?= htmlspecialchars($household['subdivision']) ?>
                </p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-500">Birthday / Age / Gender</label>
                <p class="mt-1 text-lg">
                    <?= $household['birthday'] ? date('M d, Y', strtotime($household['birthday'])) : '-' ?> 
                    (Age: <?= $household['age'] ?: '-' ?>) 
                    <?= $household['gender'] ?: '-' ?>
                </p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-500">Contact / Occupation</label>
                <p class="mt-1 text-lg"><?= htmlspecialchars($household['contact_no'] ?: '-') ?> / <?= htmlspecialchars($household['occupation'] ?: '-') ?></p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-500">No. of Pets</label>
                <p class="mt-1 text-lg"><?= $household['num_pets'] ?></p>
            </div>
        </div>

        <div class="border-t border-gray-200 pt-8">
            <h3 class="text-xl font-semibold text-green-800 mb-4">Vehicles (<?= count($vehicles) ?>)</h3>
            
            <?php if (empty($vehicles)): ?>
                <p class="text-gray-500 italic">No vehicles registered</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Brand</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type/Model</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Color</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Plate No.</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($vehicles as $v): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($v['brand'] ?: '-') ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($v['type_model'] ?: '-') ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($v['color'] ?: '-') ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap font-medium"><?= htmlspecialchars($v['plate_no'] ?: '-') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="mt-8 text-center space-x-4">
    <a href="edit.php?id=<?= $id ?>" class="inline-block bg-yellow-600 hover:bg-yellow-700 text-white font-medium py-3 px-8 rounded-lg shadow transition">
        Edit Household
    </a>
    <a href="../pages/payment.php?household_id=<?= $id ?>" class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-8 rounded-lg shadow transition">
        Log Payment
    </a>
</div>

<div class="mt-4 text-center">
    <form action="../actions/delete.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this household record? This action cannot be undone.');">
        <input type="hidden" name="id" value="<?= $id ?>">
        <button type="submit" class="inline-block bg-red-600 hover:bg-red-700 text-white font-medium py-3 px-8 rounded-lg shadow transition">
            Delete Household
        </button>
    </form>
</div>

<?php include '../includes/footer.php'; ?>