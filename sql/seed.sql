-- MySQL Seed Data for Nepal Crop Yield Prediction Web Application
SET NAMES utf8mb4;
USE `nepal_crop_yield`;

-- 1. Provision administrators separately; see SECURITY.md.

-- 2. Provinces
INSERT INTO `provinces` (`name`) VALUES
('Koshi Province'),
('Madhesh Province'),
('Bagmati Province'),
('Gandaki Province'),
('Lumbini Province'),
('Karnali Province'),
('Sudurpashchim Province')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

-- 3. Crops
INSERT INTO `crops` (`name`, `display_name`) VALUES
('Barley', 'Barley (जौ)'),
('Buckwheat', 'Buckwheat (फापर)'),
('Maize', 'Maize (मकै)'),
('Millet', 'Millet (कोदो)'),
('Paddy', 'Paddy / Rice (धान)'),
('Wheat', 'Wheat (गहुँ)')
ON DUPLICATE KEY UPDATE `display_name`=VALUES(`display_name`);

-- 4. Districts across 7 provinces. The temporary table keeps seed data readable;
-- the persistent table stores only the province foreign key.
DROP TEMPORARY TABLE IF EXISTS `district_seed`;
CREATE TEMPORARY TABLE `district_seed` (
  `name` VARCHAR(50) NOT NULL,
  `province_name` VARCHAR(50) NOT NULL,
  `latitude` DECIMAL(8,5) NOT NULL,
  `longitude` DECIMAL(8,5) NOT NULL
);

INSERT INTO `district_seed` (`name`, `province_name`, `latitude`, `longitude`) VALUES
-- Province 1 (Koshi)
('Bhojpur', 'Koshi Province', 27.1700, 87.0500),
('Dhankuta', 'Koshi Province', 26.9800, 87.3300),
('Ilam', 'Koshi Province', 26.9100, 87.9200),
('Jhapa', 'Koshi Province', 26.6400, 87.9900),
('Khotang', 'Koshi Province', 27.2000, 86.7900),
('Morang', 'Koshi Province', 26.6500, 87.3300),
('Okhaldhunga', 'Koshi Province', 27.3100, 86.5000),
('Panchthar', 'Koshi Province', 27.2000, 87.8300),
('Sankhuwasabha', 'Koshi Province', 27.5800, 87.2100),
('Solukhumbu', 'Koshi Province', 27.6700, 86.7000),
('Sunsari', 'Koshi Province', 26.6200, 87.1800),
('Taplejung', 'Koshi Province', 27.3500, 87.6700),
('Terhathum', 'Koshi Province', 27.1300, 87.5500),
('Udayapur', 'Koshi Province', 26.9300, 86.5200),

-- Madhesh Province
('Bara', 'Madhesh Province', 27.0500, 85.0500),
('Dhanusha', 'Madhesh Province', 26.7000, 85.9200),
('Mahottari', 'Madhesh Province', 26.8500, 85.8000),
('Parsa', 'Madhesh Province', 27.0300, 84.8700),
('Rautahat', 'Madhesh Province', 26.9300, 85.3200),
('Saptari', 'Madhesh Province', 26.6500, 86.7500),
('Sarlahi', 'Madhesh Province', 26.9700, 85.5500),
('Siraha', 'Madhesh Province', 26.6500, 86.2000),

-- Bagmati Province
('Bhaktapur', 'Bagmati Province', 27.6700, 85.4200),
('Chitwan', 'Bagmati Province', 27.5300, 84.4500),
('Dhading', 'Bagmati Province', 27.8700, 84.9000),
('Dolakha', 'Bagmati Province', 27.7700, 86.0700),
('Kathmandu', 'Bagmati Province', 27.7172, 85.3240),
('Kavrepalanchok', 'Bagmati Province', 27.5300, 85.5500),
('Lalitpur', 'Bagmati Province', 27.6500, 85.3200),
('Makawanpur', 'Bagmati Province', 27.4200, 85.0300),
('Nuwakot', 'Bagmati Province', 27.9100, 85.1600),
('Ramechhap', 'Bagmati Province', 27.3300, 86.0800),
('Rasuwa', 'Bagmati Province', 28.1200, 85.2800),
('Sindhuli', 'Bagmati Province', 27.2500, 85.9700),
('Sindhupalchok', 'Bagmati Province', 27.8500, 85.7300),

