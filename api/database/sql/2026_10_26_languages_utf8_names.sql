-- Nombres de idioma guardados con UTF-8 doble (InglÃ©s). Restaura el catálogo de seed_data/languages.sql.
UPDATE `languages` SET `name` = CONVERT(UNHEX('496E676CC3A973') USING utf8mb4) WHERE `language_id` = 1;
UPDATE `languages` SET `name` = CONVERT(UNHEX('45737061C3B16F6C') USING utf8mb4) WHERE `language_id` = 2;
UPDATE `languages` SET `name` = CONVERT(UNHEX('506F7274756775C3A973') USING utf8mb4) WHERE `language_id` = 3;
UPDATE `languages` SET `name` = CONVERT(UNHEX('4974616C69616E6F') USING utf8mb4) WHERE `language_id` = 4;
UPDATE `languages` SET `name` = CONVERT(UNHEX('5275736F') USING utf8mb4) WHERE `language_id` = 5;
UPDATE `languages` SET `name` = CONVERT(UNHEX('416C656DC3A16E') USING utf8mb4) WHERE `language_id` = 6;
UPDATE `languages` SET `name` = CONVERT(UNHEX('4368696E6F') USING utf8mb4) WHERE `language_id` = 7;
