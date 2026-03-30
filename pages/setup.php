<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../db.php';

// Define the secret setup code (change this to something secure)
define('SETUP_CODE', 'BEVERLY_ADMIN_2026');

// Check if setup is already complete
$check_setup = $pdo->query("SELECT COUNT(*) as count FROM users WHERE is_setup_complete = 1");
$setup_status = $check_setup->fetch()['count'];

if ($setup_status > 0) {
    $error = 'Admin setup has already been completed. This page is no longer available.';
}

$error = $error ?? null;
$success = $_GET['success'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username         = trim($_POST['username'] ?? '');
    $password         = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $setup_code       = trim($_POST['setup_code'] ?? '');

    // Validation
    if ($username === '' || $password === '' || $confirm_password === '' || $setup_code === '') {
        $error = 'Please fill in all fields.';
    } elseif ($setup_code !== SETUP_CODE) {
        $error = 'Invalid setup code.';
    } elseif (strlen($username) > 50) {
        $error = 'Username must be 50 characters or less.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif ($setup_status > 0) {
        $error = 'Admin setup has already been completed.';
    } else {
        // Check for existing username
        $check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $check->execute([$username]);
        if ($check->fetch()) {
            $error = 'Username already taken. Please choose another.';
        } else {
            // Create the admin account and mark setup as complete
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role, is_setup_complete) VALUES (?, ?, 'admin', 1)");
            
            try {
                $stmt->execute([$username, $hash]);
                // Update the flag to prevent further setup
                $pdo->query("UPDATE users SET is_setup_complete = 1 WHERE is_setup_complete = 0");
                header('Location: ../pages/setup.php?success=Admin account created successfully! You can now login.');
                exit;
            } catch (Exception $e) {
                $error = 'An error occurred while creating the admin account.';
            }
        }
    }
}

include '../includes/header_auth.php';
?>

<div style="width: 450px; margin: 0 auto;">
    <div class="bg-white rounded-xl shadow-md border border-gray-200 overflow-hidden">
        <div class="px-6 py-6">
            <h2 class="text-center text-2xl font-bold text-gray-800 mb-1">Admin Setup</h2>
            <p class="text-center text-xs text-gray-500 mb-5">Create your first administrator account</p>

            <?php if ($error): ?>
                <div class="mb-5 bg-red-50 border-l-4 border-red-500 text-red-700 p-3 rounded-r text-sm">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="mb-5 bg-green-50 border-l-4 border-green-500 text-green-700 p-3 rounded-r text-sm">
                    <?= htmlspecialchars($success) ?>
                </div>
                <a href="../pages/login.php" class="w-full block bg-green-800 hover:bg-green-900 text-white font-semibold uppercase tracking-wide py-2.5 rounded-sm transition-colors text-center">
                    Go to Login
                </a>
            <?php else: ?>
                <form method="POST" class="space-y-4">
                    <div>
                        <label for="setup_code" class="block text-sm font-medium text-gray-700 mb-1.5">Setup Code</label>
                        <input 
                            type="password" 
                            id="setup_code" 
                            name="setup_code" 
                            required
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-white text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-green-700 focus:border-transparent"
                            placeholder="Enter setup code"
                        >
                    </div>

                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700 mb-1.5">Admin Username</label>
                        <input 
                            type="text" 
                            id="username" 
                            name="username" 
                            required
                            autofocus
                            maxlength="50"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-white text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-green-700 focus:border-transparent"
                            placeholder="Enter username"
                        >
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1.5">Password</label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            required
                            minlength="8"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-white text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-green-700 focus:border-transparent"
                            placeholder="Minimum 8 characters"
                        >
                    </div>

                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1.5">Confirm Password</label>
                        <input 
                            type="password" 
                            id="confirm_password" 
                            name="confirm_password" 
                            required
                            minlength="8"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-white text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-green-700 focus:border-transparent"
                            placeholder="Confirm password"
                        >
                    </div>

                    <button 
                        type="submit" 
                        class="w-full bg-green-800 hover:bg-green-900 text-white font-semibold uppercase tracking-wide py-2.5 rounded-sm transition-colors mt-6"
                    >
                        Create Admin Account
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>