<?php
session_start();
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $token = $_POST['token'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password === $confirm_password) {
        // ตรวจสอบว่าโทเค็นรีเซ็ตรหัสผ่านมีอยู่ในฐานข้อมูลหรือไม่
        $stmt = $conn->prepare("SELECT user_id FROM password_resets WHERE token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $stmt->bind_result($user_id);
        $stmt->fetch();
        $stmt->close();

        if ($user_id) {
            // อัพเดตรหัสผ่านใหม่ในฐานข้อมูล
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashed_password, $user_id);
            $stmt->execute();
            $stmt->close();

            // ลบโทเค็นรีเซ็ตรหัสผ่านหลังจากรีเซ็ตรหัสผ่านเสร็จ
            $stmt = $conn->prepare("DELETE FROM password_resets WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();

            $_SESSION['message'] = "Your password has been reset successfully.";
            header("Location: /project/login.php");
            exit;
        } else {
            $_SESSION['message'] = "Invalid token.";
            header("Location: reset_password.php?token=$token");
            exit;
        }
    } else {
        $_SESSION['message'] = "Passwords do not match.";
        header("Location: reset_password.php?token=$token");
        exit;
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link rel="stylesheet" href="/project/css/login.css">
</head>
<body>
    <div class="wrapper">
        <h1>Reset Password</h1>
        <form action="reset_password.php" method="POST">
            <input type="hidden" name="token" value="<?php echo $_GET['token']; ?>">
            <div class="input-box">
                <input type="password" name="new_password" placeholder="Enter new password" required>
                <i class='bx bxs-lock-alt'></i>
            </div>
            <div class="input-box">
                <input type="password" name="confirm_password" placeholder="Confirm new password" required>
                <i class='bx bxs-lock-alt'></i>
            </div>
            <button type="submit" class="btn">Reset Password</button>
        </form>
    </div>
</body>
</html>
