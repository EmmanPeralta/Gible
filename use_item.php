<?php
// Ensure user is logged in, else return 403 error
session_start();
if (!isset($_SESSION['first_name']) || !isset($_SESSION['last_name'])) {
    http_response_code(403);
    exit('Not logged in');
}

// Check if 'item' is provided in POST request
if (!isset($_POST['item'])) {
    http_response_code(400);
    exit('No item specified');
}

$item = $_POST['item'];
$allowed = ['potion', 'score_up', 'guard_up'];
// Ensure the requested item is allowed
if (!in_array($item, $allowed)) {
    http_response_code(400);
    exit('Invalid item');
}

// Prepare user info and connect to DB
$conn = new mysqli("localhost", "root", "", "gible_accounts");
$first_name = $_SESSION['first_name'];
$last_name = $_SESSION['last_name'];

// Decrease item count by 1 if greater than 0
$conn->query(
    "UPDATE users SET $item = IF($item > 0, $item - 1, 0) WHERE first_name='$first_name' AND last_name='$last_name'"
);

// Get updated item quantity and return it
$result = $conn->query(
    "SELECT $item FROM users WHERE first_name='$first_name' AND last_name='$last_name'"
);
$row = $result->fetch_assoc();
echo $row[$item];
$conn->close();
?>