-- Documentos Stripe (MySQL / MariaDB).
-- Equivalente al esquema de la imagen (SQL Server: dbo.stripe_documents, ntext -> LONGTEXT).

CREATE TABLE IF NOT EXISTS `stripe_documents` (
  `doc_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `urlStripe` longtext DEFAULT NULL,
  `amount` double DEFAULT NULL,
  `pagado` int DEFAULT NULL,
  `montopagado` double DEFAULT NULL,
  `fecha` datetime DEFAULT NULL,
  `fechapagado` datetime DEFAULT NULL,
  PRIMARY KEY (`doc_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
