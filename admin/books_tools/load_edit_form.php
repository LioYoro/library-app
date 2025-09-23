<?php
session_start();
include('../db.php');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<p class='text-red-600'>Invalid book ID.</p>";
    exit;
}

$id = intval($_GET['id']);
$result = $conn->query("SELECT * FROM books WHERE id=$id");
$book = $result ? $result->fetch_assoc() : null;

if (!$book) {
    echo "<p class='text-red-600'>Book not found.</p>";
    exit;
}
?>

<form method="POST" class="space-y-3">
  <input type="hidden" name="id" value="<?= $book['id'] ?>" />

  <h3 class="font-semibold text-blue-700 mb-3">Editing: <?= htmlspecialchars($book['TITLE']) ?></h3>

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
      <textarea name="summary" rows="3" class="border rounded px-2 py-1 w-full"><?= htmlspecialchars($book['SUMMARY']) ?></textarea>
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700">Keywords</label>
      <input type="text" name="keywords" value="<?= htmlspecialchars($book['KEYWORDS']) ?>" class="border rounded px-2 py-1 w-full" />
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700">General Category</label>
      <input type="text" name="general_category" value="<?= htmlspecialchars($book['General_Category']) ?>" class="border rounded px-2 py-1 w-full" />
    </div>
   
  </div>

  <div class="mt-4">
    <button type="submit" name="edit_book" class="bg-blue-600 text-white px-4 py-1 rounded hover:bg-blue-700">Save</button>
    <button type="button" onclick="document.getElementById('editPanel').classList.add('hidden');" class="bg-gray-400 px-4 py-1 rounded hover:bg-gray-500 ml-2">Cancel</button>
  </div>
</form>
