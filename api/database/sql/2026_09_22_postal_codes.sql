-- Códigos postales mundiales (fuente: GeoNames CC BY 4.0 — https://www.geonames.org/)
-- Relación lógica: country_iso2 → countries.sortname; state/city por nombre o IDs opcionales.

CREATE TABLE IF NOT EXISTS `postal_codes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `country_iso2` char(2) NOT NULL,
  `postal_code` varchar(20) NOT NULL,
  `place_name` varchar(180) NOT NULL,
  `admin_name1` varchar(100) DEFAULT NULL,
  `admin_code1` varchar(20) DEFAULT NULL,
  `admin_name2` varchar(100) DEFAULT NULL,
  `admin_code2` varchar(20) DEFAULT NULL,
  `admin_name3` varchar(100) DEFAULT NULL,
  `admin_code3` varchar(20) DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `accuracy` tinyint(3) unsigned DEFAULT NULL,
  `country_id` bigint(20) unsigned DEFAULT NULL,
  `state_id` bigint(20) unsigned DEFAULT NULL,
  `city_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `postal_codes_country_iso2_postal_index` (`country_iso2`, `postal_code`),
  KEY `postal_codes_country_place_index` (`country_iso2`, `place_name`(100)),
  KEY `postal_codes_country_admin1_place_index` (`country_iso2`, `admin_name1`(80), `place_name`(80)),
  KEY `postal_codes_country_id_index` (`country_id`),
  KEY `postal_codes_state_id_index` (`state_id`),
  KEY `postal_codes_city_id_index` (`city_id`),
  CONSTRAINT `postal_codes_country_id_foreign` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`) ON DELETE SET NULL,
  CONSTRAINT `postal_codes_state_id_foreign` FOREIGN KEY (`state_id`) REFERENCES `states` (`id`) ON DELETE SET NULL,
  CONSTRAINT `postal_codes_city_id_foreign` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
