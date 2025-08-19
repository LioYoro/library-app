<?php
session_start();
require_once __DIR__ . '/../../vendor/autoload.php'; // PhpSpreadsheet + TCPDF

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use TCPDF;

// Database connection
$pdo = new PDO("mysql:host=localhost;dbname=library_test_db;charset=utf8", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Current date
$reportDate = date('F d, Y');

// ------------------ INVENTORY STATS ------------------ //
$totalBooks = $pdo->query("SELECT COUNT(*) FROM books")->fetchColumn();
$uniqueTitles = $pdo->query("SELECT COUNT(DISTINCT TITLE) FROM books")->fetchColumn();
$duplicateTitles = $totalBooks - $uniqueTitles;
$uniqueAuthors = $pdo->query("SELECT COUNT(DISTINCT AUTHOR) FROM books")->fetchColumn();
$duplicateAuthors = $totalBooks - $uniqueAuthors;
$generalCategories = $pdo->query("SELECT COUNT(DISTINCT General_Category) FROM books")->fetchColumn();
$subCategories = $pdo->query("SELECT COUNT(DISTINCT Sub_Category) FROM books")->fetchColumn();

$booksLastMonth = $pdo->query("
    SELECT COUNT(*) FROM books
    WHERE MONTH(date_added) = MONTH(CURRENT_DATE - INTERVAL 1 MONTH)
    AND YEAR(date_added) = YEAR(CURRENT_DATE - INTERVAL 1 MONTH)
")->fetchColumn();

$booksSinceLastReport = $pdo->query("
    SELECT COUNT(*) FROM books
    WHERE date_added >= DATE_SUB(CURRENT_DATE, INTERVAL 1 MONTH)
")->fetchColumn();

$categoryStmt = $pdo->query("
    SELECT General_Category, COUNT(*) AS count
    FROM books
    GROUP BY General_Category
    ORDER BY count DESC
");
$booksPerCategory = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);

// ------------------ RESERVATION STATS ------------------ //
$totalReservations = $pdo->query("SELECT COUNT(*) FROM reservations")->fetchColumn();
$currentBorrowed = $pdo->query("SELECT COUNT(*) FROM reservations WHERE status='borrowed' AND done=0")->fetchColumn();
$currentPending = $pdo->query("SELECT COUNT(*) FROM reservations WHERE status='pending'")->fetchColumn();

$topBorrowed = $pdo->query("
    SELECT r.book_title, b.`CALL NUMBER` AS call_number, COUNT(*) AS total_borrows
    FROM reservations r
    LEFT JOIN books b ON r.book_title = b.TITLE
    WHERE r.status='borrowed' AND r.done=1
    GROUP BY r.book_title, b.`CALL NUMBER`
    ORDER BY total_borrows DESC
    LIMIT 50
")->fetchAll(PDO::FETCH_ASSOC);

$topReserved = $pdo->query("
    SELECT r.book_title, b.`CALL NUMBER` AS call_number, COUNT(*) AS total_reservations
    FROM reservations r
    LEFT JOIN books b ON r.book_title = b.TITLE
    WHERE r.status IN ('pending','borrowed') AND r.done=1
    GROUP BY r.book_title
    ORDER BY total_reservations DESC
    LIMIT 50
")->fetchAll(PDO::FETCH_ASSOC);

$format = $_GET['format'] ?? 'pdf';

$inventoryData = [
    'Total Books'=>$totalBooks,
    'Unique Titles'=>$uniqueTitles,
    'Duplicate Titles'=>$duplicateTitles,
    'Unique Authors'=>$uniqueAuthors,
    'Duplicate Authors'=>$duplicateAuthors,
    'General Categories'=>$generalCategories,
    'Subcategories'=>$subCategories,
    'Books Added Last Month'=>$booksLastMonth,
    'Books Added Since Last Report'=>$booksSinceLastReport
];

$reservationData = [
    'Total Reservations'=>$totalReservations,
    'Currently Borrowed'=>$currentBorrowed,
    'Currently Pending'=>$currentPending
];

// ------------------ GENERATE PDF ------------------ //
if ($format === 'pdf') {
    $pdf = new TCPDF();
    $pdf->AddPage();
    $pdf->SetFont('helvetica', '', 12);

    // Title
    $pdf->Cell(0, 10, 'Full Library Report', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 6, "As of: $reportDate", 0, 1, 'C');
    $pdf->Ln(5);

    // Inventory Summary
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, 'Inventory Summary', 0, 1);
    $pdf->SetFont('helvetica', '', 10);
    foreach($inventoryData as $label=>$val){
        $pdf->Cell(90,8,$label,1);
        $pdf->Cell(90,8,$val,1,1);
    }

    // Books per category
    $pdf->Ln(3);
    $pdf->SetFont('helvetica','B',12);
    $pdf->Cell(0,8,'Books per General Category',0,1);
    $pdf->SetFont('helvetica','',10);
    foreach($booksPerCategory as $row){
        $pdf->Cell(90,8,$row['General_Category'],1);
        $pdf->Cell(90,8,$row['count'],1,1);
    }

    // Reservation Summary
    $pdf->Ln(5);
    $pdf->SetFont('helvetica','B',12);
    $pdf->Cell(0,8,'Reservation Summary',0,1);
    $pdf->SetFont('helvetica','',10);
    foreach($reservationData as $label=>$val){
        $pdf->Cell(90,8,$label,1);
        $pdf->Cell(90,8,$val,1,1);
    }

    // ---------------- Top Borrowed Books ---------------- //
    $pdf->Ln(3);
    $pdf->SetFont('helvetica','B',12);
    $pdf->Cell(0,8,'Top Borrowed Books',0,1);
    $wTitle = 90; $wCall = 50; $wCount = 40;
    $pdf->SetFont('helvetica','B',10);
    $pdf->Cell($wTitle,8,'Title',1);
    $pdf->Cell($wCall,8,'Call Number',1);
    $pdf->Cell($wCount,8,'Total Borrows',1,1);
    $pdf->SetFont('helvetica','',10);

    foreach($topBorrowed as $b){
        $title = $b['book_title'];
        $call = $b['call_number'] ?: 'No call number';
        $count = $b['total_borrows'];

        $nbLines = $pdf->getNumLines($title, $wTitle);
        $rowHeight = 6 * $nbLines;

        $pdf->MultiCell($wTitle, $rowHeight, $title, 1, 'L', 0, 0);
        $pdf->MultiCell($wCall, $rowHeight, $call, 1, 'C', 0, 0);
        $pdf->MultiCell($wCount, $rowHeight, $count, 1, 'C', 0, 1);
    }

    // ---------------- Top Reserved Books ---------------- //
    $pdf->Ln(3);
    $pdf->SetFont('helvetica','B',12);
    $pdf->Cell(0,8,'Top Reserved Books',0,1);
    $pdf->SetFont('helvetica','B',10);
    $pdf->Cell($wTitle,8,'Title',1);
    $pdf->Cell($wCall,8,'Call Number',1);
    $pdf->Cell($wCount,8,'Total Reservations',1,1);
    $pdf->SetFont('helvetica','',10);

    foreach($topReserved as $r){
        $title = $r['book_title'];
        $call = $r['call_number'] ?: 'No call number';
        $count = $r['total_reservations'];

        $nbLines = $pdf->getNumLines($title, $wTitle);
        $rowHeight = 6 * $nbLines;

        $pdf->MultiCell($wTitle, $rowHeight, $title, 1, 'L', 0, 0);
        $pdf->MultiCell($wCall, $rowHeight, $call, 1, 'C', 0, 0);
        $pdf->MultiCell($wCount, $rowHeight, $count, 1, 'C', 0, 1);
    }

    ob_end_clean();
    $pdf->Output('full_library_report.pdf','D');
    exit;
}

// ------------------ GENERATE EXCEL ------------------ //
// Excel part remains unchanged
if ($format === 'excel') {
    ob_clean();
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle("Full Library Report");

    $row = 1;
    $sheet->setCellValue("A{$row}", "Full Library Report");
    $sheet->mergeCells("A{$row}:C{$row}");
    $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(14);
    $row++;
    $sheet->setCellValue("A{$row}", "As of: $reportDate");
    $sheet->mergeCells("A{$row}:C{$row}");
    $sheet->getStyle("A{$row}")->getFont()->setItalic(true)->setSize(10);
    $row+=2;

    // Inventory Summary
    $sheet->setCellValue("A{$row}", "Inventory Summary");
    $sheet->getStyle("A{$row}")->getFont()->setBold(true);
    $row++;
    foreach($inventoryData as $label=>$val){
        $sheet->setCellValue("A{$row}", $label);
        $sheet->setCellValue("B{$row}", $val);
        $row++;
    }

    // Books per Category
    $row++;
    $sheet->setCellValue("A{$row}", "Books per General Category");
    $sheet->getStyle("A{$row}")->getFont()->setBold(true);
    $row++;
    foreach($booksPerCategory as $cat){
        $sheet->setCellValue("A{$row}", $cat['General_Category']);
        $sheet->setCellValue("B{$row}", $cat['count']);
        $row++;
    }

    // Reservation Summary
    $row++;
    $sheet->setCellValue("A{$row}", "Reservation Summary");
    $sheet->getStyle("A{$row}")->getFont()->setBold(true);
    $row++;
    foreach($reservationData as $label=>$val){
        $sheet->setCellValue("A{$row}", $label);
        $sheet->setCellValue("B{$row}", $val);
        $row++;
    }

    // Top Borrowed
    $row++;
    $sheet->setCellValue("A{$row}", "Top Borrowed Books");
    $sheet->getStyle("A{$row}")->getFont()->setBold(true);
    $row++;
    $sheet->setCellValue("A{$row}", "Title");
    $sheet->setCellValue("B{$row}", "Call Number");
    $sheet->setCellValue("C{$row}", "Total Borrows");
    $row++;
    foreach($topBorrowed as $b){
        $sheet->setCellValue("A{$row}", $b['book_title']);
        $sheet->setCellValue("B{$row}", $b['call_number'] ?: 'No call number');
        $sheet->setCellValue("C{$row}", $b['total_borrows']);
        $row++;
    }

    // Top Reserved
    $row++;
    $sheet->setCellValue("A{$row}", "Top Reserved Books");
    $sheet->getStyle("A{$row}")->getFont()->setBold(true);
    $row++;
    $sheet->setCellValue("A{$row}", "Title");
    $sheet->setCellValue("B{$row}", "Call Number");
    $sheet->setCellValue("C{$row}", "Total Reservations");
    $row++;
    foreach($topReserved as $r){
        $sheet->setCellValue("A{$row}", $r['book_title']);
        $sheet->setCellValue("B{$row}", $r['call_number'] ?: 'No call number');
        $sheet->setCellValue("C{$row}", $r['total_reservations']);
        $row++;
    }

    $filename = "full_library_report_" . date('Y-m-d_H-i-s') . ".xlsx";
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header("Content-Disposition: attachment;filename=\"{$filename}\"");
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}
