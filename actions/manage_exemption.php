<?php
include '../includes/auth.php';
include '../db.php';

// CORS headers for InfinityFree compatibility
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
header('Access-Control-Max-Age: 3600');

// Admin-only
require_admin();

function respondJson(int $statusCode, array $payload): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload);
    exit;
}

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    respondJson(200, ['success' => true]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respondJson(405, ['success' => false, 'error' => 'Method not allowed']);
}

try {
    $action = $_POST['action'] ?? '';
    $household_id = (int)($_POST['household_id'] ?? 0);
    $exemption_year = (int)($_POST['exemption_year'] ?? 0);
    $exemption_month = $_POST['exemption_month'] !== '' ? (int)$_POST['exemption_month'] : null;
    $exemption_to_year = $_POST['exemption_to_year'] !== '' ? (int)$_POST['exemption_to_year'] : null;
    $exemption_to_month = $_POST['exemption_to_month'] !== '' ? (int)$_POST['exemption_to_month'] : null;
    $reason = trim($_POST['reason'] ?? '');

    if ($household_id <= 0 || $exemption_year < 1900) {
        throw new Exception('Invalid household ID or year');
    }
    
    // Validate month values if present
    if ($exemption_month !== null && ($exemption_month < 1 || $exemption_month > 12)) {
        throw new Exception('Invalid month');
    }
    if ($exemption_to_month !== null && ($exemption_to_month < 1 || $exemption_to_month > 12)) {
        throw new Exception('Invalid end month');
    }

    if ($action === 'add') {
        // Check if exempt already exists
        $checkStmt = $pdo->prepare("
            SELECT id FROM exemptions 
            WHERE household_id = ? 
            AND exemption_year = ? 
            AND (exemption_month <=> ?)
            AND (exemption_to_year <=> ?)
            AND (exemption_to_month <=> ?)
        ");
        $checkStmt->execute([$household_id, $exemption_year, $exemption_month, $exemption_to_year, $exemption_to_month]);
        if ($checkStmt->fetch()) {
            throw new Exception("This exemption already exists");
        }

        // Insert exemption
        $insertStmt = $pdo->prepare("
            INSERT INTO exemptions 
            (household_id, exemption_year, exemption_month, exemption_to_year, exemption_to_month, reason, approved_date)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $insertStmt->execute([$household_id, $exemption_year, $exemption_month, $exemption_to_year, $exemption_to_month, $reason ?: null]);

        respondJson(200, ['success' => true, 'message' => "Exemption added"]);
    } 
    else if ($action === 'delete') {
        // Delete exemption
        $deleteStmt = $pdo->prepare("
            DELETE FROM exemptions
            WHERE household_id = ? 
            AND exemption_year = ?
            AND (exemption_month <=> ?)
            AND (exemption_to_year <=> ?)
            AND (exemption_to_month <=> ?)
        ");
        $deleteStmt->execute([$household_id, $exemption_year, $exemption_month, $exemption_to_year, $exemption_to_month]);

        if ($deleteStmt->rowCount() === 0) {
            throw new Exception("Exemption not found");
        }

        respondJson(200, ['success' => true, 'message' => "Exemption removed"]);
    }
    else {
        throw new Exception("Invalid action: $action");
    }

} catch (Exception $e) {
    respondJson(400, ['success' => false, 'error' => $e->getMessage()]);
}
