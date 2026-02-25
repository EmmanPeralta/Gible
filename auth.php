<?php
session_start();
$conn = new mysqli("localhost", "root", "", "gible_accounts");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

function redirect_with_message($msg, $location = 'index.php') {
    $_SESSION['message'] = $msg;
    header("Location: $location");
    exit();
}

// Process signup, login, forgot password, and change password
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = isset($_POST['first_name']) ? trim($_POST['first_name']) : '';
    $last_name  = isset($_POST['last_name'])  ? trim($_POST['last_name'])  : '';
    // primary password field for signup/login
    $password   = isset($_POST['password'])   ? $_POST['password']        : '';

    // Basic password presence/length validation for signup/login (exactly 8 chars)
    if ((isset($_POST['signup']) || isset($_POST['login'])) && strlen($password) !== 8) {
        redirect_with_message('Password must be 8 characters', 'index.php');
    }

    // Handle user signup
    if (isset($_POST['signup'])) {
        // Check for existing user (by name)
        $stmt = $conn->prepare("SELECT id FROM users WHERE first_name = ? AND last_name = ? LIMIT 1");
        $stmt->bind_param('ss', $first_name, $last_name);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->close();
            redirect_with_message('Account already exists', 'index.php');
        }
        $stmt->close();

        // Hash password and insert
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, password, kp) VALUES (?, ?, ?, 200)");
        $stmt->bind_param('sss', $first_name, $last_name, $password_hash);
        if ($stmt->execute()) {
            $stmt->close();
            redirect_with_message('Account Created Successfully', 'index.php');
        } else {
            $stmt->close();
            redirect_with_message('Database error. Please try again.', 'index.php');
        }
    }

    // Handle user login
    if (isset($_POST['login'])) {
        $stmt = $conn->prepare("SELECT id, first_name, last_name, password FROM users WHERE first_name = ? AND last_name = ? LIMIT 1");
        $stmt->bind_param('ss', $first_name, $last_name);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $stored = $row['password'];

            // If stored password is a hash, use password_verify.
            // If not, allow plaintext match once and migrate to hashed password.
            $password_ok = false;
            if (password_verify($password, $stored)) {
                $password_ok = true;
                // If algorithm needs rehash, rehash now
                if (password_needs_rehash($stored, PASSWORD_DEFAULT)) {
                    $newHash = password_hash($password, PASSWORD_DEFAULT);
                    $u = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $u->bind_param('si', $newHash, $row['id']);
                    $u->execute();
                    $u->close();
                }
            } elseif ($stored === $password) {
                // plaintext match (legacy) — migrate to hash
                $password_ok = true;
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $u = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $u->bind_param('si', $newHash, $row['id']);
                $u->execute();
                $u->close();
            }

            if ($password_ok) {
                $_SESSION['first_name'] = $row['first_name'];
                $_SESSION['last_name'] = $row['last_name'];
                $stmt->close();
                header("Location: mainlobby.php");
                exit();
            } else {
                $stmt->close();
                redirect_with_message('Incorrect login. Please try again.', 'index.php');
            }
        } else {
            // no such user
            $stmt->close();
            redirect_with_message('No account found with that name', 'index.php');
        }
    }

    // Handle forgot password
    if (isset($_POST['forgot'])) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE first_name = ? AND last_name = ? LIMIT 1");
        $stmt->bind_param('ss', $first_name, $last_name);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            // generate a temporary password (8 chars) and store its hash
            $temp_password = substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789abcdefghjkmnpqrstuvwxyz3456789'), 0, 8);
            $temp_hash = password_hash($temp_password, PASSWORD_DEFAULT);

            $u = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $u->bind_param('si', $temp_hash, $row['id']);
            if ($u->execute()) {
                $u->close();
                $stmt->close();
                redirect_with_message("Temporary password: $temp_password. Please change password after login", 'index.php');
            } else {
                $u->close();
                $stmt->close();
                redirect_with_message('Error updating password', 'index.php');
            }
        } else {
            $stmt->close();
            redirect_with_message('No account found with that name', 'index.php');
        }
    }

    // Handle change password
    if (isset($_POST['change_password'])) {
        if (!isset($_SESSION['first_name']) || !isset($_SESSION['last_name'])) {
            redirect_with_message('You must be logged in to change your password', 'index.php');
        }

        $new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';
        if (strlen($new_password) !== 8) {
            redirect_with_message('Password must be 8 characters', 'changepassword.php');
        }

        $first_name = $_SESSION['first_name'];
        $last_name  = $_SESSION['last_name'];

        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE first_name = ? AND last_name = ?");
        $stmt->bind_param('sss', $new_hash, $first_name, $last_name);
        if ($stmt->execute()) {
            $stmt->close();
            redirect_with_message('Password changed successfully', 'changepassword.php');
        } else {
            $stmt->close();
            redirect_with_message('Failed to update password', 'changepassword.php');
        }
    }
}

$conn->close();
?>