<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GIBLE - Sign Up / Log In</title>
  <link rel="stylesheet" href="style.css">
  <link rel="canonical" href="https://gible.infinityfreeapp.com/" />
  <link rel="icon" href="favicon.ico">
</head>

<body>
  <div class="container">
    <!-- Logo Section (Left Side) -->
    <div class="left">
      <img src="images/logo.png" class="logo">
    </div>

    <!-- Login/Signup Form Section (Right Side) -->
    <div class="right">
      <form action="auth.php" method="POST">

        <!-- First Name Input -->
        <input type="text" name="first_name" placeholder="Enter First Name" required maxlength="15">
        <span class="error-message" id="first_name_error"></span>

        <!-- Last Name Input -->
        <input type="text" name="last_name" placeholder="Enter Last Name" required maxlength="15">
        <span class="error-message" id="last_name_error"></span>

        <!-- Password Input with Show/Hide Toggle -->
        <div style="position: relative; display: inline-block;">
          <input type="password" id="password" name="password" placeholder="Enter Password" minlength="8" maxlength="8">
          <img src="images/eye-close.png" alt="Show Password" id="togglePassword" 
            style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer; width: 20px;">
        </div>

        <!-- Show/Hide Password JavaScript -->
        <script>
          const togglePassword = document.getElementById('togglePassword');
          const passwordInput = document.getElementById('password');

          togglePassword.addEventListener('click', () => {
            const type = passwordInput.type === 'password' ? 'text' : 'password';
            passwordInput.type = type;

            // Optional: swap icon image
            togglePassword.src = type === 'text' ? 'images/eye-open.png' : 'images/eye-close.png';
          });
        </script>

        <span class="error-message" id="password_error"></span>
        
        <!-- Signup and Login Buttons -->
        <div class="buttons">
          <button type="submit" name="signup" class="signup">Sign Up</button>
          <button type="submit" name="login" class="login">Log In</button>
        </div>

        <?php
          // --- Display session message if set ---
          session_start();
          if (isset($_SESSION['message'])) {
            echo '<div class="session-message">' . htmlspecialchars($_SESSION['message']) . '</div>';
            unset($_SESSION['message']);
          }
        ?>
        
        <!-- Forgot Password Button -->
        <div class="buttons">
          <button type="submit" name="forgot" class="forgot">Forgot Password</button>
        </div>

        <div class="support-link">
          <a href="https://www.youtube.com/watch?v=mDQrXonlx3I&list=PL6BgkmLYiP-OedrNTabG1JGdOsLKkuDq0&index=1" target="_blank" rel="noopener noreferrer">Click for tutorial</a>
        </div>

        <!-- Audio Elements for Button Click and Hover -->
        <audio id="buttonClickSound" src="sfx/click.mp3" preload="auto"></audio>
        <audio id="hoverSound" src="sfx/hover.mp3" preload="auto"></audio>

      </form>
      <!-- Script for limiting max characters in inputs -->
      <script src="maxcharacters.js"></script>

      <!-- Script for sound effects -->
      <script src="sfx/sfx.js"></script>
    </div>
  </div>

</body>
</html>