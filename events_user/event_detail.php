<?php require __DIR__ . '/../views/header.php'; ?>

<?php
// Database connection
$pdo = new PDO("mysql:host=localhost;dbname=library_test_db", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Validate ID from URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<p style='text-align:center; margin-top:40px; color:red;'>⚠ Invalid event.</p>";
    require __DIR__ . '/../views/footer.php';
    exit;
}

$id = (int) $_GET['id'];

// Fetch event details
$stmt = $pdo->prepare("SELECT * FROM post_event WHERE id=? AND status='POSTED'");
$stmt->execute([$id]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    echo "<p style='text-align:center; margin-top:40px; color:red;'>⚠ Event not found.</p>";
    require __DIR__ . '/../views/footer.php';
    exit;
}
?>

<link rel="stylesheet" href="events_user.css">

<div class="event-detail" style="max-width:900px; margin:30px auto; padding:20px; background:#fff; border-radius:10px; box-shadow:0 2px 6px rgba(0,0,0,0.1);">
    
    <!-- Back Link -->
    <div style="margin-bottom:20px;">
        <a href="index.php" style="text-decoration:none; color:#1d4ed8; font-weight:bold;">&larr; Back to Events</a>
    </div>

    <!-- Event Title -->
    <h1 style="font-size:28px; margin-bottom:20px; color:#1d4ed8;"><?= htmlspecialchars($event['title']) ?></h1>

    <!-- Event Image -->
    <?php if (!empty($event['image'])): ?>
        <img src="/library-app/admin/events_tools/uploads/<?= htmlspecialchars($event['image']) ?>" 
             alt="<?= htmlspecialchars($event['title']) ?>" 
             style="width:100%; max-height:400px; object-fit:cover; border-radius:8px; margin-bottom:20px;">
    <?php endif; ?>

    <!-- Event Description -->
    <div>
        <h3 style="margin-bottom:10px;">📖 Description:</h3>
        <p style="line-height:1.6; color:#333;"><?= nl2br(htmlspecialchars($event['description'])) ?></p>
    </div>

    <!-- Optional Created Date -->
    <div style="margin-top:20px; font-size:14px; color:#666;">
        Posted on <?= date("F j, Y g:i A", strtotime($event['created_at'])) ?>
    </div>
</div>

<?php require __DIR__ . '/../views/footer.php'; ?>
