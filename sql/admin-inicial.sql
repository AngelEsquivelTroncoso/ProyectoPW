-- ============================================================================
-- Proyecto PW — Administrador inicial
--
-- Ejecutar UNA vez despues de importar sql/schema-produccion.sql.
--
-- ANTES DE EJECUTAR:
--   1. Reemplaza 'admin@tu-dominio.mx' por el correo real del administrador.
--   2. Genera el hash de la contrasena que quieras usar:
--
--        php -r "echo password_hash('TU_CONTRASENA', PASSWORD_DEFAULT), PHP_EOL;"
--
--   3. Reemplaza el hash de ejemplo por el que imprimio el comando anterior.
--
-- El hash de este archivo corresponde a una contrasena de ejemplo y NO debe
-- usarse en produccion.
-- ============================================================================

INSERT INTO `usuarios` (`nombre`, `correo`, `password_hash`, `rol`)
VALUES (
    'Administrador',
    'admin@tu-dominio.mx',
    -- Contrasena de ejemplo: admin123 (cambiar antes de desplegar)
    '$2y$10$VjgFhrzRAQxTHVoQrVT7X.9qlLq/wXfU0ChzXcCTmC5Dz3B8pGSTy',
    'admin'
);
