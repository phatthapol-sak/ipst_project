<?php
include 'config.php';
session_start();
$isLoggedIn = isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;
$username = $isLoggedIn ? $_SESSION['username'] : '';

// Fetch unique dates that have data
$stmt = $conn->prepare("SELECT DISTINCT DATE(created_at) as date FROM messages WHERE result IS NOT NULL ORDER BY date DESC");
$stmt->execute();
$stmt->bind_result($date);

$dates = [];
while ($stmt->fetch()) {
    $dates[] = $date;
}
$stmt->close();

// Fetch unique weeks that have data
$stmt = $conn->prepare("SELECT DISTINCT YEARWEEK(created_at, 1) as week FROM messages WHERE result IS NOT NULL ORDER BY week DESC");
$stmt->execute();
$stmt->bind_result($week);

$weeks = [];
while ($stmt->fetch()) {
    $weeks[] = $week;
}
$stmt->close();

$selected_date = isset($_GET['date']) ? $_GET['date'] : null;
$selected_week = isset($_GET['week']) ? $_GET['week'] : null;

// Fetch levels that have data based on selected date or week
if ($selected_date) {
    $stmt = $conn->prepare("SELECT DISTINCT u.level FROM messages m JOIN users u ON m.user_id = u.id WHERE DATE(m.created_at) = ? AND m.result IS NOT NULL ORDER BY u.level");
    $stmt->bind_param("s", $selected_date);
} elseif ($selected_week) {
    $stmt = $conn->prepare("SELECT DISTINCT u.level FROM messages m JOIN users u ON m.user_id = u.id WHERE YEARWEEK(m.created_at, 1) = ? AND m.result IS NOT NULL ORDER BY u.level");
    $stmt->bind_param("s", $selected_week);
} else {
    $stmt = $conn->prepare("SELECT DISTINCT u.level FROM messages m JOIN users u ON m.user_id = u.id WHERE m.result IS NOT NULL ORDER BY u.level");
}

$stmt->execute();
$stmt->bind_result($level);

$levels_with_data = [];
while ($stmt->fetch()) {
    $levels_with_data[] = $level;
}
$stmt->close();

$selected_level = isset($_GET['level']) ? $_GET['level'] : (isset($levels_with_data[0]) ? $levels_with_data[0] : null);

// Fetch data based on selected date, week, and level
if ($selected_date) {
    $stmt = $conn->prepare("SELECT AVG(m.result) as avg_result FROM messages m JOIN users u ON m.user_id = u.id WHERE DATE(m.created_at) = ?");
    $stmt->bind_param("s", $selected_date);
} elseif ($selected_week) {
    $stmt = $conn->prepare("SELECT AVG(m.result) as avg_result FROM messages m JOIN users u ON m.user_id = u.id WHERE YEARWEEK(m.created_at, 1) = ?");
    $stmt->bind_param("s", $selected_week);
} else {
    $stmt = $conn->prepare("SELECT AVG(m.result) as avg_result FROM messages m JOIN users u ON m.user_id = u.id");
}

$stmt->execute();
$stmt->bind_result($avg_result);
$stmt->fetch();
$stmt->close();

if ($selected_date) {
    $stmt = $conn->prepare("SELECT u.level, m.result, COUNT(*) as count FROM messages m JOIN users u ON m.user_id = u.id WHERE DATE(m.created_at) = ? GROUP BY u.level, m.result");
    $stmt->bind_param("s", $selected_date);
} elseif ($selected_week) {
    $stmt = $conn->prepare("SELECT u.level, m.result, COUNT(*) as count FROM messages m JOIN users u ON m.user_id = u.id WHERE YEARWEEK(m.created_at, 1) = ? GROUP BY u.level, m.result");
    $stmt->bind_param("s", $selected_week);
} else {
    $stmt = $conn->prepare("SELECT u.level, m.result, COUNT(*) as count FROM messages m JOIN users u ON m.user_id = u.id GROUP BY u.level, m.result");
}

$stmt->execute();
$result = $stmt->get_result();

$bar_chart_data = [];
while ($row = $result->fetch_assoc()) {
    $bar_chart_data[] = $row;
}
$stmt->close();

if ($selected_date && $selected_level) {
    $stmt = $conn->prepare("SELECT AVG(m.result) as avg_result FROM messages m JOIN users u ON m.user_id = u.id WHERE DATE(m.created_at) = ? AND u.level = ?");
    $stmt->bind_param("ss", $selected_date, $selected_level);
} elseif ($selected_week && $selected_level) {
    $stmt = $conn->prepare("SELECT AVG(m.result) as avg_result FROM messages m JOIN users u ON m.user_id = u.id WHERE YEARWEEK(m.created_at, 1) = ? AND u.level = ?");
    $stmt->bind_param("ss", $selected_week, $selected_level);
} elseif ($selected_level) {
    $stmt = $conn->prepare("SELECT AVG(m.result) as avg_result FROM messages m JOIN users u ON m.user_id = u.id WHERE u.level = ?");
    $stmt->bind_param("s", $selected_level);
} else {
    $stmt = $conn->prepare("SELECT AVG(m.result) as avg_result FROM messages m JOIN users u ON m.user_id = u.id");
}

