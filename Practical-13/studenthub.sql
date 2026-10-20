-- =====================================================================
-- PRACTICAL 9: Secure User Registration with Database Insert,
--              Duplicate Email Check, and Password Hashing
-- Problem Definition: Implement secure user registration by inserting user
--                     details into MySQL using MySQLi. Validate input on
--                     frontend and backend, check duplicate email/username,
--                     hash passwords using password_hash(), and display
--                     appropriate messages.
-- Advanced Extensions: Prepared Statements, CSRF Token, and Audit Logging.
-- =====================================================================

CREATE DATABASE IF NOT EXISTS studenthub
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE studenthub;

-- ---------------------------------------------------------------------
-- Drop tables in proper relational order
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS registrations;
DROP TABLE IF EXISTS events;
DROP TABLE IF EXISTS students;

-- ---------------------------------------------------------------------
-- 1. Table: students
-- Includes unique constraints for email and username, and 255-char password
-- field designed for secure password_hash() digests.
-- ---------------------------------------------------------------------
CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('student', 'admin') NOT NULL DEFAULT 'student',
    branch VARCHAR(100) NOT NULL DEFAULT 'Computer Engineering',
    course VARCHAR(100) NOT NULL DEFAULT 'Computer Science',
    semester INT NOT NULL DEFAULT 5,
    status VARCHAR(20) NOT NULL DEFAULT 'Active',
    remember_token VARCHAR(255) DEFAULT NULL,
    last_login TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 2. Table: audit_logs (Practical 9 Advanced Extension)
-- Tracks registration attempts, duplicate conflicts, and validation events
-- ---------------------------------------------------------------------
CREATE TABLE audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(50) NOT NULL DEFAULT 'USER_REGISTRATION',
    identifier VARCHAR(100) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent VARCHAR(255) DEFAULT NULL,
    status ENUM('SUCCESS', 'DUPLICATE_EMAIL', 'DUPLICATE_USERNAME', 'DUPLICATE_BOTH', 'VALIDATION_FAILED', 'ERROR') NOT NULL,
    details TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 3. Table: events
-- Includes poster upload support and validation-friendly metadata required
-- for the event management practical.
-- ---------------------------------------------------------------------
CREATE TABLE events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    category VARCHAR(50) NOT NULL DEFAULT 'Technical',
    event_date DATE NOT NULL,
    location VARCHAR(100) NOT NULL,
    capacity INT NOT NULL DEFAULT 100,
    status ENUM('Upcoming', 'Ongoing', 'Completed') NOT NULL DEFAULT 'Upcoming',
    poster VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 4. Table: registrations (Associative Table for M:N Relationship)
