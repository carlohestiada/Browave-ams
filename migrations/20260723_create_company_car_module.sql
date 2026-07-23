-- ==========================================================
-- BROWAVE AMS
-- Company Car Module Migration
-- ==========================================================

-- ==========================================================
-- 1. Transportation Types (Master)
-- ==========================================================

CREATE TABLE transportation_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transportation_name VARCHAR(100) NOT NULL UNIQUE,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;


-- ==========================================================
-- 2. Vehicles (Master)
-- ==========================================================

CREATE TABLE vehicles (
    id INT AUTO_INCREMENT PRIMARY KEY,

    plate_number VARCHAR(20) NOT NULL UNIQUE,
    vehicle_name VARCHAR(100) NOT NULL,
    vehicle_model VARCHAR(100),
    vehicle_color VARCHAR(50),
    seating_capacity INT DEFAULT 4,

    remarks TEXT,

    is_active TINYINT(1) DEFAULT 1,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;


-- ==========================================================
-- 3. Drivers (Master)
-- ==========================================================

CREATE TABLE drivers (
    id INT AUTO_INCREMENT PRIMARY KEY,

    driver_name VARCHAR(100) NOT NULL,
    contact_number VARCHAR(30),
    license_number VARCHAR(50),

    remarks TEXT,

    is_active TINYINT(1) DEFAULT 1,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;


-- ==========================================================
-- 4. Company Car Departures
-- ==========================================================

CREATE TABLE company_car_departures (

    id INT AUTO_INCREMENT PRIMARY KEY,

    assignment_id INT NOT NULL,

    transportation_type_id INT NOT NULL,

    vehicle_id INT NULL,

    driver_id INT NULL,

    pickup_date DATE NOT NULL,

    pickup_time TIME NULL,

    pickup_location VARCHAR(150) NULL,

    destination VARCHAR(150) NULL,

    status ENUM(
        'Pending',
        'Scheduled',
        'Completed',
        'Cancelled'
    ) DEFAULT 'Pending',

    remarks TEXT NULL,

    created_by INT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_assignment (assignment_id),
    INDEX idx_pickup_date (pickup_date),
    INDEX idx_status (status),
    INDEX idx_vehicle (vehicle_id),
    INDEX idx_driver (driver_id),
    INDEX idx_transportation (transportation_type_id),

    CONSTRAINT fk_car_assignment
        FOREIGN KEY (assignment_id)
        REFERENCES assignments(id),

    CONSTRAINT fk_car_transportation
        FOREIGN KEY (transportation_type_id)
        REFERENCES transportation_types(id),

    CONSTRAINT fk_car_vehicle
        FOREIGN KEY (vehicle_id)
        REFERENCES vehicles(id),

    CONSTRAINT fk_car_driver
        FOREIGN KEY (driver_id)
        REFERENCES drivers(id)

) ENGINE=InnoDB;


-- ==========================================================
-- Sample Query for Company Car List
-- ==========================================================

SELECT

    ccd.id,

    e.employee_code,
    e.full_name,

    tt.transportation_name,

    v.vehicle_name,
    v.plate_number,

    d.driver_name,

    ccd.pickup_date,
    ccd.pickup_time,
    ccd.pickup_location,
    ccd.destination,

    ccd.status,
    ccd.remarks,
    ccd.created_at

FROM company_car_departures ccd

INNER JOIN assignments a
    ON ccd.assignment_id = a.id

INNER JOIN employees e
    ON a.employee_id = e.id

LEFT JOIN transportation_types tt
    ON ccd.transportation_type_id = tt.id

LEFT JOIN vehicles v
    ON ccd.vehicle_id = v.id

LEFT JOIN drivers d
    ON ccd.driver_id = d.id

ORDER BY
    ccd.pickup_date DESC,
    ccd.pickup_time DESC;