<?php
session_start();
require 'vendor/autoload.php'; // สำหรับ PHPMailer
include 'config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = '';
$isSuccess = false;

define("SERVER_HOST", "http://bsd-pol.trueddns.com:14440");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];

    // ตรวจสอบว่าอีเมลมีอยู่ในฐานข้อมูลหรือไม่
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($user_id);
    $stmt->fetch();
    $stmt->close();

    if ($user_id) {
        // สร้างโทเค็นรีเซ็ตรหัสผ่าน
        $token = bin2hex(random_bytes(50));
        $stmt = $conn->prepare("INSERT INTO password_resets (user_id, token) VALUES (?, ?)");
        $stmt->bind_param("is", $user_id, $token);
        $stmt->execute();
        $stmt->close();

        // ส่งอีเมลรีเซ็ตรหัสผ่าน
        $reset_link = ".SERVER_HOST./project/reset_password.php?token=$token";

        $mail = new PHPMailer(true);

        try {
            // เซ็ตติ้ง SMTP
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com'; // SMTP server
            $mail->SMTPAuth = true;
            $mail->Username = getenv('SMTP_USER'); // ใส่อีเมลของคุณ
            $mail->Password = getenv('SMTP_PASS'); // ใส่รหัสผ่านของอีเมลคุณ (หรือ App Password ถ้าใช้ 2-Step Verification)
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            // ตั้งค่าอีเมล
            $mail->setFrom('noreply.daise@gmail.com', 'daise_kpn');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Request';
            $mail->Body = "Click the link below to reset your password:<br><br><a href='$reset_link'>$reset_link</a>";

            $mail->send();
            $message = "Password reset link has been sent to your email.";
            $isSuccess = true;
        } catch (Exception $e) {
            $message = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    } else {
        $message = "Email not found. Please register first.";
        $isSuccess = false;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="css/login.css">
    <style>
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgb(0,0,0);
            background-color: rgba(0,0,0,0.4);
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: #fefefe;
            margin: auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 300px;
            text-align: center;
            border-radius: 10px;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <h1>Forgot Password</h1>
        <form action="forget_password.php" method="POST">
            <div class="input-box">
                <input type="email" name="email" placeholder="Enter your email" required>
                <i class='bx bx-envelope'></i>
            </div>
            <button type="submit" class="btn">Submit</button>
            <div class="login-link">
                <p>Remembered your password? <a href="login.php">Login</a></p>
            </div>
        </form>
    </div>

    <!-- Modal -->
    <div id="messageModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <p id="modalMessage"></p>
            <?php if ($message == "Email not found. Please register first."): ?>
                <p><a href="register.html">Register</a></p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Display the modal
        function showModal(message) {
            document.getElementById("modalMessage").innerText = message;
            document.getElementById("messageModal").style.display = "flex";
        }

        // Close the modal and redirect to main.php
        function closeModal() {
            document.getElementById("messageModal").style.display = "none";
            window.location.href = 'main.php';
        }

        // Show modal if there is a message
        <?php if (!empty($message)): ?>
            showModal("<?php echo $message; ?>");
        <?php endif; ?>
    </script>
</body>
</html>
