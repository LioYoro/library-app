<?php 
$pageTitle = 'Events';
include('includes/header.php'); 
include('includes/sidebar.php'); 

$pdo = new PDO("mysql:host=localhost;dbname=library_test_db", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Fetch pending proposals (only title + name)
$pendingProposals = $pdo->query("SELECT id, name, event_title 
                                 FROM propose_event 
                                 WHERE status='PENDING' 
                                 ORDER BY date_submitted DESC LIMIT 5")
                        ->fetchAll(PDO::FETCH_ASSOC);

// Fetch posted events
$postedEvents = $pdo->query("SELECT id, title, description 
                             FROM post_event 
                             WHERE status='POSTED' 
                             ORDER BY created_at DESC LIMIT 5")
                        ->fetchAll(PDO::FETCH_ASSOC);
?>

<div id="main-content" class="flex-1 flex flex-col min-w-0 ml-[15rem] h-screen transition-all duration-300">
  <header class="h-16 bg-blue-500 text-white flex items-center justify-between px-6 shadow">
    <h1 class="text-xl font-bold"><?= $pageTitle ?></h1>
    <div class="flex items-center space-x-3">
      <span class="text-sm">ADMIN</span>
      <i class="fas fa-user-circle text-2xl"></i>
    </div>
  </header>

  <main class="flex-1 p-6 bg-gray-50 overflow-y-auto">
    <h2 class="text-lg font-semibold mb-4">📊 Events Dashboard</h2>

    <!-- Pending Proposals -->
    <div class="bg-white p-4 shadow rounded mb-6">
      <h3 class="text-md font-semibold mb-3">📩 Pending Proposals</h3>
      <?php if (empty($pendingProposals)): ?>
        <p class="text-gray-500">No pending proposals.</p>
      <?php else: ?>
        <ul class="divide-y">
          <?php foreach ($pendingProposals as $proposal): ?>
            <li class="py-2">
              <strong><?= htmlspecialchars($proposal['event_title']) ?></strong> 
              by <?= htmlspecialchars($proposal['name']) ?>
            </li>
          <?php endforeach; ?>
        </ul>
        <div class="mt-3 text-right">
          <a href="events_tools/event_proposals.php" 
             class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded">
            View All Proposals
          </a>
        </div>
      <?php endif; ?>
    </div>

    <!-- Posted Events -->
    <div class="bg-white p-4 shadow rounded mb-6">
      <h3 class="text-md font-semibold mb-3">✅ Current Posted Events</h3>
      <?php if (empty($postedEvents)): ?>
        <p class="text-gray-500">No events posted yet.</p>
      <?php else: ?>
        <ul class="divide-y">
          <?php foreach ($postedEvents as $event): ?>
            <li class="py-2">
              <strong><?= htmlspecialchars($event['title']) ?></strong> — 
              <?= htmlspecialchars(substr($event['description'], 0, 50)) ?>...
            </li>
          <?php endforeach; ?>
        </ul>
        <div class="mt-3 text-right">
          <a href="events_tools/new_event.php" 
             class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded">
            View All Events
          </a>
        </div>
      <?php endif; ?>
    </div>

    <!-- Moved the Run Event Expiry button inside main content area at the bottom -->
    <div class="flex justify-center">
        <form method="POST" action="events_tools/run_event_cron.php">
            <button type="submit" 
                    class="bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded">
                Run Event Expiry Cron Now
            </button>
        </form>
    </div>
  </main>
</div>

<?php include('includes/footer.php'); ?>
