<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $token = bin2hex(random_bytes(50)); // สร้าง token สำหรับยืนยัน

    // ตรวจสอบการเชื่อมต่อ
    if ($conn->connect_error) {
        die('Connect Error (' . $conn->connect_errno . ') ' . $conn->connect_error);
    }

    // แทรกข้อมูลลงในฐานข้อมูล
    $stmt = $conn->prepare("INSERT INTO users (email, token) VALUES (?, ?)");
    $stmt->bind_param('ss', $email, $token);
    if ($stmt->execute()) {
        // ตั้งค่า SMTP ของ Gmail
        ini_set("SMTP", "smtp.gmail.com");
        ini_set("smtp_port", "587");
        ini_set("sendmail_from", "your_email@gmail.com");

        // ข้อมูลอีเมล
        $subject = "Email Verification";
        $message = "Click the link to verify your email: http://localhost/project/verify.php?token=$token";
        $headers = "From: your_email@gmail.com";

        // ส่งอีเมล
        if (mail($email, $subject, $message, $headers)) {
            echo "Verification email sent.";
        } else {
            echo "Failed to send verification email.";
        }
    } else {
        echo "Failed to register.";
    }
    $stmt->close();
    $conn->close();
}
?>
