<?php
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Generic email sender (with optional attachment).
 */
function sendEventBaseEmail($toEmail, $toName, $subject, $body, $isHTML = true, $attachmentPath = null) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'hextech.abcy@gmail.com'; // your Gmail
        $mail->Password = 'brgm uejx knoj upsi';    // Gmail app password
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('hextech.abcy@gmail.com', 'Library App');
        $mail->addAddress($toEmail, $toName);

        if ($attachmentPath && file_exists($attachmentPath)) {
            $mail->addAttachment($attachmentPath);
        }

        $mail->isHTML($isHTML);
        $mail->Subject = $subject;
        $mail->Body = $body;

        $mail->send();
    } catch (Exception $e) {
        error_log("Event email could not be sent to {$toEmail}. Error: {$mail->ErrorInfo}");
    }
}

/**
 * Sends an event proposal confirmation email.
 */
function sendEventProposalEmail($toEmail, $toName, $eventTitle, $description, $contact, $filePath = null, $eventDate = null, $eventTime = null) {
    $body = "
    <div style='font-family: Arial, sans-serif; line-height: 1.6;'>
        <h2 style='color: #2c3e50;'>📅 Event Proposal Submitted</h2>
        <p>Hello <strong>{$toName}</strong>,</p>
        <p>Your event proposal has been successfully recorded with the following details:</p>

        <table style='border-collapse: collapse; width: 100%;'>
            <tr>
                <td style='padding:8px; border:1px solid #ddd;'>Event Title</td>
                <td style='padding:8px; border:1px solid #ddd;'>{$eventTitle}</td>
            </tr>
            <tr>
                <td style='padding:8px; border:1px solid #ddd;'>Description</td>
                <td style='padding:8px; border:1px solid #ddd;'>{$description}</td>
            </tr>
            <tr>
                <td style='padding:8px; border:1px solid #ddd;'>Event Date</td>
                <td style='padding:8px; border:1px solid #ddd;'>" . ($eventDate ? htmlspecialchars($eventDate) : "Not specified") . "</td>
            </tr>
            <tr>
                <td style='padding:8px; border:1px solid #ddd;'>Time</td>
                <td style='padding:8px; border:1px solid #ddd;'>" . date("g:i A", strtotime($eventTime)) . "</td>
            </tr>

            <tr>
                <td style='padding:8px; border:1px solid #ddd;'>Contact</td>
                <td style='padding:8px; border:1px solid #ddd;'>{$contact}</td>
            </tr>
            <tr>
                <td style='padding:8px; border:1px solid #ddd;'>Submitted By</td>
                <td style='padding:8px; border:1px solid #ddd;'>{$toEmail}</td>
            </tr>
        </table>

        <p>✅ We have received your submission. Please wait for further review by our team.</p>
        <p>Thank you,<br><strong>Library Team</strong></p>
    </div>
    ";

    sendEventBaseEmail(
        $toEmail,
        $toName,
        "Event Proposal Confirmation - {$eventTitle}",
        $body,
        true,
        $filePath
    );
}

function sendCancelledProposalEmail($toEmail, $toName, $eventTitle, $description, $contact, $dateSubmitted) {
    $body = "
    <div style='font-family: Arial, sans-serif; line-height: 1.6;'>
        <h2 style='color: #dc2626;'>❌ Event Proposal Cancelled</h2>
        <p>Hello <strong>{$toName}</strong>,</p>
        <p>You have successfully cancelled your event proposal:</p>
        <table style='border-collapse: collapse; width: 100%;'>
            <tr>
                <td style='padding:8px; border:1px solid #ddd;'>Event Title</td>
                <td style='padding:8px; border:1px solid #ddd;'>{$eventTitle}</td>
            </tr>
            <tr>
                <td style='padding:8px; border:1px solid #ddd;'>Description</td>
                <td style='padding:8px; border:1px solid #ddd;'>{$description}</td>
            </tr>
            <tr>
                <td style='padding:8px; border:1px solid #ddd;'>Contact</td>
                <td style='padding:8px; border:1px solid #ddd;'>{$contact}</td>
            </tr>
            <tr>
                <td style='padding:8px; border:1px solid #ddd;'>Date Submitted</td>
                <td style='padding:8px; border:1px solid #ddd;'>{$dateSubmitted}</td>
            </tr>
            <tr>
                <td style='padding:8px; border:1px solid #ddd;'>Status</td>
                <td style='padding:8px; border:1px solid #ddd; color:#dc2626; font-weight:bold;'>CANCELLED</td>
            </tr>
        </table>
        <p>If this was a mistake, please contact the library staff.</p>
        <p>Thank you,<br><strong>Library Team</strong></p>
    </div>
    ";

    // ✅ Fixed: use sendEventBaseEmail instead of undefined sendEmail
    sendEventBaseEmail(
        $toEmail,
        $toName,
        "Event Proposal Cancelled - {$eventTitle}",
        $body,
        true
    );
}

