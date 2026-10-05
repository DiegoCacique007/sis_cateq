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

No se modifican controladores, rutas, middleware, modelos ni migraciones.
Esta capa **no corrige por sí sola los endpoints existentes**: su adopción será
otra fase. No se crean Policies, chatbot, agentes, DTO ni serializadores.

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

1. Integrar gradualmente esta capa en endpoints y Policies. Hoy persisten consultas
   comunitarias sin límite por users.comunidad_id y boletas por ID sin alcance;
   no afirmar que toda la aplicación ya aplica este núcleo.
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
