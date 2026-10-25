-- Chat empresarial: adjuntos (fotos/audio) + tipo de mensaje.
-- Complementa 2026_07_20_company_chat.sql

ALTER TABLE `company_conversation_messages`
  MODIFY `body` longtext NULL,
  ADD COLUMN `message_type` enum('text','image','audio') NOT NULL DEFAULT 'text' AFTER `user_id`;

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
