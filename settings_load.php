<?php
session_start();

// Security: verifying active session
if (!isset($_SESSION['first_name']) || !isset($_SESSION['last_name'])) {
    http_response_code(401);
    exit("Unauthorized");
}

// Database connection
$conn = new mysqli("localhost", "root", "", "gible_accounts");
if ($conn->connect_error) {
    http_response_code(500);
    exit("Database connection failed");
}

$first_name = $conn->real_escape_string($_SESSION['first_name']);
$last_name = $conn->real_escape_string($_SESSION['last_name']);

// Ensuring colorblind columns exist (runs once automatically)
if ($conn->query("SHOW COLUMNS FROM users LIKE 'colorblind_enabled'")->num_rows === 0) {
    $conn->query("ALTER TABLE users ADD COLUMN colorblind_enabled TINYINT(1) NOT NULL DEFAULT 0");
}
if ($conn->query("SHOW COLUMNS FROM users LIKE 'colorblind_type'")->num_rows === 0) {
    $conn->query("ALTER TABLE users ADD COLUMN colorblind_type ENUM('green','red') DEFAULT NULL");
}

// Fetching user settings
$stmt = $conn->prepare("
    SELECT volume, mute, tts_enabled, colorblind_enabled, colorblind_type
    FROM users
    WHERE first_name = ? AND last_name = ?
    LIMIT 1
");
$stmt->bind_param("ss", $first_name, $last_name);
$stmt->execute();
$result = $stmt->get_result();

// Apply defaults if not found or null
if ($result && $row = $result->fetch_assoc()) {
    $volume = isset($row['volume']) ? (int)$row['volume'] : 100;
    $mute = isset($row['mute']) ? (int)$row['mute'] : 0;
    $tts_enabled = isset($row['tts_enabled']) ? (int)$row['tts_enabled'] : 0;
    $colorblind_enabled = isset($row['colorblind_enabled']) ? (int)$row['colorblind_enabled'] : 0;
    $colorblind_type = $colorblind_enabled ? ($row['colorblind_type'] ?? 'green') : null;
} else {
    $volume = 100;
    $mute = 0;
    $tts_enabled = 0;
    $colorblind_enabled = 0;
    $colorblind_type = null;
}

// JSON response
http_response_code(200);
echo json_encode([
    "status" => "success",
    "volume" => $volume,
    "mute" => $mute,
    "tts_enabled" => $tts_enabled,
    "colorblind_enabled" => $colorblind_enabled,
    "colorblind_type" => $colorblind_type
]);

$stmt->close();
$conn->close();
?>
