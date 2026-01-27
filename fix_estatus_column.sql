-- Script SQL para aumentar el tamaño de la columna Estatus en la tabla solicitudes
-- Ejecutar este script directamente en la base de datos si la migración no funciona

ALTER TABLE solicitudes MODIFY COLUMN Estatus VARCHAR(100) NOT NULL;

