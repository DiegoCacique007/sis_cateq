# Auditoría de datos de boletas — revisión pendiente

Fecha: 26 de octubre de 2026. No se aplicó ningún UPDATE, INSERT ni DELETE a la base real.

## Alcance y evidencia

El periodo 1, del 01/01/2026 al 31/12/2026, es el único periodo no eliminado y tiene `estado = 1`. Coincide con el periodo que selecciona por defecto `AsegurarPeriodoActivo`; no fue necesario leer sesiones de usuarios. Se auditaron las dos inscripciones vigentes y se revisó también el historial eliminado conservado en las tablas.

| Inscripción | Alumno | Grupo / periodo | Comunidad del alumno | Referencia explícita | Evidencia y diagnóstico |
|---|---|---|---|---|---|
| 1 | 1 | A (1) / 1 | Santa Maria Pipioltepec (9) | 385 | No existe, ni siquiera como eliminada. No tiene evaluaciones. La única asignación vigente de A es la 1, comunidad El Arco (4), nivel 4, catequista 15. No es una sustitución autorizable por inferencia. |
| 2 | 2 | A (1) / 1 | San Gabriel Ixtla (2) | NULL | Tiene 30 evaluaciones, en cinco unidades del nivel 4. La asignación 1 coincide en grupo, periodo y nivel, pero no en comunidad. Esa coincidencia no demuestra qué dato de comunidad está equivocado. |

No hay asignaciones vigentes en las comunidades 9 o 2 para este periodo; tampoco hay alternativas académicas exactas en otro grupo. Hay dos asignaciones vigentes: 1 → A / El Arco / nivel 4 / catequista 15 y 2 → B / La Candelaria / nivel 4 / catequista 19. Ambas tienen dependencias vigentes y catequistas aprobados con rol catequista.

Los grupos A y B tienen `periodo_id = NULL`. Sus asignaciones sí tienen periodo 1; el alcance actual se basa en asignación e inscripción. El NULL no demuestra un periodo incorrecto ni justifica rellenarlo automáticamente: Secretaría debe confirmar si los grupos son catálogos reutilizables o pertenecen a un ciclo específico.

Las 30 evaluaciones pertenecen a la inscripción 2: seis tienen periodo 1 y 24 tienen periodo NULL, admitido expresamente por la lógica histórica como herencia de la inscripción. Todas las unidades evaluadas pertenecen al nivel 4. No se detectaron duplicados, valores fuera del máximo del rubro ni referencias vivas inválidas de unidad/rubro en estas evaluaciones. No se propone normalizar los NULL como si fueran errores.

La inscripción 3, del alumno 2 al grupo B, fue eliminada el 21/09/2026; no autoriza restaurarla ni trasladar al alumno. Las asignaciones 3–12 están eliminadas y corresponden a otros niveles de A/B. No existe evidencia de una asignación 385 entre las doce conservadas. No se encontró un dump histórico SQL en el repositorio. No se puede determinar si 385 proviene de una importación, de otro entorno o de una eliminación física; afirmarlo sería especular.

El controlador y el modelo actuales de inscripción guardan alumno, grupo y periodo, pero no mantienen `asigna_grupo_id`. Esto explica por qué ese campo puede estar nulo o desfasado, sin demostrar quién introdujo 385. El acceso a boletas sigue comprobando comunidad/grupo/periodo; corregir solamente ese campo no resuelve las boletas.

## Cambios propuestos para revisión

### P1 — Limpieza técnica inequívoca, sin recuperación académica

Única fila: inscripción 1, `asigna_grupo_id: 385 → NULL`. El campo admite NULL y el destino no existe. Se conserva íntegramente el estado anterior en el respaldo; no se inventa un sustituto. No cambia alumno, comunidad, grupo, nivel, periodo, notas ni catequista y NO habilita la boleta. Pendiente de autorización explícita. SQL preparado: `01-limpieza-propuesta.sql`; reversión: `02-reversion.sql`.

### P2 — Relaciones académicas: requieren Secretaría

Para cada alumno 1 y 2 confirmar con la ficha de inscripción/lista del catequista:

1. Comunidad correcta y qué representa `alumnos.comunidad_id`: pertenencia académica o residencia.
2. Grupo, nivel y catequista realmente asignados durante 2026.
3. Si la asignación 1 pertenece efectivamente a El Arco y qué alumnos debe atender.

Solo si la documentación confirma que el dato del alumno está equivocado se podrá proponer cambiar su comunidad. No se propone cambiar ambas comunidades a 4 para hacer coincidir la consulta. Si sus comunidades actuales son correctas, las asignaciones existentes no permiten recuperar una relación válida sin una decisión organizativa adicional. Modificar la comunidad de la asignación 1 afecta a ambas inscripciones del grupo A y no resuelve dos comunidades distintas a la vez.

No hay correcciones académicas inequívocas aplicables con la evidencia actual. No hay un UPDATE académico preparado con valores inventados. Después de la decisión se deberá producir un plan nuevo con valores antes/después, filas dependientes e impacto sobre el alcance de coordinadores, y someterlo a aprobación.

