-- PROPUESTA PENDIENTE DE AUTORIZACIÓN. NO HABILITA LA BOLETA.
-- Ejecutar en sesión MySQL supervisada, solo tras aprobación de Secretaría.
-- Los archivos terminan en ROLLBACK deliberadamente. No ejecutar por lote con COMMIT.
-- Crear respaldo fresco y comprobar que este estado no cambió antes de comenzar.
START TRANSACTION;
SELECT * FROM inscripciones WHERE id = 1 FOR UPDATE;
SELECT id FROM asigna_grupo WHERE id = 385 FOR UPDATE;

UPDATE inscripciones
SET asigna_grupo_id = NULL, updated_at = '2026-05-02 10:54:00'
WHERE id = 1 AND alumno_id = 1 AND grupo_id = 1 AND periodo_id = 1
  AND asigna_grupo_id = 385 AND deleted_at IS NULL
  AND created_at = '2026-05-02 10:54:00' AND updated_at = '2026-05-02 10:54:00'
  AND NOT EXISTS (SELECT 1 FROM asigna_grupo WHERE id = 385);

SELECT ROW_COUNT() AS filas_modificadas; -- Debe ser exactamente 1; si no, ROLLBACK y revisar.
SELECT id, alumno_id, grupo_id, periodo_id, asigna_grupo_id, updated_at FROM inscripciones WHERE id = 1;
-- Revisar valores y autorización antes de COMMIT manual en esa misma sesión.
ROLLBACK;
