(function () {
  const root = document.documentElement;
  const savedTheme = localStorage.getItem("theme");
  const prefersDark = window.matchMedia("(prefers-color-scheme: dark)").matches;

  if (savedTheme === "dark" || (!savedTheme && prefersDark)) {
    root.classList.add("dark-theme");
  }
})();

document.addEventListener("DOMContentLoaded", function () {
  const root = document.documentElement;
  const body = document.body;

  const getIsDark = () => root.classList.contains("dark-theme");

  let toggleBtn = document.getElementById("themeToggle");
  if (!toggleBtn) {
    toggleBtn = document.createElement("button");
    toggleBtn.id = "themeToggle";
    toggleBtn.type = "button";
    toggleBtn.className = "theme-toggle";
    toggleBtn.setAttribute("aria-label", "Toggle dark mode");

    const navLinks = document.querySelector(".nav-links");
    const navbar = document.querySelector(".navbar");

    if (navLinks) {
      navLinks.appendChild(toggleBtn);
    } else if (navbar) {
      navbar.appendChild(toggleBtn);
    } else {
      toggleBtn.className = "theme-toggle floating";
      document.body.appendChild(toggleBtn);
    }
  }

  const updateToggle = () => {
    const dark = getIsDark();
    toggleBtn.innerHTML = dark ? "☀️" : "🌙";
    toggleBtn.title = dark ? "Switch to Light Mode" : "Switch to Dark Mode";
    toggleBtn.setAttribute(
      "aria-label",
      dark ? "Switch to light mode" : "Switch to dark mode",
    );
  };

  body.classList.toggle("dark-theme", getIsDark());
  updateToggle();

  toggleBtn.addEventListener("click", function () {
    const nowDark = !getIsDark();
    root.classList.toggle("dark-theme", nowDark);
    body.classList.toggle("dark-theme", nowDark);
    localStorage.setItem("theme", nowDark ? "dark" : "light");
    updateToggle();
  });
});
