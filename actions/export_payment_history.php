<?php
include '../includes/auth.php';
include '../db.php';

// Require admin access
if (!is_admin()) {
    http_response_code(403);
    exit('Access denied');
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$householdId = (int)($input['household_id'] ?? 0);
$householdName = $input['household_name'] ?? 'Household';

if (!$householdId) {
    http_response_code(400);
    exit('Invalid household ID');
}

// Verify household exists
$stmt = $pdo->prepare("SELECT id FROM households WHERE id = ?");
$stmt->execute([$householdId]);
if (!$stmt->fetch()) {
    http_response_code(404);
    exit('Household not found');
}

// Fetch all payments for household
$pstmt = $pdo->prepare("
    SELECT * FROM payments 
    WHERE household_id = ? AND deleted_at IS NULL 
    ORDER BY paid_at DESC
");
$pstmt->execute([$householdId]);
$payments = $pstmt->fetchAll();

// Use PHPSpreadsheet to create Excel
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Payment History');

// Headers
$headers = ['OR No.', 'Period', 'Amount', 'Date Paid', 'Remarks', 'Status'];
$sheet->fromArray($headers, NULL, 'A1');

// Style headers
$headerStyle = [
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'color' => ['rgb' => '1F8449'],  // Green-800
    ],
    'font' => [
        'bold' => true,
        'color' => ['rgb' => 'FFFFFF'],
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER,
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => 'CCCCCC'],
        ],
    ],
];

$sheet->getStyle('A1:F1')->applyFromArray($headerStyle);

// Add data rows
$row = 2;
foreach ($payments as $p) {
    $period = sprintf("%d-%02d", $p['period_year'], $p['period_month']);
    if ($p['period_to_year'] && $p['period_to_month']) {
        $period .= sprintf(" to %d-%02d", $p['period_to_year'], $p['period_to_month']);
    }
    
    $status = $p['is_promo'] ? 'Promo' : 'Normal';
    
    $sheet->setCellValue('A' . $row, $p['or_no'] ?: '-');
    $sheet->setCellValue('B' . $row, $period);
    $sheet->setCellValue('C' . $row, '₱' . number_format($p['amount'], 2));
    $sheet->setCellValue('D' . $row, date('M d, Y H:i', strtotime($p['paid_at'])));
    $sheet->setCellValue('E' . $row, $p['remarks'] ?: '-');
    $sheet->setCellValue('F' . $row, $status);
    
    // Center alignment and borders
    $cellStyle = [
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
            'wrapText' => true,
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => 'CCCCCC'],
            ],
        ],
    ];
    
    $sheet->getStyle('A' . $row . ':F' . $row)->applyFromArray($cellStyle);
    
    $row++;
}

// Adjust column widths
$sheet->getColumnDimension('A')->setWidth(12);
$sheet->getColumnDimension('B')->setWidth(18);
$sheet->getColumnDimension('C')->setWidth(14);
$sheet->getColumnDimension('D')->setWidth(16);
$sheet->getColumnDimension('E')->setWidth(20);
$sheet->getColumnDimension('F')->setWidth(10);

// Output
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="Payment_History_' . preg_replace('/[^a-zA-Z0-9]/', '', $householdName) . '_' . date('Y-m-d') . '.xlsx"');

$writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
$writer->save('php://output');
exit;
