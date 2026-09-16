CREATE TABLE IF NOT EXISTS booking_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    workspace_id INT NOT NULL,
    slug VARCHAR(100) NOT NULL,
    title VARCHAR(150) NOT NULL,
    description TEXT NULL,
    duration_minutes INT NOT NULL DEFAULT 30,
    working_hours_start VARCHAR(10) NOT NULL DEFAULT '09:00',
    working_hours_end VARCHAR(10) NOT NULL DEFAULT '17:00',
    buffer_minutes INT NOT NULL DEFAULT 15,
    is_active INT NOT NULL DEFAULT 1,
    created_at DATETIME NULL,
    updated_at DATETIME NULL
);

CREATE INDEX IF NOT EXISTS idx_booking_slug ON booking_settings (slug);
CREATE INDEX IF NOT EXISTS idx_booking_user ON booking_settings (user_id);
