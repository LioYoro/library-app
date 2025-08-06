<title>Manage Books</title>

<?php include('../includes/header.php'); ?>
<?php include('../includes/sidebar.php'); ?>
<?php include('../db.php'); ?>

<?php
// Handle POST requests: edit book, delete single book, delete multiple books
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['edit_book'])) {
        $id = intval($_POST['No.']);
        $title = $conn->real_escape_string($_POST['TITLE']);
        $author = $conn->real_escape_string($_POST['AUTHOR']);
        $accession_no = $conn->real_escape_string($_POST['ACCESSION NO.']);
        $call_number = $conn->real_escape_string($_POST['CALL NUMBER']);
        $date_acquired = $conn->real_escape_string($_POST['DATE ACQUIRED']);
        $summary = $conn->real_escape_string($_POST['SUMMARY']);
        $keywords = $conn->real_escape_string($_POST['KEYWORDS']);
        $general_category = $conn->real_escape_string($_POST['General_Category']);
        $sub_category = $conn->real_escape_string($_POST['Sub_Category']);
        $likes = intval($_POST['Like']);      // Fixed names here
        $dislikes = intval($_POST['Dislike']);

        $sql = "UPDATE books SET
          TITLE='$title',
          AUTHOR='$author',
          ACCESSION NO.='$accession_no',
          CALL NUMBER='$call_number',
          DATE ACQUIRED='$date_acquired',
          SUMMARY='$summary',
          KEYWORDS='$keywords',
          General_Category='$general_category',
          Sub_Category='$sub_category',
          Like=$likes,
          Dislike=$dislikes
          WHERE id=$id";
        $conn->query($sql);
    }

    if (isset($_POST['delete_book'])) {
        $id = intval($_POST['No.']);
        $conn->query("DELETE FROM books WHERE No.=$id");
    }

    if (isset($_POST['delete_selected']) && !empty($_POST['selected_ids'])) {
        $ids = array_map('intval', $_POST['selected_ids']);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $conn->prepare("DELETE FROM books WHERE No. IN ($placeholders)");
        $types = str_repeat('i', count($ids));
        $stmt->bind_param($types, ...$ids);

        if ($stmt->execute()) {
            echo "<script>alert('Selected books deleted.'); window.location.href=window.location.href;</script>";
        } else {
            echo "<script>alert('Failed to delete books.'); window.location.href=window.location.href;</script>";
        }
        $stmt->close();
    }
}

// Pagination & Search parameters
$searchTerm = (isset($_GET['search']) && trim($_GET['search']) !== '') ? trim($_GET['search']) : null;
$rowsPerPage = isset($_GET['rows']) && intval($_GET['rows']) > 0 ? intval($_GET['rows']) : 5;
$page = isset($_GET['page']) && intval($_GET['page']) > 0 ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $rowsPerPage;

