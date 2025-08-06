<?php
include('../db.php'); // Only once at the top!


  
// 1. Total users
$totalUsersResult = $conn->query("SELECT COUNT(*) AS total FROM users");
$totalUsers = $totalUsersResult ? (int)$totalUsersResult->fetch_assoc()['total'] : 0;

// 2. Gender distribution
$genderResult = $conn->query("SELECT gender, COUNT(*) AS count FROM users GROUP BY gender");
$genderCounts = [];
if ($genderResult) {
    while ($row = $genderResult->fetch_assoc()) {
        $genderCounts[$row['gender']] = (int)$row['count'];
    }
}
foreach (['Male', 'Female', 'Other'] as $g) {
    if (!isset($genderCounts[$g])) $genderCounts[$g] = 0;
}

// 3. Major/Strand distribution
$majorResult = $conn->query("SELECT major, COUNT(*) AS count FROM users GROUP BY major ORDER BY count DESC");
$majorCounts = [];
if ($majorResult) {
    while ($row = $majorResult->fetch_assoc()) {
        $major = $row['major'] ?: 'Unspecified';
        $majorCounts[$major] = (int)$row['count'];
    }
}

// 4. New users per month (last 12 months)
$sql = "
SELECT DATE_FORMAT(created_at, '%Y-%m') AS `year_month`, COUNT(*) AS `count`
FROM users
WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
GROUP BY `year_month`
ORDER BY `year_month` ASC
";
$monthlyUsersResult = $conn->query($sql);
if (!$monthlyUsersResult) {
    die("SQL Error: " . $conn->error);
}

$monthlyLabels = [];
$monthlyCounts = [];
while ($row = $monthlyUsersResult->fetch_assoc()) {
    $monthlyLabels[] = $row['year_month'];
    $monthlyCounts[] = (int)$row['count'];
}

// Generate months list once
// Set a fixed start month (e.g., Jan 2025)
$start = new DateTime('2025-01-01');
$months = [];
for ($i = 0; $i < 12; $i++) {
    $months[] = $start->format('Y-m');
    $start->modify('+1 month');
}


// Fill missing months with zero counts
$monthlyData = [];
foreach ($months as $month) {
    $index = array_search($month, $monthlyLabels);
    $monthlyData[] = $index !== false ? $monthlyCounts[$index] : 0;
}

// 1. User counts per month by gender
$sql_gender = "
SELECT DATE_FORMAT(created_at, '%Y-%m') AS `year_month`, gender, COUNT(*) AS `count`
FROM users
WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
GROUP BY `year_month`, gender
ORDER BY `year_month` ASC
";
$result_gender = $conn->query($sql_gender);
if (!$result_gender) {
    die("SQL Error: " . $conn->error);
}
$genderData = [];
while ($row = $result_gender->fetch_assoc()) {
    $genderData[$row['year_month']][$row['gender']] = (int)$row['count'];
}

// Make sure all months & genders have keys with 0 default
$genders = ['Male', 'Female', 'Other'];
foreach ($months as $m) {
    foreach ($genders as $g) {
        if (!isset($genderData[$m][$g])) $genderData[$m][$g] = 0;
    }
}

// 2. User counts per month by major/strand
$sql_major = "
SELECT DATE_FORMAT(created_at, '%Y-%m') AS `year_month`, major, COUNT(*) AS `count`
FROM users
WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
GROUP BY `year_month`, major
ORDER BY `year_month` ASC
";
$result_major = $conn->query($sql_major);
if (!$result_major) {
    die("SQL Error: " . $conn->error);
}
$majorData = [];
$majorSet = [];
while ($row = $result_major->fetch_assoc()) {
    $major = $row['major'] ?: 'Unspecified';
    $majorData[$row['year_month']][$major] = (int)$row['count'];
    $majorSet[$major] = true;
}

$majors = array_keys($majorSet);

