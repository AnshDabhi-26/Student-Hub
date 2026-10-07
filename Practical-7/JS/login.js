document.addEventListener("DOMContentLoaded", function () {
  const loginForm = document.getElementById("loginForm");

  loginForm.addEventListener("submit", function (event) {
    // VERY IMPORTANT
    // Stop the form from changing the URL
    event.preventDefault();

    const studentID = document.getElementById("enrollment").value.trim();

    const password = document.getElementById("password").value.trim();

    // Check Student ID
    if (studentID === "") {
      alert("Please enter your Student ID.");

      return;
    }

    if (studentID == "25DCE017" && password == "12345678") {
      // Login button
      alert("Login Successful!");
      // Go to index.html
      window.location.href = "index.html";
    } else alert("StudentID or password is incorrect!");

    // Check password
    if (password == "") {
      alert("Please enter your password.");

      return;
    }

    // Check password length
    if (password.length < 6) {
      alert("Password must contain at least 6 characters.");

      return;
    }

    // Save Student ID
    localStorage.setItem("studentID", studentID);
  });
});