// Build search WHERE clause
$whereClause = "";
if (!is_null($searchTerm) && $searchTerm !== '') {
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

// Get total books count for pagination
$totalResult = $conn->query("SELECT COUNT(*) as total FROM books $whereClause");
$totalRow = $totalResult->fetch_assoc();
$totalBooks = $totalRow['total'];
$totalPages = ceil($totalBooks / $rowsPerPage);

// Get current page books with search and pagination
$sql = "SELECT * FROM books $whereClause ORDER BY TITLE ASC LIMIT $rowsPerPage OFFSET $offset";
$result = $conn->query($sql);
$books = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

// Highlight function for search term inside text (case-insensitive)
function highlight($text, $term) {
    if (!$term) return htmlspecialchars($text);
    $escapedTerm = preg_quote($term, '/');
    return preg_replace_callback("/($escapedTerm)/i", function($matches) {
        return '<mark>' . htmlspecialchars($matches[0]) . '</mark>';
    }, htmlspecialchars($text));
}
?>

<div class="flex h-screen overflow-hidden">
  <!-- Sidebar included -->

  <div id="main-content" class="flex-1 flex flex-col min-w-0 ml-[15rem] h-screen transition-all duration-300">

    <header class="h-16 w-full bg-blue-500 text-white flex items-center justify-between px-6 shadow">
      <h1 class="text-xl font-bold">📚 Manage Books </h1>
      <div class="flex items-center space-x-3">
        <span class="text-sm">ADMIN</span>
        <i class="fas fa-user-circle text-2xl"></i>
      </div>
    </header>

    <div class="p-4 space-y-6">
      <a href="../books.php" class="bg-gray-300 hover:bg-gray-400 text-black px-4 py-2 rounded inline-block">← Back to Books Dashboard</a>

      <div class="flex gap-4 max-w-4xl mb-6">
        <fieldset class="border border-gray-300 p-4 rounded flex-1">
          <legend class="font-semibold text-gray-700 mb-2">Import / Upload New Books (CSV)</legend>
          <form action="process_import.php" method="POST" enctype="multipart/form-data" class="flex gap-2 items-center">
            <input type="file" name="file" accept=".csv" required class="flex-grow border rounded px-2 py-1" />
            <button type="submit" name="import" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">Upload & Import</button>
          </form>
          <a href="add_book_form.php" class="mt-2 block text-sm text-green-700 hover:underline">+ Add Manually</a>
        </fieldset>

        <fieldset class="border border-gray-300 p-4 rounded flex-1">
          <legend class="font-semibold text-gray-700 mb-2">Upload CSV to Delete Books</legend>
          <form action="process_delete.php" method="POST" enctype="multipart/form-data" class="flex gap-2 items-center">
            <input type="file" name="file" accept=".csv" required class="flex-grow border rounded px-2 py-1" />
            <button type="submit" name="delete" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">Upload & Delete</button>
          </form>
        </fieldset>

        <fieldset class="border border-gray-300 p-4 rounded flex-1">
          <legend class="font-semibold text-gray-700 mb-2">Upload CSV to Update Books</legend>
          <form action="process_update.php" method="POST" enctype="multipart/form-data" class="flex gap-2 items-center">
            <input type="file" name="file" accept=".csv" required class="flex-grow border rounded px-2 py-1" />
            <button type="submit" name="update" class="bg-yellow-600 text-white px-4 py-2 rounded hover:bg-yellow-700">Upload & Update</button>
          </form>
        </fieldset>
      </div>

      <!-- Search and pagination controls -->
      <div class="flex justify-between items-center mb-3 max-w-4xl gap-2">
        <input
          type="text"
          id="searchInput"
          placeholder="Search books..."
          class="border rounded px-3 py-1 flex-grow max-w-sm"
          value="<?= htmlspecialchars($searchTerm) ?>"
          autocomplete="off"
        />
        <button id="searchBtn" class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded">Search</button>
        <button id="clearSearchBtn" class="bg-gray-400 hover:bg-gray-500 text-white px-3 py-1 rounded">Clear</button>
        <select id="rowsPerPage" class="border rounded px-3 py-1 ml-3">
          <option value="5" <?= $rowsPerPage == 5 ? 'selected' : '' ?>>5 rows</option>
          <option value="10" <?= $rowsPerPage == 10 ? 'selected' : '' ?>>10 rows</option>
          <option value="15" <?= $rowsPerPage == 15 ? 'selected' : '' ?>>15 rows</option>
          <option value="20" <?= $rowsPerPage == 20 ? 'selected' : '' ?>>20 rows</option>
          <option value="25" <?= $rowsPerPage == 25 ? 'selected' : '' ?>>25 rows</option>
        </select>
      </div>

      <!-- TOGGLE and CLOSE Buttons -->
      <div class="flex gap-2 items-center mb-2 max-w-4xl">
        <button id="toggleBooksBtn" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
          <span id="toggleIcon">▼</span> Show/Hide Books List (<?= count($books) ?>)
        </button>
        <button id="closeBooksBtn" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">Close</button>
      </div>

      <!-- Books Table -->
      <div id="booksList" class="overflow-auto max-h-[400px] border border-gray-300 rounded p-2">
        <form method="POST" onsubmit="return confirm('Delete selected books?');">
          <button type="submit" name="delete_selected" class="mb-2 px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700">
            Delete Selected
          </button>
          <table class="min-w-full text-sm border border-gray-300">
            <thead class="bg-gray-100 sticky top-0">
              <tr>
                <th class="border px-2 py-1"><input type="checkbox" id="select-all" /></th>
                <th class="border px-2 py-1">ID</th>
                <th class="border px-2 py-1">Title</th>
                <th class="border px-2 py-1">Author</th>
                <th class="border px-2 py-1">Accession No.</th>
                <th class="border px-2 py-1">Call No.</th>
                <th class="border px-2 py-1">Date Acquired</th>
                <th class="border px-2 py-1">Summary</th>
                <th class="border px-2 py-1">Keywords</th>
                <th class="border px-2 py-1">General Category</th>
                <th class="border px-2 py-1">Sub Category</th>
                <th class="border px-2 py-1">Likes</th>
                <th class="border px-2 py-1">Dislikes</th>
                <th class="border px-2 py-1">Actions</th>
              </tr>
            </thead>

            <tbody>
              <?php if (count($books) === 0): ?>
                <tr>
                  <td class="border px-2 py-4 text-center italic text-gray-600" colspan="14">
                    No books available yet.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($books as $book): ?>
                  <tr class="hover:bg-gray-50">
                    <td class="border px-2 py-1"><input type="checkbox" name="selected_ids[]" value="<?= $book['No.'] ?>" /></td>
                    <td class="border px-2 py-1"><?= htmlspecialchars($book['No.']) ?></td>
                    <td class="border px-2 py-1"><?= highlight($book['TITLE'], $searchTerm) ?></td>
                    <td class="border px-2 py-1"><?= highlight($book['AUTHOR'], $searchTerm) ?></td>
                    <td class="border px-2 py-1"><?= highlight($book['ACCESSION NO.'], $searchTerm) ?></td>
                    <td class="border px-2 py-1"><?= highlight($book['CALL NUMBER'], $searchTerm) ?></td>
                    <td class="border px-2 py-1"><?= highlight($book['DATE ACQUIRED'], $searchTerm) ?></td>
                    <td class="border px-2 py-1"><?= highlight($book['SUMMARY'], $searchTerm) ?></td>
                    <td class="border px-2 py-1"><?= highlight($book['KEYWORDS'], $searchTerm) ?></td>
                    <td class="border px-2 py-1"><?= highlight($book['General_Category'], $searchTerm) ?></td>
                    <td class="border px-2 py-1"><?= highlight($book['Sub_Category'], $searchTerm) ?></td>
                    <td class="border px-2 py-1"><?= $book['Like'] ?></td>
                    <td class="border px-2 py-1"><?= $book['Dislike'] ?></td>
                    <td class="border px-2 py-1 space-x-1">
                      <button type="button" class="editBtn bg-yellow-400 px-2 py-1 rounded hover:bg-yellow-500" data-id="<?= $book['No.'] ?>">Edit</button>
                      <form method="POST" class="inline" onsubmit="return confirm('Delete this book?');" style="display:inline;">
                        <input type="hidden" name="id" value="<?= $book['No.'] ?>" />
                        <button type="submit" name="delete_book" class="bg-red-600 px-2 py-1 text-white rounded hover:bg-red-700">Delete</button>
                      </form>
                    </td>
                  </tr>

                  <!-- Hidden Edit Form -->
                  <tr class="editFormRow hidden bg-gray-100" id="editFormRow-<?= $book['No.'] ?>">
                    <td colspan="14" class="p-2">
                      <form method="POST" class="space-y-2">
                        <input type="hidden" name="id" value="<?= $book['No.'] ?>" />
                        <div class="flex flex-wrap gap-2">
                          <input type="text" name="title" value="<?= htmlspecialchars($book['TITLE']) ?>" placeholder="Title" class="border rounded px-2 py-1 flex-grow" required />
                          <input type="text" name="author" value="<?= htmlspecialchars($book['AUTHOR']) ?>" placeholder="Author" class="border rounded px-2 py-1 flex-grow" required />
                          <input type="text" name="accession_no" value="<?= htmlspecialchars($book['ACCESSION NO.']) ?>" placeholder="Accession No." class="border rounded px-2 py-1 flex-grow" required />
                          <input type="text" name="call_number" value="<?= htmlspecialchars($book['CALL NUMBER']) ?>" placeholder="Call No." class="border rounded px-2 py-1 flex-grow" />
                          <input type="date" name="date_acquired" value="<?= htmlspecialchars($book['DATE ACQUIRED']) ?>" class="border rounded px-2 py-1" />
                        </div>
                        <div class="flex flex-wrap gap-2 mt-2">
                          <input type="text" name="summary" value="<?= htmlspecialchars($book['SUMMARY']) ?>" placeholder="Summary" class="border rounded px-2 py-1 flex-grow" />
                          <input type="text" name="keywords" value="<?= htmlspecialchars($book['KEYWORDS ']) ?>" placeholder="Keywords" class="border rounded px-2 py-1 flex-grow" />
                          <input type="text" name="general_category" value="<?= htmlspecialchars($book['General_Category']) ?>" placeholder="Category" class="border rounded px-2 py-1 flex-grow" />
                          <input type="text" name="sub_category" value="<?= htmlspecialchars($book['Sub_Category']) ?>" placeholder="Subcategory" class="border rounded px-2 py-1 flex-grow" />
                          <input type="number" name="likes" value="<?= $book['likes'] ?>" placeholder="Like" class="border rounded px-2 py-1 w-20" />
                          <input type="number" name="dislikes" value="<?= $book['dislikes'] ?>" placeholder="Dislike" class="border rounded px-2 py-1 w-20" />
                        </div>
                        <div class="mt-2">
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
                <a href="?page=<?= $page - 1 ?>&rows=<?= $rowsPerPage ?>&search=<?= urlencode($searchTerm) ?>" class="px-3 py-1 border rounded hover:bg-gray-200">Prev</a>
              <?php endif; ?>
              <span>Page <?= $page ?> of <?= $totalPages ?></span>
              <?php if ($page < $totalPages): ?>
                <a href="?page=<?= $page + 1 ?>&rows=<?= $rowsPerPage ?>&search=<?= urlencode($searchTerm) ?>" class="px-3 py-1 border rounded hover:bg-gray-200">Next</a>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </form>
      </div>
    </div>
  </div>
</div>

<?php include('../includes/footer.php'); ?>

<script>
// Toggle books list show/hide
const toggleBtn = document.getElementById('toggleBooksBtn');
const closeBtn = document.getElementById('closeBooksBtn');
const booksList = document.getElementById('booksList');
const toggleIcon = document.getElementById('toggleIcon');

toggleBtn.addEventListener('click', () => {
  if (booksList.style.display === 'none' || booksList.style.display === '') {
    booksList.style.display = 'block';
    toggleIcon.textContent = '▼';
  } else {
    booksList.style.display = 'none';
    toggleIcon.textContent = '▶';
  }
});

closeBtn.addEventListener('click', () => {
  booksList.style.display = 'none';
  toggleIcon.textContent = '▶';
});

// Search and rows per page
const searchInput = document.getElementById('searchInput');
const searchBtn = document.getElementById('searchBtn');
const clearSearchBtn = document.getElementById('clearSearchBtn');
const rowsSelect = document.getElementById('rowsPerPage');

// Function to reload page with given params
function reloadWithParams(params) {
  const url = new URL(window.location.href);
  for (const key in params) {
    if (params[key] === '' || params[key] === null || params[key] === undefined) {
      url.searchParams.delete(key);
    } else {
      url.searchParams.set(key, params[key]);
    }
  }
  url.searchParams.delete('page'); // Reset page to 1 on new search or rows change
  window.location.href = url.toString();
}

// Search on button click or Enter press
searchBtn.addEventListener('click', () => {
  reloadWithParams({ search: searchInput.value.trim() });
});
searchInput.addEventListener('keydown', e => {
  if (e.key === 'Enter') {
    e.preventDefault();
    reloadWithParams({ search: searchInput.value.trim() });
  }
});

// Clear search and reload to show all books
clearSearchBtn.addEventListener('click', () => {
  searchInput.value = '';
  reloadWithParams({ search: '' });
});

// Change rows per page reloads page with new rows param
rowsSelect.addEventListener('change', () => {
  reloadWithParams({ rows: rowsSelect.value });
});

// Select all checkboxes functionality
const selectAllCheckbox = document.getElementById('select-all');
selectAllCheckbox.addEventListener('change', () => {
  const checkboxes = document.querySelectorAll('input[name="selected_ids[]"]');
  checkboxes.forEach(cb => cb.checked = selectAllCheckbox.checked);
});

// Edit buttons toggle edit forms
document.querySelectorAll('.editBtn').forEach(button => {
  button.addEventListener('click', () => {
    const bookId = button.dataset.id;
    const editRow = document.getElementById('editFormRow-' + bookId);
    if (editRow.classList.contains('hidden')) {
      // Hide any other open edit forms first
      document.querySelectorAll('.editFormRow').forEach(row => row.classList.add('hidden'));
      editRow.classList.remove('hidden');
      editRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
    } else {
      editRow.classList.add('hidden');
    }
  });
});

// Cancel edit buttons hide edit form
document.querySelectorAll('.cancelEditBtn').forEach(button => {
  button.addEventListener('click', () => {
    button.closest('tr.editFormRow').classList.add('hidden');
  });
});
</script>
