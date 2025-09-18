<?php 
$pageTitle = 'Dashboard';
include('includes/header.php'); 
include('includes/sidebar.php');
?>

<!-- MAIN CONTENT -->
<div id="main-content" class="flex-1 flex flex-col min-w-0 ml-[15rem] h-screen transition-all duration-300">

  <!-- HEADER -->
  <header class="h-16 bg-blue-500 text-white flex items-center justify-between px-6 shadow">
    <h1 class="text-xl font-bold"><?= $pageTitle ?></h1>
    <div class="flex items-center space-x-3">
      <span class="text-sm">ADMIN</span>
      <i class="fas fa-user-circle text-2xl"></i>
    </div>
  </header>

  <!-- MAIN DASHBOARD CONTENT -->
  <main class="flex-1 p-6 bg-gray-100 overflow-y-auto">

    <!-- DASHBOARD CARDS -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
      
      <!-- Total Books -->
      <div class="dashboard-card bg-white p-6 rounded-lg shadow border-l-4 border-blue-500">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-gray-500">Total Books</p>
            <h3 class="text-2xl font-bold">1,254</h3>
          </div>
          <div class="p-3 rounded-full bg-blue-100 text-blue-600">
            <i class="fas fa-book text-xl"></i>
          </div>
        </div>
        <div class="mt-4 text-xs text-green-500 flex items-center">
          <i class="fas fa-arrow-up mr-1"></i>
          12% from last month
        </div>
      </div>

      <!-- Active Members -->
      <div class="dashboard-card bg-white p-6 rounded-lg shadow border-l-4 border-green-500">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-gray-500">Active Members</p>
            <h3 class="text-2xl font-bold">324</h3>
          </div>
          <div class="p-3 rounded-full bg-green-100 text-green-600">
            <i class="fas fa-users text-xl"></i>
          </div>
        </div>
        <div class="mt-4 text-xs text-red-500 flex items-center">
          <i class="fas fa-arrow-down mr-1"></i>
          5% from last month
        </div>
      </div>

      <!-- Reservations Today -->
      <div class="dashboard-card bg-white p-6 rounded-lg shadow border-l-4 border-yellow-500">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-gray-500">Reservations Today</p>
            <h3 class="text-2xl font-bold">87</h3>
          </div>
          <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
            <i class="fas fa-calendar-check text-xl"></i>
          </div>
        </div>
        <div class="mt-4 text-xs text-green-500 flex items-center">
          <i class="fas fa-arrow-up mr-1"></i>
          8% from yesterday
        </div>
      </div>

      <!-- Overdue Returns -->
      <div class="dashboard-card bg-white p-6 rounded-lg shadow border-l-4 border-red-500">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-gray-500">Overdue Returns</p>
            <h3 class="text-2xl font-bold">16</h3>
          </div>
          <div class="p-3 rounded-full bg-red-100 text-red-600">
            <i class="fas fa-exclamation-triangle text-xl"></i>
          </div>
        </div>
        <div class="mt-4 text-xs text-red-500 flex items-center">
          <i class="fas fa-arrow-up mr-1"></i>
          10% from last week
        </div>
      </div>

    </div>

    <!-- TABLE + EVENTS SECTION -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      
      <!-- Recent Reservations Table -->
      <div class="bg-white p-6 rounded-lg shadow">
        <div class="flex justify-between items-center mb-4">
          <h3 class="font-semibold text-lg">Recent Reservations</h3>
          <a href="reservations.php" class="text-sm text-blue-600 hover:underline">View All</a>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead>
              <tr>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Book</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Member</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
  <tr>
    <td class="px-4 py-3 whitespace-nowrap">History of the Philippines</td>
    <td class="px-4 py-3 whitespace-nowrap">Juan Dela Cruz</td>
    <td class="px-4 py-3 whitespace-nowrap">Nov 12, 2023</td>
    <td class="px-4 py-3 whitespace-nowrap"><span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full">Active</span></td>
  </tr>
  <tr>
    <td class="px-4 py-3 whitespace-nowrap">Introduction to AI</td>
    <td class="px-4 py-3 whitespace-nowrap">Maria Santos</td>
    <td class="px-4 py-3 whitespace-nowrap">Nov 11, 2023</td>
    <td class="px-4 py-3 whitespace-nowrap"><span class="px-2 py-1 text-xs bg-yellow-100 text-yellow-800 rounded-full">Pending</span></td>
  </tr>
  <tr>
    <td class="px-4 py-3 whitespace-nowrap">World Literature</td>
    <td class="px-4 py-3 whitespace-nowrap">Carlos Reyes</td>
    <td class="px-4 py-3 whitespace-nowrap">Nov 10, 2023</td>
    <td class="px-4 py-3 whitespace-nowrap"><span class="px-2 py-1 text-xs bg-red-100 text-red-800 rounded-full">Overdue</span></td>
  </tr>
</tbody>

          </table>
        </div>
      </div>

      <!-- Upcoming Events -->
<div class="bg-white p-6 rounded-lg shadow-md">
  <div class="flex items-center justify-between mb-4">
    <h3 class="text-lg font-semibold">Upcoming Events</h3>
    <a href="events.php" class="text-sm text-blue-600 hover:underline">View All</a>
  </div>

  <div class="space-y-4">
    <div class="border-l-4 border-blue-500 pl-4 py-1">
      <h4 class="font-medium">Filipino Authors Symposium</h4>
      <p class="text-sm text-gray-500">Nov 15, 2023 • 2:00 PM</p>
      <p class="text-sm mt-1">Discussion with contemporary Filipino writers about modern literature.</p>
    </div>
    <div class="border-l-4 border-green-500 pl-4 py-1">
      <h4 class="font-medium">Children's Storytelling Hour</h4>
      <p class="text-sm text-gray-500">Nov 18, 2023 • 10:00 AM</p>
      <p class="text-sm mt-1">Interactive storytelling event for young readers aged 4–8.</p>
    </div>
    <div class="border-l-4 border-yellow-500 pl-4 py-1">
      <h4 class="font-medium">Book Donation Drive</h4>
      <p class="text-sm text-gray-500">Nov 20, 2023 • All Day</p>
      <p class="text-sm mt-1">Accepting gently used books to support local schools and community libraries.</p>
    </div>
  </div>
</div>
    </div>
  </main>
</div>

<?php include('includes/footer.php'); ?>
