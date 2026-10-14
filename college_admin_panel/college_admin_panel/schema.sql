CREATE DATABASE IF NOT EXISTS college_admin
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE college_admin;

CREATE TABLE IF NOT EXISTS students (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    roll_number VARCHAR(50) NOT NULL UNIQUE,
    course VARCHAR(120) NOT NULL,
    email VARCHAR(190) NULL,
    phone VARCHAR(30) NULL,
    date_of_birth DATE NULL,
    address VARCHAR(2000) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS student_files (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    student_id BIGINT UNSIGNED NOT NULL,
    category ENUM('profile_image', 'document') NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL UNIQUE,
    file_type VARCHAR(100) NOT NULL,
    uploaded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_student_files_student
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    INDEX idx_student_files_student_uploaded (student_id, uploaded_at)
) ENGINE=InnoDB;
