<?php
// book.php
require_once __DIR__ . '/models/BookModel.php';

$id = $_GET['id'] ?? null;
$model = new BookModel();
$book = $model->getBookById($id);

if (!$book) {
  echo "Book not found.";
  exit;
}

$title = $book[1]; // Assuming column 1 is the title
header("Location: views/book_detail.php?title=" . urlencode($title));
exit;