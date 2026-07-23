-- ==========================================================
-- BROWAVE AMS
-- Migration: Create Company Car Departures
-- File: 20260723_create_company_car_departures.sql
-- ==========================================================

DROP TABLE IF EXISTS company_car_departures;

CREATE TABLE company_car_departures (
    id INT AUTO_INCREMENT PRIMARY KEY,

    -- Reference to Room Assignment
    assignment_id INT NOT NULL,

    -- Transportation
    transportation_type_id INT NOT NULL,
    vehicle_id INT DEFAULT NULL,
    driver_id INT DEFAULT NULL,

    -- Schedule
    pickup_date DATE NOT NULL,
    pickup_time TIME DEFAULT NULL,
    pickup_location VARCHAR(150) DEFAULT NULL,
    destination VARCHAR(150) DEFAULT NULL,

    -- Status
    status ENUM(
        'Pending',
        'Scheduled',
        'Completed',
        'Cancelled'
    ) NOT NULL DEFAULT 'Pending',

    remarks TEXT DEFAULT NULL,

    -- Audit
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    -- Indexes
    INDEX idx_assignment (assignment_id),
    INDEX idx_transportation (transportation_type_id),
    INDEX idx_vehicle (vehicle_id),
    INDEX idx_driver (driver_id),
    INDEX idx_pickup_date (pickup_date),
    INDEX idx_status (status),

    -- Foreign Keys
    CONSTRAINT fk_ccd_assignment
        FOREIGN KEY (assignment_id)
        REFERENCES room_assignments(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT fk_ccd_transportation
        FOREIGN KEY (transportation_type_id)
        REFERENCES transportation_types(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_ccd_vehicle
        FOREIGN KEY (vehicle_id)
        REFERENCES vehicles(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,

    CONSTRAINT fk_ccd_driver
        FOREIGN KEY (driver_id)
        REFERENCES drivers(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;