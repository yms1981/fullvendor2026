-- Procedencia del alta de órdenes (token Sanctum / usuario / rol).
-- Si las columnas ya existen (schema:sync / dump), omitir este script.

ALTER TABLE `orders`
  ADD COLUMN `token_create` varchar(512) DEFAULT NULL
    COMMENT 'Identificador/hash del token con el que se creó la orden'
    AFTER `source`,
  ADD COLUMN `user_id_token` bigint(20) unsigned DEFAULT NULL
    COMMENT 'users.id del token que creó la orden'
    AFTER `token_create`,
  ADD COLUMN `rol_token` varchar(64) DEFAULT NULL
    COMMENT 'Rol del usuario en la compañía al crear (warehouse, seller, manager, …)'
    AFTER `user_id_token`;

CREATE INDEX `orders_user_id_token_index` ON `orders` (`user_id_token`);
