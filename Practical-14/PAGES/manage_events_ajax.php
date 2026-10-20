<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Event CRUD - AJAX / Fetch API</title>
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --bg: #f3f7ff;
            --card: #ffffff;
            --text: #1f2937;
            --muted: #6b7280;
            --border: #dbe4f0;
            --success: #16a34a;
            --danger: #dc2626;
            --warning: #f59e0b;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: "Segoe UI", Arial, sans-serif;
            background: linear-gradient(135deg, #eef5ff 0%, #f8fbff 100%);
            color: var(--text);
        }

        .container {
            max-width: 1100px;
            margin: 32px auto;
            padding: 24px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 24px;
        }

        h1 {
            margin: 0;
            font-size: 2rem;
        }

        .status {
            margin-bottom: 18px;
            padding: 12px 16px;
            border-radius: 10px;
            border: 1px solid transparent;
            display: none;
            font-weight: 600;
        }

        .status.success {
            background: #ecfdf5;
            border-color: #a7f3d0;
            color: #166534;
            display: block;
        }

        .status.error {
            background: #fef2f2;
            border-color: #fecaca;
            color: #991b1b;
            display: block;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 16px;
            box-shadow: 0 15px 30px rgba(37, 99, 235, 0.08);
            overflow: hidden;
        }

        .form-panel {
            padding: 24px;
            margin-bottom: 24px;
        }

        form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 18px;
        }

        .field {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .field.full {
            grid-column: 1 / -1;
        }

        label {
            font-weight: 600;
            color: var(--text);
        }

        input, select, textarea, button {
            font: inherit;
        }

        input, select, textarea {
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 12px 14px;
            background: #fff;
            color: var(--text);
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
        }

        textarea {
            min-height: 120px;
            resize: vertical;
        }

        .actions {
            grid-column: 1 / -1;
            display: flex;
            gap: 12px;
            margin-top: 8px;
        }

        button {
            border: 0;
            border-radius: 10px;
            padding: 12px 18px;
            font-weight: 700;
            cursor: pointer;
            transition: transform 0.15s ease, opacity 0.15s ease;
        }

        button:hover {
            transform: translateY(-1px);
        }

        .primary-btn {
            background: var(--primary);
            color: #fff;
        }

        .secondary-btn {
            background: #eef2ff;
            color: var(--primary-dark);
        }

        .table-panel {
            padding: 16px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            overflow: hidden;
            border-radius: 12px;
        }

        th, td {
            padding: 14px 12px;
            border-bottom: 1px solid var(--border);
            text-align: left;
            vertical-align: top;
        }

        thead th {
            background: #f8fbff;
            color: var(--muted);
            font-size: 0.82rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        tbody tr:hover {
            background: #f9fbff;
        }

        .badge {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 0.76rem;
            font-weight: 700;
            background: #e0f2fe;
            color: #075985;
        }

        .action-group {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .edit-btn {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .delete-btn {
            background: #fee2e2;
            color: #b91c1c;
        }

        .empty-state {
            text-align: center;
            color: var(--muted);
            padding: 24px 12px;
        }

        @media (max-width: 720px) {
            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .container {
                padding: 18px;
            }

            th, td {
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <h1>Event Management</h1>
        </div>

        <div id="statusBox" class="status" aria-live="polite"></div>

        <div class="card form-panel">
            <form id="eventForm">
                <input type="hidden" id="eventId" name="id" />

                <div class="field">
                    <label for="title">Event Title</label>
                    <input id="title" name="title" type="text" placeholder="e.g. Tech Fest 2026" required />
                </div>

                <div class="field">
                    <label for="category">Category</label>
                    <select id="category" name="category">
                        <option value="Technical">Technical</option>
                        <option value="Cultural">Cultural</option>
                        <option value="Workshop">Workshop</option>
                        <option value="Seminar">Seminar</option>
                        <option value="Sports">Sports</option>
                    </select>
                </div>

                <div class="field">
                    <label for="eventDate">Event Date</label>
                    <input id="eventDate" name="event_date" type="date" required />
                </div>

                <div class="field">
                    <label for="location">Venue</label>
                    <input id="location" name="location" type="text" placeholder="Main Auditorium" required />
                </div>

                <div class="field">
                    <label for="capacity">Capacity</label>
                    <input id="capacity" name="capacity" type="number" min="1" value="100" required />
                </div>

                <div class="field">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="Upcoming">Upcoming</option>
                        <option value="Ongoing">Ongoing</option>
                        <option value="Completed">Completed</option>
                    </select>
                </div>

                <div class="field full">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" placeholder="Describe the event details..." required></textarea>
                </div>

                <div class="actions">
                    <button type="submit" class="primary-btn">Save Event</button>
                    <button type="button" class="secondary-btn" id="resetFormBtn">Clear</button>
                </div>
            </form>
        </div>

        <div class="card table-panel">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Date</th>
                        <th>Location</th>
                        <th>Capacity</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="eventTableBody"></tbody>
            </table>
        </div>
    </div>

    <script>
        const apiUrl = 'api.php';
        const form = document.getElementById('eventForm');
        const statusBox = document.getElementById('statusBox');
        const tableBody = document.getElementById('eventTableBody');

        function showStatus(message, type) {
            statusBox.textContent = message;
            statusBox.className = 'status ' + type;
        }

        function clearForm() {
            form.reset();
            document.getElementById('eventId').value = '';
            document.getElementById('capacity').value = '100';
            document.getElementById('status').value = 'Upcoming';
            document.getElementById('category').value = 'Technical';
        }

        function formatDate(dateString) {
            if (!dateString) return 'N/A';
            const date = new Date(dateString + 'T00:00:00');
            return isNaN(date.getTime()) ? dateString : date.toLocaleDateString('en-IN', {
                day: '2-digit',
                month: 'short',
                year: 'numeric'
            });
        }

        function renderTable(events) {
            if (!events || !events.length) {
                tableBody.innerHTML = '<tr><td colspan="8" class="empty-state">No events found.</td></tr>';
                return;
            }

            tableBody.innerHTML = events.map((event) => `
                <tr>
                    <td>${event.id}</td>
                    <td>${event.title}</td>
                    <td>${event.category}</td>
                    <td>${formatDate(event.event_date)}</td>
                    <td>${event.location}</td>
                    <td>${event.capacity}</td>
                    <td><span class="badge">${event.status}</span></td>
                    <td>
                        <div class="action-group">
                            <button type="button" class="edit-btn" data-action="edit" data-id="${event.id}">Edit</button>
                            <button type="button" class="delete-btn" data-action="delete" data-id="${event.id}">Delete</button>
                        </div>
                    </td>
                </tr>
            `).join('');
        }

        async function loadEvents() {
            try {
                const response = await fetch(`${apiUrl}?action=list`);
                const result = await response.json();

                if (!result.success) {
                    throw new Error(result.message || 'Unable to load events.');
                }

                renderTable(result.data || []);
            } catch (error) {
                showStatus(error.message, 'error');
                tableBody.innerHTML = '<tr><td colspan="8" class="empty-state">Unable to load event records.</td></tr>';
            }
        }

        async function submitEvent(event) {
            event.preventDefault();

            const payload = {
                action: document.getElementById('eventId').value ? 'update' : 'add',
                id: document.getElementById('eventId').value || null,
                title: document.getElementById('title').value.trim(),
                description: document.getElementById('description').value.trim(),
                category: document.getElementById('category').value,
                event_date: document.getElementById('eventDate').value,
                location: document.getElementById('location').value.trim(),
                capacity: Number(document.getElementById('capacity').value),
                status: document.getElementById('status').value
            };

            if (!payload.title || !payload.description || !payload.event_date || !payload.location || payload.capacity <= 0) {
                showStatus('Please fill all required fields with valid values.', 'error');
                return;
            }

            try {
                const response = await fetch(apiUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });

                const result = await response.json();
                if (!result.success) {
                    throw new Error(result.message || 'Event request failed.');
                }

                showStatus(result.message, 'success');
                clearForm();
                await loadEvents();
            } catch (error) {
                showStatus(error.message, 'error');
            }
        }

        async function deleteEvent(eventId) {
            const confirmed = window.confirm('Are you sure you want to delete this event?');
            if (!confirmed) return;

            try {
                const response = await fetch(apiUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'delete', id: eventId })
                });

                const result = await response.json();
                if (!result.success) {
                    throw new Error(result.message || 'Delete failed.');
                }

                showStatus(result.message, 'success');
                await loadEvents();
            } catch (error) {
                showStatus(error.message, 'error');
            }
        }

        async function editEvent(eventId) {
            try {
                const response = await fetch(`${apiUrl}?action=view&id=${eventId}`);
                const result = await response.json();

                if (!result.success) {
                    throw new Error(result.message || 'Unable to load event data.');
                }

                const event = result.data;
                document.getElementById('eventId').value = event.id;
                document.getElementById('title').value = event.title;
                document.getElementById('description').value = event.description;
                document.getElementById('category').value = event.category;
                document.getElementById('eventDate').value = event.event_date;
                document.getElementById('location').value = event.location;
                document.getElementById('capacity').value = event.capacity;
                document.getElementById('status').value = event.status;
                window.scrollTo({ top: 0, behavior: 'smooth' });
                showStatus('Editing selected event. Update the details and save.', 'success');
            } catch (error) {
                showStatus(error.message, 'error');
            }
        }

        form.addEventListener('submit', submitEvent);
        document.getElementById('resetFormBtn').addEventListener('click', clearForm);

        tableBody.addEventListener('click', (event) => {
            const button = event.target.closest('button');
            if (!button) return;

            const action = button.dataset.action;
            const id = Number(button.dataset.id);

            if (action === 'edit') {
                editEvent(id);
            }

            if (action === 'delete') {
                deleteEvent(id);
            }
        });

        loadEvents();
    </script>
</body>
</html>
