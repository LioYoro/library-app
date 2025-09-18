<?php require __DIR__ . '/../views/header.php'; ?>
<?php
$pdo = new PDO("mysql:host=localhost;dbname=library_test_db", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// =================== PAGINATION ===================
$perPage = 3;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$start = ($page - 1) * $perPage;

// Count total posted events
$totalEvents = $pdo->query("SELECT COUNT(*) FROM post_event WHERE status='POSTED'")->fetchColumn();
$totalPages = ceil($totalEvents / $perPage);

// Fetch paginated events
$stmt = $pdo->prepare("SELECT * FROM post_event WHERE status='POSTED' ORDER BY created_at DESC LIMIT ?, ?");
$stmt->bindValue(1, $start, PDO::PARAM_INT);
$stmt->bindValue(2, $perPage, PDO::PARAM_INT);
$stmt->execute();
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<link rel="stylesheet" href="events_user.css">

<a href="my_proposals.php" 
   style="display:inline-block; margin:10px; padding:10px 20px; background:#2563eb; color:white; border-radius:6px; text-decoration:none;">
   📑 My Event Proposals
</a>

<!-- Library Events Title -->
<h2 class="events-title"><em>Library Events</em></h2>

<div class="events-container">
    <?php if (empty($events)): ?>
        <p class="no-events">No events posted yet.</p>
    <?php else: ?>
        <?php foreach ($events as $event): ?>
            <div class="event-cell" onclick="window.location.href='event_detail.php?id=<?= $event['id'] ?>'">
                <?php if ($event['image']): ?>
                    <img src="/library-app/admin/events_tools/uploads/<?= htmlspecialchars($event['image']) ?>" alt="<?= htmlspecialchars($event['title']) ?>">
                <?php endif; ?>
                <div class="content">
                    <div class="title"><?= htmlspecialchars($event['title']) ?></div>
                    <div class="snippet"><?= htmlspecialchars(substr($event['description'], 0, 150)) ?>...</div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Pagination -->
<div class="pagination">
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a href="?page=<?= $i ?>" class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
    <?php endfor; ?>
</div>

<!-- Propose Event Button -->
<div style="text-align:center; margin: 30px 0;">
    <a href="/library-app/events_user/event_submit.php" 
       style="background:#1d4ed8; color:white; padding:12px 24px; border-radius:6px; text-decoration:none; font-weight:bold; font-size:16px; transition: all 0.3s ease;"
       onmouseover="this.style.backgroundColor='#2563eb';" 
       onmouseout="this.style.backgroundColor='#1d4ed8';">
       📩 Propose an Event
    </a>
</div>

<?php require __DIR__ . '/../views/footer.php'; ?>
