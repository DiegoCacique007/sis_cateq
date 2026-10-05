# Núcleo compartido de autorización — fase 3

## Análisis y límite de integración

Se revisaron User, UserRole, EnsureUserApproved, CheckRole, AsegurarPeriodoActivo,
los once modelos de Secretaría solicitados, las rutas y las consultas de
MiGrupoController, Catequista/EvaluacionController, AlumnoComunidadController,
BoletaController y CoordComunidadController.

Los modelos académicos usan SoftDeletes. AsignaGrupo conserva id, catequista_id,
comunidad_id, grupo_id, nivel_id y periodo_id. Inscripcion no tiene asigna_grupo_id:
la correlación existente es grupo_id + periodo_id. Alumno tiene comunidad_id,
pero los controladores del catequista no garantizan que coincida con la comunidad
de la asignación; por eso no se utiliza para elegir entre asignaciones competidoras.

En fase 3 no se modificaron controladores, rutas, middleware, modelos ni migraciones.
La adopción de fase 4 se describe en «Integración con controladores existentes»;
esa sección actualiza los límites de integración descritos inicialmente aquí.
No se crean Policies, chatbot, agentes, DTO ni serializadores.

## Contrato

- AccessContext es readonly (PHP 8.2), con userId, UserRole, status,
  communityId nullable y activePeriodId nullable, sin modelos completos.
- AccessContextResolver recibe la petición del servidor, toma su identidad
  autenticada y recarga el usuario de la base. Ignora input user_id, role, status,
  comunidad_id y periodo_activo_id. Usuarios ausentes, no aprobados o con rol
  desconocido generan AuthorizationException (`UNAUTHORIZED_CONTEXT`).
- El periodo se toma exclusivamente de la sesión, comprobando existencia y
  ausencia de borrado lógico. Si falta o es inválido queda null; no se elige otro.
- El contexto es una instantánea por operación/petición: no persistirlo en sesión,
  caché, jobs o singletons ni construirlo desde datos externos. Un consumidor futuro
  debe resolverlo nuevamente al ejecutar, especialmente antes de escribir.
- CatequesisAccess::can devuelve AccessDecision, **no bool**. Usar isAllowed().
  Sus estados son allowed, denied, missing_context y ambiguous. Los códigos son
  ALLOWED, ROLE_NOT_ALLOWED, UNAUTHORIZED_CONTEXT, MISSING_PERIOD,
  MISSING_COMMUNITY, OUTSIDE_SCOPE y AMBIGUOUS_ASSIGNMENT.
- can() permite la capacidad general; nunca basta por sí solo para acceder a un ID.
  canViewAsignacion, canViewInscripcion, canViewAlumno, canViewEvaluacion,
  canViewBoleta y canManageEvaluacion comprueban el recurso. canCaptureEvaluacion
  verifica inscripción, unidad del nivel asignado y rubro antes de una nueva captura.

## Matriz implementada

| Capacidad | Secretaría | Catequista | Párroco | Coord. general | Coord. comunidades |
|---|---|---|---|---|---|
| VIEW_GROUPS, VIEW_GROUP_STUDENTS, VIEW_EVALUATIONS | Sí | Propias | Sí | Sí | Comunidad |
| VIEW_ATTENDANCE_LIST | No | Propias | No | No | No |
| MANAGE_EVALUATIONS | Sí | Propias | No | No | No |
| VIEW_BOLETAS | Sí | No | Sí | Sí | Comunidad |
| VIEW_STUDENTS, VIEW_COMMUNITIES | Sí | No | Sí | Sí | Comunidad |
| VIEW_CATECHISTS | No | No | Sí | No | Comunidad |
| VIEW_TUTORS, VIEW_INSCRIPTIONS, VIEW_LEVELS | Sí | No | No | Sí | No |
| MANAGE_STUDENTS, MANAGE_TUTORS, MANAGE_INSCRIPTIONS, MANAGE_ASSIGNMENTS | Sí | No | No | No | No |
| MANAGE_COMMUNITIES, MANAGE_PERIODS, MANAGE_LEVELS, MANAGE_UNITS | Sí | No | No | No | No |
| MANAGE_RUBRICS, MANAGE_USERS, MANAGE_GROUPS | Sí | No | No | No | No |

