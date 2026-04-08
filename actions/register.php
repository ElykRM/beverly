<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/register.php');
    exit;
}

require_once '../db.php';

$username         = trim($_POST['username'] ?? '');
$password         = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// Validation
if ($username === '' || $password === '' || $confirm_password === '') {
    header('Location: ../pages/register.php?error=' . urlencode('Please fill in all fields.'));
    exit;
}

if (strlen($username) > 50) {
    header('Location: ../pages/register.php?error=' . urlencode('Username must be 50 characters or less.'));
    exit;
}

if (strlen($password) < 8) {
    header('Location: ../pages/register.php?error=' . urlencode('Password must be at least 8 characters.'));
    exit;
}

if ($password !== $confirm_password) {
    header('Location: ../pages/register.php?error=' . urlencode('Passwords do not match.'));
    exit;
}

// Check for existing username
$check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
$check->execute([$username]);
if ($check->fetch()) {
    header('Location: ../pages/register.php?error=' . urlencode('Username already taken. Please choose another.'));
    exit;
}

// Insert new user
$hash = password_hash($password, PASSWORD_BCRYPT);
$stmt = $pdo->prepare("INSERT INTO users (username, password_hash) VALUES (?, ?)");
$stmt->execute([$username, $hash]);

header('Location: ../pages/login.php?success=' . urlencode('Account created! You can now sign in.'));
exit;
