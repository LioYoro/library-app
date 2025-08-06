<?php
include '../db.php'; // Adjust path if needed
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Book Summary</title>
  <link rel="stylesheet" href="../styles.css"> <!-- Optional -->
  <style>
    body {
      font-family: Arial, sans-serif;
      padding: 2rem;
    }
    .summary-box {
      background: #f4f4f4;
      padding: 1rem;
      margin-bottom: 1rem;
      border-left: 5px solid #007BFF;
    }
    .btn {
      padding: 10px 20px;
      background-color: #007BFF;
      color: white;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      text-decoration: none;
    }
    .btn:hover {
      background-color: #0056b3;
    }
    .summary-container {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-bottom: 20px;
  justify-content: flex-start;
    }

    .summary-box {
    background-color: #f8f9fa;
    border: 1px solid #ddd;
    padding: 10px 15px;
    border-radius: 8px;
    min-width: 200px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
  </style>
</head>
<body>

  <h1>📚 Book Summary</h1>

  <?php
  // Query summaries
  $totalBooksQuery = "SELECT COUNT(*) as count FROM books";
  $totalTitlesQuery = "SELECT COUNT(DISTINCT TITLE) as count FROM books";
  $totalAuthorsQuery = "SELECT COUNT(DISTINCT AUTHOR) as count FROM books";
  $totalCategoriesQuery = "SELECT COUNT(DISTINCT General_Category) as count FROM books";
  $totalSubCategoriesQuery = "SELECT COUNT(DISTINCT Sub_Category) as count FROM books";
  $lastMonthQuery = "
    SELECT COUNT(*) as count FROM books
    WHERE DATE_ADDED >= DATE_FORMAT(NOW() - INTERVAL 1 MONTH, '%Y-%m-01')
      AND DATE_ADDED < DATE_FORMAT(NOW(), '%Y-%m-01')
  ";

  // Duplicate titles
  $duplicateTitlesQuery = "
    SELECT COUNT(*) AS count FROM (
      SELECT TITLE FROM books
      GROUP BY TITLE
      HAVING COUNT(*) > 1
    ) AS duplicates
  ";
  $duplicateAuthorsQuery = "
  SELECT COUNT(*) AS count FROM (
    SELECT AUTHOR FROM books
    GROUP BY AUTHOR
    HAVING COUNT(*) > 1
  ) AS duplicates
";

$duplicateAuthors = getCount($conn, $duplicateAuthorsQuery);


  function getCount($conn, $query) {
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    return $row['count'];
  }

  $totalBooks = getCount($conn, $totalBooksQuery);
  $uniqueTitles = getCount($conn, $totalTitlesQuery);
  $duplicateTitles = getCount($conn, $duplicateTitlesQuery);
  $uniqueAuthors = getCount($conn, $totalAuthorsQuery);
  $duplicateAuthors = getCount($conn, $duplicateAuthorsQuery);
  $uniqueCategories = getCount($conn, $totalCategoriesQuery);
  $uniqueSubCategories = getCount($conn, $totalSubCategoriesQuery);
  $booksLastMonth = getCount($conn, $lastMonthQuery);
  ?>
    
<a href="../books.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded shadow">
          BACK 
      </a>
  <div class="summary-container">
  <div class="summary-box">📘 <strong>Total Books:</strong> <?= $totalBooks ?></div>
  <div class="summary-box">📖 <strong>Unique Titles:</strong> <?= $uniqueTitles ?></div>
  <div class="summary-box">📄 <strong>Duplicate Titles:</strong> <?= $duplicateTitles ?></div>
  <div class="summary-box">👤 <strong>Unique Authors:</strong> <?= $uniqueAuthors ?></div>
  <div class="summary-box">👥 <strong>Duplicate Authors:</strong> <?= $duplicateAuthors ?></div>
  <div class="summary-box">🏷️ <strong>General Categories:</strong> <?= $uniqueCategories ?></div>
  <div class="summary-box">🗂️ <strong>Subcategories:</strong> <?= $uniqueSubCategories ?></div>
  <div class="summary-box">📅 <strong>Books Added Last Month:</strong> <?= $booksLastMonth ?></div>
</div>

  <br>
  <a href="generate_report.php" class="btn">GENERATE BOOKS REPORT</a>

</body>
</html>
