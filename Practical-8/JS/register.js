document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("registerForm");
  const message = document.getElementById("message");

  if (!form) return;

  form.addEventListener("submit", function (event) {
    event.preventDefault();

    const name = document.getElementById("name")?.value.trim() ?? "";
    const email = document.getElementById("email")?.value.trim() ?? "";
    const password = document.getElementById("password")?.value ?? "";

    if (!name || !email || !password) {
      if (message) {
        message.textContent = "Please fill in all fields.";
        message.style.color = "red";
      } else {
        alert("Please fill in all fields.");
      }
      return;
    }

    if (password.length < 6) {
      alert("Password must be at least 6 characters!");
      return;
    }

    localStorage.setItem("userName", name);
    localStorage.setItem("userEmail", email);
    localStorage.setItem("userPassword", password);

    alert("Registration Successful!\nWelcome, " + name + "!");
    window.location.href = "index.html";
  });
});
