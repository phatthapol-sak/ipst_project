<?php
session_start();
include 'config.php';

$isLoggedIn = isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;
$username = $isLoggedIn ? $_SESSION['username'] : '';

$message = '';
$isSuccess = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $message = trim($_POST['message']);

    if (!empty($message)) {
        // Prepare data for evaluation
        $messages = [$message];
        $data = json_encode(['messages' => $messages], JSON_UNESCAPED_UNICODE);

        // Send request to Flask API
        $ch = curl_init('http://localhost:5000/evaluate');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $response = curl_exec($ch);
        curl_close($ch);

        $results = json_decode($response, true);

        // Save the result in the database
        if ($results) {
            $maxIndex = $results[0];

            if ($isLoggedIn) {
                // Get user_id from users table
                $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
                if ($stmt === false) {
                    die('Prepare failed: ' . htmlspecialchars($conn->error));
                }
                $stmt->bind_param("s", $username);
                if ($stmt->execute() === false) {
                    die('Execute failed: ' . htmlspecialchars($stmt->error));
                }
                $stmt->bind_result($user_id);
                $stmt->fetch();
                $stmt->close();

                // Insert new evaluation_1 entry and set score to - if evaluate_2 is not done
                $stmt = $conn->prepare("INSERT INTO messages (user_id, message, result, score, evaluation_type, evaluate_1_done, evaluate_2_done) VALUES (?, ?, ?, ?, 'evaluate_1', 1, 0)");
                if ($stmt === false) {
                    die('Prepare failed: ' . htmlspecialchars($conn->error));
                }
                $score = -1;
                $stmt->bind_param("isii", $user_id, $message, $maxIndex, $score);

                if ($stmt->execute()) {
                    $isSuccess = true;
                    header("Location: summary.php"); // Redirect to summary.php
                    exit;
                } else {
                    echo "Error: " . $stmt->error;
                }
                $stmt->close();
            } else {
                // Save in session if not logged in
                if (!isset($_SESSION['evaluation_session_id'])) {
                    $_SESSION['evaluation_session_id'] = uniqid('eval_', true);
                }

                $_SESSION['evaluation_1'] = [
                    'session_id' => $_SESSION['evaluation_session_id'],
                    'message' => $message,
                    'result' => $maxIndex,
                    'score' => -1,
                    'created_at' => date("Y-m-d H:i:s")
                ];
                $isSuccess = true;
                header("Location: summary.php"); // Redirect to summary.php
                exit;
            }
        } else {
            echo "Error: Failed to evaluate the message.";
        }
    } else {
        echo "Message cannot be empty";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evaluate</title>
    <link rel="stylesheet" href="css/evaluate1_1.css">
</head>
<body>
<header>
        <nav>
            <div class="container">
                <div class="nav-con">
                    <div class="logo">
                        <a href="main.php">DAISE_KPN</a>
                    </div>
                    <ul class="menu">
                        <li class="dropdown">
                            <div class="dropbtn">ประเมิน</div>
                            <div class="dropdown-content">
                                <a href="evaluate_1.php">ประเมินข้อความ</a>
                                <a href="evaluate_2.php">ประเมิน DASS</a>
                            </div>
                        </li>
                        <li><a href="summary.php">ประวัติ</a></li>
                        <li>
                            <div class="auth-box">
                                <a href="login.php" class="nav-login-btn">LOGIN</a>
                                <a href="register.html" class="nav-signup-btn">SIGNUP</a>
                                <div class="nav-user" style="display: none;">
                                    <span><?php echo $username; ?></span>
                                    <div class="dropdown-content">
                                        <a href="logout.php">Logout</a>
                                    </div>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>
<main>
    <h1>ให้ขึ้นต้นประโยคด้วย 1 ใน 5 นี้</h1>
    <form id="user-form" action="evaluate_1.php" method="POST">
        <div class="input-box">
            <textarea id="message-input" name="message" placeholder="1.ฉันอยาก.....
2.อนาคตของฉัน...
3.คนรอบตัวของฉัน...
4.ชีวิตฉัน...
5.ฉันเป็น..." required></textarea>
        </div>
        <button type="submit" class="start-button">เริ่ม</button>
    </form>
</main>

<?php if ($isSuccess): ?>
<div id="successModal" class="modal">
    <div class="modal-content">
        <span class="close" id="closeModal">&times;</span>
        <p>Message saved successfully</p>
        <button class="modal-button" onclick="location.href='summary.php'">Go to Summary</button>
    </div>
</div>
<?php endif; ?>

<script>
    const isLoggedIn = <?php echo json_encode($isLoggedIn); ?>;
    const username = <?php echo json_encode($username); ?>;
    const isSuccess = <?php echo json_encode($isSuccess); ?>;

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

    if (isSuccess) {
        const modal = document.getElementById('successModal');
        const closeModal = document.getElementById('closeModal');
        modal.style.display = 'flex';

        closeModal.onclick = function() {
            modal.style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    }

    document.getElementById('message-input').addEventListener('input', function() {
        this.style.color = '#000000';
    });
</script>
</body>
</html>
