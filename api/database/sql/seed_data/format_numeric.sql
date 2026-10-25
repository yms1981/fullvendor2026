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
-- Dumping data for table `format_numeric`
--

/*!40000 ALTER TABLE `format_numeric` DISABLE KEYS */;
INSERT IGNORE INTO `format_numeric` (`format_id`, `numeric_format`, `radixPoint`, `groupSeparator`, `digits`, `default`, `code_regional`, `created_at`, `updated_at`, `deleted_at`) VALUES (1,'1,234.56','.',',',2,1,'en_US',NULL,NULL,NULL),
(2,'1.234,56',',','.',2,NULL,'de_DE',NULL,NULL,NULL),
(3,'1 234,56',',',' ',2,NULL,'fr_FR',NULL,NULL,NULL),
(4,'1\'234.56','.','\'',2,NULL,'de_CH',NULL,NULL,NULL),
(5,'1234.56','.','',2,NULL,'en_US_nogroup',NULL,NULL,NULL),
(6,'12,34,567.89','.',',',2,NULL,'en_IN',NULL,NULL,NULL);
/*!40000 ALTER TABLE `format_numeric` ENABLE KEYS */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-24  1:26:11
