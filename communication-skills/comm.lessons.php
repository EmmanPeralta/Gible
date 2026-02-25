<?php
session_start();
if (!isset($_SESSION['first_name']) || !isset($_SESSION['last_name'])) {
    header("Location: index.php");
    exit();
}

// Database Connection
$conn = new mysqli("localhost", "root", "", "gible_accounts");
$first_name = $conn->real_escape_string($_SESSION['first_name']);
$last_name = $conn->real_escape_string($_SESSION['last_name']);

// Default values
$volume = 100;
$mute = 0;
$kp = 0;
$tts_enabled = 0;
$colorblind_enabled = 0;
$colorblind_type = null;

// Fetch user settings from DB
$result = $conn->query("SELECT volume, mute, kp, tts_enabled, colorblind_enabled, colorblind_type 
                        FROM users WHERE first_name='$first_name' AND last_name='$last_name'");

if ($row = $result->fetch_assoc()) {
    $volume = intval($row['volume']);
    $mute = intval($row['mute']);
    $kp = intval($row['kp']);
    $tts_enabled = isset($row['tts_enabled']) ? intval($row['tts_enabled']) : 0;
    $colorblind_enabled = isset($row['colorblind_enabled']) ? intval($row['colorblind_enabled']) : 0;

    // Only set type if colorblind mode is enabled
    $colorblind_type = ($colorblind_enabled === 1 && !empty($row['colorblind_type']))
                        ? $row['colorblind_type']
                        : null;
}

// Fetch Quiz Scores
$stmt = $conn->prepare("SELECT comm_quiz1, comm_quiz2, comm_quiz3 FROM quiz_scores WHERE first_name=? AND last_name=?");
$stmt->bind_param("ss", $first_name, $last_name);
$stmt->execute();
$stmt->bind_result($score1, $score2, $score3);
$stmt->fetch();
$stmt->close();

// Fetch Highest Emotion for Lesson 1
$stmt2 = $conn->prepare("SELECT comm_quiz1, comm_quiz2, comm_quiz3 FROM quiz_emotions WHERE first_name=? AND last_name=?");
$stmt2->bind_param("ss", $first_name, $last_name);
$stmt2->execute();
$stmt2->bind_result($emotion1 , $emotion2, $emotion3);
$stmt2->fetch();
$stmt2->close();

$conn->close();

$score1 = is_null($score1) ? 0 : $score1;
$score2 = is_null($score2) ? 0 : $score2;
$score3 = is_null($score3) ? 0 : $score3;
$emotion1 = (is_null($emotion1) || $emotion1 === '') ? 'None' : $emotion1;
$emotion2 = (is_null($emotion2) || $emotion2 === '') ? 'None' : $emotion2;
$emotion3 = (is_null($emotion3) || $emotion3 === '') ? 'None' : $emotion3;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comm. Skills - Lessons</title>
    <link rel="stylesheet" href="../style.css">
</head>

<body class="lessons-page">
    <!-- Top Navigation -->
    <div class="nav-buttons1">
        <div class="username speak-on-hover" data-label="<?php echo $_SESSION['first_name'] . ' ' . $_SESSION['last_name']; ?>">
            <?php echo $_SESSION['first_name'] . " " . $_SESSION['last_name']; ?>
        </div>
        <a href="../changepassword.php" class="speak-on-hover" data-label="Change Password">Change Password</a>
        <a href="../logout.php" class="speak-on-hover" data-label="Log Out">Log Out</a>
    </div>

    <!-- Navigation Buttons -->
    <div class="nav-buttons2">
        <div class="kp-container">
            <div class="kp-icon" style="color: #ffbb24;">KP</div>
            <div class="kp-amount speak-on-hover" id="currencyAmount" data-label="<?php echo $kp; ?> K P">
                <?php echo $kp; ?>
            </div>
        </div>
        <a href="../shop.php" class="speak-on-hover" data-label="Shop">SHOP</a>
        <a href="../mainlobby.php" class="speak-on-hover" data-label="Back">BACK</a>
        <a href="#" class="speak-on-hover" data-label="Settings" onclick="openModal(); return false;">SETTINGS</a>
    </div>

    <!-- Lesson Selection -->
    <div class="subject-container">
        <h1 class="choose-subject speak-on-hover" data-label="Communication Skills">COMMUNICATION SKILLS</h1>
        <h2 class="speak-on-hover" data-label="Choose a lesson" style="color: white;">CHOOSE A LESSON</h2>
        <div class="lesson-buttons">
            <button class="lesson-btn speak-on-hover" data-label="Lesson 1" onclick="window.location.href='comm.lesson1.php'">
                Lesson 1<br>
                <span style="display:block; border-bottom:2px solid #222; margin:8px 0;"></span>
                Score <?php echo $score1; ?>/10
                <br>
                <span class="desktop-only" style="display:block; border-bottom:2px solid #222; margin:8px 0;"></span>
                <span class="lesson-emotion desktop-only">Highest Emotion: <?php echo htmlspecialchars($emotion1); ?></span>
            </button>
            <button class="lesson-btn speak-on-hover" data-label="Lesson 2" onclick="window.location.href='comm.lesson2.php'">
                Lesson 2<br>
                <span style="display:block; border-bottom:2px solid #222; margin:8px 0;"></span>
                Score <?php echo $score2; ?>/10
                <span class="desktop-only" style="display:block; border-bottom:2px solid #222; margin:8px 0;"></span>
                <span class="lesson-emotion desktop-only">Highest Emotion: <?php echo htmlspecialchars($emotion2); ?></span>
            </button>
            <button class="lesson-btn speak-on-hover" data-label="Lesson 3" onclick="window.location.href='comm.lesson3.php'">
                Lesson 3<br>
                <span style="display:block; border-bottom:2px solid #222; margin:8px 0;"></span>
                Score <?php echo $score3; ?>/10
                <span class="desktop-only" style="display:block; border-bottom:2px solid #222; margin:8px 0;"></span>
                <span class="lesson-emotion desktop-only">Highest Emotion: <?php echo htmlspecialchars($emotion3); ?></span>
            </button>
        </div>
    </div>

    <!-- Settings Modal -->
    <div id="settings-modal" class="modal-overlay" style="display: none;">
        <div class="modal-content">
            <iframe src="../settings.php" id="settings-frame" frameborder="0"></iframe>
        </div>
    </div>

    <!-- Audio Elements -->
    <audio id="clickSound" src="../sfx/click.mp3" preload="auto"></audio>
    <audio id="closeSound" src="../sfx/close.mp3" preload="auto"></audio>
    <audio id="hoverSound" src="../sfx/hover.mp3" preload="auto"></audio>

    <!-- JS Files -->
    <script src="../modal.js"></script>
    <script src="../settings.js"></script>
    <script src="../sfx/sfx.js"></script>

    <!-- Settings Initialization -->
    <script>
        // settings.js automatically calls settings_load.php on page load

        const initialVolume = <?php echo $mute === 1 ? 0 : $volume; ?>;
        const initialMute = <?php echo $mute; ?>;
        const initialTTS = <?php echo $tts_enabled; ?>;
        const initialCBEnabled = <?php echo $colorblind_enabled; ?>;
        const initialCBType = '<?php echo $colorblind_type; ?>';

        // Pass PHP-side defaults to settings.js (for instant setup before AJAX load)
        initSettings({
            volume: initialVolume,
            mute: initialMute,
            tts: initialTTS,
            colorblindEnabled: initialCBEnabled,
            colorblindType: initialCBType
        });
    </script>

</body>
</html>