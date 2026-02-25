<?php
session_start();
if (!isset($_SESSION['first_name']) || !isset($_SESSION['last_name'])) {
    http_response_code(403);
    exit("Not logged in");
}

if (!isset($_POST['kp'])) {
    http_response_code(400);
    exit("No KP provided");
}

$kp = intval($_POST['kp']);
$first_name = $_SESSION['first_name'];
$last_name = $_SESSION['last_name'];

$conn = new mysqli("localhost", "root", "", "gible_accounts");
if ($conn->connect_error) {
    http_response_code(500);
    exit("DB error");
}

// Add KP to existing KP
$conn->query("UPDATE users SET kp = kp + $kp WHERE first_name='$first_name' AND last_name='$last_name'");
$conn->close();

echo "OK";
?>