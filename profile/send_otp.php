<?php
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);



require_once '../includes/db.php';
require_once '../includes/PHPMailer/src/PHPMailer.php';
require_once '../includes/PHPMailer/src/SMTP.php';
require_once '../includes/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    http_response_code(403);
    exit("Not logged in.");
}

// Fetch email
$stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) exit("User not found.");

$email = $user['email'];
$otp = rand(100000, 999999);
$expiry = date("Y-m-d H:i:s", strtotime("+5 minutes"));

// Save to DB
$stmt = $conn->prepare("UPDATE users SET otp_code = ?, otp_expires_at = ? WHERE id = ?");
$stmt->execute([$otp, $expiry, $userId]);

// Send OTP via email
$mail = new PHPMailer;
$mail->isSMTP();
$mail->Host = 'smtp.gmail.com';
$mail->SMTPAuth = true;
$mail->Username = 'hextech.abcy@gmail.com';
$mail->Password = 'brgm uejx knoj upsi'; // replace w/ app password
$mail->SMTPSecure = 'tls';
$mail->Port = 587;

$mail->setFrom('hextech.abcy@gmail.com', 'ARK Library');
$mail->addAddress($email);
$mail->Subject = 'OTP Confirmation for Profile Update';
$mail->Body = "Your OTP code is: $otp\n\nValid for 5 minutes only.";

if (!$mail->send()) {
    http_response_code(500);
    echo "Mailer Error: " . $mail->ErrorInfo;
} else {
    echo "OTP sent successfully.";
}

?>

<!-- OTP Modal -->
<div id="otpModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
  <div class="bg-white p-6 rounded-lg shadow-md w-80">
    <h2 class="text-lg font-semibold mb-2">Enter OTP</h2>
    <input type="text" id="otpInput" class="w-full border px-3 py-2 rounded mb-3" placeholder="6-digit OTP">
    <div id="otpError" class="text-red-500 text-sm mb-2 hidden"></div>
    <div class="flex justify-end gap-2">
      <button id="cancelBtn" class="text-sm px-4 py-1 border border-gray-300 rounded">Cancel</button>
      <button id="confirmOtpBtn" class="bg-blue-600 text-white px-4 py-1 rounded hover:bg-blue-700">Confirm</button>
    </div>
  </div>
</div>

<script>
const profileForm = document.querySelector("#profileForm");
const otpModal = document.querySelector("#otpModal");
const otpInput = document.querySelector("#otpInput");
const otpError = document.querySelector("#otpError");

profileForm.addEventListener("submit", async function (e) {
  e.preventDefault();

  // 1. Send OTP
  const sendOtp = await fetch("send_otp.php");
  const otpResponse = await sendOtp.text();
  if (!otpResponse.includes("OTP sent")) {
    alert("Failed to send OTP: " + otpResponse);
    return;
  }

  // 2. Show modal
  otpModal.classList.remove("hidden");
});

document.querySelector("#cancelBtn").onclick = () => {
  otpModal.classList.add("hidden");
  otpInput.value = '';
  otpError.classList.add("hidden");
};

document.querySelector("#confirmOtpBtn").onclick = async () => {
  const otp = otpInput.value.trim();
  if (!otp) {
    otpError.textContent = "Please enter the OTP.";
    otpError.classList.remove("hidden");
    return;
  }

  // Append OTP + form data
  const formData = new FormData(profileForm);
  formData.append("otp", otp);

  // 3. Save changes
  const res = await fetch("save_changes.php", {
    method: "POST",
    body: formData
  });

  const text = await res.text();
  if (text.includes("successfully")) {
    alert("✅ " + text);
    window.location.reload();
  } else {
    otpError.textContent = text;
    otpError.classList.remove("hidden");
  }
};
</script>
