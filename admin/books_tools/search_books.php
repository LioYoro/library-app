<?php
include('../db.php');

// --- Search & Pagination ---
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
$rowsPerPage = isset($_GET['rows']) && intval($_GET['rows']) > 0 ? intval($_GET['rows']) : 5;
$page = isset($_GET['page']) && intval($_GET['page']) > 0 ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $rowsPerPage;

$whereClause = "";
if ($searchTerm !== '') {
    $searchEscaped = $conn->real_escape_string($searchTerm);
    $like = "%$searchEscaped%";
    $whereClause = " WHERE
      TITLE LIKE '$like' OR
      AUTHOR LIKE '$like' OR
      `ACCESSION NO.` LIKE '$like' OR
      `CALL NUMBER` LIKE '$like' OR
      SUMMARY LIKE '$like' OR
      `KEYWORDS` LIKE '$like' OR
      General_Category LIKE '$like' OR
      Sub_Category LIKE '$like'";
}

$totalResult = $conn->query("SELECT COUNT(*) as total FROM books $whereClause");
$totalRow = $totalResult->fetch_assoc();
$totalBooks = $totalRow['total'];
$totalPages = ceil($totalBooks / $rowsPerPage);

$sql = "SELECT * FROM books $whereClause ORDER BY TITLE ASC LIMIT $rowsPerPage OFFSET $offset";
$result = $conn->query($sql);
$books = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

function highlight($text, $term) {
    if (!$term) return htmlspecialchars($text);
    $escapedTerm = preg_quote($term, '/');
    return preg_replace_callback("/($escapedTerm)/i", function($matches) {
        return '<mark>' . htmlspecialchars($matches[0]) . '</mark>';
    }, htmlspecialchars($text));
}
?>

<table class="min-w-full text-sm border border-gray-300">
  <thead class="bg-gray-100 sticky top-0">
    <tr>
      <th class="border px-2 py-1"><input type="checkbox" id="select-all" /></th>
      <th class="border px-2 py-1">ID</th>
      <th class="border px-2 py-1">Title</th>
      <th class="border px-2 py-1">Author</th>
      <th class="border px-2 py-1">Likes</th>
      <th class="border px-2 py-1">Dislikes</th>
      <th class="border px-2 py-1">Actions</th>
    </tr>
  </thead>
  <tbody>
    <?php if (count($books) === 0): ?>
      <tr>
        <td colspan="7" class="border px-2 py-4 text-center italic text-gray-600">
          No matching books found.
        </td>
      </tr>
    <?php else: ?>
      <?php foreach ($books as $book): ?>
        <tr class="hover:bg-gray-50 even:bg-gray-100">
          <td class="border px-2 py-1"><input type="checkbox" name="selected_ids[]" value="<?= $book['id'] ?>" /></td>
          <td class="border px-2 py-1"><?= $book['id'] ?></td>
          <td class="border px-2 py-1"><?= highlight($book['TITLE'], $searchTerm) ?></td>
          <td class="border px-2 py-1"><?= highlight($book['AUTHOR'], $searchTerm) ?></td>
          <td class="border px-2 py-1"><?= $book['Like'] ?></td>
          <td class="border px-2 py-1"><?= $book['Dislike'] ?></td>
          <td class="border px-2 py-1 space-x-1">
            <button type="button" class="editBtn bg-yellow-400 px-2 py-1 rounded hover:bg-yellow-500" data-id="<?= $book['id'] ?>">Edit</button>
            <form method="POST" class="inline" onsubmit="return confirmDelete();">
              <input type="hidden" name="id" value="<?= $book['id'] ?>" />
              <button type="submit" name="delete_book" class="bg-red-600 px-2 py-1 text-white rounded hover:bg-red-700">Delete</button>
            </form>
          </td>
        </tr>

        <!-- Hidden edit form row -->
        <tr class="editFormRow hidden bg-gray-100" id="editFormRow-<?= $book['id'] ?>">
          <td colspan="7" class="p-2">
            <form method="POST" class="space-y-2">
              <input type="hidden" name="id" value="<?= $book['id'] ?>" />
              <h3 class="font-semibold text-blue-700 mb-3">Editing Book: <?= htmlspecialchars($book['TITLE']) ?></h3>

              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700">Title</label>
                  <input type="text" name="title" value="<?= htmlspecialchars($book['TITLE']) ?>" class="border rounded px-2 py-1 w-full" required />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Author</label>
                  <input type="text" name="author" value="<?= htmlspecialchars($book['AUTHOR']) ?>" class="border rounded px-2 py-1 w-full" required />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Accession No.</label>
                  <input type="text" name="accession_no" value="<?= htmlspecialchars($book['ACCESSION NO.']) ?>" class="border rounded px-2 py-1 w-full" required />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Call Number</label>
                  <input type="text" name="call_number" value="<?= htmlspecialchars($book['CALL NUMBER']) ?>" class="border rounded px-2 py-1 w-full" />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Date Acquired</label>
                  <input type="date" name="date_acquired" value="<?= htmlspecialchars($book['DATE ACQUIRED']) ?>" class="border rounded px-2 py-1 w-full" />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Summary</label>
                  <input type="text" name="summary" value="<?= htmlspecialchars($book['SUMMARY']) ?>" class="border rounded px-2 py-1 w-full" />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Keywords</label>
                  <input type="text" name="keywords" value="<?= htmlspecialchars($book['KEYWORDS']) ?>" class="border rounded px-2 py-1 w-full" />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">General Category</label>
                  <input type="text" name="general_category" value="<?= htmlspecialchars($book['General_Category']) ?>" class="border rounded px-2 py-1 w-full" />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Sub Category</label>
                  <input type="text" name="sub_category" value="<?= htmlspecialchars($book['Sub_Category']) ?>" class="border rounded px-2 py-1 w-full" />
                </div>
                <!-- Likes/Dislikes shown but NOT editable -->
                <div>
                  <label class="block text-sm font-medium text-gray-700">Likes</label>
                  <input type="number" value="<?= htmlspecialchars($book['Like']) ?>" class="border rounded px-2 py-1 w-full bg-gray-100" readonly />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Dislikes</label>
                  <input type="number" value="<?= htmlspecialchars($book['Dislike']) ?>" class="border rounded px-2 py-1 w-full bg-gray-100" readonly />
                </div>
              </div>

              <div class="mt-3">
                <button type="submit" name="edit_book" class="bg-blue-600 text-white px-4 py-1 rounded hover:bg-blue-700">Save</button>
                <button type="button" class="cancelEditBtn bg-gray-400 px-4 py-1 rounded hover:bg-gray-500 ml-2">Cancel</button>
              </div>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </tbody>
</table>

<?php if ($totalPages > 1): ?>
  <div class="flex justify-center space-x-4 mt-3">
    <?php if ($page > 1): ?>
      <a href="search_books.php?page=<?= $page - 1 ?>&rows=<?= $rowsPerPage ?>&search=<?= urlencode($searchTerm) ?>" class="px-3 py-1 border rounded hover:bg-gray-200">Prev</a>
    <?php endif; ?>
    <span>Page <?= $page ?> of <?= $totalPages ?></span>
    <?php if ($page < $totalPages): ?>
      <a href="search_books.php?page=<?= $page + 1 ?>&rows=<?= $rowsPerPage ?>&search=<?= urlencode($searchTerm) ?>" class="px-3 py-1 border rounded hover:bg-gray-200">Next</a>
    <?php endif; ?>
  </div>
<?php endif; ?>