-- Gandaki Province
('Baglung', 'Gandaki Province', 28.2700, 83.6000),
('Gorkha', 'Gandaki Province', 28.0000, 84.6300),
('Kaski', 'Gandaki Province', 28.2300, 83.9800),
('Lamjung', 'Gandaki Province', 28.2300, 84.4000),
('Manang', 'Gandaki Province', 28.6000, 84.0200),
('Mustang', 'Gandaki Province', 28.7800, 83.7300),
('Myagdi', 'Gandaki Province', 28.4500, 83.5700),
('Nawalparasi East', 'Gandaki Province', 27.7000, 84.1500),
('Parbat', 'Gandaki Province', 28.2300, 83.6800),
('Syangja', 'Gandaki Province', 28.1000, 83.8700),
('Tanahu', 'Gandaki Province', 27.9700, 84.2800),

-- Lumbini Province
('Arghakhanchi', 'Lumbini Province', 27.9300, 83.0800),
('Banke', 'Lumbini Province', 28.1500, 81.7000),
('Bardiya', 'Lumbini Province', 28.3300, 81.3500),
('Dang', 'Lumbini Province', 28.1200, 82.3000),
('Rukum East', 'Lumbini Province', 28.6200, 82.6300),
('Gulmi', 'Lumbini Province', 28.0800, 83.2500),
('Kapilvastu', 'Lumbini Province', 27.5500, 83.0500),
('Palpa', 'Lumbini Province', 27.8700, 83.5500),
('Nawalparasi West', 'Lumbini Province', 27.5300, 83.6700),
('Pyuthan', 'Lumbini Province', 28.1000, 82.8700),
('Rolpa', 'Lumbini Province', 28.3500, 82.6300),
('Rupandehi', 'Lumbini Province', 27.5000, 83.4500),

-- Karnali Province
('Dailekh', 'Karnali Province', 28.8300, 81.7000),
('Dolpa', 'Karnali Province', 29.0000, 82.9000),
('Humla', 'Karnali Province', 29.9600, 81.8200),
('Jajarkot', 'Karnali Province', 28.7000, 82.2000),
('Jumla', 'Karnali Province', 29.2700, 82.1800),
('Kalikot', 'Karnali Province', 29.1500, 81.8200),
('Mugu', 'Karnali Province', 29.5500, 82.1700),
('Rukum West', 'Karnali Province', 28.6300, 82.4700),
('Salyan', 'Karnali Province', 28.3800, 82.1600),
('Surkhet', 'Karnali Province', 28.6000, 81.6300),

-- Sudurpashchim Province
('Achham', 'Sudurpashchim Province', 29.1200, 81.3000),
('Baitadi', 'Sudurpashchim Province', 29.5200, 80.5700),
('Bajhang', 'Sudurpashchim Province', 29.5500, 81.2000),
('Bajura', 'Sudurpashchim Province', 29.5000, 81.5500),
('Dadeldhura', 'Sudurpashchim Province', 29.3000, 80.5800),
('Darchula', 'Sudurpashchim Province', 29.8500, 80.5500),
('Doti', 'Sudurpashchim Province', 29.2700, 80.9500),
('Kailali', 'Sudurpashchim Province', 28.7000, 80.6000),
('Kanchanpur', 'Sudurpashchim Province', 28.9200, 80.3300);

INSERT INTO `districts` (`name`, `province_id`, `latitude`, `longitude`)
SELECT ds.`name`, p.`id`, ds.`latitude`, ds.`longitude`
FROM `district_seed` ds
JOIN `provinces` p ON p.`name` = ds.`province_name`
ON DUPLICATE KEY UPDATE
  `province_id`=VALUES(`province_id`),
  `latitude`=VALUES(`latitude`),
  `longitude`=VALUES(`longitude`);

DROP TEMPORARY TABLE `district_seed`;

