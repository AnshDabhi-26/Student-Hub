document.addEventListener("DOMContentLoaded", function () {
  const loginForm = document.getElementById("loginForm");

  if (!loginForm) return;

  loginForm.addEventListener("submit", function (event) {
    event.preventDefault();

    const studentID = document.getElementById("enrollment")?.value.trim() ?? "";
    const password = document.getElementById("password")?.value.trim() ?? "";

    if (studentID === "") {
      alert("Please enter your Student ID.");
      return;
    }

    if (password === "") {
      alert("Please enter your password.");
      return;
    }

    if (password.length < 6) {
      alert("Password must contain at least 6 characters.");
      return;
    }

    if (studentID === "25DCE017" && password === "12345678") {
      localStorage.setItem("studentID", studentID);
      alert("Login Successful!");
      window.location.href = "index.html";
      return;
    }

    alert("StudentID or password is incorrect!");
  });
});
