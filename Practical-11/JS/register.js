/**
 * Frontend Validation for User Registration
 * 
 * Ensures consistency with backend PHP validation rules:
 * 1. Full Name: At least 2 characters, letters only.
 * 2. Username: 3-30 characters, alphanumeric and underscore only.
 * 3. Email: Valid email format regex matching standard syntax.
 * 4. Password: Minimum 8 characters, containing both letters and numbers.
 * 5. Confirm Password: Must match Password field.
 */

document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("registrationForm") || document.getElementById("registerForm");
  if (!form) return;

  const fullnameInput = document.getElementById("fullname") || document.getElementById("name");
  const usernameInput = document.getElementById("username");
  const emailInput = document.getElementById("email");
  const passwordInput = document.getElementById("password");
  const confirmInput = document.getElementById("confirm_password");
  
  const fullnameError = document.getElementById("fullnameError");
  const usernameError = document.getElementById("usernameError");
  const emailError = document.getElementById("emailError");
  const passwordError = document.getElementById("passwordError");
  const confirmError = document.getElementById("confirmError");

  const pwdStrengthFill = document.getElementById("pwdStrengthFill");
  const pwdStrengthText = document.getElementById("pwdStrengthText");
  const togglePasswordBtn = document.getElementById("togglePasswordBtn");
  const toggleConfirmBtn = document.getElementById("toggleConfirmBtn");

  // Regex patterns matching backend validation exactly
  const namePattern = /^[a-zA-Z\s\.\'-]{2,100}$/;
  const usernamePattern = /^[a-zA-Z0-9_]{3,30}$/;
  const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  const passwordHasLetter = /[A-Za-z]/;
  const passwordHasNumber = /[0-9]/;

  // Toggle password visibility
  if (togglePasswordBtn && passwordInput) {
    togglePasswordBtn.addEventListener("click", function () {
      const type = passwordInput.getAttribute("type") === "password" ? "text" : "password";
      passwordInput.setAttribute("type", type);
      togglePasswordBtn.textContent = type === "password" ? "👁️" : "🙈";
    });
  }

  if (toggleConfirmBtn && confirmInput) {
    toggleConfirmBtn.addEventListener("click", function () {
      const type = confirmInput.getAttribute("type") === "password" ? "text" : "password";
      confirmInput.setAttribute("type", type);
      toggleConfirmBtn.textContent = type === "password" ? "👁️" : "🙈";
    });
  }

  function setFieldState(input, errorElement, isValid, errorMsg) {
    if (!input) return isValid;
    if (isValid) {
      input.classList.remove("is-invalid");
      input.classList.add("is-valid");
      if (errorElement) errorElement.style.display = "none";
    } else {
      input.classList.remove("is-valid");
      input.classList.add("is-invalid");
      if (errorElement) {
        if (errorMsg) errorElement.textContent = errorMsg;
        errorElement.style.display = "block";
      }
    }
    return isValid;
  }

  function validateFullname() {
    if (!fullnameInput) return true;
    const val = fullnameInput.value.trim();
    if (val.length === 0) {
      return setFieldState(fullnameInput, fullnameError, false, "Full Name is required.");
    }
    if (!namePattern.test(val)) {
      return setFieldState(fullnameInput, fullnameError, false, "Full Name must contain at least 2 characters (letters only).");
    }
    return setFieldState(fullnameInput, fullnameError, true);
  }

  function validateUsername() {
    if (!usernameInput) return true;
    const val = usernameInput.value.trim();
    if (val.length === 0) {
      return setFieldState(usernameInput, usernameError, false, "Username is required.");
    }
    if (!usernamePattern.test(val)) {
      return setFieldState(usernameInput, usernameError, false, "Username must be 3-30 characters (letters, numbers, underscore only).");
    }
    return setFieldState(usernameInput, usernameError, true);
  }

  function validateEmail() {
    if (!emailInput) return true;
    const val = emailInput.value.trim();
    if (val.length === 0) {
      return setFieldState(emailInput, emailError, false, "Email Address is required.");
    }
    if (!emailPattern.test(val)) {
      return setFieldState(emailInput, emailError, false, "Please enter a valid email address (e.g., student@charusat.edu.in).");
    }
    return setFieldState(emailInput, emailError, true);
  }

  function updatePasswordStrength(val) {
    if (!pwdStrengthFill || !pwdStrengthText) return;

    if (val.length === 0) {
      pwdStrengthFill.style.width = "0%";
      pwdStrengthFill.style.backgroundColor = "#e5e7eb";
      pwdStrengthText.textContent = "Use at least 8 characters with letters & numbers";
      pwdStrengthText.style.color = "var(--muted, #6b7280)";
      return;
    }

    let score = 0;
    if (val.length >= 8) score++;
    if (/[A-Z]/.test(val) && /[a-z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;

    if (val.length < 8 || !passwordHasLetter.test(val) || !passwordHasNumber.test(val)) {
      pwdStrengthFill.style.width = "30%";
      pwdStrengthFill.style.backgroundColor = "#ef4444";
      pwdStrengthText.textContent = "Weak (minimum 8 characters with letters & numbers required)";
      pwdStrengthText.style.color = "#dc2626";
    } else if (score <= 2) {
      pwdStrengthFill.style.width = "65%";
      pwdStrengthFill.style.backgroundColor = "#f59e0b";
      pwdStrengthText.textContent = "Moderate password strength";
      pwdStrengthText.style.color = "#d97706";
    } else {
      pwdStrengthFill.style.width = "100%";
      pwdStrengthFill.style.backgroundColor = "#10b981";
      pwdStrengthText.textContent = "Strong password";
      pwdStrengthText.style.color = "#059669";
    }
  }

  function validatePassword() {
    if (!passwordInput) return true;
    const val = passwordInput.value;
    updatePasswordStrength(val);

    if (val.length === 0) {
      return setFieldState(passwordInput, passwordError, false, "Password is required.");
    }
    if (val.length < 8) {
      return setFieldState(passwordInput, passwordError, false, "Password must be at least 8 characters long.");
    }
    if (!passwordHasLetter.test(val) || !passwordHasNumber.test(val)) {
      return setFieldState(passwordInput, passwordError, false, "Password must contain both letters and at least one number.");
    }
    return setFieldState(passwordInput, passwordError, true);
  }

  function validateConfirm() {
    if (!confirmInput) return true;
    const pwdVal = passwordInput ? passwordInput.value : "";
    const confirmVal = confirmInput.value;

    if (confirmVal.length === 0) {
      return setFieldState(confirmInput, confirmError, false, "Please confirm your password.");
    }
    if (confirmVal !== pwdVal) {
      return setFieldState(confirmInput, confirmError, false, "Passwords do not match.");
    }
    return setFieldState(confirmInput, confirmError, true);
  }

  // Attach real-time input event listeners
  if (fullnameInput) fullnameInput.addEventListener("input", validateFullname);
  if (usernameInput) usernameInput.addEventListener("input", validateUsername);
  if (emailInput) emailInput.addEventListener("input", validateEmail);
  if (passwordInput) {
    passwordInput.addEventListener("input", function () {
      validatePassword();
      if (confirmInput && confirmInput.value.length > 0) validateConfirm();
    });
  }
  if (confirmInput) confirmInput.addEventListener("input", validateConfirm);

  // Form submit handler
  form.addEventListener("submit", function (e) {
    const isNameValid = validateFullname();
    const isUserValid = validateUsername();
    const isEmailValid = validateEmail();
    const isPassValid = validatePassword();
    const isConfirmValid = validateConfirm();

    if (!isNameValid || !isUserValid || !isEmailValid || !isPassValid || !isConfirmValid) {
      e.preventDefault(); // Stop form submission

      // Focus first invalid field
      if (!isNameValid && fullnameInput) fullnameInput.focus();
      else if (!isUserValid && usernameInput) usernameInput.focus();
      else if (!isEmailValid && emailInput) emailInput.focus();
      else if (!isPassValid && passwordInput) passwordInput.focus();
      else if (!isConfirmValid && confirmInput) confirmInput.focus();
      return false;
    }

    return true;
  });
});
