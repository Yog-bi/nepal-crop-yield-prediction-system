-- Normalized MySQL schema for the Nepal Crop Yield Prediction Web Application
-- Database: nepal_crop_yield

CREATE DATABASE IF NOT EXISTS `nepal_crop_yield`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE `nepal_crop_yield`;

CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` VARCHAR(20) NOT NULL DEFAULT 'admin',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `provinces` (
  `id` TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `districts` (
  `id` SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `province_id` TINYINT UNSIGNED NOT NULL,
  `name` VARCHAR(50) NOT NULL UNIQUE,
  `latitude` DECIMAL(8,5) NOT NULL,
  `longitude` DECIMAL(8,5) NOT NULL,
  CONSTRAINT `fk_districts_province`
    FOREIGN KEY (`province_id`) REFERENCES `provinces` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  INDEX `idx_districts_province` (`province_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `crops` (
  `id` TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL UNIQUE,
  `display_name` VARCHAR(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `posts` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `body` TEXT NOT NULL,
  `source_name` VARCHAR(100) NOT NULL,
  `source_url` VARCHAR(255) NULL,
  `category` VARCHAR(50) NOT NULL DEFAULT 'General',
  `published_at` DATE NULL,
  `created_by` INT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_posts_user`
    FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
    ON UPDATE CASCADE ON DELETE SET NULL,
  INDEX `idx_posts_author` (`created_by`),
  INDEX `idx_posts_published` (`published_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `historical_yields` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `district_id` SMALLINT UNSIGNED NOT NULL,
  `crop_id` TINYINT UNSIGNED NOT NULL,
  `year` SMALLINT UNSIGNED NOT NULL,
  `yield_kg_ha` DECIMAL(10,2) NOT NULL,
  `rainfall_mm` DECIMAL(10,2) NULL,
  `avg_temp_c` DECIMAL(5,2) NULL,
  CONSTRAINT `fk_historical_district`
    FOREIGN KEY (`district_id`) REFERENCES `districts` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_historical_crop`
    FOREIGN KEY (`crop_id`) REFERENCES `crops` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `uq_historical_series`
    UNIQUE (`district_id`, `crop_id`, `year`),
  INDEX `idx_historical_crop` (`crop_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `predictions` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_session` VARCHAR(64) NULL,
  `district_id` SMALLINT UNSIGNED NOT NULL,
  `crop_id` TINYINT UNSIGNED NOT NULL,
  `area_ha` DECIMAL(10,2) NOT NULL,
  `climate_inputs` JSON NOT NULL,
  `predicted_yield` DECIMAL(10,2) NOT NULL,
  `model_version` VARCHAR(50) NOT NULL DEFAULT 'audit_v1',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_predictions_district`
    FOREIGN KEY (`district_id`) REFERENCES `districts` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_predictions_crop`
    FOREIGN KEY (`crop_id`) REFERENCES `crops` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  INDEX `idx_predictions_district_crop` (`district_id`, `crop_id`),
  INDEX `idx_predictions_created` (`created_at`),
  INDEX `idx_predictions_crop` (`crop_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
