-- ============================================================
-- Holistic Wellness — Migration v2
-- Run in phpMyAdmin AFTER importing holistic_wellness.sql
-- ============================================================
USE `holistic_wellness`;

-- ── 1. CSRF Tokens ──────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `csrf_tokens` (
    `id`         INT          NOT NULL AUTO_INCREMENT,
    `token`      VARCHAR(64)  NOT NULL UNIQUE,
    `session_id` VARCHAR(128) NOT NULL,
    `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_token` (`token`),
    KEY `idx_session` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Auto-expire tokens older than 2 hours (EVENT — enable if event scheduler is on)
-- CREATE EVENT IF NOT EXISTS cleanup_csrf ON SCHEDULE EVERY 1 HOUR DO DELETE FROM csrf_tokens WHERE created_at < DATE_SUB(NOW(), INTERVAL 2 HOUR);

-- ── 2. Password Reset Tokens ────────────────────────────────
CREATE TABLE IF NOT EXISTS `password_resets` (
    `id`         INT          NOT NULL AUTO_INCREMENT,
    `email`      VARCHAR(150) NOT NULL,
    `token`      VARCHAR(64)  NOT NULL UNIQUE,
    `expires_at` DATETIME     NOT NULL,
    `used`       TINYINT(1)   NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_token` (`token`),
    KEY `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 3. Two-Way Messaging ────────────────────────────────────
CREATE TABLE IF NOT EXISTS `thread_messages` (
    `id`          INT       NOT NULL AUTO_INCREMENT,
    `user_id`     INT       NOT NULL,
    `sender`      ENUM('client','therapist') NOT NULL DEFAULT 'client',
    `body`        TEXT      NOT NULL,
    `is_read`     TINYINT(1) NOT NULL DEFAULT 0,
    `created_at`  TIMESTAMP  NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `fk_tm_user` (`user_id`),
    CONSTRAINT `fk_tm_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 4. Testimonials ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `testimonials` (
    `id`           INT          NOT NULL AUTO_INCREMENT,
    `client_name`  VARCHAR(100) NOT NULL,
    `service`      VARCHAR(100) DEFAULT NULL,
    `rating`       TINYINT(1)   NOT NULL DEFAULT 5,
    `body`         TEXT         NOT NULL,
    `is_published` TINYINT(1)   NOT NULL DEFAULT 1,
    `sort_order`   INT          NOT NULL DEFAULT 0,
    `created_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `testimonials` (`client_name`, `service`, `rating`, `body`, `is_published`, `sort_order`) VALUES
('Maria K.',     'Individual Therapy',  5, 'Working with Holistic Wellness transformed my approach to anxiety. The online sessions were convenient and just as effective as in-person therapy.', 1, 1),
('James & Sarah','Couples Therapy',     5, 'The couples therapy helped us rebuild our communication from the ground up. We\'re so grateful for the guidance during a difficult time.', 1, 2),
('Thomas R.',    'Anxiety Support',     5, 'I was skeptical about online therapy at first, but the experience has been seamless. The flexibility to schedule sessions around my busy work life has been invaluable.', 1, 3);

-- ── 5. Blog / Resources ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS `blog_posts` (
    `id`           INT          NOT NULL AUTO_INCREMENT,
    `slug`         VARCHAR(160) NOT NULL UNIQUE,
    `title`        VARCHAR(200) NOT NULL,
    `excerpt`      TEXT,
    `body`         LONGTEXT     NOT NULL,
    `category`     VARCHAR(80)  NOT NULL DEFAULT 'General',
    `cover_image`  VARCHAR(255) DEFAULT NULL,
    `is_published` TINYINT(1)   NOT NULL DEFAULT 0,
    `views`        INT          NOT NULL DEFAULT 0,
    `created_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_slug` (`slug`),
    KEY `idx_published` (`is_published`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `blog_posts` (`slug`,`title`,`excerpt`,`body`,`category`,`is_published`) VALUES
('understanding-anxiety',
 '5 Signs You Might Be Dealing With Anxiety (And What To Do)',
 'Anxiety can show up in ways you might not expect. Here are five common signs and evidence-based steps you can take right now.',
 '<p>Anxiety is one of the most common mental health challenges in the world, yet it often goes unrecognized. Many people live with anxiety for years without knowing there is a name for what they experience.</p><h3>1. Constant Worry</h3><p>If you find yourself worrying about everyday situations — work deadlines, social interactions, health — to an extent that feels uncontrollable, this may be a sign of generalized anxiety.</p><h3>2. Physical Symptoms</h3><p>Anxiety is not just a mental experience. It manifests physically through a racing heart, shallow breathing, muscle tension, headaches, and digestive upset.</p><h3>3. Avoidance</h3><p>One of the hallmark signs of anxiety is avoiding situations that trigger it. While avoidance provides short-term relief, it reinforces anxiety over time.</p><h3>4. Sleep Disturbances</h3><p>Racing thoughts at night, difficulty falling asleep, or waking up in the early hours with a sense of dread are all common anxiety symptoms.</p><h3>5. Irritability</h3><p>When the nervous system is in a constant state of alert, it takes very little to tip into frustration or irritability.</p><h3>What You Can Do</h3><p>The good news: anxiety is highly treatable. Cognitive Behavioural Therapy (CBT), mindfulness practices, and in some cases medication can all be effective. Reaching out to a therapist is often the most important first step.</p>',
 'Anxiety', 1),
('couples-communication',
 'The 3 Communication Mistakes Couples Make (And How to Fix Them)',
 'Good communication is the backbone of a healthy relationship. Discover the three most common pitfalls and practical ways to overcome them.',
 '<p>Communication breakdown is the most frequently cited reason couples seek therapy. Yet the mistakes couples make are often subtle — patterns that develop gradually without either partner noticing.</p><h3>Mistake 1: Criticising Instead of Complaining</h3><p>There is an important distinction between a complaint and a criticism. A complaint addresses a specific behaviour: "I felt hurt when you didn\'t call." A criticism attacks character: "You never think about anyone but yourself." Complaints are constructive; criticisms are destructive.</p><h3>Mistake 2: Defensiveness</h3><p>When we feel attacked, defensiveness is a natural reaction — but it shuts down dialogue. Instead of hearing your partner\'s concern, you focus on defending yourself. Try this: acknowledge your partner\'s experience before explaining yours.</p><h3>Mistake 3: Stonewalling</h3><p>Stonewalling — withdrawing emotionally and refusing to engage — often develops when one partner feels overwhelmed. While it feels protective, it leaves the other partner feeling dismissed. If you need a break, say so explicitly and agree to return to the conversation.</p><h3>The Fix</h3><p>Couples therapy provides a structured space to identify your unique patterns and build healthier habits together. Many couples find that just a few sessions produce transformative shifts.</p>',
 'Relationships', 1);

-- ── 6. Site Settings additions ──────────────────────────────
INSERT IGNORE INTO `site_settings` (`setting_key`, `setting_value`, `setting_type`, `label`, `description`) VALUES
('analytics_enabled', '1',  'text', 'Enable Analytics Page', ''),
('blog_enabled',      '1',  'text', 'Enable Blog/Resources',  '');
