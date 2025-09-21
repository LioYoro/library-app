<title>Books Dashboard</title>
<?php 
include('includes/header.php'); 
include('includes/sidebar.php'); 
include('db.php');

// 📊 Summary queries
function getCount($conn, $query) {
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    return $row['count'] ?? 0;
}

$totalBooks = getCount($conn, "SELECT COUNT(*) as count FROM books");
$uniqueTitles = getCount($conn, "SELECT COUNT(DISTINCT TITLE) as count FROM books");
$duplicateTitles = getCount($conn, "SELECT COUNT(*) AS count FROM (SELECT TITLE FROM books GROUP BY TITLE HAVING COUNT(*) > 1) AS duplicates");
$uniqueAuthors = getCount($conn, "SELECT COUNT(DISTINCT AUTHOR) as count FROM books");
$duplicateAuthors = getCount($conn, "SELECT COUNT(*) AS count FROM (SELECT AUTHOR FROM books GROUP BY AUTHOR HAVING COUNT(*) > 1) AS duplicates");
$uniqueCategories = getCount($conn, "SELECT COUNT(DISTINCT General_Category) as count FROM books");
$booksThisMonth = getCount($conn, "
    SELECT COUNT(*) as count FROM books
    WHERE DATE_FORMAT(date_added, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')
");

// 📊 Category data for pie chart
$categoryData = [];
$res = mysqli_query($conn, "SELECT General_Category, COUNT(*) as total FROM books GROUP BY General_Category");
while ($row = mysqli_fetch_assoc($res)) {
    $categoryData[] = $row;
}

// 🆕 Recently added books (latest 5)
$recentBooks = [];
$res = mysqli_query($conn, "SELECT TITLE, General_Category FROM books ORDER BY date_added DESC LIMIT 5");
while ($row = mysqli_fetch_assoc($res)) {
    $recentBooks[] = $row;
}
?>

<div id="main-content" class="flex-1 flex flex-col min-w-0 ml-[15rem] p-6 bg-gray-100 space-y-6">

  <!-- Header -->
  <header class="h-16 bg-blue-500 text-white flex items-center justify-between px-6 shadow rounded">
    <h1 class="text-xl font-bold">📚 Books Dashboard</h1>
    <div class="flex items-center space-x-3">
      <span class="text-sm">ADMIN</span>
      <i class="fas fa-user-circle text-2xl"></i>
    </div>
  </header>

  <!-- Library Summary + Category Pie (same box, side by side) -->
  <div class="bg-white p-6 rounded-xl shadow">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

      <!-- Library Summary -->
      <div>
        <h2 class="text-lg font-semibold text-gray-800 mb-4">📊 Library Summary</h2>
        <ul class="text-sm text-gray-700 space-y-1">
          <li>📘 Total Books: <?= $totalBooks ?></li>
          <li>📖 Unique Titles: <?= $uniqueTitles ?></li>
          <li>📄 Duplicate Titles: <?= $duplicateTitles ?></li>
          <li>👤 Unique Authors: <?= $uniqueAuthors ?></li>
          <li>👥 Duplicate Authors: <?= $duplicateAuthors ?></li>
          <li>🏷️ Categories: <?= $uniqueCategories ?></li>
          <li>📅 Books Added This Month: <?= $booksThisMonth ?></li>
        </ul>
        <div class="mt-4">
          <a href="books_tools/book_summary.php" 
             class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded shadow text-sm">
             VIEW SUMMARY & GENERATE REPORT
          </a>
        </div>
      </div>

      <!-- Category Pie Chart -->
      <div class="flex flex-col items-center">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">🥧 Books per Category</h2>
        <div class="h-72 w-full max-w-sm">
          <canvas id="categoryChart"></canvas>
        </div>
      
      </div>

    </div>
  </div>

  <!-- Recently Added Books -->
  <div class="bg-white p-6 rounded-xl shadow">
    <h2 class="text-lg font-semibold text-gray-800 mb-4">🆕 Recently Added Books</h2>
    <table class="w-full text-sm border border-gray-200 rounded">
      <thead class="bg-gray-100">
        <tr>
          <th class="px-3 py-2 border">Title</th>
          <th class="px-3 py-2 border">Category</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($recentBooks)): ?>
          <tr><td colspan="2" class="px-3 py-2 text-center italic">No books added yet.</td></tr>
        <?php else: ?>
          <?php foreach ($recentBooks as $book): ?>
            <tr class="hover:bg-gray-50">
              <td class="px-3 py-2 border"><?= htmlspecialchars($book['TITLE']) ?></td>
              <td class="px-3 py-2 border"><?= htmlspecialchars($book['General_Category']) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
    <div class="mt-4">
          <a href="books_tools/manage_books.php" 
             class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded shadow text-sm">
             MANAGE OR ADD MORE BOOKS
          </a>
        </div>
  </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  const categoryLabels = <?= json_encode(array_column($categoryData, 'General_Category')) ?>;
  const categoryTotals = <?= json_encode(array_column($categoryData, 'total')) ?>;

  const categoryCtx = document.getElementById('categoryChart').getContext('2d');
  new Chart(categoryCtx, {
    type: 'pie',
    data: {
      labels: categoryLabels,
      datasets: [{
        data: categoryTotals,
        backgroundColor: [
          '#FF6384','#36A2EB','#FFCE56',
          '#4BC0C0','#9966FF','#FF9F40',
          '#FF8C69','#C9CBCF','#8FBC8F',
          '#DDA0DD','#F0E68C','#87CEEB'
        ]
      }]
    },
    options: { 
      responsive: true,
      plugins: { legend: { display: true, position: 'bottom' } }
    }
  });
</script>

<?php include('includes/footer.php'); ?>
