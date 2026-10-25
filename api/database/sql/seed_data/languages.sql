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
-- Dumping data for table `languages`
--

/*!40000 ALTER TABLE `languages` DISABLE KEYS */;
INSERT IGNORE INTO `languages` (`language_id`, `name`, `iso_639_1`, `created_at`, `status`, `type`, `default`, `updated_at`, `deleted_at`) VALUES (1,'Inglés','en','2025-11-21 18:36:57',1,'eng',NULL,'2026-07-02 10:42:49',NULL),
(2,'Español','es','2025-11-21 18:36:57',1,'spa',NULL,'2026-07-02 10:42:49',NULL),
(3,'Portugués','pt','2026-07-02 10:42:49',1,'por',NULL,'2026-07-02 10:42:49',NULL),
(4,'Italiano','it','2026-07-02 10:42:49',1,'ita',NULL,'2026-07-02 10:42:49',NULL),
(5,'Ruso','ru','2026-07-02 10:42:49',1,'rus',NULL,'2026-07-02 10:42:49',NULL),
(6,'Alemán','de','2026-07-02 10:42:49',1,'deu',NULL,'2026-07-02 10:42:49',NULL),
(7,'Chino','zh','2026-07-02 10:42:49',1,'zho',NULL,'2026-07-02 10:42:49',NULL);
/*!40000 ALTER TABLE `languages` ENABLE KEYS */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-24  1:26:11
