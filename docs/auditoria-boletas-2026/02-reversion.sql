-- Solo tras una aplicación autorizada del plan 01 y comprobación del estado posterior.
-- Restituye el valor histórico 385; NO significa que esa asignación sea válida.
START TRANSACTION;
SELECT * FROM inscripciones WHERE id = 1 FOR UPDATE;

UPDATE inscripciones
SET asigna_grupo_id = 385, updated_at = '2026-05-02 10:54:00'
WHERE id = 1 AND alumno_id = 1 AND grupo_id = 1 AND periodo_id = 1
  AND asigna_grupo_id IS NULL AND deleted_at IS NULL
  AND created_at = '2026-05-02 10:54:00' AND updated_at = '2026-05-02 10:54:00'
  AND NOT EXISTS (SELECT 1 FROM asigna_grupo WHERE id = 385);

SELECT ROW_COUNT() AS filas_revertidas; -- Debe ser 1. No forzar si hubo cambios posteriores.
SELECT id, alumno_id, grupo_id, periodo_id, asigna_grupo_id, updated_at FROM inscripciones WHERE id = 1;
-- Confirmar solo si el historial de aplicación y el estado completo siguen coincidiendo.
ROLLBACK;
