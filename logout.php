<?php
// Start session and destroy session to log out user
session_start();
session_destroy();

// Redirect user to index.php after logout and exit script
header("Location: index.php");
exit();
?>