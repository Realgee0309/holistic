-- ============================================================
-- Holistic Wellness — Feature Additions (run after holistic_wellness.sql)
-- Import via phpMyAdmin: Import tab → choose this file → Go
-- ============================================================
USE `holistic_wellness`;

-- ------------------------------------------------------------
-- Table: password_resets
-- Stores time-limited tokens for the Forgot Password flow
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE `password_resets` (
    `id`         INT          NOT NULL AUTO_INCREMENT,
    `email`      VARCHAR(150) NOT NULL,
    `token`      VARCHAR(64)  NOT NULL,
    `expires_at` DATETIME     NOT NULL,
    `used`       TINYINT(1)   NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_token` (`token`),
    INDEX `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Table: payments
-- Stores payment records for bookings
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `payments`;
CREATE TABLE `payments` (
    `id`             INT             NOT NULL AUTO_INCREMENT,
    `booking_id`     INT             NOT NULL,
    `amount`         DECIMAL(10,2)   NOT NULL,
    `currency`       VARCHAR(3)      NOT NULL DEFAULT 'KES',
    `method`         ENUM('mpesa','card','bank','paypal') NOT NULL,
    `status`         ENUM('pending','completed','failed','refunded') NOT NULL DEFAULT 'pending',
    `transaction_id` VARCHAR(100)    NULL,
    `reference`      VARCHAR(100)    NULL,
    `paid_at`        TIMESTAMP       NULL,
    `created_at`     TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `fk_payment_booking` (`booking_id`),
    CONSTRAINT `fk_payment_booking` FOREIGN KEY (`booking_id`)
        REFERENCES `bookings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Table: email_logs
-- Stores sent email records for tracking
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `email_logs`;
CREATE TABLE `email_logs` (
    `id`         INT          NOT NULL AUTO_INCREMENT,
    `recipient`  VARCHAR(150) NOT NULL,
    `subject`    VARCHAR(255) NOT NULL,
    `type`       VARCHAR(50)  NOT NULL, -- 'booking_confirmation', 'reminder', etc.
    `sent_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `status`     ENUM('sent','failed') NOT NULL DEFAULT 'sent',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Table: assessments
-- Stores client assessment responses
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `assessment_responses`;
DROP TABLE IF EXISTS `assessments`;
CREATE TABLE `assessments` (
    `id`         INT          NOT NULL AUTO_INCREMENT,
    `user_id`    INT          NOT NULL,
    `type`       VARCHAR(50)  NOT NULL, -- 'initial', 'progress', 'feedback'
    `responses`  JSON         NOT NULL,
    `score`      INT          NULL,
    `completed_at` TIMESTAMP  NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `fk_assessment_user` (`user_id`),
    CONSTRAINT `fk_assessment_user` FOREIGN KEY (`user_id`)
        REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
