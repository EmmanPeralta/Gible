// Wait for the DOM to fully load before running script
document.addEventListener("DOMContentLoaded", function () {
  const nameInputs = document.querySelectorAll('input[name="first_name"], input[name="last_name"]');
  const passwordInput = document.querySelector('input[name="password"]');

  // Add input event listener for name fields
  nameInputs.forEach(input => {
    input.addEventListener("input", () => {

      // Allow only letters, remove non-letter characters
      const cleanValue = input.value.replace(/[^a-zA-Z]/g, '');

      // If character limit reached, show error and trim input
      if (cleanValue.length == 15) {
        document.getElementById(`${input.name}_error`).textContent = "Character limit reached";
        input.value = cleanValue.slice(0, 15);
      } else {
        // Clear error and update value
        document.getElementById(`${input.name}_error`).textContent = "";
        input.value = cleanValue;
      }
    });
  });

  // Add input event listener for password field
  passwordInput.addEventListener("input", () => {
    // Allow only letters and numbers, remove other characters
    const cleanValue = passwordInput.value.replace(/[^a-zA-Z0-9]/g, '');

    // If character limit reached, show error and trim input
    if (cleanValue.length == 8) {
      document.getElementById(`password_error`).textContent = "Character limit reached";
      passwordInput.value = cleanValue.slice(0, 8);
    } else {
      // Clear error and update value
      document.getElementById(`password_error`).textContent = "";
      passwordInput.value = cleanValue;
    }
  });
});