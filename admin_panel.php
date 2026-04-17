<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../includes/auth.php';
require_once '../db.php';

// Admin-only page
require_admin();

$success = $_GET['success'] ?? null;
$error = $_GET['error'] ?? null;

// Get all users
$users_stmt = $pdo->query("SELECT id, username, role, created_at FROM users ORDER BY created_at DESC");
$users = $users_stmt->fetchAll();

// Handle role update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $user_id = intval($_POST['user_id'] ?? 0);
    $new_role = $_POST['new_role'] ?? '';

    if ($action === 'update_role' && $user_id > 0 && in_array($new_role, ['admin', 'viewer'])) {
        // Prevent self-demotion
        if ($user_id === $_SESSION['user_id'] && $new_role === 'viewer') {
            $error = 'You cannot demote yourself from admin. Ask another admin to change your role.';
        } else {
            $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
            $stmt->execute([$new_role, $user_id]);
            $success = 'User role updated successfully.';
            header('Location: ../pages/admin_panel.php?success=' . urlencode($success));
            exit;
        }
    }

    if ($action === 'delete_user' && $user_id > 0) {
        // Prevent self-deletion
        if ($user_id === $_SESSION['user_id']) {
            $error = 'You cannot delete your own account.';
        } else {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $success = 'User deleted successfully.';
            header('Location: ../pages/admin_panel.php?success=' . urlencode($success));
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Beverly Homes</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 flex flex-col min-h-screen">
    <?php include '../includes/header.php'; ?>

    <div class="flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Admin Panel</h1>
            <p class="text-gray-600 mt-1">Manage users and roles</p>
        </div>

        <?php if ($success): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded mb-6">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-6">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Username</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Current Role</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Created</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <?= htmlspecialchars($user['username']) ?>
                                <?php if ($user['id'] === $_SESSION['user_id']): ?>
                                    <span class="ml-2 text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">You</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span class="inline-flex px-3 py-1 rounded-full text-sm font-medium <?= $user['role'] === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800' ?>">
                                    <?= ucfirst($user['role']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?= date('M d, Y', strtotime($user['created_at'])) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm space-x-3">
                                <form method="POST" class="inline">
                                    <input type="hidden" name="action" value="update_role">
                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                    <select name="new_role" class="px-2 py-1 border border-gray-300 rounded text-sm" onchange="this.form.submit()">
                                        <option value="">Change role...</option>
                                        <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Make Admin</option>
                                        <option value="viewer" <?= $user['role'] === 'viewer' ? 'selected' : '' ?>>Make Viewer</option>
                                    </select>
                                </form>

                                <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                    <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this user?')">
                                        <input type="hidden" name="action" value="delete_user">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <button type="submit" class="text-red-600 hover:text-red-900 text-sm font-medium">Delete</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-6">
            <h2 class="text-lg font-medium text-blue-900 mb-2">Need to create a new admin?</h2>
            <p class="text-blue-800">
                1. Create a new user account via registration (they'll be created as a viewer)
                <br>2. Use the dropdown above to promote them to admin
            </p>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
