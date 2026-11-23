-- Esquema de la base de datos del catálogo multitienda.
-- Ejecutar una sola vez sobre una base de datos vacía (por ejemplo `catalogo`).

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS usuarios (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre        VARCHAR(120) NOT NULL,
  email         VARCHAR(190) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  creado_en     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_usuarios_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tiendas (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  usuario_id  INT UNSIGNED NOT NULL,
  slug        VARCHAR(60)  NOT NULL,
  nombre      VARCHAR(120) NOT NULL,
  whatsapp    VARCHAR(20)  NOT NULL,
  descripcion VARCHAR(500) NOT NULL DEFAULT '',
  logo        VARCHAR(120) NULL,
  color       CHAR(7)      NOT NULL DEFAULT '#16a34a',
  creado_en   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_tiendas_slug (slug),
  UNIQUE KEY uq_tiendas_usuario (usuario_id),
  CONSTRAINT fk_tiendas_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS productos (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  tienda_id   INT UNSIGNED NOT NULL,
  nombre      VARCHAR(150) NOT NULL,
  descripcion TEXT NULL,
  precio      DECIMAL(12,2) NOT NULL,
  imagen      VARCHAR(120) NULL,
  activo      TINYINT(1) NOT NULL DEFAULT 1,
  creado_en   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_productos_tienda (tienda_id, activo),
  CONSTRAINT fk_productos_tienda FOREIGN KEY (tienda_id) REFERENCES tiendas (id) ON DELETE CASCADE,
  CONSTRAINT chk_productos_precio CHECK (precio >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
