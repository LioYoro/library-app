<?php
session_start();
include('../includes/header.php');
include('../includes/sidebar.php');
include('../db.php');

// ------------------ POST HANDLING ------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Edit book
    if (isset($_POST['edit_book'])) {
        $id = intval($_POST['id']);
        $title = $conn->real_escape_string($_POST['title']);
        $author = $conn->real_escape_string($_POST['author']);
        $accession_no = $conn->real_escape_string($_POST['accession_no']);
        $call_number = $conn->real_escape_string($_POST['call_number']);
        $date_acquired = $conn->real_escape_string($_POST['date_acquired']);
        $summary = $conn->real_escape_string($_POST['summary']);
        $keywords = $conn->real_escape_string($_POST['keywords']);
        $general_category = $conn->real_escape_string($_POST['general_category']);
        
     
        $sql = "UPDATE books SET
          TITLE='$title',
          AUTHOR='$author',
          `ACCESSION NO.`='$accession_no',
          `CALL NUMBER`='$call_number',
          `DATE ACQUIRED`='$date_acquired',
          SUMMARY='$summary',
          `KEYWORDS`='$keywords',
          General_Category='$general_category'
          
          WHERE id=$id";

        if ($conn->query($sql)) {
            $toastMsg = "Book updated successfully.";
            $toastType = "success";
        } else {
            $toastMsg = "Failed to update book.";
            $toastType = "error";
        }
    }

    // Delete single book
    if (isset($_POST['delete_book'])) {
        $id = intval($_POST['id']);
        $res = $conn->query("SELECT * FROM books WHERE id=$id");
        $deletedBook = $res->fetch_assoc();
        if ($deletedBook) {
            $_SESSION['last_deleted'] = $deletedBook;
            $_SESSION['undo_expiry'] = time() + 10;
            $conn->query("DELETE FROM books WHERE id=$id");
            $toastMsg = "Book deleted. You can undo for 10s.";
            $toastType = "undo";
        }
    }

    // Undo delete
    if (isset($_POST['undo_delete']) && isset($_SESSION['last_deleted']) && time() < $_SESSION['undo_expiry']) {
        $book = $_SESSION['last_deleted'];
        $stmt = $conn->prepare("INSERT INTO books 
            (id, TITLE, AUTHOR, `ACCESSION NO.`, `CALL NUMBER`, `DATE ACQUIRED`, SUMMARY, `KEYWORDS`, General_Category) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssssss",
            $book['id'], $book['TITLE'], $book['AUTHOR'], $book['ACCESSION NO.'],
            $book['CALL NUMBER'], $book['DATE ACQUIRED'], $book['SUMMARY'], $book['KEYWORDS'],
            $book['General_Category']
        );
        $stmt->execute();
        unset($_SESSION['last_deleted'], $_SESSION['undo_expiry']);
        $toastMsg = "Book restored successfully.";
        $toastType = "success";
    }

        if (isset($_POST['delete_selected']) && !empty($_POST['selected_ids'])) {
        $ids = array_map('intval', $_POST['selected_ids']);
        $placeholders = implode(',', $ids);

        // Fetch deleted books first (so we can restore them)
        $res = $conn->query("SELECT * FROM books WHERE id IN ($placeholders)");
        $deletedBooks = $res->fetch_all(MYSQLI_ASSOC);

        if ($conn->query("DELETE FROM books WHERE id IN ($placeholders)")) {
            $_SESSION['last_deleted_bulk'] = $deletedBooks;
            $_SESSION['undo_bulk_expiry'] = time() + 10; // 10s undo window

            $toastMsg = "Selected books deleted. You can undo for 10s.";
            $toastType = "undo_bulk";
        } else {
            $toastMsg = "Failed to delete selected books.";
            $toastType = "error";
        }
    }
    if (isset($_POST['undo_bulk_delete']) && isset($_SESSION['last_deleted_bulk']) && time() < $_SESSION['undo_bulk_expiry']) {
    foreach ($_SESSION['last_deleted_bulk'] as $book) {
        $stmt = $conn->prepare("INSERT INTO books 
            (id, TITLE, AUTHOR, `ACCESSION NO.`, `CALL NUMBER`, `DATE ACQUIRED`, SUMMARY, `KEYWORDS`, General_Category) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssssss",
            $book['id'],
            $book['TITLE'],
            $book['AUTHOR'],
            $book['ACCESSION NO.'],
            $book['CALL NUMBER'],
            $book['DATE ACQUIRED'],
            $book['SUMMARY'],
            $book['KEYWORDS'],
            $book['General_Category']
        );
        $stmt->execute();
    }
    unset($_SESSION['last_deleted_bulk'], $_SESSION['undo_bulk_expiry']);
    $toastMsg = "Books restored successfully.";
    $toastType = "success";
}

}

