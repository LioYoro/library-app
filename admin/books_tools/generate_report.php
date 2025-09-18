<?php
ob_start();
session_start();
require_once __DIR__ . '/../../vendor/autoload.php'; // PhpSpreadsheet + TCPDF

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Chart\{
    Chart, DataSeries, DataSeriesValues, Layout, Legend, PlotArea, Title
};
$pdf = new TCPDF();


// -------------------- DATABASE -------------------- //
$pdo = new PDO("mysql:host=localhost;dbname=library_test_db;charset=utf8", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Current date
$reportDate = date('F d, Y');
$format = $_GET['format'] ?? 'pdf'; // 'pdf' or 'excel'

// ------------------ FETCH DATA ------------------ //
// Inventory
$totalBooks = $pdo->query("SELECT COUNT(*) FROM books")->fetchColumn();
$uniqueTitles = $pdo->query("SELECT COUNT(DISTINCT TITLE) FROM books")->fetchColumn();
$duplicateTitles = $totalBooks - $uniqueTitles;
$uniqueAuthors = $pdo->query("SELECT COUNT(DISTINCT AUTHOR) FROM books")->fetchColumn();
$duplicateAuthors = $totalBooks - $uniqueAuthors;
$generalCategories = $pdo->query("SELECT COUNT(DISTINCT General_Category) FROM books")->fetchColumn();
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

// Monthly data for line chart
$monthlyStmt = $pdo->query("
    SELECT DATE_FORMAT(date_added,'%Y-%m') as month, COUNT(*) as total
    FROM books
    GROUP BY DATE_FORMAT(date_added,'%Y-%m')
    ORDER BY month ASC
");
$monthlyData = $monthlyStmt->fetchAll(PDO::FETCH_ASSOC);

$inventoryData = [
    'Total Books' => $totalBooks,
    'Unique Titles' => $uniqueTitles,
    'Duplicate Titles' => $duplicateTitles,
    'Unique Authors' => $uniqueAuthors,
    'Duplicate Authors' => $duplicateAuthors,
    'General Categories' => $generalCategories,
    'Books Added Last Month' => $booksLastMonth,
    'Books Added Since Last Report' => $booksSinceLastReport
];

// ------------------ RESERVATIONS ------------------ //
$totalReservations = $pdo->query("SELECT COUNT(*) FROM reservations")->fetchColumn();
$currentBorrowed = $pdo->query("SELECT COUNT(*) FROM reservations WHERE status='borrowed' AND done=0")->fetchColumn();
$currentPending = $pdo->query("SELECT COUNT(*) FROM reservations WHERE status='pending'")->fetchColumn();

$reservationData = [
    'Total Reservations' => $totalReservations,
    'Currently Borrowed' => $currentBorrowed,
    'Currently Pending' => $currentPending
];

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
    GROUP BY r.book_title, b.`CALL NUMBER`
    ORDER BY total_reservations DESC
    LIMIT 50
")->fetchAll(PDO::FETCH_ASSOC);

// ------------------ PDF GENERATION ------------------ //
if ($format === 'pdf') {

    // Charts definitions
    $lineChart = [
        'type' => 'line',
        'data' => [
            'labels' => array_column($monthlyData,'month'),
            'datasets' => [[
                'label'=>'Books Added',
                'data'=>array_column($monthlyData,'total'),
                'borderColor'=>'rgb(54,162,235)',
                'fill'=>false
            ]]
        ],
        'options' => [
            'plugins' => [
                'title' => ['display'=>true, 'text'=>'Books Added Per Month']
            ]
        ]
    ];

    $pieChart = [
        'type' => 'pie',
        'data' => [
            'labels' => array_column($booksPerCategory,'General_Category'),
            'datasets' => [[
                'data'=>array_column($booksPerCategory,'count'),
                'backgroundColor' => [
                    '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', 
                    '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF'
                ]
            ]]
        ],
        'options' => [
            'plugins' => [
                'title' => ['display'=>true, 'text'=>'Books by Category']
            ]
        ]
    ];

    $pdf = new TCPDF();
    $pdf->AddPage();
    $pdf->SetFont('helvetica', '', 12);

    // Title
    $pdf->Cell(0, 10, 'Library Report', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 6, "As of: $reportDate", 0, 1, 'C');
    $pdf->Ln(5);

    $context = stream_context_create(['http'=>['timeout'=>10,'user_agent'=>'Mozilla/5.0']]);

    // ----- 1. Inventory Summary -----
    $pdf->SetFont('helvetica','B',12);
    $pdf->Cell(0,8,'Inventory Summary',0,1);
    $pdf->SetFont('helvetica','',10);
    foreach($inventoryData as $label=>$val){
        $pdf->Cell(90,8,$label,1);
        $pdf->Cell(90,8,$val,1,1);
    }
    $pdf->Ln(5);

    // ----- 2. Line Chart -----
    $lineChartUrl = 'https://quickchart.io/chart?format=jpg&c=' . urlencode(json_encode($lineChart));
    $lineImg = @file_get_contents($lineChartUrl, false, $context);
    if ($lineImg !== false) {
        file_put_contents('monthly_chart.jpg', $lineImg);
        $pdf->Image('monthly_chart.jpg','', '', 180, 80);
    } else {
        $pdf->SetFont('helvetica','B',10);
        $pdf->Cell(0,8,'Books Added Per Month Chart (Image could not be loaded)',0,1);
    }
    $pdf->Ln(5);

    // ----- 3. Books per General Category Table -----
    $pdf->SetFont('helvetica','B',12);
    $pdf->Cell(0,8,'Books per General Category',0,1);
    $pdf->SetFont('helvetica','',10);
    foreach($booksPerCategory as $cat){
        $pdf->Cell(90,8,$cat['General_Category'],1);
        $pdf->Cell(90,8,$cat['count'],1,1);
    }
    $pdf->Ln(5);

    // ----- 4. Pie Chart -----
    $pieChartUrl = 'https://quickchart.io/chart?format=jpg&c=' . urlencode(json_encode($pieChart));
    $pieImg = @file_get_contents($pieChartUrl, false, $context);
    if ($pieImg !== false) {
        file_put_contents('pie_chart.jpg', $pieImg);
        $pdf->Image('pie_chart.jpg','', '', 180, 80);
    } else {
        $pdf->SetFont('helvetica','B',10);
        $pdf->Cell(0,8,'Books by Category Chart (Image could not be loaded)',0,1);
    }
    $pdf->Ln(5);

    // ----- 5. Remaining Tables -----
    // Reservation Summary
    $pdf->SetFont('helvetica','B',12);
    $pdf->Cell(0,8,'Reservation Summary',0,1);
    $pdf->SetFont('helvetica','',10);
    foreach($reservationData as $label=>$val){
        $pdf->Cell(90,8,$label,1);
        $pdf->Cell(90,8,$val,1,1);
    }

    // Top Borrowed Books
    $pdf->AddPage();
    $pdf->SetFont('helvetica','B',12);
    $pdf->Cell(0,8,'Top Borrowed Books',0,1);
    $wTitle=90; $wCall=50; $wCount=40;
    $pdf->SetFont('helvetica','B',10);
    $pdf->Cell($wTitle,8,'Title',1);
    $pdf->Cell($wCall,8,'Call Number',1);
    $pdf->Cell($wCount,8,'Total Borrows',1,1);
    $pdf->SetFont('helvetica','',10);
    foreach($topBorrowed as $b){
        $nbLines = $pdf->getNumLines($b['book_title'],$wTitle);
        $rowHeight = 6*$nbLines;
        $pdf->MultiCell($wTitle,$rowHeight,$b['book_title'],1,'L',0,0);
        $pdf->MultiCell($wCall,$rowHeight,$b['call_number'] ?: 'No call number',1,'C',0,0);
        $pdf->MultiCell($wCount,$rowHeight,$b['total_borrows'],1,'C',0,1);
    }

    // Top Reserved Books
    $pdf->Ln(10);
    $pdf->SetFont('helvetica','B',14);
    $pdf->Cell(0,8,'Top Reserved Books',0,1);
    $pdf->SetFont('helvetica','B',10);
    $pdf->Cell($wTitle,8,'Title',1);
    $pdf->Cell($wCall,8,'Call Number',1);
    $pdf->Cell($wCount,8,'Total Reservations',1,1);
    $pdf->SetFont('helvetica','',10);
    foreach($topReserved as $r){
        $nbLines = $pdf->getNumLines($r['book_title'],$wTitle);
        $rowHeight = 6*$nbLines;
        $pdf->MultiCell($wTitle,$rowHeight,$r['book_title'],1,'L',0,0);
        $pdf->MultiCell($wCall,$rowHeight,$r['call_number'] ?: 'No call number',1,'C',0,0);
        $pdf->MultiCell($wCount,$rowHeight,$r['total_reservations'],1,'C',0,1);
    }

    // Clean up temporary files
    @unlink('monthly_chart.jpg');
    @unlink('pie_chart.jpg');

    ob_end_clean();
    $pdf->Output('library_report.pdf','D');
    exit;
}





// ------------------ EXCEL GENERATION ------------------ //
if($format==='excel'){
    ob_clean();
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle("Library Report");
    $row=1;

    $sheet->setCellValue("A{$row}", "Library Report");
    $sheet->mergeCells("A{$row}:C{$row}");
    $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(14);
    $row++;
    $sheet->setCellValue("A{$row}", "As of: $reportDate");
    $sheet->mergeCells("A{$row}:C{$row}");
    $sheet->getStyle("A{$row}")->getFont()->setItalic(true)->setSize(10);
    $row+=2;

    // ----- Inventory ----- //
    $sheet->setCellValue("A{$row}","Inventory Summary");
    $sheet->getStyle("A{$row}")->getFont()->setBold(true);
    $row++;
    foreach($inventoryData as $label=>$val){
        $sheet->setCellValue("A{$row}",$label);
        $sheet->setCellValue("B{$row}",$val);
        $row++;
    }

    // ----- Books per Category ----- //
    $row++;
    $sheet->setCellValue("A{$row}","Books per General Category");
    $sheet->getStyle("A{$row}")->getFont()->setBold(true);
    $row++;
    $rowStartCat=$row;
    foreach($booksPerCategory as $cat){
        $sheet->setCellValue("A{$row}",$cat['General_Category']);
        $sheet->setCellValue("B{$row}",$cat['count']);
        $row++;
    }
    $rowEndCat=$row-1;

    // ----- Monthly Data for Line Chart ----- //
    $row++;
    $sheet->setCellValue("A{$row}","Books Added Per Month");
    $sheet->getStyle("A{$row}")->getFont()->setBold(true);
    $row++;
    $rowStartLine=$row;
    foreach($monthlyData as $m){
        $sheet->setCellValue("A{$row}",$m['month']);
        $sheet->setCellValue("B{$row}",$m['total']);
        $row++;
    }
    $rowEndLine=$row-1;

    // ----- Reservation ----- //
    $row++;
    $sheet->setCellValue("A{$row}","Reservation Summary");
    $sheet->getStyle("A{$row}")->getFont()->setBold(true);
    $row++;
    foreach($reservationData as $label=>$val){
        $sheet->setCellValue("A{$row}",$label);
        $sheet->setCellValue("B{$row}",$val);
        $row++;
    }

    // ----- Top Borrowed ----- //
    $row++;
    $sheet->setCellValue("A{$row}","Top Borrowed Books");
    $sheet->getStyle("A{$row}")->getFont()->setBold(true);
    $row++;
    $sheet->setCellValue("A{$row}","Title");
    $sheet->setCellValue("B{$row}","Call Number");
    $sheet->setCellValue("C{$row}","Total Borrows");
    $row++;
    foreach($topBorrowed as $b){
        $sheet->setCellValue("A{$row}",$b['book_title']);
        $sheet->setCellValue("B{$row}",$b['call_number'] ?: 'No call number');
        $sheet->setCellValue("C{$row}",$b['total_borrows']);
        $row++;
    }

    // ----- Top Reserved ----- //
    $row++;
    $sheet->setCellValue("A{$row}","Top Reserved Books");
    $sheet->getStyle("A{$row}")->getFont()->setBold(true);
    $row++;
    $sheet->setCellValue("A{$row}","Title");
    $sheet->setCellValue("B{$row}","Call Number");
    $sheet->setCellValue("C{$row}","Total Reservations");
    $row++;
    foreach($topReserved as $r){
        $sheet->setCellValue("A{$row}",$r['book_title']);
        $sheet->setCellValue("B{$row}",$r['call_number'] ?: 'No call number');
        $sheet->setCellValue("C{$row}",$r['total_reservations']);
        $row++;
    }

    // ----- CREATE EXCEL CHARTS ----- //
    if ($rowStartLine < $rowEndLine) { // Only create chart if we have data
        $dataseriesLabels = [new DataSeriesValues('String','Worksheet!$B$'.$rowStartLine, null, 1)];
        $xAxisTickValues = [new DataSeriesValues('String','Worksheet!$A$'.$rowStartLine.':$A$'.$rowEndLine,null,$rowEndLine-$rowStartLine+1)];
        $dataSeriesValues = [new DataSeriesValues('Number','Worksheet!$B$'.$rowStartLine.':$B$'.$rowEndLine,null,$rowEndLine-$rowStartLine+1)];
        $series = new DataSeries(DataSeries::TYPE_LINECHART, DataSeries::GROUPING_STANDARD, range(0,count($dataSeriesValues)-1),$dataseriesLabels,$xAxisTickValues,$dataSeriesValues);
        $plotArea = new PlotArea(null,[$series]);
        $titleChart = new Title('Books Added Per Month');
        $chart = new Chart('line_chart',$titleChart,new Legend(Legend::POSITION_RIGHT,null,false),$plotArea,true,0,null,null);
        $chart->setTopLeftPosition('D2');
        $chart->setBottomRightPosition('L15');
        $sheet->addChart($chart);
    }

    // ----- SAVE EXCEL ----- //
    $filename = "library_report_".date('Y-m-d_H-i-s').".xlsx";
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header("Content-Disposition: attachment;filename=\"{$filename}\"");
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->setIncludeCharts(true);
    $writer->save('php://output');
    exit;
}
?>