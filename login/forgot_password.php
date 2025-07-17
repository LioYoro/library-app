<?php
session_start();
require '../includes/db.php';
require_once '../includes/PHPMailer/src/PHPMailer.php';
require_once '../includes/PHPMailer/src/SMTP.php';
require_once '../includes/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';

    // Check if email exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    if ($user) {
        // Generate code
        $code = rand(100000, 999999);
        $_SESSION['reset_email'] = $email;
        $_SESSION['reset_code'] = $code;

        // Send email
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com'; // or your SMTP
            $mail->SMTPAuth = true;
            $mail->Username = 'hextech.abcy@gmail.com';
            $mail->Password = 'PASTE HERE';
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom('hextech.abcy@gmail.com', 'Library App');
            $mail->addAddress($email);
            $mail->Subject = 'Your Password Reset Code';
            $mail->Body    = "Your password reset code is: $code";
            $mail->send();

            header('Location: verify_reset_code.php');
            exit;
        } catch (Exception $e) {
            echo "Email could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    } else {
        echo "Email not found.";
    }
}
?>

<form method="POST">
    <h2>Forgot Password</h2>
    <input type="email" name="email" required placeholder="Enter your email">
    <button type="submit">Send Code</button>
</form>