// ------------------ SEARCH & PAGINATION ------------------
$searchTerm = (isset($_GET['search']) && trim($_GET['search']) !== '') ? trim($_GET['search']) : null;
$rowsPerPage = isset($_GET['rows']) && intval($_GET['rows']) > 0 ? intval($_GET['rows']) : 5;
$page = isset($_GET['page']) && intval($_GET['page']) > 0 ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $rowsPerPage;

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
      General_Category LIKE '$like'";
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

<div class="flex h-screen overflow-hidden">
  <div id="main-content" class="flex-1 flex flex-col min-w-0 ml-[15rem] h-screen transition-all duration-300">
    <header class="h-16 w-full bg-blue-500 text-white flex items-center justify-between px-6 shadow">
      <h1 class="text-xl font-bold">📚 Manage Books </h1>
      <div class="flex items-center space-x-3">
        <span class="text-sm">ADMIN</span>
        <i class="fas fa-user-circle text-2xl"></i>
      </div>
    </header>

    <div class="grid grid-cols-3 gap-4 p-4">
      <!-- Left: Tools -->
      <div class="space-y-4">
        <a href="../books.php" class="bg-gray-300 hover:bg-gray-400 text-black px-4 py-2 rounded inline-block">← Back</a>
        <fieldset class="border border-gray-300 p-4 rounded">
          <legend class="font-semibold text-gray-700 mb-2">Import Books</legend>
          <form action="process_import.php" method="POST" enctype="multipart/form-data" onsubmit="return validateFile(this)">
            <input type="file" name="file" accept=".csv,.xls,.xlsx" required class="w-full mb-2 border rounded px-2 py-1" />
            <button type="submit" name="import" class="bg-green-600 text-white px-4 py-1 rounded hover:bg-green-700 w-full">Upload</button>
          </form>
        </fieldset>
        <fieldset class="border border-gray-300 p-4 rounded">
          <legend class="font-semibold text-gray-700 mb-2">Delete via CSV</legend>
          <form action="process_delete.php" method="POST" enctype="multipart/form-data" onsubmit="return validateFile(this)">
            <input type="file" name="file" accept=".csv,.xls,.xlsx" required class="w-full mb-2 border rounded px-2 py-1" />
            <button type="submit" name="delete" class="bg-red-600 text-white px-4 py-1 rounded hover:bg-red-700 w-full">Delete</button>
          </form>
        </fieldset>
        <fieldset class="border border-gray-300 p-4 rounded">
          <legend class="font-semibold text-gray-700 mb-2">Update via CSV</legend>
          <form action="process_update.php" method="POST" enctype="multipart/form-data" onsubmit="return validateFile(this)">
            <input type="file" name="file" accept=".csv,.xls,.xlsx" required class="w-full mb-2 border rounded px-2 py-1" />
            <button type="submit" name="update" class="bg-yellow-600 text-white px-4 py-1 rounded hover:bg-yellow-700 w-full">Update</button>
          </form>
        </fieldset>
      </div>

      <!-- Center: Books -->
      <div>
        <div class="flex justify-between items-center mb-3 gap-2">
          <input type="text" id="searchInput" placeholder="Search books..." class="border rounded px-3 py-1 flex-grow max-w-sm"
            value="<?= htmlspecialchars($searchTerm) ?>" autocomplete="off" />
          <select id="rowsPerPage" class="border rounded px-3 py-1 ml-3">
            <option value="5" <?= $rowsPerPage == 5 ? 'selected' : '' ?>>5 rows</option>
            <option value="10" <?= $rowsPerPage == 10 ? 'selected' : '' ?>>10 rows</option>
            <option value="15" <?= $rowsPerPage == 15 ? 'selected' : '' ?>>15 rows</option>
            <option value="20" <?= $rowsPerPage == 20 ? 'selected' : '' ?>>20 rows</option>
            <option value="25" <?= $rowsPerPage == 25 ? 'selected' : '' ?>>25 rows</option>
          </select>
        </div>

        <form method="POST" onsubmit="return confirmBulkDelete();">

          <button type="submit" name="delete_selected" class="mb-2 px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700">Delete Selected</button>
          <div id="booksList" class="overflow-auto max-h-[500px] border border-gray-300 rounded p-2">
            <table class="min-w-full text-sm border border-gray-300">
              <thead class="bg-gray-100 sticky top-0">
                <tr>
                  <th class="border px-2 py-1"><input type="checkbox" id="select-all" /></th>
                  <th class="border px-2 py-1">ID</th>
                  <th class="border px-2 py-1">Title</th>
                  <th class="border px-2 py-1">Author</th>
                  <th class="border px-2 py-1">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (count($books) === 0): ?>
                  <tr>
                    <td colspan="5" class="border px-2 py-4 text-center italic text-gray-600">No books found.</td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($books as $book): ?>
                    <tr class="hover:bg-gray-50">
                      <td class="border px-2 py-1"><input type="checkbox" name="selected_ids[]" value="<?= $book['id'] ?>" /></td>
                      <td class="border px-2 py-1"><?= $book['id'] ?></td>
                      <td class="border px-2 py-1"><?= highlight($book['TITLE'], $searchTerm) ?></td>
                      <td class="border px-2 py-1"><?= highlight($book['AUTHOR'], $searchTerm) ?></td>
                      <td class="border px-2 py-1 space-x-1">
                        <button type="button" class="editBtn bg-yellow-400 px-2 py-1 rounded hover:bg-yellow-500" data-id="<?= $book['id'] ?>">Edit</button>
                        <form method="POST" class="inline" onsubmit="return confirmDelete();">
                          <input type="hidden" name="id" value="<?= $book['id'] ?>" />
                          <button type="submit" name="delete_book" class="bg-red-600 px-2 py-1 text-white rounded hover:bg-red-700">Delete</button>
                        </form>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
