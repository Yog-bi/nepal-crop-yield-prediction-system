-- One-time migration from the original text-based schema to the normalized schema.
-- Back up nepal_crop_yield before running this file.
USE `nepal_crop_yield`;

DROP TABLE IF EXISTS `predictions_normalized`;
DROP TABLE IF EXISTS `historical_yields_normalized`;
DROP TABLE IF EXISTS `districts_normalized`;
DROP TABLE IF EXISTS `crops_normalized`;
DROP TABLE IF EXISTS `provinces_normalized`;

CREATE TABLE `provinces_normalized` (
  `id` TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `provinces_normalized` (`name`) VALUES
('Koshi Province'),
('Madhesh Province'),
('Bagmati Province'),
('Gandaki Province'),
('Lumbini Province'),
('Karnali Province'),
('Sudurpashchim Province');

CREATE TABLE `districts_normalized` (
  `id` SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `province_id` TINYINT UNSIGNED NOT NULL,
  `name` VARCHAR(50) NOT NULL UNIQUE,
  `latitude` DECIMAL(8,5) NOT NULL,
  `longitude` DECIMAL(8,5) NOT NULL,
  CONSTRAINT `fk_districts_normalized_province`
    FOREIGN KEY (`province_id`) REFERENCES `provinces_normalized` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  INDEX `idx_districts_normalized_province` (`province_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `districts_normalized` (`id`, `province_id`, `name`, `latitude`, `longitude`)
SELECT
  MIN(d.id),
  p.id,
  CASE d.name
    WHEN 'Bhakthapur' THEN 'Bhaktapur'
    WHEN 'Nawalpur' THEN 'Nawalparasi East'
    WHEN 'Parasi' THEN 'Nawalparasi West'
    WHEN 'Eastern Rukum' THEN 'Rukum East'
    WHEN 'Western Rukum' THEN 'Rukum West'
    WHEN 'Makwanpur' THEN 'Makawanpur'
    ELSE d.name
  END AS canonical_district,
  MAX(d.latitude),
  MAX(d.longitude)
FROM districts d
INNER JOIN provinces_normalized p
  ON p.name = CASE d.province
    WHEN 'Province 1' THEN 'Koshi Province'
    WHEN 'Koshi' THEN 'Koshi Province'
    WHEN 'Koshi Province' THEN 'Koshi Province'
    WHEN 'Madhesh' THEN 'Madhesh Province'
    WHEN 'Madhesh Province' THEN 'Madhesh Province'
    WHEN 'Bagmati' THEN 'Bagmati Province'
    WHEN 'Bagmati Province' THEN 'Bagmati Province'
    WHEN 'Gandaki' THEN 'Gandaki Province'
    WHEN 'Gandaki Province' THEN 'Gandaki Province'
    WHEN 'Lumbini' THEN 'Lumbini Province'
    WHEN 'Lumbini Province' THEN 'Lumbini Province'
    WHEN 'Karnali' THEN 'Karnali Province'
    WHEN 'Karnali Province' THEN 'Karnali Province'
    WHEN 'Sudurpashchim' THEN 'Sudurpashchim Province'
    WHEN 'Sudurpashchim Province' THEN 'Sudurpashchim Province'
    ELSE d.province
  END
GROUP BY p.id, canonical_district;

CREATE TABLE `crops_normalized` (
  `id` TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL UNIQUE,
  `display_name` VARCHAR(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `crops_normalized` (`id`, `name`, `display_name`)
SELECT `id`, `name`, `display_name` FROM `crops`;

CREATE TABLE `historical_yields_normalized` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `district_id` SMALLINT UNSIGNED NOT NULL,
  `crop_id` TINYINT UNSIGNED NOT NULL,
  `year` SMALLINT UNSIGNED NOT NULL,
  `yield_kg_ha` DECIMAL(10,2) NOT NULL,
  `rainfall_mm` DECIMAL(10,2) NULL,
  `avg_temp_c` DECIMAL(5,2) NULL,
  CONSTRAINT `fk_historical_normalized_district`
    FOREIGN KEY (`district_id`) REFERENCES `districts_normalized` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_historical_normalized_crop`
    FOREIGN KEY (`crop_id`) REFERENCES `crops_normalized` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `uq_historical_normalized_series`
    UNIQUE (`district_id`, `crop_id`, `year`),
  INDEX `idx_historical_normalized_crop` (`crop_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `historical_yields_normalized`
  (`id`, `district_id`, `crop_id`, `year`, `yield_kg_ha`, `rainfall_mm`, `avg_temp_c`)
SELECT
  h.id,
  d.id,
  c.id,
  h.year,
  h.yield_kg_ha,
  h.rainfall_mm,
  h.avg_temp_c
FROM historical_yields h
LEFT JOIN districts_normalized d
  ON d.name = CASE h.district_name
    WHEN 'Bhakthapur' THEN 'Bhaktapur'
    WHEN 'Nawalpur' THEN 'Nawalparasi East'
    WHEN 'Parasi' THEN 'Nawalparasi West'
    WHEN 'Eastern Rukum' THEN 'Rukum East'
    WHEN 'Western Rukum' THEN 'Rukum West'
    WHEN 'Makwanpur' THEN 'Makawanpur'
    ELSE h.district_name
  END
LEFT JOIN crops_normalized c ON c.name = h.crop_name
WHERE d.id IS NOT NULL AND c.id IS NOT NULL;

CREATE TABLE `predictions_normalized` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_session` VARCHAR(64) NULL,
  `district_id` SMALLINT UNSIGNED NOT NULL,
  `crop_id` TINYINT UNSIGNED NOT NULL,
  `area_ha` DECIMAL(10,2) NOT NULL,
  `climate_inputs` JSON NOT NULL,
  `predicted_yield` DECIMAL(10,2) NOT NULL,
  `model_version` VARCHAR(50) NOT NULL DEFAULT 'audit_v1',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_predictions_normalized_district`
    FOREIGN KEY (`district_id`) REFERENCES `districts_normalized` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_predictions_normalized_crop`
    FOREIGN KEY (`crop_id`) REFERENCES `crops_normalized` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  INDEX `idx_predictions_normalized_district_crop` (`district_id`, `crop_id`),
  INDEX `idx_predictions_normalized_created` (`created_at`),
  INDEX `idx_predictions_normalized_crop` (`crop_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `predictions_normalized`
  (`id`, `user_session`, `district_id`, `crop_id`, `area_ha`, `climate_inputs`, `predicted_yield`, `model_version`, `created_at`)
SELECT
  pr.id,
  pr.user_session,
  d.id,
  c.id,
  pr.area_ha,
  pr.climate_inputs,
  pr.predicted_yield,
  pr.model_version,
  pr.created_at
FROM predictions pr
LEFT JOIN districts_normalized d
  ON d.name = CASE pr.district
    WHEN 'Bhakthapur' THEN 'Bhaktapur'
    WHEN 'Nawalpur' THEN 'Nawalparasi East'
    WHEN 'Parasi' THEN 'Nawalparasi West'
    WHEN 'Eastern Rukum' THEN 'Rukum East'
    WHEN 'Western Rukum' THEN 'Rukum West'
    WHEN 'Makwanpur' THEN 'Makawanpur'
    ELSE pr.district
  END
LEFT JOIN crops_normalized c ON c.name = pr.crop
WHERE d.id IS NOT NULL AND c.id IS NOT NULL;

DELIMITER //
CREATE PROCEDURE `assert_normalization_ready`()
BEGIN
  IF (SELECT COUNT(*) FROM districts_normalized) <> 77 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Migration stopped: normalized district count is not 77';
  END IF;
  IF (SELECT COUNT(*) FROM crops_normalized) <> 6 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Migration stopped: normalized crop count is not 6';
  END IF;
  IF (SELECT COUNT(*) FROM historical_yields_normalized) <> (SELECT COUNT(*) FROM historical_yields) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Migration stopped: one or more historical rows could not be mapped';
  END IF;
  IF (SELECT COUNT(*) FROM predictions_normalized) <> (SELECT COUNT(*) FROM predictions) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Migration stopped: one or more predictions could not be mapped';
  END IF;

  RENAME TABLE
    `districts` TO `districts_legacy`,
    `crops` TO `crops_legacy`,
    `historical_yields` TO `historical_yields_legacy`,
    `predictions` TO `predictions_legacy`,
    `provinces_normalized` TO `provinces`,
    `districts_normalized` TO `districts`,
    `crops_normalized` TO `crops`,
    `historical_yields_normalized` TO `historical_yields`,
    `predictions_normalized` TO `predictions`;

  DROP TABLE `historical_yields_legacy`;
  DROP TABLE `predictions_legacy`;
  DROP TABLE `districts_legacy`;
  DROP TABLE `crops_legacy`;
END//
DELIMITER ;

CALL `assert_normalization_ready`();
DROP PROCEDURE `assert_normalization_ready`;

SELECT
  (SELECT COUNT(*) FROM provinces) AS provinces,
  (SELECT COUNT(*) FROM districts) AS districts,
  (SELECT COUNT(*) FROM crops) AS crops,
  (SELECT COUNT(*) FROM historical_yields) AS historical_rows,
  (SELECT COUNT(*) FROM predictions) AS prediction_rows;
