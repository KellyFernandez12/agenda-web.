USE agenda;

-- 1. Respaldo antes de modificar eventos
CREATE TABLE eventos_respaldo_p8 AS SELECT * FROM eventos;

-- 2. Tabla de usuarios
CREATE TABLE usuarios (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  nombre     VARCHAR(80) NOT NULL,
  correo     VARCHAR(120) NOT NULL UNIQUE,
  clave_hash VARCHAR(255) NOT NULL,
  creado_en  DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 3. Regístrate primero y reemplaza 1 por tu id real
ALTER TABLE eventos ADD COLUMN usuario_id INT NULL AFTER id;
UPDATE eventos SET usuario_id = 1 WHERE id > 0;

ALTER TABLE eventos
  MODIFY usuario_id INT NOT NULL,
  ADD CONSTRAINT fk_eventos_usuario
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
    ON DELETE CASCADE;

DESCRIBE eventos;
