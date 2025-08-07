<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$pdo = new PDO("mysql:host=localhost;dbname=library_test_db", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "<h2>Database Category Analysis</h2>";

// Get all unique categories
$stmt = $pdo->query("SELECT DISTINCT General_Category, COUNT(*) as count FROM books GROUP BY General_Category ORDER BY count DESC");
$categories = $stmt->fetchAll();

echo "<h3>All Categories in Database:</h3>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Category</th><th>Book Count</th></tr>";
foreach ($categories as $cat) {
    echo "<tr><td>" . htmlspecialchars($cat['General_Category']) . "</td><td>" . $cat['count'] . "</td></tr>";
}
echo "</table>";

// Check for health-related categories
echo "<h3>Health-Related Categories:</h3>";
$healthStmt = $pdo->query("SELECT DISTINCT General_Category, COUNT(*) as count FROM books WHERE General_Category LIKE '%health%' OR General_Category LIKE '%medical%' OR General_Category LIKE '%medicine%' OR General_Category LIKE '%dental%' GROUP BY General_Category");
$healthCategories = $healthStmt->fetchAll();

if (empty($healthCategories)) {
    echo "<p style='color: red;'>❌ No health-related categories found!</p>";
    
    // Let's see what categories might be related
    echo "<h3>Possible Related Categories:</h3>";
    $possibleStmt = $pdo->query("SELECT DISTINCT General_Category FROM books WHERE General_Category LIKE '%science%' OR General_Category LIKE '%bio%' OR General_Category LIKE '%care%'");
    $possibleCategories = $possibleStmt->fetchAll();
    
    foreach ($possibleCategories as $cat) {
        echo "<p>- " . htmlspecialchars($cat['General_Category']) . "</p>";
    }
} else {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Health Category</th><th>Book Count</th></tr>";
    foreach ($healthCategories as $cat) {
        echo "<tr><td>" . htmlspecialchars($cat['General_Category']) . "</td><td>" . $cat['count'] . "</td></tr>";
    }
    echo "</table>";
}

// Show some sample books to see their categories
echo "<h3>Sample Books and Their Categories:</h3>";
$sampleStmt = $pdo->query("SELECT TITLE, AUTHOR, General_Category FROM books LIMIT 20");
$sampleBooks = $sampleStmt->fetchAll();

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Title</th><th>Author</th><th>Category</th></tr>";
foreach ($sampleBooks as $book) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($book['TITLE']) . "</td>";
    echo "<td>" . htmlspecialchars($book['AUTHOR']) . "</td>";
    echo "<td>" . htmlspecialchars($book['General_Category']) . "</td>";
    echo "</tr>";
}
echo "</table>";

// Test the exact search that Flask would do
echo "<h3>Testing Flask Search Logic:</h3>";
echo "<p>Looking for books with category containing 'Health'...</p>";

$testStmt = $pdo->prepare("SELECT TITLE, AUTHOR, General_Category FROM books WHERE General_Category LIKE '%Health%' ORDER BY `Like` DESC LIMIT 5");
$testStmt->execute();
$testBooks = $testStmt->fetchAll();

if (empty($testBooks)) {
    echo "<p style='color: red;'>❌ No books found with 'Health' in category name</p>";
} else {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Title</th><th>Author</th><th>Category</th></tr>";
    foreach ($testBooks as $book) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($book['TITLE']) . "</td>";
        echo "<td>" . htmlspecialchars($book['AUTHOR']) . "</td>";
        echo "<td>" . htmlspecialchars($book['General_Category']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}
?>

<style>
table {
    margin: 10px 0;
}
th, td {
    padding: 8px;
    text-align: left;
}
th {
    background-color: #f2f2f2;
}
</style>
