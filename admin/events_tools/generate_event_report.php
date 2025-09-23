<?php
ob_start();
session_start();
require_once __DIR__ . '/../../vendor/autoload.php'; // PhpSpreadsheet + TCPDF

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$pdo = new PDO("mysql:host=localhost;dbname=library_test_db;charset=utf8", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$type   = $_GET['type']   ?? 'monthly'; // 'monthly' or 'yearly'
$format = $_GET['format'] ?? 'pdf';     // 'pdf' or 'excel'

$reportData = [];
$title = "Event Report";

// ================== MONTHLY ==================
if ($type === 'monthly' && isset($_GET['month'])) {
    [$year, $month] = explode('-', $_GET['month']);
    $date = DateTime::createFromFormat('Y-m', "$year-$month");

    // Current month counts
    $stmt = $pdo->prepare("SELECT status, COUNT(*) as cnt 
                           FROM event_report 
                           WHERE YEAR(date_submitted)=? AND MONTH(date_submitted)=? 
                           GROUP BY status");
    $stmt->execute([$year, $month]);
    $current = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Last month counts
    $lastMonth = $date->modify("-1 month");
    $stmt = $pdo->prepare("SELECT status, COUNT(*) as cnt 
                           FROM event_report 
                           WHERE YEAR(date_submitted)=? AND MONTH(date_submitted)=? 
                           GROUP BY status");
    $stmt->execute([$lastMonth->format("Y"), $lastMonth->format("n")]);
    $previous = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $reportData = [
        'previous' => [
            'label'    => $lastMonth->format("F Y"),
            'Proposed' => array_sum($previous),
            'Accepted' => $previous['ACCEPTED'] ?? 0,
            'Rejected' => $previous['REJECTED'] ?? 0,
            'Pending'  => $previous['PENDING'] ?? 0,
        ],
        'current' => [
            'label'    => (new DateTime("$year-$month-01"))->format("F Y"),
            'Proposed' => array_sum($current),
            'Accepted' => $current['ACCEPTED'] ?? 0,
            'Rejected' => $current['REJECTED'] ?? 0,
            'Pending'  => $current['PENDING'] ?? 0,
        ]
    ];
    $title = "Monthly Event Report";
}

// ================== YEARLY ==================
if ($type === 'yearly' && isset($_GET['year'])) {
    $year = (int)$_GET['year'];
    $stmt = $pdo->prepare("SELECT MONTH(date_submitted) as m, status, COUNT(*) as cnt
                           FROM event_report
                           WHERE YEAR(date_submitted)=?
                           GROUP BY m, status
                           ORDER BY m");
    $stmt->execute([$year]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $reportData = [];
    foreach ($rows as $r) {
        $month = $r['m'];
        if (!isset($reportData[$month])) {
            $reportData[$month] = [
                'label'    => date("F", mktime(0,0,0,$month,1)),
                'Proposed' => 0,
                'Accepted' => 0,
                'Rejected' => 0,
                'Pending'  => 0,
            ];
        }
        $reportData[$month][$r['status']] = $r['cnt'];
        $reportData[$month]['Proposed'] += $r['cnt'];
    }
    $title = "Yearly Event Report ($year)";
}

// ================== EXPORT ==================
if ($format === 'excel') {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $row = 1;

    $sheet->setCellValue("A{$row}", $title);
    $sheet->mergeCells("A{$row}:E{$row}");
    $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(14);
    $row += 2;

    // Headers
    $sheet->setCellValue("A{$row}","Period");
    $sheet->setCellValue("B{$row}","Proposed");
    $sheet->setCellValue("C{$row}","Accepted");
    $sheet->setCellValue("D{$row}","Rejected");
    $sheet->setCellValue("E{$row}","Pending");
    $sheet->getStyle("A{$row}:E{$row}")->getFont()->setBold(true);
    $row++;

    foreach ($reportData as $r) {
        $sheet->setCellValue("A{$row}", $r['label']);
        $sheet->setCellValue("B{$row}", $r['Proposed']);
        $sheet->setCellValue("C{$row}", $r['Accepted']);
        $sheet->setCellValue("D{$row}", $r['Rejected']);
        $sheet->setCellValue("E{$row}", $r['Pending']);
        $row++;
    }

    $filename = "event_report_".date('Y-m-d_H-i-s').".xlsx";
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header("Content-Disposition: attachment;filename=\"{$filename}\"");
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
} else {
    $pdf = new TCPDF();
    $pdf->AddPage();
    $pdf->SetFont('helvetica', '', 12);

    $pdf->Cell(0,10,$title,0,1,'C');
    $pdf->Ln(5);

    $tbl = '<table border="1" cellpadding="5">
            <tr style="font-weight:bold; background-color:#f2f2f2;">
              <th>Period</th>
              <th>Proposed</th>
              <th>Accepted</th>
              <th>Rejected</th>
              <th>Pending</th>
            </tr>';
    foreach ($reportData as $r) {
        $tbl .= "<tr>
                   <td>{$r['label']}</td>
                   <td>{$r['Proposed']}</td>
                   <td>{$r['Accepted']}</td>
                   <td>{$r['Rejected']}</td>
                   <td>{$r['Pending']}</td>
                 </tr>";
    }
    $tbl .= '</table>';

    $pdf->writeHTML($tbl, true, false, false, false, '');
    $pdf->Output("event_report.pdf","D");
    exit;
}
