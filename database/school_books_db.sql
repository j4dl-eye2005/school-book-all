-- ============================================================
-- School Book Management System - Database Script
-- Import this file in phpMyAdmin (or the MySQL command line).
-- It creates the database, the tables, and some sample data.
--
-- Two roles:
--   admin -> manages books, students, and all borrow records.
--   user  -> registers an account, borrows books, returns them,
--            and sees only their own borrowed books.
-- Each "user" account is linked to a row in the students table.
-- ============================================================

-- Create the database if it does not exist, then use it.
CREATE DATABASE IF NOT EXISTS school_books_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_general_ci;

USE school_books_db;

-- ------------------------------------------------------------
-- Drop old tables first so we can re-import cleanly.
-- Order matters because of foreign keys.
-- ------------------------------------------------------------
DROP TABLE IF EXISTS borrowed_books;
DROP TABLE IF EXISTS books;
DROP TABLE IF EXISTS students;
DROP TABLE IF EXISTS users;

-- ------------------------------------------------------------
-- Table: users (login accounts for BOTH admins and users)
-- role tells us what the account can do: 'admin' or 'user'.
-- ------------------------------------------------------------
CREATE TABLE users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    username    VARCHAR(50) UNIQUE NOT NULL,
    password    VARCHAR(255) NOT NULL,          -- stores a hashed password
    role        VARCHAR(20) NOT NULL DEFAULT 'user',   -- 'admin' or 'user'
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ------------------------------------------------------------
-- Table: books
-- ------------------------------------------------------------
CREATE TABLE books (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    title        VARCHAR(255) NOT NULL,
    author       VARCHAR(150) NOT NULL,
    isbn         VARCHAR(20) UNIQUE,
    category     VARCHAR(100),
    grade_level  VARCHAR(50),
    quantity     INT DEFAULT 1,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ------------------------------------------------------------
-- Table: students
-- Each student may be linked to a login account (user_id).
-- When someone registers, we create a users row AND a students row,
-- then point students.user_id at the new users.id.
-- ------------------------------------------------------------
CREATE TABLE students (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    user_id      INT NULL,                       -- link to users.id (null for admin-added students)
    student_no   VARCHAR(30) UNIQUE NOT NULL,
    full_name    VARCHAR(150) NOT NULL,
    grade_level  VARCHAR(50),
    email        VARCHAR(150),
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- ------------------------------------------------------------
-- Table: borrowed_books
-- Links a book and a student together with dates.
-- ------------------------------------------------------------
CREATE TABLE borrowed_books (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    book_id      INT,
    student_id   INT,
    borrow_date  DATE NOT NULL,
    due_date     DATE NOT NULL,
    return_date  DATE NULL,
    status       VARCHAR(20) DEFAULT 'borrowed',
    FOREIGN KEY (book_id) REFERENCES books(id),
    FOREIGN KEY (student_id) REFERENCES students(id)
);

-- ============================================================
-- Sample data
-- ============================================================

-- Default admin account.
-- Username: admin
-- Password: admin123   (this is a bcrypt hash of "admin123")
-- IMPORTANT: change this password after your first login.
INSERT INTO users (username, password, role) VALUES
('admin', '$2a$10$V0NFYhTfsEGS3WDtZL4UIuny4IuKNuBmXC4TvzAaDRxq6s5Hnw7K2', 'admin');

-- 5 sample books
INSERT INTO books (title, author, isbn, category, grade_level, quantity) VALUES
('Introduction to Algebra',       'John Smith',      '9780011111111', 'Mathematics', 'Grade 7',  5),
('World History Basics',          'Maria Lopez',     '9780022222222', 'History',     'Grade 8',  3),
('English Grammar Made Easy',     'Anne Brown',      '9780033333333', 'English',     'Grade 6',  0),
('Science Around Us',             'David Chen',      '9780044444444', 'Science',     'Grade 7',  4),
('Basic Computer Skills',         'Sarah Johnson',   '9780055555555', 'Computer',    'Grade 9',  2);

-- 5 sample students (added by admin; not linked to a login account yet)
INSERT INTO students (student_no, full_name, grade_level, email) VALUES
('S-1001', 'Emma Reyes',   'Grade 7', 'emma@example.com'),
('S-1002', 'Liam Cruz',    'Grade 8', 'liam@example.com'),
('S-1003', 'Olivia Santos','Grade 6', 'olivia@example.com'),
('S-1004', 'Noah Garcia',  'Grade 7', 'noah@example.com'),
('S-1005', 'Ava Mendoza',  'Grade 9', 'ava@example.com');

-- 3 sample borrow records.
-- Record 1: currently borrowed and OVERDUE (due date is in the past).
-- Record 2: currently borrowed, not yet due.
-- Record 3: already returned.
INSERT INTO borrowed_books (book_id, student_id, borrow_date, due_date, return_date, status) VALUES
(1, 1, DATE_SUB(CURDATE(), INTERVAL 20 DAY), DATE_SUB(CURDATE(), INTERVAL 13 DAY), NULL, 'borrowed'),
(4, 2, DATE_SUB(CURDATE(), INTERVAL 2 DAY),  DATE_ADD(CURDATE(), INTERVAL 5 DAY),  NULL, 'borrowed'),
(2, 3, DATE_SUB(CURDATE(), INTERVAL 15 DAY), DATE_SUB(CURDATE(), INTERVAL 8 DAY),  DATE_SUB(CURDATE(), INTERVAL 9 DAY), 'returned');
