-- =====================================================================
-- PRACTICAL 8: MySQL Schema Design, ER Model, PDO Connectivity,
--              and Prepared Statements
-- Problem Definition: Design the database for StudentHub with students,
--                     events, and registrations. Create PHP database
--                     connection using MySQL statements / PDO.
-- =====================================================================

-- ---------------------------------------------------------------------
-- ER MODEL & NORMALIZATION DOCUMENTATION
-- ---------------------------------------------------------------------
-- Entities:
--   1. STUDENTS (id, fullname, email, password, branch, semester, created_at)
--   2. EVENTS (id, title, category, event_date, location, capacity, created_at)
--   3. REGISTRATIONS (id, student_id, event_id, registration_date, status)
--
-- Relationships:
--   • STUDENTS (1) ----- (M) REGISTRATIONS
--     (One student can register for multiple events)
--   • EVENTS (1) ------- (M) REGISTRATIONS
--     (One event can have multiple student registrations)
--   • STUDENTS (M) <---> (N) EVENTS through associative table REGISTRATIONS
--
-- Normalization:
--   • 1NF: All attributes contain atomic values; each table has a primary key.
--   • 2NF: No partial dependency; all non-key attributes fully depend on PK.
--   • 3NF: No transitive dependencies; non-key attributes depend only on PK.
--   • Referential Integrity: Foreign keys with ON DELETE CASCADE and
--     UNIQUE constraint (student_id, event_id) to prevent duplicate registrations.
-- ---------------------------------------------------------------------

CREATE DATABASE IF NOT EXISTS studenthub
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE studenthub;

-- ---------------------------------------------------------------------
-- 1. Table: students
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS registrations;
DROP TABLE IF EXISTS events;
DROP TABLE IF EXISTS students;

CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    branch VARCHAR(100) NOT NULL DEFAULT 'Computer Engineering',
    semester INT NOT NULL DEFAULT 5,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 2. Table: events
-- ---------------------------------------------------------------------
CREATE TABLE events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    category VARCHAR(50) NOT NULL DEFAULT 'Technical',
    event_date DATE NOT NULL,
    location VARCHAR(100) NOT NULL,
    capacity INT NOT NULL DEFAULT 100,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 3. Table: registrations (Associative Table for M:N Relationship)
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
-- STORED PROCEDURES (Advanced Extension)
-- =====================================================================

DROP PROCEDURE IF EXISTS sp_RegisterStudentForEvent;
DROP PROCEDURE IF EXISTS sp_GetStudentRegistrations;
DROP PROCEDURE IF EXISTS sp_GetEventAttendees;

DELIMITER $$

-- Procedure 1: Register a student for an event safely
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

-- Procedure 2: Retrieve all events registered by a specific student
CREATE PROCEDURE sp_GetStudentRegistrations(
    IN p_student_id INT
)
BEGIN
    SELECT 
        r.id AS registration_id,
        s.fullname AS student_name,
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

-- Procedure 3: Retrieve all attendees for an event
CREATE PROCEDURE sp_GetEventAttendees(
    IN p_event_id INT
)
BEGIN
    SELECT 
        e.title AS event_title,
        s.id AS student_id,
        s.fullname,
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
-- SEED DATA (16 Students, 16 Events, 16 Registrations)
-- Matches StudentHub datasets (students.json & events.json)
-- =====================================================================

-- 1. Insert 16 Students
INSERT INTO students (id, fullname, email, password, branch, semester) VALUES
(1, 'Ansh Dabhi', 'ansh.dabhi@charusat.edu.in', 'pass123', 'Computer Engineering', 5),
(2, 'Priya Shah', 'priya.shah@charusat.edu.in', 'pass123', 'Computer Engineering', 5),
(3, 'Rahul Patel', 'rahul.patel@charusat.edu.in', 'pass123', 'Information Technology', 5),
(4, 'Sneha Mehta', 'sneha.mehta@charusat.edu.in', 'pass123', 'Electronics & Communication', 5),
(5, 'Dev Chauhan', 'dev.chauhan@charusat.edu.in', 'pass123', 'Mechanical Engineering', 5),
(6, 'Nisha Joshi', 'nisha.joshi@charusat.edu.in', 'pass123', 'Computer Engineering', 5),
(7, 'Arjun Trivedi', 'arjun.trivedi@charusat.edu.in', 'pass123', 'Information Technology', 5),
(8, 'Kavya Desai', 'kavya.desai@charusat.edu.in', 'pass123', 'Computer Engineering', 5),
(9, 'Rohan Sharma', 'rohan.sharma@charusat.edu.in', 'pass123', 'Electronics & Communication', 5),
(10, 'Meera Gupta', 'meera.gupta@charusat.edu.in', 'pass123', 'Mechanical Engineering', 5),
(11, 'Vikram Nair', 'vikram.nair@charusat.edu.in', 'pass123', 'Computer Engineering', 5),
(12, 'Pooja Verma', 'pooja.verma@charusat.edu.in', 'pass123', 'Information Technology', 5),
(13, 'Yash Soni', 'yash.soni@charusat.edu.in', 'pass123', 'Computer Engineering', 5),
(14, 'Riya Parekh', 'riya.parekh@charusat.edu.in', 'pass123', 'Electronics & Communication', 5),
(15, 'Harsh Vaghela', 'harsh.vaghela@charusat.edu.in', 'pass123', 'Mechanical Engineering', 5),
(16, 'Tanvi Bhatt', 'tanvi.bhatt@charusat.edu.in', 'pass123', 'Information Technology', 5);

-- 2. Insert 16 Events
INSERT INTO events (id, title, category, event_date, location, capacity) VALUES
(1, 'Web Development Workshop', 'Workshop', '2026-10-05', 'Lab 301', 60),
(2, 'Hackathon 2026', 'Competition', '2026-10-12', 'Main Auditorium', 200),
(3, 'Alumni Meet & Greet', 'Networking', '2026-10-18', 'Conference Hall A', 100),
(4, 'Data Science Seminar', 'Seminar', '2026-09-20', 'Seminar Room 2', 80),
(5, 'Cultural Fest - Rangmanch', 'Cultural', '2026-10-25', 'Open Air Theatre', 500),
(6, 'Python Bootcamp', 'Workshop', '2026-09-15', 'Lab 102', 50),
(7, 'Sports Day 2026', 'Sports', '2026-11-02', 'University Sports Complex', 300),
(8, 'Placement Prep Session', 'Career', '2026-10-08', 'Auditorium B', 150),
(9, 'Cyber Security Hands-on Lab', 'Workshop', '2026-10-22', 'Security Lab 204', 45),
(10, 'Robotics Championship', 'Competition', '2026-11-10', 'Robotics Arena', 120),
(11, 'IoT Smart Device Expo', 'Exhibition', '2026-11-15', 'Exhibition Hall', 250),
(12, 'Cloud Computing Summit', 'Seminar', '2026-10-29', 'Virtual Hall 1', 300),
(13, 'Competitive Coding Sprint', 'Competition', '2026-11-05', 'Computer Center', 80),
(14, 'AI & Ethics Panel Discussion', 'Seminar', '2026-11-18', 'Senate Hall', 100),
(15, 'Startup Pitch Deck Showcase', 'Career', '2026-11-25', 'Incubation Center', 75),
(16, 'Android Appathon', 'Workshop', '2026-12-01', 'Mobile Computing Lab', 50);

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

-- Auto-increment reset
ALTER TABLE students AUTO_INCREMENT = 17;
ALTER TABLE events AUTO_INCREMENT = 17;
ALTER TABLE registrations AUTO_INCREMENT = 17;
