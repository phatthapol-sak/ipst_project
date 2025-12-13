<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // ใช้ Composer ในการโหลด PHPMailer

include 'config.php';

$response = array('success' => false, 'message' => '');

define("SERVER_HOST", "http://bsd-pol.trueddns.com:14440");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $conn -> real_escape_string(trim($_POST['email']));
    $token = bin2hex(random_bytes(50)); // สร้าง token สำหรับยืนยัน

    // ตรวจสอบว่ามีอีเมลนี้อยู่ในระบบหรือไม่
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    if ($stmt === false) {
        $response['message'] = 'Prepare failed: ' . htmlspecialchars($conn->error);
    } else {
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            // ส่งอีเมลยืนยันใหม่หากอีเมลนี้มีอยู่แล้ว
            $stmt->bind_result($id);
            $stmt->fetch();
            $stmt->close();

            // สร้าง token ใหม่สำหรับการยืนยัน
            $token = bin2hex(random_bytes(50));

            // อัพเดต token ในฐานข้อมูล
            $stmt = $conn->prepare("UPDATE users SET token = ? WHERE email = ?");
            if ($stmt === false) {
                $response['message'] = 'Prepare failed: ' . htmlspecialchars($conn->error);
            } else {
                $stmt->bind_param('ss', $token, $email);
                if ($stmt->execute()) {
                    // ใช้ PHPMailer ในการส่งอีเมล
                    $mail = new PHPMailer(true);
                    try {
                        // ตั้งค่าเซิร์ฟเวอร์ SMTP
                        $mail->isSMTP();
                        $mail->Host = 'smtp.gmail.com'; // ใช้ Gmail SMTP
                        $mail->SMTPAuth = true;
                        $mail->Username = 'noreply.daise@gmail.com'; // อีเมลของคุณ
                        $mail->Password = 'idbj wdyi ujej ykdm'; // รหัสผ่านแอปที่สร้าง
                        $mail->SMTPSecure = 'tls';
                        $mail->Port = 587;

                        // ผู้ส่ง
                        $mail->setFrom('noreply.daise@gmail.com', 'daise_kpn');
                        // ผู้รับ
                        $mail->addAddress($email);

                        // เนื้อหาอีเมล
                        $mail->isHTML(true);
                        $mail->Subject = 'Email Verification';
                        $mail->Body    = "Click the link to verify your email: <a href='".SERVER_HOST."/project/verify-email/$token'>Verify Email</a>";
                        $mail->AltBody = "Click the link to verify your email: ".SERVER_HOST."/project/verify-email/$token";

                        $mail->send();
                        $response['success'] = true;
                        $response['message'] = 'Verification email sent. Please check your inbox.';
                    } catch (Exception $e) {
                        $response['message'] = "Failed to send verification email. Mailer Error: {$mail->ErrorInfo}";
                    }
                } else {
                    $response['message'] = "Failed to update token: " . htmlspecialchars($stmt->error);
                }
                $stmt->close();
            }
        } else {
            // แทรกข้อมูลลงในฐานข้อมูล
            $stmt = $conn->prepare("INSERT INTO users (email, token) VALUE (?, ?)");
            if ($stmt === false) {
                $response['message'] = 'Prepare failed: ' . htmlspecialchars($conn->error);
            } else {
                $stmt->bind_param('ss', $email, $token);
                if ($stmt->execute()) {
                    // ใช้ PHPMailer ในการส่งอีเมล
                    $mail = new PHPMailer(true);
                    try {
                        // ตั้งค่าเซิร์ฟเวอร์ SMTP
                        $mail->isSMTP();
                        $mail->Host = 'smtp.gmail.com'; // ใช้ Gmail SMTP
                        $mail->SMTPAuth = true;
                        $mail->Username = 'noreply.daise@gmail.com'; // อีเมลของคุณ
                        $mail->Password = 'idbj wdyi ujej ykdm'; // รหัสผ่านแอปที่สร้าง
                        $mail->SMTPSecure = 'tls';
                        $mail->Port = 587;

                        // ผู้ส่ง
                        $mail->setFrom('noreply.daise@gmail.com', 'daise_kpn');
                        // ผู้รับ
                        $mail->addAddress($email);

                        // เนื้อหาอีเมล
                        $mail->isHTML(true);
                        $mail->Subject = 'Email Verification';
                        $mail->Body    = "Click the link to verify your email: <a href='".SERVER_HOST."/project/verify-email/$token'>Verify Email</a>";
                        $mail->AltBody = "Click the link to verify your email: ".SERVER_HOST."/project/verify-email/$token";

                        $mail->send();
                        $response['success'] = true;
                        $response['message'] = 'Verification email sent. Please check your inbox.';
                    } catch (Exception $e) {
                        $response['message'] = "Failed to send verification email. Mailer Error: {$mail->ErrorInfo}";
                    }
                } else {
                    $response['message'] = "Failed to register: " . htmlspecialchars($stmt->error);
                }
                $stmt->close();
            }
        }
    }
    $conn->close();
}

echo json_encode($response);
?>