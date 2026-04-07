<?php
include '../includes/auth.php';
include '../db.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('Invalid household ID');
}

$id = (int)$_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM households WHERE id = ?");
$stmt->execute([$id]);
$household = $stmt->fetch();

if (!$household) {
    die('Household not found');
}

$vstmt = $pdo->prepare("SELECT * FROM vehicles WHERE household_id = ? ORDER BY id");
$vstmt->execute([$id]);
$vehicles = $vstmt->fetchAll();

$mstmt = $pdo->prepare("SELECT * FROM household_members WHERE household_id = ? ORDER BY last_name, first_name");
$mstmt->execute([$id]);
$members = $mstmt->fetchAll();

$pstmt = $pdo->prepare("
    SELECT * FROM payments 
    WHERE household_id = ? AND deleted_at IS NULL 
    ORDER BY paid_at DESC
");
$pstmt->execute([$id]);
$payments = $pstmt->fetchAll();

// Calculate age
$age = null;
if ($household['birthday']) {
    $birthDate = new DateTime($household['birthday']);
    $today = new DateTime();
    $age = $today->diff($birthDate)->y;
}

// Build list of paid months
$paidMonths = [];
foreach ($payments as $p) {
    $startY = (int)$p['period_year'];
    $startM = (int)$p['period_month'];
    $endY = $p['period_to_year'] !== null ? (int)$p['period_to_year'] : $startY;
    $endM = $p['period_to_month'] !== null ? (int)$p['period_to_month'] : $startM;
    
    for ($y = $startY; $y <= $endY; $y++) {
        $mFrom = ($y == $startY) ? $startM : 1;
        $mTo = ($y == $endY) ? $endM : 12;
        for ($m = $mFrom; $m <= $mTo; $m++) {
            $paidMonths["$y-$m"] = true;
        }
    }
}

// Generate HTML for PDF
$html = '
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11pt; line-height: 1.4; }
        h1 { font-size: 20pt; color: #1f7f1f; margin-bottom: 10pt; text-align: center; }
        h2 { font-size: 14pt; color: #1f7f1f; margin-top: 15pt; margin-bottom: 8pt; border-bottom: 2px solid #1f7f1f; padding-bottom: 4pt; }
        .section { margin-bottom: 15pt; }
        .info-row { margin-bottom: 6pt; }
        .info-label { font-weight: bold; color: #333; width: 30%; display: inline-block; }
        .info-value { color: #555; }
        table { width: 100%; border-collapse: collapse; margin-top: 8pt; }
        th { background-color: #f0f0f0; color: #333; font-weight: bold; padding: 6pt; border: 1px solid #ccc; text-align: left; font-size: 10pt; }
        td { padding: 6pt; border: 1px solid #ccc; font-size: 10pt; }
        .title-section { margin-bottom: 20pt; }
        .paid-badge { background-color: #d4edda; color: #155724; padding: 2pt 6pt; border-radius: 3pt; font-size: 9pt; }
    </style>
</head>
<body>
    <div class="title-section">
        <h1>Household Details Report</h1>
        <div style="text-align: center; color: #666; font-size: 10pt; margin-bottom: 15pt;">
            Generated on ' . date('F d, Y H:i A') . '
        </div>
    </div>

    <h2>Primary Member Information</h2>
    <div class="section">
        <div class="info-row">
            <span class="info-label">Name:</span>
            <span class="info-value">' . htmlspecialchars($household['last_name'] . ', ' . $household['first_name'] . ' ' . ($household['middle_name'] ?? '')) . '</span>
        </div>
        <div class="info-row">
            <span class="info-label">Home Status:</span>
            <span class="info-value">' . htmlspecialchars($household['home_status']) . '</span>
        </div>
        <div class="info-row">
            <span class="info-label">Birthday:</span>
            <span class="info-value">' . ($household['birthday'] ? date('M d, Y', strtotime($household['birthday'])) . ' (Age: ' . $age . ')' : '-') . '</span>
        </div>
        <div class="info-row">
            <span class="info-label">Gender:</span>
            <span class="info-value">' . htmlspecialchars($household['gender'] ?: '-') . '</span>
        </div>
        <div class="info-row">
            <span class="info-label">Contact No.:</span>
            <span class="info-value">' . htmlspecialchars($household['contact_no'] ?: '-') . '</span>
        </div>
        <div class="info-row">
            <span class="info-label">Occupation:</span>
            <span class="info-value">' . htmlspecialchars($household['occupation'] ?: '-') . '</span>
        </div>
        <div class="info-row">
            <span class="info-label">No. of Pets:</span>
            <span class="info-value">' . $household['num_pets'] . '</span>
        </div>
    </div>

    <h2>Address</h2>
    <div class="section">
        <div class="info-row">
            <span class="info-label">Block/Lot/Street:</span>
            <span class="info-value">' . htmlspecialchars(
                ($household['block'] ? 'Block ' . $household['block'] : '') . 
                ($household['lot'] ? ' Lot ' . $household['lot'] : '') . 
                ($household['street'] ? ' ' . $household['street'] : '')
            ) . '</span>
        </div>
        <div class="info-row">
            <span class="info-label">Subdivision:</span>
            <span class="info-value">' . htmlspecialchars($household['subdivision'] ?? 'Beverly Homes Subd. Brgy Hugo Perez Trece Martires Cavite') . '</span>
        </div>
    </div>

    <h2>Move-in/Move-out Dates</h2>
    <div class="section">
        <div class="info-row">
            <span class="info-label">Move-in Date:</span>
            <span class="info-value">' . ($household['move_in_date'] ? date('M d, Y', strtotime($household['move_in_date'])) : '-') . '</span>
        </div>
        <div class="info-row">
            <span class="info-label">Move-out Date:</span>
            <span class="info-value">' . ($household['move_out_date'] ? date('M d, Y', strtotime($household['move_out_date'])) : '<span class="paid-badge">Currently Living</span>') . '</span>
        </div>
    </div>
';

// Additional Members
if (!empty($members)) {
    $html .= '<h2>Additional Household Members (' . count($members) . ')</h2><div class="section">
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Relation</th>
                <th>Birthday</th>
                <th>Gender</th>
                <th>Contact</th>
            </tr>
        </thead>
        <tbody>';
    
    foreach ($members as $m) {
        $html .= '<tr>
            <td>' . htmlspecialchars($m['last_name'] . ', ' . $m['first_name'] . ' ' . ($m['middle_name'] ?? '')) . '</td>
            <td>' . htmlspecialchars($m['relation']) . '</td>
            <td>' . ($m['birthday'] ? date('M d, Y', strtotime($m['birthday'])) : '-') . '</td>
            <td>' . htmlspecialchars($m['gender'] ?: '-') . '</td>
            <td>' . htmlspecialchars($m['contact_no'] ?: '-') . '</td>
        </tr>';
    }
    $html .= '</tbody></table></div>';
}

// Vehicles
if (!empty($vehicles)) {
    $html .= '<h2>Vehicles (' . count($vehicles) . ')</h2><div class="section">
    <table>
        <thead>
            <tr>
                <th>Brand</th>
                <th>Type/Model</th>
                <th>Color</th>
                <th>Plate No.</th>
            </tr>
        </thead>
        <tbody>';
    
    foreach ($vehicles as $v) {
        $html .= '<tr>
            <td>' . htmlspecialchars($v['brand'] ?: '-') . '</td>
            <td>' . htmlspecialchars($v['type_model'] ?: '-') . '</td>
            <td>' . htmlspecialchars($v['color'] ?: '-') . '</td>
            <td><strong>' . htmlspecialchars($v['plate_no'] ?: '-') . '</strong></td>
        </tr>';
    }
    $html .= '</tbody></table></div>';
}

// Payment History
if (!empty($payments)) {
    $html .= '<h2>Payment History</h2><div class="section">
    <table>
        <thead>
            <tr>
                <th>OR No.</th>
                <th>Period</th>
                <th>Amount</th>
                <th>Date Paid</th>
                <th>Remarks</th>
            </tr>
        </thead>
        <tbody>';
    
    foreach ($payments as $p) {
        $period = sprintf("%d-%02d", $p['period_year'], $p['period_month']);
        if ($p['period_to_year'] && $p['period_to_month']) {
            $period .= sprintf(" to %d-%02d", $p['period_to_year'], $p['period_to_month']);
        }
        $html .= '<tr>
            <td>' . htmlspecialchars($p['or_no'] ?: '-') . '</td>
            <td>' . htmlspecialchars($period) . '</td>
            <td style="text-align:right;">₱' . number_format($p['amount'], 2) . '</td>
            <td>' . date('M d, Y', strtotime($p['paid_at'])) . '</td>
            <td>' . htmlspecialchars($p['remarks'] ?: '-') . '</td>
        </tr>';
    }
    $html .= '</tbody></table></div>';
}

$html .= '</body></html>';

// Generate PDF
require '../vendor/autoload.php';

$mpdf = new \Mpdf\Mpdf([
    'margin_left' => 12,
    'margin_right' => 12,
    'margin_top' => 15,
    'margin_bottom' => 15
]);

$mpdf->WriteHTML($html);

$filename = 'Household_' . preg_replace('/[^a-zA-Z0-9-]/', '', str_replace(' ', '_', $household['last_name'])) . '_' . date('Y-m-d') . '.pdf';
$mpdf->Output($filename, 'D');
