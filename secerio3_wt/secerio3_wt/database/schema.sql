CREATE DATABASE IF NOT EXISTS student_portal
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE student_portal;

CREATE TABLE portal_users (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(60) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE students (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(200) NOT NULL,
    email VARCHAR(254),
    grade_level VARCHAR(50),
    profile_text TEXT
);

CREATE TABLE student_files (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    student_id BIGINT NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    stored_path VARCHAR(500) NOT NULL,
    content_type VARCHAR(100),
    uploaded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_student_files_student
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- Insert only BCrypt password hashes in portal_users.password_hash.
