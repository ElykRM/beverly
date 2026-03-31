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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beverly Homes - Login</title>
    <link href="../assets/css/tailwind.css" rel="stylesheet">
</head>
<body class="bg-gray-50 min-h-screen flex flex-col">
    <header class="bg-green-800 text-white shadow-md">
        <div class="max-w-7xl mx-auto px-6 py-6 text-center">
            <h1 class="text-3xl font-bold">Beverly Homes Phase 1</h1>
            <p class="mt-1 text-lg opacity-90">Household Records Management System</p>
        </div>
    </header>

    <main class="flex-grow flex items-center justify-center">
        <div style="width: 450px;">
            <div class="bg-white rounded-xl shadow-md border border-gray-200 p-6">
                <h2 class="text-center text-2xl font-bold text-gray-800 mb-1">Log In</h2>
                <p class="text-center text-xs text-gray-500 mb-5">Access your account</p>

                <?php if ($error): ?>
                    <div class="mb-5 bg-red-50 border-l-4 border-red-500 text-red-700 p-3 text-sm">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="mb-5 bg-green-50 border-l-4 border-green-500 text-green-700 p-3 text-sm">
                        <?= htmlspecialchars($success) ?>
                    </div>
                <?php endif; ?>

                <form action="../actions/login.php" method="POST" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Username</label>
                        <input type="text" name="username" required autofocus class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-700">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Password</label>
                        <input type="password" name="password" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-700">
                    </div>
                    <button type="submit" class="w-full bg-green-800 hover:bg-green-900 text-white font-semibold py-2.5 rounded mt-6">
                        Log In
                    </button>
                </form>

                <p class="text-center text-sm text-gray-600 mt-5">
                    No account? <a href="register.php" class="text-green-700 hover:text-green-900 font-semibold">Register here</a>
                </p>
            </div>
        </div>
    </main>

    <footer class="bg-gray-800 text-white text-center py-6">
        <p>© <?php echo date('Y'); ?> Beverly Homes Phase 1 HOA. All rights reserved.</p>
    </footer>
</body>
</html>
