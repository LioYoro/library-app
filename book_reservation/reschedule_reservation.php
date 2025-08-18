<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/login.php");
    exit;
}

$pdo = new PDO("mysql:host=localhost;dbname=library_test_db", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$userId = $_SESSION['user_id'];
$message = "";
$error = "";

// Check if reservation ID is passed
$reservationId = $_POST['reservation_id'] ?? null;

if (!$reservationId) {
    $error = "No reservation selected to reschedule.";
}

// Fetch reservation details
if (!$error) {
    $stmt = $pdo->prepare("SELECT r.*, b.TITLE, b.id AS book_id FROM reservations r JOIN books b ON r.book_id = b.id WHERE r.reservation_id = ? AND r.user_id = ? AND r.status = 'pending' AND (r.rescheduled IS NULL OR r.rescheduled = 0)");
    $stmt->execute([$reservationId, $userId]);
    $reservation = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reservation) {
        $error = "Reservation cannot be rescheduled or already rescheduled.";
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_reservation']) && $reservation) {
    $pickupDate = $_POST['pickup_date'] ?? '';
    $pickupTime = $_POST['pickup_time'] ?? '';

    $today = date('Y-m-d');
    $tomorrow = date('Y-m-d', strtotime('+1 day'));
    $nowTime = date('H:i');

    // Restriction: only today or tomorrow
    if (!in_array($pickupDate, [$today, $tomorrow])) {
        $error = "You can only select today or tomorrow for pickup.";
    }

    // Restriction: if today, must be after 07:30 and before 19:00
    if (!$error && $pickupDate === $today) {
        if ($nowTime >= '19:00') {
            $error = "It's past 7:00 PM today. Please reserve for tomorrow after 7:30 AM.";
        } elseif ($pickupTime <= $nowTime) {
            $error = "Selected time is already past. Please choose a later time.";
        } elseif ($pickupTime < '07:30' || $pickupTime > '19:00') {
            $error = "Pickup time must be between 7:30 AM and 7:00 PM.";
        }
    }

    // Restriction: if tomorrow, must be between 07:30 and 19:00
    if (!$error && $pickupDate === $tomorrow) {
        if ($pickupTime < '07:30' || $pickupTime > '19:00') {
            $error = "Pickup time must be between 7:30 AM and 7:00 PM.";
        }
    }

    // Update reservation if all checks pass
    if (!$error) {
        $updateStmt = $pdo->prepare("UPDATE reservations SET created_at = CONCAT(?, ' ', ?), rescheduled = 1 WHERE reservation_id = ? AND user_id = ?");
        $updateStmt->execute([$pickupDate, $pickupTime, $reservationId, $userId]);
        $message = "Reservation rescheduled successfully!";
        $reservation['created_at'] = $pickupDate . ' ' . $pickupTime;
        $reservation['rescheduled'] = 1;
    }
}
?>

<?php require __DIR__ . '/../views/header.php'; ?>

<main class="max-w-3xl mx-auto px-4 py-8">

    <h1 class="text-2xl font-bold mb-4">🕐 Reschedule Reservation</h1>

    <?php if ($error): ?>
        <p class="text-red-600 font-bold mb-4"><?= htmlspecialchars($error) ?></p>
    <?php elseif ($message): ?>
        <p class="text-green-600 font-bold mb-4"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <?php if ($reservation): ?>
    <div class="bg-white p-6 rounded shadow space-y-4">
        <h2 class="font-semibold text-lg"><?= htmlspecialchars($reservation['TITLE']) ?></h2>
        <p class="text-sm text-gray-600">Author: <?= htmlspecialchars($reservation['AUTHOR']) ?></p>

        <form method="POST" class="space-y-4">
            <input type="hidden" name="reservation_id" value="<?= htmlspecialchars($reservation['reservation_id']) ?>">

            <div>
                <label for="pickup_date" class="block text-sm font-medium text-gray-700 mb-1">📅 Pickup Date</label>
                <input type="date" id="pickup_date" name="pickup_date" required
                       min="<?= date('Y-m-d') ?>"
                       max="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                       class="w-full px-3 py-2 border rounded-md"
                       value="<?= $_POST['pickup_date'] ?? date('Y-m-d', strtotime($reservation['created_at'])) ?>">
            </div>

            <div>
                <label for="pickup_time" class="block text-sm font-medium text-gray-700 mb-1">🕐 Pickup Time</label>
                <select id="pickup_time" name="pickup_time" required class="w-full px-3 py-2 border rounded-md">
                    <option value="">Select pickup time</option>
                    <?php
                    $start = new DateTime('07:30');
                    $end = new DateTime('19:00');
                    $interval = new DateInterval('PT30M');
                    while ($start <= $end) {
                        $timeValue = $start->format('H:i');
                        $timeDisplay = $start->format('g:i A');
                        $selected = ($_POST['pickup_time'] ?? date('H:i', strtotime($reservation['created_at']))) === $timeValue ? 'selected' : '';
                        echo "<option value=\"$timeValue\" $selected>$timeDisplay</option>";
                        $start->add($interval);
                    }
                    ?>
                </select>
            </div>

            <div class="flex gap-3 pt-4">
                <button type="submit" name="submit_reservation" class="flex-1 bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700">
                    📌 Submit Reschedule
                </button>
                <a href="my_reservations.php" class="flex-1 bg-gray-500 text-white py-2 px-4 rounded-md hover:bg-gray-600 text-center">Cancel</a>
            </div>
        </form>
    </div>
    <?php endif; ?>

</main>

<?php if ($message): ?>
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative space-y-4">
        <p class="font-bold"><?= htmlspecialchars($message) ?></p>
        <div class="flex gap-3">
            <!-- Back to My Reservations -->
            <a href="my_reservations.php" class="flex-1 bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 text-center">
                ✅ Back to My Reservations
            </a>
            <!-- Cancel / Close -->
            <a href="reschedule_reservation.php?reservation_id=<?= htmlspecialchars($reservation['reservation_id']) ?>" 
               class="flex-1 bg-gray-500 text-white py-2 px-4 rounded-md hover:bg-gray-600 text-center">
               ✖ Cancel
            </a>
        </div>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../views/footer.php'; ?>
