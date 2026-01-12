-- Script SQL para corregir deleted_at en solicitudes
-- Establece deleted_at = NULL para solicitudes donde deleted_at = created_at
-- Esto corrige el problema donde deleted_at se establece incorrectamente al crear

UPDATE solicitudes 
SET deleted_at = NULL 
WHERE deleted_at IS NOT NULL 
  AND deleted_at = created_at;

-- Verificar resultado
SELECT SolicitudID, Estatus, created_at, deleted_at 
FROM solicitudes 
ORDER BY created_at DESC 
LIMIT 5;


