<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/header.php';

$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 5; // books per page
$offset = ($page - 1) * $limit;

// Build WHERE clause
$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(TITLE LIKE :search OR SUMMARY LIKE :search)";
    $params['search'] = "%$search%";
}
if (!empty($category)) {
    $where[] = "General_Category = :category";
    $params['category'] = $category;
}

$whereSQL = $where ? " WHERE " . implode(" AND ", $where) : "";

// Count total books
$countSql = "SELECT COUNT(*) FROM books $whereSQL";
$countStmt = $conn->prepare($countSql);
$countStmt->execute($params);
$totalBooks = (int)$countStmt->fetchColumn();

// Fetch paginated books
$sql = "SELECT * FROM books $whereSQL ORDER BY `Like` DESC LIMIT :limit OFFSET :offset";
$stmt = $conn->prepare($sql);
foreach ($params as $key => $val) {
    $stmt->bindValue(":$key", $val);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$books = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch comment counts per book title
$commentCounts = [];
$commentStmt = $conn->query("SELECT book_title, COUNT(*) as count FROM comments GROUP BY book_title");
foreach ($commentStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $commentCounts[$row['book_title']] = $row['count'];
}

// Total pages
$totalPages = ceil($totalBooks / $limit);
?>

<?php require __DIR__ . '/search_bar.php'; ?>

<div class="container mx-auto px-4 py-6">
  <h2 class="text-lg font-semibold mb-4"><?= $totalBooks ?> Book(s) Found</h2>

  <?php if ($search || $category): ?>
    <div class="mb-3">
      <a href="../index.php" class="text-blue-600 hover:underline">← Back to Home</a>
    </div>
  <?php endif; ?>

  <?php if (count($books) === 0): ?>
    <p class="text-red-600 font-medium">No results found.</p>
  <?php else: ?>
    <div class="results grid gap-4">
      <?php foreach ($books as $book): ?>
        <a href="/library-app/views/book_detail.php?title=<?= urlencode($book['TITLE']) ?>"
           class="card flex gap-4 p-4 border rounded-md bg-white shadow hover:shadow-md transition">
          <div class="thumbnail text-4xl">📘</div>
          <div class="info flex-1">
            <div class="meta text-lg font-semibold text-blue-700 mb-1">
              <?= htmlspecialchars($book['TITLE']) ?>
            </div>
            <div class="text-sm text-gray-600 mb-1">
              👤 <?= htmlspecialchars($book['AUTHOR'] ?? 'Unknown') ?><br>
              🔖 <?= htmlspecialchars($book['CALL NUMBER'] ?? 'N/A') ?><br>
              🏷 <?= htmlspecialchars($book['General_Category'] ?? 'Uncategorized') ?>
            </div>
            <div class="summary text-sm mt-2">
              <?= nl2br(htmlspecialchars($book['SUMMARY'] ?? 'No summary available.')) ?>
            </div>
            <div class="likes text-sm text-gray-700 mt-2">
              👍 <?= $book['Like'] ?? 0 ?> &nbsp; | 
              👎 <?= $book['Dislike'] ?? 0 ?> &nbsp; | 
              💬 <?= $commentCounts[$book['TITLE']] ?? 0 ?> comment<?= ($commentCounts[$book['TITLE']] ?? 0) == 1 ? '' : 's' ?>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
      <div class="pagination flex gap-2 mt-6 justify-center">
        <?php
        $range = 2; // how many pages around current
        $start = max(1, $page - $range);
        $end = min($totalPages, $page + $range);

        // Prev button
        if ($page > 1): ?>
          <a href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>&category=<?= urlencode($category) ?>" 
             class="px-3 py-1 border rounded bg-gray-100 hover:bg-gray-200">Prev</a>
        <?php endif; ?>

        <!-- Page numbers -->
        <?php for ($i = $start; $i <= $end; $i++): ?>
          <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&category=<?= urlencode($category) ?>" 
             class="px-3 py-1 border rounded <?= $i == $page ? 'bg-blue-500 text-white' : 'bg-gray-100 hover:bg-gray-200' ?>">
             <?= $i ?>
          </a>
        <?php endfor; ?>

        <!-- Next button -->
        <?php if ($page < $totalPages): ?>
          <a href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>&category=<?= urlencode($category) ?>" 
             class="px-3 py-1 border rounded bg-gray-100 hover:bg-gray-200">Next</a>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
