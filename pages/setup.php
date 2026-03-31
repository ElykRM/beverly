<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Minimal setup without complex includes
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Direct database connection
try {
    $pdo = new PDO(
        "mysql:host=sql208.infinityfree.com;dbname=if0_41510481_beverly;charset=utf8mb4",
        'if0_41510481',
        'F1Iagq6Qs3NM0N'
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

define('SETUP_CODE', 'BEVERLY_ADMIN_2026');

// Check if setup is already complete
try {
    $check_setup = $pdo->query("SELECT COUNT(*) as count FROM users WHERE is_setup_complete = 1");
    $setup_status = $check_setup->fetch()['count'] ?? 0;
} catch (Exception $e) {
    $setup_status = 0;
}

$error = null;
$success = $_GET['success'] ?? null;

if ($setup_status > 0) {
    $error = 'Admin setup has already been completed. This page is no longer available.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username         = trim($_POST['username'] ?? '');
    $password         = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $setup_code       = trim($_POST['setup_code'] ?? '');

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
        $check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $check->execute([$username]);
        if ($check->fetch()) {
            $error = 'Username already taken. Please choose another.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role, is_setup_complete) VALUES (?, ?, 'admin', 1)");
            
            try {
                $stmt->execute([$username, $hash]);
                $pdo->query("UPDATE users SET is_setup_complete = 1 WHERE is_setup_complete = 0");
                header('Location: login.php?success=Admin account created successfully!');
                exit;
            } catch (Exception $e) {
                $error = 'Database error: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beverly Homes - Admin Setup</title>
    <link href="../assets/css/tailwind.css" rel="stylesheet">
</head>
<body class="bg-gray-50 min-h-screen flex flex-col">
    <header class="bg-green-800 text-white shadow-md">
        <div class="max-w-7xl mx-auto px-6 py-6 text-center">
            <h1 class="text-3xl font-bold">Beverly Homes Phase 1</h1>
            <p class="mt-1 text-lg opacity-90">Admin Setup</p>
        </div>
    </header>

    <main class="flex-grow flex items-center justify-center">
        <div style="width: 450px;">
            <div class="bg-white rounded-xl shadow-md border border-gray-200 p-6">
                <h2 class="text-center text-2xl font-bold text-gray-800 mb-1">Create Admin Account</h2>
                <p class="text-center text-xs text-gray-500 mb-5">First time setup</p>

                <?php if ($error): ?>
                    <div class="mb-5 bg-red-50 border-l-4 border-red-500 text-red-700 p-3 text-sm">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="mb-5 bg-green-50 border-l-4 border-green-500 text-green-700 p-3 text-sm">
                        <?= htmlspecialchars($success) ?>
                    </div>
                    <a href="login.php" class="w-full block bg-green-800 hover:bg-green-900 text-white font-semibold py-2.5 rounded text-center">
                        Go to Login
                    </a>
                <?php else: ?>
                    <form method="POST" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Setup Code</label>
                            <input type="password" name="setup_code" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-700" placeholder="Enter setup code">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Admin Username</label>
                            <input type="text" name="username" required maxlength="50" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-700" placeholder="Enter username">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Password</label>
                            <input type="password" name="password" required minlength="8" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-700" placeholder="Minimum 8 characters">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Confirm Password</label>
                            <input type="password" name="confirm_password" required minlength="8" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-700" placeholder="Confirm password">
                        </div>
                        <button type="submit" class="w-full bg-green-800 hover:bg-green-900 text-white font-semibold py-2.5 rounded mt-6">
                            Create Admin Account
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer class="bg-gray-800 text-white text-center py-6">
        <p>© <?php echo date('Y'); ?> Beverly Homes Phase 1 HOA. All rights reserved.</p>
    </footer>
</body>
</html>