-- ============================================================
-- Holistic Wellness — MySQL Database Schema (v2: User Accounts)
-- Import via phpMyAdmin: Import tab → choose this file → Go
-- ============================================================

CREATE DATABASE IF NOT EXISTS `holistic_wellness`
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `holistic_wellness`;

-- Drop in reverse FK order
DROP TABLE IF EXISTS `progress_notes`;
DROP TABLE IF EXISTS `admin_users`;
DROP TABLE IF EXISTS `contacts`;
DROP TABLE IF EXISTS `bookings`;
DROP TABLE IF EXISTS `users`;

-- ------------------------------------------------------------
-- Table: users  (client accounts)
-- ------------------------------------------------------------
CREATE TABLE `users` (
    `id`            INT          NOT NULL AUTO_INCREMENT,
    `name`          VARCHAR(100) NOT NULL,
    `email`         VARCHAR(150) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `is_anonymous`  TINYINT(1)   NOT NULL DEFAULT 0,
    `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sample clients
INSERT INTO `users` (`name`, `email`, `password_hash`, `is_anonymous`) VALUES
('Maria K.',  'maria@example.com',  '$2y$12$kQGxMFlcAepDatdM5WGI7.QJGX3.y1MPiyFcFsetPAmZhCbcm1ksG', 0),
('Anonymous', 'anon@example.com',   '$2y$12$kQGxMFlcAepDatdM5WGI7.QJGX3.y1MPiyFcFsetPAmZhCbcm1ksG', 1);
-- Both sample accounts use password: admin123

-- ------------------------------------------------------------
-- Table: bookings
-- ------------------------------------------------------------
CREATE TABLE `bookings` (
    `id`             INT          NOT NULL AUTO_INCREMENT,
    `user_id`        INT          NULL DEFAULT NULL,
    `name`           VARCHAR(100) NOT NULL,
    `email`          VARCHAR(150) NOT NULL,
    `service`        VARCHAR(100) NOT NULL,
    `preferred_date` DATE         NOT NULL,
    `preferred_time` VARCHAR(50)  NOT NULL,
    `message`        TEXT,
    `status`         ENUM('pending','confirmed','cancelled') NOT NULL DEFAULT 'pending',
    `created_at`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `fk_booking_user` (`user_id`),
    CONSTRAINT `fk_booking_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `bookings` (`user_id`, `name`, `email`, `service`, `preferred_date`, `preferred_time`, `message`, `status`) VALUES
(1, 'Maria K.',     'maria@example.com',  'Individual Therapy',  '2025-06-10', 'Morning (9am-12pm)',   'Looking forward to our first session.', 'confirmed'),
(NULL, 'James Mwangi', 'james@example.com', 'Couples Therapy',   '2025-06-12', 'Afternoon (1pm-5pm)', 'My partner and I are excited.',         'pending'),
(2, 'Anonymous',    'anon@example.com',   'Anxiety & Depression','2025-06-15', 'Evening (6pm-9pm)',   'I have been struggling lately.',        'pending');

-- ------------------------------------------------------------
-- Table: contacts
-- ------------------------------------------------------------
CREATE TABLE `contacts` (
    `id`         INT          NOT NULL AUTO_INCREMENT,
    `user_id`    INT          NULL DEFAULT NULL,
    `name`       VARCHAR(100) NOT NULL,
    `email`      VARCHAR(150) NOT NULL,
    `subject`    VARCHAR(100) NOT NULL,
    `message`    TEXT         NOT NULL,
    `is_read`    TINYINT(1)   NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `fk_contact_user` (`user_id`),
    CONSTRAINT `fk_contact_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `contacts` (`user_id`, `name`, `email`, `subject`, `message`, `is_read`) VALUES
(NULL, 'Thomas R.', 'thomas@example.com', 'General Inquiry', 'I am interested in learning more about your services and pricing.', 0);

-- ------------------------------------------------------------
-- Table: progress_notes  (therapist notes visible to client)
-- ------------------------------------------------------------
CREATE TABLE `progress_notes` (
    `id`         INT       NOT NULL AUTO_INCREMENT,
    `user_id`    INT       NOT NULL,
    `booking_id` INT       NULL DEFAULT NULL,
    `note`       TEXT      NOT NULL,
    `is_visible` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP  NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `fk_note_user`    (`user_id`),
    KEY `fk_note_booking` (`booking_id`),
    CONSTRAINT `fk_note_user`    FOREIGN KEY (`user_id`)    REFERENCES `users`    (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_note_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `progress_notes` (`user_id`, `booking_id`, `note`, `is_visible`) VALUES
(1, 1, 'Great first session. Maria showed excellent insight into her anxiety triggers. Recommended breathing exercises for daily practice. Next session to focus on CBT techniques.', 1);

-- ------------------------------------------------------------
-- Table: admin_users
-- Default credentials: admin / admin123
-- ------------------------------------------------------------
CREATE TABLE `admin_users` (
    `id`            INT          NOT NULL AUTO_INCREMENT,
    `username`      VARCHAR(50)  NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `admin_users` (`username`, `password_hash`) VALUES
('admin', '$2y$12$kQGxMFlcAepDatdM5WGI7.QJGX3.y1MPiyFcFsetPAmZhCbcm1ksG');
-- Hash = password_hash('admin123', PASSWORD_BCRYPT)