Las capacidades de lectura adicionales reflejan las rutas GET existentes;
MANAGE_GROUPS refleja el resource CRUD de grupos. VIEW_GROUP_STUDENTS también
representa las lecturas de alumnos por grupo necesarias para evaluaciones/boletas.
No se concede una ruta independiente de catequistas a Secretaría ni de asistencia
a los supervisores por inferencia de sus otras capacidades.

Las capacidades de trabajo por grupo, asistencia, evaluaciones y boletas requieren
periodo. La consulta de alumnos del coordinador comunitario también. Las capacidades
administrativas de catálogos no requieren periodo por sí mismas. Los códigos de
capacidad no sustituyen las futuras comprobaciones sobre recursos administrativos.

## Consultas y alcance

`app(AccessibleAsignaciones::class)->for($context)` y las otras tres clases devuelven
Builders Eloquent restringidos en SQL, antes de get/paginate/exists. No serializan
modelos ni cargan relaciones automáticamente. Los filtros del usuario se traducen
por código confiable a condiciones AND adicionales (y los OR deben agruparse).
No aceptar SQL, nombres de columnas, scopes o métodos del builder desde requests.
No quitar restricciones ni usar withoutGlobalScopes/withTrashed en consumidores.
`AccessibleAsignaciones::valid()` es exclusivamente una base interna sin autorización.

- Asignaciones: periodo exacto y relaciones vigentes con comunidad, grupo, nivel,
  periodo y usuario. Catequista exige catequista_id = userId; coordinador exige
  comunidad_id = communityId. No se agrupa ni se selecciona la primera asignación.
- Inscripciones: consulta **académica del periodo de trabajo**, derivada de
  asignaciones autorizadas, con grupo/periodo exactos, alumno y comunidad vigentes
  y una única asignación válida. No es un listado administrativo histórico de todas
  las inscripciones. Inscripciones sin asignación o con periodo NULL se excluyen.
- Alumnos: catequista deriva de inscripciones autorizadas; coordinador exige además
  comunidad del alumno igual a users.comunidad_id. Secretaría, párroco y coordinador
  general conservan lectura general del catálogo de alumnos con comunidad vigente.
- Evaluaciones: derivan de inscripciones autorizadas y exigen unidad del nivel de
  la asignación y rubro vigentes. Un periodo explícito diferente se excluye. NULL
  hereda el periodo de la inscripción porque el guardado actual del catequista
  omite evaluaciones.periodo_id. No se permite NULL en inscripciones/asignaciones.
- Consultas sin contexto requerido devuelven cero registros. Usar CatequesisAccess
  para distinguir falta de contexto de un listado vacío.
- IDs inexistentes y externos producen OUTSIDE_SCOPE sin cargar/devolver el registro.

Ejemplo de consumo futuro (no integrado en esta fase):

```php
$context = app(AccessContextResolver::class)->resolve($request);
$access = app(CatequesisAccess::class);
$decision = $access->can($context, CatequesisCapability::ViewGroups);
if (!$decision->isAllowed()) {
    // Traducir el código a la respuesta apropiada, sin consultar datos externos.
}
$query = app(AccessibleAsignaciones::class)->for($context);
// Un filtro validado REDUCE; no reemplaza la comunidad del contexto.
$query->where('asigna_grupo.comunidad_id', $validatedCommunityId);
```

## Ambigüedad

Ejemplo: grupo 7, periodo 2 tiene asignaciones A (catequista 10, comunidad 3,
nivel 1) y B (catequista 20, comunidad 5, nivel 2). Una inscripción solo indica
grupo 7 y periodo 2. No puede determinarse A o B a partir del esquema.
Se cuentan todas las asignaciones válidas, sin ocultar B por el filtro del actor.
Se excluye la inscripción y sus evaluaciones de las consultas autorizadas.
canViewInscripcion/canViewBoleta retornan AMBIGUOUS_ASSIGNMENT únicamente cuando
el actor tiene una asignación candidata autorizada; un usuario externo recibe
OUTSIDE_SCOPE. Las evaluaciones/alumnos excluidos reciben denegación segura.
También se rechazan duplicados o múltiples niveles del mismo catequista.
La consulta mantiene otras inscripciones inequívocas; no elige first().

## Riesgos y decisiones pendientes

