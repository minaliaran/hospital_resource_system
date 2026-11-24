CREATE DATABASE  hospital_resource_system;
USE hospital_resource_system;


DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS email_logs;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS transfers;
DROP TABLE IF EXISTS external_requests;
DROP TABLE IF EXISTS requests;
DROP TABLE IF EXISTS oxygen_stock;
DROP TABLE IF EXISTS blood_stock;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS hospitals;
USE hospital_resource_system;
CREATE TABLE hospitals (
    hospital_id INT AUTO_INCREMENT PRIMARY KEY,
    hospital_name VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    address TEXT NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    hospital_id INT NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'blood_staff', 'oxygen_staff') NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (hospital_id) REFERENCES hospitals(hospital_id) ON DELETE CASCADE
) ENGINE=InnoDB;


CREATE TABLE blood_stock (
    blood_id INT AUTO_INCREMENT PRIMARY KEY,
    hospital_id INT NOT NULL,
    blood_group ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-') NOT NULL,
    component ENUM('Whole Blood', 'RBC', 'Platelets', 'Plasma') NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    status ENUM('Available', 'Low', 'Critical', 'Out of Stock') AS (
        CASE 
            WHEN quantity <= 0 THEN 'Out of Stock'
            WHEN quantity BETWEEN 1 AND 5 THEN 'Critical'
            WHEN quantity BETWEEN 6 AND 15 THEN 'Low'
            ELSE 'Available'
        END
    ) STORED,
    expiry_date DATE NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (hospital_id) REFERENCES hospitals(hospital_id) ON DELETE CASCADE,
    UNIQUE KEY uq_blood_hospital (hospital_id, blood_group, component, expiry_date)
) ENGINE=InnoDB;


CREATE TABLE oxygen_stock (
    oxygen_id INT AUTO_INCREMENT PRIMARY KEY,
    hospital_id INT NOT NULL,
    cylinder_type ENUM('Small', 'Medium', 'Large', 'Portable', 'E-Type', 'D-Type', 'J-Type') NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    status ENUM('Available', 'Low', 'Critical', 'Out of Stock') AS (
        CASE 
            WHEN quantity <= 0 THEN 'Out of Stock'
            WHEN quantity BETWEEN 1 AND 2 THEN 'Critical'
            WHEN quantity BETWEEN 3 AND 5 THEN 'Low'
            ELSE 'Available'
        END
    ) STORED,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (hospital_id) REFERENCES hospitals(hospital_id) ON DELETE CASCADE,
    UNIQUE KEY uq_oxygen_hospital (hospital_id, cylinder_type)
) ENGINE=InnoDB;


