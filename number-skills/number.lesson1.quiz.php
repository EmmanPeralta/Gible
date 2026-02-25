<?php
session_start();
if (!isset($_SESSION['first_name']) || !isset($_SESSION['last_name'])) {
    header("Location: index.php");
    exit();
}

// Connect to database
$conn = new mysqli("localhost", "root", "", "gible_accounts");
$first_name = $conn->real_escape_string($_SESSION['first_name']);
$last_name = $conn->real_escape_string($_SESSION['last_name']);

// Default values
$potion = 0;
$score_up = 0;
$guard_up = 0;
$volume = 100;
$mute = 0;
$kp = 0;
$colorblind_enabled = 0;
$colorblind_type = null;
$tts_enabled = 0;
$last_score = null;

// Fetch all user settings in one query
$result = $conn->query("
    SELECT potion, score_up, guard_up, volume, mute, kp, tts_enabled, colorblind_enabled, colorblind_type
    FROM users
    WHERE first_name='$first_name' AND last_name='$last_name'
");

if ($row = $result->fetch_assoc()) {
    // Items
    $potion = isset($row['potion']) ? intval($row['potion']) : 0;
    $score_up = isset($row['score_up']) ? intval($row['score_up']) : 0;
    $guard_up = isset($row['guard_up']) ? intval($row['guard_up']) : 0;

    // Volume & KP
    $volume = isset($row['volume']) ? intval($row['volume']) : 100;
    $mute = isset($row['mute']) ? intval($row['mute']) : 0;
    $kp = isset($row['kp']) ? intval($row['kp']) : 0;

    // TTS
    $tts_enabled = isset($row['tts_enabled']) ? intval($row['tts_enabled']) : 0;

    // Colorblind
    $colorblind_enabled = isset($row['colorblind_enabled']) ? intval($row['colorblind_enabled']) : 0;
    $colorblind_type = ($colorblind_enabled === 1 && !empty($row['colorblind_type'])) ? $row['colorblind_type'] : null;
}
// Keep connection open for subsequent queries

// Add: query previous score for this quiz
$stmt_prev = $conn->prepare("SELECT num_quiz1 FROM quiz_scores WHERE first_name=? AND last_name=?");
$stmt_prev->bind_param("ss", $first_name, $last_name);
$stmt_prev->execute();
$stmt_prev->bind_result($prev);
if ($stmt_prev->fetch()) {
    $last_score = is_null($prev) ? null : intval($prev);
}
$stmt_prev->close();
// Now safe to close the connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Number Skills - Lesson 1 - Quiz</title>
    <link rel="stylesheet" href="../style.css">
</head>

<body>
    <!-- Layout: Emotion Tracker (left) + Quiz (right) -->
        <div class="page-row" style="height:0; position:relative;">
            <div id="emotion-tracker" style="margin-top:0; min-width:260px; position:fixed; top:20px; left:20px; z-index:1000; width:260px; border-radius:8px; padding:8px;">
              <div style="font-weight:600; margin-bottom:6px;">Emotion Tracker</div>
              <button id="et-toggle" class="speak-on-hover" data-label="Start Emotion Tracker" style="padding:8px 12px; margin-bottom:8px;">
                ▶ Start Emotion Tracker
              </button>
                <div id="et-wrap" style="display:none; margin-top:0;">
                    <div style="position: relative; display: inline-block;">
                        <video id="et-video" width="200" height="150" autoplay muted></video>
                        <canvas id="et-overlay" width="200" height="150" style="position:absolute; left:0; top:0;"></canvas>
                </div>
                    <div id="et-emotions" style="margin-top: 10px; font-size: 14px; font-family: Arial, sans-serif; max-height: 140px;">
                        <h3 style="margin:8px 0;">Current Emotions</h3>
                        <div id="et-emotion-list" width="100">
                  </div>
                </div>
              </div>
            </div>
        </div>

        <div class="page-row" style="display:flex; align-items:flex-start; gap:16px;">
            <div class="quiz-container" style="flex:1;">
                <div class="quiz-header">QUESTION <span id="question-num">1</span> / <span id="question-total">10</span></div>
                <div class="question-box" id="question-box"></div>
                <div class="quiz-flex-row">
                    <div class="side-col">
                        <div class="side-title">ITEMS</div>
                        <div class="item-list">
                            <div class="item-box speak-on-hover" id="item-potion" data-label="Potion. Adds 10 KP during quiz. Click to use." onclick="selectItem('potion')">
                                <img src="../images/potion.png" alt="Potion">
                                <span class="item-qty" id="qty-potion">x<?php echo $potion; ?></span>
                            </div>
                            <div class="item-box speak-on-hover" id="item-score_up" data-label="Score Up. Correct answer gives 15 KP instead of 10 KP. Click to use." onclick="selectItem('score_up')">
                                <img src="../images/scoreup.png" alt="Score Up">
                                <span class="item-qty" id="qty-scoreup">x<?php echo $score_up; ?></span>
                            </div>
                            <div class="item-box speak-on-hover" id="item-guard_up" data-label="Guard Up. Wrong answer takes only 5 KP instead of 10 KP. Click to use." onclick="selectItem('guard_up')">
                                <img src="../images/guardup.png" alt="Guard Up">
                                <span class="item-qty" id="qty-guardup">x<?php echo $guard_up; ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="center-col">
                        <div class="kp-row">KP: <span id="kp-amount">100</span></div>
                        <div class="desc-row" id="desc-row"></div>
                    </div>
                    <div class="side-col">
                        <div class="side-title">POWER-UPS (RANDOM)</div>
                        <div class="powerup-list">
                            <div class="powerup-box speak-on-hover" id="powerup-1" data-label="" onclick="selectPowerup('powerup1')"></div>
                            <div class="powerup-box speak-on-hover" id="powerup-2" data-label="" onclick="selectPowerup('powerup2')"></div>
                        </div>
                    </div>
                </div>
                <div class="answers-row">
                    <button type="button" class="answer-btn red speak-on-hover" data-label="Triangle" onclick="answerQuestion(0)">△</button>
                    <button type="button" class="answer-btn blue speak-on-hover" data-label="Circle" onclick="answerQuestion(1)">〇</button>
                    <button type="button" class="answer-btn pink speak-on-hover" data-label="Cross" onclick="answerQuestion(2)">✖</button>
                    <button type="button" class="answer-btn green speak-on-hover" data-label="Square" onclick="answerQuestion(3)">☐</button>
                </div>
            </div>
        </div>

        <script src="../quiz.ui.js"></script>
        <script src="../quiz.engine.js"></script>
        <script>
            window.questions = [
            {
                question: "How many apples are in this picture?", 
                img: '../images/NumberSkills/Quiz/fourapple.jpg',
                options: [
                    "△ - 2 (Two)",
                    "〇 - 3 (Three)",
                    "✖ - 4 (Four)", 
                    "☐ - 5 (Five)"
                ],
                correct: 2 // ✖
            },
            {
                question: "How many balloons are in this picture?", 
                img: '../images/NumberSkills/Quiz/sixballoons.png',
                options: [
                    "△ - 4 (Four)",
                    "〇 - 5 (Five)",
                    "✖ - 7 (Seven)",
                    "☐ - 6 (Six)" 
                ],
                correct: 3 // ☐
            },
            {
                question: "How many stars are in this sky?", 
                img: '../images/NumberSkills/Quiz/threestars.png',
                options: [
                    "△ - 2 (Two)", 
                    "〇 - 3 (Three)", 
                    "✖ - 4 (Four)",
                    "☐ - 5 (Five)"
                ],
                correct: 1 // 〇
            },
            {
                question: "How many bananas can you see?", 
                img: '../images/NumberSkills/Quiz/fivebananas.png',
                options: [
                    "△ - 6 (Six)", 
                    "〇 - 4 (Four)", 
                    "✖ - 5 (Five)", 
                    "☐ - 7 (Seven)"
                ],
                correct: 2 // ✖
            },
            {
                question: "How many pencils are in this picture?", 
                img: '../images/NumberSkills/Quiz/twopencils.jpg',
                options: [
                    "△ - 6 (Six)", 
                    "〇 - 2 (Two)", 
                    "✖ - 5 (Five)", 
                    "☐ - 7 (Seven)"
                ],
                correct: 1 // 〇
            },
            {
                question: "How many ducks are in this picture?", 
                img: '../images/NumberSkills/Quiz/sevenducks.jpg',
                options: [
                    "△ - 6 (Six)", 
                    "〇 - 2 (Two)", 
                    "✖ - 5 (Five)", 
                    "☐ - 7 (Seven)" 
                ],
                correct: 3 // ☐
            },
            {
                question: "How many shoes are in this picture?", 
                img: '../images/NumberSkills/Quiz/oneshoe.png',
                options: [
                    "△ - 1 (One)", 
                    "〇 - 2 (Two)", 
                    "✖ - 5 (Five)", 
                    "☐ - 7 (Seven)" 
                ],
                correct: 0 // △
            },
            {
                question: "How many books can you see?", 
                img: '../images/NumberSkills/Quiz/fivebooks.png',
                options: [
                    "△ - 1 (One)", 
                    "〇 - 2 (Two)", 
                    "✖ - 5 (Five)", 
                    "☐ - 7 (Seven)" 
                ],
                correct: 2 // ✖
            },
            {
                question: "How many fishes can you see?", 
                img: '../images/NumberSkills/Quiz/ninefishes.png',
                options: [
                    "△ - 1 (One)", 
                    "〇 - 2 (Two)", 
                    "✖ - 10 (Ten)", 
                    "☐ - 9 (Nine)" 
                ],
                correct: 3 // ☐ 
            },
            {
                question: "How many Hats are in the picture?",
                img: '../images/NumberSkills/Quiz/ninehats.png',
                options: [
                    "△ - 1 (One)", 
                    "〇 - 9 (Nine)", 
                    "✖ - 10 (Ten)", 
                    "☐ - 8 (Eight)" 
                ],
                correct: 1 // 〇
            }
            ];

            window.currentQuestion = 0;
            window.kp = 100;
            window.correctAnswers = 0;
            window.userAnswers = [];
            window.questionResults = [];

            // Expose last score to JS for display
            window.lastScore = <?php echo is_null($last_score) ? 'null' : $last_score; ?>;

            function showQuestion() {
                document.querySelectorAll('.answer-btn').forEach(btn => {
                    btn.style.background = '';
                    btn.style.border = '';
                    btn.disabled = false;
                    btn.style.pointerEvents = 'auto';
                });

                if (window.currentQuestion >= window.questions.length) {
                    // Snapshot latest/best emotion before stopping the tracker, then stop it
                    try {
                        const bestName = (typeof window.etTopEmotionName !== 'undefined' && window.etTopEmotionName) ? window.etTopEmotionName : window.etLatestEmotion;
                        const bestScore = (typeof window.etTopEmotionScore !== 'undefined' && window.etTopEmotionScore) ? window.etTopEmotionScore : window.etLatestEmotionScore;
                        window.etFinalEmotionName = bestName || null;
                        window.etFinalEmotionScore = (typeof bestScore === 'number' ? bestScore : 0);
                        if (typeof window.etStop === 'function') {
                            window.etStop();
                        }
                    } catch (e) {}
                    fetch('../update_kp.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'kp=' + encodeURIComponent(window.kp)
                    });

                    // Build result UI (change "Total Score" to "Current Score" and add "Last Score")
                    const correct = window.correctAnswers;
                    const wrong = window.questions.length - correct;
                    let note = "";
                    const percent = (correct / window.questions.length) * 100;
                    if (percent >= 90) {
                        note = "Excellent Work!";
                    } else if (percent >= 70) {
                        note = "Nice! Keep it up!";
                    } else if (percent >= 50) {
                        note = "Not bad! Review and try again!";
                    } else {
                        note = "Keep practicing! You'll get there!";
                    }

                    // Hide the quiz UI and the emotion tracker
                    document
                        .querySelectorAll('#question-box, .answers-row, .item-row, .powerup-row, .quiz-header, .quiz-flex-row, #desc-row, #emotion-tracker')
                        .forEach(el => (el.style.display = 'none'));

                    const resultBox = document.createElement('div');
                    resultBox.classList.add('result-box');
                    resultBox.style.maxWidth = '1200px';
                    resultBox.style.margin = '2rem auto';
                    resultBox.style.padding = '1.2rem';
                    resultBox.style.color = '#fff';
                    resultBox.style.borderRadius = '12px';
                    resultBox.style.boxShadow = '0 4px 12px rgba(0,0,0,0.2)';
                    resultBox.style.textAlign = 'center';

                    // Two-column layout + button row
                    resultBox.innerHTML = `
                        <div id="result-content" class="result-content">
                            <div style="display:flex; align-items:flex-start; justify-content:center;">

                                <!-- Left Box -->
                                <div class="left-box" style="flex:1; background:#2b2f3a; padding:0px;">
                                    <div class="result-heading speak-on-hover" data-label="Quiz complete" style="font-size:30px; font-weight:bold; margin-bottom:2rem; margin-top:1rem;">🎉 Quiz Complete! 🎉</div>
                                    <canvas id="scorePieChart" width="350" height=auto style="display:block; margin:0 auto 1rem;"></canvas>
                                    <div class="total-score speak-on-hover" data-label="Current score ${correct} over ${window.questions.length}" style="margin-bottom:0.5rem; font-size:1.3rem;">
                                        <strong>Current Score:</strong> ${correct}/${window.questions.length}
                                    </div>
                                    <div class="last-score speak-on-hover" data-label="Last score ${window.lastScore === null ? 'None' : window.lastScore}" style="margin-bottom:0.5rem; font-size:1.3rem;">
                                        <strong>Last Score:</strong> ${window.lastScore === null ? 'None' : window.lastScore}
                                    </div>
                                    <div class="final-kp speak-on-hover" data-label="Final KP ${window.kp}" style="margin-bottom:0.5rem; font-size:1.3rem;">
                                        <strong>Final KP:</strong> ${window.kp}
                                    </div>
                                    <br>
                                    <div class="note speak-on-hover" data-label="${note}" style="font-style:italic; color:#ddd; font-size:1.3rem;">${note}</div>
                                </div>

                                <!-- Right Box (Answer Analysis) -->
                                <div class="right-box" style="flex:1; min-width:620px; background:#2b2f3a; padding:5px; text-align:left;">
                                    <div id="analysis-wrap"></div>
                                </div>
                            </div>

                            <!-- Bottom Button Row -->
                                <button class="back-button speak-on-hover" data-label="Back to Number Lessons" style="padding:0.6rem 1.2rem; font-size:1.1rem; margin-top: 5px" onclick="saveScoreAndGoBack()">
                                    Back to Number Lessons
                                </button>
                        </div>
                    `;
                    document.body.appendChild(resultBox);

                    const resultContent = document.getElementById('result-content');

                    setTimeout(() => {
                        const pieCanvas = document.getElementById('scorePieChart');
                        if (!pieCanvas) return;
                        const ctx = pieCanvas.getContext('2d');
                        new Chart(ctx, {
                            type: 'pie',
                            data: {
                                labels: ['Correct', 'Wrong'],
                                datasets: [
                                    {
                                        data: [correct, wrong],
                                        backgroundColor: [getColor('correct'), getColor('wrong')],
                                        borderColor: '#000',
                                        borderWidth: 2
                                    }
                                ]
                            },
                            options: {
                                responsive: true,
                                plugins: {
                                    legend: { display: false },
                                    tooltip: {
                                        callbacks: {
                                            label: (ctx) => {
                                                const total = correct + wrong;
                                                const percent = ((ctx.raw / total) * 100).toFixed(0);
                                                return `${ctx.label}: ${percent}%`;
                                            }
                                        }
                                    },
                                    datalabels: {
                                        color: '#000',
                                        font: { weight: 'bold', size: 14 },
                                        formatter: (value) => {
                                            const total = correct + wrong;
                                            const percent = ((value / total) * 100).toFixed(0);
                                            return `${percent}%`;
                                        }
                                    }
                                }
                            },
                            plugins: [ChartDataLabels]
                        });
                    }, 50);

                    // Build Answer Analysis and place it into the right box
                    const analysisWrap = document.createElement('div');
                    analysisWrap.style.marginTop = '0';
                    analysisWrap.style.borderTop = '1px solid rgba(255,255,255,0.1)';
                    analysisWrap.style.background = '#292525';
                    analysisWrap.style.borderRadius = '8px';
                    analysisWrap.style.padding = '0.8rem 1rem';
                    // Static header (no toggle)
                    const analysisHeader = document.createElement('div');
                    analysisHeader.style.display = 'flex';
                    analysisHeader.style.alignItems = 'center';
                    analysisHeader.style.justifyContent = 'center';
                    analysisHeader.style.gap = '6px';
                    analysisHeader.style.fontWeight = '700';
                    analysisHeader.style.fontSize = '1.1rem';
                    analysisHeader.style.marginBottom = '0.5rem';
                    analysisHeader.textContent = 'Answer Analysis';
                    analysisWrap.appendChild(analysisHeader);

                    const analysisContent = document.createElement('div');
                    analysisContent.style.maxHeight = '420px';
                    analysisContent.style.overflowY = 'auto';
                    analysisContent.style.transition = 'all 0.3s ease';
                    analysisContent.style.display = 'block';
                    analysisContent.style.marginTop = '0.5rem';

                    function wrapTextOnly(html, color) {
                        const temp = document.createElement('div');
                        temp.innerHTML = html;
                        temp.childNodes.forEach(node => {
                            if (node.nodeType === Node.TEXT_NODE && node.textContent.trim() !== '') {
                                const span = document.createElement('span');
                                span.style.color = color;
                                span.textContent = node.textContent;
                                node.replaceWith(span);
                            }
                        });
                        return temp.innerHTML;
                    }

                    let analysisHtml = window.questionResults
                        .map((r, i) => {
                            const qnum = i + 1;
                            const qtext = window.questions[i].question;
                            const userIndex = r.userIndex;
                            const correctIndex = r.correctIndex;
                            const userSpeech = optionToTTSWithShape(r.userOptionHtml, userIndex);
                            const correctSpeech = optionToTTSWithShape(r.correctOptionHtml, correctIndex);
                            const ttsAnalysis = `Question ${qnum}. ${qtext}. Your answer. ${userSpeech}. Correct answer. ${correctSpeech}.`;
                            const correctColor = getColor('correct');
                            const userColor = (r.userIndex === r.correctIndex) ? getColor('correct') : getColor('wrong');
                            const userHtml = wrapTextOnly(r.userOptionHtml, userColor);
                            const correctHtml = wrapTextOnly(r.correctOptionHtml, correctColor);
                            return `
                                <div class="analysis-card speak-on-hover" data-label="${ttsAnalysis}"
                                    style="margin-bottom:12px; padding:14px; background:#555; border-radius:8px;
                                    box-shadow:0 1px 3px rgba(0,0,0,0.1); font-size:1.1rem;">
                                    <div style="font-weight:600; margin-bottom:8px;">Q${qnum}. ${qtext}</div>
                                    <div style="margin-bottom:6px; color:${userColor};"><strong>Your answer:</strong> ${userHtml}</div>
                                    <div style="color:${correctColor};"><strong>Correct answer:</strong> ${correctHtml}</div>
                                </div>
                            `;
                        })
                        .join('');

                    analysisContent.innerHTML = analysisHtml;
                    analysisWrap.appendChild(analysisContent);

                    // Mount analysis into the right column
                    const rightBox = resultBox.querySelector('.right-box #analysis-wrap');
                    rightBox.appendChild(analysisWrap);

                    // No toggle; analysis is always visible

                    // Animate result
                    resultContent.style.opacity = 0;
                    resultContent.style.transform = 'translateY(20px)';
                    setTimeout(() => {
                        resultContent.style.transition = 'all 0.5s ease';
                        resultContent.style.opacity = 1;
                        resultContent.style.transform = 'translateY(0)';
                        showCelebration();
                    }, 50);

                    showResultContent();
                    return;
                }

                document.getElementById('question-num').textContent = window.currentQuestion + 1;
                document.getElementById('question-total').textContent = window.questions.length;

                let qNum = window.currentQuestion + 1;
                let qText = `Question ${qNum}, ${window.questions[window.currentQuestion].question}`;
                let opts = window.questions[window.currentQuestion].options;

                function extractAnswer(opt, idx) {
                    if (/<img/i.test(opt)) return `image ${idx + 1}`;
                    const dashIdx = opt.indexOf('-');
                    return dashIdx !== -1 ? opt.substring(dashIdx + 1).trim() : opt.trim();
                }

                let optLabels = [
                    `Triangle for. ${extractAnswer(opts[0], 0)}`,
                    `Circle for. ${extractAnswer(opts[1], 1)}`,
                    `Cross for. ${extractAnswer(opts[2], 2)}`,
                    `Square for. ${extractAnswer(opts[3], 3)}`
                ];

                let ttsLabel = qText + '. ' + optLabels.join(', ') + '.';
                const optionsMarkup = quizUtils.buildOptionsMarkup(window.questions[window.currentQuestion].options);

                document.getElementById('question-box').innerHTML =
                    `<div class="tts-rectangle speak-on-hover" data-label="${ttsLabel}" tabindex="0" style="outline:none;">` +
                    `<div style="margin-bottom:36px;">${window.questions[window.currentQuestion].question}</div>` +
                    (window.questions[window.currentQuestion].img ? `<img src="${window.questions[window.currentQuestion].img}" alt="" style="max-width:225px;display:block;margin:12px auto;">` : '') +
                    optionsMarkup +
                    `</div>`;

                [1, 2].forEach(slotNum => {
                    let el = document.getElementById('powerup-' + slotNum);
                    let p = powerups['powerup' + slotNum];
                    if (el && p && !p.used) el.setAttribute('data-label', p.desc);
                    else if (el) el.setAttribute('data-label', '');
                });
            }

            // Initialize shared item/power-up logic
            initItemsAndPowerups(<?php echo $potion; ?>, <?php echo $score_up; ?>, <?php echo $guard_up; ?>);
        </script>

        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>

        <audio id="clickSound" src="../sfx/click.mp3" preload="auto"></audio>
        <audio id="closeSound" src="../sfx/close.mp3" preload="auto"></audio>
        <audio id="hoverSound" src="../sfx/hover.mp3" preload="auto"></audio>
        <audio id="wrongSound" src="../sfx/wrong.mp3" preload="auto"></audio>

        <script src="../sfx/sfx.js"></script>
        <script src="../settings.js"></script>
        <script>
            const initialVolume = <?php echo $mute === 1 ? 0 : $volume; ?>;
            const initialMute = <?php echo $mute; ?>;
            const initialTTS = <?php echo $tts_enabled; ?>;
            const initialCBEnabled = <?php echo $colorblind_enabled; ?>;
            const initialCBType = '<?php echo $colorblind_type; ?>';

            initSettings({
                volume: initialVolume,
                mute: initialMute,
                tts: initialTTS,
                colorblindEnabled: initialCBEnabled,
                colorblindType: initialCBType
            });
        </script>

        <script src="https://unpkg.com/face-api.js@0.22.2/dist/face-api.min.js"></script>
        <script src="../emotion-tracker.js"></script>

        <script>
            function saveScoreAndGoBack() {
            let formatted = '';
            try {
                    // Prefer final snapped value, then best, then latest
                    const name = (typeof window.etFinalEmotionName !== 'undefined' && window.etFinalEmotionName)
                        ? window.etFinalEmotionName
                        : (window.etTopEmotionName || window.etLatestEmotion);
                    const score = (typeof window.etFinalEmotionScore === 'number')
                        ? window.etFinalEmotionScore
                        : (window.etTopEmotionScore || window.etLatestEmotionScore);
                if (name && typeof score === 'number' && !isNaN(score)) {
                    const title = name.charAt(0).toUpperCase() + name.slice(1);
                    const pct = (score * 100).toFixed(1) + '%';
                    formatted = `${title} ${pct}`;
                }
            } catch (e) {}

            // Save with overwrite (endpoint now overwrites unconditionally)
            fetch('../save_quiz_result.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body:
                    'quiz=num_quiz1' +
                    '&score=' + encodeURIComponent(window.correctAnswers) +
                    '&emotion=' + encodeURIComponent(formatted)
            })
                .then(() => {
                    window.location.href = 'number.lessons.php';
                })
                .catch(() => {
                    window.location.href = 'number.lessons.php';
                });
            }
        </script>
</body>
</html>