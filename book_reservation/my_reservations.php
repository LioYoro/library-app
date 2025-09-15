<?php
session_start();
require_once __DIR__ . '/../views/header.php'; // load header first

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    echo "<div class='max-w-3xl mx-auto px-4 py-8'>";
    echo "<p class='text-red-600 font-semibold p-4 bg-red-50 border-l-4 border-red-400'>You must be logged in to view your borrow requests.</p>";
    echo "</div>";
    require_once __DIR__ . '/../views/footer.php';
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

// Filter handling
$filter = $_GET['filter'] ?? '';

// Pagination setup
$perPage = 3;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $perPage;

// Count total reservations for pagination with filter
$whereCount = "WHERE user_id = ?";
$paramsCount = [$userId];

if ($filter) {
    if ($filter === 'finished') {
        $whereCount .= " AND done = 1";
    } else {
        $whereCount .= " AND status = ?";
        $paramsCount[] = $filter;
    }
}
$totalStmt = $pdo->prepare("SELECT COUNT(*) FROM reservations $whereCount");
$totalStmt->execute($paramsCount);
$totalReservations = (int)$totalStmt->fetchColumn();
$totalPages = ceil($totalReservations / $perPage);

// Fetch reservations with filter and pagination
$whereClause = "WHERE r.user_id = ?";
$params = [$userId];

if ($filter) {
    if ($filter === 'finished') {
        $whereClause .= " AND r.done = 1";
    } else {
        $whereClause .= " AND r.status = ?";
        $params[] = $filter;
    }
}

try {
    $stmt = $pdo->prepare("
        SELECT r.*, b.TITLE AS book_title_link, b.AUTHOR 
        FROM reservations r
        JOIN books b ON r.book_id = b.id
        $whereClause
        ORDER BY FIELD(r.status, 'confirmed', 'pending', 'cancelled', 'expired'), r.created_at DESC
        LIMIT ? OFFSET ?
    ");
    foreach ($params as $i => $param) {
        $stmt->bindValue($i + 1, $param);
    }
    $stmt->bindValue(count($params) + 1, $perPage, PDO::PARAM_INT);
    $stmt->bindValue(count($params) + 2, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Query failed: " . $e->getMessage());
    $reservations = [];
}

?>

<main class="max-w-3xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-4">📌 My Borrowing List</h1>

    <!-- Reservation note -->
    <div class="mb-6 p-4 bg-blue-50 border-l-4 border-blue-400 text-blue-800">
        <p>ℹ️ You can borrow a maximum of 3 books at a time, and you can only borrow a book once.</p>
        <p>Currently, you have <strong><?= $activeReservations ?></strong> active borrow request<?= $activeReservations === 1 ? '' : 's' ?>.</p>
    </div>

    <!-- Filter dropdown -->
    <div class="mb-6 flex items-center gap-4">
        <label for="status_filter" class="font-medium text-gray-700">Filter by Status:</label>
        <form method="GET" id="filterForm">
            <select name="filter" id="status_filter" class="px-3 py-2 border rounded-md" onchange="document.getElementById('filterForm').submit();">
                <option value="">All</option>
                <option value="pending" <?= ($filter === 'pending') ? 'selected' : '' ?>>Pending</option>
                <option value="borrowed" <?= ($filter === 'borrowed') ? 'selected' : '' ?>>Borrowed</option>
                <option value="finished" <?= ($filter === 'finished') ? 'selected' : '' ?>>Finished</option>
                <option value="cancelled" <?= ($filter === 'cancelled') ? 'selected' : '' ?>>Cancelled</option>
                <option value="expired" <?= ($filter === 'expired') ? 'selected' : '' ?>>Expired</option>
            </select>
        </form>
    </div>

    <?php if (!$reservations): ?>
        <p>You have no borrow requests<?= $filter ? " that has been marked as '$filter'" : '' ?>.</p>
    <?php else: ?>
        <div class="grid gap-4">
            <?php foreach ($reservations as $r): ?>
                <?php
                    $displayStatus = ($r['done'] == 1) ? 'Finished' : ucfirst($r['status']);
                    $statusColors = [
                        'pending' => 'bg-yellow-300 text-yellow-800',
                        'borrowed' => 'bg-green-300 text-green-800',
                        'finished' => 'bg-gray-400 text-white',
                        'cancelled' => 'bg-red-300 text-red-800',
                        'expired' => 'bg-gray-300 text-gray-800',
                    ];
                    $statusClass = $statusColors[strtolower($displayStatus)] ?? 'bg-gray-300 text-gray-800';
                ?>
                <div class="flex items-center justify-between p-4 border rounded-md bg-white shadow hover:shadow-md transition hover:bg-blue-50">
                    <div>
                        <div class="text-lg font-semibold text-blue-700 mb-1">
                            <a href="../views/book_detail.php?title=<?= urlencode($r['book_title_link']) ?>" class="text-blue-600 hover:underline">
                               <?= htmlspecialchars($r['book_title_link']) ?>
                            </a>
                        </div>
                        <div class="text-sm text-gray-600 mb-1">
                            👤 <?= htmlspecialchars($r['AUTHOR'] ?? 'Unknown') ?><br>
                            Created: <?= $r['created_at'] ?><br>
                            Status: 
                            <span class="inline-block px-2 py-1 rounded text-sm font-semibold <?= $statusClass ?>">
                                <?= htmlspecialchars($displayStatus) ?>
                            </span>
                        </div>
                    </div>

                    <div class="flex gap-3">
                        <?php if ($r['status'] === 'pending' && $r['done'] == 0): ?>
                            <button 
                                class="cancel-btn bg-red-500 text-white px-5 py-2 rounded text-sm hover:bg-red-600"
                                data-reservation-id="<?= htmlspecialchars($r['reservation_id']) ?>"
                                data-book-title="<?= htmlspecialchars($r['book_title_link']) ?>"
                            >
                                Cancel
                            </button>

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
                    <a href="?page=<?= $p ?>&filter=<?= urlencode($filter) ?>" class="px-3 py-1 rounded <?= $p == $page ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' ?>">
                        <?= $p ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</main>

<!-- Cancel Confirmation Modal -->
<div id="cancelModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-96">
        <h2 class="text-lg font-bold mb-4">Cancel Borrow Request</h2>
        <p class="mb-6" id="modalMessage">Are you sure you want to cancel this request?</p>
        <div class="flex justify-end gap-4">
            <button id="modalCancel" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">No</button>
            <form id="modalForm" method="post" action="cancel_reservation.php">
                <input type="hidden" name="reservation_id" id="modalReservationId">
                <button type="submit" class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600">Yes, Cancel</button>
            </form>
        </div>
    </div>
</div>

<script>
    const modal = document.getElementById('cancelModal');
    const modalReservationId = document.getElementById('modalReservationId');
    const modalMessage = document.getElementById('modalMessage');
    const modalCancel = document.getElementById('modalCancel');

    document.querySelectorAll('.cancel-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const resId = btn.dataset.reservationId;
            const bookTitle = btn.dataset.bookTitle;

            modalReservationId.value = resId;
            modalMessage.innerHTML = `Are you sure you want to cancel the reservation for "<strong>${bookTitle}</strong>"?`;

            modal.classList.remove('hidden');
            modal.classList.add('flex');
        });
    });

    modalCancel.addEventListener('click', () => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    });
</script>



<?php require __DIR__ . '/../views/footer.php'; ?>
