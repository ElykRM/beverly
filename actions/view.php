<?php
include '../includes/auth.php';
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

$mstmt = $pdo->prepare("SELECT * FROM household_members WHERE household_id = ? ORDER BY last_name, first_name");
$mstmt->execute([$id]);
$members = $mstmt->fetchAll();

// Fetch payments (exclude soft-deleted)
$pstmt = $pdo->prepare("
    SELECT * FROM payments 
    WHERE household_id = ? AND deleted_at IS NULL 
    ORDER BY paid_at DESC
");
$pstmt->execute([$id]);
$payments = $pstmt->fetchAll();

$age = null;
if ($household['birthday']) {
    $birthDate = new DateTime($household['birthday']);
    $today = new DateTime();
    $age = $today->diff($birthDate)->y;
}

$success_msg = $_GET['msg'] ?? '';
?>

<div class="mb-10">
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
        <h2 class="text-3xl font-bold text-green-800">Household Details</h2>
        <a href="../pages/habitants.php" class="text-green-700 hover:text-green-900 font-medium">&larr; Back to List</a>
    </div>
</div>

<?php if ($success_msg): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-r-lg">
        <?= htmlspecialchars($success_msg) ?>
    </div>
<?php endif; ?>

<div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden mb-10">
    <div class="p-8">
        <!-- Primary Member -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-10">
            <div>
                <label class="block text-sm font-medium text-gray-500">Primary Member Name</label>
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
                    <?= htmlspecialchars($household['subdivision'] ?? '') ?>
                </p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-500">Birthday / Age / Gender</label>
                <p class="mt-1 text-lg">
                    <?= $household['birthday'] ? date('M d, Y', strtotime($household['birthday'])) : '-' ?> 
                    (Age: <?= $age ?: '-' ?>) 
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

        <!-- Additional Members – Compact -->
        <div class="border-t border-gray-200 pt-8 mb-8">
            <h3 class="text-xl font-semibold text-green-800 mb-4">Other Household Members (<?= count($members) ?>)</h3>
            
            <?php if (empty($members)): ?>
                <p class="text-gray-500 italic">No additional members registered</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sub-member name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Relation</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Birthday / Gender</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Contact No.</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Occupation</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($members as $m): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap font-medium">
                                        <?= htmlspecialchars($m['last_name'] . ', ' . $m['first_name'] . ' ' . $m['middle_name']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($m['relation']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?= $m['birthday'] ? date('M d, Y', strtotime($m['birthday'])) : '-' ?>
                                        / <?= htmlspecialchars($m['gender'] ?: '-') ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($m['contact_no'] ?: '-') ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($m['occupation'] ?: '-') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Vehicles -->
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

<!-- Payment History with Delete -->
<div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden mb-10">
    <div class="p-8">
        <h3 class="text-xl font-semibold text-green-800 mb-4">Payment History</h3>
        
        <?php if (empty($payments)): ?>
            <p class="text-gray-500 italic">No payments recorded yet.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">OR No.</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Period</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date Paid</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Remarks</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($payments as $p): ?>
                            <?php
                            $period = sprintf("%d-%02d", $p['period_year'], $p['period_month']);
                            if ($p['period_to_year'] && $p['period_to_month']) {
                                $period .= sprintf(" to %d-%02d", $p['period_to_year'], $p['period_to_month']);
                            }
                            ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($p['or_no'] ?: '-') ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($period) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-right font-medium text-green-700">
                                    ₱<?= number_format($p['amount'], 2) ?>
                                    <?php if ($p['is_promo']): ?>
                                        <span class="text-xs text-purple-600 font-bold ml-2">Promo</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap"><?= date('M d, Y h:i A', strtotime($p['paid_at'])) ?></td>
                                <td class="px-6 py-4"><?= htmlspecialchars($p['remarks'] ?: '-') ?></td>
                                <td class="px-6 py-4 text-center">
                                    <?php if (is_admin()): ?>
                                    <form action="../actions/delete_payment.php" method="POST" 
                                          data-confirm-message="Are you sure you want to delete this payment record? This cannot be undone.">
                                        <input type="hidden" name="payment_id" value="<?= $p['id'] ?>">
                                        <input type="hidden" name="household_id" value="<?= $id ?>">
                                        <button type="submit" class="text-red-600 hover:text-red-800 font-medium text-sm">
                                            Delete
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="mt-8 flex flex-wrap justify-center gap-4">
    <?php if (is_admin()): ?>
    <a href="../actions/edit.php?id=<?= $id ?>" class="inline-block bg-yellow-600 hover:bg-yellow-700 text-white font-medium py-3 px-8 rounded-lg shadow transition">
        Edit Household
    </a>
    <a href="../pages/payment.php?household_id=<?= $id ?>" class="inline-block bg-green-600 hover:bg-green-700 text-white font-medium py-3 px-8 rounded-lg shadow transition">
        Log Payment
    </a>
    <?php endif; ?>
    <a href="../pages/dues.php?household_id=<?= $id ?>" class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-8 rounded-lg shadow transition">
        View Dues History
    </a>
    <?php if (is_admin()): ?>
    <form action="../actions/delete.php" method="POST" data-confirm-message="Are you sure you want to delete this household record? This action cannot be undone." class="inline-block">
        <input type="hidden" name="id" value="<?= $id ?>">
        <button type="submit" class="inline-block bg-red-600 hover:bg-red-700 text-white font-medium py-3 px-8 rounded-lg shadow transition">
            Delete Household
        </button>
    </form>
    <?php endif; ?>
</div>

<script>
// Modal popup for delete confirmations
function showDeleteConfirmModal(message, formElement) {
    const existing = document.getElementById('modal-overlay');
    if (existing) existing.remove();

    const overlay = document.createElement('div');
    overlay.id = 'modal-overlay';
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
        <h3 class="text-lg font-bold text-gray-800 mb-3">Confirm Delete</h3>
        <p class="text-sm text-gray-700 mb-6"></p>
        <div style="display: flex; justify-content: center; gap: 0.75rem; flex-wrap: wrap;">
            <button type="button" class="modal-confirm" style="background-color: #b91c1c; color: white; font-weight: 500; padding: 0.5rem 1.25rem; border-radius: 0.5rem; border: none; cursor: pointer;">Delete</button>
            <button type="button" class="modal-cancel" style="background-color: #6b7280; color: white; font-weight: 500; padding: 0.5rem 1.25rem; border-radius: 0.5rem; border: none; cursor: pointer;">Cancel</button>
        </div>
    `;

    const messageEl = box.querySelector('p');
    messageEl.textContent = message;
    Object.assign(messageEl.style, {
        whiteSpace: 'pre-line',
        textAlign: 'left',
        lineHeight: '1.5'
    });
    const confirmBtn = box.querySelector('.modal-confirm');
    const cancelBtn = box.querySelector('.modal-cancel');

    confirmBtn.addEventListener('click', () => {
        overlay.remove();
        formElement.submit();
    });

    cancelBtn.addEventListener('click', () => overlay.remove());
    overlay.addEventListener('click', (ev) => {
        if (ev.target === overlay) overlay.remove();
    });

    overlay.appendChild(box);
    document.body.appendChild(overlay);
    confirmBtn.focus();
}

// Handle delete confirmation forms
document.querySelectorAll('form[action*="delete"]').forEach(form => {
    form.addEventListener('submit', (e) => {
        e.preventDefault();
        const message = form.getAttribute('data-confirm-message') || 'Are you sure?';
        showDeleteConfirmModal(message, form);
    });
});
</script>

<?php include '../includes/footer.php'; ?>