-- ============================================================
-- HOTEL SENANG HATI - DATABASE SCHEMA
-- MySQL Database Structure for Hotel Management System
-- Updated for Web Application Integration
-- ============================================================

-- Create Database
CREATE DATABASE IF NOT EXISTS hotel_senang_hati CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hotel_senang_hati;

-- ============================================================
-- 1. ROOMS TABLE - Master data kamar hotel
-- ============================================================
CREATE TABLE rooms (
    room_id INT PRIMARY KEY AUTO_INCREMENT,
    room_number VARCHAR(10) NOT NULL UNIQUE,
    room_type ENUM('standard', 'deluxe', 'suite', 'presidential') NOT NULL,
    capacity INT NOT NULL DEFAULT 2,
    base_price DECIMAL(12,2) NOT NULL,
    status ENUM('available', 'occupied', 'maintenance', 'cleaning') DEFAULT 'available',
    floor_number INT NOT NULL,
    features JSON,
    images JSON,
    description TEXT,
    amenities TEXT,
    bed_type VARCHAR(50) DEFAULT 'Queen Bed',
    view_type VARCHAR(50) DEFAULT 'City View',
    wifi_available BOOLEAN DEFAULT TRUE,
    ac_available BOOLEAN DEFAULT TRUE,
    tv_available BOOLEAN DEFAULT TRUE,
    minibar_available BOOLEAN DEFAULT FALSE,
    balcony_available BOOLEAN DEFAULT FALSE,
    smoking_allowed BOOLEAN DEFAULT FALSE,
    pet_friendly BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_room_type (room_type),
    INDEX idx_status (status),
    INDEX idx_capacity (capacity),
    INDEX idx_price (base_price)
);

-- ============================================================
-- 2. GUESTS TABLE - Data tamu hotel
-- ============================================================
CREATE TABLE guests (
    guest_id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    phone VARCHAR(20),
    id_type ENUM('ktp', 'passport', 'sim') DEFAULT 'ktp',
    id_number VARCHAR(50),
    nationality VARCHAR(50) DEFAULT 'Indonesia',
    date_of_birth DATE,
    gender ENUM('male', 'female', 'other'),
    address TEXT,
    city VARCHAR(100),
    postal_code VARCHAR(10),
    emergency_contact_name VARCHAR(100),
    emergency_contact_phone VARCHAR(20),
    preferences JSON,
    loyalty_points INT DEFAULT 0,
    is_vip BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_email (email),
    INDEX idx_phone (phone),
    INDEX idx_id_number (id_number)
);

