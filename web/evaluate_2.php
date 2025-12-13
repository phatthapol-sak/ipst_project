<?php
session_start();
include 'config.php';

$isLoggedIn = isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;
$username = $isLoggedIn ? $_SESSION['username'] : '';

$isSuccess = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Calculate total score for evaluation_2
    $totalScore = 0;
    for ($i = 1; $i <= 14; $i++) {
        $totalScore += intval($_POST["score_$i"]);
    }

    // Categorize score for evaluation_2
    if ($totalScore >= 0 && $totalScore <= 9) {
        $category = 1;
    } elseif ($totalScore >= 10 && $totalScore <= 13) {
        $category = 2;
    } elseif ($totalScore >= 14 && $totalScore <= 20) {
        $category = 3;
    } elseif ($totalScore >= 21 && $totalScore <= 27) {
        $category = 4;
    } else {
        $category = 5;
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

        // Check if an evaluation_1 record exists for this user without evaluate_2_done
        $stmt = $conn->prepare("SELECT id FROM messages WHERE user_id = ? AND evaluate_2_done = 0 ORDER BY created_at DESC LIMIT 1");
        if ($stmt === false) {
            die('Prepare failed: ' . htmlspecialchars($conn->error));
        }
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            // If an evaluation_1 record exists without evaluate_2_done, update it
            $stmt->bind_result($message_id);
            $stmt->fetch();
            $stmt->close();

            $stmt = $conn->prepare("UPDATE messages SET score = ?, category = ?, evaluate_2_done = 1 WHERE id = ?");
            if ($stmt === false) {
                die('Prepare failed: ' . htmlspecialchars($conn->error));
            }
            $stmt->bind_param("iii", $totalScore, $category, $message_id);
        } else {
            // Insert new evaluation_2 entry
            $stmt->close();
            $stmt = $conn->prepare("INSERT INTO messages (user_id, score, category, evaluation_type, created_at, evaluate_2_done) VALUES (?, ?, ?, 'evaluation_2', NOW(), 1)");
            if ($stmt === false) {
                die('Prepare failed: ' . htmlspecialchars($conn->error));
            }
            $stmt->bind_param("iii", $user_id, $totalScore, $category);
        }

        if ($stmt->execute()) {
            $isSuccess = true;
            $_SESSION['isSuccess'] = true; // เก็บสถานะใน session
            header("Location: summary.php");
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

        $_SESSION['evaluation_2'] = [
            'session_id' => $_SESSION['evaluation_session_id'],
            'score' => $totalScore,
            'category' => $category,
            'created_at' => date("Y-m-d H:i:s")
        ];
        $isSuccess = true;
        $_SESSION['isSuccess'] = true; // เก็บสถานะใน session
        header("Location: summary.php");
        exit;
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
    <link rel="stylesheet" href="css/evaluate0_2.css">
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
    <h1>โปรดตอบคำถามต่อไปนี้:</h1>
    <div>
        <b>เกณฑ์ประเมิน</b>
        <p>0 หมายถึง ไม่เคยเกิดขึ้นเลย /
        1 หมายถึง เกิดขึ้นในบางครั้ง /
        2 หมายถึง เกิดขึ้นค่อนข้างบ่อย /
        3 หมายถึง เกิดบ่อยมากหรือเกือบตลอดเวลา</p>
    </div>
    
    <form id="user-form" action="" method="POST" class="scrollable-form">
        <?php 
        $questions = [
            "คำถาม 1: ฉันรู้สึกว่า ฉันไม่เคยมีความรู้สึกในแง่บวกเลย",
            "คำถาม 2: ฉันมีความรู้สึกไม่อยากจะทำอะไร",
            "คำถาม 3: ฉันรู้สึกว่า ฉันไม่มีจุดมุ่งหมายในชีวิต",
            "คำถาม 4: ฉันรู้สึกโศกเศร้า เสียใจและหดหู่",
            "คำถาม 5: ฉันรู้สึกว่า ฉันไม่สนใจกับสิ่งต่างๆ รอบตัว",
            "คำถาม 6: ฉันรู้สึกว่าฉันเป็นคนไม่มีคุณค่า",
            "คำถาม 7: ฉันมีความรู้สึกว่าชีวิตฉันไม่มีค่า",
            "คำถาม 8: ฉันรู้สึกไม่สนุกในสิ่งที่ฉันทำ",
            "คำถาม 9: ฉันมีความรู้สึกเหมือนโลกมืดมน ไม่มีความหวัง",
            "คำถาม 10: ฉันไม่มีความกระตือรือร้นในสิ่งต่าง",
            "คำถาม 11: ฉันรู้สึกว่า ฉันเป็นคนไร้ค่า",
            "คำถาม 12: ฉันมองไม่เห็นอนาคตของตนเองในวันข้างหน้า",
            "คำถาม 13: ฉันรู้สึกว่าชีวิตไม่มีความหมาย",
            "คำถาม 14: ฉันพบว่าการที่จะเริ่มต้นทำสิ่งใดสิ่งหนึ่งเป็นเรื่องยาก"
        ];

        for ($i = 1; $i <= 14; $i++): 
            $score = isset($_POST["score_$i"]) ? $_POST["score_$i"] : ''; // Get previous value if exists
        ?>
        <div class="input-box">
            <label for="score_<?php echo $i; ?>"><?php echo $questions[$i-1]; ?></label>
            <div class="button-group" id="score_<?php echo $i; ?>">
                <input type="radio" name="score_<?php echo $i; ?>" value="0" id="score_<?php echo $i; ?>_0" <?php if ($score == '0') echo 'checked'; ?> required>
                <label for="score_<?php echo $i; ?>_0">0 <small>(ไม่เคยเกิดเลย)</small></label>
                <input type="radio" name="score_<?php echo $i; ?>" value="1" id="score_<?php echo $i; ?>_1" <?php if ($score == '1') echo 'checked'; ?>>
                <label for="score_<?php echo $i; ?>_1">1 <small>(เกิดบางครั้ง)</small></label>
                <input type="radio" name="score_<?php echo $i; ?>" value="2" id="score_<?php echo $i; ?>_2" <?php if ($score == '2') echo 'checked'; ?>>
                <label for="score_<?php echo $i; ?>_2">2 <small>(เกิดค่อนข้างบ่อย)</small></label>
                <input type="radio" name="score_<?php echo $i; ?>" value="3" id="score_<?php echo $i; ?>_3" <?php if ($score == '3') echo 'checked'; ?>>
                <label for="score_<?php echo $i; ?>_3">3 <small>(เกิดบ่อยมาก)</small></label>
            </div>
        </div>
        <?php endfor; ?>
        <div style="text-align: center;">
            <button type="submit" class="start-button">ยืนยัน</button>
        </div>
    </form>
</main>

<!-- Modal for success -->
<div id="successModal" class="modal">
    <div class="modal-content">
        <span class="close" id="closeModal">&times;</span>
        <p>คะแนนของคุณถูกบันทึกเรียบร้อยแล้ว</p>
        <button class="modal-button" onclick="closeSuccessModal()"><a href="summary.php"></a>ปิด</button>
    </div>
</div>

<!-- Modal for incomplete form -->
<div id="errorModal" class="modal">
    <div class="modal-content">
        <span class="close" id="closeErrorModal">&times;</span>
        <p>กรุณากรอกข้อมูลให้ครบทุกข้อ</p>
        <div id="missing-questions"></div>
        <button class="modal-button" onclick="closeErrorModal()">ปิด</button>
    </div>
</div>

<script>
    const isLoggedIn = <?php echo json_encode($isLoggedIn); ?>;
    const username = <?php echo json_encode($username); ?>;

    if (isLoggedIn) {
        document.querySelector('.nav-login-btn').style.display = 'none';
        document.querySelector('.nav-signup-btn').style.display = 'none';
        const userElement = document.querySelector('.nav-user');
        userElement.style.display = 'inline-block';
        userElement.querySelector('span').textContent = username;

        userElement.addEventListener('click', function(event) {
            const dropdownContent = this.querySelector('.dropdown-content');
            dropdownContent.style.display = dropdownContent.style.display === 'block' ? 'none' : 'block';
            event.stopPropagation();
        });

        document.addEventListener('click', function(event) {
            const dropdownContent = document.querySelector('.nav-user .dropdown-content');
            if (!userElement.contains(event.target)) {
                dropdownContent.style.display = 'none';
            }
        });
    }

    document.getElementById('user-form').addEventListener('submit', function(event) {
        const missingQuestions = [];
        const formData = new FormData(this);
        for (let i = 1; i <= 14; i++) {
            const radioButtons = document.getElementsByName('score_' + i);
            let isChecked = false;
            for (const radioButton of radioButtons) {
                if (radioButton.checked) {
                    isChecked = true;
                    break;
                }
            }
            if (!isChecked) {
                missingQuestions.push(i);
            }
        }

        if (missingQuestions.length > 0) {
            event.preventDefault();
            const errorModal = document.getElementById('errorModal');
            const missingQuestionsDiv = document.getElementById('missing-questions');
            missingQuestionsDiv.innerHTML = 'ข้อที่ยังไม่ได้ตอบ: ' + missingQuestions.join(', ');
            errorModal.style.display = 'flex';

            // Preserve the radio button values
            formData.forEach((value, key) => {
                const radio = document.getElementById(key + '_' + value);
                if (radio) {
                    radio.checked = true;
                }
            });
        }
    });

    window.onload = function() {
        const isSuccess = <?php echo json_encode(isset($_SESSION['isSuccess']) && $_SESSION['isSuccess']); ?>;
        if (isSuccess) {
            const modal = document.getElementById('successModal');
            modal.style.display = 'flex';
            const closeModal = document.getElementById('closeModal');
            closeModal.onclick = function() {
                modal.style.display = 'none';
            }

            window.onclick = function(event) {
                if (event.target == modal) {
                    modal.style.display = 'none';
                }
            }

            // Reset isSuccess in session
            <?php unset($_SESSION['isSuccess']); ?>
        }
    }

    function closeSuccessModal() {
        const modal = document.getElementById('successModal');
        modal.style.display = 'none';
    }

    function closeErrorModal() {
        const modal = document.getElementById('errorModal');
        modal.style.display = 'none';
    }
</script>
</body>
</html>
