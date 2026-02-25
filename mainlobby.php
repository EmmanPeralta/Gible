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
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Main Lobby</title>
<link rel="stylesheet" href="style.css" />
</head>

<body>
<!-- Top Navigation -->
<div class="nav-buttons1">
    <div class="username speak-on-hover" data-label="<?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?>">
        <?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?>
    </div>
    <a href="changepassword.php" class="speak-on-hover" data-label="Change Password">Change Password</a>
    <a href="logout.php" class="speak-on-hover" data-label="Log Out">Log Out</a>
</div>

<!-- Secondary Navigation -->
<div class="nav-buttons2">
    <div class="kp-container">
        <div class="kp-icon" style="color: #ffbb24;">KP</div>
        <div class="kp-amount speak-on-hover" id="currencyAmount" data-label="<?php echo $kp; ?> K P">
            <?php echo $kp; ?>
        </div>
    </div>
    <a href="shop.php" class="speak-on-hover" data-label="Shop">SHOP</a>
    <a href="#" class="speak-on-hover" data-label="Settings" onclick="openModal(); return false;">SETTINGS</a>
</div>

<!-- Subject Selection -->
<div class="subject-container">
    <div class="choose-subject speak-on-hover" data-label="Choose a subject">CHOOSE A SUBJECT</div>
    <div class="subject-grid">
        <a class="subject-card speak-on-hover" href="communication-skills/comm.lessons.php" data-label="Communication Skills">
            <div class="subject-icon">
                <img src="images/communication.png" alt="Communication Skills" style="width: 100%; max-width: 170px; height: auto;" />
            </div>
            COMMUNICATION SKILLS
        </a>
        <a class="subject-card speak-on-hover" href="number-skills/number.lessons.php" data-label="Number Skills">
            <div class="subject-icon">
                <img src="images/number.png" alt="Number Skills" style="width: 100%; max-width: 170px; height: auto;" />
            </div>
            NUMBER SKILLS
        </a>
        <a class="subject-card speak-on-hover" href="life-skills/life.lessons.php" data-label="Life Skills">
            <div class="subject-icon">
                <img src="images/life.png" alt="Life Skills" style="width: 100%; max-width: 170px; height: auto;" />
            </div>
            LIFE SKILLS
        </a>
        <a class="subject-card speak-on-hover" href="fa-skills/fa.lessons.php" data-label="Functional Academics">
            <div class="subject-icon">
                <img src="images/fa.png" alt="Functional Academics" style="width: 90%; max-width: 170px; height: auto;" />
            </div>
            FUNCTIONAL ACADEMICS
        </a>
    </div>
</div>

<!-- Settings Modal -->
<div id="settings-modal" class="modal-overlay" style="display: none;">
    <div class="modal-content">
        <iframe src="settings.php" id="settings-frame" frameborder="0"></iframe>
    </div>
</div>

<!-- Audio Elements -->
<audio id="clickSound" src="sfx/click.mp3" preload="auto"></audio>
<audio id="closeSound" src="sfx/close.mp3" preload="auto"></audio>
<audio id="hoverSound" src="sfx/hover.mp3" preload="auto"></audio>

<!-- JS Files -->
<script src="modal.js"></script>
<script src="sfx/sfx.js"></script>
<script src="settings.js"></script>

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
