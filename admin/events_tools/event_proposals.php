<?php
$pageTitle = 'Event Proposals';
include('../includes/header.php');
include('../includes/sidebar.php');
include('../db.php');

// Helper to get month boundaries
$currentMonth = date('m');
$currentYear = date('Y');
$lastMonth = date('m', strtotime('-1 month'));
$lastMonthYear = date('Y', strtotime('-1 month'));
?>

<div id="main-content" class="flex-1 flex flex-col min-w-0 ml-[15rem] h-screen transition-all duration-300">
  <header class="h-16 bg-blue-500 text-white flex items-center justify-between px-6 shadow">
    <h1 class="text-xl font-bold"><?= $pageTitle ?></h1>
    <div class="flex items-center space-x-3">
      <span class="text-sm">ADMIN</span>
      <i class="fas fa-user-circle text-2xl"></i>
    </div>
  </header>
 
  

  <a href="../events.php" class="inline-block bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded mb-4">
  ← Back to Events Dashboard
</a>
  <main class="flex-1 p-6 bg-white overflow-y-auto">

    <!-- This Month Proposals -->
    <h2 class="text-lg font-semibold mb-2">📌 New Event Proposals (This Month)</h2>
    <table class="w-full border text-sm mb-6">
      <thead class="bg-gray-200">
        <tr>
          <th class="border px-3 py-2">Name</th>
          <th class="border px-3 py-2">Event Title</th>
          <th class="border px-3 py-2">Description</th>
          <th class="border px-3 py-2">Contact</th>
          <th class="border px-3 py-2">File</th>
          <th class="border px-3 py-2">Submitted At</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $queryNew = "SELECT * FROM events WHERE MONTH(date_submitted) = $currentMonth AND YEAR(date_submitted) = $currentYear ORDER BY date_submitted DESC";
        $resultNew = mysqli_query($conn, $queryNew);

        if (mysqli_num_rows($resultNew) > 0) {
          while ($row = mysqli_fetch_assoc($resultNew)) {
            echo "<tr>";
            echo "<td class='border px-3 py-2'>" . htmlspecialchars($row['name']) . "</td>";
            echo "<td class='border px-3 py-2'>" . htmlspecialchars($row['event_title']) . "</td>";
            echo "<td class='border px-3 py-2'>" . htmlspecialchars($row['description']) . "</td>";
            echo "<td class='border px-3 py-2'>" . htmlspecialchars($row['contact']) . "</td>";
            echo "<td class='border px-3 py-2'><a href='uploads/" . $row['file_path'] . "' target='_blank'>View File</a></td>";
            echo "<td class='border px-3 py-2'>" . $row['date_submitted'] . "</td>";
            echo "</tr>";
          }
        } else {
          echo "<tr><td colspan='6' class='text-center p-4'>No new proposals submitted this month.</td></tr>";
        }
        ?>
      </tbody>
    </table>

    <!-- Past Proposals -->
    <h2 class="text-lg font-semibold mb-2">📂 Past Event Proposals (Before This Month)</h2>
    <table class="w-full border text-sm">
      <thead class="bg-gray-200">
        <tr>
          <th class="border px-3 py-2">Name</th>
          <th class="border px-3 py-2">Event Title</th>
          <th class="border px-3 py-2">Description</th>
          <th class="border px-3 py-2">Contact</th>
          <th class="border px-3 py-2">File</th>
          <th class="border px-3 py-2">Submitted At</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $queryOld = "SELECT * FROM events WHERE (MONTH(date_submitted) < $currentMonth OR YEAR(date_submitted) < $currentYear) ORDER BY date_submitted DESC";
        $resultOld = mysqli_query($conn, $queryOld);

        if (mysqli_num_rows($resultOld) > 0) {
          while ($row = mysqli_fetch_assoc($resultOld)) {
            echo "<tr>";
            echo "<td class='border px-3 py-2'>" . htmlspecialchars($row['name']) . "</td>";
            echo "<td class='border px-3 py-2'>" . htmlspecialchars($row['event_title']) . "</td>";
            echo "<td class='border px-3 py-2'>" . htmlspecialchars($row['description']) . "</td>";
            echo "<td class='border px-3 py-2'>" . htmlspecialchars($row['contact']) . "</td>";
            echo "<td class='border px-3 py-2'><a href='uploads/" . $row['file_path'] . "' target='_blank'>View File</a></td>";
            echo "<td class='border px-3 py-2'>" . $row['date_submitted'] . "</td>";
            echo "</tr>";
          }
        } else {
          echo "<tr><td colspan='6' class='text-center p-4'>No older proposals found.</td></tr>";
        }
        ?>
      </tbody>
    </table>

  </main>
</div>

<?php include('../includes/footer.php'); ?>
