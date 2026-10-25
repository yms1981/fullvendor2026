/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19  Distrib 10.11.13-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: mariadb    Database: appfv
-- ------------------------------------------------------
-- Server version	10.11.15-MariaDB-ubu2204

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Dumping data for table `format_date`
--

/*!40000 ALTER TABLE `format_date` DISABLE KEYS */;
INSERT IGNORE INTO `format_date` (`id`, `format_php`, `format_js`, `language_id`, `date_firstmonth`, `default`, `month_year`, `created_at`, `updated_at`, `deleted_at`) VALUES (1,'d/m/Y','dd/mm/yy',2,'01/m/Y',NULL,'m/Y',NULL,NULL,NULL),
(2,'d-m-Y','dd-mm-yy',NULL,'01-m-Y',NULL,'m-Y',NULL,NULL,NULL),
(3,'d.m.Y','dd.mm.yy',NULL,'01.m.Y',NULL,'m.Y',NULL,NULL,NULL),
(4,'m/d/Y','mm/dd/yy',1,'m/01/Y','1','m/Y',NULL,NULL,NULL),
(5,'m-d-Y','mm-dd-yy',NULL,'m-01-Y',NULL,'m-Y',NULL,NULL,NULL),
(6,'Y-m-d','yy-mm-dd',NULL,'Y-m-01',NULL,'Y-m',NULL,NULL,NULL),
(7,'Y/m/d','yy/mm/dd',NULL,'Y/m/01',NULL,'Y/m',NULL,NULL,NULL),
(8,'Y.m.d','yy.mm.dd',NULL,'Y.m.01',NULL,'Y.m',NULL,NULL,NULL),
(9,'d/m/y','dd/mm/y',NULL,'01/m/y',NULL,'m/y',NULL,NULL,NULL),
(10,'d M Y','d M yy',NULL,'01 M Y',NULL,'M Y',NULL,NULL,NULL),
(11,'M j, Y','M d, yy',NULL,'M 1, Y',NULL,'M Y',NULL,NULL,NULL),
(12,'d \\d\\e M \\d\\e Y','d de M de yy',2,'01 \\d\\e M \\d\\e Y',NULL,'M Y',NULL,NULL,NULL),
(13,'Y年n月j日','yy年m月d日',NULL,'Y年n月1日',NULL,'Y年n月',NULL,NULL,NULL);
/*!40000 ALTER TABLE `format_date` ENABLE KEYS */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-24  1:26:11
