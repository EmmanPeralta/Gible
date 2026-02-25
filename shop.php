<?php
session_start();
if (!isset($_SESSION['first_name']) || !isset($_SESSION['last_name'])) {
    header("Location: index.php");
    exit();
}

// DB connection
$conn = new mysqli("localhost", "root", "", "gible_accounts");
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$first_name = $conn->real_escape_string($_SESSION['first_name']);
$last_name = $conn->real_escape_string($_SESSION['last_name']);

// Fetch user data: KP, items, audio, TTS, and colorblind settings
$sql = "SELECT kp, potion, score_up, guard_up, volume, mute, tts_enabled, colorblind_enabled, colorblind_type 
        FROM users 
        WHERE first_name='$first_name' AND last_name='$last_name' 
        LIMIT 1";
$result = $conn->query($sql);

// Default values
$kp = 0;
$potion = 0;
$score_up = 0;
$guard_up = 0;
$volume = 100;
$mute = 0;
$tts_enabled = 0;
$colorblind_enabled = 0;
$colorblind_type = null;

if ($row = $result->fetch_assoc()) {
    $kp = intval($row['kp']);
    $potion = intval($row['potion']);
    $score_up = intval($row['score_up']);
    $guard_up = intval($row['guard_up']);
    $volume = intval($row['volume']);
    $mute = intval($row['mute']);
    $tts_enabled = isset($row['tts_enabled']) ? intval($row['tts_enabled']) : 0;
    $colorblind_enabled = isset($row['colorblind_enabled']) ? intval($row['colorblind_enabled']) : 0;

    // Only assign colorblind type if enabled
    $colorblind_type = ($colorblind_enabled === 1 && !empty($row['colorblind_type']))
                        ? $row['colorblind_type']
                        : null;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop</title>
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
        <a href="#" onclick="if(document.referrer){window.history.back();}else{window.location.href='mainlobby.php';} return false;" class="speak-on-hover" data-label="Back">BACK</a>
        <a href="#" onclick="openModal(); return false;" class="speak-on-hover" data-label="Settings">SETTINGS</a>
    </div>

    <!-- Shop Container -->
    <div class="shop-container">
        <div class="items">
            <!-- Potion Item -->
            <div class="item-card speak-on-hover" data-item="Potion" data-price="30" data-label="Potion.. Adds 10 KP during quiz. Cost.. 30 KP.">
                <img src="images/potion.png" alt="Potion" style="width: 100%; max-width: 180px; height: auto;" />
                <h3 style="color: #ffbb24;">POTION</h3>
                <p style="color: white;">ADDS 10 KP DURING QUIZ</p>
                <p style="color: #ffbb24;">COST: 30 KP</p>
                <div class="item-owned speak-on-hover" id="owned-potion" data-label="You have <?php echo $potion; ?>/10 potions." style="color: white; margin-top: 8px;">
                    You have: <?php echo $potion; ?>/10
                </div>
            </div>

            <!-- Score Up Item -->
            <div class="item-card speak-on-hover" data-item="Score Up!" data-price="20" data-label="Score Up... Correct answer gives 15 KP instead of 10 KP. Cost.. 20 KP.">
                <img src="images/scoreup.png" alt="Score Up" style="width: 100%; max-width: 180px; height: auto;" />
                <h3 style="color: #ffbb24;">SCORE UP!</h3>
                <p style="color: white;">CORRECT ANSWER GIVES 15 KP INSTEAD OF 10 KP</p>
                <p style="color: #ffbb24;">COST: 20 KP</p>
                <div class="item-owned speak-on-hover" id="owned-scoreup" data-label="You have <?php echo $score_up; ?>/10 score up items." style="color: white; margin-top: 8px;">
                    You have: <?php echo $score_up; ?>/10
                </div>
            </div>

            <!-- Guard Up Item -->
            <div class="item-card speak-on-hover" data-item="Guard Up!" data-price="25" data-label="Guard Up. Wrong answer takes only 5 KP instead of 10 KP. Cost.. 25 KP.">
                <img src="images/guardup.png" alt="Guard Up" style="width: 100%; max-width: 180px; height: auto;" />
                <h3 style="color: #ffbb24;">GUARD UP!</h3>
                <p style="color: white;">WRONG ANSWER TAKES ONLY 5 KP INSTEAD OF 10 KP</p>
                <p style="color: #ffbb24;">COST: 25 KP</p>
                <div class="item-owned speak-on-hover" id="owned-guardup" data-label="You have <?php echo $guard_up; ?>/10 guard up items." style="color: white; margin-top: 8px;">
                    You have: <?php echo $guard_up; ?>/10
                </div>
            </div>
        </div>

        <!-- Cart Section -->
        <div class="cart">
            <h3 style="color: #ffbb24;" class="speak-on-hover" data-label="Cart">CART</h3>
            <ul id="cart-items" style="color: white;"></ul>
            <p style="color: #ffbb24;" id="total-price-container" class="speak-on-hover" data-label="Total: 0 KP">
                Total: <span id="total-price">0</span> KP
            </p>
            <button id="buy-button" class="buy-button speak-on-hover" data-label="Buy">BUY</button>
            <div id="purchase-message" class="purchase-message" style="margin-top:8px; color:#2ecc71;"></div>
        </div>
    </div>

    <!-- SETTINGS MODAL -->
    <div id="settings-modal" class="modal-overlay" style="display: none;">
        <div class="modal-content">
            <iframe src="settings.php" id="settings-frame" frameborder="0"></iframe>
        </div>
    </div>

    <!-- Audio Elements -->
    <audio id="clickSound" src="sfx/click.mp3" preload="auto"></audio>
    <audio id="closeSound" src="sfx/close.mp3" preload="auto"></audio>
    <audio id="hoverSound" src="sfx/hover.mp3" preload="auto"></audio>

    <!-- External JS -->
    <script src="shop.js"></script>
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