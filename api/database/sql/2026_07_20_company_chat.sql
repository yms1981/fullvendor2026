-- Chat empresarial entre Sellers, Warehouse y Managers (scoped por company).
-- Conversaciones 1:N participantes y 1:N mensajes.
-- Participantes: solo roles seller | warehouse | manager (espejo company_user.type).

CREATE TABLE IF NOT EXISTS `company_conversations` (
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
  KEY `company_conversations_company_last_message_index` (`company_id`, `last_message_at`),
  KEY `company_conversations_context_index` (`context_type`, `context_id`),
  KEY `company_conversations_created_by_index` (`created_by`),
  CONSTRAINT `company_conversations_company_id_foreign`
    FOREIGN KEY (`company_id`) REFERENCES `companies` (`company_id`) ON DELETE CASCADE,
  CONSTRAINT `company_conversations_created_by_foreign`
    FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `company_conversation_participants` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `conversation_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `role_type` enum('seller','warehouse','manager','customer') NOT NULL,
  `last_read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_conversation_participants_unique` (`conversation_id`, `user_id`),
  KEY `company_conversation_participants_user_id_index` (`user_id`),
  CONSTRAINT `company_conversation_participants_conversation_id_foreign`
    FOREIGN KEY (`conversation_id`) REFERENCES `company_conversations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `company_conversation_participants_user_id_foreign`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `company_conversation_messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `conversation_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `message_type` enum('text','image','audio') NOT NULL DEFAULT 'text',
  `body` longtext DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `company_conversation_messages_conversation_created_index` (`conversation_id`, `created_at`),
  KEY `company_conversation_messages_user_id_index` (`user_id`),
  CONSTRAINT `company_conversation_messages_conversation_id_foreign`
    FOREIGN KEY (`conversation_id`) REFERENCES `company_conversations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `company_conversation_messages_user_id_foreign`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `company_conversation_attachments` (
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
  CONSTRAINT `company_conversation_attachments_message_id_foreign`
    FOREIGN KEY (`message_id`) REFERENCES `company_conversation_messages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
