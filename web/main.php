<?php
session_start();
$isLoggedIn = isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;
$username = $isLoggedIn ? $_SESSION['username'] : '';
if (!$isLoggedIn) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>index</title>
    <link rel="stylesheet" href="css/main.css">
</head>
<body>
    <header>
        <nav>
            <div class="container">
                <div class="nav-con">
                    <div class="logo">
                        <a href="main">DAISE_KPN</a>
                    </div>
                    <ul class="menu">
                        <li class="dropdown">
                            <div class="dropbtn">ประเมิน</div>
                            <div class="dropdown-content">
                                <a href="evaluate_1">ประเมินข้อความ</a>
                                <a href="evaluate_2">ประเมิน DASS</a>
                            </div>
                        </li>
                        <li><a href="summary">ประวัติ</a></li>
                        <li>
                            <div class="auth-box">
                                <a href="login" class="nav-login-btn">LOGIN</a>
                                <a href="register.html" class="nav-signup-btn">SIGNUP</a>
                                <div class="nav-user" style="display: none;">
                                    <span><?php echo $username; ?></span>
                                    <div class="dropdown-content">
                                        <a href="logout">Logout</a>
                                    </div>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>
    <section class="main"> 
        <div class="header">
            <p class="text">Find Yourself <span class="black-text"> in Every Corner of the World </span></p>  
        </div>
        <div class="content-group">
            <div class="background-image">
                <img src="css/image/Stressed_Student.png" alt="background">
            </div>
            <div class="function">
                <div class="data1">
                    <a href="evaluate_1">แบบประเมินข้อความ</a>
                </div>
                <div class="data2"><a href="evaluate_2">แบบประเมิน DASS</a></div>
            </div>
            <div class="function">
                <div class="data3"><a href="summary">ประวัติการทำ</a></div>
                <div class="data4"><a href="average_results">สถิติภาพรวม</a></div>
            </div>
        </div>
    </section>

    <!-- Modal -->
    <div id="loginModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <p>Please log in to take the test.</p>
            <button class="login-button" onclick="window.location.href='login'">Login</button>
        </div>
    </div>

    <script>
        // Send login status from PHP to JavaScript
        const isLoggedIn = <?php echo json_encode($isLoggedIn); ?>;
        const username = <?php echo json_encode($username); ?>;

        if (isLoggedIn) {
            document.querySelector('.nav-login-btn').style.display = 'none';
            document.querySelector('.nav-signup-btn').style.display = 'none';
            const userElement = document.querySelector('.nav-user');
            userElement.style.display = 'inline-block';
            userElement.querySelector('span').textContent = username;

            // Add event listener for dropdown
            userElement.addEventListener('click', function(event) {
                const dropdownContent = this.querySelector('.dropdown-content');
                dropdownContent.style.display = dropdownContent.style.display === 'block' ? 'none' : 'block';
                event.stopPropagation(); // Prevent the document click event from firing immediately
            });

            // Hide dropdown when clicking outside
            document.addEventListener('click', function(event) {
                const dropdownContent = document.querySelector('.nav-user .dropdown-content');
                if (!userElement.contains(event.target)) {
                    dropdownContent.style.display = 'none';
                }
            });
        } else {
            // Handle "Take a test" link click for non-logged-in users
            document.getElementById('take-test-link').addEventListener('click', function(event) {
                event.preventDefault();
                const modal = document.getElementById('loginModal');
                modal.style.display = 'block';
            });

            // Get the modal
            const modal = document.getElementById('loginModal');

            // Get the <span> element that closes the modal
            const span = document.getElementsByClassName('close')[0];

            // When the user clicks on <span> (x), close the modal
            span.onclick = function() {
                modal.style.display = 'none';
            }

            // When the user clicks anywhere outside of the modal, close it
            window.onclick = function(event) {
                if (event.target == modal) {
                    modal.style.display = 'none';
                }
            }
        }
    </script>
</body>
</html>
