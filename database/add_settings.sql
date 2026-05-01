-- Add site_settings table
-- Run this in phpMyAdmin or append to the main import
USE `holistic_wellness`;

CREATE TABLE IF NOT EXISTS `site_settings` (
    `id`            INT           NOT NULL AUTO_INCREMENT,
    `setting_key`   VARCHAR(100)  NOT NULL UNIQUE,
    `setting_value` TEXT,
    `setting_type`  ENUM('text','textarea','image','email','tel','url') NOT NULL DEFAULT 'text',
    `label`         VARCHAR(150)  NOT NULL,
    `description`   VARCHAR(255)  DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default settings
INSERT INTO `site_settings` (`setting_key`, `setting_value`, `setting_type`, `label`, `description`) VALUES
-- Images
('img_hero',         '',  'image',    'Homepage Hero Background',    'Full-width image behind the hero section'),
('img_about_photo',  '',  'image',    'Therapist Photo',             'Your photo shown on the About and Homepage sections'),
('img_about_banner', '',  'image',    'About Page Banner',           'Background image for the About page header'),
('img_services_banner','','image',    'Services Page Banner',        'Background for the Services page header'),
('img_book_banner',  '',  'image',    'Booking Page Banner',         'Background for the Book page header'),
('img_contact_banner','', 'image',    'Contact Page Banner',         'Background for the Contact page header'),
('img_og',           '',  'image',    'Social Share Image (OG)',     'Image shown when the site is shared on social media'),
-- General
('site_name',        'Holistic Wellness',  'text',  'Practice Name',    'Displayed in the browser tab and header'),
('site_tagline',     'Professional Online Therapy', 'text', 'Tagline', 'Short description under the site name'),
('contact_phone',    '+254 797 582 384',   'tel',   'WhatsApp / Phone', 'Used for the WhatsApp booking links'),
('contact_email',    'contact@holisticwellness.com','email','Primary Email','Shown in the footer and contact page'),
('therapist_name',   'Dr. Jerald',         'text',  'Therapist Name',   'Displayed on the About page and footer'),
('therapist_title',  'Licensed Clinical Psychologist & Founder', 'text', 'Therapist Title', 'Shown below the therapist name'),
('hero_headline',    'Your Journey to Wellbeing Begins Here', 'text', 'Hero Headline', 'Main heading on the homepage'),
('hero_subtext',     'Professional online therapy that fits your schedule, from the comfort of your own space.', 'textarea', 'Hero Subtext', 'Paragraph below the hero headline'),
('footer_tagline',   'Professional online counseling for individuals, couples, and families.', 'textarea', 'Footer Tagline', 'Small text in the footer');
