<?php
session_start();
$pageTitle = "Manage Reservations";

// Include database connection
include('../../includes/db.php'); // Adjust path as needed
require_once __DIR__ . '/../../includes/reservation_mailer.php';

// Only handle POST actions for reservations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reservation_id'], $_POST['action'])) {
    header('Content-Type: application/json'); // Ensure JSON response
    try {
        $reservationId = intval($_POST['reservation_id']);
        $action = strtolower(trim($_POST['action']));

        if (!in_array($action, ['confirm','cancel','done'])) {
            throw new Exception("Invalid action.");
        }

        // Fetch reservation details first
        $stmtRes = $conn->prepare("SELECT r.book_title, r.user_id, u.first_name, u.email FROM reservations r JOIN users u ON r.user_id=u.id WHERE r.reservation_id=?");
        $stmtRes->execute([$reservationId]);
        $resData = $stmtRes->fetch(PDO::FETCH_ASSOC);
        if (!$resData) throw new Exception("Reservation not found.");

        $bookTitle = $resData['book_title'];
        $userEmail = $resData['email'];
        $userName = $resData['first_name'];

        if ($action === 'confirm') {
            $stmt = $conn->prepare("UPDATE reservations SET status='borrowed', done=0 WHERE reservation_id=?");
            $stmt->execute([$reservationId]);
            $newStatus = 'borrowed';
            $done = 0;

            sendReservationConfirmedEmail($userEmail, $userName, $bookTitle);

        } elseif ($action === 'cancel') {
            $stmt = $conn->prepare("UPDATE reservations SET status='cancelled', done=1 WHERE reservation_id=?");
            $stmt->execute([$reservationId]);
            $newStatus = 'cancelled';
            $done = 1;

            sendReservationCancelledEmail($userEmail, $userName, $bookTitle);

        } elseif ($action === 'done') {
            $conn->beginTransaction();
            try {
                $stmt = $conn->prepare("UPDATE reservations SET done=1 WHERE reservation_id=?");
                $stmt->execute([$reservationId]);

                $stmt2 = $conn->prepare("
                    UPDATE books b
                    JOIN reservations r ON b.TITLE = r.book_title
                    SET b.status='available'
                    WHERE r.reservation_id=?
                ");
                $stmt2->execute([$reservationId]);

                $stmt3 = $conn->prepare("SELECT status FROM reservations WHERE reservation_id=?");
                $stmt3->execute([$reservationId]);
                $newStatus = $stmt3->fetchColumn();

                $conn->commit();
                $done = 1;

                sendBookReturnedEmail($userEmail, $userName, $bookTitle);

            } catch (Exception $e) {
                $conn->rollBack();
                throw $e;
            }
        }

        echo json_encode([
            'success' => true,
            'new_status' => $newStatus,
            'done' => $done
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit;
}

// Fetch all reservations
$reservationsQuery = "
    SELECT r.reservation_id, r.book_title, r.pickup_time, r.expiry_time, r.status, r.done, r.reservation_type, r.created_at,
           u.first_name, u.last_name
    FROM reservations r
    JOIN users u ON r.user_id = u.id
    ORDER BY r.created_at DESC
";
$stmt = $conn->query($reservationsQuery);
$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= $pageTitle ?></title>
<link rel="stylesheet" href="reservations.css">
</head>
<body>
<h1><?= $pageTitle ?></h1>
<a href="../../admin/reservations.php" class="btn-back">← Back to Admin Hub</a>

<!-- Filters -->
<div class="filters">
    <label>
        Status:
        <select id="filter-status">
            <option value="">All</option>
            <option value="pending">Pending</option>
            <option value="borrowed">Borrowed</option>
            <option value="cancelled">Cancelled</option>
            <option value="expired">Expired</option>
        </select>
    </label>
    <label>
        Type:
        <select id="filter-type">
            <option value="">All</option>
            <option value="user">User</option>
            <option value="walk-in">Walk-in</option>
        </select>
    </label>
    <button id="clear-filters" class="action-btn">Clear Filters</button>
</div>


<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>User</th>
            <th>Book Title</th>
            <th>Pickup Time</th>
            <th>Expiry Time</th>
            <th>Status</th>
            <th>Done</th>
            <th>Type</th>
            <th>Created At</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($reservations as $row): 
            $now = new DateTime();
            $expiry = new DateTime($row['expiry_time']);
            $statusClass = $row['status'];
        ?>
        <tr id="row-<?= $row['reservation_id'] ?>">
            <td><?= $row['reservation_id'] ?></td>
            <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
            <td><?= htmlspecialchars($row['book_title']) ?></td>
            <td><?= $row['pickup_time'] ?></td>
            <td><?= $row['expiry_time'] ?></td>
            <td id="status-<?= $row['reservation_id'] ?>">
                    <span class="status-<?= $statusClass ?>"><?= $row['status'] ?></span>
            </td>
            <td><?= $row['done'] == 0 ? 'No' : 'Yes' ?></td>
            <td><?= $row['reservation_type'] ?></td>
            <td><?= $row['created_at'] ?></td>
            <td>
                <?php if (in_array($row['status'], ['pending','borrowed']) && $row['done'] == 0): ?>
                    <div class="action-container" id="container-<?= $row['reservation_id'] ?>">
                        <div class="action-buttons" id="buttons-<?= $row['reservation_id'] ?>">
                            <?php if ($row['reservation_type'] !== 'walk-in'): ?>
                                <button class="action-btn btn-confirm" onclick="showPrompt(<?= $row['reservation_id'] ?>, 'confirm')">Confirm</button>
                                <button class="action-btn btn-cancel" onclick="showPrompt(<?= $row['reservation_id'] ?>, 'cancel')">Cancel</button>
                            <?php endif; ?>
                            <button class="action-btn btn-done" onclick="showPrompt(<?= $row['reservation_id'] ?>, 'done')">Done</button>
                        </div>
                        <div class="overlay-prompt" id="prompt-<?= $row['reservation_id'] ?>">
                            <div class="prompt-content">
                                <div class="prompt-text">
                                    Mark this reservation as <strong id="prompt-action-text-<?= $row['reservation_id'] ?>"></strong>?
                                </div>
                                <div class="prompt-buttons">
                                    <button class="prompt-btn yes" onclick="executeAction(<?= $row['reservation_id'] ?>)">Yes, Continue</button>
                                    <button class="prompt-btn no" onclick="hidePrompt(<?= $row['reservation_id'] ?>)">Cancel</button>
                                </div>
                            </div>
                        </div>
                        <div class="action-result" id="result-<?= $row['reservation_id'] ?>">
                            <span id="result-text-<?= $row['reservation_id'] ?>"></span>
                        </div>
                    </div>
                <?php else: ?>
                    <em>No actions available</em>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<script src="reservations.js"></script>
</body>
</html>
