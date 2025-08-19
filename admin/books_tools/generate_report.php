<?php
session_start();
ob_clean();
ob_start();

require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use TCPDF;

$pdo = new PDO("mysql:host=localhost;dbname=library_test_db", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Current date for "As of"
$reportDate = date('F d, Y'); // e.g., August 19, 2025

// ------------------ FETCH BOOK STATS ------------------ //
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

// ------------------ BOOKS PER GENERAL_CATEGORY ------------------ //
$categoryStmt = $pdo->query("
    SELECT General_Category, COUNT(*) AS count
    FROM books
    GROUP BY General_Category
    ORDER BY count DESC
");
$booksPerCategory = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);

// ------------------ COMBINE DATA ------------------ //
$reportData = [
    "Total Books" => $totalBooks,
    "Unique Titles" => $uniqueTitles,
    "Duplicate Titles" => $duplicateTitles,
    "Unique Authors" => $uniqueAuthors,
    "Duplicate Authors" => $duplicateAuthors,
    "General Categories" => $generalCategories,
    "Subcategories" => $subCategories,
    "Books Added Last Month" => $booksLastMonth,
    "Books Added Since Last Report" => $booksSinceLastReport,
];

// ------------------ DETECT FORMAT ------------------ //
$format = $_GET['format'] ?? 'pdf';

// ------------------ GENERATE PDF ------------------ //
if ($format === 'pdf') {
    $pdf = new TCPDF();
    $pdf->AddPage();
    $pdf->SetFont('helvetica', '', 12);

    // Title & As of
    $pdf->Cell(0, 10, 'Library Books Report', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 6, "As of: $reportDate", 0, 1, 'C');
    $pdf->Ln(5);
    $pdf->SetFont('helvetica', '', 12);

    // Summary data
    foreach ($reportData as $label => $value) {
        $pdf->Cell(90, 10, $label, 1);
        $pdf->Cell(90, 10, $value, 1, 1);
    }

    // Books per General_Category
    $pdf->Ln(5);
    $pdf->Cell(0, 10, 'Books Per General Category', 0, 1, 'L');
    foreach ($booksPerCategory as $row) {
        $pdf->Cell(90, 10, $row['General_Category'], 1);
        $pdf->Cell(90, 10, $row['count'], 1, 1);
    }

    ob_end_clean();
    $pdf->Output('book_report.pdf', 'D');
    exit;
}

// ------------------ GENERATE EXCEL ------------------ //
if ($format === 'excel') {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Title
    $sheet->setCellValue('A1', 'Library Books Report');
    $sheet->mergeCells('A1:B1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

    // As of
    $sheet->setCellValue('A2', "As of: $reportDate");
    $sheet->mergeCells('A2:B2');
    $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(10);

    // Summary
    $row = 4;
    foreach ($reportData as $label => $value) {
        $sheet->setCellValue("A{$row}", $label);
        $sheet->setCellValue("B{$row}", $value);
        $row++;
    }

    // Books per General_Category
    $row += 2; // spacing
    $sheet->setCellValue("A{$row}", 'Books Per General Category');
    $sheet->getStyle("A{$row}")->getFont()->setBold(true);
    $row++;
    foreach ($booksPerCategory as $cat) {
        $sheet->setCellValue("A{$row}", $cat['General_Category']);
        $sheet->setCellValue("B{$row}", $cat['count']);
        $row++;
    }

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="book_summary.xlsx"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}
