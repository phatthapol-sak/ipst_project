<?php
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
	$email = $_POST['email'];
	$username = $_POST['username'];
	$password = password_hash($_POST['password'], PASSWORD_BCRYPT);

	$sql = "INSERT INTO users (email, username, password) VALUES ('$email', '$username', '$password')";

	if ($conn->query($sql) === TRUE) {
		echo "Registration successful. <a href='login.php'>Login</a>";
	} else {
		echo "Error: " . $sql . "<br>" . $conn->error;
	}

	$conn->close();
}
?>
