<?php
session_start();

// Security: verifying active session
if (!isset($_SESSION['first_name']) || !isset($_SESSION['last_name'])) {
    http_response_code(401);
    exit("Unauthorized");
}

// Getting JSON payload
$data = json_decode(file_get_contents('php://input'), true);

// Cleaning and applying of settings
$volume = isset($data['volume']) ? max(0, min(100, intval($data['volume']))) : 100;
$mute = isset($data['mute']) ? ($data['mute'] == 1 ? 1 : 0) : 0;
$tts_enabled = isset($data['tts_enabled']) ? ($data['tts_enabled'] == 1 ? 1 : 0) : 0;
$colorblind_enabled = isset($data['colorblind_enabled']) ? ($data['colorblind_enabled'] == 1 ? 1 : 0) : 0;

// Colorblind options (2 only)
$allowed_types = ['green', 'red'];
$colorblind_type = ($colorblind_enabled && in_array($data['colorblind_type'] ?? '', $allowed_types))
    ? $data['colorblind_type']
    : null;

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

// Update settings
$stmt = $conn->prepare("
    UPDATE users
    SET volume = ?, mute = ?, tts_enabled = ?, colorblind_enabled = ?, colorblind_type = ?
    WHERE first_name = ? AND last_name = ?
");
$stmt->bind_param(
    "iiiisss",
    $volume,
    $mute,
    $tts_enabled,
    $colorblind_enabled,
    $colorblind_type,
    $first_name,
    $last_name
);

// Response
if ($stmt->execute()) {
    http_response_code(200);
    echo json_encode([
        "status" => "success",
        "volume" => $volume,
        "mute" => $mute,
        "tts_enabled" => $tts_enabled,
        "colorblind_enabled" => $colorblind_enabled,
        "colorblind_type" => $colorblind_type
    ]);
} else {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Failed to save settings"]);
}

// Cleanup
$stmt->close();
$conn->close();
?>
