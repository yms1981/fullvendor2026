-- Control FIFO de ventas vs notas de crédito por cliente / producto / orden origen.
-- Capas = líneas de venta (tipo_d=D). Allocations = consumo por líneas de NC (tipo_d=C).
-- Regla: credit_price <= sale_price de la capa; qty_remaining = qty_sold - qty_credited.
-- Tipos alineados con orders/order_details/customers/products (bigint).

CREATE TABLE IF NOT EXISTS `customer_order_product_fifo` (
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
  CONSTRAINT `fifo_source_order_foreign` FOREIGN KEY (`source_order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  CONSTRAINT `fifo_source_detail_foreign` FOREIGN KEY (`source_detail_id`) REFERENCES `order_details` (`detail_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Capas FIFO vendido/devuelto por cliente-producto-orden';

CREATE TABLE IF NOT EXISTS `customer_order_product_fifo_allocations` (
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
  CONSTRAINT `fifo_alloc_fifo_foreign` FOREIGN KEY (`fifo_id`) REFERENCES `customer_order_product_fifo` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fifo_alloc_company_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`company_id`) ON DELETE CASCADE,
  CONSTRAINT `fifo_alloc_credit_order_foreign` FOREIGN KEY (`credit_order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  CONSTRAINT `fifo_alloc_credit_detail_foreign` FOREIGN KEY (`credit_detail_id`) REFERENCES `order_details` (`detail_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Consumo FIFO de capas por líneas de nota de crédito';