-- ============================================================
-- 3. RESERVATIONS TABLE - Data reservasi
-- ============================================================
CREATE TABLE reservations (
    reservation_id INT PRIMARY KEY AUTO_INCREMENT,
    reservation_code VARCHAR(20) UNIQUE NOT NULL,
    guest_id INT NOT NULL,
    room_id INT NOT NULL,
    check_in_date DATE NOT NULL,
    check_out_date DATE NOT NULL,
    check_in_time TIME DEFAULT '14:00:00',
    check_out_time TIME DEFAULT '12:00:00',
    adults INT DEFAULT 1,
    children INT DEFAULT 0,
    nights_count INT GENERATED ALWAYS AS (DATEDIFF(check_out_date, check_in_date)) STORED,
    room_rate DECIMAL(12,2) NOT NULL,
    extra_charges DECIMAL(12,2) DEFAULT 0.00,
    tax_amount DECIMAL(12,2) DEFAULT 0.00,
    discount_amount DECIMAL(12,2) DEFAULT 0.00,
    total_amount DECIMAL(12,2) NOT NULL,
    status ENUM('pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled', 'no_show') DEFAULT 'pending',
    booking_source ENUM('website', 'phone', 'email', 'walk_in', 'agent') DEFAULT 'website',
    special_requests TEXT,
    notes TEXT,
    cancellation_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    cancelled_at TIMESTAMP NULL,
    
    FOREIGN KEY (guest_id) REFERENCES guests(guest_id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(room_id) ON DELETE CASCADE,
    
    INDEX idx_reservation_code (reservation_code),
    INDEX idx_check_in_date (check_in_date),
    INDEX idx_check_out_date (check_out_date),
    INDEX idx_status (status),
    INDEX idx_guest_id (guest_id),
    INDEX idx_room_id (room_id)
);

-- ============================================================
-- 4. PAYMENTS TABLE - Data pembayaran
-- ============================================================
CREATE TABLE payments (
    payment_id INT PRIMARY KEY AUTO_INCREMENT,
    reservation_id INT NOT NULL,
    payment_code VARCHAR(20) UNIQUE NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    payment_method ENUM('cash', 'credit_card', 'debit_card', 'bank_transfer', 'ewallet', 'qris') NOT NULL,
    payment_type ENUM('deposit', 'full_payment', 'additional_charge', 'refund') DEFAULT 'full_payment',
    currency VARCHAR(3) DEFAULT 'IDR',
    exchange_rate DECIMAL(10,4) DEFAULT 1.0000,
    transaction_id VARCHAR(100),
    reference_number VARCHAR(100),
    payment_gateway VARCHAR(50),
    gateway_response JSON,
    status ENUM('pending', 'processing', 'completed', 'failed', 'cancelled', 'refunded') DEFAULT 'pending',
    paid_at TIMESTAMP NULL,
    refunded_at TIMESTAMP NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (reservation_id) REFERENCES reservations(reservation_id) ON DELETE CASCADE,
    
    INDEX idx_payment_code (payment_code),
    INDEX idx_status (status),
    INDEX idx_payment_method (payment_method),
    INDEX idx_reservation_id (reservation_id)
);

-- ============================================================
-- 5. STAFF TABLE - Data staff hotel
-- ============================================================
CREATE TABLE staff (
    staff_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    phone VARCHAR(20),
    role ENUM('admin', 'manager', 'receptionist', 'housekeeping', 'maintenance', 'accounting') NOT NULL,
    department VARCHAR(50),
    hire_date DATE,
    salary DECIMAL(12,2),
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    login_attempts INT DEFAULT 0,
    permissions JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_is_active (is_active)
);

-- ============================================================
-- 6. ROOM_MAINTENANCE TABLE - Log maintenance kamar
-- ============================================================
CREATE TABLE room_maintenance (
    maintenance_id INT PRIMARY KEY AUTO_INCREMENT,
    room_id INT NOT NULL,
    staff_id INT,
    maintenance_type ENUM('cleaning', 'repair', 'inspection', 'upgrade') NOT NULL,
    description TEXT NOT NULL,
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    status ENUM('scheduled', 'in_progress', 'completed', 'cancelled') DEFAULT 'scheduled',
    scheduled_date DATE NOT NULL,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    estimated_duration INT COMMENT 'Duration in minutes',
    actual_duration INT COMMENT 'Actual duration in minutes',
    cost DECIMAL(10,2) DEFAULT 0.00,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (room_id) REFERENCES rooms(room_id) ON DELETE CASCADE,
    FOREIGN KEY (staff_id) REFERENCES staff(staff_id) ON DELETE SET NULL,
    
    INDEX idx_room_id (room_id),
    INDEX idx_status (status),
    INDEX idx_scheduled_date (scheduled_date)
);

-- ============================================================
-- 7. GUEST_STAYS TABLE - History menginap tamu
-- ============================================================
CREATE TABLE guest_stays (
    stay_id INT PRIMARY KEY AUTO_INCREMENT,
    reservation_id INT NOT NULL,
    guest_id INT NOT NULL,
    room_id INT NOT NULL,
    actual_check_in TIMESTAMP,
    actual_check_out TIMESTAMP,
    extended_checkout BOOLEAN DEFAULT FALSE,
    early_checkout BOOLEAN DEFAULT FALSE,
    guest_rating INT CHECK (guest_rating >= 1 AND guest_rating <= 5),
    guest_review TEXT,
    staff_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (reservation_id) REFERENCES reservations(reservation_id) ON DELETE CASCADE,
    FOREIGN KEY (guest_id) REFERENCES guests(guest_id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(room_id) ON DELETE CASCADE,
    
    INDEX idx_guest_id (guest_id),
    INDEX idx_check_in_date (actual_check_in),
    INDEX idx_guest_rating (guest_rating)
);

-- ============================================================
-- 8. HOTEL_SETTINGS TABLE - Pengaturan hotel
-- ============================================================
CREATE TABLE hotel_settings (
    setting_id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    description TEXT,
    category VARCHAR(50) DEFAULT 'general',
    is_editable BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_setting_key (setting_key),
    INDEX idx_category (category)
);

-- ============================================================
-- INSERT DEFAULT DATA
-- ============================================================

-- Insert default hotel settings
INSERT INTO hotel_settings (setting_key, setting_value, setting_type, description, category) VALUES
('hotel_name', 'Hotel Senang Hati', 'string', 'Nama Hotel', 'general'),
('check_in_time', '14:00', 'string', 'Waktu Check-in Standard', 'operations'),
('check_out_time', '12:00', 'string', 'Waktu Check-out Standard', 'operations'),
('currency', 'IDR', 'string', 'Mata Uang Hotel', 'financial'),
('tax_rate', '10.00', 'number', 'Persentase Pajak (%)', 'financial'),
('service_charge', '5.00', 'number', 'Persentase Service Charge (%)', 'financial'),
('cancellation_hours', '24', 'number', 'Batas Waktu Pembatalan (jam)', 'policy'),
('max_advance_booking', '365', 'number', 'Maksimal Booking di Muka (hari)', 'policy'),
('loyalty_points_rate', '1.00', 'number', 'Poin per 1000 IDR', 'loyalty'),
('max_occupancy_override', 'true', 'boolean', 'Izinkan Override Kapasitas', 'operations');

-- Insert sample rooms with updated data matching our frontend
INSERT INTO rooms (room_number, room_type, capacity, base_price, floor_number, features, images, description, amenities, bed_type, view_type, minibar_available, balcony_available) VALUES
-- Standard Rooms
('101', 'standard', 2, 500000, 1, 
 '["AC", "TV", "WiFi", "Kamar Mandi Dalam"]', 
 '["Standar-room.png"]',
 'Kamar nyaman dengan fasilitas standar untuk 2 orang. Dilengkapi dengan AC, TV LCD, WiFi gratis, dan kamar mandi dalam yang bersih.', 
 'AC, TV LCD 32", WiFi gratis, Kamar mandi dalam, Handuk, Toiletries, Meja kerja, Lemari pakaian', 
 'Queen Bed', 'Garden View', FALSE, FALSE),

('102', 'standard', 2, 500000, 1, 
 '["AC", "TV", "WiFi", "Kamar Mandi Dalam"]', 
 '["Standar-room.png"]',
 'Kamar nyaman dengan fasilitas standar untuk 2 orang. Dilengkapi dengan AC, TV LCD, WiFi gratis, dan kamar mandi dalam yang bersih.', 
 'AC, TV LCD 32", WiFi gratis, Kamar mandi dalam, Handuk, Toiletries, Meja kerja, Lemari pakaian', 
 'Queen Bed', 'Garden View', FALSE, FALSE),

('103', 'standard', 2, 500000, 1, 
 '["AC", "TV", "WiFi", "Kamar Mandi Dalam"]', 
 '["Standar-room.png"]',
 'Kamar nyaman dengan fasilitas standar untuk 2 orang. Dilengkapi dengan AC, TV LCD, WiFi gratis, dan kamar mandi dalam yang bersih.', 
 'AC, TV LCD 32", WiFi gratis, Kamar mandi dalam, Handuk, Toiletries, Meja kerja, Lemari pakaian', 
 'Queen Bed', 'City View', FALSE, FALSE),

-- Deluxe Rooms (Business Rooms)
('201', 'deluxe', 3, 750000, 2, 
 '["AC", "TV LCD", "WiFi", "Mini Bar", "Balkon", "Area Kerja"]', 
 '["Business-room.png"]',
 'Kamar business yang luas dengan area kerja khusus, balkon pribadi dan mini bar untuk 3 orang. Cocok untuk perjalanan bisnis.', 
 'AC, TV LCD 42", WiFi premium, Mini bar, Balkon, Area kerja luas, Kamar mandi dengan bathtub, Brankas, Coffee maker', 
 'King Bed', 'City View', TRUE, TRUE),

('202', 'deluxe', 3, 750000, 2, 
 '["AC", "TV LCD", "WiFi", "Mini Bar", "Balkon", "Area Kerja"]', 
 '["Business-room.png"]',
 'Kamar business yang luas dengan area kerja khusus, balkon pribadi dan mini bar untuk 3 orang. Cocok untuk perjalanan bisnis.', 
 'AC, TV LCD 42", WiFi premium, Mini bar, Balkon, Area kerja luas, Kamar mandi dengan bathtub, Brankas, Coffee maker', 
 'King Bed', 'Garden View', TRUE, TRUE),

('203', 'deluxe', 3, 750000, 2, 
 '["AC", "TV LCD", "WiFi", "Mini Bar", "Balkon", "Area Kerja"]', 
 '["Business-room.png"]',
 'Kamar business yang luas dengan area kerja khusus, balkon pribadi dan mini bar untuk 3 orang. Cocok untuk perjalanan bisnis.', 
 'AC, TV LCD 42", WiFi premium, Mini bar, Balkon, Area kerja luas, Kamar mandi dengan bathtub, Brankas, Coffee maker', 
 'King Bed', 'Pool View', TRUE, TRUE),

-- Executive Suite Rooms
('301', 'suite', 4, 1200000, 3, 
 '["AC", "Smart TV", "WiFi", "Mini Bar", "Jacuzzi", "Living Room", "Ruang Makan"]', 
 '["Executive-room.png"]',
 'Executive suite mewah dengan ruang tamu terpisah, ruang makan, dan jacuzzi premium untuk 4 orang. Sempurna untuk keluarga atau grup kecil.', 
 'AC, Smart TV 55", WiFi ultra-cepat, Mini bar premium, Jacuzzi, Ruang tamu, Ruang makan, Kamar mandi premium, Butler service', 
 'King Bed + Sofa Bed', 'City View', TRUE, TRUE),

('302', 'suite', 4, 1200000, 3, 
 '["AC", "Smart TV", "WiFi", "Mini Bar", "Jacuzzi", "Living Room", "Ruang Makan"]', 
 '["Executive-room.png"]',
 'Executive suite mewah dengan ruang tamu terpisah, ruang makan, dan jacuzzi premium untuk 4 orang. Sempurna untuk keluarga atau grup kecil.', 
 'AC, Smart TV 55", WiFi ultra-cepat, Mini bar premium, Jacuzzi, Ruang tamu, Ruang makan, Kamar mandi premium, Butler service', 
 'King Bed + Sofa Bed', 'Garden View', TRUE, TRUE),

-- Presidential Suite
('401', 'presidential', 6, 2500000, 4, 
 '["AC", "Smart TV", "WiFi", "Mini Bar", "Jacuzzi", "Living Room", "Kitchen", "Butler Service", "Private Dining"]', 
 '["Pressidential-room.png"]',
 'Presidential suite eksklusif dengan kitchen lengkap, private dining, dan butler service 24 jam untuk 6 orang. Pengalaman menginap yang tak terlupakan.', 
 'AC multi-zone, Smart TV 65", WiFi dedicated, Mini bar premium, Jacuzzi king-size, Ruang tamu mewah, Kitchen lengkap, Private dining, Butler service 24 jam, Concierge pribadi', 
 'King Bed + 2 Single Beds', 'Panoramic View', TRUE, TRUE);

-- Insert default admin staff
-- Password untuk semua akun demo: "password123"
INSERT INTO staff (username, password_hash, first_name, last_name, email, role, department, hire_date, permissions) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Hotel', 'Administrator', 'admin@hotelsenanghati.com', 'admin', 'Management', CURDATE(), '{"all": true}'),
('demo', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Demo', 'Admin', 'demo@hotelsenanghati.com', 'admin', 'Management', CURDATE(), '{"all": true}'),
('receptionist1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sari', 'Dewi', 'receptionist@hotelsenanghati.com', 'receptionist', 'Front Office', CURDATE(), '{"reservations": true, "guests": true, "rooms": true}'),
('manager1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Budi', 'Santoso', 'manager@hotelsenanghati.com', 'manager', 'Management', CURDATE(), '{"reports": true, "staff": true, "settings": true}');

-- ============================================================
-- CREATE VIEWS FOR REPORTING
-- ============================================================

-- View untuk occupancy rate
CREATE VIEW room_occupancy AS
SELECT 
    r.room_type,
    COUNT(r.room_id) as total_rooms,
    COUNT(CASE WHEN r.status = 'occupied' THEN 1 END) as occupied_rooms,
    ROUND((COUNT(CASE WHEN r.status = 'occupied' THEN 1 END) / COUNT(r.room_id)) * 100, 2) as occupancy_rate
FROM rooms r
GROUP BY r.room_type;

-- View untuk revenue report
CREATE VIEW daily_revenue AS
SELECT 
    DATE(p.paid_at) as revenue_date,
    COUNT(DISTINCT p.reservation_id) as bookings_count,
    SUM(p.amount) as total_revenue,
    AVG(p.amount) as average_booking_value
FROM payments p 
WHERE p.status = 'completed' AND p.payment_type IN ('full_payment', 'deposit')
GROUP BY DATE(p.paid_at)
ORDER BY revenue_date DESC;

-- View untuk guest statistics
CREATE VIEW guest_statistics AS
SELECT 
    g.guest_id,
    CONCAT(g.first_name, ' ', g.last_name) as guest_name,
    g.email,
    COUNT(r.reservation_id) as total_bookings,
    SUM(r.total_amount) as total_spent,
    MAX(r.check_out_date) as last_visit,
    g.loyalty_points
FROM guests g
LEFT JOIN reservations r ON g.guest_id = r.guest_id
WHERE r.status IN ('completed', 'checked_out')
GROUP BY g.guest_id
ORDER BY total_spent DESC;

-- ============================================================
-- CREATE STORED PROCEDURES
-- ============================================================

DELIMITER //

-- Procedure untuk check room availability
CREATE PROCEDURE CheckRoomAvailability(
    IN p_check_in DATE,
    IN p_check_out DATE,
    IN p_room_type VARCHAR(20)
)
BEGIN
    SELECT r.*
    FROM rooms r
    WHERE r.room_type = p_room_type
    AND r.status = 'available'
    AND r.room_id NOT IN (
        SELECT res.room_id
        FROM reservations res
        WHERE res.status IN ('confirmed', 'checked_in')
        AND (
            (res.check_in_date <= p_check_in AND res.check_out_date > p_check_in)
            OR (res.check_in_date < p_check_out AND res.check_out_date >= p_check_out)
            OR (res.check_in_date >= p_check_in AND res.check_out_date <= p_check_out)
        )
    );
END //

-- Procedure untuk generate reservation code
CREATE PROCEDURE GenerateReservationCode(
    OUT p_reservation_code VARCHAR(20)
)
BEGIN
    DECLARE code_exists INT DEFAULT 1;
    DECLARE temp_code VARCHAR(20);
    
    WHILE code_exists > 0 DO
        SET temp_code = CONCAT('HSH', DATE_FORMAT(NOW(), '%y%m%d'), LPAD(FLOOR(RAND() * 9999), 4, '0'));
        SELECT COUNT(*) INTO code_exists FROM reservations WHERE reservation_code = temp_code;
    END WHILE;
    
    SET p_reservation_code = temp_code;
END //

DELIMITER ;

-- ============================================================
-- CREATE INDEXES FOR PERFORMANCE
-- ============================================================

-- Additional indexes for better performance
CREATE INDEX idx_reservations_dates ON reservations(check_in_date, check_out_date);
CREATE INDEX idx_payments_date ON payments(paid_at);
CREATE INDEX idx_room_maintenance_date ON room_maintenance(scheduled_date, status);

-- ============================================================
-- TRIGGERS
-- ============================================================

DELIMITER //

-- Trigger untuk update room status saat check-in
CREATE TRIGGER update_room_status_checkin
AFTER UPDATE ON reservations
FOR EACH ROW
BEGIN
    IF NEW.status = 'checked_in' AND OLD.status != 'checked_in' THEN
        UPDATE rooms SET status = 'occupied' WHERE room_id = NEW.room_id;
    END IF;
END //

-- Trigger untuk update room status saat check-out
CREATE TRIGGER update_room_status_checkout
AFTER UPDATE ON reservations
FOR EACH ROW
BEGIN
    IF NEW.status = 'checked_out' AND OLD.status != 'checked_out' THEN
        UPDATE rooms SET status = 'cleaning' WHERE room_id = NEW.room_id;
    END IF;
END //

-- Trigger untuk auto-generate reservation code
CREATE TRIGGER generate_reservation_code
BEFORE INSERT ON reservations
FOR EACH ROW
BEGIN
    IF NEW.reservation_code IS NULL OR NEW.reservation_code = '' THEN
        CALL GenerateReservationCode(@new_code);
        SET NEW.reservation_code = @new_code;
    END IF;
END //

DELIMITER ;

-- ============================================================
-- FINAL SETUP COMPLETE
-- ============================================================

SELECT 'Hotel Senang Hati Database Schema Created Successfully!' as status;