function sendProposalExpiryEmail($toEmail, $toName, $eventTitle, $description = '', $eventDate = null, $eventTime = null) {
    $body = "
    <div style='font-family: Arial, sans-serif; line-height: 1.6;'>
        <h2 style='color: #dc2626;'>❌ Event Proposal Expired</h2>
        <p>Hello <strong>{$toName}</strong>,</p>
        <p>Your event proposal has expired because it was not approved within the allowed time:</p>

        <table style='border-collapse: collapse; width: 100%;'>
            <tr>
                <td style='padding:8px; border:1px solid #ddd;'>Event Title</td>
                <td style='padding:8px; border:1px solid #ddd;'>{$eventTitle}</td>
            </tr>
            <tr>
                <td style='padding:8px; border:1px solid #ddd;'>Description</td>
                <td style='padding:8px; border:1px solid #ddd;'>{$description}</td>
            </tr>
            <tr>
                <td style='padding:8px; border:1px solid #ddd;'>Event Date</td>
                <td style='padding:8px; border:1px solid #ddd;'>" . ($eventDate ?? 'Not specified') . "</td>
            </tr>
            <tr>
                <td style='padding:8px; border:1px solid #ddd;'>Event Time</td>
                <td style='padding:8px; border:1px solid #ddd;'>" . ($eventTime ? date("g:i A", strtotime($eventTime)) : "Not specified") . "</td>
            </tr>
            <tr>
                <td style='padding:8px; border:1px solid #ddd;'>Status</td>
                <td style='padding:8px; border:1px solid #ddd; color:#dc2626; font-weight:bold;'>EXPIRED</td>
            </tr>
        </table>

        <p>If you want, you may submit a new proposal for the event.</p>
        <p>Thank you,<br><strong>Library Team</strong></p>
    </div>
    ";

    sendEventBaseEmail(
        $toEmail,
        $toName,
        "Event Proposal Expired - {$eventTitle}",
        $body,
        true
    );
}

function sendProposalAcceptedEmail($toEmail, $toName, $eventTitle, $description = '', $eventDate = null, $eventTime = null) {
    $body = "
    <div style='font-family: Arial, sans-serif; line-height: 1.6;'>
        <h2 style='color: #16a34a;'>✅ Event Proposal Accepted</h2>
        <p>Hello <strong>{$toName}</strong>,</p>
        <p>Your event proposal has been <strong>accepted</strong>:</p>

        <table style='border-collapse: collapse; width: 100%;'>
            <tr>
                <td style='padding:8px; border:1px solid #ddd;'>Event Title</td>
                <td style='padding:8px; border:1px solid #ddd;'>{$eventTitle}</td>
            </tr>
            <tr>
                <td style='padding:8px; border:1px solid #ddd;'>Description</td>
                <td style='padding:8px; border:1px solid #ddd;'>{$description}</td>
            </tr>
            <tr>
                <td style='padding:8px; border:1px solid #ddd;'>Event Date</td>
                <td style='padding:8px; border:1px solid #ddd;'>" . ($eventDate ?? 'Not specified') . "</td>
            </tr>
            <tr>
                <td style='padding:8px; border:1px solid #ddd;'>Event Time</td>
                <td style='padding:8px; border:1px solid #ddd;'>" . ($eventTime ? date("g:i A", strtotime($eventTime)) : "Not specified") . "</td>
            </tr>
            <tr>
                <td style='padding:8px; border:1px solid #ddd;'>Status</td>
                <td style='padding:8px; border:1px solid #ddd; color:#16a34a; font-weight:bold;'>ACCEPTED</td>
            </tr>
        </table>

        <p><strong>Important:</strong> Please go to <em>Kaban ng Hiyas Congressional Library</em> as soon as possible to discuss the event with the officer in charge.</p>

        <p>Thank you for submitting your event proposal! We look forward to your event.</p>
        <p>Library Team</p>
    </div>
    ";

    sendEventBaseEmail(
        $toEmail,
        $toName,
        "Event Proposal Accepted - {$eventTitle}",
        $body,
        true
    );
}

function sendProposalRejectedEmail($toEmail, $toName, $eventTitle, $description = '', $eventDate = null, $eventTime = null) {
    $body = "
    <div style='font-family: Arial, sans-serif; line-height: 1.6;'>
        <h2 style='color: #dc2626;'>❌ Event Proposal Rejected</h2>
        <p>Hello <strong>{$toName}</strong>,</p>
        <p>Unfortunately, your event proposal has been <strong>rejected</strong>:</p>

        <table style='border-collapse: collapse; width: 100%;'>
            <tr>
                <td style='padding:8px; border:1px solid #ddd;'>Event Title</td>
                <td style='padding:8px; border:1px solid #ddd;'>{$eventTitle}</td>
            </tr>
            <tr>
                <td style='padding:8px; border:1px solid #ddd;'>Description</td>
                <td style='padding:8px; border:1px solid #ddd;'>{$description}</td>
            </tr>
            <tr>
                <td style='padding:8px; border:1px solid #ddd;'>Event Date</td>
                <td style='padding:8px; border:1px solid #ddd;'>" . ($eventDate ?? 'Not specified') . "</td>
            </tr>
            <tr>
                <td style='padding:8px; border:1px solid #ddd;'>Event Time</td>
                <td style='padding:8px; border:1px solid #ddd;'>" . ($eventTime ? date("g:i A", strtotime($eventTime)) : "Not specified") . "</td>
            </tr>
            <tr>
                <td style='padding:8px; border:1px solid #ddd;'>Status</td>
                <td style='padding:8px; border:1px solid #ddd; color:#dc2626; font-weight:bold;'>REJECTED</td>
            </tr>
        </table>

        <p>You may submit a new proposal if you wish.</p>
        <p>Library Team</p>
    </div>
    ";

    sendEventBaseEmail(
        $toEmail,
        $toName,
        "Event Proposal Rejected - {$eventTitle}",
        $body,
        true
    );
}