-- ---------------------------------------------------------------------
CREATE TABLE registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    event_id INT NOT NULL,
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('Confirmed', 'Pending', 'Cancelled') NOT NULL DEFAULT 'Confirmed',
    CONSTRAINT fk_registration_student
        FOREIGN KEY (student_id) REFERENCES students(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_registration_event
        FOREIGN KEY (event_id) REFERENCES events(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT uq_student_event
        UNIQUE (student_id, event_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- STORED PROCEDURES
-- =====================================================================
DROP PROCEDURE IF EXISTS sp_RegisterStudentForEvent;
DROP PROCEDURE IF EXISTS sp_GetStudentRegistrations;
DROP PROCEDURE IF EXISTS sp_GetEventAttendees;

DELIMITER $$

CREATE PROCEDURE sp_RegisterStudentForEvent(
    IN p_student_id INT,
    IN p_event_id INT,
    OUT p_message VARCHAR(100)
)
BEGIN
    DECLARE already_registered INT DEFAULT 0;

    SELECT COUNT(*) INTO already_registered
    FROM registrations
    WHERE student_id = p_student_id AND event_id = p_event_id;

    IF already_registered > 0 THEN
        SET p_message = 'Already registered for this event.';
    ELSE
        INSERT INTO registrations (student_id, event_id, status)
        VALUES (p_student_id, p_event_id, 'Confirmed');
        SET p_message = 'Registration successful.';
    END IF;
END$$

CREATE PROCEDURE sp_GetStudentRegistrations(
    IN p_student_id INT
)
BEGIN
    SELECT 
        r.id AS registration_id,
        s.fullname AS student_name,
        s.username,
        e.title AS event_title,
        e.category,
        e.event_date,
        e.location,
        r.registration_date,
        r.status
    FROM registrations r
    INNER JOIN students s ON r.student_id = s.id
    INNER JOIN events e ON r.event_id = e.id
    WHERE r.student_id = p_student_id
    ORDER BY e.event_date ASC;
END$$

CREATE PROCEDURE sp_GetEventAttendees(
    IN p_event_id INT
)
BEGIN
    SELECT 
        e.title AS event_title,
        s.id AS student_id,
        s.fullname,
        s.username,
        s.email,
        s.branch,
        r.registration_date,
        r.status
    FROM registrations r
    INNER JOIN students s ON r.student_id = s.id
    INNER JOIN events e ON r.event_id = e.id
    WHERE r.event_id = p_event_id
    ORDER BY s.fullname ASC;
END$$

DELIMITER ;

-- =====================================================================
-- SEED DATA
-- Passwords hashed using PHP password_hash('pass123', PASSWORD_DEFAULT)
-- Hash: $2y$10$qlNRAzzu0VscUPSysPPSveUrkozRe5TSixqp6hBGopwmXybeRKTWW
-- =====================================================================

-- 1. Insert 16 Seed Students
INSERT INTO students (id, fullname, username, email, password, role, branch, semester) VALUES
(1, 'Ansh Dabhi', 'anshdabhi', 'ansh.dabhi@charusat.edu.in', '$2y$10$qlNRAzzu0VscUPSysPPSveUrkozRe5TSixqp6hBGopwmXybeRKTWW', 'student', 'Computer Engineering', 5),
(2, 'Priya Shah', 'priyashah', 'priya.shah@charusat.edu.in', '$2y$10$qlNRAzzu0VscUPSysPPSveUrkozRe5TSixqp6hBGopwmXybeRKTWW', 'student', 'Computer Engineering', 5),
(3, 'Rahul Patel', 'rahulpatel', 'rahul.patel@charusat.edu.in', '$2y$10$qlNRAzzu0VscUPSysPPSveUrkozRe5TSixqp6hBGopwmXybeRKTWW', 'student', 'Information Technology', 5),
(4, 'Sneha Mehta', 'snehamehta', 'sneha.mehta@charusat.edu.in', '$2y$10$qlNRAzzu0VscUPSysPPSveUrkozRe5TSixqp6hBGopwmXybeRKTWW', 'student', 'Electronics & Communication', 5),
(5, 'Dev Chauhan', 'devchauhan', 'dev.chauhan@charusat.edu.in', '$2y$10$qlNRAzzu0VscUPSysPPSveUrkozRe5TSixqp6hBGopwmXybeRKTWW', 'student', 'Mechanical Engineering', 5),
(6, 'Nisha Joshi', 'nishajoshi', 'nisha.joshi@charusat.edu.in', '$2y$10$qlNRAzzu0VscUPSysPPSveUrkozRe5TSixqp6hBGopwmXybeRKTWW', 'student', 'Computer Engineering', 5),
(7, 'Arjun Trivedi', 'arjuntrivedi', 'arjun.trivedi@charusat.edu.in', '$2y$10$qlNRAzzu0VscUPSysPPSveUrkozRe5TSixqp6hBGopwmXybeRKTWW', 'student', 'Information Technology', 5),
(8, 'Kavya Desai', 'kavyadesai', 'kavya.desai@charusat.edu.in', '$2y$10$qlNRAzzu0VscUPSysPPSveUrkozRe5TSixqp6hBGopwmXybeRKTWW', 'student', 'Computer Engineering', 5),
(9, 'Rohan Sharma', 'rohansharma', 'rohan.sharma@charusat.edu.in', '$2y$10$qlNRAzzu0VscUPSysPPSveUrkozRe5TSixqp6hBGopwmXybeRKTWW', 'student', 'Electronics & Communication', 5),
(10, 'Meera Gupta', 'meeragupta', 'meera.gupta@charusat.edu.in', '$2y$10$qlNRAzzu0VscUPSysPPSveUrkozRe5TSixqp6hBGopwmXybeRKTWW', 'student', 'Mechanical Engineering', 5),
(11, 'Vikram Nair', 'vikramnair', 'vikram.nair@charusat.edu.in', '$2y$10$qlNRAzzu0VscUPSysPPSveUrkozRe5TSixqp6hBGopwmXybeRKTWW', 'student', 'Computer Engineering', 5),
(12, 'Pooja Verma', 'poojaverma', 'pooja.verma@charusat.edu.in', '$2y$10$qlNRAzzu0VscUPSysPPSveUrkozRe5TSixqp6hBGopwmXybeRKTWW', 'student', 'Information Technology', 5),
(13, 'Yash Soni', 'yashsoni', 'yash.soni@charusat.edu.in', '$2y$10$qlNRAzzu0VscUPSysPPSveUrkozRe5TSixqp6hBGopwmXybeRKTWW', 'student', 'Computer Engineering', 5),
(14, 'Riya Parekh', 'riyaparekh', 'riya.parekh@charusat.edu.in', '$2y$10$qlNRAzzu0VscUPSysPPSveUrkozRe5TSixqp6hBGopwmXybeRKTWW', 'student', 'Electronics & Communication', 5),
(15, 'Harsh Vaghela', 'harshvaghela', 'harsh.vaghela@charusat.edu.in', '$2y$10$qlNRAzzu0VscUPSysPPSveUrkozRe5TSixqp6hBGopwmXybeRKTWW', 'student', 'Mechanical Engineering', 5),
(17, 'System Administrator', 'admin', 'admin@charusat.edu.in', '$2b$10$fw7GrwF8JFRqYOPkp1skg.B63tBlgmLP8yh4CUra5YyJwY2Y3xnZG', 'admin', 'Computer Engineering', 5),
(18, 'Karan Prajapati', 'karanprajapati', 'karan.prajapati@charusat.edu.in', '$2y$10$qlNRAzzu0VscUPSysPPSveUrkozRe5TSixqp6hBGopwmXybeRKTWW', 'student', 'Civil Engineering', 5),
(19, 'Bhavya Doshi', 'bhavyadoshi', 'bhavya.doshi@charusat.edu.in', '$2y$10$qlNRAzzu0VscUPSysPPSveUrkozRe5TSixqp6hBGopwmXybeRKTWW', 'student', 'Computer Engineering', 5),
(20, 'Aaditya Rana', 'aadityarana', 'aaditya.rana@charusat.edu.in', '$2y$10$qlNRAzzu0VscUPSysPPSveUrkozRe5TSixqp6hBGopwmXybeRKTWW', 'student', 'Information Technology', 5),
(21, 'Diya Solanki', 'diyasolanki', 'diya.solanki@charusat.edu.in', '$2y$10$qlNRAzzu0VscUPSysPPSveUrkozRe5TSixqp6hBGopwmXybeRKTWW', 'student', 'Electronics & Communication', 5);

-- 2. Insert 16 Events
INSERT INTO events (id, title, description, category, event_date, location, capacity, status, poster) VALUES
(1, 'Web Development Workshop', 'Hands-on workshop on modern front-end and back-end development practices for students.', 'Workshop', '2026-10-05', 'Lab 301', 60, 'Upcoming', 'web-dev-workshop.jpg'),
(2, 'Hackathon 2026', 'A 24-hour coding challenge designed to promote problem solving, teamwork, and innovation.', 'Competition', '2026-10-12', 'Main Auditorium', 200, 'Upcoming', 'hackathon-2026.jpg'),
(3, 'Alumni Meet & Greet', 'Connect with successful graduates and industry leaders to explore career guidance and networking.', 'Networking', '2026-10-18', 'Conference Hall A', 100, 'Upcoming', 'alumni-meet.jpg'),
(4, 'Data Science Seminar', 'Learn about machine learning, analytics, and career opportunities in the data science domain.', 'Seminar', '2026-09-20', 'Seminar Room 2', 80, 'Completed', 'data-science-seminar.jpg'),
(5, 'Cultural Fest - Rangmanch', 'Annual cultural festival featuring music, dance, stage drama, and arts competitions.', 'Cultural', '2026-10-25', 'Open Air Theatre', 500, 'Upcoming', 'rangmanch.jpg'),
(6, 'Python Bootcamp', 'An intensive learning session covering Python fundamentals, libraries, and practical applications.', 'Workshop', '2026-09-15', 'Lab 102', 50, 'Completed', 'python-bootcamp.jpg'),
(7, 'Sports Day 2026', 'A university-wide sports event focusing on fitness, teamwork, and student participation.', 'Sports', '2026-11-02', 'University Sports Complex', 300, 'Upcoming', 'sports-day.jpg'),
(8, 'Placement Prep Session', 'Prepare for interviews, aptitude tests, and company-specific selection processes.', 'Career', '2026-10-08', 'Auditorium B', 150, 'Upcoming', 'placement-prep.jpg'),
(9, 'Cyber Security Hands-on Lab', 'Practical cybersecurity session covering web security, awareness, and safe coding practices.', 'Workshop', '2026-10-22', 'Security Lab 204', 45, 'Upcoming', 'cyber-security.jpg'),
(10, 'Robotics Championship', 'Build, program, and battle robots in a competitive engineering challenge.', 'Competition', '2026-11-10', 'Robotics Arena', 120, 'Upcoming', 'robotics-championship.jpg'),
(11, 'IoT Smart Device Expo', 'Showcase of innovative IoT products and prototype devices built by student teams.', 'Exhibition', '2026-11-15', 'Exhibition Hall', 250, 'Upcoming', 'iot-expo.jpg'),
(12, 'Cloud Computing Summit', 'Explore cloud platforms, architecture patterns, and deployment strategies in modern computing.', 'Seminar', '2026-10-29', 'Virtual Hall 1', 300, 'Upcoming', 'cloud-summit.jpg'),
(13, 'Competitive Coding Sprint', 'A timed coding contest focused on algorithms, problem solving, and efficiency.', 'Competition', '2026-11-05', 'Computer Center', 80, 'Upcoming', 'coding-sprint.jpg'),
(14, 'AI & Ethics Panel Discussion', 'A conversation on responsible AI, ethics, governance, and modern digital responsibilities.', 'Seminar', '2026-11-18', 'Senate Hall', 100, 'Upcoming', 'ai-ethics.jpg'),
(15, 'Startup Pitch Deck Showcase', 'Student entrepreneurs present their venture ideas and business models to investors and mentors.', 'Career', '2026-11-25', 'Incubation Center', 75, 'Upcoming', 'startup-pitch.jpg'),
(16, 'Android Appathon', 'A hands-on mobile app development challenge focused on Android UI and functionality.', 'Workshop', '2026-12-01', 'Mobile Computing Lab', 50, 'Upcoming', 'android-appathon.jpg');

-- 3. Insert 16 Registrations
INSERT INTO registrations (id, student_id, event_id, status) VALUES
(1, 1, 1, 'Confirmed'),
(2, 1, 2, 'Confirmed'),
(3, 2, 2, 'Confirmed'),
(4, 2, 8, 'Confirmed'),
(5, 3, 1, 'Confirmed'),
(6, 3, 4, 'Confirmed'),
(7, 4, 3, 'Confirmed'),
(8, 4, 11, 'Confirmed'),
(9, 5, 7, 'Confirmed'),
(10, 6, 9, 'Confirmed'),
(11, 7, 13, 'Confirmed'),
(12, 8, 1, 'Confirmed'),
(13, 9, 10, 'Confirmed'),
(14, 10, 5, 'Confirmed'),
(15, 11, 6, 'Confirmed'),
(16, 12, 12, 'Confirmed');

-- 4. Insert Seed Audit Logs (Demonstrating Registration Audits)
INSERT INTO audit_logs (id, event_type, identifier, ip_address, user_agent, status, details) VALUES
(1, 'USER_REGISTRATION', 'ansh.dabhi@charusat.edu.in', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', 'SUCCESS', 'User anshdabhi registered successfully with hashed password.'),
(2, 'USER_REGISTRATION', 'priya.shah@charusat.edu.in', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', 'SUCCESS', 'User priyashah registered successfully with hashed password.'),
(3, 'USER_REGISTRATION', 'ansh.dabhi@charusat.edu.in', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', 'DUPLICATE_EMAIL', 'Registration blocked: duplicate email attempt for ansh.dabhi@charusat.edu.in');

-- Auto-increment reset
ALTER TABLE students AUTO_INCREMENT = 22;
ALTER TABLE events AUTO_INCREMENT = 17;
ALTER TABLE registrations AUTO_INCREMENT = 17;
ALTER TABLE audit_logs AUTO_INCREMENT = 4;
