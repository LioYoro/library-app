<?php
session_start(); // ✅ START SESSION FIRST
require_once __DIR__ . '/models/BookModel.php';
$model = new BookModel();

$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';

$categories = $model->getCategories();
$books = $model->getBooks($search, $category);

$title = "Library Book Search";

require __DIR__ . '/views/header.php';
require __DIR__ . '/views/search_bar.php';

// Show homepage if nothing searched or filtered
if (empty($search) && empty($category)) {
    require __DIR__ . '/views/home.php';
} else {
    require __DIR__ . '/views/book_results.php';
}

require __DIR__ . '/views/footer.php';
?>
