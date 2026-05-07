

DROP DATABASE IF EXISTS rideshare;
CREATE DATABASE rideshare CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE rideshare;

-- ── 1. USERS ──────────────────────────────────────────────────
CREATE TABLE users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100)  NOT NULL,
    phone       VARCHAR(15)   UNIQUE NOT NULL,
    email       VARCHAR(100)  UNIQUE NOT NULL,
    password    VARCHAR(255)  NOT NULL,
    role        ENUM('rider','driver') NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ── 2. DRIVER ─────────────────────────────────────────────────
-- Was "drivers" in T1; T2+T3 expect "driver"
CREATE TABLE driver (
    driver_id           INT AUTO_INCREMENT PRIMARY KEY,
    user_id             INT UNIQUE,
    vehicle_type        ENUM('car','bike','cng') NOT NULL,
    license_number      VARCHAR(50) NOT NULL,
    availability_status ENUM('online','offline') DEFAULT 'offline',

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ── 3. RIDER PROFILE ──────────────────────────────────────────
CREATE TABLE rider_profile (
    rider_id            INT AUTO_INCREMENT PRIMARY KEY,
    user_id             INT NOT NULL UNIQUE,
    home_address        VARCHAR(255),
    work_address        VARCHAR(255),
    preferred_vehicle   ENUM('car','bike','both') DEFAULT 'both',
    payment_preference  ENUM('cash','card','mobile_banking') DEFAULT 'cash',
    profile_picture     VARCHAR(255),
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ── 4. RIDES ──────────────────────────────────────────────────

CREATE TABLE rides (
    ride_id          INT AUTO_INCREMENT PRIMARY KEY,
    rider_id         INT NOT NULL,
    driver_id        INT DEFAULT NULL,
    pickup_location  VARCHAR(255) NOT NULL,
    drop_location    VARCHAR(255) NOT NULL,
    distance         DECIMAL(8,2) DEFAULT 0,
    fare             DECIMAL(10,2) DEFAULT 0,
    vehicle_type     ENUM('car','bike','cng') DEFAULT 'car',
    status           ENUM('Requested','Accepted','Ongoing','completed','cancelled') DEFAULT 'Requested',
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_time   DATETIME DEFAULT NULL,

    FOREIGN KEY (rider_id)  REFERENCES users(id)   ON DELETE CASCADE,
    FOREIGN KEY (driver_id) REFERENCES driver(driver_id) ON DELETE SET NULL
);

-- ── 5. PAYMENTS ───────────────────────────────────────────────

CREATE TABLE payments (
    payment_id      INT AUTO_INCREMENT PRIMARY KEY,
    ride_id         INT NOT NULL,
    amount          DECIMAL(10,2) NOT NULL,
    payment_method  VARCHAR(50) NOT NULL,         -- cash, wallet, card
    payment_status  VARCHAR(50) DEFAULT 'unpaid', -- paid, unpaid
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (ride_id) REFERENCES rides(ride_id) ON DELETE CASCADE
);

-- ── 6. RATINGS ────────────────────────────────────────────────
CREATE TABLE ratings (
    rating_id   INT AUTO_INCREMENT PRIMARY KEY,
    ride_id     INT NOT NULL,
    rider_id    INT NOT NULL,
    driver_id   INT NOT NULL,
    rating      INT CHECK (rating >= 1 AND rating <= 5),
    feedback    TEXT,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (ride_id)   REFERENCES rides(ride_id)          ON DELETE CASCADE,
    FOREIGN KEY (rider_id)  REFERENCES users(id)               ON DELETE CASCADE,
    FOREIGN KEY (driver_id) REFERENCES driver(driver_id)       ON DELETE CASCADE
);