// Fill missing data with zero
foreach ($months as $m) {
    foreach ($majors as $maj) {
        if (!isset($majorData[$m][$maj])) $majorData[$m][$maj] = 0;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>User Reports</title>

<!-- Chart.js CDN (only once) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- jQuery (required by DataTables) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- DataTables CSS & JS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" />
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<style>
  body { font-family: Arial, sans-serif; margin: 20px; }
  .stats { display: flex; gap: 40px; margin-bottom: 30px; }
  .card { padding: 20px; border: 1px solid #ccc; border-radius: 8px; flex: 1; }
  h2 { margin-top: 0; }
</style>
</head>
<body>

<h1>User Reports Dashboard</h1>
<div class="flex items-center space-x-3">
  <a href="../users.php" class="bg-gray-200 text-gray-700 px-3 py-1 rounded hover:bg-gray-300">🏠 Back to Users</a>
</div>
<h2>User Counts by Gender per Month</h2>
<table border="1" cellpadding="6" cellspacing="0">
  <thead>
    <tr>
      <th>Month</th>
      <?php foreach ($genders as $g): ?>
      <th><?= htmlspecialchars($g) ?></th>
      <?php endforeach; ?>
      <th>Total</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($months as $m): ?>
    <tr>
      <td><?= $m ?></td>
      <?php
        $sum = 0;
        foreach ($genders as $g) {
          $count = $genderData[$m][$g] ?? 0;
          echo "<td>$count</td>";
          $sum += $count;
        }
      ?>
      <td><?= $sum ?></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<h2>User Counts by Major/Strand per Month</h2>
<table border="1" cellpadding="6" cellspacing="0">
  <thead>
    <tr>
      <th>Month</th>
      <?php foreach ($majors as $maj): ?>
      <th><?= htmlspecialchars($maj) ?></th>
      <?php endforeach; ?>
      <th>Total</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($months as $m): ?>
    <tr>
      <td><?= $m ?></td>
      <?php
        $sum = 0;
        foreach ($majors as $maj) {
          $count = $majorData[$m][$maj] ?? 0;
          echo "<td>$count</td>";
          $sum += $count;
        }
      ?>
      <td><?= $sum ?></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<div class="stats">
  <div class="card">
    <h2>Total Users</h2>
    <p style="font-size: 2rem; font-weight: bold;"><?= $totalUsers ?></p>
  </div>

  <div class="card">
    <h2>Gender Distribution</h2>
    <ul>
      <li>Male: <?= $genderCounts['Male'] ?? 0 ?></li>
      <li>Female: <?= $genderCounts['Female'] ?? 0 ?></li>
      <li>Other: <?= $genderCounts['Other'] ?? 0 ?></li>
    </ul>
  </div>

  <div class="card">
    <h2>Top Majors/Strands</h2>
    <ul>
      <?php foreach ($majorCounts as $major => $count): ?>
        <li><?= htmlspecialchars($major) ?>: <?= $count ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
</div>

<div style="max-width:700px;">
  <h2>New Users per Month (Last 12 Months)</h2>
  <canvas id="monthlyUsersChart" height="100"></canvas>
</div>

<script>
// Chart for New Users per Month
const ctxMonthly = document.getElementById('monthlyUsersChart').getContext('2d');
const monthlyUsersChart = new Chart(ctxMonthly, {
    type: 'bar',
    data: {
        labels: <?= json_encode($months) ?>,
        datasets: [{
            label: 'New Users',
            data: <?= json_encode($monthlyData) ?>,
            backgroundColor: 'rgba(54, 162, 235, 0.6)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 1,
            borderRadius: 4,
        }]
    },
    options: {
        scales: {
            y: { beginAtZero: true, precision: 0 }
        },
        plugins: {
          legend: { display: false }
        }
    }
});
</script>

<h2>Users List</h2>

<table id="userTable" class="display" style="width:100%; margin-top:10px;">
  <thead>
    <tr>
      <th></th> <!-- expand/collapse icon -->
      <th>First Name</th>
      <th>Last Name</th>
      <th>Email</th>
      <th>Gender</th>
      <th>Major</th>
      <th>Created At</th>
    </tr>
  </thead>
  <tbody>
    <?php
    $usersResult = $conn->query("SELECT id, first_name, last_name, email, gender, major, created_at FROM users ORDER BY created_at DESC");
    while ($user = $usersResult->fetch_assoc()):
    ?>
    <tr data-userid="<?= $user['id'] ?>">
      <td class="details-control" style="cursor:pointer; text-align:center;">+</td>
      <td><?= htmlspecialchars($user['first_name']) ?></td>
      <td><?= htmlspecialchars($user['last_name']) ?></td>
      <td><?= htmlspecialchars($user['email']) ?></td>
      <td><?= htmlspecialchars($user['gender']) ?></td>
      <td><?= htmlspecialchars($user['major'] ?: 'Unspecified') ?></td>
      <td><?= htmlspecialchars($user['created_at']) ?></td>
    </tr>
    <?php endwhile; ?>
  </tbody>
</table>

<script>
// DataTables init
$(document).ready(function() {
  var table = $('#userTable').DataTable({
    pageLength: 10,
    lengthMenu: [10, 25, 50]
  });

  // Expand/Collapse row details toggle
  $('#userTable tbody').on('click', 'td.details-control', function () {
    var tr = $(this).closest('tr');
    var row = table.row(tr);

    if (row.child.isShown()) {
      row.child.hide();
      $(this).text('+');
    } else {
      var userId = tr.data('userid');
      row.child('<div>User ID: ' + userId + '</div>').show();
      $(this).text('-');
    }
  });
});
</script>

<div style="max-width:700px; margin-top:40px;">
  <h2>User Counts by Gender per Month (Stacked Bar Chart)</h2>
  <canvas id="usersChart" height="150"></canvas>
</div>

<script>
const ctxUsers = document.getElementById('usersChart').getContext('2d');

const monthsJs = <?= json_encode($months) ?>;
const genderDataJs = <?= json_encode($genderData) ?>;

// Prepare data arrays for each gender with fallback to 0
const dataMale = monthsJs.map(m => genderDataJs[m]?.Male || 0);
const dataFemale = monthsJs.map(m => genderDataJs[m]?.Female || 0);
const dataOther = monthsJs.map(m => genderDataJs[m]?.Other || 0);

const usersChart = new Chart(ctxUsers, {
    type: 'bar',
    data: {
        labels: monthsJs,
        datasets: [
            {
                label: 'Male',
                data: dataMale,
                backgroundColor: 'rgba(54, 162, 235, 0.7)'
            },
            {
                label: 'Female',
                data: dataFemale,
                backgroundColor: 'rgba(255, 99, 132, 0.7)'
            },
            {
                label: 'Other',
                data: dataOther,
                backgroundColor: 'rgba(201, 203, 207, 0.7)'
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            tooltip: {
                mode: 'index',
                intersect: false,
                callbacks: {
                    label: function(context) {
                        return context.dataset.label + ': ' + context.parsed.y;
                    }
                }
            },
            legend: {
                position: 'top',
            },
        },
        scales: {
            x: { stacked: true },
            y: { stacked: true, beginAtZero: true }
        }
    }
});
</script>

</body>
</html>
