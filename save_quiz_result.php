<?php
// Unified save for quiz score and emotion into quiz_scores and quiz_emotions
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['first_name']) || !isset($_SESSION['last_name'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

$first_name = $_SESSION['first_name'];
$last_name  = $_SESSION['last_name'];

$quiz   = isset($_POST['quiz']) ? $_POST['quiz'] : '';
$score  = isset($_POST['score']) && $_POST['score'] !== '' ? intval($_POST['score']) : null;
$rawEmotion = isset($_POST['emotion']) ? trim($_POST['emotion']) : '';

// Accept percent/percentage/confidence as optional numeric
$percentRaw = $_POST['percent'] ?? $_POST['percentage'] ?? $_POST['confidence'] ?? null;

// Whitelist all quiz columns we support
$allowedQuizzes = [
  // Life
  'life_quiz1','life_quiz2','life_quiz3',
  // Number
  'num_quiz1','num_quiz2','num_quiz3',
  // Communication
  'comm_quiz1','comm_quiz2','comm_quiz3',
  // FA: English, Math, Science
  'eng_quiz1','eng_quiz2',
  'math_quiz1','math_quiz2',
  'sci_quiz1','sci_quiz2',
];

if (!in_array($quiz, $allowedQuizzes, true)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid quiz']);
    exit;
}

if ($score === null && $rawEmotion === '' && $percentRaw === null) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Nothing to save']);
    exit;
}

// Build emotion value (compatible with existing save_*_quiz_emotion.php logic)
$allowedEmotions = ['', 'neutral', 'happy', 'sad', 'angry', 'fearful', 'disgusted', 'surprised'];
$percentStr = null;
if ($percentRaw !== null) {
    $clean = str_replace('%', '', trim((string)$percentRaw));
    if (is_numeric($clean)) {
        $p = (float)$clean;
        if ($p > 0 && $p <= 1.0) {
            $p *= 100.0;
        }
        if ($p < 0) $p = 0;
        if ($p > 100) $p = 100;
        $percentStr = rtrim(rtrim(number_format($p, 1, '.', ''), '0'), '.');
    }
}

$emotion = '';
if ($rawEmotion !== '') {
    if (preg_match('/([0-9]+(?:\.[0-9]+)?)\s*%/', $rawEmotion, $m)) {
        $p = (float)$m[1];
        if ($p < 0) $p = 0;
        if ($p > 100) $p = 100;
        $percentStr = rtrim(rtrim(number_format($p, 1, '.', ''), '0'), '.');

        $emotionOnly = preg_replace('/[0-9]+(?:\.[0-9]+)?\s*%?/', '', strtolower($rawEmotion));
        $emotionOnly = preg_replace('/[^a-z]+/', ' ', $emotionOnly);
        $emotionOnly = trim(preg_replace('/\s+/', ' ', $emotionOnly));
        $emotionToken = $emotionOnly !== '' ? explode(' ', $emotionOnly)[0] : '';
        $emotion = $emotionToken;
    } else {
        $emotion = strtolower(trim($rawEmotion));
    }
}
if (!in_array($emotion, $allowedEmotions, true)) {
    $emotion = '';
}

// Build final value for quiz_emotions column
$label = $emotion !== '' ? ucfirst($emotion) : '';
if ($label !== '' && $percentStr !== null) {
    $emotionValue = $label . ' ' . $percentStr . '%';
} elseif ($label !== '') {
    $emotionValue = $label;
} elseif ($percentStr !== null) {
    $emotionValue = $percentStr . '%';
} else {
    $emotionValue = '';
}

$conn = new mysqli("localhost", "root", "", "gible_accounts");
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'DB error']);
    exit;
}

$response = ['ok' => true, 'score' => 'skipped', 'emotion' => 'skipped'];

// Save score (only if provided)
if ($score !== null) {
    $stmt = $conn->prepare("SELECT score_id, $quiz FROM quiz_scores WHERE first_name=? AND last_name=?");
    $stmt->bind_param("ss", $first_name, $last_name);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($score_id, $existing_score);
        $stmt->fetch();
        $stmt->close();

        // Always overwrite existing score
        $stmt2 = $conn->prepare("UPDATE quiz_scores SET $quiz=? WHERE score_id=?");
        $stmt2->bind_param("ii", $score, $score_id);
        $stmt2->execute();
        $stmt2->close();
        $response['score'] = 'updated';
    } else {
        $stmt->close();
        $stmt2 = $conn->prepare("INSERT INTO quiz_scores (first_name, last_name, $quiz) VALUES (?, ?, ?)");
        $stmt2->bind_param("ssi", $first_name, $last_name, $score);
        $stmt2->execute();
        $stmt2->close();
        $response['score'] = 'inserted';
    }
}

// Save emotion (only if provided via label or percent)
if ($rawEmotion !== '' || $percentRaw !== null) {
    // If both empty after normalization, allow saving empty string (keeps behavior consistent)
    $check = $conn->prepare("SELECT 1 FROM quiz_emotions WHERE first_name = ? AND last_name = ? LIMIT 1");
    $check->bind_param("ss", $first_name, $last_name);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $sql = "UPDATE quiz_emotions SET $quiz = ? WHERE first_name = ? AND last_name = ?";
        $u = $conn->prepare($sql);
        $u->bind_param("sss", $emotionValue, $first_name, $last_name);
        $u->execute();
        $u->close();
        $response['emotion'] = 'saved';
    } else {
        $sql = "INSERT INTO quiz_emotions (first_name, last_name, $quiz) VALUES (?, ?, ?)";
        $i = $conn->prepare($sql);
        $i->bind_param("sss", $first_name, $last_name, $emotionValue);
        $i->execute();
        $i->close();
        $response['emotion'] = 'saved';
    }
    $check->close();
}

$conn->close();
echo json_encode($response);