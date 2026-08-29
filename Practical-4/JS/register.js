const form = document.getElementById("registerForm");

form.addEventListener("submit", function (event) {
  event.preventDefault();

  const name = document.getElementById("name").value.trim();
  const email = document.getElementById("email").value.trim();
  const password = document.getElementById("password").value;

  if (name === "" || email === "" || password === "") {
    message.textContent = "Please fill in all fields.";
    message.style.color = "red";
    return;
  }
  // Check password length
  if (password.length < 6) {
    alert("Password must be at least 6 characters!");
    return;
  }

  // Save registration details
  localStorage.setItem("userName", name);
  localStorage.setItem("userEmail", email);
  localStorage.setItem("userPassword", password);

  // Registration successful popup
  alert("Registration Successful!\nWelcome, " + name + "!");

  // Go directly to Home page
  window.location.href = "index.html";
});
