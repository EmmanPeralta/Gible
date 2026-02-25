// Assign DOM audio elements to variables
const clickSound = document.getElementById('clickSound');
const hoverSound = document.getElementById('hoverSound');
const closeSound = document.getElementById('closeSound');
const wrongSound = document.getElementById('wrongSound');

// Plays the provided audio element from the start
function playSound(sound) {
    if (!sound) return;
    sound.currentTime = 0;
    sound.play().catch(err => console.error("Audio error:", err));
}

// All clickable elements with sound effects
const clickSelectors = [
    'button[name="signup"]',
    'button[name="login"]',
    'button[name="forgot"]',
    'a[href="changepassword.php"]',
    'a[href="../changepassword.php"]',
    'a[href="shop.php"]',
    'a[href="../shop.php"]',
    'a[onclick="openModal(); return false;"]',
    '.subject-icon img',
    'button[name="change_password"]',
    '#mute-all',
    '#tts-toggle',
    '#colorblind-toggle',
    '#colorblind-type',
    '.settings-btn:nth-of-type(1)',
    '.settings-btn:nth-of-type(2)',
    '.lesson-btn',
    '.item-card',
    '#buy-button',
    '.plus-btn',
    'a[href="#"]',
    '.settings-btn',
    '.item-box',
    '.powerup-box',
    '.nav-buttons2 a[href="#"] + a',
];

const closeSelectors = [
    'a[href="logout.php"]',
    'a[href="../logout.php"]',
    'a[onclick="history.go(-2);"]',
    'a.settings-btn[onclick^="parent.closeModal"]',
    '#buy-button[disabled]',
    '.minus-btn',
    'a[href="logout.php"]',
    '.nav-buttons2 a[href="#"]',
    'a[href="../mainlobby.php"]',
    'a[href="comm.lessons.php"]',
    'a[href="number.lessons.php"]',
    'a[href="life.lessons.php"]',
    'a[href="se.lessons.php"]',
];

// Unified handler for click + hover
function addEnhancedEvents(selector, options = {}) {
    document.querySelectorAll(selector).forEach(elem => {
        // Hover sound
        elem.addEventListener('mouseenter', () => playSound(hoverSound));

        // Click sound and optional delay/override behavior
        elem.addEventListener('click', (e) => {
            // Always prevent default if an onDelayedAction is set (navigation)
            if (options.onDelayedAction) e.preventDefault();
            else if (options.preventDefault) e.preventDefault();

            const soundToPlay = options.sound || clickSound;
            playSound(soundToPlay);

            // If there's a delayed action (like navigation), wait for the sound to finish
            if (options.onDelayedAction) {
                const delay = (soundToPlay && soundToPlay.duration)
                    ? soundToPlay.duration * 1000
                    : 300; // fallback to 300ms if duration is not available
                setTimeout(() => options.onDelayedAction(elem), delay);
            } else if (options.delay && options.onDelayedAction) {
                setTimeout(() => options.onDelayedAction(elem), options.delay);
            }
        });
    });
}

// Apply enhanced events to all click selectors (default click sound)
clickSelectors.forEach(selector => {
    addEnhancedEvents(selector);
});

// Apply enhanced events to all close selectors (with close sound & behavior)
closeSelectors.forEach(selector => {
    // Default close behavior
    let action = null;
    let prevent = false;

    if (selector.includes('logout.php')) {
        action = (elem) => {
            window.location.href = elem.getAttribute('href');
        };
        prevent = true;
    } else if (selector.includes('history.back()') || selector.includes('history.go(-2)')) {
        action = () => {
            history.back(); // or history.go(-2)
        };
        prevent = true;
    }

    addEnhancedEvents(selector, {
        sound: closeSound,
        preventDefault: prevent,
        onDelayedAction: action
    });
});

// Add hover-only sound to answer buttons
document.querySelectorAll('.answer-btn').forEach(elem => {
    elem.addEventListener('mouseenter', () => playSound(hoverSound));
});