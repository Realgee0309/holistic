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
-- Table: admin_replies
-- Stores admin replies to contact form messages
-- Client can see their replies in the dashboard Messages tab
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `admin_replies`;
CREATE TABLE `admin_replies` (
    `id`         INT       NOT NULL AUTO_INCREMENT,
    `contact_id` INT       NOT NULL,
    `reply`      TEXT      NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `fk_reply_contact` (`contact_id`),
    CONSTRAINT `fk_reply_contact` FOREIGN KEY (`contact_id`)
        REFERENCES `contacts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
