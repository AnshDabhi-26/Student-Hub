# Practical 15: Final Integration, Testing, Documentation, GitHub Submission, and Local Deployment

## Project Title
StudentHub Portal - Final Integrated Web Application

## Objective
This project integrates all previous practical modules into a single deployable StudentHub portal. It includes:
- User registration and login
- Role-based access control for admin and student dashboards
- Dynamic menus and dashboard logic
- Event management and student management
- Audit log tracking
- Responsive front-end design
- Security and validation checks
- AJAX-based interaction
- Documentation and deployment readiness

## Modules Included
- Authentication and session management
- Database integration and CRUD operations
- Admin dashboard with statistics and audits
- Student dashboard for role-based access
- Event management and registration tracking
- Responsive UI and theme toggle
- Audit logs for registrations and logins
- Local deployment using XAMPP / Apache virtual host

## Project Structure

Practical-15/
├── CSS/
│   └── script.css
├── JS/
│   └── theme.js
├── PAGES/
│   ├── admin_dashboard.php
│   ├── auth.php
│   ├── dashboard.php
│   ├── db.php
│   ├── login.php
│   ├── logout.php
│   ├── manage_events.php
│   ├── manage_events_ajax.php
│   ├── manage_students.php
│   ├── register.php
│   ├── register_db.php
│   ├── student_dashboard.php
│   ├── test_connection.php
│   ├── view_submissions.php
│   └── index.html
├── data/
│   ├── courses.json
│   ├── events.json
│   ├── faqs.json
│   ├── notices.json
│   ├── students.json
│   └── submissions.json
├── uploads/
├── studenthub.sql
├── README.md
└── .gitignore

## Prerequisites
- XAMPP / WAMP / Apache + MySQL
- PHP 7.4 or newer
- Web browser
- GitHub account

## Local Deployment Steps
1. Start Apache and MySQL from XAMPP Control Panel.
2. Open phpMyAdmin and create the database `studenthub`.
3. Import the SQL file:
   - `Practical-15/studenthub.sql`
4. Copy the project folder into the web root:
   - Windows: `C:/xampp/htdocs/WDF/Practicals/Practical-15`
5. Open the app in browser:
   - `http://localhost/WDF/Practicals/Practical-15/PAGES/login.php`
6. If needed, update the database credentials in `PAGES/db.php`:
   - Host: `localhost`
   - Username: `root`
   - Password: `` (empty)
   - Database: `studenthub`

## Default Access
After importing the SQL data:
- Student accounts are created in the `students` table.
- The admin account is included in the seeded data.
- Login with the seeded admin/student credentials already present in the database script.

## Testing Checklist
Verify the following before final submission:
- [ ] Homepage loads without errors
- [ ] Registration works and validates input
- [ ] Duplicate email/username checks work
- [ ] Login works for student and admin users
- [ ] Admin dashboard is accessible only to admin users
- [ ] Student dashboard is accessible only to student users
- [ ] Event management pages work correctly
- [ ] Student management pages work correctly
- [ ] Theme toggle works
- [ ] Audit logs record key actions
- [ ] Database queries and insert/update/delete operations work
- [ ] Responsive layout works on desktop and mobile screens
- [ ] No broken links or missing assets

## Security & Validation Checks
- Input validation on registration and management forms
- Password hashing using `password_hash()`
- Prepared statements for database queries
- Session timeout and access restriction
- Audit logging for registration and login actions
- Role enforcement using `require_role()`

## GitHub Submission Steps
Run the following commands in the project directory:

```bash
git init
git add .
git commit -m "Final StudentHub portal integration"
git branch -M main
git remote add origin <your-github-repository-url>
git push -u origin main
```

Then submit the repository link along with:
- project description
- SQL dump path
- README link
- screenshot evidence
- demo summary

## Required Screenshots
Capture the following screenshots for submission:
1. Login page
2. Registration page
3. Student dashboard
4. Admin dashboard
5. Manage students page
6. Manage events page
7. Audit log page
8. Final responsive homepage

## Documentation Summary
This project is the final integrated StudentHub application built using:
- HTML
- CSS
- JavaScript
- PHP
- MySQL
- XAMPP
- GitHub

It demonstrates the complete end-to-end workflow of a student portal from design to deployment.

## Final Evaluation Focus
Your final practical will be evaluated on:
- working navigation and page flow
- authentication and role-based access control
- database correctness and validation
- audit logging and security practices
- UI/UX quality and responsiveness
- documentation and deployment readiness

## Conclusion
The StudentHub project is ready for local deployment, final testing, and GitHub submission as a complete full-stack web application.
