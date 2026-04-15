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
        body { font-family: Arial, sans-serif; font-size: 10pt; line-height: 1.3; color: #333; }
        
        .header-banner { 
            background-color: #1F8449; 
            color: white; 
            padding: 8pt 10pt; 
            text-align: center; 
            margin: -12pt -12pt 0 -12pt;
            margin-bottom: 12pt;
        }
        .header-banner h1 { 
            font-size: 18pt; 
            margin: 0; 
            font-weight: bold; 
        }
        .header-banner p { 
            font-size: 8pt; 
            margin: 3pt 0 0 0; 
            color: #e8f5e9;
        }
        
        h2 { 
            font-size: 11pt; 
            color: #1F8449;
            margin-top: 8pt; 
            margin-bottom: 6pt; 
            padding: 3pt 0 3pt 0;
            border-bottom: 2pt solid #1F8449;
        }
        
        .primary-info-box {
            background-color: #fafafa;
            border: none;
            padding: 8pt;
            margin-bottom: 10pt;
            border-radius: 3pt;
        }
        
        .section { margin-bottom: 8pt; }
        
        .info-row { 
            margin-bottom: 4pt; 
            display: flex;
            font-size: 9pt;
        }
        
        .info-label { 
            font-weight: bold; 
            color: #1F8449; 
            width: 32%; 
            flex-shrink: 0;
        }
        
        .info-value { 
            color: #555; 
            flex-grow: 1;
        }
        
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 6pt; 
        }
        
        th { 
            background-color: #e8f5e9; 
            color: #1F8449; 
            font-weight: bold; 
            padding: 5pt 6pt; 
            border: 1pt solid #1F8449; 
            text-align: left; 
            font-size: 9pt; 
        }
        
        td { 
            padding: 5pt 6pt; 
            border: 1pt solid #ddd; 
            font-size: 9pt; 
        }
        
        tbody tr:nth-child(odd) {
            background-color: #fafafa;
        }
        
        tbody tr:nth-child(even) {
            background-color: #ffffff;
        }
        
        .badge-success { 
            background-color: #d4edda; 
            color: #155724; 
            padding: 2pt 6pt; 
            border-radius: 3pt; 
            font-size: 8pt;
            font-weight: bold;
            display: inline-block;
        }
        
        .badge-info { 
            background-color: #d1ecf1; 
            color: #0c5460; 
            padding: 2pt 6pt; 
            border-radius: 3pt; 
            font-size: 8pt;
            font-weight: bold;
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="header-banner">
        <h1>Household Details Report</h1>
        <p>Generated on ' . date('F d, Y H:i A') . '</p>
    </div>

    <h2>Primary Member Information</h2>
    <div class="primary-info-box">
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

    <h2>Address Information</h2>
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

    <h2>Residency Dates</h2>
    <div class="section">
        <div class="info-row">
            <span class="info-label">Move-in Date:</span>
            <span class="info-value">' . ($household['move_in_date'] ? date('M d, Y', strtotime($household['move_in_date'])) : '-') . '</span>
        </div>
        <div class="info-row">
            <span class="info-label">Move-out Date:</span>
            <span class="info-value">' . ($household['move_out_date'] ? date('M d, Y', strtotime($household['move_out_date'])) : '<span class="badge-success">Currently Living</span>') . '</span>
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
    'margin_top' => 12,
    'margin_bottom' => 12
]);

$mpdf->WriteHTML($html);

$filename = 'Household_' . preg_replace('/[^a-zA-Z0-9-]/', '', str_replace(' ', '_', $household['last_name'])) . '_' . date('Y-m-d') . '.pdf';
$mpdf->Output($filename, 'D');
