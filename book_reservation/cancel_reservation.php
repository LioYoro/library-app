<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/login.php");
    exit;
}

require_once __DIR__ . '/../includes/reservation_mailer.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reservation_id'])) {
    try {
        $pdo = new PDO("mysql:host=localhost;dbname=library_test_db", "root", "");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $reservationId = $_POST['reservation_id'];
        $userId = $_SESSION['user_id'];

        // Fetch reservation details for email
        $stmt = $pdo->prepare("
            SELECT r.*, b.TITLE, b.AUTHOR, b.`CALL NUMBER`, b.`ACCESSION NO.`, u.email, u.first_name
            FROM reservations r
            JOIN books b ON r.book_id = b.id
            JOIN users u ON r.user_id = u.id
            WHERE r.reservation_id = ? AND r.user_id = ?
        ");
        $stmt->execute([$reservationId, $userId]);
        $reservation = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($reservation) {
            // Update the reservation status to cancelled
            $updateStmt = $pdo->prepare("UPDATE reservations SET status = 'cancelled' WHERE reservation_id = ? AND user_id = ?");
            $updateStmt->execute([$reservationId, $userId]);
            
            // Send cancellation email
            sendReservationCancelledByUserEmail(
                $reservation['email'],
                $reservation['first_name'],
                $reservation['reservation_id'],
                $reservation['TITLE'],
                $reservation['AUTHOR'],
                $reservation['created_at']
            );

            $_SESSION['message'] = "Reservation cancelled successfully.";
        } else {
            $_SESSION['error'] = "Reservation not found or cannot be cancelled.";
        }
    } catch (PDOException $e) {
        error_log("Cancel reservation error: " . $e->getMessage());
        $_SESSION['error'] = "An error occurred while cancelling the reservation.";
    }
}

// Redirect back
header("Location: my_reservations.php");
exit;
?>
