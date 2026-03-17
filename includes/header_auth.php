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
        <div class="max-w-7xl mx-auto px-6 py-6 flex items-center justify-center">
            <div class="flex items-center gap-4">
                <img src="../images/HOA.png" alt="Beverly Homes HOA Icon"
                     class="w-12 h-12 md:w-16 md:h-16 object-contain rounded-full">

                <div class="text-center md:text-left">
                    <h1 class="text-3xl md:text-4xl font-bold">Beverly Homes Phase 1</h1>
                    <p class="mt-1 text-lg opacity-90">Household Records Management System</p>
                    <p class="mt-1 text-sm">Barangay Hugo Perez, Trece Martires City, Cavite</p>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-6 py-10 flex-grow">