<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/login.php");
    exit;
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=library_test_db", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection failed. Please try again later.");
}

$userId = $_SESSION['user_id'];

// Count active reservations
try {
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE user_id = ? AND status = 'pending'");
    $countStmt->execute([$userId]);
    $activeReservations = (int)$countStmt->fetchColumn();
} catch (PDOException $e) {
    $activeReservations = 0;
}

// Pagination setup
$perPage = 3;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $perPage;

// Count total reservations for pagination
$totalStmt = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE user_id = ?");
$totalStmt->execute([$userId]);
$totalReservations = (int)$totalStmt->fetchColumn();
$totalPages = ceil($totalReservations / $perPage);

// Fetch user's reservations with status ordering and pagination
try {
    $stmt = $pdo->prepare("
        SELECT r.*, b.TITLE AS book_title_link, b.AUTHOR 
        FROM reservations r
        JOIN books b ON r.book_id = b.id
        WHERE r.user_id = ?
        ORDER BY FIELD(r.status, 'confirmed', 'pending', 'cancelled', 'expired'), r.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->bindValue(1, $userId, PDO::PARAM_INT);
    $stmt->bindValue(2, $perPage, PDO::PARAM_INT);
    $stmt->bindValue(3, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Query failed: " . $e->getMessage());
    $reservations = [];
}
?>

<?php require __DIR__ . '/../views/header.php'; ?>

<main class="max-w-3xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-4">📌 My Reservations</h1>

    <!-- Reservation note -->
    <div class="mb-6 p-4 bg-blue-50 border-l-4 border-blue-400 text-blue-800">
        <p>ℹ️ You can reserve a maximum of 3 books at a time, and you can only reserve a book once.</p>
        <p>Currently, you have <strong><?= $activeReservations ?></strong> active reservation<?= $activeReservations === 1 ? '' : 's' ?>.</p>
    </div>

    <?php if (!$reservations): ?>
        <p>You have no active reservations.</p>
    <?php else: ?>
        <div class="grid gap-4">
            <?php foreach ($reservations as $r): ?>
                <?php
                    // Status color mapping
                    $statusColors = [
                        'pending' => 'bg-yellow-300 text-yellow-800',
                        'borrowed' => 'bg-green-300 text-green-800',
                        'cancelled' => 'bg-red-300 text-red-800',
                        'expired' => 'bg-gray-300 text-gray-800',
                    ];
                    $statusClass = $statusColors[strtolower($r['status'])] ?? 'bg-gray-300 text-gray-800';
                ?>
                <div class="flex items-center justify-between p-4 border rounded-md bg-white shadow hover:shadow-md transition hover:bg-blue-50">
                    <div>
                        <!-- Book title clickable -->
                        <div class="text-lg font-semibold text-blue-700 mb-1">
                            <a href="../views/book_detail.php?title=<?= urlencode($r['book_title_link']) ?>"
                               class="text-blue-600 hover:underline">
                               <?= htmlspecialchars($r['book_title_link']) ?>
                            </a>
                        </div>
                        <div class="text-sm text-gray-600 mb-1">
                            👤 <?= htmlspecialchars($r['AUTHOR'] ?? 'Unknown') ?><br>
                            Created: <?= $r['created_at'] ?><br>
                            Status: 
                            <span class="inline-block px-2 py-1 rounded text-sm font-semibold <?= $statusClass ?>">
                                <?= ucfirst($r['status']) ?>
                            </span>
                        </div>
                    </div>

                    <div class="flex gap-3">
                        <?php if ($r['status'] === 'pending'): ?>
                            <form method="post" action="cancel_reservation.php" onsubmit="return confirm('Cancel this reservation?');">
                                <input type="hidden" name="reservation_id" value="<?= htmlspecialchars($r['reservation_id']) ?>">
                                <button type="submit" class="bg-red-500 text-white px-5 py-2 rounded text-sm hover:bg-red-600">
                                    Cancel
                                </button>
                            </form>

                            <?php if (($r['rescheduled'] ?? 0) == 0): ?>
                                <form method="post" action="reschedule_reservation.php">
                                    <input type="hidden" name="reservation_id" value="<?= htmlspecialchars($r['reservation_id']) ?>">
                                    <button type="submit" class="bg-yellow-500 text-white px-5 py-2 rounded text-sm hover:bg-yellow-600">
                                        Reschedule
                                    </button>
                                </form>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="text-gray-500 text-sm px-3 py-2 rounded">N/A</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="mt-6 flex justify-center gap-2">
                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                    <a href="?page=<?= $p ?>" class="px-3 py-1 rounded <?= $p == $page ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' ?>">
                        <?= $p ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</main>

<?php require __DIR__ . '/../views/footer.php'; ?>
