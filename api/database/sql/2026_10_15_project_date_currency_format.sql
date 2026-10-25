-- Estándar de proyecto (Company Profile mockup):
-- Fecha: MM/DD/YYYY (12/31/2026)
-- Moneda/número: $ 1,234.56 (group `,` radix `.` digits 2)

-- format_numeric: formato US como default
UPDATE `format_numeric` SET `default` = NULL WHERE `default` = 1;
UPDATE `format_numeric`
SET `numeric_format` = '1,234.56',
    `radixPoint` = '.',
    `groupSeparator` = ',',
    `digits` = 2,
    `default` = 1,
    `code_regional` = 'en_US'
WHERE `format_id` = 1;

INSERT INTO `format_numeric` (`format_id`, `numeric_format`, `radixPoint`, `groupSeparator`, `digits`, `default`, `code_regional`, `created_at`, `updated_at`, `deleted_at`)
SELECT 1, '1,234.56', '.', ',', 2, 1, 'en_US', NULL, NULL, NULL
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `format_numeric` WHERE `format_id` = 1);

-- format_date: MM/DD/YYYY como default
UPDATE `format_date` SET `default` = NULL WHERE `default` = '1' OR `default` = 1;
UPDATE `format_date`
SET `format_php` = 'm/d/Y',
    `format_js` = 'mm/dd/yy',
    `date_firstmonth` = '01/m/Y',
    `month_year` = 'm/Y',
    `default` = '1'
WHERE `id` = 1;

INSERT INTO `format_date` (`id`, `format_php`, `format_js`, `language_id`, `date_firstmonth`, `default`, `month_year`, `created_at`, `updated_at`, `deleted_at`)
SELECT 1, 'm/d/Y', 'mm/dd/yy', 1, '01/m/Y', '1', 'm/Y', NULL, NULL, NULL
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `format_date` WHERE `id` = 1);

-- Empresas sin formato numérico / con el antiguo default: asignar el estándar US
UPDATE `companies` SET `format_id` = 1 WHERE `format_id` IS NULL OR `format_id` = 0 OR `format_id` = 4;
