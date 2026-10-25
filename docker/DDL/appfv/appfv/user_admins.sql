-- Tabla para el login de la plataforma admin (/admin).
-- Los administradores inician sesión con email y password desde esta tabla.

CREATE TABLE IF NOT EXISTS user_admins
(
    id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name           VARCHAR(255)    NOT NULL,
    email          VARCHAR(255)    NOT NULL,
    password       VARCHAR(255)    NOT NULL,
    status         TINYINT UNSIGNED DEFAULT 1 COMMENT '1=Activo, 0=Inactivo',
    remember_token VARCHAR(100)   NULL,
    created_at     TIMESTAMP       NULL,
    updated_at     TIMESTAMP       NULL,
    UNIQUE KEY user_admins_email_unique (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
