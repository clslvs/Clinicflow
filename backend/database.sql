-- Create the database if it doesn't exist
CREATE DATABASE IF NOT EXISTS clinicflow;
USE clinicflow;

-- Create the users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('patient', 'staff', 'admin') NOT NULL DEFAULT 'patient',
    is_active BOOLEAN DEFAULT TRUE,
    allergies TEXT DEFAULT NULL,
    medical_conditions TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert initial default accounts (Password is 'admin123' for all)
INSERT INTO users (first_name, last_name, email, phone, password, role, allergies, medical_conditions) VALUES 
('System', 'Admin', 'imadmin123@gmail.com', '09123456789', '$2y$10$q6fSd4v0.BaHIscwnPFdResJTMEJxm7ePoV4GvEmUC5/gThmb6Xni', 'admin', NULL, NULL),
('Clinic', 'Staff', 'admin@clinicflow.com', '09123456788', '$2y$10$q6fSd4v0.BaHIscwnPFdResJTMEJxm7ePoV4GvEmUC5/gThmb6Xni', 'staff', NULL, NULL),
('Jane', 'Doe', 'patient@clinicflow.com', '09123456787', '$2y$10$q6fSd4v0.BaHIscwnPFdResJTMEJxm7ePoV4GvEmUC5/gThmb6Xni', 'patient', 'Penicillin', 'None')
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
(1, 'Dr. John Doe', 'General Dentistry', 'assets/images/temporarypicdoctor.jpg'),
(2, 'Dr. Jane Smith', 'Orthodontics', 'assets/images/temporarypicdoctor.jpg'),
(3, 'Dr. Robert Lee', 'Periodontics', 'assets/images/temporarypicdoctor.jpg'),
(4, 'Dr. Emily Davis', 'Endodontics', 'assets/images/temporarypicdoctor.jpg'),
(5, 'Dr. Michael Brown', 'Oral Surgery', 'assets/images/temporarypicdoctor.jpg'),
(6, 'Dr. Sarah Wilson', 'Cosmetic Dentistry', 'assets/images/temporarypicdoctor.jpg');

-- Create appointments table
CREATE TABLE IF NOT EXISTS appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    service VARCHAR(100) NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    status ENUM('pending', 'confirmed', 'in_progress', 'completed', 'cancelled', 'rescheduling') DEFAULT 'pending',
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
