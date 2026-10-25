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
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `Chat`
--

DROP TABLE IF EXISTS `Chat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Chat` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `idfrom` bigint(20) DEFAULT NULL,
  `idto` bigint(20) DEFAULT NULL,
  `tipo` int(11) DEFAULT NULL,
  `mensaje` longtext DEFAULT NULL,
  `fecha` datetime DEFAULT NULL,
  `company_id` bigint(20) DEFAULT NULL,
  `leido` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Temporary table structure for view `CurrencyCodes`
--

DROP TABLE IF EXISTS `CurrencyCodes`;
/*!50001 DROP VIEW IF EXISTS `CurrencyCodes`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `CurrencyCodes` AS SELECT
 1 AS `CurrencyCode` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `Valid_Img`
--

DROP TABLE IF EXISTS `Valid_Img`;
/*!50001 DROP VIEW IF EXISTS `Valid_Img`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `Valid_Img` AS SELECT
 1 AS `pro_id`,
  1 AS `img_id`,
  1 AS `product_id`,
  1 AS `language_id`,
  1 AS `images`,
  1 AS `created`,
  1 AS `updated`,
  1 AS `Img`,
  1 AS `sImgURL`,
  1 AS `ImgURL`,
  1 AS `id_kor`,
  1 AS `Description`,
  1 AS `company_id`,
  1 AS `sku`,
  1 AS `img_default` */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `address_company`
--

DROP TABLE IF EXISTS `address_company`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `address_company` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `address_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `address_company_address_id_foreign` (`address_id`),
  KEY `address_company_company_id_foreign` (`company_id`),
  CONSTRAINT `address_company_address_id_foreign` FOREIGN KEY (`address_id`) REFERENCES `addresses` (`id`),
  CONSTRAINT `address_company_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `address_customer`
--

DROP TABLE IF EXISTS `address_customer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `address_customer` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `address_id` bigint(20) unsigned NOT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `type` enum('commercial','dispatch','commercial_delivery') NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `address_customer_address_id_foreign` (`address_id`),
  KEY `address_customer_customer_id_foreign` (`customer_id`),
  CONSTRAINT `address_customer_address_id_foreign` FOREIGN KEY (`address_id`) REFERENCES `addresses` (`id`),
  CONSTRAINT `address_customer_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2564 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `addresses`
--

DROP TABLE IF EXISTS `addresses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `addresses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `street` varchar(255) NOT NULL DEFAULT '',
  `street_number` varchar(20) NOT NULL DEFAULT '',
  `country_id` bigint(20) unsigned DEFAULT NULL,
  `state_id` bigint(20) unsigned DEFAULT NULL,
  `city_id` bigint(20) unsigned DEFAULT NULL,
  `zone` varchar(100) DEFAULT NULL,
  `zipcode` varchar(20) NOT NULL DEFAULT '',
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `state` varchar(200) DEFAULT NULL,
  `city` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `addresses_country_id_foreign` (`country_id`),
  KEY `addresses_state_id_foreign` (`state_id`),
  KEY `addresses_city_id_foreign` (`city_id`),
  CONSTRAINT `addresses_city_id_foreign` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`),
  CONSTRAINT `addresses_country_id_foreign` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`),
  CONSTRAINT `addresses_state_id_foreign` FOREIGN KEY (`state_id`) REFERENCES `states` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2567 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `admin`
--

DROP TABLE IF EXISTS `admin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `admin` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `created` timestamp NULL DEFAULT NULL,
  `edited` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` smallint(6) DEFAULT 1 COMMENT '1(Active), 0(Deactive)',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `email` varchar(500) DEFAULT NULL,
  `username` varchar(500) DEFAULT NULL,
  `password` varchar(1000) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `admin_user_id_foreign` (`user_id`),
  CONSTRAINT `admin_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `app_updates`
--

DROP TABLE IF EXISTS `app_updates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `app_updates` (
  `fecha` datetime DEFAULT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `updatenumber` bigint(20) NOT NULL,
  `ID` bigint(20) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`ID`),
  KEY `app_updates_companies_company_id_fk` (`company_id`),
  KEY `id` (`ID`),
  CONSTRAINT `app_updates_companies_company_id_fk` FOREIGN KEY (`company_id`) REFERENCES `companies` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `banner`
--