1. Integrar gradualmente esta capa en endpoints y Policies. Al terminar fase 3 persistían consultas
   comunitarias sin límite por users.comunidad_id y boletas por ID sin alcance;
   no afirmar que toda la aplicación ya aplica este núcleo. Ver avances de fase 4 abajo.
2. Solo una comunidad por coordinador. NULL deniega; no se infiere otra comunidad.
3. Validar los datos reales: no se ha consultado ni modificado la base local.
   Decidir posteriormente cómo corregir asignaciones duplicadas, periodos NULL y
   vínculos faltantes. No hay migración para asigna_grupo_id en esta fase.
4. La política conservadora excluye inscripciones ambiguas incluso a supervisores
   y Secretaría en estas consultas académicas. La administración general mantiene
   su capacidad, pero requiere adaptadores de recursos separados al integrarla.
5. AsegurarPeriodoActivo aún elige el periodo más reciente si falta sesión; este
   resolver no lo hace. El orden de integración debe preservar esa distinción.
6. Para futuras escrituras, autorizar todos los destinos y ejecutar con transacción
   y controles de concurrencia apropiados; la decisión es una instantánea, no un lock.
7. El builder es para código del servidor, no una API que reciba consultas arbitrarias.
   La proyección de campos y las relaciones que se pueden mostrar siguen pendientes.
8. Las pruebas usan esquema académico mínimo en SQLite en memoria. No validan
   migraciones de producción, rendimiento/indexación MySQL ni la suite completa.

## Pruebas

`tests/Support/CatequesisTestCase.php` reutiliza las migraciones de autenticación
de AuthTestCase y crea únicamente las columnas académicas necesarias en memoria.
`tests/Feature/Authorization/CatequesisAccessTest.php` comprueba matriz, propiedad,
periodo, comunidad, captura de evaluaciones, soft deletes de registros y relaciones,
IDs externos, suplantación por input, revocación, roles inválidos y ambigüedad.

## Integración con controladores existentes

### Flujos integrados (fase 4)

- Catequista/MiGrupoController: listado y PDF resuelven contexto, comprueban
  capacidades y usan AccessibleAsignaciones, AccessibleInscripciones y
  AccessibleAlumnos. El PDF verifica permisos en su propia petición. Se conservan
  plantilla, orientación, tamaño y nombre del archivo. Con varias asignaciones
  propias se exige selección; no se usa la primera por defecto.
- Catequista/EvaluacionController: lectura mediante consultas autorizadas.
  Captura, actualización, restauración y borrado validan el destino con el núcleo
  dentro de una transacción. Una entrada ajena rechaza todo el lote y revierte
  modificaciones previas. Se bloquean las filas de asignación, inscripción y
  evaluación consultadas. Las nuevas evaluaciones guardan el periodo autorizado.
- CoordComunidadController: dashboard, catequistas y evaluaciones limitados.
  Los conteos se realizan en SQL sobre las consultas autorizadas; los catequistas
  deben estar aprobados y tener asignación vigente en la comunidad y periodo.
- Secretaria/AlumnoComunidadController: usuarios comunitarios restringidos por
  contexto, incluyendo inscripciones precargadas y catálogos de filtros.
  Secretaría conserva acceso a ambas comunidades y a alumnos con inscripción sin
  asignación inequívoca mediante forStudentReport. En esos casos no se infiere nivel.
- Secretaria/BoletaController: listado y generación autorizados. Los IDs externos
  e inexistentes devuelven la misma respuesta. Se verifica grupo, periodo y comunidad
  del alumno frente a la asignación; el nivel se obtiene de la asignación inequívoca.
  Evaluaciones y unidades se limitan al contexto autorizado.
- Secretaria/GrupoController::index y ComunidadController::index: dependencias
  necesarias porque el coordinador comparte estos endpoints. Las nuevas consultas
  AccessibleGrupos, AccessibleComunidades y AccessibleCatequistas centralizan
  sus límites. Los métodos CRUD de estos controladores no cambian su lógica.

No cambian URLs, nombres de rutas, formularios, plantilla PDF ni diseño de boletas.
La vista de alumnos deja de cargar relaciones sin alcance y de elegir first()
para inferir asignación/nivel. Cuando varias inscripciones impiden mostrar un
nivel único, muestra los textos vacíos que ya tenía la plantilla.
Welcome muestra el mensaje flash de contexto faltante para hacer visible la
redirección segura, sin regresar al endpoint que la originó.

