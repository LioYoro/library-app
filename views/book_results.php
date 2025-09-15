<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/header.php';


// Input Handling + Validation
// =======================
$search = trim($_GET['search'] ?? '');
$filterBy = $_GET['filter_by'] ?? 'TITLE';
$since_year = $_GET['since_year'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

$errors = [];

// Validate search text
if (!empty($search)) {
    if (strlen($search) < 2) {
        $errors[] = "Search term must be at least 2 characters.";
        $search = "";
    } elseif (strlen($search) > 100) {
        $errors[] = "Search term cannot exceed 100 characters.";
        $search = substr($search, 0, 100); // truncate safely
    }
}

// Validate since_year
if (!empty($since_year)) {
    $year = (int)$since_year;
    $currentYear = (int)date("Y");
    if ($year < 1900 || $year > $currentYear) {
        $errors[] = "Invalid year filter.";
        $since_year = "";
    }
}

// =======================
// Build WHERE Clause
// =======================
$where = [];
$params = [];

if (!empty($search)) {
    switch ($filterBy) {
        case 'TITLE':
            $where[] = "TITLE LIKE :search";
            break;
        case 'AUTHOR':
            $where[] = "AUTHOR LIKE :search";
            break;
        case 'ACCESSION NO.':
            $where[] = "`ACCESSION NO.` LIKE :search";
            break;
        case 'CALL NUMBER':
            $where[] = "`CALL NUMBER` LIKE :search";
            break;
        case 'KEYWORDS':
            if (strlen($search) >= 3) {
                $where[] = "KEYWORDS LIKE :search";
            }
            break;
        case 'General_Category':
            $where[] = "General_Category = :search";
            break;
        default:
            $where[] = "(TITLE LIKE :search OR SUMMARY LIKE :search)";
    }
    $params['search'] = "%$search%";
}

if (!empty($since_year)) {
    $where[] = "YEAR(STR_TO_DATE(`DATE ACQUIRED`, '%Y-%m-%d')) >= :since_year";
    $params['since_year'] = (int)$since_year;
}

$whereSQL = $where ? " WHERE " . implode(" AND ", $where) : "";

// =======================
// Fetch Data
// =======================
$countSql = "SELECT COUNT(*) FROM books $whereSQL";
$countStmt = $conn->prepare($countSql);
$countStmt->execute($params);
$totalBooks = (int)$countStmt->fetchColumn();

$sql = "SELECT * FROM books $whereSQL ORDER BY `Like` DESC LIMIT :limit OFFSET :offset";
$stmt = $conn->prepare($sql);
foreach ($params as $key => $val) {
    $stmt->bindValue(":$key", $val);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$books = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Comment counts
$commentCounts = [];
$commentStmt = $conn->query("SELECT book_title, COUNT(*) as count FROM comments GROUP BY book_title");
foreach ($commentStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $commentCounts[$row['book_title']] = $row['count'];
}

$totalPages = ceil($totalBooks / $limit);
?>

<div class="flex">
  <!-- Sidebar -->
  <div class="w-60 p-4 border-r bg-gray-50">
    <h3 class="font-semibold mb-3">Filter by Year</h3>
    <ul class="space-y-2 text-blue-600">
      <li><a href="?search=<?=urlencode($search)?>&filter_by=<?=$filterBy?>&since_year=2010" class="hover:underline">Since 2010</a></li>
      <li><a href="?search=<?=urlencode($search)?>&filter_by=<?=$filterBy?>&since_year=2015" class="hover:underline">Since 2015</a></li>
      <li><a href="?search=<?=urlencode($search)?>&filter_by=<?=$filterBy?>&since_year=2020" class="hover:underline">Since 2020</a></li>
      <li><a href="?search=<?=urlencode($search)?>&filter_by=<?=$filterBy?>&since_year=2023" class="hover:underline">Since 2023</a></li>
    </ul>
  </div>

  <!-- Main Content -->
  <div class="flex-1 p-6">
    <!-- Search bar -->
      <form method="GET" class="flex gap-2 mb-6 items-center relative">
        <div class="relative flex-1">
          <input type="text" name="search" id="searchInput"
                value="<?=htmlspecialchars($search)?>" 
                placeholder="Search books..."
                class="border p-2 w-full rounded pr-8">

          <!-- Clear Button (X) inside search bar -->
          <?php if (!empty($search)): ?>
            <button type="button" 
                    onclick="document.getElementById('searchInput').value=''; document.getElementById('searchInput').focus();" 
                    class="absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-red-600">
          ✖
            </button>
            <?php endif; ?>
          </div>

          <select name="filter_by" class="border p-2 rounded">
            <option value="TITLE" <?= $filterBy=='TITLE'?'selected':'' ?>>Title</option>
            <option value="AUTHOR" <?= $filterBy=='AUTHOR'?'selected':'' ?>>Author</option>
            <option value="ACCESSION NO." <?= $filterBy=='ACCESSION NO.'?'selected':'' ?>>Accession No.</option>
            <option value="CALL NUMBER" <?= $filterBy=='CALL NUMBER'?'selected':'' ?>>Call Number</option>
            <option value="KEYWORDS" <?= $filterBy=='KEYWORDS'?'selected':'' ?>>Keywords</option>
            <option value="General_Category" <?= $filterBy=='General_Category'?'selected':'' ?>>Category</option>
          </select>

          <button type="submit" class="bg-blue-500 text-white px-4 rounded">Search</button>
        </form>

    <!-- Errors -->
    <?php if (!empty($errors)): ?>
      <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-2 rounded mb-4">
        <ul class="list-disc pl-5">
          <?php foreach ($errors as $err): ?>
            <li><?= htmlspecialchars($err) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <h2 class="text-lg font-semibold mb-4"><?= $totalBooks ?> Book(s) Found</h2>

    <?php if (count($books) === 0): ?>
      <p class="text-red-600 font-medium">No results found.</p>
    <?php else: ?>
      <!-- Results -->
      <div class="results space-y-6">
        <?php foreach ($books as $book): ?>
          <div class="result">
            <!-- Title -->
            <a href="/library-app/views/book_detail.php?title=<?=urlencode($book['TITLE'])?>" 
               class="text-blue-700 font-semibold hover:underline text-lg">
              <?= htmlspecialchars($book['TITLE']) ?>
            </a>
            <!-- Meta -->
            <div class="text-sm text-gray-600">
              <?= htmlspecialchars($book['AUTHOR'] ?? 'Unknown') ?> · 
              <?= htmlspecialchars($book['DATE ACQUIRED'] ?? 'N/A') ?> · 
              <?= htmlspecialchars($book['General_Category'] ?? 'Uncategorized') ?>
            </div>
            <!-- Summary -->
            <div class="text-sm text-gray-800 mt-1">
              <?= htmlspecialchars(substr($book['SUMMARY'] ?? 'No summary available.', 0, 250)) ?>...
            </div>
            <!-- Stats -->
            <div class="text-xs text-gray-500 mt-1">
              👍 <?= $book['Like'] ?? 0 ?> | 👎 <?= $book['Dislike'] ?? 0 ?> | 
              💬 <?= $commentCounts[$book['TITLE']] ?? 0 ?> comment<?= ($commentCounts[$book['TITLE']] ?? 0) == 1 ? '' : 's' ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Pagination -->
      <?php if ($totalPages > 1): ?>
        <div class="pagination flex gap-2 mt-6 justify-center">
          <?php if ($page > 1): ?>
            <a href="?page=<?=$page-1?>&search=<?=urlencode($search)?>&filter_by=<?=$filterBy?>&since_year=<?=$since_year?>"
               class="px-3 py-1 border rounded bg-gray-100 hover:bg-gray-200">Prev</a>
          <?php endif; ?>
          <?php for ($i = max(1,$page-2); $i <= min($totalPages,$page+2); $i++): ?>
            <a href="?page=<?=$i?>&search=<?=urlencode($search)?>&filter_by=<?=$filterBy?>&since_year=<?=$since_year?>"
               class="px-3 py-1 border rounded <?= $i==$page?'bg-blue-500 text-white':'bg-gray-100 hover:bg-gray-200' ?>">
               <?=$i?>
            </a>
          <?php endfor; ?>
          <?php if ($page < $totalPages): ?>
            <a href="?page=<?=$page+1?>&search=<?=urlencode($search)?>&filter_by=<?=$filterBy?>&since_year=<?=$since_year?>"
               class="px-3 py-1 border rounded bg-gray-100 hover:bg-gray-200">Next</a>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
