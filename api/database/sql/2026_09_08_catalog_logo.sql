-- Logo opcional del catálogo (UUID → images.uuid)
ALTER TABLE `catalog`
  ADD COLUMN `logo` uuid DEFAULT NULL AFTER `cabeza_pagina`,
  ADD KEY `catalog_logo_foreign` (`logo`),
  ADD CONSTRAINT `catalog_logo_foreign` FOREIGN KEY (`logo`) REFERENCES `images` (`uuid`) ON DELETE SET NULL;
