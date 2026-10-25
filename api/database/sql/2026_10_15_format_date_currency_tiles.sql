-- Selectores de formato fecha/moneda (Company Profile tiles)
-- Fecha default: MM/DD/YYYY | Número default: 1,234.56

ALTER TABLE `companies`
  ADD COLUMN IF NOT EXISTS `format_date_id` int(11) DEFAULT 1 AFTER `format_id`;

DELETE FROM `format_date`;
INSERT INTO `format_date` (`id`, `format_php`, `format_js`, `language_id`, `date_firstmonth`, `default`, `month_year`) VALUES
(1,'d/m/Y','dd/mm/yy',2,'01/m/Y',NULL,'m/Y'),
(2,'d-m-Y','dd-mm-yy',NULL,'01-m-Y',NULL,'m-Y'),
(3,'d.m.Y','dd.mm.yy',NULL,'01.m.Y',NULL,'m.Y'),
(4,'m/d/Y','mm/dd/yy',1,'m/01/Y','1','m/Y'),
(5,'m-d-Y','mm-dd-yy',NULL,'m-01-Y',NULL,'m-Y'),
(6,'Y-m-d','yy-mm-dd',NULL,'Y-m-01',NULL,'Y-m'),
(7,'Y/m/d','yy/mm/dd',NULL,'Y/m/01',NULL,'Y/m'),
(8,'Y.m.d','yy.mm.dd',NULL,'Y.m.01',NULL,'Y.m'),
(9,'d/m/y','dd/mm/y',NULL,'01/m/y',NULL,'m/y'),
(10,'d M Y','d M yy',NULL,'01 M Y',NULL,'M Y'),
(11,'M j, Y','M d, yy',NULL,'M 1, Y',NULL,'M Y'),
(12,'d \\d\\e M \\d\\e Y','d de M de yy',2,'01 \\d\\e M \\d\\e Y',NULL,'M Y'),
(13,'Y年n月j日','yy年m月d日',NULL,'Y年n月1日',NULL,'Y年n月');

DELETE FROM `format_numeric`;
INSERT INTO `format_numeric` (`format_id`, `numeric_format`, `radixPoint`, `groupSeparator`, `digits`, `default`, `code_regional`) VALUES
(1,'1,234.56','.',',',2,1,'en_US'),
(2,'1.234,56',',','.',2,NULL,'de_DE'),
(3,'1 234,56',',',' ',2,NULL,'fr_FR'),
(4,'1''234.56','.','''',2,NULL,'de_CH'),
(5,'1234.56','.','',2,NULL,'en_US_nogroup'),
(6,'12,34,567.89','.',',',2,NULL,'en_IN');

UPDATE `companies` SET `format_date_id` = 4 WHERE `format_date_id` IS NULL OR `format_date_id` = 0;
UPDATE `companies` SET `format_id` = 1 WHERE `format_id` IS NULL OR `format_id` = 0;
