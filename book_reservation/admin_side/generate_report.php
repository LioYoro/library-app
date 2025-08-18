<?php
require '../../vendor/autoload.php'; // make sure PhpSpreadsheet is installed via Composer

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$host = "localhost";
$dbname = "library_test_db";
$username = "root";
$password = "";

$conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle("Book Summary");

// === 1. Borrowed Books ===
$sheet->setCellValue('A1', 'Borrowed Books');
$sheet->setCellValue('A2', 'Title');
$sheet->setCellValue('B2', 'Call Number');
$sheet->setCellValue('C2', 'Total Borrowed');

$stmt = $conn->query("
    SELECT r.book_title AS TITLE, b.`CALL NUMBER` AS call_number, COUNT(*) AS total_borrowed
    FROM reservations r
    LEFT JOIN books b ON r.book_title = b.TITLE
    WHERE r.status = 'borrowed'
    GROUP BY r.book_title
");
$row = 3;
while ($book = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $sheet->setCellValue("A{$row}", $book['TITLE']);
    $sheet->setCellValue("B{$row}", $book['call_number']);
    $sheet->setCellValue("C{$row}", $book['total_borrowed']);
    $row++;
}


// === 2. Reserved Books ===
$row += 2;
$sheet->setCellValue("A{$row}", 'Reserved Books');
$row++;
$sheet->setCellValue("A{$row}", 'Title');
$sheet->setCellValue("B{$row}", 'Call Number');
$sheet->setCellValue("C{$row}", 'Total Reservations');

$stmt = $conn->query("
    SELECT r.book_title, b.`CALL NUMBER` AS call_number, COUNT(*) as total_reservations
    FROM reservations r
    LEFT JOIN books b ON r.book_title = b.TITLE
    WHERE r.status IN ('pending','borrowed')
    GROUP BY r.book_title
");
$row++;
while ($book = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $sheet->setCellValue("A{$row}", $book['book_title']);
    $sheet->setCellValue("B{$row}", $book['call_number']);
    $sheet->setCellValue("C{$row}", $book['total_reservations']);
    $row++;
}

// Output Excel file
$filename = "book_summary_report_" . date('Y-m-d_H-i-s') . ".xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment;filename=\"{$filename}\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