-- 5. Historical yield sample. Names exist only in the temporary staging table;
-- the persistent records use district and crop foreign keys.
DROP TEMPORARY TABLE IF EXISTS `historical_seed`;
CREATE TEMPORARY TABLE `historical_seed` (
  `district_name` VARCHAR(50) NOT NULL,
  `crop_name` VARCHAR(50) NOT NULL,
  `year` SMALLINT UNSIGNED NOT NULL,
  `yield_kg_ha` DECIMAL(10,2) NOT NULL,
  `rainfall_mm` DECIMAL(10,2) NULL,
  `avg_temp_c` DECIMAL(5,2) NULL
);

INSERT INTO `historical_seed` (`district_name`, `crop_name`, `year`, `yield_kg_ha`, `rainfall_mm`, `avg_temp_c`) VALUES
-- Jhapa Paddy & Wheat
('Jhapa', 'Paddy', 2016, 3350.00, 1420.5, 24.2),
('Jhapa', 'Paddy', 2017, 3410.00, 1390.0, 24.5),
('Jhapa', 'Paddy', 2018, 3500.00, 1510.2, 24.1),
('Jhapa', 'Paddy', 2019, 3620.00, 1480.0, 24.8),
('Jhapa', 'Paddy', 2020, 3580.00, 1430.8, 24.6),
('Jhapa', 'Paddy', 2021, 3690.00, 1550.0, 24.3),
('Jhapa', 'Paddy', 2022, 3750.00, 1520.4, 24.9),

('Jhapa', 'Wheat', 2016, 2400.00, 120.0, 18.2),
('Jhapa', 'Wheat', 2017, 2450.00, 110.5, 18.5),
('Jhapa', 'Wheat', 2018, 2520.00, 135.0, 18.1),
('Jhapa', 'Wheat', 2019, 2580.00, 125.4, 18.4),
('Jhapa', 'Wheat', 2020, 2610.00, 118.0, 18.6),
('Jhapa', 'Wheat', 2021, 2680.00, 140.2, 18.3),
('Jhapa', 'Wheat', 2022, 2720.00, 130.0, 18.7),

-- Chitwan Paddy, Maize & Wheat
('Chitwan', 'Paddy', 2016, 3500.00, 1600.0, 23.5),
('Chitwan', 'Paddy', 2017, 3580.00, 1550.0, 23.8),
('Chitwan', 'Paddy', 2018, 3650.00, 1680.0, 23.4),
('Chitwan', 'Paddy', 2019, 3720.00, 1620.0, 23.9),
('Chitwan', 'Paddy', 2020, 3780.00, 1590.0, 24.0),
('Chitwan', 'Paddy', 2021, 3850.00, 1710.0, 23.6),
('Chitwan', 'Paddy', 2022, 3900.00, 1650.0, 24.1),

('Chitwan', 'Maize', 2016, 2900.00, 1400.0, 23.0),
('Chitwan', 'Maize', 2017, 3010.00, 1380.0, 23.3),
('Chitwan', 'Maize', 2018, 3100.00, 1450.0, 23.1),
('Chitwan', 'Maize', 2019, 3180.00, 1420.0, 23.5),
('Chitwan', 'Maize', 2020, 3250.00, 1390.0, 23.7),
('Chitwan', 'Maize', 2021, 3320.00, 1480.0, 23.2),
('Chitwan', 'Maize', 2022, 3400.00, 1440.0, 23.8),

-- Kaski Paddy & Maize
('Kaski', 'Paddy', 2016, 3100.00, 2800.0, 20.5),
('Kaski', 'Paddy', 2017, 3150.00, 2750.0, 20.8),
('Kaski', 'Paddy', 2018, 3220.00, 2900.0, 20.4),
('Kaski', 'Paddy', 2019, 3290.00, 2820.0, 20.9),
('Kaski', 'Paddy', 2020, 3340.00, 2780.0, 21.0),
('Kaski', 'Paddy', 2021, 3410.00, 2950.0, 20.6),
('Kaski', 'Paddy', 2022, 3470.00, 2880.0, 21.1),

