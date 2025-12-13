<?php
session_start();
include 'config.php';

$isLoggedIn = false;
$error = '';
$redirect_url = 'main.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	$username = $_POST['username'];
	$password = $_POST['password'];

	// Query to check user credentials
	$stmt = $conn->prepare("SELECT id, password FROM users WHERE username = ?");
	$stmt->bind_param("s", $username);
	$stmt->execute();
	$stmt->bind_result($user_id, $hashed_password);
	$stmt->fetch();
	$stmt->close();

	if (password_verify($password, $hashed_password)) {
		$_SESSION['loggedin'] = true;
		$_SESSION['username'] = $username;
		
		// Check if there's a redirect URL
		$redirect_url = isset($_SESSION['redirect_url']) ? $_SESSION['redirect_url'] : 'main.php';
		unset($_SESSION['redirect_url']);
		
		$isLoggedIn = true;

		// Move session data to the database if exists
		if (isset($_SESSION['evaluation_1'])) {
			$evaluation_1 = $_SESSION['evaluation_1'];
			$stmt = $conn->prepare("INSERT INTO messages (user_id, message, result, evaluation_type, evaluate_1_done, created_at, session_id) VALUES (?, ?, ?, 'evaluate_1', 1, ?, ?)");
			if ($stmt === false) {
				die('Prepare failed: ' . htmlspecialchars($conn->error));
			}
			$stmt->bind_param("isiss", $user_id, $evaluation_1['message'], $evaluation_1['result'], $evaluation_1['created_at'], $evaluation_1['session_id']);
			if ($stmt->execute() === false) {
				die('Execute failed: ' . htmlspecialchars($stmt->error));
			}
			$stmt->close();
			unset($_SESSION['evaluation_1']); // Clear session data
		}
		if (isset($_SESSION['evaluation_2'])) {
			$evaluation_2 = $_SESSION['evaluation_2'];
			// Get the message_id from the previously inserted evaluation_1
			$stmt = $conn->prepare("SELECT id FROM messages WHERE user_id = ? AND session_id = ? AND evaluate_2_done = 0 ORDER BY created_at DESC LIMIT 1");
			if ($stmt === false) {
				die('Prepare failed: ' . htmlspecialchars($conn->error));
			}
			$stmt->bind_param("is", $user_id, $evaluation_2['session_id']);
			$stmt->execute();
			$stmt->bind_result($message_id);
			$stmt->fetch();
			$stmt->close();

			// Update the existing evaluation_1 entry with evaluation_2 data
			$stmt = $conn->prepare("UPDATE messages SET score = ?, category = ?, evaluate_2_done = 1 WHERE id = ?");
			if ($stmt === false) {
				die('Prepare failed: ' . htmlspecialchars($conn->error));
			}
			$stmt->bind_param("iii", $evaluation_2['score'], $evaluation_2['category'], $message_id);
			if ($stmt->execute() === false) {
				die('Execute failed: ' . htmlspecialchars($stmt->error));
			}
			$stmt->close();
			unset($_SESSION['evaluation_2']); // Clear session data
		}

		// Redirect to the original page or a default page
		header("Location: $redirect_url");
		exit;
	} else {
		$error = "Invalid username or password";
	}
}

// Initialize variables to avoid warnings
$isSuccess = $isSuccess ?? false;
$message = $message ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Login</title>
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
		<h1>Login</h1>
		<form action="login.php" method="POST">
			<div class="input-box">
				<input type="text" name="username" placeholder="username" required>
				<i class='bx bx-user'></i>
			</div>
			<div class="input-box">
				<input type="password" name="password" placeholder="password" required>
				<i class='bx bxs-lock-alt'></i>
			</div>
			<p id="error-message" class="error-message"><?php echo $error; ?></p>
			<div class="remember-forgot">
				<label><input type="checkbox">Remember me</label>
				<a href="forget_password.php">Forgot password?</a>
			</div>
			<button type="submit" class="btn">Login</button>
			<div class="register-link">
				<p>Don't have an account? <a href="register.html">Register</a></p>
			</div>
		</form>
	</div>

	<?php if ($isLoggedIn): ?>
	<div id="successModal" class="modal">
		<div class="modal-content">
			<span class="close" onclick="closeModal()">&times;</span>
			<p>Login สำเร็จ! ยินดีต้อนรับ <?php echo htmlspecialchars($username); ?></p>
			<button class="btn" onclick="redirectUser()">ไปต่อ</button>
		</div>
	</div>
	<?php endif; ?>

	<script>
		// Redirect user to the previous page
		function redirectUser() {
			window.location.href = 'main.php';
		}

		// Display the success modal if login is successful
		if (<?php echo json_encode($isLoggedIn); ?>) {
			const modal = document.getElementById('successModal');
			modal.style.display = 'flex';
		}

		// Close the modal and redirect if success
		function closeModal() {
			const modal = document.getElementById('successModal');
			modal.style.display = 'none';
			redirectUser();
		}
	</script>
</body>
</html>
