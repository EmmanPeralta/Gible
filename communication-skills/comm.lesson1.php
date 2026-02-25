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
<title>Comm. Skills - Lesson 1</title>
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
    <div class="slider-header speak-on-hover" data-label="Communication Lesson 1">Communication Lesson 1</div>
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
        { img: '../images/CommSkills/Lessons/comm.lesson1.page1.jpg', label: "In this lesson, you will learn about: making eye contact, following requests, and listening to sounds" },
        { img: '../images/CommSkills/Lessons/comm.lesson1.page2.jpg', label: "Eye contact means looking at the eyes of the person talking to you" },
        { img: '../images/CommSkills/Lessons/comm.lesson1.page3.jpg', label: "It helps show that you are listening and that you care" },
        { img: '../images/CommSkills/Lessons/comm.lesson1.page4.jpg', label: "If we don't look at someone's eyes, they might think we are not paying attention or we don't want to talk" },
        { img: '../images/CommSkills/Lessons/comm.lesson1.page5.jpg', label: "Listening is an important skill for you to learn how to cooperate with others" },
        { img: '../images/CommSkills/Lessons/comm.lesson1.page6.jpg', label: "Listening can help you recognize the various sounds or noises in your environment" },
        { img: '../images/CommSkills/Lessons/comm.lesson1.page7.jpg', label: "Listening can help to understand how we can listen and follow requests" },
    ];

    if (typeof mountLessonSlider === 'function') {
        mountLessonSlider({ slides, quizHref: 'comm.lesson1.quiz.php' });
    }
    });
    </script>

</body>
</html>