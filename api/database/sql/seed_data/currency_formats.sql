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
-- Dumping data for table `currency_formats`
--

/*!40000 ALTER TABLE `currency_formats` DISABLE KEYS */;
INSERT IGNORE INTO `currency_formats` (`CurrencyName`, `CurrencyCode`, `CurrencyFormat`, `currOutput`, `CodeRegional`, `format_id`, `order`, `created_at`, `updated_at`, `deleted_at`) VALUES ('Bulgarian Lev','BGN','### ### ###,##','123 000,50','BG_BG.UTF-8',10,3,NULL,NULL,NULL),
('Euro','EUR','###\'###\'###.## ?','123\'000.50','de_CH',22,4,NULL,NULL,NULL),
('Euro','EUR','? ###,###,###.##','123,000.50','en_US',25,2,NULL,NULL,NULL),
('Indian Rupee','INR','? ##,##,##,###.##','12,34,000.50','HI_IN',35,5,NULL,NULL,NULL),
('United States Dollar','USD','$ #########.##','123000.50',NULL,104,6,NULL,NULL,NULL),
('United States Dollar','USD','$###.###.###,##','123.000,50','es-CL',109,1,NULL,NULL,NULL);
/*!40000 ALTER TABLE `currency_formats` ENABLE KEYS */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-24  1:26:11
