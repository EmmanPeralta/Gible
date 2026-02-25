<?php
require_once 'auth.php';

// Fetch settings from DB for the user
$conn = new mysqli("localhost", "root", "", "gible_accounts");
$first_name = $conn->real_escape_string($_SESSION['first_name']);
$last_name = $conn->real_escape_string($_SESSION['last_name']);

$volume = 100;
$mute = 0;
$tts_enabled = 0;
$colorblind_enabled = 0;
$colorblind_type = null;

$result = $conn->query("SELECT volume, mute, tts_enabled, colorblind_enabled, colorblind_type 
                        FROM users WHERE first_name='$first_name' AND last_name='$last_name'");
if ($row = $result->fetch_assoc()) {
    $volume = intval($row['volume']);
    $mute = intval($row['mute']);
    $tts_enabled = isset($row['tts_enabled']) ? intval($row['tts_enabled']) : 0;

    // Colorblind
    $colorblind_enabled = isset($row['colorblind_enabled']) ? intval($row['colorblind_enabled']) : 0;
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
    <title>Change Password</title>
    <link rel="stylesheet" href="style.css" />
    <style>
        /* small inline style for the char message above buttons */
        #charMessage {
            color: white;
            margin: 1px auto;
            display: none;
            text-align: center;
            width: 100%;
        }
        .settings-buttons {
            margin-top: 6px;
            display: flex;
            gap: 12px;
            justify-content: center;
            align-items: center;
        }
    </style>
</head>

<body>
    <div class="changepassword-container">
        <!-- Title for the Change Password section -->
        <div class="settings-title speak-on-hover" data-label="Change Password">CHANGE PASSWORD</div>

        <!-- Change Password Form -->
        <form method="POST" action="changepassword.php" novalidate>
            <div class="setting-controls">
                <div class="setting-row">
                    <div style="position: relative; display: inline-block;">
                        <input
                            type="password"
                            id="password"
                            name="new_password"
                            placeholder="Enter New Password"
                            maxlength="8"
                            required
                            class="speak-on-hover"
                            data-label="Enter New Password"
                        />
                        <img
                            src="images/eye-close.png"
                            alt="Show Password"
                            id="togglePassword"
                            style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer; width: 20px;"
                            class="speak-on-hover"
                            data-label="Show Password"
                        />
                    </div>
                </div>
            </div>

            <div id="charMessage" style="display:none;color:#fff;text-align:center;width:100%;">Character limit reached</div>

            <script>
                const togglePassword = document.getElementById('togglePassword');
                const passwordInput = document.getElementById('password');
                const charMessage = document.getElementById('charMessage');
                const form = document.querySelector('form[action="changepassword.php"]');

                togglePassword.addEventListener('click', () => {
                    const type = passwordInput.type === 'password' ? 'text' : 'password';
                    passwordInput.type = type;
                    togglePassword.src = type === 'text' ? 'images/eye-open.png' : 'images/eye-close.png';
                });

                passwordInput.addEventListener('input', () => {
                    if (passwordInput.value.length > 8) {
                        passwordInput.value = passwordInput.value.slice(0, 8);
                    }
                    if (passwordInput.value.length >= 8) {
                        charMessage.textContent = 'Character limit reached';
                        charMessage.style.color = '#fff';
                        charMessage.style.display = 'block';
                    } else {
                        charMessage.style.display = 'none';
                    }
                });

                passwordInput.addEventListener('paste', (e) => {
                    setTimeout(() => {
                        if (passwordInput.value.length > 8) {
                            passwordInput.value = passwordInput.value.slice(0, 8);
                        }
                        if (passwordInput.value.length >= 8) {
                            charMessage.textContent = 'Character limit reached';
                            charMessage.style.color = '#fff';
                            charMessage.style.display = 'block';
                        } else {
                            charMessage.style.display = 'none';
                        }
                    }, 0);
                });

                form.addEventListener('submit', (e) => {
                    if (passwordInput.value.length < 8) {
                        e.preventDefault();
                        charMessage.textContent = 'Password must be 8 characters';
                        charMessage.style.color = 'white';
                        charMessage.style.display = 'block';
                        passwordInput.focus();
                    }
                });
            </script>

            <?php
            if (isset($_SESSION['message'])) {
                echo '<div class="session-message speak-on-hover" data-label="' . htmlspecialchars($_SESSION['message']) . '">' . htmlspecialchars($_SESSION['message']) . '</div>';
                unset($_SESSION['message']);
            }
            ?>

            <div class="settings-buttons">
                <button type="submit" name="change_password" class="settings-btn speak-on-hover" data-label="Change" onclick="sessionStorage.setItem('passwordChanged', 'true');">CHANGE</button>
                <a href="#" onclick="handleClose(); return false;" class="settings-btn speak-on-hover" data-label="Close">CLOSE</a>
            </div>

            <script>
                function handleClose() {
                    if (sessionStorage.getItem('passwordChanged') === 'true') {
                        history.go(-2);
                        sessionStorage.removeItem('passwordChanged');
                    } else {
                        history.back();
                    }
                }
            </script>

            <audio id="clickSound" src="sfx/click.mp3" preload="auto"></audio>
            <audio id="closeSound" src="sfx/close.mp3" preload="auto"></audio>
            <audio id="hoverSound" src="sfx/hover.mp3" preload="auto"></audio>
        </form>

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
    </div>

</body>
</html>