DROP TABLE IF EXISTS `banner`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `banner` (
  `bannerId` bigint(20) NOT NULL AUTO_INCREMENT,
  `bannerHeading` varchar(255) DEFAULT NULL,
  `bannerDescription` text DEFAULT NULL,
  `bannerImage` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`bannerId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Temporary table structure for view `cartImages`
--

DROP TABLE IF EXISTS `cartImages`;
/*!50001 DROP VIEW IF EXISTS `cartImages`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `cartImages` AS SELECT
 1 AS `company_id`,
  1 AS `img_id`,
  1 AS `pro_id` */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `catalog`
--

DROP TABLE IF EXISTS `catalog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `catalog` (
  `catalog_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `catalog_name` varchar(255) NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `cat_id` text NOT NULL COMMENT 'pk of categories',
  `product_name` text DEFAULT NULL,
  `status` int(11) NOT NULL,
  `created` datetime DEFAULT NULL,
  `customer_emails` longtext DEFAULT NULL,
  `email_sent_on` datetime DEFAULT NULL,
  `attach` varchar(255) DEFAULT NULL,
  `dayexpired` int(11) NOT NULL DEFAULT 0,
  `default_catalog` int(11) DEFAULT NULL,
  `cabeza_pagina` varchar(255) DEFAULT NULL,
  `logo` uuid DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`catalog_id`),
  KEY `catalog_company_id_foreign` (`company_id`),
  KEY `catalog_logo_foreign` (`logo`),
  CONSTRAINT `catalog_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`company_id`),
  CONSTRAINT `catalog_logo_foreign` FOREIGN KEY (`logo`) REFERENCES `images` (`uuid`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `catalog_cart`
--

DROP TABLE IF EXISTS `catalog_cart`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `catalog_cart` (
  `cart_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `customer_id` bigint(20) DEFAULT NULL COMMENT 'Pk of customers',
  `product_id` bigint(20) DEFAULT NULL COMMENT 'PK of products',
  `qty` int(11) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `updated` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `token_car` varchar(255) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `company_id` int(11) DEFAULT NULL,
  `catalog_id` int(11) DEFAULT NULL,
  `expired_date` datetime DEFAULT NULL,
  `token_kor` text DEFAULT NULL,
  `section_address` int(11) DEFAULT NULL,
  `show_logofullvendor` int(11) DEFAULT NULL,
  `return_admin` int(11) DEFAULT NULL,
  `order_sent` int(11) DEFAULT NULL,
  `catalog_text` varchar(255) DEFAULT NULL,
  `view` int(11) DEFAULT 0,
  `status_cart` int(11) DEFAULT NULL,
  `login_seller` int(11) DEFAULT NULL,
  `disable_total` int(11) DEFAULT NULL,
  `type_cart` varchar(50) DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `father_id` int(11) DEFAULT NULL,
  `cart_number` varchar(50) DEFAULT NULL,
  `order_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`cart_id`),
  KEY `idx_catalog_cart_token_kor` (`token_kor`(768)),
  KEY `idx_catalog_cart_company_customer` (`company_id`,`customer_id`),
  KEY `idx_catalog_cart_catalog_id` (`catalog_id`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Carritos de catálogo (sesión por token para orden desde catálogo)';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `catalog_category`
--

DROP TABLE IF EXISTS `catalog_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `catalog_category` (
  `catalog_id` bigint(20) unsigned NOT NULL,
  `category_id` bigint(20) unsigned NOT NULL,
  `order` smallint(5) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`catalog_id`,`category_id`),
  UNIQUE KEY `unique_category_catalog` (`category_id`,`catalog_id`),
  UNIQUE KEY `unique_catalog_order` (`order`,`catalog_id`),
  CONSTRAINT `catalog_category_catalog_id_foreign` FOREIGN KEY (`catalog_id`) REFERENCES `catalog` (`catalog_id`),
  CONSTRAINT `catalog_category_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`cat_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `catalog_product`
--

DROP TABLE IF EXISTS `catalog_product`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `catalog_product` (
  `catalog_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  KEY `catalog_product_catalog_id_foreign` (`catalog_id`),
  KEY `catalog_product_product_id_foreign` (`product_id`),
  CONSTRAINT `catalog_product_catalog_id_foreign` FOREIGN KEY (`catalog_id`) REFERENCES `catalog` (`catalog_id`) ON DELETE CASCADE,
  CONSTRAINT `catalog_product_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`pro_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `catalog_request`
--

DROP TABLE IF EXISTS `catalog_request`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `catalog_request` (
  `request_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `descriptions` tinytext DEFAULT NULL,
  `created` datetime DEFAULT current_timestamp(),
  `updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` tinyint(4) NOT NULL DEFAULT 1,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `catalog_id` bigint(20) unsigned DEFAULT NULL,
  `access_count` int(11) NOT NULL DEFAULT 0,
  `token` text DEFAULT NULL,
  PRIMARY KEY (`request_id`),
  KEY `catalog_request_user_id_foreign` (`user_id`),
  KEY `catalog_request_customer_id_foreign` (`customer_id`),
  KEY `catalog_request_catalog_id_foreign` (`catalog_id`),
  CONSTRAINT `catalog_request_catalog_id_foreign` FOREIGN KEY (`catalog_id`) REFERENCES `catalog` (`catalog_id`),
  CONSTRAINT `catalog_request_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE,
  CONSTRAINT `catalog_request_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `catalog_sent_mail`
--

DROP TABLE IF EXISTS `catalog_sent_mail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `catalog_sent_mail` (
  `catalog_sent_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `catlog_reqest_id` bigint(20) NOT NULL,
  `hide_price` int(11) NOT NULL COMMENT '0=No,1=Yes',
  `catalog_id` bigint(20) unsigned NOT NULL,
  `catalog_text` varchar(1000) DEFAULT NULL,
  `catalog_send_type` varchar(255) DEFAULT NULL,
  `catalog_email_text` text DEFAULT NULL,
  `send_date` datetime DEFAULT NULL,
  `customer_emails` longtext DEFAULT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `expiration` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`catalog_sent_id`),
  KEY `catalog_sent_mail_catalog_id_foreign` (`catalog_id`),
  KEY `catalog_sent_mail_catlog_reqest_id_foreign` (`catlog_reqest_id`),
  KEY `catalog_sent_mail_company_id_foreign` (`company_id`),
  KEY `catalog_sent_mail_customer_id_foreign` (`customer_id`),
  CONSTRAINT `catalog_sent_mail_catalog_id_foreign` FOREIGN KEY (`catalog_id`) REFERENCES `catalog` (`catalog_id`),
  CONSTRAINT `catalog_sent_mail_catlog_reqest_id_foreign` FOREIGN KEY (`catlog_reqest_id`) REFERENCES `catalog_request` (`request_id`),
  CONSTRAINT `catalog_sent_mail_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`company_id`),
  CONSTRAINT `catalog_sent_mail_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `cat_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `category_name` varchar(255) DEFAULT NULL,
  `image` uuid DEFAULT NULL,
  `category_status` int(11) NOT NULL DEFAULT 1,
  `category_created_at` datetime DEFAULT NULL,
  `category_default` int(11) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`cat_id`),
  KEY `categories_company_id_foreign` (`company_id`),
  KEY `categories_image_foreign` (`image`),
  CONSTRAINT `categories_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`company_id`),
  CONSTRAINT `categories_image_foreign` FOREIGN KEY (`image`) REFERENCES `images` (`uuid`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `category_groups`
--

DROP TABLE IF EXISTS `category_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `category_groups` (
  `group_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `category_id` bigint(20) unsigned NOT NULL,
  `group_name` varchar(255) NOT NULL,
  `group_status` tinyint(4) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `image` uuid DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`group_id`),
  KEY `category_groups_company_id_foreign` (`company_id`),
  KEY `category_groups_category_id_foreign` (`category_id`),
  KEY `category_groups_image_foreign` (`image`),
  CONSTRAINT `category_groups_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`cat_id`) ON DELETE CASCADE,
  CONSTRAINT `category_groups_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`company_id`),
  CONSTRAINT `category_groups_image_foreign` FOREIGN KEY (`image`) REFERENCES `images` (`uuid`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `category_product`
--

DROP TABLE IF EXISTS `category_product`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `category_product` (
  `category_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`category_id`,`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `category_subgroups`
--

DROP TABLE IF EXISTS `category_subgroups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `category_subgroups` (
  `subgroup_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `group_id` bigint(20) unsigned NOT NULL,
  `subgroup_name` varchar(255) NOT NULL,
  `subgroup_status` tinyint(4) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `image` uuid DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`subgroup_id`),
  KEY `category_subgroups_company_id_foreign` (`company_id`),
  KEY `category_subgroups_group_id_foreign` (`group_id`),
  KEY `category_subgroups_image_foreign` (`image`),
  CONSTRAINT `category_subgroups_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`company_id`),
  CONSTRAINT `category_subgroups_group_id_foreign` FOREIGN KEY (`group_id`) REFERENCES `category_groups` (`group_id`) ON DELETE CASCADE,
  CONSTRAINT `category_subgroups_image_foreign` FOREIGN KEY (`image`) REFERENCES `images` (`uuid`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `changes_app`
--

DROP TABLE IF EXISTS `changes_app`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `changes_app` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `read` int(11) NOT NULL DEFAULT 0,
  `email` varchar(300) DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `changes_app_company_id_foreign` (`company_id`),
  KEY `changes_app_user_id_foreign` (`user_id`),
  CONSTRAINT `changes_app_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`company_id`),
  CONSTRAINT `changes_app_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cities`
--

DROP TABLE IF EXISTS `cities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cities` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(30) NOT NULL,
  `state_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cities_state_id_foreign` (`state_id`),
  CONSTRAINT `cities_state_id_foreign` FOREIGN KEY (`state_id`) REFERENCES `states` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=48367 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cms`
--

DROP TABLE IF EXISTS `cms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cms` (
  `pageId` bigint(20) NOT NULL AUTO_INCREMENT,
  `pageTitle` varchar(100) DEFAULT NULL,
  `pageText` tinytext DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL,
  `content` longtext DEFAULT NULL,
  `created` timestamp NULL DEFAULT NULL,
  `modified` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`pageId`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `companies`
--

DROP TABLE IF EXISTS `companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `companies` (
  `company_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_category_id` bigint(20) DEFAULT NULL,
  `language_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) DEFAULT ' ',
  `short_name` varchar(255) DEFAULT '',
  `email` varchar(255) DEFAULT '',
  `stripe_id` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT '',
  `phone_number` varchar(255) DEFAULT '',
  `cell_number` varchar(255) DEFAULT '',
  `fax` varchar(255) DEFAULT '',
  `image` varchar(255) DEFAULT '',
  `small_image` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `state` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `zip_code` varchar(255) DEFAULT NULL,
  `tax_condition` varchar(255) DEFAULT '',
  `tax_id` varchar(255) DEFAULT '',
  `max_number_of_clients` double DEFAULT 0,
  `max_number_of_orders` double DEFAULT 0,
  `max_number_of_vendors` double DEFAULT 0,
  `total_price` double DEFAULT 0,
  `website` varchar(255) DEFAULT '',
  `created` datetime DEFAULT NULL,
  `status` smallint(6) DEFAULT 1 COMMENT '0=InActive 1= Active\n',
  `slug_url` varchar(255) DEFAULT ' ',
  `currency` bigint(20) unsigned DEFAULT NULL,
  `createdby_comp_id` bigint(20) unsigned DEFAULT NULL,
  `token` text DEFAULT NULL,
  `ordersend_time` int(11) DEFAULT NULL,
  `catalogsend_time` int(11) DEFAULT NULL,
  `format_id` int(11) DEFAULT NULL,
  `format_date_id` int(11) DEFAULT 1,
  `deleted_at` datetime DEFAULT NULL,
  `sell_with_inventory` int(11) DEFAULT 1,
  `rol_id` bigint(20) unsigned DEFAULT NULL,
  `show_inventory` int(11) NOT NULL DEFAULT 0,
  `show_semaforo_stock` int(11) DEFAULT 0,
  `use_whatsapp` bit(1) DEFAULT NULL,
  `whatsapp_instance_name` varchar(255) DEFAULT NULL,
  `whatsapp_apikey` varchar(255) DEFAULT NULL,
  `email_client_on_salesman_order` tinyint(1) NOT NULL DEFAULT 1,
  `email_on_salesman_order` tinyint(1) NOT NULL DEFAULT 0,
  `publish_price_list` tinyint(1) NOT NULL DEFAULT 0,
  `show_plus_taxes_label` tinyint(1) NOT NULL,
  `sellers_ids` varchar(250) DEFAULT NULL,
  `sem_red_hasta` int(11) DEFAULT NULL,
  `sem_amarillo_desde` int(11) DEFAULT NULL,
  `sem_amarillo_hasta` int(11) DEFAULT NULL,
  `sem_verde_desde` int(11) DEFAULT NULL,
  `sem_verde_hasta` int(11) DEFAULT NULL,
  `sem_red` varchar(255) DEFAULT NULL COMMENT 'Label to replace red color',
  `sem_amarillo` varchar(255) DEFAULT NULL COMMENT 'Label to replace yellow color',
  `sem_verde` varchar(255) DEFAULT NULL COMMENT 'Label to replace green color',
  `plus_taxes_label` varchar(255) DEFAULT NULL COMMENT 'Customized label to replace ''Plus taxes''',
  `updated_at` timestamp NULL DEFAULT NULL,
  `default_unit_id` bigint(20) unsigned DEFAULT NULL,
  `default_term_id` bigint(20) unsigned DEFAULT NULL,
  `default_customer_group_id` bigint(20) unsigned DEFAULT NULL,
  `default_seller_id` bigint(20) unsigned DEFAULT NULL,
  `full_address` text DEFAULT NULL,
  `zone` varchar(191) DEFAULT NULL,
  PRIMARY KEY (`company_id`),
  KEY `companies_company_category_id_foreign` (`company_category_id`),
  KEY `companies_language_id_foreign` (`language_id`),
  KEY `companies_createdby_comp_id_foreign` (`createdby_comp_id`),
  KEY `companies_rol_id_foreign` (`rol_id`),
  KEY `companies_default_unit_id_foreign` (`default_unit_id`),
  KEY `companies_default_term_id_foreign` (`default_term_id`),
  KEY `companies_default_customer_group_id_foreign` (`default_customer_group_id`),
  KEY `companies_default_seller_id_foreign` (`default_seller_id`),
  KEY `companies_format_date_id_index` (`format_date_id`),
  CONSTRAINT `companies_company_category_id_foreign` FOREIGN KEY (`company_category_id`) REFERENCES `companies_categories` (`category_id`),
  CONSTRAINT `companies_createdby_comp_id_foreign` FOREIGN KEY (`createdby_comp_id`) REFERENCES `companies` (`company_id`),
  CONSTRAINT `companies_default_customer_group_id_foreign` FOREIGN KEY (`default_customer_group_id`) REFERENCES `customer_groups` (`group_id`) ON DELETE SET NULL,
  CONSTRAINT `companies_default_seller_id_foreign` FOREIGN KEY (`default_seller_id`) REFERENCES `sellers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `companies_default_term_id_foreign` FOREIGN KEY (`default_term_id`) REFERENCES `terms_of_sales` (`term_id`),
  CONSTRAINT `companies_default_unit_id_foreign` FOREIGN KEY (`default_unit_id`) REFERENCES `units` (`unit_id`),
  CONSTRAINT `companies_language_id_foreign` FOREIGN KEY (`language_id`) REFERENCES `languages` (`language_id`),
  CONSTRAINT `companies_rol_id_foreign` FOREIGN KEY (`rol_id`) REFERENCES `manager_profiles` (`rol_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `companies_categories`
--

DROP TABLE IF EXISTS `companies_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `companies_categories` (
  `category_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(255) DEFAULT NULL,
  `category_status` tinyint(4) DEFAULT 1,
  `category_created_at` datetime DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `company_contacts`
--

DROP TABLE IF EXISTS `company_contacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `company_contacts` (
  `c_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `cell_phone` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`c_id`),
  KEY `company_contacts_company_id_foreign` (`company_id`),
  CONSTRAINT `company_contacts_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`company_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `company_conversation_attachments`
--

DROP TABLE IF EXISTS `company_conversation_attachments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `company_conversation_attachments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `message_id` bigint(20) unsigned NOT NULL,
  `disk` varchar(50) NOT NULL DEFAULT 'minio',
  `path` varchar(500) NOT NULL,
  `mime_type` varchar(120) NOT NULL,
  `original_name` varchar(255) DEFAULT NULL,
  `size_bytes` bigint(20) unsigned NOT NULL DEFAULT 0,
  `duration_seconds` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `company_conversation_attachments_message_id_index` (`message_id`),
  CONSTRAINT `company_conversation_attachments_message_id_foreign` FOREIGN KEY (`message_id`) REFERENCES `company_conversation_messages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `company_conversation_messages`
--

DROP TABLE IF EXISTS `company_conversation_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `company_conversation_messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `conversation_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `message_type` enum('text','image','audio') NOT NULL DEFAULT 'text',
  `body` longtext DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `company_conversation_messages_conversation_created_index` (`conversation_id`,`created_at`),
  KEY `company_conversation_messages_user_id_index` (`user_id`),
  CONSTRAINT `company_conversation_messages_conversation_id_foreign` FOREIGN KEY (`conversation_id`) REFERENCES `company_conversations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `company_conversation_messages_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `company_conversation_participants`
--

DROP TABLE IF EXISTS `company_conversation_participants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `company_conversation_participants` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `conversation_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `role_type` enum('seller','warehouse','manager','customer') NOT NULL,
  `last_read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_conversation_participants_unique` (`conversation_id`,`user_id`),
  KEY `company_conversation_participants_user_id_index` (`user_id`),
  CONSTRAINT `company_conversation_participants_conversation_id_foreign` FOREIGN KEY (`conversation_id`) REFERENCES `company_conversations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `company_conversation_participants_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `company_conversations`
--

DROP TABLE IF EXISTS `company_conversations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `company_conversations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `context_type` varchar(50) DEFAULT NULL COMMENT 'Opcional: order, general, etc.',
  `context_id` bigint(20) unsigned DEFAULT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `last_message_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `company_conversations_company_id_index` (`company_id`),
  KEY `company_conversations_company_last_message_index` (`company_id`,`last_message_at`),
  KEY `company_conversations_context_index` (`context_type`,`context_id`),
  KEY `company_conversations_created_by_index` (`created_by`),
  CONSTRAINT `company_conversations_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`company_id`) ON DELETE CASCADE,
  CONSTRAINT `company_conversations_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `company_messaging_daily`
--

DROP TABLE IF EXISTS `company_messaging_daily`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `company_messaging_daily` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `stat_date` date NOT NULL,
  `email_count` int(10) unsigned NOT NULL DEFAULT 0,
  `sms_count` int(10) unsigned NOT NULL DEFAULT 0,
  `whatsapp_count` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_messaging_daily_company_date_unique` (`company_id`,`stat_date`),
  KEY `company_messaging_daily_stat_date_index` (`stat_date`),
  CONSTRAINT `company_messaging_daily_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`company_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `company_status_order`
--

DROP TABLE IF EXISTS `company_status_order`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `company_status_order` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cod_status` int(11) DEFAULT 0,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(50) DEFAULT NULL,
  `email` longtext DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `company_status_order_company_id_foreign` (`company_id`),
  CONSTRAINT `company_status_order_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `company_user`
--

DROP TABLE IF EXISTS `company_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `company_user` (
  `company_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `type` enum('customer','seller','manager','warehouse') NOT NULL,
  `profileable_id` bigint(20) unsigned DEFAULT NULL,
  `profileable_type` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`user_id`,`company_id`,`type`),
  KEY `company_user_company_id_foreign` (`company_id`),
  CONSTRAINT `company_user_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`company_id`),
  CONSTRAINT `company_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `consoleconfig`
--

DROP TABLE IF EXISTS `consoleconfig`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `consoleconfig` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `token` varchar(500) DEFAULT NULL,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `username` varchar(200) DEFAULT NULL,
  `password` varchar(200) DEFAULT NULL,
  `email` varchar(200) DEFAULT NULL,
  `address` varchar(500) DEFAULT NULL,
  `telephone` varchar(500) DEFAULT NULL,
  `app` varchar(500) DEFAULT NULL,
  `rol` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `consoleconfig_company_id_foreign` (`company_id`),
  CONSTRAINT `consoleconfig_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `consoleusers`
--

DROP TABLE IF EXISTS `consoleusers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `consoleusers` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `email` varchar(500) NOT NULL,
  `password` varchar(500) DEFAULT NULL,
  `rol` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `countries`
--

DROP TABLE IF EXISTS `countries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `countries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sortname` varchar(3) NOT NULL,
  `name` varchar(150) NOT NULL,
  `phonecode` int(11) NOT NULL,
  `time_zone` varchar(50) NOT NULL DEFAULT 'America/Lima',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `phonemask` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=247 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `currency`
--

DROP TABLE IF EXISTS `currency`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `currency` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `code` varchar(10) NOT NULL,
  `symbol` varchar(10) NOT NULL,
  `tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tags`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `company_id` int(11) NOT NULL,
  `status` int(11) NOT NULL DEFAULT 1,
  `country` varchar(100) DEFAULT NULL,
  `currency` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=87 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `currency_formats`
--

DROP TABLE IF EXISTS `currency_formats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `currency_formats` (
  `CurrencyName` varchar(100) NOT NULL,
  `CurrencyCode` varchar(100) NOT NULL,
  `CurrencyFormat` varchar(100) NOT NULL,
  `currOutput` varchar(100) DEFAULT NULL,
  `CodeRegional` varchar(100) DEFAULT NULL,
  `format_id` int(11) NOT NULL AUTO_INCREMENT,
  `order` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`format_id`)
) ENGINE=InnoDB AUTO_INCREMENT=110 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `currencybycountries`
--

