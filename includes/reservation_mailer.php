<?php
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendEmail($toEmail, $toName, $subject, $body, $isHTML = true) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'hextech.abcy@gmail.com'; // your email
        $mail->Password = 'brgm uejx knoj upsi';    // your app password
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('hextech.abcy@gmail.com', 'Library App');
        $mail->addAddress($toEmail, $toName);

        $mail->isHTML($isHTML);
        $mail->Subject = $subject;
        $mail->Body = $body;

        $mail->send();
    } catch (Exception $e) {
        error_log("Email could not be sent to {$toEmail}. Error: {$mail->ErrorInfo}");
    }
}

/**
 * Sends reservation email
 */
function sendReservationEmail($toEmail, $toName, $reservationId, $bookTitle, $author, $callNumber, $accessionNumber, $pickupDateTime, $status = 'Pending') {
    $pickupFormatted = date('F j, Y g:i A', strtotime($pickupDateTime));
    $body = "
    <div style='font-family: Arial, sans-serif; line-height: 1.6;'>
        <h2 style='color: #2c3e50;'>📖 Book Borrow Request Details</h2>
        <p>Hello <strong>{$toName}</strong>,</p>
        <p>Your borrow request has been recorded with the following details:</p>
        <table style='border-collapse: collapse; width: 100%;'>
            <tr><td style='padding:8px; border:1px solid #ddd;'>Request ID</td><td style='padding:8px; border:1px solid #ddd;'>{$reservationId}</td></tr>
            <tr><td style='padding:8px; border:1px solid #ddd;'>Book Title</td><td style='padding:8px; border:1px solid #ddd;'>{$bookTitle}</td></tr>
            <tr><td style='padding:8px; border:1px solid #ddd;'>Author</td><td style='padding:8px; border:1px solid #ddd;'>{$author}</td></tr>
            <tr><td style='padding:8px; border:1px solid #ddd;'>Call Number</td><td style='padding:8px; border:1px solid #ddd;'>{$callNumber}</td></tr>
            <tr><td style='padding:8px; border:1px solid #ddd;'>Accession Number</td><td style='padding:8px; border:1px solid #ddd;'>{$accessionNumber}</td></tr>
            <tr><td style='padding:8px; border:1px solid #ddd;'>Pickup Date & Time</td><td style='padding:8px; border:1px solid #ddd;'>{$pickupFormatted}</td></tr>
            <tr><td style='padding:8px; border:1px solid #ddd;'>Status</td><td style='padding:8px; border:1px solid #ddd;'>{$status}</td></tr>
        </table>
        <p>✅ Please arrive on time for pickup.</p>
        <p>Thank you,<br><strong>Library Team</strong></p>
    </div>
    ";

    sendEmail($toEmail, $toName, 'Book Borrow Request Confirmation', $body, true);
}

function sendAdminReservationNotification($adminEmail, $reservationId, $userName, $bookTitle, $pickupDateTime) {
    $pickupFormatted = date('F j, Y g:i A', strtotime($pickupDateTime));
    $body = "
    <div style='font-family: Arial, sans-serif; line-height: 1.6;'>
        <h2>New Book Borrow Request</h2>
        <p>User <strong>{$userName}</strong> has placed a new borrow request.</p>
        <table style='border-collapse: collapse; width: 100%;'>
            <tr><td style='padding:8px; border:1px solid #ddd;'>Request ID</td><td style='padding:8px; border:1px solid #ddd;'>{$reservationId}</td></tr>
            <tr><td style='padding:8px; border:1px solid #ddd;'>Book Title</td><td style='padding:8px; border:1px solid #ddd;'>{$bookTitle}</td></tr>
            <tr><td style='padding:8px; border:1px solid #ddd;'>Pickup Time</td><td style='padding:8px; border:1px solid #ddd;'>{$pickupFormatted}</td></tr>
        </table>
    </div>
    ";

    sendEmail($adminEmail, 'Admin', 'New Book Borrow Request Placed', $body, true);
}

function sendReservationExpiryEmail($email, $name, $bookTitle, $pickupTime) {
    $subject = "Book Request Expired";
    $pickupFormatted = date('F j, Y g:i A', strtotime($pickupTime));
    $body = "
    <p>Hi {$name},</p>
    <p>Your borrow request for <strong>{$bookTitle}</strong> scheduled at {$pickupFormatted} has expired.</p>
    <p>Please make a new request if needed.</p>
    <p>- Library Team</p>
    ";
    sendEmail($email, $name, $subject, $body, true);
}


function sendReservationReminderEmail($email, $name, $bookTitle, $pickupTime) {
    $subject = "Book Request Reminder";
    $pickupFormatted = date('F j, Y g:i A', strtotime($pickupTime));
    $body = "
    <p>Hi {$name},</p>
    <p>This is a reminder that your borrow request for <strong>{$bookTitle}</strong> is expiring in 1 hour.</p>
    <p>Scheduled pickup: {$pickupFormatted}</p>
    <p>Please pick up your book on time.</p>
    <p>- Library Team</p>
    ";
    sendEmail($email, $name, $subject, $body, true);
}


