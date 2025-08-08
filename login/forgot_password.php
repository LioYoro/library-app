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
    
    if (empty($email)) {
        echo "Email is required.";
        exit;
    }
    
    try {
        // Check if email exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Generate code
            $code = rand(100000, 999999);
            $_SESSION['reset_email'] = $email;
            $_SESSION['reset_code'] = $code;
            $_SESSION['reset_code_expires'] = time() + (5 * 60); // 5 minutes expiry
            
            // Send email
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'hextech.abcy@gmail.com';
                $mail->Password = 'brgm uejx knoj upsi'; // Use your app password
                $mail->SMTPSecure = 'tls';
                $mail->Port = 587;
                
                $mail->setFrom('hextech.abcy@gmail.com', 'Library App');
                $mail->addAddress($email);
                $mail->Subject = 'Your Password Reset Code';
                $mail->Body = "Your password reset code is: $code\n\nThis code will expire in 5 minutes.";
                
                $mail->send();
                echo "success";
            } catch (Exception $e) {
                echo "Email could not be sent. Mailer Error: {$mail->ErrorInfo}";
            }
        } else {
            echo "Email not found.";
        }
    } catch (PDOException $e) {
        echo "Database error: " . $e->getMessage();
    }
} else {
    echo "Invalid request method.";
}
?>