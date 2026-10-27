# Boletas: diagnóstico y corrección

## Causa comprobada

Consulta de solo lectura de MySQL local durante esta revisión:

- Inscripción 1: alumno 1, grupo 1, periodo 1; comunidad del alumno: 9.
- Asignación 1: grupo 1, periodo 1, comunidad 4, nivel 4.
- `inscripciones.asigna_grupo_id` de la inscripción 1 contiene 385; esa asignación no existe.

El listado anterior asociaba inscripción y asignación únicamente por grupo. La generación comprobaba además la comunidad mediante `canUseInscripcionAsignacion`, devolvía `OUTSIDE_SCOPE` y `CatequesisHttp::enforce` lo convertía en 404. La ruta sí existe. No se encontró una traza histórica específica de este 404 en el log consultado; la causa se comprobó con las relaciones y el código.

Además, la consulta académica general exige exactamente una asignación por grupo/periodo. Por ello descartaba inscripciones con varias asignaciones; el controlador usaba `sole()` y rechazaba cualquier ambigüedad. Esa restricción sigue vigente para captura y consultas académicas que no incluyen una selección explícita.

## Solución aplicada

- Consulta específica de boletas: periodo activo, dependencias vivas, alcance del rol y coincidencia de grupo, periodo y comunidad.
- Listado y generación utilizan la misma regla. El filtro de catequista o nivel no empareja alumnos de otra comunidad.
- Una sola asignación se utiliza directamente; varias muestran selector. La selección se vuelve a autorizar en el servidor.
- Evaluaciones limitadas a la inscripción, nivel seleccionado, rubros vigentes y periodo activo; los registros históricos con periodo nulo heredan el de la inscripción.
- Duplicados de evaluación producen 409 para revisión, sin sobrescribir ni sumar registros arbitrariamente.
- Fórmula compartida con el resumen final de Evaluaciones de Catequista: suma de puntos capturados / suma del valor de rubros × 10. Promedios de unidad a dos decimales y promedio final calculado desde valores sin redondeo intermedio. Las unidades sin captura quedan pendientes; los ceros registrados sí cuentan. Las capturas parciales conservan el denominador completo existente.
- Se mantiene la vista HTML institucional y `window.print()`. Se retiraron nombres ficticios usados como valores de respaldo.

## Datos pendientes de revisión humana

La inscripción 1 sigue sin poder generar una boleta legítima con la asignación 1: sus comunidades son diferentes. La consulta nueva encontró cero boletas compatibles en el periodo 1 de la base local revisada. No se cambiaron comunidades, grupos, periodos, vínculos ni registros para forzar un resultado.

El esquema contiene `asigna_grupo_id`, pero el modelo y los flujos actuales de inscripción no lo utilizan. No se adoptó ese campo como autoridad ni se inventó una reparación de sus valores huérfanos. Secretaría debe verificar documentalmente el grupo, periodo y comunidad correctos y resolver las inconsistencias mediante un trabajo de datos independiente.

Las pruebas automatizadas utilizan SQLite en memoria y el esquema de pruebas del proyecto, sin alterar información real. Comprueban el flujo completo de filtros, selección y renderizado imprimible, los cuatro roles autorizados, límites de periodo/comunidad, calificaciones pendientes y equivalencia con Evaluaciones. La emisión de una boleta real del periodo 1 queda pendiente de corregir sus datos; una prueba sintética aprobada no demuestra que esas inscripciones inconsistentes sean válidas.