function sendReservationConfirmedEmail($email, $name, $bookTitle) {
    $subject = "Book Borrow Request Confirmed";
    $body = "Hi $name,\n\nYour book borrow request for \"$bookTitle\" has been confirmed.\nConfirmed at: " . date('Y-m-d H:i:s') . "\n\nThank you for using our library!";
    sendEmail($email, $name, $subject, $body); // <-- pass $name as 2nd argument
}

function sendReservationCancelledEmail($email, $name, $bookTitle) {
    $subject = "Book Borrow Request Cancelled";
    $body = "Hi $name,\n\nYour book borrow request for \"$bookTitle\" has been cancelled by the admin.\n\nThank you for using our library!";
    sendEmail($email, $name, $subject, $body); // <-- pass $name as 2nd argument
}

function sendBookReturnedEmail($email, $name, $bookTitle) {
    $subject = "Book Returned";
    $body = "Hi $name,\n\nYour borrowed book \"$bookTitle\" has been marked as returned. Thank you for using our library!";
    sendEmail($email, $name, $subject, $body); // <-- pass $name as 2nd argument
}

/**
 * Sends email when a reservation is rescheduled
 */
function sendReservationRescheduledEmail($toEmail, $toName, $reservationId, $bookTitle, $author, $callNumber, $accessionNumber, $pickupDateTime) {
    $pickupFormatted = date('F j, Y g:i A', strtotime($pickupDateTime));
    $body = "
    <div style='font-family: Arial, sans-serif; line-height: 1.6;'>
        <h2 style='color: #2c3e50;'>🔄 Book Borrow Request Rescheduled</h2>
        <p>Hello <strong>{$toName}</strong>,</p>
        <p>Your borrow request has been rescheduled with the following details:</p>
        <table style='border-collapse: collapse; width: 100%;'>
            <tr><td style='padding:8px; border:1px solid #ddd;'>Request ID</td><td style='padding:8px; border:1px solid #ddd;'>{$reservationId}</td></tr>
            <tr><td style='padding:8px; border:1px solid #ddd;'>Book Title</td><td style='padding:8px; border:1px solid #ddd;'>{$bookTitle}</td></tr>
            <tr><td style='padding:8px; border:1px solid #ddd;'>Author</td><td style='padding:8px; border:1px solid #ddd;'>{$author}</td></tr>
            <tr><td style='padding:8px; border:1px solid #ddd;'>Call Number</td><td style='padding:8px; border:1px solid #ddd;'>{$callNumber}</td></tr>
            <tr><td style='padding:8px; border:1px solid #ddd;'>Accession Number</td><td style='padding:8px; border:1px solid #ddd;'>{$accessionNumber}</td></tr>
            <tr><td style='padding:8px; border:1px solid #ddd;'>New Pickup Date & Time</td><td style='padding:8px; border:1px solid #ddd;'>{$pickupFormatted}</td></tr>
            <tr><td style='padding:8px; border:1px solid #ddd;'>Status</td><td style='padding:8px; border:1px solid #ddd;'>Rescheduled</td></tr>
        </table>
        <p>✅ Please arrive on time for pickup.</p>
        <p>Thank you,<br><strong>Library Team</strong></p>
    </div>
    ";

    sendEmail($toEmail, $toName, 'Book Borrow Request Rescheduled', $body, true);
}

function sendReservationCancelledByUserEmail($email, $name, $reservationId, $bookTitle, $author, $pickupDateTime) {
    $subject = "Book Borrow Request Cancelled Successfully";
    $pickupFormatted = date('F j, Y g:i A', strtotime($pickupDateTime));

    $body = "
    <div style='font-family: Arial, sans-serif; line-height: 1.6;'>
        <h2 style='color: #c0392b;'>❌ Book Borrow Request Cancelled</h2>
        <p>Hi <strong>{$name}</strong>,</p>
        <p>Your book borrow request has been successfully cancelled. Here are the details of the cancelled request:</p>
        <table style='border-collapse: collapse; width: 100%;'>
            <tr><td style='padding:8px; border:1px solid #ddd;'>Request ID</td><td style='padding:8px; border:1px solid #ddd;'>{$reservationId}</td></tr>
            <tr><td style='padding:8px; border:1px solid #ddd;'>Book Title</td><td style='padding:8px; border:1px solid #ddd;'>{$bookTitle}</td></tr>
            <tr><td style='padding:8px; border:1px solid #ddd;'>Author</td><td style='padding:8px; border:1px solid #ddd;'>{$author}</td></tr>
            <tr><td style='padding:8px; border:1px solid #ddd;'>Original Pickup Time</td><td style='padding:8px; border:1px solid #ddd;'>{$pickupFormatted}</td></tr>
        </table>
        <p>If this was a mistake, you can place a new book borrow request at any time.</p>
        <p>Thank you,<br><strong>Library Team</strong></p>
    </div>
    ";

    sendEmail($email, $name, $subject, $body, true);
}