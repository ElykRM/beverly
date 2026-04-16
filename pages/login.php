<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = $_GET['error'] ?? '';
$success = $_GET['success'] ?? '';

include '../includes/header_auth.php';
?>

<div style="width: 450px; margin: 0 auto;">
    <div class="bg-white rounded-xl shadow-md border border-gray-200 overflow-hidden">
        <div class="px-6 py-6">
            <h2 class="text-center text-2xl font-bold text-gray-800 mb-1">Log In</h2>
            <p class="text-center text-xs text-gray-500 mb-5">Log In to your Account</p>

            <?php if ($error): ?>
                <div class="mb-5 bg-red-50 border-l-4 border-red-500 text-red-700 p-3 rounded-r text-sm">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="mb-5 bg-green-50 border-l-4 border-green-500 text-green-700 p-3 rounded-r text-sm">
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <form action="../actions/login.php" method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Username</label>
                    <input
                        id="username"
                        type="text"
                        name="username"
                        required
                        autofocus
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-white text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-green-700 focus:border-transparent"
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Password</label>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        required
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-white text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-green-700 focus:border-transparent"
                    >
                </div>

                <button
                    type="submit"
                    class="w-full bg-green-800 hover:bg-green-900 text-white font-semibold uppercase tracking-wide py-2.5 rounded-sm transition-colors mt-6 mb-1.5"
                >
                    Log In
                </button>
            </form>

            <p class="text-center text-sm text-gray-600 mt-5">
                No account yet?
                <a href="register.php" class="text-green-700 hover:text-green-900 font-semibold">Register here</a>
            </p>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
