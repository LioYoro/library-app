<?php
session_start();
$pageTitle = "Book Summary";

// Correct include paths
include(__DIR__ . '/../../admin/includes/header.php');
include(__DIR__ . '/../../admin/includes/sidebar.php');

// Include database connection
include(__DIR__ . '/../../includes/db.php'); // adjust path as needed

// Pagination settings
$perPage = 5;
$borrowedPage = isset($_GET['borrowed_page']) ? max(1, intval($_GET['borrowed_page'])) : 1;
$reservedPage = isset($_GET['reserved_page']) ? max(1, intval($_GET['reserved_page'])) : 1;

// ----------------------
// Quick Stats
// ----------------------
$totalReservations = $conn->query("SELECT COUNT(*) FROM reservations")->fetchColumn();
$currentBorrowed = $conn->query("SELECT COUNT(*) FROM reservations WHERE status='borrowed' AND done=0")->fetchColumn();
$currentPending = $conn->query("SELECT COUNT(*) FROM reservations WHERE status='pending'")->fetchColumn();

// ----------------------
// Top Borrowed Books (Historic)
// ----------------------
$borrowedOffset = ($borrowedPage - 1) * $perPage;
$stmtBorrowed = $conn->prepare("
    SELECT r.book_title, b.`CALL NUMBER` AS call_number, COUNT(*) as total_borrows
    FROM reservations r
    LEFT JOIN books b ON r.book_title = b.TITLE
    WHERE r.status='borrowed' AND r.done=1
    GROUP BY r.book_title, b.`CALL NUMBER`
    ORDER BY total_borrows DESC
    LIMIT :limit OFFSET :offset
");
$stmtBorrowed->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmtBorrowed->bindValue(':offset', $borrowedOffset, PDO::PARAM_INT);
$stmtBorrowed->execute();
$topBorrowed = $stmtBorrowed->fetchAll(PDO::FETCH_ASSOC);

$totalBorrowedBooks = $conn->query("SELECT COUNT(DISTINCT book_title) FROM reservations WHERE status='borrowed' AND done=1")->fetchColumn();
$totalBorrowedPages = ceil($totalBorrowedBooks / $perPage);

// ----------------------
// Top Reserved Books (Historic)
// ----------------------
$reservedOffset = ($reservedPage - 1) * $perPage;
$stmtReserved = $conn->prepare("
    SELECT r.book_title, b.`CALL NUMBER` AS call_number, COUNT(*) as total_reservations
    FROM reservations r
    LEFT JOIN books b ON r.book_title = b.TITLE
    WHERE r.status IN ('pending','borrowed') AND r.done=1
    GROUP BY r.book_title
    ORDER BY total_reservations DESC
    LIMIT :limit OFFSET :offset
");
$stmtReserved->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmtReserved->bindValue(':offset', $reservedOffset, PDO::PARAM_INT);
$stmtReserved->execute();
$topReserved = $stmtReserved->fetchAll(PDO::FETCH_ASSOC);

$totalReservedBooks = $conn->query("SELECT COUNT(DISTINCT book_title) FROM reservations WHERE status IN ('pending','borrowed') AND done=1")->fetchColumn();
$totalReservedPages = ceil($totalReservedBooks / $perPage);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= $pageTitle ?></title>
<link rel="stylesheet" href="book_summary.css">
</head>
<body>
<div id="main-content" class="with-sidebar">
    <h1><?= $pageTitle ?></h1>
    <a href="../../admin/reservations.php" class="btn-back">← Back to Admin Hub</a>

<!-- Quick Stats -->
<div class="summary-boxes">
    <div class="summary-box">
        <strong>Total Borrow Requests</strong>
        <p><?= $totalReservations ?></p>
    </div>
    <div class="summary-box">
        <strong>Currently Borrowed</strong>
        <p><?= $currentBorrowed ?></p>
    </div>
    <div class="summary-box">
        <strong>Currently Pending</strong>
        <p><?= $currentPending ?></p>
    </div>
</div>

<!-- Top Borrowed Books -->
<h2>Top Borrowed Books</h2>
<div class="book-list">
    <?php foreach($topBorrowed as $b): ?>
        <div class="book-item">
            <div class="book-info">
                <span class="book-title" data-fulltitle="<?= htmlspecialchars($b['book_title']) ?>">
                    <strong><?= htmlspecialchars($b['book_title']) ?></strong>
                </span>
                <span class="book-callnumber"><?= htmlspecialchars($b['call_number']) ?></span>
            </div>
            <span class="book-count"><?= $b['total_borrows'] ?> borrows</span>
        </div>
    <?php endforeach; ?>
</div>
<div class="pagination">
    <?php for($p=1; $p<=$totalBorrowedPages; $p++): ?>
        <a href="?borrowed_page=<?= $p ?>&reserved_page=<?= $reservedPage ?>" class="<?= $p==$borrowedPage?'active':'' ?>"><?= $p ?></a>
    <?php endfor; ?>
</div>

<!-- Top Reserved Books -->
<h2>Top Requested Books</h2>
<div class="book-list">
    <?php foreach($topReserved as $r): ?>
        <div class="book-item">
            <div class="book-info">
                <span class="book-title" data-fulltitle="<?= htmlspecialchars($r['book_title']) ?>">
                    <strong><?= htmlspecialchars($r['book_title']) ?></strong>
                </span>
                <span class="book-callnumber"><?= htmlspecialchars($r['call_number']) ?></span>
            </div>
            <span class="book-count"><?= $r['total_reservations'] ?> requests</span>
        </div>
        <div class="summary-box">
            <strong>Currently Pending</strong>
            <p><?= $currentPending ?></p>
        </div>
    </div>

    <!-- Top Borrowed Books -->
    <h2>Top Borrowed Books</h2>
    <div class="book-list">
        <?php foreach($topBorrowed as $b): ?>
            <div class="book-item">
                <div class="book-info">
                    <span class="book-title" data-fulltitle="<?= htmlspecialchars($b['book_title']) ?>">
                        <strong><?= htmlspecialchars($b['book_title']) ?></strong>
                    </span>
                    <span class="book-callnumber"><?= htmlspecialchars($b['call_number']) ?></span>
                </div>
                <span class="book-count"><?= $b['total_borrows'] ?> borrows</span>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="pagination">
        <?php for($p=1; $p<=$totalBorrowedPages; $p++): ?>
            <a href="?borrowed_page=<?= $p ?>&reserved_page=<?= $reservedPage ?>" class="<?= $p==$borrowedPage?'active':'' ?>"><?= $p ?></a>
        <?php endfor; ?>
    </div>

    <!-- Top Reserved Books -->
    <h2>Top Requested Books</h2>
    <div class="book-list">
        <?php foreach($topReserved as $r): ?>
            <div class="book-item">
                <div class="book-info">
                    <span class="book-title" data-fulltitle="<?= htmlspecialchars($r['book_title']) ?>">
                        <strong><?= htmlspecialchars($r['book_title']) ?></strong>
                    </span>
                    <span class="book-callnumber"><?= htmlspecialchars($r['call_number']) ?></span>
                </div>
                <span class="book-count"><?= $r['total_reservations'] ?> requests</span>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="pagination">
        <?php for($p=1; $p<=$totalReservedPages; $p++): ?>
            <a href="?borrowed_page=<?= $borrowedPage ?>&reserved_page=<?= $p ?>" class="<?= $p==$reservedPage?'active':'' ?>"><?= $p ?></a>
        <?php endfor; ?>
    </div>

    <!-- Generate Report Button -->
    <a href="generate_report.php" class="btn-generate">Generate Report</a>
</div> <!-- end #main-content -->
</body>
</html>

<style>
/* Sidebar hover fix */
.sidebar {
  position: fixed;
  top: 0;
  left: 0;
  width: 60px; /* collapsed */
  height: 100%;
  background: #2c3e50;
  color: #fff;
  overflow-x: hidden;
  transition: width 0.3s ease;
  z-index: 1000;
}
.sidebar:hover {
  width: 220px; /* expanded */
}
#main-content.with-sidebar {
  margin-left: 60px; /* collapsed width */
  transition: margin-left 0.3s ease;
}
.sidebar:hover ~ #main-content.with-sidebar {
  margin-left: 220px; /* expanded width */
}

