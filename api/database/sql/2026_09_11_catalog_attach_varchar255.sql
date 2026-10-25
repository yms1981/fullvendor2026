-- Ampliar catalog.attach para guardar la definición completa de Catalog Studio
-- (template::show_prices::banner_bg::banner_text::footer_bg::footer_text::accent ≈ 55–70 chars).
-- Antes: varchar(50) truncaba/fallaba en STRICT y el tema no llegaba al catálogo público.

ALTER TABLE `catalog`
  MODIFY COLUMN `attach` varchar(255) DEFAULT NULL;
