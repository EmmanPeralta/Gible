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
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Comm. Skills - Lesson 2</title>
<link rel="stylesheet" href="../style.css" />
</head>

<body>

<!-- Navigation Buttons -->
<div class="nav-buttons2">
    <a href="comm.lessons.php" class="speak-on-hover" data-label="Back">BACK</a>
    <a href="#" onclick="openModal(); return false;" class="speak-on-hover" data-label="Open settings">SETTINGS</a>
</div>

<!-- Lesson Slider -->
<div class="lesson-slider">
    <div class="slider-header speak-on-hover" data-label="Communication Lesson 2">Communication Lesson 2</div>
    <div class="slider-content">
        <div class="slide" id="slide-box"></div>
    </div>
    <div id="next-slide-container"></div>
    <div class="slider-counter speak-on-hover" id="slide-counter" tabindex="0" aria-label="Slide number"></div>
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
<script src="../modal.js" defer></script>
<script src="../sfx/sfx.js" defer></script>
<script src="../settings.js" defer></script>
<script src="../slider.js" defer></script>

<!-- Settings Initialization -->
<script>
    // settings.js automatically calls settings_load.php on page load
    window.addEventListener('DOMContentLoaded', function () {
        const initialVolume = <?php echo $mute === 1 ? 0 : $volume; ?>;
        const initialMute = <?php echo $mute; ?>;
        const initialTTS = <?php echo $tts_enabled; ?>;
        const initialCBEnabled = <?php echo $colorblind_enabled; ?>;
        const initialCBType = '<?php echo $colorblind_type; ?>';

        // Pass PHP-side defaults to settings.js (for instant setup before AJAX load)
        if (typeof initSettings === 'function') {
            initSettings({
                volume: initialVolume,
                mute: initialMute,
                tts: initialTTS,
                colorblindEnabled: initialCBEnabled,
                colorblindType: initialCBType
            });
        }
    });
</script>

<!-- Main Lesson Slider Script -->
<script>
    window.addEventListener('DOMContentLoaded', function () {
    const slides = [
        { img: '../images/CommSkills/Lessons/comm.lesson2.page1.jpg', label: "In this lesson, you will learn about: understanding your identity, understanding your interests, and understanding your abilities" },
        { img: '../images/CommSkills/Lessons/comm.lesson2.page2.jpg', label: "Understanding your identity. Identity means knowing who you are, such as your name, your family, and what makes you special" },
        { img: '../images/CommSkills/Lessons/comm.lesson2.page3.jpg', label: "Understanding your abilities. Abilities are the things that you are good at, like solving puzzles, singing, running, or helping others" },
        { img: '../images/CommSkills/Lessons/comm.lesson2.page4.jpg', label: "Understanding your interests. Interests are the things you enjoy doing, like playing games, drawing, reading, or listening to music" },
    ];

    if (typeof mountLessonSlider === 'function') {
        mountLessonSlider({ slides, quizHref: 'comm.lesson2.quiz.php' });
    }
    });
</script>

</body>
</html>