### HTTP, identidad y ambigüedad

CatequesisHttp es un adaptador HTTP de AccessDecision, no una segunda matriz:
auth y approved permanecen en middleware; rol incorrecto 403; OUTSIDE_SCOPE 404;
ambigüedad 409 con mensaje genérico; falta de periodo o comunidad redirige a
welcome con un aviso visible. No se muestran IDs ni códigos internos.

canUseAsignacion exige además unicidad global de grupo/periodo para utilizar su
lista o capturar evaluaciones. canUseInscripcionAsignacion comprueba la combinación
de ambos recursos y la comunidad del alumno. Un asignacion_id explícito no permite
eludir ambigüedad. Las evaluaciones duplicadas del mismo destino también rechazan
la escritura con 409. Se preserva la selección por ID único, pero no first() para
resolver varias candidatas.

El request solo aporta filtros validados. Comunidad/catequista/periodo no reemplazan
el contexto de servidor. Los filtros usan AND sobre consultas ya restringidas;
el filtrado de colecciones posterior únicamente prepara presentación y cálculos.

### Periodos, históricos y calificaciones

- Las lecturas conservan evaluaciones con periodo_id NULL, heredado de su inscripción.
- Nuevas evaluaciones y actualizaciones/restauraciones llevan activePeriodId.
  No se ejecuta una migración masiva ni se alteran registros ajenos.
- La restauración usa withTrashed únicamente en la consulta de evaluaciones del
  destino previamente autorizado, conservando todos los filtros de alcance y
  las comprobaciones de relaciones vigentes.
- Inscripciones/asignaciones sin periodo y asignaciones ambiguas siguen excluidas
  de flujos académicos. La excepción del reporte administrativo de Secretaría
  conserva sus alumnos inscritos sin asignación, sin autorizar boletas incoherentes.
- Límites de captura: mínimo 0, máximo rubro.valor. Evidencia: la vista de catequista
  anuncia «Valor máx» y calcula (obtenido / total) * valorRubro. El catálogo de rubros
  ya valida valor entre 0 y 100. No se inventa una escala fija 0–10 por evaluación.
- AsegurarPeriodoActivo mantiene su selección previa del periodo más reciente.
  El resolver no elige otro periodo si el recibido en sesión no existe.

### Pruebas y límites de verificación

CatequesisControllersTest usa rutas reales, middleware y vistas sobre SQLite en
memoria. CatequesisWebTestCase amplía el esquema mínimo solo para pruebas. Cubre
PDF autorizado y real, ataques por ID, comunidad/periodo, evaluaciones históricas,
las cuatro operaciones de captura, rollback de lote, límites del rubro,
duplicados, boletas por rol, conservación del reporte de Secretaría y CRUD de
comunidades. Una prueba inspecciona SQL de alumnos para confirmar subconsultas
con catequista y periodo antes de recuperar filas.

No había otras pruebas académicas de controladores previas a esta integración.
Se ejecutan también las pruebas originales de las fases 1, 2 y 3; esto no implica
que la suite completa ni las migraciones de producción hayan sido verificadas.

### Pendiente y rendimiento

Siguen sin integrar al núcleo los dashboards/evaluaciones propios de ParrocoController
y CoordGeneralController, CatequistaController, y los controladores de Secretaría:
Dashboard, Alumno, Tutor, Inscripcion, AsignaGrupo, Evaluacion, Nivel, Unidad,
Rubro, Periodo, PeriodoActivo y UsuariosPendientes. Los CRUD de Grupo/Comunidad
siguen usando sus comprobaciones previas. Auth continúa con su autorización
de las fases 1–2; no requiere autorización académica.

Las relaciones de lectura se precargan para evitar N+1 en vistas. Las subconsultas
correlacionadas de unicidad y las comprobaciones por destino de captura pueden ser
costosas en lotes grandes; medir con datos reales e índices de grupo/periodo,
catequista y claves de evaluación antes de optimizar. No se agrega caché.

La transacción y los locks de las filas consultadas no garantizan por sí solos
ausencia de carreras con escritores administrativos que aún no usan el núcleo.
Quedan pendientes la integridad/índices del esquema real, los escritores restantes
y la política definitiva de duplicados y datos históricos. No se ha inspeccionado
ni modificado la base local de producción.