CREATE TABLE requests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    created_by INT NOT NULL,
    hospital_id INT NOT NULL,
    resource_type ENUM('Blood', 'Oxygen') NOT NULL,
    blood_group ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-') NULL,
    cylinder_type ENUM('Small', 'Medium', 'Large', 'Portable', 'E-Type', 'D-Type', 'J-Type') NULL,
    quantity INT NOT NULL,
    priority ENUM('Low', 'Medium', 'High', 'Emergency') NOT NULL,
    reason TEXT NOT NULL,
    status ENUM('Pending', 'Approved', 'Rejected', 'Forwarded', 'Completed') DEFAULT 'Pending',
    admin_note TEXT NULL,
    approved_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_at TIMESTAMP NULL,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (hospital_id) REFERENCES hospitals(hospital_id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;


CREATE TABLE external_requests (
    external_request_id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NULL,
    sender_hospital_id INT NOT NULL,
    receiver_hospital_id INT NOT NULL,
    resource_type ENUM('Blood', 'Oxygen') NOT NULL,
    blood_group ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-') NULL,
    cylinder_type ENUM('Small', 'Medium', 'Large', 'Portable', 'E-Type', 'D-Type', 'J-Type') NULL,
    quantity INT NOT NULL,
    priority ENUM('Low', 'Medium', 'High', 'Emergency') NOT NULL,
    reason TEXT NOT NULL,
    status ENUM('Pending', 'Accepted', 'Rejected', 'Cancelled') DEFAULT 'Pending',
    response_note TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    responded_at TIMESTAMP NULL,
    FOREIGN KEY (request_id) REFERENCES requests(request_id) ON DELETE SET NULL,
    FOREIGN KEY (sender_hospital_id) REFERENCES hospitals(hospital_id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_hospital_id) REFERENCES hospitals(hospital_id) ON DELETE CASCADE
) ENGINE=InnoDB;


CREATE TABLE transfers (
    transfer_id INT AUTO_INCREMENT PRIMARY KEY,
    external_request_id INT NOT NULL,
    from_hospital_id INT NOT NULL,
    to_hospital_id INT NOT NULL,
    resource_type ENUM('Blood', 'Oxygen') NOT NULL,
    blood_group ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-') NULL,
    cylinder_type ENUM('Small', 'Medium', 'Large', 'Portable', 'E-Type', 'D-Type', 'J-Type') NULL,
    quantity INT NOT NULL,
    driver_name VARCHAR(100) NOT NULL,
    vehicle_number VARCHAR(20) NOT NULL,
    status ENUM('Pending', 'In Transit', 'Completed', 'Cancelled') DEFAULT 'Pending',
    transfer_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    received_date TIMESTAMP NULL,
    received_by INT NULL,
    FOREIGN KEY (external_request_id) REFERENCES external_requests(external_request_id) ON DELETE CASCADE,
    FOREIGN KEY (from_hospital_id) REFERENCES hospitals(hospital_id) ON DELETE CASCADE,
    FOREIGN KEY (to_hospital_id) REFERENCES hospitals(hospital_id) ON DELETE CASCADE,
    FOREIGN KEY (received_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;


CREATE TABLE notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    hospital_id INT NOT NULL,
    user_id INT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (hospital_id) REFERENCES hospitals(hospital_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;


CREATE TABLE email_logs (
    email_id INT AUTO_INCREMENT PRIMARY KEY,
    recipient VARCHAR(100) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('sent', 'failed') NOT NULL,
    sent_by INT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sent_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;


CREATE TABLE audit_logs (
    audit_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;


INSERT INTO hospitals (hospital_id, hospital_name, email, phone, address) VALUES
(1, 'National Hospital Colombo', 'hrcs.system@gmail.com', '+94112691111', 'Regent Street, Colombo 10'),
(2, 'Colombo South Teaching Hospital', 'hrcs.system@gmail.com', '+94112763261', 'Kalubowila, Dehiwala'),
(3, 'Lady Ridgeway Hospital', 'hrcs.system@gmail.com', '+94112693474', 'Maradana Road, Colombo 08'),
(4, 'Castle Street Hospital', 'hrcs.system@gmail.com', '+94112696231', 'Castle Street, Colombo 08'),
(5, 'National Eye Hospital', 'hrcs.system@gmail.com', '+94112693911', 'Lipton Circus, Colombo 10');

INSERT INTO users (user_id, hospital_id, full_name, username, password, role) VALUES
(1, 1, 'NH Admin', 'nh_admin', '$2y$10$J8jcwn0UKJGzSfK6RlSVwO6Ae1H0ScR1yoZsB4Z.fMVSx5ExyLB6m', 'admin'),
(2, 1, 'NH Blood Staff', 'nh_blood', '$2y$10$J8jcwn0UKJGzSfK6RlSVwO6Ae1H0ScR1yoZsB4Z.fMVSx5ExyLB6m', 'blood_staff'),
(3, 1, 'NH Oxygen Staff', 'nh_oxygen', '$2y$10$J8jcwn0UKJGzSfK6RlSVwO6Ae1H0ScR1yoZsB4Z.fMVSx5ExyLB6m', 'oxygen_staff'),

(4, 2, 'CSTH Admin', 'csth_admin', '$2y$10$J8jcwn0UKJGzSfK6RlSVwO6Ae1H0ScR1yoZsB4Z.fMVSx5ExyLB6m', 'admin'),
(5, 2, 'CSTH Blood Staff', 'csth_blood', '$2y$10$J8jcwn0UKJGzSfK6RlSVwO6Ae1H0ScR1yoZsB4Z.fMVSx5ExyLB6m', 'blood_staff'),
(6, 2, 'CSTH Oxygen Staff', 'csth_oxygen', '$2y$10$J8jcwn0UKJGzSfK6RlSVwO6Ae1H0ScR1yoZsB4Z.fMVSx5ExyLB6m', 'oxygen_staff'),

(7, 3, 'LRH Admin', 'lrh_admin', '$2y$10$J8jcwn0UKJGzSfK6RlSVwO6Ae1H0ScR1yoZsB4Z.fMVSx5ExyLB6m', 'admin'),
(8, 3, 'LRH Blood Staff', 'lrh_blood', '$2y$10$J8jcwn0UKJGzSfK6RlSVwO6Ae1H0ScR1yoZsB4Z.fMVSx5ExyLB6m', 'blood_staff'),
(9, 3, 'LRH Oxygen Staff', 'lrh_oxygen', '$2y$10$J8jcwn0UKJGzSfK6RlSVwO6Ae1H0ScR1yoZsB4Z.fMVSx5ExyLB6m', 'oxygen_staff'),

(10, 4, 'Castle Admin', 'castle_admin', '$2y$10$J8jcwn0UKJGzSfK6RlSVwO6Ae1H0ScR1yoZsB4Z.fMVSx5ExyLB6m', 'admin'),
(11, 4, 'Castle Blood Staff', 'castle_blood', '$2y$10$J8jcwn0UKJGzSfK6RlSVwO6Ae1H0ScR1yoZsB4Z.fMVSx5ExyLB6m', 'blood_staff'),
(12, 4, 'Castle Oxygen Staff', 'castle_oxygen', '$2y$10$J8jcwn0UKJGzSfK6RlSVwO6Ae1H0ScR1yoZsB4Z.fMVSx5ExyLB6m', 'oxygen_staff'),

(13, 5, 'NEH Admin', 'neh_admin', '$2y$10$J8jcwn0UKJGzSfK6RlSVwO6Ae1H0ScR1yoZsB4Z.fMVSx5ExyLB6m', 'admin'),
(14, 5, 'NEH Blood Staff', 'neh_blood', '$2y$10$J8jcwn0UKJGzSfK6RlSVwO6Ae1H0ScR1yoZsB4Z.fMVSx5ExyLB6m', 'blood_staff'),
(15, 5, 'NEH Oxygen Staff', 'neh_oxygen', '$2y$10$J8jcwn0UKJGzSfK6RlSVwO6Ae1H0ScR1yoZsB4Z.fMVSx5ExyLB6m', 'oxygen_staff');


INSERT INTO blood_stock (hospital_id, blood_group, component, quantity, expiry_date) VALUES
(1, 'A+', 'RBC', 25, '2026-08-15'),
(1, 'A-', 'Whole Blood', 12, '2026-11-15'),
(1, 'B+', 'Platelets', 15, '2026-07-20'),
(1, 'B-', 'RBC', 10, '2026-11-20'),
(1, 'AB+', 'Plasma', 8, '2026-09-01'),
(1, 'AB-', 'Plasma', 6, '2026-12-05'),
(1, 'O+', 'Whole Blood', 30, '2026-08-10'),
(1, 'O-', 'Whole Blood', 18, '2026-11-10');


INSERT INTO blood_stock (hospital_id, blood_group, component, quantity, expiry_date) VALUES
(2, 'A+', 'Whole Blood', 5, '2026-08-01'),
(2, 'A-', 'RBC', 4, '2026-10-25'),
(2, 'B+', 'RBC', 8, '2026-08-05'),
(2, 'B-', 'Platelets', 3, '2026-10-30'),
(2, 'AB+', 'Plasma', 1, '2026-08-20'),
(2, 'AB-', 'Plasma', 2, '2026-11-05'),
(2, 'O+', 'Whole Blood', 3, '2026-07-25'),
(2, 'O-', 'Whole Blood', 6, '2026-10-28');

INSERT INTO blood_stock (hospital_id, blood_group, component, quantity, expiry_date) VALUES
(3, 'A+', 'RBC', 10, '2026-08-10'),
(3, 'A-', 'Whole Blood', 8, '2026-11-12'),
(3, 'B+', 'Platelets', 8, '2026-07-25'),
(3, 'B-', 'RBC', 7, '2026-11-18'),
(3, 'AB+', 'Plasma', 2, '2026-09-05'),
(3, 'AB-', 'Plasma', 4, '2026-12-01'),
(3, 'O+', 'Whole Blood', 12, '2026-08-12'),
(3, 'O-', 'Whole Blood', 9, '2026-11-14');


INSERT INTO blood_stock (hospital_id, blood_group, component, quantity, expiry_date) VALUES
(4, 'A+', 'Whole Blood', 4, '2026-08-05'),
(4, 'A-', 'Platelets', 2, '2026-10-20'),
(4, 'B+', 'RBC', 2, '2026-08-12'),
(4, 'B-', 'Whole Blood', 3, '2026-10-22'),
(4, 'AB+', 'Plasma', 1, '2026-08-30'),
(4, 'AB-', 'Plasma', 1, '2026-11-02'),
(4, 'O+', 'Whole Blood', 3, '2026-07-28'),
(4, 'O-', 'RBC', 4, '2026-10-29');


INSERT INTO blood_stock (hospital_id, blood_group, component, quantity, expiry_date) VALUES
(5, 'A+', 'Whole Blood', 8, '2026-11-01'),
(5, 'A-', 'RBC', 3, '2026-10-24'),
(5, 'B+', 'Whole Blood', 1, '2026-08-15'),
(5, 'B-', 'Platelets', 4, '2026-10-26'),
(5, 'AB+', 'Plasma', 5, '2026-11-15'),
(5, 'AB-', 'Whole Blood', 2, '2026-11-10'),
(5, 'O+', 'Whole Blood', 14, '2026-11-25'),
(5, 'O-', 'RBC', 5, '2026-11-08');


INSERT INTO oxygen_stock (hospital_id, cylinder_type, quantity) VALUES
(1, 'Small', 12),
(1, 'Medium', 8),
(1, 'Large', 20);


INSERT INTO oxygen_stock (hospital_id, cylinder_type, quantity) VALUES
(2, 'Small', 1),
(2, 'Medium', 2),
(2, 'Large', 4);


INSERT INTO oxygen_stock (hospital_id, cylinder_type, quantity) VALUES
(3, 'Small', 5),
(3, 'Medium', 6),
(3, 'Large', 9);


INSERT INTO oxygen_stock (hospital_id, cylinder_type, quantity) VALUES
(4, 'Small', 1),
(4, 'Medium', 1),
(4, 'Large', 3);


INSERT INTO oxygen_stock (hospital_id, cylinder_type, quantity) VALUES
(5, 'Small', 0),
(5, 'Medium', 1),
(5, 'Large', 1);
