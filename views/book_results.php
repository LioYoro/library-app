<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/header.php';

$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';

// Build search query
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

$sql = "SELECT * FROM books";
if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY `Like` DESC";

$stmt = $conn->prepare($sql);aw 
$stmt->execute($params);
$books = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mx-auto px-4 py-6">
  <h2 class="text-lg font-semibold mb-4"><?= count($books) ?> Book(s) Found</h2>

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
        <a href="book_detail.php?title=<?= urlencode($book['TITLE']) ?>" class="card flex gap-4 p-4 border rounded-md bg-white shadow hover:shadow-md transition">
          <div class="thumbnail text-4xl">
            📘
          </div>
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
              👍 <?= $book['Like'] ?? 0 ?> &nbsp; | &nbsp; 👎 <?= $book['Dislike'] ?? 0 ?>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