$stmt->execute();
$stmt->bind_result($avg_result_selected);
$stmt->fetch();
$stmt->close();

if ($selected_date && $selected_level) {
    $stmt = $conn->prepare("SELECT u.level, m.result, COUNT(*) as count FROM messages m JOIN users u ON m.user_id = u.id WHERE DATE(m.created_at) = ? AND u.level = ? GROUP BY u.level, m.result");
    $stmt->bind_param("ss", $selected_date, $selected_level);
} elseif ($selected_week && $selected_level) {
    $stmt = $conn->prepare("SELECT u.level, m.result, COUNT(*) as count FROM messages m JOIN users u ON m.user_id = u.id WHERE YEARWEEK(m.created_at, 1) = ? AND u.level = ? GROUP BY u.level, m.result");
    $stmt->bind_param("ss", $selected_week, $selected_level);
} elseif ($selected_level) {
    $stmt = $conn->prepare("SELECT u.level, m.result, COUNT(*) as count FROM messages m JOIN users u ON m.user_id = u.id WHERE u.level = ? GROUP BY u.level, m.result");
    $stmt->bind_param("s", $selected_level);
} else {
    $stmt = $conn->prepare("SELECT u.level, m.result, COUNT(*) as count FROM messages m JOIN users u ON m.user_id = u.id WHERE u.level = ? GROUP BY u.level, m.result");
    $stmt->bind_param("s", $selected_level);
}

$stmt->execute();
$result_selected = $stmt->get_result();

$bar_chart_data_selected = [];
while ($row = $result_selected->fetch_assoc()) {
    $bar_chart_data_selected[] = $row;
}
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Average Results</title>
    <link rel="stylesheet" href="css/average_results.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
    <div class="container">
        <div class="box">
            <div class="form-container">
                <h3>ภาพรวม</h3>
                <form action="average_results.php" method="GET">
                    <label for="date">Select Date:</label>
                    <select name="date" id="date" onchange="this.form.submit()">
                        <option value="">All Dates</option>
                        <?php foreach ($dates as $date): ?>
                            <option value="<?php echo $date; ?>" <?php echo $selected_date == $date ? 'selected' : ''; ?>><?php echo $date; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label for="week">Select Week:</label>
                    <select name="week" id="week" onchange="this.form.submit()">
                        <option value="">All Weeks</option>
                        <?php foreach ($weeks as $week): ?>
                            <option value="<?php echo $week; ?>" <?php echo $selected_week == $week ? 'selected' : ''; ?>><?php echo $week; ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
            <div class="inner-box">
                <div class="inner-inner-box">
                    <canvas id="gaugeChartAll" width="400" height="400"></canvas>
                </div>
                <div class="inner-inner-box">
                    <canvas id="barChartAll"></canvas>
                </div>
            </div>
        </div>
        <div class="box">
            <div class="form-container">
                <h3>แต่ละระดับชั้น</h3>
                <form action="average_results.php" method="GET">
                    <label for="date">Select Date:</label>
                    <select name="date" id="date" onchange="this.form.submit()">
                        <option value="">All Dates</option>
                        <?php foreach ($dates as $date): ?>
                            <option value="<?php echo $date; ?>" <?php echo $selected_date == $date ? 'selected' : ''; ?>><?php echo $date; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label for="week">Select Week:</label>
                    <select name="week" id="week" onchange="this.form.submit()">
                        <option value="">All Weeks</option>
                        <?php foreach ($weeks as $week): ?>
                            <option value="<?php echo $week; ?>" <?php echo $selected_week == $week ? 'selected' : ''; ?>><?php echo $week; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label for="level">ม.</label>
                    <select name="level" id="level" onchange="this.form.submit()">
                        <?php foreach ($levels_with_data as $level): ?>
                            <option value="<?php echo $level; ?>" <?php echo $selected_level == $level ? 'selected' : ''; ?>><?php echo $level; ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
            <div class="inner-box">
                <div class="inner-inner-box">
                    <canvas id="gaugeChartSelected" width="400" height="400"></canvas>
                </div>
                <div class="inner-inner-box">
                    <canvas id="barChartSelected"></canvas>
                </div>
            </div>
        </div>
    </div>
    <a href="summary.php" class="summary-button">ไปหน้าผลส่วนตัว</a>
