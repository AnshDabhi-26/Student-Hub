document.addEventListener("DOMContentLoaded", function () {
  let allEvents = [];
  let sortedEvents = [];
  const pageSize = 4;
  let currentPage = 1;

  const loader = document.getElementById("eventLoader");
  const controls = document.getElementById("eventControls");
  const grid = document.getElementById("eventsGrid");
  const emptyState = document.getElementById("eventEmpty");
  const count = document.getElementById("eventCount");
  const pagination = document.getElementById("eventPagination");
  const previousButton = document.getElementById("eventPrevBtn");
  const nextButton = document.getElementById("eventNextBtn");
  const pageNumbers = document.getElementById("eventPageNumbers");
  const sortSelect = document.getElementById("eventSort");

  function formatDate(dateString) {
    return new Date(dateString).toLocaleDateString("en-IN", {
      day: "2-digit",
      month: "short",
      year: "numeric",
    });
  }

  function categoryColor(category) {
    const colors = {
      Workshop: "#6366f1",
      Competition: "#ef4444",
      Networking: "#10b981",
      Seminar: "#f59e0b",
      Cultural: "#ec4899",
      Sports: "#14b8a6",
      Conference: "#8b5cf6",
    };
    return colors[category] || "#2563eb";
  }

  function sortEvents() {
    const sort = sortSelect.value;

    sortedEvents = [...allEvents];

    sortedEvents.sort(function (first, second) {
      if (sort === "date-asc")
        return new Date(first.date) - new Date(second.date);
      if (sort === "date-desc")
        return new Date(second.date) - new Date(first.date);
      if (sort === "name-asc") return first.title.localeCompare(second.title);
      if (sort === "name-desc") return second.title.localeCompare(first.title);
      return 0;
    });

    currentPage = 1;
    renderPage();
  }

  function renderPage() {
    const totalPages = Math.ceil(sortedEvents.length / pageSize);
    const start = (currentPage - 1) * pageSize;
    const pageEvents = sortedEvents.slice(start, start + pageSize);

    grid.replaceChildren();
    emptyState.style.display = "none";
    count.textContent = `${sortedEvents.length} event${sortedEvents.length === 1 ? "" : "s"} found`;
    count.style.display = "block";

    if (!sortedEvents.length) {
      emptyState.style.display = "flex";
      count.style.display = "none";
      pagination.style.display = "none";
      return;
    }

    pageEvents.forEach(function (event) {
      const card = document.createElement("article");
      const header = document.createElement("div");
      const categoryBadge = document.createElement("span");
      const statusBadge = document.createElement("span");
      const body = document.createElement("div");
      const title = document.createElement("h3");
      const description = document.createElement("p");
      const meta = document.createElement("div");
      const date = document.createElement("span");
      const venue = document.createElement("span");

      card.className = "event-card";
      header.className = "event-card-header";
      header.style.backgroundColor = `${categoryColor(event.category)}22`;
      header.style.borderLeft = `4px solid ${categoryColor(event.category)}`;
      categoryBadge.className = "event-category-badge";
      categoryBadge.style.backgroundColor = categoryColor(event.category);
      categoryBadge.textContent = event.category;
      statusBadge.className = `event-status ${event.status === "Upcoming" ? "status-upcoming" : "status-completed"}`;
      statusBadge.textContent = event.status;
      body.className = "event-card-body";
      title.className = "event-title";
      title.textContent = event.title;
      description.className = "event-desc";
      description.textContent = event.description;
      meta.className = "event-meta";
      date.className = "meta-item";
      date.textContent = `📅 ${formatDate(event.date)}`;
      venue.className = "meta-item";
      venue.textContent = `📍 ${event.venue}`;

      header.append(categoryBadge, statusBadge);
      meta.append(date, venue);
      body.append(title, description, meta);
      card.append(header, body);
      grid.appendChild(card);
    });

    if (totalPages <= 1) {
      pagination.style.display = "none";
      return;
    }

    pagination.style.display = "flex";
    pageNumbers.replaceChildren();
    for (let page = 1; page <= totalPages; page++) {
      const button = document.createElement("button");
      button.type = "button";
      button.className = `page-num-btn${page === currentPage ? " active-page" : ""}`;
      button.textContent = page;
      button.setAttribute("aria-label", `Page ${page}`);
      button.setAttribute(
        "aria-current",
        page === currentPage ? "page" : "false",
      );
      button.addEventListener("click", function () {
        currentPage = page;
        renderPage();
        grid.scrollIntoView({ behavior: "smooth", block: "start" });
      });
      pageNumbers.appendChild(button);
    }

    previousButton.disabled = currentPage === 1;
    nextButton.disabled = currentPage === totalPages;
  }

  previousButton.addEventListener("click", function () {
    if (currentPage > 1) {
      currentPage--;
      renderPage();
    }
  });

  nextButton.addEventListener("click", function () {
    const totalPages = Math.ceil(sortedEvents.length / pageSize);
    if (currentPage < totalPages) {
      currentPage++;
      renderPage();
    }
  });

  fetch("../data/events.json")
    .then(function (response) {
      if (!response.ok) throw new Error(`HTTP error ${response.status}`);
      return response.json();
    })
    .then(function (events) {
      allEvents = events;
      loader.style.display = "none";
      controls.style.display = "flex";
      sortEvents();

      sortSelect.addEventListener("change", sortEvents);
    })
    .catch(function (error) {
      console.error("Events fetch error:", error);
      loader.innerHTML =
        '<p class="error-msg">Failed to load events. Please try again later.</p>';
    });
});
