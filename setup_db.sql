USE clinicflow;

CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE
);

CREATE TABLE IF NOT EXISTS system_settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value TEXT
);

INSERT IGNORE INTO services (name, description) VALUES 
('General Check up', 'Routine dental examination'),
('Dental Cleaning', 'Professional teeth cleaning'),
('Tooth Extraction', 'Safe removal of a tooth'),
('Root Canal', 'Treatment for infected tooth pulp'),
('Whitening', 'Cosmetic teeth whitening'),
('Consultation', 'Initial consultation with dentist');

INSERT IGNORE INTO system_settings (setting_key, setting_value) VALUES 
('clinic_name', 'ClinicFlow Dental'),
('clinic_email', 'contact@clinicflow.com'),
('clinic_phone', '0912 345 6789'),
('closed_days', '["Sunday"]'),
('open_time', '09:00'),
('close_time', '17:00');