DROP TABLE IF EXISTS `currencybycountries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `currencybycountries` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `GroupID` varchar(300) DEFAULT NULL,
  `Name` varchar(300) DEFAULT NULL,
  `Contry` longtext DEFAULT NULL,
  `Code` varchar(50) DEFAULT NULL,
  `Symbol` varchar(50) DEFAULT NULL,
  `GroupIdspa` varchar(255) DEFAULT NULL,
  `Namespa` varchar(255) DEFAULT NULL,
  `default` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  FULLTEXT KEY `GroupID_Name` (`GroupID`,`Name`)
) ENGINE=InnoDB AUTO_INCREMENT=87 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `customer_emails`
--

DROP TABLE IF EXISTS `customer_emails`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_emails` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint(20) unsigned NOT NULL,
  `email` varchar(255) NOT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customer_emails_customer_email_unique` (`customer_id`,`email`),
  KEY `customer_emails_customer_id_index` (`customer_id`),
  CONSTRAINT `customer_emails_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `customer_groups`
--

DROP TABLE IF EXISTS `customer_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_groups` (
  `group_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `percentage_on_price` enum('Increase on Price List %','Decrease on Price List %') DEFAULT NULL,
  `percent_price_amount` double DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `group_status` tinyint(4) NOT NULL DEFAULT 1,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`group_id`),
  KEY `customer_groups_company_id_foreign` (`company_id`),
  CONSTRAINT `customer_groups_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`company_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `customer_order_product_fifo`
--

DROP TABLE IF EXISTS `customer_order_product_fifo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_order_product_fifo` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `sku` varchar(128) DEFAULT NULL,
  `source_order_id` bigint(20) unsigned NOT NULL COMMENT 'Orden venta origen (tipo_d=D)',
  `source_detail_id` bigint(20) NOT NULL COMMENT 'Línea de order_details de la venta',
  `source_order_number` varchar(64) DEFAULT NULL,
  `sale_price` decimal(18,4) NOT NULL DEFAULT 0.0000 COMMENT 'Precio de venta de la capa; tope para NC',
  `qty_sold` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `qty_credited` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `qty_remaining` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `opened_at` datetime NOT NULL COMMENT 'Fecha de la venta para orden FIFO ASC',
  `status` varchar(16) NOT NULL DEFAULT 'open' COMMENT 'open|exhausted|cancelled',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `fifo_source_detail_unique` (`source_detail_id`),
  KEY `fifo_company_customer_product_opened` (`company_id`,`customer_id`,`product_id`,`opened_at`),
  KEY `fifo_company_product_remaining` (`company_id`,`product_id`,`qty_remaining`,`opened_at`),
  KEY `fifo_source_order_id` (`source_order_id`),
  KEY `fifo_customer_id` (`customer_id`),
  KEY `fifo_product_id` (`product_id`),
  CONSTRAINT `fifo_company_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`company_id`) ON DELETE CASCADE,
  CONSTRAINT `fifo_customer_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE,
  CONSTRAINT `fifo_product_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`pro_id`) ON DELETE CASCADE,
  CONSTRAINT `fifo_source_detail_foreign` FOREIGN KEY (`source_detail_id`) REFERENCES `order_details` (`detail_id`) ON DELETE CASCADE,
  CONSTRAINT `fifo_source_order_foreign` FOREIGN KEY (`source_order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=61122 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Capas FIFO vendido/devuelto por cliente-producto-orden';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `customer_order_product_fifo_allocations`
--

DROP TABLE IF EXISTS `customer_order_product_fifo_allocations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_order_product_fifo_allocations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `fifo_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `credit_order_id` bigint(20) unsigned NOT NULL COMMENT 'Nota de crédito (tipo_d=C)',
  `credit_detail_id` bigint(20) NOT NULL,
  `credit_order_number` varchar(64) DEFAULT NULL,
  `qty` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `credit_price` decimal(18,4) NOT NULL DEFAULT 0.0000 COMMENT 'Precio devolución (<= sale_price capa)',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fifo_alloc_fifo_id` (`fifo_id`),
  KEY `fifo_alloc_credit_order` (`credit_order_id`),
  KEY `fifo_alloc_credit_detail` (`credit_detail_id`),
  KEY `fifo_alloc_company_customer_product` (`company_id`,`customer_id`,`product_id`),
  CONSTRAINT `fifo_alloc_company_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`company_id`) ON DELETE CASCADE,
  CONSTRAINT `fifo_alloc_credit_detail_foreign` FOREIGN KEY (`credit_detail_id`) REFERENCES `order_details` (`detail_id`) ON DELETE CASCADE,
  CONSTRAINT `fifo_alloc_credit_order_foreign` FOREIGN KEY (`credit_order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  CONSTRAINT `fifo_alloc_fifo_foreign` FOREIGN KEY (`fifo_id`) REFERENCES `customer_order_product_fifo` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Consumo FIFO de capas por líneas de nota de crédito';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `customer_seller`
--

DROP TABLE IF EXISTS `customer_seller`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_seller` (
  `customer_id` bigint(20) unsigned NOT NULL,
  `seller_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  KEY `customer_seller_customer_id_foreign` (`customer_id`),
  KEY `customer_seller_seller_id_foreign` (`seller_id`),
  CONSTRAINT `customer_seller_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE,
  CONSTRAINT `customer_seller_seller_id_foreign` FOREIGN KEY (`seller_id`) REFERENCES `sellers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `customer_user`
--

DROP TABLE IF EXISTS `customer_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_user` (
  `customer_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`customer_id`,`user_id`),
  KEY `customer_user_user_id_foreign` (`user_id`),
  CONSTRAINT `customer_user_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE,
  CONSTRAINT `customer_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `customers` (
  `customer_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `language_id` bigint(20) unsigned DEFAULT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) DEFAULT ' ',
  `business_name` varchar(255) DEFAULT ' ',
  `tax_id` varchar(255) DEFAULT ' ',
  `term_id` bigint(20) unsigned DEFAULT NULL,
  `group_id` bigint(20) unsigned DEFAULT NULL,
  `email` varchar(255) DEFAULT ' ',
  `phone` varchar(255) DEFAULT ' ',
  `cell_phone` varchar(255) DEFAULT ' ',
  `notes` varchar(50) DEFAULT ' ',
  `dispatch_delivery_address` varchar(255) DEFAULT ' ',
  `dispatch_shipping_notes` varchar(50) DEFAULT ' ',
  `catalog_emails` tinyint(4) DEFAULT 0,
  `customer_created_at` datetime DEFAULT current_timestamp(),
  `customer_status` tinyint(4) NOT NULL DEFAULT 1,
  `cust_id_kor` varchar(50) DEFAULT '' COMMENT 'Code',
  `id_kor` bigint(20) DEFAULT 0,
  `seller_assign_catalog` bigint(20) unsigned DEFAULT NULL,
  `discount` double DEFAULT 0,
  `modified_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `password` varchar(250) DEFAULT NULL,
  `balance` double DEFAULT 0,
  `sales` double DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `commercial_address` varchar(255) DEFAULT NULL,
  `commercial_delivery_address` varchar(255) DEFAULT NULL,
  `commercial_country` varchar(255) DEFAULT NULL,
  `commercial_state` varchar(255) DEFAULT NULL,
  `commercial_city` varchar(255) DEFAULT NULL,
  `commercial_zone` varchar(255) DEFAULT NULL,
  `commercial_zip_code` varchar(255) DEFAULT NULL,
  `dispatch_address` varchar(255) DEFAULT NULL,
  `dispatch_country` varchar(255) DEFAULT NULL,
  `dispatch_state` varchar(255) DEFAULT NULL,
  `dispatch_city` varchar(255) DEFAULT NULL,
  `dispatch_zone` varchar(255) DEFAULT NULL,
  `dispatch_zip_code` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`customer_id`),
  KEY `customers_company_id_foreign` (`company_id`),
  KEY `customers_language_id_foreign` (`language_id`),
  KEY `customers_term_id_foreign` (`term_id`),
  KEY `customers_group_id_foreign` (`group_id`),
  KEY `customers_seller_assign_catalog_foreign` (`seller_assign_catalog`),
  CONSTRAINT `customers_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`company_id`),
  CONSTRAINT `customers_group_id_foreign` FOREIGN KEY (`group_id`) REFERENCES `customer_groups` (`group_id`),
  CONSTRAINT `customers_language_id_foreign` FOREIGN KEY (`language_id`) REFERENCES `languages` (`language_id`),
  CONSTRAINT `customers_seller_assign_catalog_foreign` FOREIGN KEY (`seller_assign_catalog`) REFERENCES `sellers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `customers_term_id_foreign` FOREIGN KEY (`term_id`) REFERENCES `terms_of_sales` (`term_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1280 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `delivered_orders`
--

DROP TABLE IF EXISTS `delivered_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `delivered_orders` (
  `delivered_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `delivered_quantity` double DEFAULT NULL,
  `delivered_date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `delivered_pack` double DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`delivered_id`),
  KEY `delivered_orders_order_id_foreign` (`order_id`),
  KEY `delivered_orders_product_id_foreign` (`product_id`),
  CONSTRAINT `delivered_orders_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  CONSTRAINT `delivered_orders_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`pro_id`)
) ENGINE=InnoDB AUTO_INCREMENT=97 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `detail`
--

DROP TABLE IF EXISTS `detail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `detail` (
  `detail_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned DEFAULT NULL,
  `token_car` varchar(255) DEFAULT NULL,
  `customer_id` bigint(20) unsigned DEFAULT NULL,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `catalog_id` bigint(20) unsigned DEFAULT NULL,
  `qty` int(11) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `sale_price` double(16,2) DEFAULT 0.00,
  `comment` varchar(255) DEFAULT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `cookie_id` int(11) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`detail_id`),
  KEY `idx_customer_id` (`customer_id`),
  KEY `idx_company_id` (`company_id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_catalog_id` (`catalog_id`),
  KEY `idx_token_car` (`token_car`),
  KEY `idx_deleted_at` (`deleted_at`),
  CONSTRAINT `detail_catalog_id_foreign` FOREIGN KEY (`catalog_id`) REFERENCES `catalog` (`catalog_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `detail_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`company_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `detail_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `detail_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`pro_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `detail_car`
--

DROP TABLE IF EXISTS `detail_car`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `detail_car` (
  `detail_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) DEFAULT NULL,
  `token_car` text DEFAULT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `company_id` int(11) DEFAULT NULL,
  `qty` int(11) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `sale_price` double(16,2) DEFAULT 0.00,
  `comment` varchar(255) DEFAULT NULL,
  `cart_id` int(11) DEFAULT NULL,
  `session_id` int(11) DEFAULT NULL,
  `cookie_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`detail_id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Temporary table structure for view `devicebycompanylist`
--

DROP TABLE IF EXISTS `devicebycompanylist`;
/*!50001 DROP VIEW IF EXISTS `devicebycompanylist`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `devicebycompanylist` AS SELECT
 1 AS `deviceinfo`,
  1 AS `so`,
  1 AS `version`,
  1 AS `created`,
  1 AS `updated_at`,
  1 AS `versionname`,
  1 AS `company_id` */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `deviceinfos`
--

DROP TABLE IF EXISTS `deviceinfos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `deviceinfos` (
  `deviceinfo` varchar(200) NOT NULL,
  `so` varchar(200) NOT NULL,
  `version` varchar(200) NOT NULL,
  `created` date NOT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp(),
  `versionname` varchar(300) DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`deviceinfo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Temporary table structure for view `devicesCompanytemp`
--

DROP TABLE IF EXISTS `devicesCompanytemp`;
/*!50001 DROP VIEW IF EXISTS `devicesCompanytemp`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `devicesCompanytemp` AS SELECT
 1 AS `company_id`,
  1 AS `deviceinfo` */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `dictionary_gps`
--

