<?php
include 'config.php';

if (isset($_GET['token'])) {
	$token = $_GET['token'];

	// ตรวจสอบ token
	$stmt = $conn->prepare("SELECT email FROM users WHERE token = ?");
	$stmt->bind_param('s', $token);
	$stmt->execute();
	$result = $stmt->get_result();

	if ($result->num_rows > 0) {
		$row = $result->fetch_assoc();
		$email = $row['email'];
	} else {
		die('Invalid token.');
	}
	$stmt->close();
} else {
	die('No token provided.');
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Complete Registration</title>
	<link rel="stylesheet" href="/project/css/register.css">
</head>
<body>
	<div class="wrapper">
		<h1>Complete Registration</h1>
		<form id="complete-registration-form">
			<input type="hidden" name="email" id="email" value="<?php echo htmlspecialchars($email); ?>">
			<div class="input-box">
				<input type="text" name="username" id="username" placeholder="Username" required>
				<i class='bx bx-user'></i>
			</div>
			<div class="input-box">
				<input type="password" name="password" id="password" placeholder="Password" required>
				<i class='bx bx-lock'></i>
			</div>
			<div class="input-box">
				<select name="level" id="level" required>
					<option value="" disabled selected hidden>ระดับชั้น</option>
					<option value="1">ม.1</option>
					<option value="2">ม.2</option>
					<option value="3">ม.3</option>
					<option value="4">ม.4</option>
					<option value="5">ม.5</option>
					<option value="6">ม.6</option>
					<option value="other">อื่นๆ</option>
				</select>
			</div>
			<button type="submit" class="btn">Complete Registration</button>
		</form>
	</div>

	<div class="message-box" id="message-box">
		<p id="message-content"></p>
		<button id="close-button">Close</button>
	</div>

	<script>
		document.getElementById('complete-registration-form').addEventListener('submit', function(e) {
			e.preventDefault();
			var email = document.getElementById('email').value;
			var username = document.getElementById('username').value;
			var password = document.getElementById('password').value;
			var level = document.getElementById('level').value;
			var xhr = new XMLHttpRequest();
			xhr.open('POST', '/project/complete_registration', true);
			xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
			xhr.onload = function() {
				var messageBox = document.getElementById('message-box');
				var messageContent = document.getElementById('message-content');
				if (xhr.status === 200) {
					var response = JSON.parse(xhr.responseText);
					messageContent.innerText = response.message;
					messageBox.style.display = 'block';
					if (response.success) {
						setTimeout(function() {
							window.location.href = response.redirect; // Redirect to login page
						}, 3000); // Wait for 3 seconds before redirecting
					} else {
						document.getElementById('close-button').onclick = function() {
							messageBox.style.display = 'none'; // Close the box if not successful
						};
					}
				} else {
					messageContent.innerText = 'An error occurred. Please try again.';
					messageBox.style.display = 'block';
					document.getElementById('close-button').onclick = function() {
						messageBox.style.display = 'none'; // Close the box if an error occurs
					};
				}
			};
			xhr.send('email=' + encodeURIComponent(email) + '&username=' + encodeURIComponent(username) + '&password=' + encodeURIComponent(password) + '&level=' + encodeURIComponent(level));
		});
	</script>
</body>
</html>
