-- Dispositivos móviles FullVendor + sincronización + log de cambios
-- Umbral de sync total: 500 cambios desde last_sync_at (ver MobileSyncService).

CREATE TABLE IF NOT EXISTS `mobile_devices` (
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
  CONSTRAINT `mobile_devices_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `mobile_devices_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`company_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Dispositivos que acceden a FullVendor (app móvil)';

CREATE TABLE IF NOT EXISTS `mobile_sync_logs` (
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
  CONSTRAINT `mobile_sync_logs_device_foreign` FOREIGN KEY (`mobile_device_id`) REFERENCES `mobile_devices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `mobile_sync_logs_company_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`company_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Historial de sincronizaciones solicitadas por dispositivo';

CREATE TABLE IF NOT EXISTS `mobile_change_log` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Ítems modificados por grupo para sync parcial móvil';