## Respaldo creado y verificado

Directorio privado: `storage/app/private/audits/boletas-20261026-220924/` (excluido de Git y fuera de `public/`).

- `snapshot.json`: tablas académicas completas, incluidos eliminados, más referencias de usuarios sin contraseñas, tokens ni correos.
- `schema.json`: `SHOW CREATE TABLE` de las once tablas académicas, todas InnoDB.
- `diagnostico.json` y `inscripciones.csv`: resultados por inscripción y alertas de asignaciones.
- `propuestas.json`: cero reasignaciones académicas inequívocas. P1 es una propuesta técnica separada en este documento, no una reasignación.
- `manifest.json`: conteos y SHA-256 de los cinco archivos.

Se capturó una instantánea consistente en transacción MySQL de solo lectura y aislamiento REPEATABLE READ. Contiene 114 filas académicas y diez referencias de usuarios. Se comprobaron los cinco hashes y se restauraron las 124 filas en SQLite en memoria, comparando todos sus valores. Se ensayaron la aplicación y ROLLBACK, y una aplicación confirmada seguida de reversión, recuperando exactamente el estado original de las inscripciones. No se ejecutó el SQL de corrección en MySQL.

Es un respaldo académico, no una copia completa de la aplicación ni de cuentas/configuración. La restauración de valores en SQLite no reproduce los tipos, restricciones ni disparadores de MySQL. Para P1 se verifica además que no haya disparadores de inscripciones y se mantendrán las comprobaciones en la transacción real. No restaurar todas las tablas sobre producción para deshacer una sola corrección.

## Procedimiento transaccional después de aprobación

1. Registrar quién aprobó P1, el estado antes/después y el hash del plan revisado. Sin aprobación no ejecutar el UPDATE.
2. Detener temporalmente las escrituras del módulo durante la ventana acordada. No desactivar permisos, claves foráneas ni protecciones.
3. Crear otra instantánea fresca con `php tools/audit-boletas.php 1`, verificar sus hashes y compararla con la revisada. Si existen diferencias en filas afectadas/dependientes, detenerse y presentar un plan actualizado.
4. En una única conexión MySQL, iniciar transacción y bloquear la inscripción 1 y la clave 385 de asignaciones. Revalidar campos completos, ausencia de 385 y ausencia de efectos secundarios nuevos.
5. Ejecutar el UPDATE condicionado. Exigir una única fila modificada; si es cero o hay cualquier discrepancia, ROLLBACK. Conservar `created_at` y `updated_at` originales para que la reversión del plan sea exacta; la trazabilidad reside en el expediente antes/después y la autorización, no en un cambio silencioso de fecha.
6. Inspeccionar el estado posterior dentro de esa conexión. Los archivos entregados terminan en ROLLBACK como medida de revisión. Solo con aprobación vigente y verificación satisfactoria ejecutar COMMIT manual en lugar de ROLLBACK, y guardar un comprobante antes/después.
7. Para revertir P1, comprobar primero el comprobante de aplicación y que la fila completa no tuvo cambios posteriores. Ejecutar el SQL inverso con las mismas garantías, verificar una fila y confirmar solamente si coincide con el estado esperado. Si otro proceso modificó la inscripción, no sobrescribirlo. Recuperar 385 solo restaura el valor histórico, no legitima esa asignación.
8. Tras un futuro plan académico autorizado, probar las boletas reales correspondientes: tarjeta → selección → documento → vista previa de impresión. Verificar nombre, grupo, nivel, catequista, periodo y notas; repetir los accesos por los cuatro roles, denegación de otras comunidades/periodos y las pruebas automatizadas.

## Comandos reproducibles

Resultado de la regresión ejecutada tras la auditoría: **868 pruebas aprobadas, 5708 aserciones** (autorización, boletas, alumnos, inscripciones, evaluaciones y chatbot). Sintaxis PHP de ambas herramientas y `git diff --check` correctos. Verificación adicional de MySQL: cero disparadores en inscripciones, una única referencia a 385 y cero asignaciones con ese ID, incluyendo eliminadas.

Hashes SHA-256 de los planes entregados para revisión:

- P1: `E91E2E340B233C9D8CD4764EEEAFC8F2E0775A9F657179486F827177140E99EC`.
- Reversión: `588E3CFCACC65D8330BAA97B34604147D9628C65E00FCF51EA5B14201DD470B9`.

```powershell
C:/xampp/php/php.exe tools/audit-boletas.php 1
C:/xampp/php/php.exe tools/verify-boletas-backup.php storage/app/private/audits/boletas-20261026-220924
C:/xampp/php/php.exe artisan test tests/Feature/Authorization tests/Feature/Chatbot tests/Unit/Chatbot
```

El script de auditoría únicamente consulta la base y escribe archivos privados. El verificador no inicia Laravel ni se conecta a MySQL; opera exclusivamente en SQLite en memoria. Se preservaron tarjetas, plantilla institucional y lógica de producción. La apertura e impresión de boletas reales queda pendiente de la decisión académica y de la aprobación del plan correspondiente.
