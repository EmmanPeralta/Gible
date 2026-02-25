<?php
session_start();
if (!isset($_SESSION['first_name']) || !isset($_SESSION['last_name'])) {
    exit("Unauthorized");
}

$conn = new mysqli("localhost", "root", "", "gible_accounts");
if ($conn->connect_error) exit("Database connection failed");

$first_name = $conn->real_escape_string($_SESSION['first_name']);
$last_name = $conn->real_escape_string($_SESSION['last_name']);

$volume = 100;
$mute = 0;
$tts_enabled = 0;
$colorblind_enabled = 0;
$colorblind_type = 'green';

$result = $conn->query("SELECT volume, mute, tts_enabled, colorblind_enabled, colorblind_type 
                        FROM users 
                        WHERE first_name='$first_name' AND last_name='$last_name'");
if ($result && $row = $result->fetch_assoc()) {
    $volume = intval($row['volume']);
    $mute = intval($row['mute']);
    $tts_enabled = isset($row['tts_enabled']) ? intval($row['tts_enabled']) : 0;
    $colorblind_enabled = isset($row['colorblind_enabled']) ? intval($row['colorblind_enabled']) : 0;
    $colorblind_type = !empty($row['colorblind_type']) ? $row['colorblind_type'] : 'green';
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>GIBLE - Settings</title>
<link rel="stylesheet" href="style.css">
<style>
    html, body { margin: 0; padding: 0; }
    .settings-container { margin-top: 0; margin-bottom: 0; }
</style>
</head>

<body class="settings-page">
    
    <div class="settings-container">
        <div class="settings-title">SETTINGS</div>

        <div class="tab">AUDIO</div>
        <div class="setting-controls">
            <div class="setting-row">
                <p>SOUND EFFECTS</p>
                <input type="range" id="sfx-volume" min="0" max="100" value="<?php echo $volume; ?>" />
                <span id="sfx-percent"><?php echo $volume; ?>%</span>
            </div>
            <div class="setting-row">
                <p>MUTE</p>
                <input type="checkbox" id="mute-all" <?php echo ($mute === 1) ? 'checked' : ''; ?> />
            </div>
        </div>

        <div class="tab">ACCESSIBILITY</div>
        <div class="setting-controls">
            <div class="setting-row">
                <p>TEXT-TO-SPEECH</p>
                <input type="checkbox" id="tts-toggle" <?php echo ($tts_enabled === 1) ? 'checked' : ''; ?> />
            </div>
            <div class="setting-row">
                <p>COLORBLIND MODE</p>
                <input type="checkbox" id="colorblind-toggle" <?php echo ($colorblind_enabled === 1) ? 'checked' : ''; ?> />
            </div>
            <div class="setting-row" id="colorblind-type-row" style="display: <?php echo ($colorblind_enabled === 1) ? 'flex' : 'none'; ?>;">
                <p>COLORBLIND TYPE</p>
                <select id="colorblind-type">
                    <option value="green" <?php echo ($colorblind_type === 'green') ? 'selected' : ''; ?>>Deuteranomaly (Green-Weak)</option>
                    <option value="red" <?php echo ($colorblind_type === 'red') ? 'selected' : ''; ?>>Protanomaly (Red-Weak)</option>
                </select>
            </div>
        </div>

        <div class="settings-buttons">
            <button id="save-btn" class="settings-btn">APPLY & SAVE</button>
            <a href="#" class="settings-btn" onclick="parent.closeModal(); return false;">CLOSE</a>
        </div>

        <audio id="clickSound" src="sfx/click.mp3" preload="auto"></audio>
        <audio id="closeSound" src="sfx/close.mp3" preload="auto"></audio>
        <audio id="hoverSound" src="sfx/hover.mp3" preload="auto"></audio>
        <audio id="wrongSound" src="sfx/wrong.mp3" preload="auto"></audio>

        <div id="notification" class="notification" style="display:none;"></div>
    </div>

    <script src="modal.js"></script>
    <script src="settings.js"></script>
    <script src="sfx/sfx.js"></script>
</body>
</html>