<?php if (empty($searchTerm)): ?>
  <?php if ($totalPages > 1): ?>
    <div class="flex justify-center space-x-4 mt-3">
      <?php if ($page > 1): ?>
        <a href="?page=<?= $page - 1 ?>&rows=<?= $rowsPerPage ?>" 
           class="px-3 py-1 border rounded hover:bg-gray-200">Prev</a>
      <?php endif; ?>

      <span>Page <?= $page ?> of <?= $totalPages ?></span>

      <?php if ($page < $totalPages): ?>
        <a href="?page=<?= $page + 1 ?>&rows=<?= $rowsPerPage ?>" 
           class="px-3 py-1 border rounded hover:bg-gray-200">Next</a>
      <?php endif; ?>
    </div>
  <?php endif; ?>
<?php endif; ?>

          </div>
        </form>
      </div>

      <!-- Right panel -->
<!-- Right Edit Panel -->
<div id="editPanel" class="w-[400px] p-4 border-l border-gray-300 bg-white overflow-y-auto">
  <p class="text-gray-500">📖 Please choose a book to edit.</p>
</div>



    </div>
  </div>
</div>

<?php include('../includes/footer.php'); ?>

<!-- Toast -->
<div id="toast" class="hidden fixed bottom-5 right-5 bg-gray-800 text-white px-4 py-2 rounded shadow"></div>

<script>
// File validation
function validateFile(form) {
  const fileInput = form.querySelector('input[type="file"]');
  if (!fileInput.value) return true;
  const allowed = [".csv", ".xls", ".xlsx"];
  const ext = fileInput.value.substring(fileInput.value.lastIndexOf(".")).toLowerCase();
  if (!allowed.includes(ext)) {
    showToast("Only CSV or Excel files are allowed.", "error");
    return false;
  }
  return true;
}

function confirmDelete() {
  return confirm("⚠️ WARNING: You might delete the only copy of this book.\nMake sure you have a backup.\nProceed?");
}

