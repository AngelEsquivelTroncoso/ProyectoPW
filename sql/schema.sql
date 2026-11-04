-- ============================================================================
-- Proyecto PW — Congreso SysIoT 2026
-- Esquema de base de datos + datos semilla
--
-- Uso con phpMyAdmin:
--   1. Abrir http://localhost/phpmyadmin
--   2. Pestaña "Importar" -> seleccionar este archivo -> Continuar
--   (o pegar todo el contenido en la pestaña "SQL" y ejecutar)
--
-- Credenciales de prueba:
--   Admin:   admin@congreso.test   / admin123
--   Cliente: cliente@congreso.test / cliente123
-- ============================================================================

CREATE DATABASE IF NOT EXISTS proyecto_pw
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE proyecto_pw;

-- Orden de borrado respetando las claves foráneas (permite reimportar limpio)
DROP TABLE IF EXISTS detalle_pedido;
DROP TABLE IF EXISTS pedidos;
DROP TABLE IF EXISTS productos;
DROP TABLE IF EXISTS usuarios;

-- ---------------------------------------------------------------------------
-- Usuarios (clientes y administradores)
-- ---------------------------------------------------------------------------
CREATE TABLE usuarios (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  nombre        VARCHAR(100) NOT NULL,
  correo        VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  rol           ENUM('cliente', 'admin') NOT NULL DEFAULT 'cliente',
  creado_en     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB;

-- ---------------------------------------------------------------------------
-- Productos (inscripciones y talleres). stock = cupos disponibles.
-- ---------------------------------------------------------------------------
CREATE TABLE productos (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  nombre      VARCHAR(150) NOT NULL,
  descripcion TEXT,
  precio      DECIMAL(10, 2) NOT NULL,
  stock       INT NOT NULL DEFAULT 0,
  activo      TINYINT(1) NOT NULL DEFAULT 1
) ENGINE = InnoDB;

-- ---------------------------------------------------------------------------
-- Pedidos (cabecera de la venta)
-- ---------------------------------------------------------------------------
CREATE TABLE pedidos (
  id                INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id        INT NOT NULL,
  total             DECIMAL(10, 2) NOT NULL,
  estado            ENUM('pendiente', 'pagado', 'cancelado') NOT NULL DEFAULT 'pendiente',
  paypal_order_id   VARCHAR(100) NULL,
  paypal_captura_id VARCHAR(100) NULL,
  creado_en         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_pedidos_usuario
    FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
) ENGINE = InnoDB;

-- ---------------------------------------------------------------------------
-- Detalle del pedido (líneas de venta)
-- ---------------------------------------------------------------------------
CREATE TABLE detalle_pedido (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  pedido_id       INT NOT NULL,
  producto_id     INT NOT NULL,
  cantidad        INT NOT NULL,
  precio_unitario DECIMAL(10, 2) NOT NULL,
  CONSTRAINT fk_detalle_pedido
    FOREIGN KEY (pedido_id) REFERENCES pedidos (id),
  CONSTRAINT fk_detalle_producto
    FOREIGN KEY (producto_id) REFERENCES productos (id)
) ENGINE = InnoDB;

-- ---------------------------------------------------------------------------
-- Datos semilla
-- ---------------------------------------------------------------------------

-- Contraseñas hasheadas con password_hash() de PHP (bcrypt, cost 10).
INSERT INTO usuarios (nombre, correo, password_hash, rol) VALUES
  (
    'Administrador',
    'admin@congreso.test',
    '$2y$10$bKpDeHkhPoPFbH4uLO.nvuOvQhnjGw5QhP5uxKBL.f5S2zDRn3pvi',
    'admin'
  ),
  (
    'Cliente de Prueba',
    'cliente@congreso.test',
    '$2y$10$mY8/N7c31LnrVYfpi.Dn6OfRTTpYMmWgRBEtsQfeLCMmnyv5j3SGC',
    'cliente'
  );

INSERT INTO productos (nombre, descripcion, precio, stock, activo) VALUES
  (
    'Inscripción Ponente',
    'Presentación de ponencia, publicación en la memoria y constancia de ponente.',
    1500.00,
    40,
    1
  ),
  (
    'Inscripción Participante',
    'Acceso a conferencias y ponencias, talleres con cupo limitado y constancia de asistencia.',
    800.00,
    120,
    1
  ),
  (
    'Taller: Sensores y actuadores con ESP32',
    'Construcción de un nodo de monitoreo ambiental paso a paso. Requiere laptop.',
    350.00,
    25,
    1
  ),
  (
    'Taller: Seguridad básica para prototipos',
    'Buenas prácticas de credenciales, cifrado y actualizaciones remotas.',
    300.00,
    25,
    1
  );
