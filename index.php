<?php
session_start();
require_once __DIR__ . '/models/BookModel.php';

$model = new BookModel();
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$categories = $model->getCategories();
$books = $model->getBooks($search, $category);
$title = "Library Book Search";

require __DIR__ . '/views/header.php';
?>

<!-- Main Content Container -->
<div style="min-height: calc(100vh - 200px); width: 100%; display: block; position: relative;">
  <?php
  require __DIR__ . '/views/search_bar.php';
  
  if (empty($search) && empty($category)) {
      require __DIR__ . '/views/home.php';
  } else {
      require __DIR__ . '/views/book_results.php';
  }
  ?>
</div>

<?php require __DIR__ . '/views/footer.php'; ?>
