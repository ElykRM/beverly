<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied - Beverly Homes</title>
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
            <div class="bg-white rounded-xl shadow-md border border-gray-200 p-6 text-center">
                <div class="mb-4">
                    <svg class="w-16 h-16 mx-auto text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4v.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-800 mb-2">Access Denied</h2>
                <p class="text-gray-600 mb-6 text-sm">
                    You don't have permission to access this page. Only administrators can access this area.
                </p>
                <a href="../pages/index.php" class="inline-block bg-green-800 hover:bg-green-900 text-white font-semibold py-2.5 px-6 rounded transition">
                    Go to Dashboard
                </a>
            </div>
        </div>
    </main>

    <footer class="bg-gray-800 text-white text-center py-6">
        <p>© <?php echo date('Y'); ?> Beverly Homes Phase 1 HOA. All rights reserved.</p>
    </footer>
</body>
</html>
