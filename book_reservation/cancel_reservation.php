<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reservation_id'])) {
    try {
        $pdo = new PDO("mysql:host=localhost;dbname=library_test_db", "root", "");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $reservationId = $_POST['reservation_id'];
        $userId = $_SESSION['user_id'];
        
        // Verify the reservation belongs to the user (any status)
        $stmt = $pdo->prepare("SELECT reservation_id FROM reservations WHERE reservation_id = ? AND user_id = ?");
        $stmt->execute([$reservationId, $userId]);
        
        if ($stmt->fetch()) {
            // Update the reservation status to cancelled
            $updateStmt = $pdo->prepare("UPDATE reservations SET status = 'cancelled' WHERE reservation_id = ? AND user_id = ?");
            $updateStmt->execute([$reservationId, $userId]);
            
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
