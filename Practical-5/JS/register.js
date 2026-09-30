document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("registerForm");

  const nameInput = document.getElementById("name");
  const emailInput = document.getElementById("email");
  const mobileInput = document.getElementById("mobile");
  const passwordInput = document.getElementById("password");
  const confirmPasswordInput = document.getElementById("confirmPassword");
  const courseSelect = document.getElementById("course");
  const yearSelect = document.getElementById("year");
  const termsCheckbox = document.getElementById("terms");
  const messageElement = document.getElementById("message");

  // Regular Expression Patterns
  const regexPatterns = {
    // Name: letters and spaces only, 2-50 characters
    name: /^[A-Za-z\s]{2,50}$/,
    // Email: standard email format (user@domain.ext)
    email: /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/,
    // Mobile: 10-digit number starting with 6-9
    mobile: /^[6-9]\d{9}$/,
    // Password: at least 8 chars, 1 uppercase, 1 lowercase, 1 digit, 1 special character
    password: /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#])[A-Za-z\d@$!%*?&#]{8,}$/,
    // Course: matches one of the valid course selections
    course: /^(Computer Engineering|Information Technology|Electronics & Communication|Mechanical Engineering|Civil Engineering)$/,
    // Year: matches valid academic years
    year: /^(1st Year|2nd Year|3rd Year|4th Year)$/,
    // Gender: matches Male, Female, or Other
    gender: /^(Male|Female|Other)$/
  };

  // Helper functions to show/hide errors
  function showError(inputId, errorId, message) {
    const errorEl = document.getElementById(errorId);
    if (errorEl) {
      errorEl.textContent = message;
      errorEl.style.display = "block";
    }
    const inputEl = document.getElementById(inputId);
    if (inputEl) {
      inputEl.classList.add("invalid");
      inputEl.classList.remove("valid");
    }
  }

  function clearError(inputId, errorId) {
    const errorEl = document.getElementById(errorId);
    if (errorEl) {
      errorEl.textContent = "";
      errorEl.style.display = "none";
    }
    const inputEl = document.getElementById(inputId);
    if (inputEl) {
      inputEl.classList.remove("invalid");
      inputEl.classList.add("valid");
    }
  }

  // Field validation functions
  function validateName() {
    const value = nameInput.value.trim();
    if (value === "") {
      showError("name", "nameError", "Full Name is required.");
      return false;
    } else if (!regexPatterns.name.test(value)) {
      showError("name", "nameError", "Name must contain only letters and spaces (2-50 characters).");
      return false;
    }
    clearError("name", "nameError");
    return true;
  }

  function validateEmail() {
    const value = emailInput.value.trim();
    if (value === "") {
      showError("email", "emailError", "Email address is required.");
      return false;
    } else if (!regexPatterns.email.test(value)) {
      showError("email", "emailError", "Please enter a valid email address (e.g., student@example.com).");
      return false;
    }
    clearError("email", "emailError");
    return true;
  }

  function validateMobile() {
    const value = mobileInput.value.trim();
    if (value === "") {
      showError("mobile", "mobileError", "Mobile number is required.");
      return false;
    } else if (!regexPatterns.mobile.test(value)) {
      showError("mobile", "mobileError", "Please enter a valid 10-digit mobile number starting with 6-9.");
      return false;
    }
    clearError("mobile", "mobileError");
    return true;
  }

  function validatePassword() {
    const value = passwordInput.value;
    if (value === "") {
      showError("password", "passwordError", "Password is required.");
      return false;
    } else if (!regexPatterns.password.test(value)) {
      showError(
        "password",
        "passwordError",
        "Password must be at least 8 characters with 1 uppercase, 1 lowercase, 1 digit, & 1 special char (@$!%*?&#)."
      );
      return false;
    }
    clearError("password", "passwordError");
    return true;
  }

  function validateConfirmPassword() {
    const passwordVal = passwordInput.value;
    const confirmVal = confirmPasswordInput.value;
    if (confirmVal === "") {
      showError("confirmPassword", "confirmPasswordError", "Please confirm your password.");
      return false;
    } else if (confirmVal !== passwordVal) {
      showError("confirmPassword", "confirmPasswordError", "Passwords do not match.");
      return false;
    }
    clearError("confirmPassword", "confirmPasswordError");
    return true;
  }

  function validateCourse() {
    const value = courseSelect.value;
    if (value === "" || !regexPatterns.course.test(value)) {
      showError("course", "courseError", "Please select a valid course.");
      return false;
    }
    clearError("course", "courseError");
    return true;
  }

  function validateYear() {
    const value = yearSelect.value;
    if (value === "" || !regexPatterns.year.test(value)) {
      showError("year", "yearError", "Please select an academic year.");
      return false;
    }
    clearError("year", "yearError");
    return true;
  }

  function validateGender() {
    const selectedGender = document.querySelector('input[name="gender"]:checked');
    const genderErrorEl = document.getElementById("genderError");

    if (!selectedGender || !regexPatterns.gender.test(selectedGender.value)) {
      if (genderErrorEl) {
        genderErrorEl.textContent = "Please select your gender.";
        genderErrorEl.style.display = "block";
      }
      return false;
    }
    if (genderErrorEl) {
      genderErrorEl.textContent = "";
      genderErrorEl.style.display = "none";
    }
    return true;
  }

  function validateTerms() {
    const termsErrorEl = document.getElementById("termsError");
    if (!termsCheckbox.checked) {
      if (termsErrorEl) {
        termsErrorEl.textContent = "You must accept the Terms and Conditions to register.";
        termsErrorEl.style.display = "block";
      }
      return false;
    }
    if (termsErrorEl) {
      termsErrorEl.textContent = "";
      termsErrorEl.style.display = "none";
    }
    return true;
  }

  // Real-time event listeners
  nameInput.addEventListener("blur", validateName);
  emailInput.addEventListener("blur", validateEmail);
  mobileInput.addEventListener("blur", validateMobile);
  passwordInput.addEventListener("blur", validatePassword);
  confirmPasswordInput.addEventListener("blur", validateConfirmPassword);
  confirmPasswordInput.addEventListener("input", validateConfirmPassword);
  courseSelect.addEventListener("change", validateCourse);
  yearSelect.addEventListener("change", validateYear);
  termsCheckbox.addEventListener("change", validateTerms);

  const genderRadios = document.querySelectorAll('input[name="gender"]');
  genderRadios.forEach((radio) => radio.addEventListener("change", validateGender));

  // Form submit handler
  form.addEventListener("submit", function (event) {
    event.preventDefault();

    const isNameValid = validateName();
    const isEmailValid = validateEmail();
    const isMobileValid = validateMobile();
    const isPasswordValid = validatePassword();
    const isConfirmPasswordValid = validateConfirmPassword();
    const isCourseValid = validateCourse();
    const isYearValid = validateYear();
    const isGenderValid = validateGender();
    const isTermsValid = validateTerms();

    if (
      isNameValid &&
      isEmailValid &&
      isMobileValid &&
      isPasswordValid &&
      isConfirmPasswordValid &&
      isCourseValid &&
      isYearValid &&
      isGenderValid &&
      isTermsValid
    ) {
      const selectedGender = document.querySelector('input[name="gender"]:checked').value;

      // Save user registration details to localStorage
      localStorage.setItem("userName", nameInput.value.trim());
      localStorage.setItem("userEmail", emailInput.value.trim());
      localStorage.setItem("userMobile", mobileInput.value.trim());
      localStorage.setItem("userPassword", passwordInput.value);
      localStorage.setItem("userCourse", courseSelect.value);
      localStorage.setItem("userYear", yearSelect.value);
      localStorage.setItem("userGender", selectedGender);

      messageElement.textContent = "Registration Successful! Redirecting...";
      messageElement.className = "form-message success";
      messageElement.style.display = "block";

      setTimeout(() => {
        alert(`Registration Successful!\nWelcome, ${nameInput.value.trim()}!`);
        window.location.href = "index.html";
      }, 500);
    } else {
      messageElement.textContent = "Please fix the highlighted errors before submitting.";
      messageElement.className = "form-message error";
      messageElement.style.display = "block";
    }
  });
});

