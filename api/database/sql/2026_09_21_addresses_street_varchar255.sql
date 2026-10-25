-- ============================================================================
-- 2026_09_21_addresses_street_varchar255.sql
-- Amplía addresses.street para alinear con validación API (max:255)
-- y direcciones de entrega concatenadas.
-- ============================================================================

ALTER TABLE `addresses`
    MODIFY COLUMN `street` VARCHAR(255) NOT NULL DEFAULT '';
