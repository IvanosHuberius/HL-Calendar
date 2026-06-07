-- HL Calendar schema update 1.8.0
-- Adds holiday-skip / exception-date support to recurring events.

ALTER TABLE `#__calendar_events`
    ADD COLUMN `skip_holidays` tinyint(1) NOT NULL DEFAULT 0 AFTER `recurrence_days`,
    ADD COLUMN `holiday_country` varchar(2) NOT NULL DEFAULT '' AFTER `skip_holidays`,
    ADD COLUMN `holiday_subdivision` varchar(6) NOT NULL DEFAULT '' AFTER `holiday_country`,
    ADD COLUMN `exception_dates` text AFTER `holiday_subdivision`;

CREATE TABLE IF NOT EXISTS `#__calendar_holidays` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `country` varchar(2) NOT NULL DEFAULT '',
    `subdivision` varchar(6) NOT NULL DEFAULT '',
    `hyear` smallint unsigned NOT NULL DEFAULT 0,
    `dates` mediumtext,
    `fetched` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_csy` (`country`, `subdivision`, `hyear`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;
