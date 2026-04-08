<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beverly Homes Phase 1 - Records System</title>
    
    <link rel="icon" type="image/png" href="../images/HOA.png" sizes="32x32">
    <link rel="icon" type="image/png" href="../images/HOA.png" sizes="64x64">
    <link rel="apple-touch-icon" href="../images/HOA.png" sizes="180x180">
    
    <link href="../assets/css/tailwind.css" rel="stylesheet">
</head>
<body class="bg-gray-50 min-h-screen font-sans antialiased flex flex-col">

    <header class="bg-green-800 text-white shadow-md relative">
        <div class="max-w-7xl mx-auto px-6 py-6 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <img src="../images/HOA.png" alt="Beverly Homes HOA Icon" 
                     class="w-12 h-12 md:w-16 md:h-16 object-contain rounded-full">
                
                <div class="text-center md:text-left">
                    <h1 class="text-3xl md:text-4xl font-bold">Beverly Homes Phase 1</h1>
                    <p class="mt-1 text-lg opacity-90">Household Records Management System</p>
                    <p class="mt-1 text-sm">Barangay Hugo Perez, Trece Martires City, Cavite</p>
                </div>
            </div>

            <!-- Main Menu and User Menu - Right Side -->
            <div class="flex items-center gap-4">
                <a href="../pages/index.php" 
                   class="flex items-center gap-2 bg-white text-green-800 hover:bg-gray-100 hover:scale-105 font-medium py-2 px-5 rounded-lg shadow transition-all">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" />
                    </svg>
                    <span>Main Menu</span>
                </a>

                <!-- User Menu Dropdown -->
                <?php if (isset($_SESSION['user_id'])): ?>
                <div class="relative">
                    <button
                        onclick="toggleUserMenu()"
                        class="flex items-center justify-center w-12 h-10 border-white rounded-xl text-white hover:opacity-80 transition-opacity focus:outline-none"
                        title="User Menu">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-9 w-9" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/>
                        </svg>
                    </button>

                <!-- Dropdown Menu -->
                <div
                    id="userMenu"
                    class="hidden absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg border border-gray-200 z-50">
                    <div class="px-4 py-3 border-b border-gray-200">
                        <p class="text-sm text-gray-600">Logged in as:</p>
                        <p class="text-sm font-semibold text-gray-900">
                            <?= htmlspecialchars($_SESSION['username'] ?? '') ?>
                        </p>
                        <?php if (isset($_SESSION['role'])): ?>
                        <p class="text-xs text-gray-500 mt-1">
                            Role: <span class="font-medium"><?= ucfirst($_SESSION['role']) ?></span>
                        </p>
                        <?php endif; ?>
                    </div>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <a href="../pages/admin_panel.php"
                       class="block px-4 py-2 text-sm text-blue-600 hover:bg-blue-50 transition-colors font-medium">
                        Admin Panel
                    </a>
                    <?php endif; ?>
                    <a href="../actions/logout.php"
                       class="block px-4 py-3 text-sm text-red-600 hover:bg-red-50 font-medium border-t border-gray-200 transition-colors">
                        Logout
                    </a>
                </div>
                </div>
                <?php endif; ?>
            </div>

            <script>
                function toggleUserMenu() {
                    const menu = document.getElementById('userMenu');
                    menu.classList.toggle('hidden');
                }

                // Close menu when clicking outside
                document.addEventListener('click', function(event) {
                    const userMenu = document.getElementById('userMenu');
                    const button = event.target.closest('button[onclick="toggleUserMenu()"]');
                    if (!button && userMenu && !userMenu.classList.contains('hidden')) {
                        userMenu.classList.add('hidden');
                    }
                });
            </script>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-6 py-10 flex-grow">