-- Additive migration: run only if this table is not already present.
-- No passwords or reusable credentials are stored here.
CREATE TABLE IF NOT EXISTS `admin_login_attempts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `username_hash` CHAR(64) NOT NULL,
  `ip_hash` CHAR(64) NOT NULL,
  `attempted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_admin_attempt_time` (`attempted_at`),
  INDEX `idx_admin_attempt_username_time` (`username_hash`, `attempted_at`),
  INDEX `idx_admin_attempt_ip_time` (`ip_hash`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
