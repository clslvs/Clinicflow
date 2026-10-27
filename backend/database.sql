-- Create the database if it doesn't exist
CREATE DATABASE IF NOT EXISTS clinicflow;
USE clinicflow;

-- Create the users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    middle_name VARCHAR(50) DEFAULT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('patient', 'staff', 'admin', 'doctor') NOT NULL DEFAULT 'patient',
    sex ENUM('Male', 'Female', 'Other') DEFAULT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    is_priority BOOLEAN DEFAULT FALSE,
    allergies TEXT DEFAULT NULL,
    medical_conditions TEXT DEFAULT NULL,
    linked_doctor_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert initial default accounts (Password is 'admin123' for all)
INSERT INTO users (first_name, last_name, email, phone, password, role, allergies, medical_conditions, is_priority, linked_doctor_id) VALUES 
('System', 'Admin', 'imadmin123@gmail.com', '09123456789', '$2y$10$q6fSd4v0.BaHIscwnPFdResJTMEJxm7ePoV4GvEmUC5/gThmb6Xni', 'admin', NULL, NULL, FALSE, NULL),
('Clinic', 'Staff', 'admin@clinicflow.com', '09123456788', '$2y$10$q6fSd4v0.BaHIscwnPFdResJTMEJxm7ePoV4GvEmUC5/gThmb6Xni', 'staff', NULL, NULL, FALSE, NULL),
('Jane', 'Doe', 'patient@clinicflow.com', '09123456787', '$2y$10$q6fSd4v0.BaHIscwnPFdResJTMEJxm7ePoV4GvEmUC5/gThmb6Xni', 'patient', 'Penicillin', 'None', FALSE, NULL),
('John', 'Doe', 'doctor@clinicflow.com', '09123456786', '$2y$10$q6fSd4v0.BaHIscwnPFdResJTMEJxm7ePoV4GvEmUC5/gThmb6Xni', 'doctor', NULL, NULL, FALSE, 1)
ON DUPLICATE KEY UPDATE email=email;

-- Create doctors table
CREATE TABLE IF NOT EXISTS doctors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    specialty VARCHAR(100) NOT NULL,
    image_url VARCHAR(255)
);

-- Seed initial doctors
INSERT IGNORE INTO doctors (id, name, specialty, image_url) VALUES 
(1, 'Dr. John Doe', 'General Dentistry', 'assets/images/temporarypicdoctor.jpg');

-- Create appointments table
CREATE TABLE IF NOT EXISTS appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    service VARCHAR(100) NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NULL,
    status ENUM('pending', 'confirmed', 'in_progress', 'completed', 'cancelled', 'rescheduling') DEFAULT 'pending',
    notes TEXT DEFAULT NULL,
    doctor_notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE
);

-- Create services table
CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE
);

-- Seed initial services
INSERT IGNORE INTO services (id, name, description) VALUES 
(1, 'General Check up', 'Routine dental examination'),
(2, 'Dental Cleaning', 'Professional teeth cleaning'),
(3, 'Tooth Extraction', 'Safe removal of a tooth'),
(4, 'Root Canal', 'Treatment for infected tooth pulp'),
(5, 'Whitening', 'Cosmetic teeth whitening'),
(6, 'Consultation', 'Initial consultation with dentist');

-- Create system settings table
CREATE TABLE IF NOT EXISTS system_settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value TEXT
);

-- Seed default settings
INSERT IGNORE INTO system_settings (setting_key, setting_value) VALUES 
('clinic_name', 'ClinicFlow Dental'),
('clinic_email', 'contact@clinicflow.com'),
('clinic_phone', '0912 345 6789'),
('closed_days', '["Sunday"]'),
('open_time', '09:00'),
('close_time', '17:00');

-- Create doctor availability table
CREATE TABLE IF NOT EXISTS doctor_availability (
    id INT AUTO_INCREMENT PRIMARY KEY,
    doctor_id INT NOT NULL,
    day_of_week ENUM('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday') NOT NULL,
    start_time TIME NOT NULL DEFAULT '09:00:00',
    end_time TIME NOT NULL DEFAULT '17:00:00',
    is_available BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE,
    UNIQUE KEY unique_doctor_day (doctor_id, day_of_week)
);