DROP TABLE IF EXISTS `dictionary_gps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dictionary_gps` (
  `id` bigint(20) NOT NULL,
  `eng` varchar(500) DEFAULT NULL,
  `esp` varchar(500) DEFAULT NULL,
  `arg` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `email_customers`
--

DROP TABLE IF EXISTS `email_customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_customers` (
  `email_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `customer_id` bigint(20) DEFAULT NULL,
  `company_id` bigint(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`email_id`),
  UNIQUE KEY `email_customers_customer_id_IDX` (`customer_id`,`company_id`,`email`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `enquiries`
--

DROP TABLE IF EXISTS `enquiries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `enquiries` (
  `enquiryId` bigint(20) NOT NULL AUTO_INCREMENT,
  `name` varchar(500) DEFAULT NULL,
  `email` varchar(500) DEFAULT NULL,
  `phone` varchar(100) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `created` timestamp NULL DEFAULT current_timestamp(),
  `status` smallint(6) DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`enquiryId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `format_date`
--

DROP TABLE IF EXISTS `format_date`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `format_date` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `format_php` varchar(50) DEFAULT NULL,
  `format_js` varchar(50) DEFAULT NULL,
  `language_id` bigint(20) unsigned DEFAULT NULL,
  `date_firstmonth` varchar(50) DEFAULT NULL,
  `default` varchar(50) DEFAULT NULL,
  `month_year` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `format_date_language_id_foreign` (`language_id`),
  CONSTRAINT `format_date_language_id_foreign` FOREIGN KEY (`language_id`) REFERENCES `languages` (`language_id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `format_numeric`
--

DROP TABLE IF EXISTS `format_numeric`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `format_numeric` (
  `format_id` int(11) NOT NULL AUTO_INCREMENT,
  `numeric_format` varchar(50) DEFAULT NULL,
  `radixPoint` varchar(11) DEFAULT NULL,
  `groupSeparator` varchar(11) DEFAULT NULL,
  `digits` int(11) DEFAULT NULL,
  `default` int(11) DEFAULT NULL,
  `code_regional` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`format_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `formats`
--

DROP TABLE IF EXISTS `formats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `formats` (
  `format_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `format_name` varchar(255) NOT NULL COMMENT 'Nombre del formato',
  `format_description` text DEFAULT NULL COMMENT 'Descripción del formato',
  `template` varchar(10000) DEFAULT NULL COMMENT 'Plantilla HTML del formato de impresión',
  `order` int(11) DEFAULT 0 COMMENT 'Orden de visualización',
  `is_default` tinyint(1) DEFAULT 0 COMMENT '1 si es el formato por defecto',
  `company_id` bigint(20) unsigned DEFAULT NULL COMMENT 'ID de la empresa propietaria del formato',
  `format_type` varchar(50) DEFAULT NULL COMMENT 'Tipo de formato: catalog, order, invoice, etc.',
  `related_id` bigint(20) DEFAULT NULL COMMENT 'ID del recurso relacionado (catalog_id, order_id, etc.) según format_type',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`format_id`),
  KEY `idx_formats_company_id` (`company_id`),
  KEY `idx_formats_format_type` (`format_type`),
  KEY `idx_formats_related_id` (`related_id`),
  KEY `idx_formats_is_default` (`is_default`),
  KEY `idx_formats_deleted_at` (`deleted_at`),
  KEY `idx_formats_type_related` (`format_type`,`related_id`),
  CONSTRAINT `fk_formats_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`company_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Formatos genéricos de impresión';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Temporary table structure for view `get_imagesproducts`
--

DROP TABLE IF EXISTS `get_imagesproducts`;
/*!50001 DROP VIEW IF EXISTS `get_imagesproducts`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `get_imagesproducts` AS SELECT
 1 AS `company_id`,
  1 AS `product_id`,
  1 AS `qty_images`,
  1 AS `group_images` */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `gpslog`
--

DROP TABLE IF EXISTS `gpslog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `gpslog` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) NOT NULL DEFAULT 0,
  `user_id` bigint(20) NOT NULL DEFAULT 0,
  `login` bigint(20) NOT NULL DEFAULT 1,
  `infotext` text NOT NULL,
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `gpspost`
--

DROP TABLE IF EXISTS `gpspost`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `gpspost` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) DEFAULT NULL,
  `company_id` bigint(20) DEFAULT NULL,
  `longitud` double DEFAULT NULL,
  `latitud` double DEFAULT NULL,
  `fecha` date DEFAULT NULL,
  `hora` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `gpstracker`
--

DROP TABLE IF EXISTS `gpstracker`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `gpstracker` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) NOT NULL DEFAULT 0,
  `user_id` bigint(20) NOT NULL DEFAULT 0,
  `longitude` double NOT NULL DEFAULT 0,
  `latitude` double NOT NULL DEFAULT 0,
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  `login` bigint(20) DEFAULT NULL,
  `address` varchar(600) DEFAULT NULL,
  `deviceinfo` text DEFAULT '-',
  PRIMARY KEY (`id`),
  KEY `idx_gpstracker_company_user_created` (`company_id`,`user_id`,`created`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `images`
--

DROP TABLE IF EXISTS `images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `images` (
  `uuid` uuid NOT NULL,
  `name` varchar(255) NOT NULL,
  `path` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `size` varchar(255) NOT NULL,
  `extension` varchar(255) NOT NULL,
  `mime_type` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `language` varchar(100) DEFAULT NULL,
  `disk` varchar(100) NOT NULL DEFAULT 'minio',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`uuid`),
  KEY `images_name_index` (`name`),
  KEY `images_path_index` (`path`),
  KEY `images_company_id_index` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Temporary table structure for view `images_products`
--

DROP TABLE IF EXISTS `images_products`;
/*!50001 DROP VIEW IF EXISTS `images_products`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `images_products` AS SELECT
 1 AS `img_id`,
  1 AS `images`,
  1 AS `created`,
  1 AS `pro_id`,
  1 AS `company_id`,
  1 AS `imageBlob` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `img_nodata`
--

DROP TABLE IF EXISTS `img_nodata`;
/*!50001 DROP VIEW IF EXISTS `img_nodata`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `img_nodata` AS SELECT
 1 AS `pro_id`,
  1 AS `product_id`,
  1 AS `sku`,
  1 AS `company_id` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `implode_img`
--

DROP TABLE IF EXISTS `implode_img`;
/*!50001 DROP VIEW IF EXISTS `implode_img`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `implode_img` AS SELECT
 1 AS `company_id`,
  1 AS `product_id`,
  1 AS `imploded` */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `import_history`
--

DROP TABLE IF EXISTS `import_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `import_history` (
  `import_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `import_date` datetime DEFAULT NULL,
  `importedfile` varchar(255) NOT NULL,
  `records_imported` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`import_id`),
  KEY `import_history_company_id_foreign` (`company_id`),
  CONSTRAINT `import_history_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `initial_order_number`
--

DROP TABLE IF EXISTS `initial_order_number`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `initial_order_number` (
  `company_id` bigint(20) unsigned NOT NULL,
  `initial_number` int(11) NOT NULL,
  KEY `initial_order_number_company_id_foreign` (`company_id`),
  CONSTRAINT `initial_order_number_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `inventory`
--

DROP TABLE IF EXISTS `inventory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory` (
  `company_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `kor_id` bigint(20) DEFAULT 0,
  `qty` double DEFAULT 0,
  `sale_price` double DEFAULT 0,
  `purchased_price` double DEFAULT 0,
  `modify_date` datetime DEFAULT current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`company_id`,`product_id`),
  KEY `inventory_product_id_foreign` (`product_id`),
  CONSTRAINT `inventory_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`company_id`),
  CONSTRAINT `inventory_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`pro_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `invoices`
--

DROP TABLE IF EXISTS `invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoices` (
  `invoice_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `doc_number` varchar(50) DEFAULT NULL,
  `subscription_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `date` datetime DEFAULT NULL,
  `date_expired` datetime DEFAULT NULL,
  `paid` int(11) DEFAULT NULL,
  `days` tinyint(3) unsigned DEFAULT NULL COMMENT 'Días de periodo o flag; ajuste tipo si usa otro criterio',
  `issuing_entity` varchar(50) DEFAULT NULL,
  `address_entity` text DEFAULT NULL,
  `country_entity` varchar(50) DEFAULT NULL,
  `state_entity` varchar(50) DEFAULT NULL,
  `city_entity` varchar(50) DEFAULT NULL COMMENT 'Corregido nombre (antes city:entitty)',
  `concept` text DEFAULT NULL,
  `invoice_paid` decimal(12,2) DEFAULT NULL,
  `debt` decimal(12,2) DEFAULT NULL,
  `stripe_id` varchar(255) DEFAULT NULL,
  `stripe_doc` varchar(512) DEFAULT NULL,
  `stripe_url` varchar(1024) DEFAULT NULL,
  `stripe_pdf` varchar(1024) DEFAULT NULL,
  `stripe_status` varchar(64) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`invoice_id`),
  KEY `invoices_company_id` (`company_id`),
  KEY `invoices_subscription_id` (`subscription_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `issuing_entity`
--

DROP TABLE IF EXISTS `issuing_entity`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `issuing_entity` (
  `entity_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `entity_name` varchar(100) DEFAULT NULL,
  `entity_address` text DEFAULT NULL,
  `entity_country` varchar(50) DEFAULT NULL,
  `entity_state` varchar(50) DEFAULT NULL,
  `entity_city` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`entity_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `keys`
--

DROP TABLE IF EXISTS `keys`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `keys` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `key` varchar(40) NOT NULL,
  `level` int(11) NOT NULL,
  `ignore_limits` tinyint(1) NOT NULL DEFAULT 0,
  `is_private_key` tinyint(1) NOT NULL DEFAULT 0,
  `ip_addresses` text DEFAULT NULL,
  `date_created` datetime NOT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `keys_user_id_foreign` (`user_id`),
  CONSTRAINT `keys_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `languages`
--

DROP TABLE IF EXISTS `languages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `languages` (
  `language_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` char(49) DEFAULT NULL,
  `iso_639_1` char(2) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1,
  `type` varchar(50) DEFAULT NULL,
  `default` int(11) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`language_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `logs`
--

DROP TABLE IF EXISTS `logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `logs` (
  `log_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `log_type` varchar(200) NOT NULL COMMENT 'all,sync,login',
  `notes` text DEFAULT NULL,
  `created_on` datetime DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`log_id`),
  KEY `logs_company_id_foreign` (`company_id`),
  KEY `logs_user_id_foreign` (`user_id`),
  CONSTRAINT `logs_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`company_id`),
  CONSTRAINT `logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=64 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mails_logs`
--

DROP TABLE IF EXISTS `mails_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mails_logs` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `customers` longtext NOT NULL,
  `subject` longtext NOT NULL,
  `content` longtext NOT NULL,
  `fecha` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `manager_profiles`
--

DROP TABLE IF EXISTS `manager_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `manager_profiles` (
  `rol_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(50) DEFAULT NULL,
  `created_date` datetime DEFAULT current_timestamp(),
  `modified_date` datetime DEFAULT current_timestamp(),
  `is_active` int(11) DEFAULT 0,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`rol_id`),
  KEY `manager_profiles_company_id_foreign` (`company_id`),
  CONSTRAINT `manager_profiles_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mobile_change_log`
--

DROP TABLE IF EXISTS `mobile_change_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mobile_change_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `entity_group` varchar(32) NOT NULL COMMENT 'products|customers|categories|orders',
  `entity_id` bigint(20) unsigned NOT NULL,
  `action` varchar(16) NOT NULL COMMENT 'created|updated|deleted',
  `changed_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `mobile_change_log_company_changed_index` (`company_id`,`changed_at`),
  KEY `mobile_change_log_group_changed_index` (`company_id`,`entity_group`,`changed_at`),
  KEY `mobile_change_log_entity_index` (`entity_group`,`entity_id`),
  CONSTRAINT `mobile_change_log_company_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`company_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1125 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Ítems modificados por grupo para sync parcial móvil';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mobile_devices`
--

DROP TABLE IF EXISTS `mobile_devices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mobile_devices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `device_uuid` varchar(191) NOT NULL COMMENT 'Identificador único del dispositivo (app)',
  `user_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `device_name` varchar(255) DEFAULT NULL,
  `device_type` varchar(32) DEFAULT NULL COMMENT 'ios|android|other',
  `os_version` varchar(64) DEFAULT NULL,
  `app_version` varchar(64) DEFAULT NULL,
  `model` varchar(128) DEFAULT NULL,
  `manufacturer` varchar(128) DEFAULT NULL,
  `push_token` varchar(512) DEFAULT NULL,
  `last_used_at` datetime DEFAULT NULL,
  `last_sync_at` datetime DEFAULT NULL COMMENT 'NULL = nunca sincronizó',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mobile_devices_device_uuid_unique` (`device_uuid`),
  KEY `mobile_devices_user_id_index` (`user_id`),
  KEY `mobile_devices_company_id_index` (`company_id`),
  KEY `mobile_devices_last_sync_at_index` (`last_sync_at`),
  CONSTRAINT `mobile_devices_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`company_id`) ON DELETE SET NULL,
  CONSTRAINT `mobile_devices_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Dispositivos que acceden a FullVendor (app móvil)';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mobile_sync_logs`
--

DROP TABLE IF EXISTS `mobile_sync_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mobile_sync_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `mobile_device_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `sync_mode` varchar(16) NOT NULL COMMENT 'total|partial',
  `changes_count` int(11) NOT NULL DEFAULT 0,
  `changes_products` int(11) NOT NULL DEFAULT 0,
  `changes_customers` int(11) NOT NULL DEFAULT 0,
  `changes_categories` int(11) NOT NULL DEFAULT 0,
  `changes_orders` int(11) NOT NULL DEFAULT 0,
  `since_at` datetime DEFAULT NULL COMMENT 'Punto de referencia del dispositivo',
  `status` varchar(16) NOT NULL DEFAULT 'started' COMMENT 'started|completed|failed',
  `synced_at` datetime NOT NULL,
  `completed_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `mobile_sync_logs_device_id_index` (`mobile_device_id`),
  KEY `mobile_sync_logs_company_synced_index` (`company_id`,`synced_at`),
  CONSTRAINT `mobile_sync_logs_company_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`company_id`) ON DELETE SET NULL,
  CONSTRAINT `mobile_sync_logs_device_foreign` FOREIGN KEY (`mobile_device_id`) REFERENCES `mobile_devices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Historial de sincronizaciones solicitadas por dispositivo';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Temporary table structure for view `monto`
--

DROP TABLE IF EXISTS `monto`;
/*!50001 DROP VIEW IF EXISTS `monto`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `monto` AS SELECT
 1 AS `detail_id`,
  1 AS `created`,
  1 AS `customer`,
  1 AS `discount`,
  1 AS `sub_total`,
  1 AS `discount_total`,
  1 AS `total`,
  1 AS `catalog_id`,
  1 AS `company_id`,
  1 AS `catalog_name`,
  1 AS `product_id`,
  1 AS `customer_id` */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `navbar_rol_id`
--

DROP TABLE IF EXISTS `navbar_rol_id`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `navbar_rol_id` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `navbar_id` bigint(20) unsigned DEFAULT NULL,
  `rol_id` bigint(20) unsigned DEFAULT NULL,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `fhater_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `navbar_rol_id_company_id_foreign` (`company_id`),
  KEY `navbar_rol_id_navbar_id_foreign` (`navbar_id`),
  KEY `navbar_rol_id_rol_id_foreign` (`rol_id`),
  CONSTRAINT `navbar_rol_id_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`company_id`),
  CONSTRAINT `navbar_rol_id_navbar_id_foreign` FOREIGN KEY (`navbar_id`) REFERENCES `navbars` (`navbar_id`),
  CONSTRAINT `navbar_rol_id_rol_id_foreign` FOREIGN KEY (`rol_id`) REFERENCES `manager_profiles` (`rol_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `navbars`
--

DROP TABLE IF EXISTS `navbars`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `navbars` (
  `navbar_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `url_nav` varchar(150) DEFAULT NULL,
  `identify` varchar(255) DEFAULT NULL,
  `name_eng` varchar(150) DEFAULT NULL,
  `name_spa` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp(),
  `father_id` int(11) DEFAULT NULL,
  `icon` varchar(150) DEFAULT NULL,
  `order` int(11) DEFAULT NULL,
  `config` int(11) DEFAULT NULL,
  `is_active_to_rol` int(11) DEFAULT 1,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`navbar_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `newsletter`
--

DROP TABLE IF EXISTS `newsletter`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `newsletter` (
  `newsletterId` bigint(20) NOT NULL AUTO_INCREMENT,
  `email` varchar(500) DEFAULT NULL,
  `created` timestamp NULL DEFAULT NULL,
  `status` tinyint(4) DEFAULT 1 COMMENT '1-Subscribed, 0-Unsubscribed',
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`newsletterId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Temporary table structure for view `oDetailList`
--

DROP TABLE IF EXISTS `oDetailList`;
/*!50001 DROP VIEW IF EXISTS `oDetailList`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `oDetailList` AS SELECT
 1 AS `detail_id`,
  1 AS `order_id`,
  1 AS `product_id`,
  1 AS `sku`,
  1 AS `name`,
  1 AS `qty`,
  1 AS `delivered_quantity`,
  1 AS `discount`,
  1 AS `discount_type`,
  1 AS `sale_price`,
  1 AS `fob_price`,
  1 AS `purchase_price`,
  1 AS `comment`,
  1 AS `created`,
  1 AS `amount_sales`,
  1 AS `discount_amount`,
  1 AS `total_amount`,
  1 AS `amount_delivered`,
  1 AS `discount_delivered`,
  1 AS `total_delivered` */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `order_categories`
--

DROP TABLE IF EXISTS `order_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_categories` (
  `order_category_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` bigint(20) unsigned NOT NULL,
  `catalog_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `order` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`order_category_id`),
  KEY `order_categories_category_id_foreign` (`category_id`),
  KEY `order_categories_company_id_foreign` (`company_id`),
  KEY `order_categories_catalog_id_foreign` (`catalog_id`),
  CONSTRAINT `order_categories_catalog_id_foreign` FOREIGN KEY (`catalog_id`) REFERENCES `catalog` (`catalog_id`),
  CONSTRAINT `order_categories_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`cat_id`),
  CONSTRAINT `order_categories_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`company_id`)
) ENGINE=InnoDB AUTO_INCREMENT=738 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `order_details`
--

DROP TABLE IF EXISTS `order_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_details` (
  `detail_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `qty` double NOT NULL DEFAULT 0,
  `delivered_quantity` double NOT NULL,
  `discount` double NOT NULL,
  `discount_type` tinyint(4) NOT NULL DEFAULT 1 COMMENT '1 = in %, 2 = in $',
  `sale_price` double NOT NULL DEFAULT 0,
  `fob_price` double NOT NULL DEFAULT 0,
  `sale_price_app` double NOT NULL DEFAULT 0,
  `purchase_price` double NOT NULL DEFAULT 0,
  `comment` text NOT NULL,
  `created` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `id_kor` bigint(20) NOT NULL,
  `comments` text NOT NULL,
  `modify_date` timestamp NULL DEFAULT NULL,
  `fixed_pricedate` timestamp NULL DEFAULT NULL,
  `amount_sales` double DEFAULT 0,
  `discount_amount` double DEFAULT 0,
  `total_amount` double DEFAULT 0,
  `amount_delivered` double DEFAULT 0,
  `discount_delivered` double DEFAULT 0,
  `total_delivered` double DEFAULT 0,
  `amount` double DEFAULT 0,
  `discountt` double DEFAULT 0,
  `delivered_date` timestamp NULL DEFAULT NULL,
  `total` double DEFAULT 0,
  `groupcustomer` varchar(200) DEFAULT NULL,
  `tipolista` varchar(200) DEFAULT NULL,
  `perc_price` double DEFAULT NULL,
  `salesp` double DEFAULT NULL,
  `impprice` double DEFAULT NULL,
  `totalprice` double DEFAULT NULL,
  `import_price` double DEFAULT 0,
  `pack` double DEFAULT 0,
  `delivered_pack` double DEFAULT NULL,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`detail_id`),
  KEY `order_details_product_id_foreign` (`product_id`),
  KEY `order_details_order_id_foreign` (`order_id`),
  CONSTRAINT `order_details_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  CONSTRAINT `order_details_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`pro_id`)
) ENGINE=InnoDB AUTO_INCREMENT=50234 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `order_flow_transitions`
--

DROP TABLE IF EXISTS `order_flow_transitions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_flow_transitions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `order_flow_id` bigint(20) unsigned NOT NULL,
  `origin_status_order_id` bigint(20) unsigned NOT NULL,
  `destination_status_order_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `order_flows`
--

DROP TABLE IF EXISTS `order_flows`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_flows` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `order_notes`
--

DROP TABLE IF EXISTS `order_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_notes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `type` enum('order','shipping','internal','delivery') NOT NULL,
  `note` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_notes_order_id_foreign` (`order_id`),
  KEY `order_notes_user_id_foreign` (`user_id`),
  CONSTRAINT `order_notes_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  CONSTRAINT `order_notes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=58 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `order_status_history`
--

DROP TABLE IF EXISTS `order_status_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_status_history` (
  `history_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned DEFAULT NULL,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `cod_status` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`history_id`),
  KEY `order_status_history_company_id_foreign` (`company_id`),
  KEY `order_status_history_order_id_foreign` (`order_id`),
  KEY `order_status_history_user_id_foreign` (`user_id`),
  CONSTRAINT `order_status_history_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`company_id`),
  CONSTRAINT `order_status_history_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  CONSTRAINT `order_status_history_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `orders` (
  `tipo_d` varchar(1) DEFAULT 'D',
  `order_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `UUID` char(36) NOT NULL DEFAULT 'uuid()',
  `order_number` varchar(255) DEFAULT '',
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `warehouse_user_id` bigint(20) unsigned DEFAULT NULL,
  `warehouse_id` bigint(20) unsigned DEFAULT NULL,
  `seller_id` bigint(20) unsigned DEFAULT NULL,
  `language_id` bigint(20) unsigned NOT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `payment_method` tinyint(4) NOT NULL DEFAULT 0 COMMENT '0-CashOn Delivery, 1-Payment Gateway',
  `payment_status` tinyint(4) NOT NULL DEFAULT 0 COMMENT '0-Due, 1-Paid, 2-Refund, 3-Settled',
  `transaction_id` varchar(255) DEFAULT '',
  `delivery_status` int(11) DEFAULT NULL COMMENT '1=Full Delivered, 0=Partial Delivered',
  `discount` double NOT NULL,
  `discount_type` tinyint(4) NOT NULL DEFAULT 1 COMMENT '1 = in %, 2 = in $ ',
  `created` datetime DEFAULT NULL,
  `updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `approved_by` varchar(255) DEFAULT '',
  `warehouse_assign_date` datetime DEFAULT NULL,
  `source` varchar(50) NOT NULL DEFAULT 'app',
  `token_create` varchar(512) DEFAULT NULL,
  `user_id_token` bigint(20) unsigned DEFAULT NULL,
  `rol_token` varchar(64) DEFAULT NULL,
  `token_pass` varchar(255) DEFAULT '',
  `date_confirmed` datetime DEFAULT NULL,
  `send_email_order` datetime DEFAULT NULL,
  `date_rejected_email` datetime DEFAULT NULL,
  `date_expired_email` datetime DEFAULT NULL,
  `expiration_days` int(11) DEFAULT NULL,
  `amount` double DEFAULT 0,
  `discount_a` double DEFAULT 0,
  `total_amount` double DEFAULT 0,
  `amount_delivered` double DEFAULT 0,
  `discount_delivered` double DEFAULT 0,
  `total_delivered` double DEFAULT 0,
  `portal_vendor` int(11) DEFAULT NULL,
  `virified_byportal` datetime DEFAULT NULL,
  `id_kor` bigint(20) DEFAULT NULL,
  `catalog_id` bigint(20) unsigned DEFAULT NULL,
  `update_bybackend` int(11) DEFAULT 0,
  `discount_type_since_movil` int(11) DEFAULT NULL,
  `discount_movil` decimal(20,2) DEFAULT NULL,
  `leido` int(11) DEFAULT 0,
  `proforma` int(11) DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `status_id` tinyint(3) unsigned NOT NULL,
  `address_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`order_id`),
  KEY `orders_catalog_id_foreign` (`catalog_id`),
  KEY `orders_company_id_foreign` (`company_id`),
  KEY `orders_seller_id_foreign` (`seller_id`),
  KEY `orders_language_id_foreign` (`language_id`),
  KEY `orders_customer_id_foreign` (`customer_id`),
  KEY `orders_status_id_foreign` (`status_id`),
  KEY `orders_address_id_foreign` (`address_id`),
  KEY `orders_warehouse_id_foreign` (`warehouse_id`),
  KEY `orders_user_id_token_index` (`user_id_token`),
  CONSTRAINT `orders_address_id_foreign` FOREIGN KEY (`address_id`) REFERENCES `addresses` (`id`),
  CONSTRAINT `orders_catalog_id_foreign` FOREIGN KEY (`catalog_id`) REFERENCES `catalog` (`catalog_id`),
  CONSTRAINT `orders_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`company_id`),
  CONSTRAINT `orders_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE,
  CONSTRAINT `orders_language_id_foreign` FOREIGN KEY (`language_id`) REFERENCES `languages` (`language_id`),
  CONSTRAINT `orders_seller_id_foreign` FOREIGN KEY (`seller_id`) REFERENCES `sellers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1981 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Temporary table structure for view `orders_categories`
--

DROP TABLE IF EXISTS `orders_categories`;
/*!50001 DROP VIEW IF EXISTS `orders_categories`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `orders_categories` AS SELECT
 1 AS `order_category_id`,
  1 AS `category_id`,
  1 AS `catalog_id`,
  1 AS `company_id`,
  1 AS `order`,
  1 AS `catalog_name` */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB AUTO_INCREMENT=249 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `plan`
--

DROP TABLE IF EXISTS `plan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `plan` (
  `plan_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(150) DEFAULT NULL,
  `description` varchar(80) DEFAULT NULL,
  `billing_period` varchar(20) NOT NULL COMMENT 'monthly | annual',
  `status` bit(1) DEFAULT NULL,
  `max_clients` int(10) unsigned NOT NULL DEFAULT 0,
  `max_orders` int(10) unsigned NOT NULL DEFAULT 0,
  `max_users` int(10) unsigned NOT NULL DEFAULT 0,
  `ecommerce_permission` bit(1) NOT NULL DEFAULT b'0',
  `price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `plan_name_eng` text DEFAULT NULL,
  `plan_name_spa` text DEFAULT NULL,
  `line1_spa` text DEFAULT NULL,
  `line1_eng` text DEFAULT NULL,
  `payment` float DEFAULT NULL,
  `days` int(11) DEFAULT NULL,
  `max_Customers` int(11) DEFAULT NULL,
  `paymenttextp` text DEFAULT NULL,
  `paymentperiocity` int(11) DEFAULT NULL,
  `stripeld` varchar(50) DEFAULT NULL,
  `neverexpire` int(11) DEFAULT NULL,
  `stripeName` varchar(50) DEFAULT NULL,
  `stripe_doc` text DEFAULT NULL,
  `stripe_url` text DEFAULT NULL,
  `stripe_pdf` text DEFAULT NULL,
  `stripe_status` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`plan_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `postal_codes`
--

DROP TABLE IF EXISTS `postal_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `postal_codes` (
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
  KEY `postal_codes_country_iso2_postal_index` (`country_iso2`,`postal_code`),
  KEY `postal_codes_country_place_index` (`country_iso2`,`place_name`(100)),
  KEY `postal_codes_country_admin1_place_index` (`country_iso2`,`admin_name1`(80),`place_name`(80)),
  KEY `postal_codes_country_id_index` (`country_id`),
  KEY `postal_codes_state_id_index` (`state_id`),
  KEY `postal_codes_city_id_index` (`city_id`),
  CONSTRAINT `postal_codes_city_id_foreign` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE SET NULL,
  CONSTRAINT `postal_codes_country_id_foreign` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`) ON DELETE SET NULL,
  CONSTRAINT `postal_codes_state_id_foreign` FOREIGN KEY (`state_id`) REFERENCES `states` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=1868438 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Temporary table structure for view `pro_repetidos`
--

DROP TABLE IF EXISTS `pro_repetidos`;
/*!50001 DROP VIEW IF EXISTS `pro_repetidos`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `pro_repetidos` AS SELECT
 1 AS `company_id`,
  1 AS `sku`,
  1 AS `cuenta` */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `product_category_group`
--

DROP TABLE IF EXISTS `product_category_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_category_group` (
  `product_id` bigint(20) unsigned NOT NULL,
  `group_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`product_id`,`group_id`),
  KEY `product_category_group_group_id_foreign` (`group_id`),
  CONSTRAINT `product_category_group_group_id_foreign` FOREIGN KEY (`group_id`) REFERENCES `category_groups` (`group_id`) ON DELETE CASCADE,
  CONSTRAINT `product_category_group_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`pro_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `product_category_subgroup`
--

DROP TABLE IF EXISTS `product_category_subgroup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_category_subgroup` (
  `product_id` bigint(20) unsigned NOT NULL,
  `subgroup_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`product_id`,`subgroup_id`),
  KEY `product_category_subgroup_subgroup_id_foreign` (`subgroup_id`),
  CONSTRAINT `product_category_subgroup_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`pro_id`) ON DELETE CASCADE,
  CONSTRAINT `product_category_subgroup_subgroup_id_foreign` FOREIGN KEY (`subgroup_id`) REFERENCES `category_subgroups` (`subgroup_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `product_images`
--

DROP TABLE IF EXISTS `product_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_images` (
  `img_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `updated` datetime NOT NULL DEFAULT current_timestamp(),
  `img_order` bigint(20) DEFAULT 1,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `image_id` uuid DEFAULT NULL,
  PRIMARY KEY (`img_id`),
  KEY `product_images_product_id_foreign` (`product_id`),
  KEY `product_images_image_id_index` (`image_id`),
  CONSTRAINT `product_images_image_id_foreign` FOREIGN KEY (`image_id`) REFERENCES `images` (`uuid`),
  CONSTRAINT `product_images_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`pro_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10173 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `product_ratings`
--

DROP TABLE IF EXISTS `product_ratings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_ratings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `rating` decimal(2,1) NOT NULL DEFAULT 3.5,
  `sales_count` decimal(14,2) NOT NULL DEFAULT 0.00,
  `sales_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `source` varchar(20) NOT NULL DEFAULT 'standard',
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_ratings_company_product_unique` (`company_id`,`product_id`),
  KEY `product_ratings_company_id_foreign` (`company_id`),
  KEY `product_ratings_product_id_foreign` (`product_id`),
  KEY `product_ratings_company_rating_index` (`company_id`,`rating`),
  CONSTRAINT `product_ratings_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`company_id`) ON DELETE CASCADE,
  CONSTRAINT `product_ratings_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`pro_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6060 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `pro_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) DEFAULT NULL,
  `language_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `warehouse_id` bigint(20) unsigned DEFAULT NULL,
  `sku` varchar(255) DEFAULT NULL,
  `default_image_id` uuid DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `sale_price` double NOT NULL,
  `category_id` varchar(255) DEFAULT NULL COMMENT 'PK of categories',
  `barcode` varchar(255) DEFAULT NULL,
  `barcode_images` varchar(255) DEFAULT NULL,
  `tags` text DEFAULT NULL,
  `descriptions` text DEFAULT NULL,
  `specifications` longtext DEFAULT NULL COMMENT 'JSON array of product specs [{name,value}]',
  `stock` double NOT NULL DEFAULT 0,
  `minimum_stock` double NOT NULL DEFAULT 1,
  `notify_minimum_stock` tinyint(4) NOT NULL DEFAULT 1 COMMENT '1-Yes 0-No',
  `currency_type` varchar(255) DEFAULT NULL,
  `fob_price` double NOT NULL,
  `purchase_price` double NOT NULL,
  `locations` varchar(255) DEFAULT NULL,
  `created_by` varchar(255) DEFAULT NULL,
  `created_on` datetime DEFAULT NULL,
  `modified_on` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` tinyint(4) NOT NULL COMMENT '0-InActive 1-Active',
  `Img` blob DEFAULT NULL,
  `imgURL` longtext DEFAULT NULL,
  `simgURL` longtext DEFAULT NULL,
  `id_kor` bigint(20) DEFAULT NULL,
  `cat_id` longtext DEFAULT NULL,
  `cats_id` longtext DEFAULT NULL,
  `sell_with_inventory` int(11) DEFAULT 0,
  `image_updated` int(11) DEFAULT 0,
  `price_reduced` double NOT NULL DEFAULT 0,
  `sem_red_hasta` int(11) DEFAULT NULL,
  `sem_amarillo_desde` int(11) DEFAULT NULL,
  `sem_amarillo_hasta` int(11) DEFAULT NULL,
  `sem_verde_desde` int(11) DEFAULT NULL,
  `sem_verde_hasta` int(11) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `unit_id` bigint(20) unsigned DEFAULT NULL,
  `category_group_id` bigint(20) unsigned DEFAULT NULL,
  `category_subgroup_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`pro_id`),
  UNIQUE KEY `company_id_sku` (`sku`,`company_id`),
  KEY `products_company_id_foreign` (`company_id`),
  KEY `products_language_id_foreign` (`language_id`),
  KEY `products_unit_id_foreign` (`unit_id`),
  KEY `products_warehouse_id_foreign` (`warehouse_id`),
  KEY `products_category_group_id_foreign` (`category_group_id`),
  KEY `products_category_subgroup_id_foreign` (`category_subgroup_id`),
  CONSTRAINT `products_category_group_id_foreign` FOREIGN KEY (`category_group_id`) REFERENCES `category_groups` (`group_id`) ON DELETE SET NULL,
  CONSTRAINT `products_category_subgroup_id_foreign` FOREIGN KEY (`category_subgroup_id`) REFERENCES `category_subgroups` (`subgroup_id`) ON DELETE SET NULL,
  CONSTRAINT `products_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`company_id`),
  CONSTRAINT `products_language_id_foreign` FOREIGN KEY (`language_id`) REFERENCES `languages` (`language_id`),
  CONSTRAINT `products_unit_id_foreign` FOREIGN KEY (`unit_id`) REFERENCES `units` (`unit_id`),
  CONSTRAINT `products_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6060 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rols`
--

DROP TABLE IF EXISTS `rols`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `rols` (
  `rol_id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(100) DEFAULT NULL,
  `desc_es` varchar(255) DEFAULT NULL,
  `desc_en` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`rol_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Temporary table structure for view `rs_orders`
--

DROP TABLE IF EXISTS `rs_orders`;
/*!50001 DROP VIEW IF EXISTS `rs_orders`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `rs_orders` AS SELECT
 1 AS `order_id`,
  1 AS `amount`,
  1 AS `discount`,
  1 AS `amount_delivered`,
  1 AS `discount_delivered`,
  1 AS `total_amount`,
  1 AS `total_delivered` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `rs_requested`
--

DROP TABLE IF EXISTS `rs_requested`;
/*!50001 DROP VIEW IF EXISTS `rs_requested`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `rs_requested` AS SELECT
 1 AS `requested`,
  1 AS `product_id`,
  1 AS `name`,
  1 AS `qty`,
  1 AS `company_id`,
  1 AS `customer_id` */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `seller_profiles`
--

DROP TABLE IF EXISTS `seller_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `seller_profiles` (
  `profile_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `profile_name` varchar(255) DEFAULT NULL,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `order_discount` tinyint(4) NOT NULL DEFAULT 0 COMMENT '0=No 1=Yes',
  `order_disc_price` varchar(255) DEFAULT NULL,
  `order_net_discount` tinyint(4) NOT NULL DEFAULT 0 COMMENT '0=No 1=Yes',
  `order_net_disc_price` varchar(255) DEFAULT NULL,
  `can_change_price` tinyint(4) NOT NULL DEFAULT 0 COMMENT '0=No 1=Yes',
  `can_send_catalog` tinyint(4) NOT NULL DEFAULT 0 COMMENT '0=No 1=Yes',
  `can_create_customer` tinyint(4) NOT NULL DEFAULT 0 COMMENT '0=No 1=Yes',
  `created` datetime DEFAULT NULL,
  `status` smallint(6) DEFAULT 1 COMMENT '0=InActive 1= Active\n',
  `default` int(11) DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`profile_id`),
  KEY `seller_profiles_company_id_foreign` (`company_id`),
  CONSTRAINT `seller_profiles_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`company_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sellers`
--

DROP TABLE IF EXISTS `sellers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sellers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `phone_number` varchar(255) DEFAULT '',
  `cell_number` varchar(255) DEFAULT '',
  `fax` varchar(255) DEFAULT '',
  `created` datetime DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` smallint(6) DEFAULT 1 COMMENT '0=InActive 1= Active\n',
  `all_customers` int(11) DEFAULT 0,
  `proforma` int(11) NOT NULL DEFAULT 0,
  `email_on_salesman_order` int(11) DEFAULT 1,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sellers_company_id_foreign` (`company_id`),
  KEY `sellers_user_id_foreign` (`user_id`),
  CONSTRAINT `sellers_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`company_id`),
  CONSTRAINT `sellers_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `settingId` bigint(20) NOT NULL AUTO_INCREMENT,
  `logo` varchar(500) DEFAULT NULL,
  `favicon` varchar(500) DEFAULT NULL,
  `title` varchar(500) DEFAULT NULL,
  `address` varchar(500) DEFAULT NULL,
  `email` varchar(500) DEFAULT NULL,
  `phone` varchar(500) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `facebook` tinytext DEFAULT NULL,
  `twitter` tinytext DEFAULT NULL,
  `linkedin` tinytext DEFAULT NULL,
  `instagram` tinytext DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`settingId`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `states`
--

DROP TABLE IF EXISTS `states`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `states` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(30) NOT NULL,
  `country_id` bigint(20) unsigned NOT NULL,
  `created_on` datetime DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `states_country_id_foreign` (`country_id`),
  CONSTRAINT `states_country_id_foreign` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4122 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `status_cart`
--

DROP TABLE IF EXISTS `status_cart`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `status_cart` (
  `id_status` int(11) NOT NULL AUTO_INCREMENT,
  `name_spa` varchar(50) DEFAULT NULL,
  `name_eng` varchar(50) DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `cod` varchar(50) DEFAULT NULL,
  `class` varchar(50) DEFAULT NULL,
  `selected_default` varchar(50) DEFAULT NULL,
  `allow_toremove` int(11) DEFAULT 0,
  `multiple_cart` int(11) DEFAULT NULL,
  `nosubmit_order` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_status`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `status_order_flow`
--

DROP TABLE IF EXISTS `status_order_flow`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `status_order_flow` (
  `status_order_id` bigint(20) unsigned NOT NULL,
  `order_flow_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`status_order_id`,`order_flow_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `status_orders`
--

DROP TABLE IF EXISTS `status_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `status_orders` (
  `id_status` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `name_status_spanish` varchar(255) DEFAULT NULL,
  `name_status_english` varchar(255) DEFAULT NULL,
  `cod_status` int(11) DEFAULT NULL,
  `color` varchar(255) DEFAULT NULL,
  `disabled` int(11) DEFAULT NULL,
  `sombrilla` varchar(255) DEFAULT NULL,
  `color_icono` varchar(50) DEFAULT NULL,
  `tipo_d` varchar(50) DEFAULT NULL,
  `cannot_edit` int(11) DEFAULT NULL,
  `can_show_data_asigned` int(11) DEFAULT NULL,
  `send_email` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_status`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `status_rol`
--

DROP TABLE IF EXISTS `status_rol`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `status_rol` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `rol_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `update_at` datetime DEFAULT current_timestamp(),
  `id_status` int(11) DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `status_rol_company_id_foreign` (`company_id`),
  KEY `status_rol_rol_id_foreign` (`rol_id`),
  CONSTRAINT `status_rol_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`company_id`),
  CONSTRAINT `status_rol_rol_id_foreign` FOREIGN KEY (`rol_id`) REFERENCES `manager_profiles` (`rol_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `stripe_documents`
--

DROP TABLE IF EXISTS `stripe_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `stripe_documents` (
  `doc_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `urlStripe` longtext DEFAULT NULL,
  `amount` double DEFAULT NULL,
  `pagado` int(11) DEFAULT NULL,
  `montopagado` double DEFAULT NULL,
  `fecha` datetime DEFAULT NULL,
  `fechapagado` datetime DEFAULT NULL,
  PRIMARY KEY (`doc_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `stripe_publish`
--

DROP TABLE IF EXISTS `stripe_publish`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `stripe_publish` (
  `stripeID` bigint(20) NOT NULL AUTO_INCREMENT,
  `secretAPI` longtext DEFAULT NULL,
  PRIMARY KEY (`stripeID`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `stripe_secret`
--

DROP TABLE IF EXISTS `stripe_secret`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `stripe_secret` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `secret` longtext DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `subscriptions`
--

DROP TABLE IF EXISTS `subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `subscriptions` (
  `subscription_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `plan_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `notes` varchar(80) DEFAULT NULL,
  `stripe_subscription_id` varchar(255) DEFAULT NULL,
  `stripe_status` varchar(64) DEFAULT NULL,
  `stripe_price_id` varchar(255) DEFAULT NULL,
  `stripe_currency` varchar(8) DEFAULT NULL,
  `stripe_collection_method` varchar(32) DEFAULT NULL,
  `stripe_cancel_at_period_end` tinyint(1) NOT NULL DEFAULT 0,
  `stripe_current_period_start` datetime DEFAULT NULL,
  `stripe_current_period_end` datetime DEFAULT NULL,
  `stripe_trial_start` datetime DEFAULT NULL,
  `stripe_trial_end` datetime DEFAULT NULL,
  `stripe_cancel_at` datetime DEFAULT NULL,
  `stripe_canceled_at` datetime DEFAULT NULL,
  `stripe_ended_at` datetime DEFAULT NULL,
  `stripe_latest_invoice_id` varchar(255) DEFAULT NULL,
  `stripe_default_payment_method_id` varchar(255) DEFAULT NULL,
  `stripe_created_at` datetime DEFAULT NULL,
  `start_period_date` datetime DEFAULT NULL,
  `end_period_date` datetime DEFAULT NULL,
  `status` bit(1) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`subscription_id`),
  KEY `subscriptions_company_id` (`company_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Temporary table structure for view `tb_inventory`
--

DROP TABLE IF EXISTS `tb_inventory`;
/*!50001 DROP VIEW IF EXISTS `tb_inventory`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `tb_inventory` AS SELECT
 1 AS `company_id`,
  1 AS `product_id`,
  1 AS `sku`,
  1 AS `name`,
  1 AS `descriptions`,
  1 AS `qty`,
  1 AS `modify_date`,
  1 AS `sp`,
  1 AS `pp`,
  1 AS `sale_price`,
  1 AS `purchase_price`,
  1 AS `category_id` */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `terms_of_sales`
--

DROP TABLE IF EXISTS `terms_of_sales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `terms_of_sales` (
  `term_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `term_status` tinyint(4) NOT NULL DEFAULT 1,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`term_id`),
  KEY `terms_of_sales_company_id_foreign` (`company_id`),
  CONSTRAINT `terms_of_sales_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`company_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `units`
--

DROP TABLE IF EXISTS `units`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `units` (
  `unit_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `unit` varchar(50) NOT NULL,
  `description` varchar(500) NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `status` int(11) DEFAULT NULL,
  `created` date DEFAULT NULL,
  `decimales` int(11) DEFAULT NULL,
  `formato` varchar(50) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `default` int(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`unit_id`),
  KEY `units_company_id_foreign` (`company_id`),
  CONSTRAINT `units_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`company_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `language_id` bigint(20) unsigned NOT NULL DEFAULT 1,
  `password` varchar(255) NOT NULL,
  `password_plain` varchar(255) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `cellphone` varchar(30) DEFAULT NULL,
  `fax` varchar(30) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `zip_code` varchar(30) DEFAULT NULL,
  `two_factor_secret` text DEFAULT NULL,
  `two_factor_recovery_codes` text DEFAULT NULL,
  `two_factor_confirmed_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `lat1` double DEFAULT NULL,
  `lon1` double DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_language_id_foreign` (`language_id`),
  CONSTRAINT `users_language_id_foreign` FOREIGN KEY (`language_id`) REFERENCES `languages` (`language_id`)
) ENGINE=InnoDB AUTO_INCREMENT=685 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Temporary table structure for view `version`
--

DROP TABLE IF EXISTS `version`;
/*!50001 DROP VIEW IF EXISTS `version`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `version` AS SELECT
 1 AS `id`,
  1 AS `company_id` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `versiones`
--

DROP TABLE IF EXISTS `versiones`;
/*!50001 DROP VIEW IF EXISTS `versiones`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `versiones` AS SELECT
 1 AS `id`,
  1 AS `company_id`,
  1 AS `fecha`,
  1 AS `version` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `vw_inventory`
--

DROP TABLE IF EXISTS `vw_inventory`;
/*!50001 DROP VIEW IF EXISTS `vw_inventory`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `vw_inventory` AS SELECT
 1 AS `company_id`,
  1 AS `product_id`,
  1 AS `sku`,
  1 AS `name`,
  1 AS `Inventory`,
  1 AS `sale_price`,
  1 AS `amount_inventory` */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `warehouse_users`
--

DROP TABLE IF EXISTS `warehouse_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `warehouse_users` (
  `user_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `warehouse_id` bigint(20) unsigned NOT NULL,
  `created` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`user_id`,`company_id`,`warehouse_id`),
  KEY `warehouse_users_company_id_foreign` (`company_id`),
  KEY `warehouse_users_warehouse_id_foreign` (`warehouse_id`),
  CONSTRAINT `warehouse_users_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`company_id`),
  CONSTRAINT `warehouse_users_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `warehouse_users_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Temporary table structure for view `warehouseorders`
--

DROP TABLE IF EXISTS `warehouseorders`;
/*!50001 DROP VIEW IF EXISTS `warehouseorders`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `warehouseorders` AS SELECT
 1 AS `ano_id`,
  1 AS `mes_id`,
  1 AS `tipo_d`,
  1 AS `order_id`,
  1 AS `order_number`,
  1 AS `company_id`,
  1 AS `user_id`,
  1 AS `language_id`,
  1 AS `customer_id`,
  1 AS `payment_method`,
  1 AS `payment_status`,
  1 AS `transaction_id`,
  1 AS `order_comments`,
  1 AS `order_status`,
  1 AS `delivery_status`,
  1 AS `discount`,
  1 AS `discount_type`,
  1 AS `created`,
  1 AS `updated`,
  1 AS `approved_by`,
  1 AS `delivery_notes`,
  1 AS `warehouse_user_id`,
  1 AS `warehouse_assign_date`,
  1 AS `source`,
  1 AS `internal_notes`,
  1 AS `token_pass`,
  1 AS `expiration_days`,
  1 AS `amount`,
  1 AS `discount_a`,
  1 AS `total_amount`,
  1 AS `amount_delivered`,
  1 AS `discount_delivered`,
  1 AS `total_delivered`,
  1 AS `name_status_spanish`,
  1 AS `name_status_english`,
  1 AS `color`,
  1 AS `warehouse_name` */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `warehouses`
--

DROP TABLE IF EXISTS `warehouses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `warehouses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `address_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `warehouses_company_id_foreign` (`company_id`),
  KEY `warehouses_address_id_foreign` (`address_id`),
  CONSTRAINT `warehouses_address_id_foreign` FOREIGN KEY (`address_id`) REFERENCES `addresses` (`id`),
  CONSTRAINT `warehouses_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`company_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wordersList`
--

DROP TABLE IF EXISTS `wordersList`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `wordersList` (
  `tipo_d` varchar(1) DEFAULT NULL,
  `order_id` bigint(20) unsigned NOT NULL,
  `order_number` varchar(255) DEFAULT NULL,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `warehouse_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `language_id` bigint(20) unsigned NOT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `payment_method` tinyint(4) NOT NULL DEFAULT 0 COMMENT '0-CashOn Delivery, 1-Payment Gateway',
  `payment_status` tinyint(4) NOT NULL DEFAULT 0 COMMENT '0-Due, 1-Paid, 2-Refund, 3-Settled',
  `transaction_id` varchar(255) DEFAULT NULL,
  `order_comments` text DEFAULT NULL,
  `order_status` int(11) NOT NULL DEFAULT 0 COMMENT '0-Placed, 1-Dispatched, 2-Delivered, 3-Return Request, 4-Returned, 5-Received, 6-Cancelled,7-Approved,\n8-Warehouse Assigned,\n9-Partially Completed,\n10-Warehouse Completed,\n11-Order Completed,\n12-Rejected\n',
  `delivery_status` int(11) DEFAULT NULL COMMENT '1=Full Delivered, 0=Partial Delivered',
  `discount` double NOT NULL,
  `discount_type` tinyint(4) NOT NULL DEFAULT 0 COMMENT '1 = in %, 2 = in $ ',
  `created` datetime DEFAULT NULL,
  `updated` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `approved_by` varchar(255) DEFAULT NULL,
  `delivery_notes` text DEFAULT NULL,
  `warehouse_assign_date` datetime DEFAULT NULL,
  `source` varchar(50) NOT NULL DEFAULT '',
  `internal_notes` text NOT NULL,
  `token_pass` varchar(255) DEFAULT NULL,
  `date_confirmed` datetime DEFAULT NULL,
  `send_email_order` datetime DEFAULT NULL,
  `date_rejected_email` datetime DEFAULT NULL,
  `date_expired_email` datetime DEFAULT NULL,
  `expiration_days` int(11) DEFAULT NULL,
  `amount` double DEFAULT NULL,
  `discount_a` double DEFAULT NULL,
  `total_amount` double DEFAULT NULL,
  `amount_delivered` double DEFAULT NULL,
  `discount_delivered` double DEFAULT NULL,
  `total_delivered` double DEFAULT NULL,
  `name_status_spanish` varchar(255) DEFAULT NULL,
  `name_status_english` varchar(255) DEFAULT NULL,
  `color` varchar(255) DEFAULT NULL,
  `warehouse_name` varchar(511) DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  KEY `worderslist_company_id_foreign` (`company_id`),
  KEY `worderslist_order_id_foreign` (`order_id`),
  KEY `worderslist_user_id_foreign` (`user_id`),
  KEY `worderslist_language_id_foreign` (`language_id`),
  KEY `worderslist_customer_id_foreign` (`customer_id`),
  KEY `worderslist_warehouse_id_foreign` (`warehouse_id`),
  CONSTRAINT `worderslist_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`company_id`),
  CONSTRAINT `worderslist_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE,
  CONSTRAINT `worderslist_language_id_foreign` FOREIGN KEY (`language_id`) REFERENCES `languages` (`language_id`),
  CONSTRAINT `worderslist_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  CONSTRAINT `worderslist_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `worderslist_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Final view structure for view `CurrencyCodes`
--

/*!50001 DROP VIEW IF EXISTS `CurrencyCodes`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`appfv`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `CurrencyCodes` AS select `currency_formats`.`CurrencyCode` AS `CurrencyCode` from `currency_formats` group by `currency_formats`.`CurrencyCode` order by `currency_formats`.`CurrencyCode` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `Valid_Img`
--

/*!50001 DROP VIEW IF EXISTS `Valid_Img`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb3 */;
/*!50001 SET character_set_results     = utf8mb3 */;
/*!50001 SET collation_connection      = utf8mb3_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`appfv`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `Valid_Img` AS select `b`.`pro_id` AS `pro_id`,`a`.`img_id` AS `img_id`,`a`.`product_id` AS `product_id`,NULL AS `language_id`,`i`.`path` AS `images`,`a`.`created` AS `created`,`a`.`updated` AS `updated`,NULL AS `Img`,`i`.`path` AS `sImgURL`,`i`.`path` AS `ImgURL`,NULL AS `id_kor`,`i`.`original_name` AS `Description`,`b`.`company_id` AS `company_id`,`b`.`sku` AS `sku`,`b`.`default_image_id` = `a`.`image_id` AS `img_default` from ((`product_images` `a` left join `products` `b` on(`b`.`pro_id` = `a`.`product_id`)) left join `images` `i` on(`i`.`uuid` = `a`.`image_id`)) where `a`.`deleted_at` is null */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `cartImages`
--

/*!50001 DROP VIEW IF EXISTS `cartImages`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`appfv`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `cartImages` AS select `b`.`company_id` AS `company_id`,`a`.`img_id` AS `img_id`,`b`.`pro_id` AS `pro_id` from (`product_images` `a` join `products` `b` on(`b`.`pro_id` = `a`.`product_id`)) order by `b`.`company_id`,`b`.`product_id` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `devicebycompanylist`
--

/*!50001 DROP VIEW IF EXISTS `devicebycompanylist`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`appfv`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `devicebycompanylist` AS select `a`.`deviceinfo` AS `deviceinfo`,`a`.`so` AS `so`,`a`.`version` AS `version`,`a`.`created` AS `created`,`a`.`updated_at` AS `updated_at`,`a`.`versionname` AS `versionname`,`b`.`company_id` AS `company_id` from (`deviceinfos` `a` left join `devicesCompanytemp` `b` on(`b`.`deviceinfo` = `a`.`deviceinfo`)) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `devicesCompanytemp`
--

/*!50001 DROP VIEW IF EXISTS `devicesCompanytemp`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`appfv`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `devicesCompanytemp` AS select `gpstracker`.`company_id` AS `company_id`,`gpstracker`.`deviceinfo` AS `deviceinfo` from `gpstracker` group by `gpstracker`.`company_id`,`gpstracker`.`deviceinfo` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `get_imagesproducts`
--

/*!50001 DROP VIEW IF EXISTS `get_imagesproducts`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb3 */;
/*!50001 SET character_set_results     = utf8mb3 */;
/*!50001 SET collation_connection      = utf8mb3_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`appfv`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `get_imagesproducts` AS select `p`.`company_id` AS `company_id`,`pi`.`product_id` AS `product_id`,count(`pi`.`img_id`) AS `qty_images`,group_concat(`i`.`path` order by `pi`.`img_order` ASC separator ',') AS `group_images` from ((`product_images` `pi` join `products` `p` on(`p`.`pro_id` = `pi`.`product_id`)) left join `images` `i` on(`i`.`uuid` = `pi`.`image_id`)) where `pi`.`product_id` is not null and `pi`.`deleted_at` is null group by `pi`.`product_id`,`p`.`company_id` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `images_products`
--

/*!50001 DROP VIEW IF EXISTS `images_products`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb3 */;
/*!50001 SET character_set_results     = utf8mb3 */;
/*!50001 SET collation_connection      = utf8mb3_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`appfv`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `images_products` AS select `pi`.`img_id` AS `img_id`,`i`.`path` AS `images`,`pi`.`created` AS `created`,`p`.`pro_id` AS `pro_id`,`p`.`company_id` AS `company_id`,NULL AS `imageBlob` from ((`products` `p` left join `product_images` `pi` on(`p`.`pro_id` = `pi`.`product_id` and `pi`.`deleted_at` is null)) left join `images` `i` on(`i`.`uuid` = `pi`.`image_id`)) order by `p`.`pro_id` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `img_nodata`
--

/*!50001 DROP VIEW IF EXISTS `img_nodata`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb3 */;
/*!50001 SET character_set_results     = utf8mb3 */;
/*!50001 SET collation_connection      = utf8mb3_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`appfv`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `img_nodata` AS select `b`.`pro_id` AS `pro_id`,`a`.`product_id` AS `product_id`,`b`.`sku` AS `sku`,`b`.`company_id` AS `company_id` from (`product_images` `a` left join `products` `b` on(`b`.`pro_id` = `a`.`product_id`)) where `a`.`product_id` is null or `a`.`product_id` = 0 */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `implode_img`
--

/*!50001 DROP VIEW IF EXISTS `implode_img`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb3 */;
/*!50001 SET character_set_results     = utf8mb3 */;
/*!50001 SET collation_connection      = utf8mb3_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`appfv`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `implode_img` AS select `p`.`company_id` AS `company_id`,`pi`.`product_id` AS `product_id`,group_concat(coalesce(`i`.`path`,'') separator ',') AS `imploded` from ((`product_images` `pi` join `products` `p` on(`p`.`pro_id` = `pi`.`product_id`)) left join `images` `i` on(`i`.`uuid` = `pi`.`image_id`)) where `pi`.`deleted_at` is null group by `pi`.`product_id`,`p`.`company_id` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `monto`
--

/*!50001 DROP VIEW IF EXISTS `monto`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`appfv`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `monto` AS select `d`.`detail_id` AS `detail_id`,`d`.`created` AS `created`,concat(`c`.`name`,' ',`c`.`business_name`) AS `customer`,concat(`c`.`discount`,'%') AS `discount`,sum(`d`.`sale_price` * `d`.`qty`) AS `sub_total`,sum(`d`.`sale_price` * `d`.`qty`) * `c`.`discount` / 100 AS `discount_total`,sum(`d`.`sale_price` * `d`.`qty`) - sum(`d`.`sale_price` * `d`.`qty`) * `c`.`discount` / 100 AS `total`,`d`.`catalog_id` AS `catalog_id`,`d`.`company_id` AS `company_id`,`cat`.`catalog_name` AS `catalog_name`,`d`.`product_id` AS `product_id`,`d`.`customer_id` AS `customer_id` from ((`detail` `d` join `customers` `c` on(`c`.`customer_id` = `d`.`customer_id`)) left join `catalog` `cat` on(`cat`.`catalog_id` = `d`.`catalog_id`)) group by `d`.`token_car`,`d`.`detail_id`,`d`.`created`,`c`.`name`,`c`.`business_name`,`c`.`discount`,`d`.`catalog_id`,`d`.`company_id`,`cat`.`catalog_name`,`d`.`product_id`,`d`.`customer_id` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `oDetailList`
--

/*!50001 DROP VIEW IF EXISTS `oDetailList`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`appfv`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `oDetailList` AS select `o`.`detail_id` AS `detail_id`,`o`.`order_id` AS `order_id`,`o`.`product_id` AS `product_id`,`p`.`sku` AS `sku`,`p`.`name` AS `name`,`o`.`qty` AS `qty`,`o`.`delivered_quantity` AS `delivered_quantity`,`o`.`discount` AS `discount`,`o`.`discount_type` AS `discount_type`,`o`.`sale_price` AS `sale_price`,`o`.`fob_price` AS `fob_price`,`o`.`purchase_price` AS `purchase_price`,`o`.`comment` AS `comment`,`o`.`created` AS `created`,`o`.`amount_sales` AS `amount_sales`,`o`.`discount_amount` AS `discount_amount`,`o`.`total_amount` AS `total_amount`,`o`.`amount_delivered` AS `amount_delivered`,`o`.`discount_delivered` AS `discount_delivered`,`o`.`total_delivered` AS `total_delivered` from ((`order_details` `o` join `orders` `c` on(`c`.`order_id` = `o`.`order_id`)) join `products` `p` on(`p`.`pro_id` = `o`.`product_id`)) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `orders_categories`
--

/*!50001 DROP VIEW IF EXISTS `orders_categories`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`appfv`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `orders_categories` AS select `od`.`order_category_id` AS `order_category_id`,`od`.`category_id` AS `category_id`,`od`.`catalog_id` AS `catalog_id`,`od`.`company_id` AS `company_id`,`od`.`order` AS `order`,`ca`.`catalog_name` AS `catalog_name` from (`order_categories` `od` join `catalog` `ca` on(`ca`.`catalog_id` = `od`.`catalog_id`)) where `od`.`deleted_at` is null */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `pro_repetidos`
--

/*!50001 DROP VIEW IF EXISTS `pro_repetidos`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`appfv`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `pro_repetidos` AS select `products`.`company_id` AS `company_id`,`products`.`sku` AS `sku`,count(`products`.`pro_id`) AS `cuenta` from `products` group by `products`.`sku`,`products`.`company_id` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `rs_orders`
--

/*!50001 DROP VIEW IF EXISTS `rs_orders`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`appfv`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `rs_orders` AS select `o`.`order_id` AS `order_id`,sum(`o`.`amount_sales`) AS `amount`,sum(`o`.`discount_amount`) AS `discount`,sum(`o`.`amount_delivered`) AS `amount_delivered`,sum(`o`.`discount_delivered`) AS `discount_delivered`,sum(`o`.`total_amount`) AS `total_amount`,sum(`o`.`total_delivered`) AS `total_delivered` from `order_details` `o` group by `o`.`order_id` order by `o`.`order_id` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `rs_requested`
--

/*!50001 DROP VIEW IF EXISTS `rs_requested`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`appfv`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `rs_requested` AS select count(`od`.`product_id`) AS `requested`,`od`.`product_id` AS `product_id`,`p`.`name` AS `name`,sum(`od`.`qty`) AS `qty`,`o`.`company_id` AS `company_id`,`o`.`customer_id` AS `customer_id` from ((`orders` `o` join `order_details` `od` on(`o`.`order_id` = `od`.`order_id`)) join `products` `p` on(`od`.`product_id` = `p`.`pro_id`)) where `o`.`status_id` = 14 group by `od`.`product_id`,`o`.`company_id`,`o`.`customer_id`,`p`.`name` order by `o`.`company_id`,`o`.`customer_id` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `tb_inventory`
--

/*!50001 DROP VIEW IF EXISTS `tb_inventory`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`appfv`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `tb_inventory` AS select `i`.`company_id` AS `company_id`,`i`.`product_id` AS `product_id`,`p`.`sku` AS `sku`,`p`.`name` AS `name`,`p`.`descriptions` AS `descriptions`,`i`.`qty` AS `qty`,`i`.`modify_date` AS `modify_date`,`i`.`sale_price` AS `sp`,`i`.`purchased_price` AS `pp`,`p`.`sale_price` AS `sale_price`,`p`.`purchase_price` AS `purchase_price`,`p`.`category_id` AS `category_id` from (`inventory` `i` join `products` `p` on(`p`.`company_id` = `i`.`company_id` and `p`.`product_id` = `i`.`product_id`)) order by `i`.`company_id`,`p`.`sku` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `version`
--

/*!50001 DROP VIEW IF EXISTS `version`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`appfv`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `version` AS select max(`app_updates`.`ID`) AS `id`,`app_updates`.`company_id` AS `company_id` from `app_updates` group by `app_updates`.`company_id` order by `app_updates`.`company_id` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `versiones`
--

/*!50001 DROP VIEW IF EXISTS `versiones`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`appfv`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `versiones` AS select `a`.`id` AS `id`,`a`.`company_id` AS `company_id`,`b`.`fecha` AS `fecha`,`b`.`updatenumber` AS `version` from (`version` `a` join `app_updates` `b` on(`b`.`ID` = `a`.`id`)) order by `a`.`company_id` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `vw_inventory`
--

/*!50001 DROP VIEW IF EXISTS `vw_inventory`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`appfv`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_inventory` AS select `od`.`company_id` AS `company_id`,`odt`.`product_id` AS `product_id`,`p`.`sku` AS `sku`,`p`.`name` AS `name`,sum(`odt`.`qty` * -1) AS `Inventory`,avg(`odt`.`sale_price`) AS `sale_price`,sum(`odt`.`qty` * `odt`.`sale_price` * -1) AS `amount_inventory` from ((`order_details` `odt` join `orders` `od` on(`od`.`order_id` = `odt`.`order_id`)) join `products` `p` on(`p`.`pro_id` = `odt`.`product_id`)) group by `od`.`company_id`,`odt`.`product_id`,`p`.`sku`,`p`.`name` order by `od`.`company_id`,`odt`.`product_id` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `warehouseorders`
--

/*!50001 DROP VIEW IF EXISTS `warehouseorders`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`appfv`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `warehouseorders` AS select year(`c`.`created`) AS `ano_id`,month(`c`.`created`) AS `mes_id`,`c`.`tipo_d` AS `tipo_d`,`c`.`order_id` AS `order_id`,`c`.`order_number` AS `order_number`,`c`.`company_id` AS `company_id`,`c`.`seller_id` AS `user_id`,`c`.`language_id` AS `language_id`,`c`.`customer_id` AS `customer_id`,`c`.`payment_method` AS `payment_method`,`c`.`payment_status` AS `payment_status`,`c`.`transaction_id` AS `transaction_id`,cast(NULL as char charset utf8mb4) AS `order_comments`,`c`.`status_id` AS `order_status`,`c`.`delivery_status` AS `delivery_status`,`c`.`discount` AS `discount`,`c`.`discount_type` AS `discount_type`,`c`.`created` AS `created`,`c`.`updated` AS `updated`,`c`.`approved_by` AS `approved_by`,cast(NULL as char charset utf8mb4) AS `delivery_notes`,`c`.`warehouse_user_id` AS `warehouse_user_id`,`c`.`warehouse_assign_date` AS `warehouse_assign_date`,`c`.`source` AS `source`,cast(NULL as char charset utf8mb4) AS `internal_notes`,`c`.`token_pass` AS `token_pass`,`c`.`expiration_days` AS `expiration_days`,`c`.`amount` AS `amount`,`c`.`discount_a` AS `discount_a`,`c`.`total_amount` AS `total_amount`,`c`.`amount_delivered` AS `amount_delivered`,`c`.`discount_delivered` AS `discount_delivered`,`c`.`total_delivered` AS `total_delivered`,`b`.`name_status_spanish` AS `name_status_spanish`,`b`.`name_status_english` AS `name_status_english`,`b`.`color` AS `color`,coalesce(nullif(trim(concat(coalesce(`u`.`first_name`,''),' ',coalesce(`u`.`last_name`,''))),''),nullif(trim(`u`.`name`),''),nullif(trim(`w`.`name`),''),'') AS `warehouse_name` from ((((`orders` `c` join `status_orders` `b` on(`b`.`id_status` = `c`.`status_id`)) left join `warehouse_users` `a` on(`a`.`user_id` = `c`.`warehouse_user_id`)) left join `users` `u` on(`u`.`id` = `a`.`user_id`)) left join `warehouses` `w` on(`w`.`id` = `c`.`warehouse_id`)) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-24  1:26:11