body { font-family: Arial, sans-serif; padding: 2rem; background-color: #fafafa; color: #333; }
h1,h2 { margin-bottom: 1rem; }
a.btn-back, .btn-generate {
  display:inline-block;
  padding:8px 15px;
  margin-bottom:20px;
  background-color:#007BFF;
  color:#fff;
  text-decoration:none;
  border-radius:5px;
  transition:0.2s;
  width:auto; /* fit to text */
}
a.btn-back:hover, .btn-generate:hover { background-color:#0056b3; }

.summary-boxes { display:flex; flex-wrap:wrap; gap:15px; margin-bottom:25px; }
.summary-box { background:#f8f9fa; border:1px solid #ddd; padding:15px 20px; border-radius:8px; min-width:200px; flex:1 1 200px; box-shadow:0 1px 3px rgba(0,0,0,0.1); }
.summary-box strong { display:block; font-size:1rem; margin-bottom:5px; }
.summary-box p { font-size:1.5rem; margin:0; }
.book-list { margin-bottom:20px; }
.book-item { background:#fff; border:1px solid #ddd; padding:10px 15px; margin-bottom:8px; border-radius:6px; display:flex; justify-content:space-between; align-items:center; box-shadow:0 1px 2px rgba(0,0,0,0.05); }
.book-info { display:flex; flex-direction:column; max-width:70%; }
.book-item .book-title { white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.book-callnumber { font-size:0.85rem; color:#555; margin-top:2px; }
.book-item .book-count { background-color:#007BFF; color:#fff; padding:3px 8px; border-radius:12px; font-size:0.9rem; }
.pagination { margin-bottom:30px; }
.pagination a { display:inline-block; padding:6px 12px; margin:0 3px; border:1px solid #ddd; border-radius:4px; text-decoration:none; color:#007BFF; transition:0.2s; }
.pagination a:hover { background-color:#007BFF; color:#fff; }
.pagination a.active { background-color:#007BFF; color:#fff; border-color:#007BFF; }
</style>
