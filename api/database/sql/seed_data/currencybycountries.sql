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
-- Dumping data for table `currencybycountries`
--

/*!40000 ALTER TABLE `currencybycountries` DISABLE KEYS */;
INSERT IGNORE INTO `currencybycountries` (`id`, `GroupID`, `Name`, `Contry`, `Code`, `Symbol`, `GroupIdspa`, `Namespa`, `default`) VALUES (1,'North America','Dollar','United States','USD','$','America del norte','Dolar','selected'),
(2,'North America','Canadian Dollar','Canada','CAD','$','America del norte','Dolar Canadiense',NULL),
(3,'North America','Mexican Peso','Mexico','MXN','$','America del norte','Peso Mexicano',NULL),
(4,'North America','Florin','Aruba','AWG','Ÿ','America del norte','Florin',NULL),
(5,'North America','Lower Dollar','Barbados','BBD','$','America del norte','Bajan Dollar',NULL),
(6,'North America','Bermuda Dollar','Bermuda Islands','BMD','$','America del norte','Dolar De Las Bermudas',NULL),
(7,'North America','Bahamian Dollar','Bahamas','BSD','$','America del norte','Dolar De Las Bahamas',NULL),
(8,'North America','Dominican Peso','Dominican Republic','DOP','$','America del norte','Peso Dominicano',NULL),
(9,'North America','Jamaican Dollar','Jamaica','JMD','$','America del norte','Dolar Jamaiquino',NULL),
(10,'North America','Guatemalan Quetzal','Guatemala','GTQ','Q','America del norte','Quetzal Guatemalteco',NULL),
(11,'North America','Panamanian Balboa','Panama','PAB','B/.','America del norte','Balboa Panameno',NULL),
(12,'North America','Caribbean Dollar','Antigua and Barbuda, Anguilla, Dominica, Grenada, Montserrat, Saint Kitts and Nevis, Saint Lucia, Saint Vincent and the Grenadines','XCD','$','America del norte','Dolar Caribeno',NULL),
(13,'Europe','Euro','European Union','EUR','€','Europa','Euro',NULL),
(14,'Europe','British Pound Sterling','United Kingdom, British Indian Ocean Territory, Isle of Man, Jersey, Guernsey','GBP','£','Europa','Libra Esterlina Brit Nica',NULL),
(15,'Europe','Georgian Race','Georgia','GEL','GEL','Europa','Carrera Georgiana',NULL),
(16,'Europe','Bulgarian Lev','Bulgaria','BGN','лв','Europa','Lev Bulgaro',NULL),
(17,'Europe','Swiss Franc','Switzerland, Liechtenstein, Italian Champion','CHF','CHF','Europa','Franco Suizo',NULL),
(18,'Europe','Danish Crown','Denmark, Faroe Islands, Greenland','DKK','kr','Europa','Corona Danesa',NULL),
(19,'Europe','Crown Of The Czech Republic','Czech Republic','CZK','Kč','Europa','Corona De La Republica Checa',NULL),
(20,'Europe','Croatian Kuna','Croatia','HRK','kn','Europa','Kuna Croata',NULL),
(21,'Europe','Hungarian Forint','Hungary','HUF','ft','Europa','Florin Hungaro',NULL),
(22,'Europe','The Norwegian Crown','Norway, Svalbard and Jan Mayen, Bouvet Island','NOK','kr','Europa','Corona Noruega',NULL),
(23,'Europe','Russian Ruble','Russia','RUB','₽','Europa','Rublo Ruso',NULL),
(24,'Europe','Polish Zloty','Poland','PLN','zł','Europa','Zloty Polaco',NULL),
(25,'Europe','Romanian Leu','Rumania','RON','lei','Europa','Leu Rumano',NULL),
(26,'Europe','Swedish Krona','Sweden','SEK','kr','Europa','Corona Sueca',NULL),
(27,'Europe','Ukrainian Grivna','Ukraine','UAH','₴','Europa','Grivna Ucraniana',NULL),
(28,'Europe','Turkish Lira','Turkiye','TRY','₺','Europa','Lira Turca',NULL),
(29,'South America','Argentine Peso','Argentina','ARS','$','Sudamerica','Peso Argentino',NULL),
(30,'South America','Bolivian Bolivian','Bolivia','BOB','Bs.','Sudamerica','Bolivian Boliviano',NULL),
(31,'South America','Brazilian Real','Brasil','BRL','R$','Sudamerica','Real Brasileno',NULL),
(32,'South America','Chilean Peso','Chile','CLP','$','Sudamerica','Chilean Peso',NULL),
(33,'South America','Colombian Peso','Colombia','COP','$','Sudamerica','Colombian Peso',NULL),
(34,'South America','Peruvian Nuevo Sol','Peru','PEN','S/.','Sudamerica','Peruvian Nuevo Sol',NULL),
(35,'South America','Paraguayan Guaraní','Paraguay','PYG','Gs','Sudamerica','Guarani Paraguayo',NULL),
(36,'South America','Uruguayan Peso','Uruguay','UYU','$','Sudamerica','Uruguayan Peso',NULL),
(37,'South America','Venezuelan Bolívar','Venezuela','VES','Bs.','Sudamerica','Venezuelan Bolivar',NULL),
(38,'Asia','Japanese Yen','Japan','JPY','¥','Asia','Yen Japones',NULL),
(39,'Asia','Bangladeshi Taka','Bangladesh','BDT','฿','Asia','Bangladesh Taka',NULL),
(40,'Asia','Chinese Yuan','China','CNY','¥','Asia','Yuan Chino',NULL),
(41,'Asia','Hong Kong Dollar','Hong Kong','HKD','$','Asia','Dolar De Hong Kong',NULL),
(42,'Asia','Indian Rupee','India','INR','₹','Asia','Rupia India',NULL),
(43,'Asia','Cambodian Riel','Camboya','KHR','៛','Asia','Riel Camboyano',NULL),
(44,'Asia','Chicken','Laos','LAK','₭','Asia','Pollo',NULL),
(45,'Asia','Sri Lankan Rupee','Sri Lanka','LKR','රු','Asia','Rupia De Sri Lanka',NULL),
(46,'Asia','Rufiyaa','Maldives','MVR','Rf','Asia','Rufiyaa',NULL),
(47,'Asia','Malay Ringgit','Malaysia','MYR','RM','Asia','Ringgit Malayo',NULL),
(48,'Asia','Nepal Rupee','Nepal','NPR','रु','Asia','Rupia Nepali',NULL),
(49,'Asia','Philippine Peso','Philippines','PHP','₱','Asia','Peso Filipino',NULL),
(50,'Asia','Pakistan Rupee','Pakistan','PKR','Rs','Asia','Rupia Pakistani',NULL),
(51,'Asia','Singapore Dollar','Singapur','SGD','$','Asia','Dolar De Singapur',NULL),
(52,'Asia','Thai Baht','Thailand','THB','฿','Asia','Baht Tailands',NULL),
(53,'Asia','New Taiwan Dollar‚S','Taiwan','TWD','$','Asia','Nuevo Dolar Taiwan‚S',NULL),
(54,'Asia','Vietnamese Dong','Vietnam','VND','₫','Asia','Dong Vietnamita',NULL),
(55,'Oceania','Australian Dollar','Australia, Christmas Island, Cocos (Keeling) Islands, Heard Island and McDonald Islands, Kiribati, Nauru, Norfolk Island, Tuvalu','AUD','$','Oceania','Dolar Australiano',NULL),
(56,'Oceania','Fijian Dollar','Fiji','FJD','$','Oceania','Dolar Fiyiano',NULL),
(57,'Oceania','New Zealand Dollar','New Zealand','NZD','$','Oceania','Dolar De Nueva Zelanda',NULL),
(58,'Oceania','Cfp Franc','Wallis and Futuna','XPF','₣','Oceania','Franco Cfp',NULL),
(59,'Africa','Egyptian Pound','Egypt','EGP','œ','Africa','Libra Egipcia',NULL),
(60,'Africa','Ghana Cedi','Ghana','GHS','GH₵','Africa','Cedi De Ghana',NULL),
(61,'Africa','Dalasi','Gambia','GMD','D','Africa','Dalasi',NULL),
(62,'Africa','Kenyan Shilling','Kenya','KES','Sh','Africa','Chelin Keniano',NULL),
(63,'Africa','Moroccan Dirham','Morocco','MAD','DH','Africa','Dirham Marroqui',NULL),
(64,'Africa','Malagasy Ariary','Madagascar','MGA','Ar','Africa','Ariary Malgache',NULL),
(65,'Africa','Mauritian Rupee','Mauricio','MUR','₨','Africa','Rupia De Mauricio',NULL),
(66,'Africa','Namibian Dollar','Namibia','NAD','N$','Africa','Dolar De Namibia',NULL),
(67,'Africa','Nigerian Naira','Nigeria','NGN','₦','Africa','Naira Nigeriana',NULL),
(68,'Africa','Rupee','Seychelles','SCR','Rs‎','Africa','Rupia',NULL),
(69,'Africa','Tunisian Dinar','Tunez','TND','DT','Africa','Tunisian Dinar',NULL),
(70,'Africa','Uganda Shilling','Uganda','UGX','Sh','Africa','Chelin Uganda',NULL),
(71,'Africa','Cfa Franc Beac','Cameroon, Central African Republic, Republic of the Congo, Chad, Equatorial Guinea, Gabon','XAF','Fr','Africa','Franco Cfa Beac',NULL),
(72,'Africa','Cfa Bceao Franc','Benin, Burkina Faso, Ivory Coast, Guinea-Bissau, Mali, Niger, Senegal, Togo','XOF','Fr','Africa','Franco Cfa Bceao',NULL),
(73,'Africa','South African Rand','Lesotho, Namibia, South Africa','ZAR','Br','Africa','Rand Sudafricano',NULL),
(74,'Middle East','United Arab Emirates Dirham','United Arab Emirates','AED','د.إ','Medio Oriente','Dirham De Los Emiratos Arabes Unidos',NULL),
(75,'Middle East','New Israeli Sheqel','Israel','ILS','₪','Medio Oriente','Nuevo Sheqel Israeli',NULL),
(76,'Middle East','Syrian Pound','Syria','SYP','LS','Medio Oriente','Libra Siria',NULL),
(77,'Middle East','Jordanian Dinar','Jordan','JOD','د.ا','Medio Oriente','Dinar Jordano',NULL),
(78,'Middle East','Kuwaiti Dinar','Kuwait','KWD','KD','Medio Oriente','Dinar Kuwaiti',NULL),
(79,'Middle East','Lebanese Pound','Libano','LBP','ل.ل','Medio Oriente','Libra Libanesa',NULL),
(80,'Middle East','Omani Rial','Oman','OMR','ر.ع.','Medio Oriente','Rial Omani',NULL),
(81,'Middle East','Qatar Rial','Qatar','QAR','ر.ع.','Medio Oriente','Rial De Qatar',NULL),
(82,'Middle East','Saudi Riyal','currency','SAR','SAR','Medio Oriente','Riyal Saudi',NULL),
(83,'Cryptocurrencies','Bitcoin','BTC','BTC','B','Cripto Monedas','Bitcoin',NULL),
(84,'Cryptocurrencies','Ethereum','ETH','ETH','Ð','Cripto Monedas','Ethereum',NULL),
(85,'Cryptocurrencies','Litecoin','LTC','LTC','LTC','Cripto Monedas','Litecoin',NULL),
(86,'Cryptocurrencies','Ripples','XRP','XRP','XPR','Cripto Monedas','Ripples',NULL);
/*!40000 ALTER TABLE `currencybycountries` ENABLE KEYS */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-24  1:26:11
