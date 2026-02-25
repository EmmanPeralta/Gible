<?php
session_start();
header('Content-Type: application/json');

// If not logged in, return error as JSON and exit
if (!isset($_SESSION['first_name']) || !isset($_SESSION['last_name'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

// Process cart purchase if POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cart = isset($_POST['cart']) ? json_decode($_POST['cart'], true) : [];
    $totalPrice = intval($_POST['totalPrice']);

    // Connect to database
    $conn = new mysqli("localhost", "root", "", "gible_accounts");
    if ($conn->connect_error) {
        echo json_encode(['success' => false, 'error' => 'DB error']);
        exit();
    }

    $first_name = $conn->real_escape_string($_SESSION['first_name']);
    $last_name = $conn->real_escape_string($_SESSION['last_name']);

    // Get current KP and item counts
    $result = $conn->query("SELECT kp, potion, score_up, guard_up FROM users WHERE first_name='$first_name' AND last_name='$last_name'");
    $row = $result->fetch_assoc();
    $kp = $row ? intval($row['kp']) : 0;
    $potion = $row ? intval($row['potion']) : 0;
    $score_up = $row ? intval($row['score_up']) : 0;
    $guard_up = $row ? intval($row['guard_up']) : 0;

    // Check if user has enough KP and update inventory
    if ($kp >= $totalPrice) {
        // Update item counts based on cart
        $potion += isset($cart['Potion']) ? intval($cart['Potion']['quantity']) : 0;
        $score_up += isset($cart['Score Up!']) ? intval($cart['Score Up!']['quantity']) : 0;
        $guard_up += isset($cart['Guard Up!']) ? intval($cart['Guard Up!']['quantity']) : 0;

        $newKP = $kp - $totalPrice;
        $update = $conn->query("UPDATE users SET kp=$newKP, potion=$potion, score_up=$score_up, guard_up=$guard_up WHERE first_name='$first_name' AND last_name='$last_name'");
        // Return updated values as JSON
        echo json_encode([
            'success' => true,
            'kp' => $newKP,
            'potion' => $potion,
            'score_up' => $score_up,
            'guard_up' => $guard_up
        ]);
    } else {
        // Not enough KP
        echo json_encode(['success' => false, 'error' => 'Not enough KP']);
    }
    $conn->close();
}
?>