('Kaski', 'Millet', 2016, 1150.00, 2400.0, 19.5),
('Kaski', 'Millet', 2017, 1180.00, 2350.0, 19.8),
('Kaski', 'Millet', 2018, 1210.00, 2500.0, 19.4),
('Kaski', 'Millet', 2019, 1250.00, 2420.0, 19.9),
('Kaski', 'Millet', 2020, 1280.00, 2380.0, 20.0),
('Kaski', 'Millet', 2021, 1310.00, 2550.0, 19.6),
('Kaski', 'Millet', 2022, 1350.00, 2480.0, 20.1),

-- Jumla Barley & Buckwheat
('Jumla', 'Barley', 2016, 1250.00, 750.0, 12.5),
('Jumla', 'Barley', 2017, 1280.00, 720.0, 12.8),
('Jumla', 'Barley', 2018, 1320.00, 800.0, 12.3),
('Jumla', 'Barley', 2019, 1360.00, 760.0, 12.7),
('Jumla', 'Barley', 2020, 1390.00, 740.0, 13.0),
('Jumla', 'Barley', 2021, 1430.00, 820.0, 12.4),
('Jumla', 'Barley', 2022, 1460.00, 790.0, 12.9),

('Jumla', 'Buckwheat', 2016, 980.00, 680.0, 12.0),
('Jumla', 'Buckwheat', 2017, 1010.00, 650.0, 12.3),
('Jumla', 'Buckwheat', 2018, 1050.00, 710.0, 11.9),
('Jumla', 'Buckwheat', 2019, 1080.00, 690.0, 12.4),
('Jumla', 'Buckwheat', 2020, 1110.00, 670.0, 12.6),
('Jumla', 'Buckwheat', 2021, 1140.00, 730.0, 12.1),
('Jumla', 'Buckwheat', 2022, 1170.00, 700.0, 12.5);

INSERT INTO `historical_yields`
  (`district_id`, `crop_id`, `year`, `yield_kg_ha`, `rainfall_mm`, `avg_temp_c`)
SELECT d.`id`, c.`id`, hs.`year`, hs.`yield_kg_ha`, hs.`rainfall_mm`, hs.`avg_temp_c`
FROM `historical_seed` hs
JOIN `districts` d ON d.`name` = hs.`district_name`
JOIN `crops` c ON c.`name` = hs.`crop_name`
ON DUPLICATE KEY UPDATE
  `yield_kg_ha`=VALUES(`yield_kg_ha`),
  `rainfall_mm`=VALUES(`rainfall_mm`),
  `avg_temp_c`=VALUES(`avg_temp_c`);

DROP TEMPORARY TABLE `historical_seed`;

-- 6. Information Posts Seed
INSERT INTO `posts` (`title`, `body`, `source_name`, `source_url`, `category`, `published_at`, `created_by`) VALUES
('Overview of Cereal Crop Yield Dynamics in Terai and Hill Regions',
'Statistical analysis of Nepal\'s agricultural statistics demonstrates distinct yield patterns between Terai plains and Hill ecological zones. Terai districts benefit from flatter topography and higher irrigation density, achieving higher per-hectare yields for cereal crops such as Paddy and Wheat, while Hill and Mountain regions excel in resilient crops including Buckwheat, Barley, and Millet.',
'Ministry of Agriculture and Livestock Development (MoALD)',
'https://moald.gov.np/',
'Agro-Climatic',
'2024-01-15',
NULL),

('Role of Agro-Meteorological Parameters in Yield Prediction Modeling',
'Climate variables including total seasonal rainfall, daily mean temperatures, solar radiation, and relative humidity directly correlate with crop evapotranspiration and biomass accumulation. Satellite reanalysis data provided by NASA POWER enables high-resolution spatial modeling across Nepal\'s diverse micro-climates.',
'NASA POWER Project',
'https://power.larc.nasa.gov/',
'Soil & Moisture',
'2024-02-10',
NULL),

('Systematic Crop Calendar and Seasonality in Nepalese Agriculture',
'Paddy is typically transplanted during the monsoon season (June to July) and harvested in late autumn (October to November). Wheat and Barley serve as primary winter crops (November to April), whereas Maize is widely cultivated as a summer or spring crop depending on altitude.',
'ICIMOD Regional Database',
'https://www.icimod.org/',
'Crop Management',
'2024-03-01',
NULL);
