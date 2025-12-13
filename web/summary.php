<?php
session_start();
include 'config.php';

$isLoggedIn = isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;
$username = $isLoggedIn ? $_SESSION['username'] : '';
if (!$isLoggedIn) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
}

$latest_result = null;
$previous_result = null;
$weekly_avg = null;
$biweekly_avg = null;
$monthly_avg = null;

function getResultText($result) {
    if ($result == 0) {
        return 'ไม่ใช่โรคซึมเศร้าแน่ๆ';
    } elseif ($result == 1) {
        return 'ไม่น่าจะเป็นโรคซึมเศร้า';
    } elseif ($result == 2) {
        return 'ไม่แน่ใจ';
    } elseif ($result == 3) {
        return 'อาจเป็นโรคซึมเศร้า';
    } elseif ($result == 4) {
        return 'มีความเป็นไปได้ว่าเป็นโรคซึมเศร้า';
    } else {
        return 'ไม่มีข้อมูล';
    }
}

function getResultImage($result) {
    if ($result == 0) {
        return 'css/image/level_0.png';
    } elseif ($result == 1) {
        return 'css/image/level_1.png';
    } elseif ($result== 2) {
        return 'css/image/level_2.png';
    } elseif ($result == 3) {
        return 'css/image/level_3.png';
    } elseif ($result == 4) {
        return 'css/image/level_4.png';
    } else {
        return 'css\image\no_data.png';
    }
}

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

    // Fetch messages and results
    $stmt = $conn->prepare("SELECT message, result, score, created_at FROM messages WHERE user_id = ? ORDER BY id DESC");
    if ($stmt === false) {
        die('Prepare failed: ' . htmlspecialchars($conn->error));
    }
    $stmt->bind_param("i", $user_id);
    if ($stmt->execute() === false) {
        die('Execute failed: ' . htmlspecialchars($stmt->error));
    }
    $stmt->bind_result($message, $result, $score, $created_at);

    $results = [];
    while ($stmt->fetch()) {
        $results[] = [
            'message' => $message,
            'result' => $result,
            'score' => $score,
            'created_at' => $created_at
        ];
    }
    $stmt->close();

    // Get latest result
    if (!empty($results)) {
        $latest_result = $results[0];
        $latest_result_text = getResultText($results[0]['result']);
        $latest_result_img = getResultImage($results[0]['result']);
        // Get previous result
        if (isset($results[1])) {
            $previous_result = $results[1];
            $previous_result_text = getResultText($results[1]['result']);
            $previous_result_img = getResultImage($results[1]['result']);
        }
    }

    // Calculate averages
    $now = new DateTime();
    $one_week_ago = $now->modify('-1 week')->format('Y-m-d H:i:s');
    $two_weeks_ago = $now->modify('-2 weeks')->format('Y-m-d H:i:s');
    $one_month_ago = $now->modify('-1 month')->format('Y-m-d H:i:s');

    $weekly_results = [];
    $biweekly_results = [];
    $monthly_results = [];

    foreach ($results as $res) {
        if ($res['created_at'] >= $one_week_ago) {
            $weekly_results[] = $res;
        }
        if ($res['created_at'] >= $two_weeks_ago) {
            $biweekly_results[] = $res;
        }
        if ($res['created_at'] >= $one_month_ago) {
            $monthly_results[] = $res;
        }
    }

    if (!empty($weekly_results)) {
        $weekly_avg_result = round(array_sum(array_column($weekly_results, 'result')) / count($weekly_results), 1);
        $weekly_avg_score = round(array_sum(array_column($weekly_results, 'score')) / count($weekly_results), 1);
        $weekly_avg_text = getResultText(round($weekly_avg_result));
        $weekly_avg_img = getResultImage(round($weekly_avg_result));
    }
    if (!empty($biweekly_results)) {
        $biweekly_avg_result = round(array_sum(array_column($biweekly_results, 'result')) / count($biweekly_results), 1);
        $biweekly_avg_score = round(array_sum(array_column($biweekly_results, 'score')) / count($biweekly_results), 1);
        $biweekly_avg_text = getResultText(round($biweekly_avg_result));
        $biweekly_avg_img = getResultImage(round($biweekly_avg_result));
    }
    if (!empty($monthly_results)) {
        $monthly_avg_result = round(array_sum(array_column($monthly_results, 'result')) / count($monthly_results), 1);
        $monthly_avg_score = round(array_sum(array_column($monthly_results, 'score')) / count($monthly_results), 1);
        $monthly_avg_text = getResultText(round($monthly_avg_result));
        $monthly_avg_img = getResultImage(round($monthly_avg_result));
    }
} else {
    if (isset($_SESSION['evaluation_1']) && isset($_SESSION['evaluation_2'])) {
        $latest_result = array_merge($_SESSION['evaluation_1'], $_SESSION['evaluation_2']);
        $latest_result_text = getResultText($latest_result['result']);
        $latest_result_img = getResultImage($latest_result['result']);
    } elseif (isset($_SESSION['evaluation_1'])) {
        $latest_result = $_SESSION['evaluation_1'];
        $latest_result_text = getResultText($latest_result['result']);
        $latest_result_img = getResultImage($latest_result['result']);
    } elseif (isset($_SESSION['evaluation_2'])) {
        $latest_result = $_SESSION['evaluation_2'];
        $latest_result_text = getResultText($latest_result['result']);
        $latest_result_img = getResultImage($latest_result['result']);
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Summary</title>
    <link rel="stylesheet" href="css/summary.css">
    <style>
        .result-item .info-button,
        .past-result-item-1day .info-button,
        .past-result-item-1week .info-button,
        .past-result-item-2week .info-button,
        .past-result-item-1month .info-button {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: #6200ea;
            color: #fff;
            border: none;
            border-radius: 4px;
            padding: 5px;
            cursor: pointer;
        }
        .result-item,
        .past-result-item-1day,
        .past-result-item-1week,
        .past-result-item-2week,
        .past-result-item-1month {
            position: relative;
        }
        .result-info, .past-result-info {
            display: none;
            margin-top: 10px;
        }

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
            max-width: 500px;
            text-align: center;
            border-radius: 10px;
            position: relative;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            position: absolute;
            right: 20px;
            top: 10px;
            cursor: pointer;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

        .overlay {
            display: none;
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255,255,255,0.95);
            justify-content: center;
            align-items: center;
            z-index: 2;
        }

        .overlay-content {
            background-color: #fefefe;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 500px;
            text-align: center;
            border-radius: 10px;
            position: relative;
        }

        .close-overlay {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            position: absolute;
            right: 20px;
            top: 10px;
            cursor: pointer;
        }

        .close-overlay:hover,
        .close-overlay:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
    </style>
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
    <div class="summary-container">
        <div class="latest-result">
            <h1>ความเสี่ยงภาวะซึมเศร้า</h1>
            <h2>ผลการประเมินครั้งนี้</h2>
            <div class="result-item">
                <button class="info-button" onclick="toggleModal('latest')">i</button>
                <div class="result-content">
                    <div class="result-img" style="float: left; margin-right: 20px;">
                        <img src="<?php echo $latest_result_img ?? 'css/image/no_data.png'?>" alt="Result Image">
                    </div>
                    <div class="result-text" style="overflow: hidden;">
                        <p><?php echo $latest_result_text ?? '<a href="evaluate_1.php">กรุณาไปทำแบบทดสอบ</a>'; ?></p>
                    </div>
                </div>
                <div class="result-info" id="info-latest">
                    <p>Message: <?php echo $latest_result['message'] ?? '-'; ?></p>
                    <p>คะแนนประเมินจากข้อความ: <?php echo $latest_result['result'] ?? '-'; ?></p>
                    <p>Score: <?php echo $latest_result['score'] ?? '<a href="evaluate_2.php"> ทำแบบประเมิน php-9 </a>'; ?></p>
                </div>
            </div>
        </div>
        <div class="past-results">
            <?php if ($isLoggedIn): ?>
                <div class="past-result-item-1day">
                    <h2>ผลการประเมินครั้งที่ผ่านมา</h2>
                    <button class="info-button" onclick="toggleOverlay('previous')">i</button>
                    <div class="result-img">
                        <img src="<?php echo $previous_result_img ?? 'css/image/no_data.png'; ?>" alt="Result Image">
                    </div>
                    <p><?php echo $previous_result_text ?? 'ไม่มีข้อมูล'; ?></p>
                    <div class="result-info overlay" id="info-previous">
                        <div class="overlay-content">
                            <span class="close-overlay" onclick="closeOverlay('previous')">&times;</span>
                            <p>Message: <?php echo $previous_result['message'] ?? '-'; ?></p>
                            <p>คะแนนข้อความ: <?php echo $previous_result['result'] ?? '-'; ?></p>
                            <p>คะแนนDASS: <?php echo $previous_result['score'] ?? '-'; ?></p>
                        </div>
                    </div>
                </div>
                <div class="past-result-item-1week">
                    <h2>ผลการประเมินในสัปดาห์นี้</h2>
                    <button class="info-button" onclick="toggleOverlay('weekly')">i</button>
                    <div class="result-img">
                        <img src="<?php echo $weekly_avg_img ?? 'images/no_data.png'; ?>" alt="Result Image">
                    </div>
                    <p><?php echo $weekly_avg_text ?? 'ไม่มีข้อมูล'; ?></p>
                    <div class="result-info overlay" id="info-weekly">
                        <div class="overlay-content">
                            <span class="close-overlay" onclick="closeOverlay('weekly')">&times;</span>
                            <p>คะแนนข้อความเฉลี่ย: <?php echo $weekly_avg_result ?? '-'; ?></p>
                            <p>คะแนนDASSเฉลี่ย: <?php echo $weekly_avg_score ?? '-'; ?></p>
                        </div>
                    </div>
                </div>
                <div class="past-result-item-2week">
                    <h2>ผลการประเมินในช่วง 2 สัปดาห์</h2>
                    <button class="info-button" onclick="toggleOverlay('biweekly')">i</button>
                    <div class="result-img">
                        <img src="<?php echo $biweekly_avg_img ?? 'images/no_data.png'; ?>" alt="Result Image">
                    </div>
                    <p><?php echo $biweekly_avg_text ?? 'ไม่มีข้อมูล'; ?></p>
                    <div class="result-info overlay" id="info-biweekly">
                        <div class="overlay-content">
                            <span class="close-overlay" onclick="closeOverlay('biweekly')">&times;</span>
                            <p>คะแนนข้อความเฉลี่ย: <?php echo $biweekly_avg_result ?? '-'; ?></p>
                            <p>คะแนนDASSเฉลี่ย: <?php echo $biweekly_avg_score ?? '-'; ?></p>
                        </div>
                    </div>
                </div>
                <div class="past-result-item-1month">
                    <h2>ผลการประเมินในช่วง 1 เดือน</h2>
                    <button class="info-button" onclick="toggleOverlay('monthly')">i</button>
                    <div class="result-img">
                        <img src="<?php echo $monthly_avg_img ?? 'images/no_data.png'; ?>" alt="Result Image">
                    </div>
                    <p><?php echo $monthly_avg_text ?? 'ไม่มีข้อมูล'; ?></p>
                    <div class="result-info overlay" id="info-monthly">
                        <div class="overlay-content">
                            <span class="close-overlay" onclick="closeOverlay('monthly')">&times;</span>
                            <p>คะแนนข้อความเฉลี่ย: <?php echo $monthly_avg_result ?? '-'; ?></p>
                            <p>คะแนนDASSเฉลี่ย: <?php echo $monthly_avg_score ?? '-'; ?></p>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="past-result-item-1day">
                    <h2>ผลการประเมินครั้งที่ผ่านมา</h2>
                    <p><a href="login.php">กรุณา login</a></p>
                </div>
                <div class="past-result-item-1week">
                    <h2>ผลการประเมินในสัปดาห์นี้</h2>
                    <p><a href="login.php">กรุณา login</a></p>
                </div>
                <div class="past-result-item-2week">
                    <h2>ผลการประเมินในช่วง 2 สัปดาห์</h2>
                    <p><a href="login.php">กรุณา login</a></p>
                </div>
                <div class="past-result-item-1month">
                    <h2>ผลการประเมินในช่วง 1 เดือน</h2>
                    <p><a href="login.php">กรุณา login</a></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <a href="average_results.php" class="average-button">ไปหน้าผลภาพรวม</a>
</main>


<div id="infoModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <div id="modal-info-content"></div>
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
                dropdownContent.style.display = dropdownContent.style.display === 'block';
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

    function closeAllOverlays() {
        const overlays = document.querySelectorAll('.overlay');
        overlays.forEach(overlay => {
            overlay.style.display = 'none';
        });
    }

    function toggleModal(section) {
        closeAllOverlays();  // Close all overlays when opening modal
        const infoDiv = document.getElementById('info-' + section);
        const modal = document.getElementById('infoModal');
        const modalContent = document.getElementById('modal-info-content');
        modalContent.innerHTML = infoDiv.innerHTML;
        modal.style.display = 'flex';
    }

    function closeModal() {
        const modal = document.getElementById('infoModal');
        modal.style.display = 'none';
    }

    function toggleOverlay(section) {
        closeModal();  // Close modal when opening overlay
        closeAllOverlays();  // Close other overlays
        const overlay = document.getElementById('info-' + section);
        overlay.style.display = 'flex';
    }

    function closeOverlay(section) {
        const overlay = document.getElementById('info-' + section);
        overlay.style.display = 'none';
    }

    document.addEventListener('DOMContentLoaded', function() {
        const userElement = document.querySelector('.nav-user');
        if (userElement) {
            const dropdownContent = userElement.querySelector('.dropdown-content');
            userElement.addEventListener('click', function(event) {
                dropdownContent.style.display = dropdownContent.style.display === 'block' ? 'none' : 'block';
                event.stopPropagation();
            });

            document.addEventListener('click', function(event) {
                if (!userElement.contains(event.target)) {
                    dropdownContent.style.display = 'none';
                }
            });
        }
    });
</script>
</body>
</html>
