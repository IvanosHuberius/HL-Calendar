CREATE TABLE IF NOT EXISTS `#__calendar_events` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `title` varchar(255) NOT NULL DEFAULT '',
    `description` text,
    `location` varchar(255) DEFAULT '',
    `start_date` datetime NOT NULL,
    `end_date` datetime NOT NULL,
    `all_day` tinyint(1) NOT NULL DEFAULT 0,
    `color` varchar(7) NOT NULL DEFAULT '#3788d8',
    `category_id` int unsigned NOT NULL DEFAULT 0,
    `recurrence_type` varchar(20) DEFAULT 'none',
    `recurrence_interval` int unsigned DEFAULT 1,
    `recurrence_end` datetime DEFAULT NULL,
    `recurrence_days` varchar(50) DEFAULT '',
    `reminder_minutes` int DEFAULT 0,
    `state` tinyint(1) NOT NULL DEFAULT 1,
    `access` int unsigned NOT NULL DEFAULT 1,
    `created_by` int unsigned NOT NULL DEFAULT 0,
    `created` datetime NOT NULL,
    `modified` datetime DEFAULT NULL,
    `modified_by` int unsigned DEFAULT 0,
    `checked_out` int unsigned DEFAULT NULL,
    `checked_out_time` datetime DEFAULT NULL,
    `params` text,
    PRIMARY KEY (`id`),
    KEY `idx_state` (`state`),
    KEY `idx_category_id` (`category_id`),
    KEY `idx_created_by` (`created_by`),
    KEY `idx_start_date` (`start_date`),
    KEY `idx_access` (`access`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__calendar_categories` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `title` varchar(255) NOT NULL DEFAULT '',
    `color` varchar(7) NOT NULL DEFAULT '#3788d8',
    `description` text,
    `state` tinyint(1) NOT NULL DEFAULT 1,
    `access` int unsigned NOT NULL DEFAULT 1,
    `created_by` int unsigned NOT NULL DEFAULT 0,
    `created` datetime NOT NULL,
    `ordering` int NOT NULL DEFAULT 0,
    `params` text,
    PRIMARY KEY (`id`),
    KEY `idx_state` (`state`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

INSERT INTO `#__calendar_categories` (`title`, `color`, `description`, `state`, `created`) VALUES
('Allgemein', '#3788d8', 'Allgemeine Termine', 1, NOW()),
('Arbeit', '#e67c73', 'Arbeitstermine', 1, NOW()),
('Persönlich', '#33b679', 'Persönliche Termine', 1, NOW()),
('Familie', '#f6bf26', 'Familientermine', 1, NOW()),
('Feiertage', '#8e24aa', 'Feiertage und besondere Tage', 1, NOW());
