USE clinicflow;

-- Ensure soft-delete column exists on doctors and users
ALTER TABLE doctors ADD COLUMN IF NOT EXISTS is_active BOOLEAN DEFAULT TRUE;
ALTER TABLE users ADD COLUMN IF NOT EXISTS is_deleted BOOLEAN DEFAULT FALSE;

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
('clinic_name', 'NgipinSolution Biñan'),
('clinic_email', 'contact@clinicflow.com'),
('clinic_phone', '09951467688'),
('clinic_address', '028 Santo Tomas Rd, Biñan, Laguna'),
('closed_days', '["Sunday"]'),
('open_time', '08:00'),
('close_time', '18:00');