// Toasts
function showToast(message, type="info") {
  const toast = document.getElementById("toast");
  toast.textContent = message;
  toast.className = `fixed bottom-5 right-5 px-4 py-2 rounded shadow text-white ${
    type === "success" ? "bg-green-600" :
    type === "error" ? "bg-red-600" : "bg-gray-800"
  }`;
  toast.classList.remove("hidden");
  setTimeout(() => toast.classList.add("hidden"), 3000);
}

function showUndo(message) {
  const toast = document.getElementById("toast");
  let countdown = 10;
  toast.innerHTML = `${message} 
    <form method="POST" class="inline">
      <button type="submit" name="undo_delete" class="ml-2 bg-green-600 px-2 py-1 rounded">Undo (<span id="undoTimer">10</span>s)</button>
    </form>`;
  toast.className = "fixed bottom-5 right-5 px-4 py-2 rounded shadow text-white bg-red-600";
  toast.classList.remove("hidden");
  const timer = setInterval(() => {
    countdown--;
    document.getElementById("undoTimer").textContent = countdown;
    if (countdown <= 0) {
      clearInterval(timer);
      toast.classList.add("hidden");
    }
  }, 1000);
}

// Attach events
function attachEventListeners() {
  // Select all
  const selectAll = document.getElementById('select-all');
  if (selectAll) {
    selectAll.addEventListener('change', e => {
      document.querySelectorAll('input[name="selected_ids[]"]').forEach(cb => cb.checked = e.target.checked);
    });
  }

// Edit buttons
document.querySelectorAll('.editBtn').forEach(button => {
  button.addEventListener('click', () => {
    const bookId = button.dataset.id;

    fetch("load_edit_form.php?id=" + bookId)
      .then(res => res.text())
      .then(html => {
        document.getElementById("editPanel").innerHTML = html;
      });
  });
});

}

// --- Live search ---
const searchInput = document.getElementById('searchInput');
const booksList = document.getElementById('booksList');

searchInput.addEventListener("input", () => {
  const query = searchInput.value.trim();
  if (query.length > 0) {
    fetch("search_books.php?search=" + encodeURIComponent(query))
      .then(res => res.text())
      .then(html => {
        booksList.innerHTML = html;
        attachEventListeners();
      });
  } else {
    // Instead of just re-fetching list, reload the page to restore pagination
    window.location.href = "manage_books.php";
  }
});

function confirmBulkDelete() {
  return confirm("⚠️ WARNING: You are about to delete multiple books.\n\nThis action cannot be undone unless you click Undo within 10 seconds.\n\nProceed?");
}

function showUndoBulk(message) {
  const toast = document.getElementById("toast");
  let countdown = 10;
  toast.innerHTML = `
    ${message} 
    <form method="POST" class="inline">
      <button type="submit" name="undo_bulk_delete" class="ml-2 bg-green-600 px-2 py-1 rounded">Undo (<span id="undoBulkTimer">10</span>s)</button>
    </form>
  `;
  toast.className = "fixed bottom-5 right-5 px-4 py-2 rounded shadow text-white bg-red-600";
  toast.classList.remove("hidden");

  const timer = setInterval(() => {
    countdown--;
    document.getElementById("undoBulkTimer").textContent = countdown;
    if (countdown <= 0) {
      clearInterval(timer);
      toast.classList.add("hidden");
    }
  }, 1000);
}


// Rows per page
document.getElementById('rowsPerPage').addEventListener('change', e => {
  const url = new URL(window.location.href);
  url.searchParams.set('rows', e.target.value);
  url.searchParams.delete('page');
  window.location.href = url.toString();
});

// PHP Toast
<?php if (isset($toastType) && $toastType === "undo"): ?>
  showUndo("<?= $toastMsg ?>");
<?php elseif (isset($toastMsg)): ?>
  showToast("<?= $toastMsg ?>", "<?= $toastType ?>");
<?php endif; ?>
<?php if (isset($toastType) && $toastType === "undo_bulk"): ?>
  showUndoBulk("<?= $toastMsg ?>");
<?php elseif (isset($toastType) && $toastType === "undo"): ?>
  showUndo("<?= $toastMsg ?>");
<?php elseif (isset($toastMsg)): ?>
  showToast("<?= $toastMsg ?>", "<?= $toastType ?>");
<?php endif; ?>

attachEventListeners();
</script>