</main>
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
    } else {
        document.querySelector('.nav-login-btn').style.display = 'inline-block';
        document.querySelector('.nav-signup-btn').style.display = 'inline-block';
        document.querySelector('.nav-user').style.display = 'none';
    }

    const avgResult = <?php echo $avg_result; ?>;
    const barChartData = <?php echo json_encode($bar_chart_data); ?>;
    const avgResultSelected = <?php echo $avg_result_selected; ?>;
    const barChartDataSelected = <?php echo json_encode($bar_chart_data_selected); ?>;

    function getLabel(value) {
        if (value <= 1) {
            return "ไม่มีความเสี่ยงซึมเศร้า";
        } else if (value <= 2) {
            return "เสี่ยงซึมเศร้าเล็กน้อย";
        } else if (value <= 3) {
            return "เสี่ยงซึมเศร้าปานกลาง";
        } else {
            return "เสี่ยงซึมเศร้าค่อนข้างมาก";
        }
    }

    function drawGaugeChart(ctx, value) {
        const centerX = ctx.canvas.width / 2;
        const centerY = ctx.canvas.height / 2 + 50;
        const radius = Math.min(ctx.canvas.width, ctx.canvas.height) / 2 - 50;
        const startAngle = Math.PI;
        const endAngle = 2 * Math.PI;

        ctx.clearRect(0, 0, ctx.canvas.width, ctx.canvas.height);

        if (value === null || value === undefined) {
            ctx.font = '24px Arial';
            ctx.fillStyle = 'black';
            ctx.textAlign = 'center';
            ctx.fillText('ไม่มีข้อมูล', centerX, centerY);
            return;
        }

        const needleValue = value;

        ctx.beginPath();
        ctx.arc(centerX, centerY, radius, startAngle, endAngle);
        ctx.lineWidth = 20;
        ctx.strokeStyle = '#e6e6e6';
        ctx.stroke();
        ctx.closePath();

        const colors = ['#FF5722', '#FF9800', '#FFEB3B', '#4CAF50'];
        const sections = colors.length;
        const sectionAngle = (endAngle - startAngle) / sections;

        colors.forEach((color, index) => {
            ctx.beginPath();
            ctx.arc(centerX, centerY, radius, startAngle + index * sectionAngle, startAngle + (index + 1) * sectionAngle);
            ctx.strokeStyle = color;
            ctx.stroke();
            ctx.closePath();
        });

        const needleAngle = startAngle + (needleValue / 4) * (endAngle - startAngle);
        ctx.beginPath();
        ctx.moveTo(centerX, centerY);
        ctx.lineTo(centerX + radius * Math.cos(needleAngle), centerY + radius * Math.sin(needleAngle));
        ctx.lineWidth = 5;
        ctx.strokeStyle = 'black';
        ctx.stroke();
        ctx.closePath();

        ctx.font = '24px Arial';
        ctx.fillStyle = 'black';
        ctx.textAlign = 'center';
        ctx.fillText(needleValue.toFixed(4), centerX, centerY - 10);

        const label = getLabel(needleValue);
        ctx.font = '18px Arial';
        ctx.fillText(label, centerX, centerY + 30);

        ctx.font = '16px Arial';
        ctx.fillText('0', centerX - radius, centerY + 20);
        ctx.fillText('4', centerX + radius, centerY + 20);
    }

    const gaugeCtxAll = document.getElementById('gaugeChartAll').getContext('2d');
    drawGaugeChart(gaugeCtxAll, avgResult);

    const gaugeCtxSelected = document.getElementById('gaugeChartSelected').getContext('2d');
    drawGaugeChart(gaugeCtxSelected, avgResultSelected);

    function drawBarChart(ctx, barChartData) {
        const barData = {};
        barChartData.forEach(item => {
            if (!barData[item.level]) {
                barData[item.level] = [0, 0, 0, 0, 0];
            }
            barData[item.level][item.result] = item.count;
        });

        const levels = Object.keys(barData);
        const datasets = levels.map(level => {
            return {
                label: `Level ${level}`,
                data: barData[level],
                backgroundColor: `rgba(${Math.random()*255}, ${Math.random()*255}, ${Math.random()*255}, 0.2)`,
                borderColor: `rgba(${Math.random()*255}, ${Math.random()*255}, ${Math.random()*255}, 1)`,
                borderWidth: 1,
                stack: 'Stack 0'
            };
        });

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: [0, 1, 2, 3, 4],
                datasets: datasets
            },
            options: {
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'ระดับ',
                            font: {
                                size: 16
                            }
                        },
                        stacked: true
                    },
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'จำนวนคน',
                            font: {
                                size: 16
                            }
                        },
                        stacked: true
                    }
                }
            }
        });
    }

    const barCtxAll = document.getElementById('barChartAll').getContext('2d');
    drawBarChart(barCtxAll, barChartData);

    const barCtxSelected = document.getElementById('barChartSelected').getContext('2d');
    drawBarChart(barCtxSelected, barChartDataSelected);

    window.addEventListener('resize', () => {
        gaugeCtxAll.canvas.width = gaugeCtxAll.canvas.parentElement.clientWidth;
        gaugeCtxAll.canvas.height = gaugeCtxAll.canvas.parentElement.clientHeight;
        gaugeCtxSelected.canvas.width = gaugeCtxSelected.canvas.parentElement.clientWidth;
        gaugeCtxSelected.canvas.height = gaugeCtxSelected.canvas.parentElement.clientHeight;
        drawGaugeChart(gaugeCtxAll, avgResult);
        drawGaugeChart(gaugeCtxSelected, avgResultSelected);
    });
</script>

</body>
</html>
