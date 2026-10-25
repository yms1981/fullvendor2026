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
-- Dumping data for table `status_cart`
--

/*!40000 ALTER TABLE `status_cart` DISABLE KEYS */;
INSERT IGNORE INTO `status_cart` (`id_status`, `name_spa`, `name_eng`, `color`, `cod`, `class`, `selected_default`, `allow_toremove`, `multiple_cart`, `nosubmit_order`, `created_at`, `updated_at`, `deleted_at`) VALUES (1,'Enviado','Sent','#1EA910','1','text-success','1',1,0,0,NULL,NULL,NULL),
(2,'Visto','Viewed','#3498DB','2','text-info',NULL,1,0,0,NULL,NULL,NULL),
(3,'Abierto','Open','#F39C12','3','text-warning',NULL,1,0,0,NULL,NULL,NULL),
(4,'Completado','Completed','#CB4335','4','text-danger',NULL,0,1,1,NULL,NULL,NULL);
/*!40000 ALTER TABLE `status_cart` ENABLE KEYS */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-24  1:26:11
