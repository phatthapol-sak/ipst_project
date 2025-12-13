<?php
include 'config.php';

$response = array('success' => false, 'message' => '');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT); // แฮช password
    $level = intval($_POST['level']); // ระดับชั้น

    // ตรวจสอบว่าชื่อผู้ใช้มีอยู่แล้วหรือไม่
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    if ($stmt === false) {
        $response['message'] = 'Prepare failed: ' . htmlspecialchars($conn->error);
    } else {
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $response['message'] = 'Username already exists. Please choose another username.';
        } else {
            // อัปเดตข้อมูลผู้ใช้
            $stmt2 = $conn->prepare("UPDATE users SET username = ?, password = ?, token = NULL, level = ? WHERE email = ?");
            if ($stmt2 === false) {
                $response['message'] = 'Prepare failed: ' . htmlspecialchars($conn->error);
            } else {
                $stmt2->bind_param('ssss', $username, $password, $level, $email);
                if ($stmt2->execute()) {
                    if ($stmt2->affected_rows > 0) {
                        $response['success'] = true;
                        $response['message'] = 'Registration complete.';
                        $response['redirect'] = '/project/login';
                    } else {
                        $response['message'] = 'Invalid token or token expired.';
                    }
                } else {
                    $response['message'] = 'Failed to complete registration: ' . htmlspecialchars($stmt2->error);
                }
                $stmt2->close();
            }
        }
        $stmt->close();
    }
    $conn->close();
}

echo json_encode($response);
?>
