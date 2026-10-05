# Motor determinista del chatbot — fase 5

## Objetivo y alcance

Transformar contexto confiable + intención estructurada + hechos + autorización
existente en una conclusión explicable. No interpreta lenguaje natural, recupera
listados, ejecuta cambios ni envía mensajes. No necesita dependencias nuevas,
tablas, rutas, controladores, proveedores registrados ni configuración adicional:
el contenedor de Laravel resuelve sus dos dependencias concretas.

## Arquitectura y conceptos

| Componente | Responsabilidad |
|---|---|
| UserRole | Identidad operativa, enum canónico existente |
| CatequesisCapability | Vocabulario de capacidades del dominio |
| AccessContextResolver | Obtiene identidad real, estado, comunidad y periodo del servidor |
| AccessContext | Instantánea inmutable por operación; nunca procede del mensaje |
| CatequesisAccess y Accessible* | Única autoridad sobre capacidades, recursos y ambigüedad |
| ChatbotIntent | Solicitud ya estructurada; no es ruta HTTP ni permiso |
| ChatbotFacts | Hechos de entrada tipados, sin modelos ni permisos declarados por el cliente |
| RuleCatalog | Mapeo explícito intención/capacidad, contrato de selección e IDs explicativos |
| RuleEngine | Aplica condiciones, delega autorización y traduce decisiones |
| RuleResult | Conclusión tipada, código estable, reglas aplicadas y recomendación |

Un **hecho** es información conocida: contexto resuelto, intención o ID solicitado.
Un ID solicitado no implica que el recurso exista ni esté autorizado. Una **regla**
es una condición determinista con una consecuencia y un identificador estable.
Una **intención** expresa qué se solicita (lectura, orientación o modificación).
Una **conclusión** indica el resultado de evaluar esas condiciones; no ejecuta la
acción. Los nombres PHP usan PascalCase como los enums existentes; sus valores
string conservan exactamente los nombres UPPER_SNAKE solicitados.

ChatbotFacts contiene exclusivamente:

```php
AccessContext $context;
ChatbotIntent $intent;
?int $assignmentId = null;
?int $inscriptionId = null;
?int $studentId = null;
?int $evaluationId = null;
```

No hay constructor desde array/request ni parámetros de autoridad separados.
El consumidor futuro debe resolver AccessContext en cada operación mediante
AccessContextResolver. No construirlo deserializando JSON, desde un LLM, desde el
mensaje o desde una conversación persistida. El motor verifica estado aprobado e
identidad positiva; el tipo UserRole excluye roles desconocidos. No vuelve a
autenticar ni recarga el usuario: esa frontera corresponde al resolver existente.
Un contexto antiguo no detecta por sí solo una revocación posterior.

RuleResult es readonly: `result`, `code`, `matchedRules`, `capability` nullable,
`context` (solo nombres de campos faltantes) y `recommendedResponsible` nullable
(`secretaria` o `system_human`). No contiene registros, IDs ni datos personales.
Los IDs de reglas son trazas internas; una futura presentación traducirá códigos.

## Matriz de intenciones

| Valor ChatbotIntent | Capability |
|---|---|
| HELP_SYSTEM | Ninguna, ayuda general para usuario operativo |
| VIEW_MY_GROUPS | VIEW_GROUPS |
| VIEW_GROUP_STUDENTS | VIEW_GROUP_STUDENTS |
| VIEW_ATTENDANCE_LIST | VIEW_ATTENDANCE_LIST |
| VIEW_EVALUATIONS | VIEW_EVALUATIONS |
| VIEW_BOLETA | VIEW_BOLETAS |
| HOW_TO_RECORD_EVALUATIONS | MANAGE_EVALUATIONS |
| HOW_TO_REGISTER_STUDENT | MANAGE_STUDENTS |
| HOW_TO_REGISTER_TUTOR | MANAGE_TUTORS |
| HOW_TO_REGISTER_INSCRIPTION | MANAGE_INSCRIPTIONS |
| HOW_TO_ASSIGN_GROUP | MANAGE_ASSIGNMENTS |
| HOW_TO_MANAGE_COMMUNITIES | MANAGE_COMMUNITIES |
| HOW_TO_MANAGE_PERIODS | MANAGE_PERIODS |
| HOW_TO_MANAGE_LEVELS | MANAGE_LEVELS |
| HOW_TO_MANAGE_UNITS | MANAGE_UNITS |
| HOW_TO_MANAGE_RUBRICS | MANAGE_RUBRICS |
| HOW_TO_MANAGE_USERS | MANAGE_USERS |
| MODIFY_STUDENT | MANAGE_STUDENTS; aun poseyéndola, ejecución no soportada |
| MODIFY_ADMINISTRATIVE_DATA | Ninguna: solicitud genérica sin capacidad concreta; canalizar sin autorizar |
| UNKNOWN | Ninguna; no soportada |

`VIEW_MY_GROUPS` conserva el alcance que ViewGroups tiene para cada rol: propias
para catequista, comunidad para coordinador comunitario, periodo para supervisión
y Secretaría. No se inventa propiedad personal para los otros roles.

## Selección de recursos

| Intención | Selectores aceptados | Obligatorios |
|---|---|---|
| VIEW_MY_GROUPS | assignmentId opcional | Ninguno |
| VIEW_GROUP_STUDENTS, VIEW_ATTENDANCE_LIST | assignmentId | assignmentId |
| VIEW_EVALUATIONS, HOW_TO_RECORD_EVALUATIONS | Un único assignmentId, inscriptionId, studentId o evaluationId | Ninguno |
| VIEW_BOLETA | assignmentId + inscriptionId | Ambos |
| HELP_SYSTEM y orientación administrativa | Ninguno | Ninguno |

IDs cero/negativos producen DENIED/INVALID_RESOURCE_ID. Selectores no admitidos o
combinaciones no soportadas producen UNSUPPORTED/RESOURCE_SELECTION_UNSUPPORTED;
no se ignoran silenciosamente. Falta de selección produce
MISSING_CONTEXT/MISSING_RESOURCE con los nombres de los campos requeridos.
No se consulta la primera asignación disponible ni se adivina una selección.

Para un ID se llama a canViewAsignacion (listado de grupos), canUseAsignacion
(uso académico y unicidad), canViewInscripcion, canViewAlumno, canViewEvaluacion
o canManageEvaluacion según corresponda. La boleta requiere canViewBoleta y
canUseInscripcionAsignacion, comprobando que ambos recursos estén relacionados.
El motor no contiene SQL ni consultas Eloquent propias.

Una lectura sin ID autoriza únicamente solicitar la colección restringida, no
acceder a cualquier registro. Un filtro por alumno autorizado no garantiza que
existan evaluaciones consultables. Un consumidor posterior deberá usar
AccessibleEvaluaciones/Inscripciones/Asignaciones/Alumnos con el mismo contexto
y filtros adicionales AND; nunca consultar tablas globales a partir de ALLOWED.
Las proyecciones, campos mostrables y respuestas de datos quedan fuera de esta fase.

## Resultados y códigos

| Resultado | Interpretación y códigos |
|---|---|
| ALLOWED | Solicitud admitida; `<INTENT>_ALLOWED` |
| DENIED | UNAUTHORIZED_CONTEXT, ROLE_NOT_ALLOWED, OUTSIDE_SCOPE o INVALID_RESOURCE_ID |
| MISSING_CONTEXT | MISSING_PERIOD, MISSING_COMMUNITY o MISSING_RESOURCE |
| AMBIGUOUS | AMBIGUOUS_ASSIGNMENT, sin elegir una candidata |
| UNSUPPORTED | UNKNOWN_INTENT, EXECUTION_UNSUPPORTED o RESOURCE_SELECTION_UNSUPPORTED |
| ESCALATE | ADMINISTRATIVE_EXECUTION_UNSUPPORTED; recomendación, nunca concesión |

Los códigos provenientes de CatequesisAccess se conservan. Recursos ajenos e
inexistentes son indistinguibles (OUTSIDE_SCOPE), sin IDs en la conclusión.
Evaluaciones/alumnos que el dominio excluye por ambigüedad pueden producir
OUTSIDE_SCOPE; el motor no realiza consultas adicionales para revelar su causa.

## Catálogo de reglas

| ID | Condición / consecuencia |
|---|---|
| AUTH-001 | Contexto tipado de usuario positivo y aprobado: continuar |
| AUTH-002 | Identidad/estado inválido: DENIED antes de cualquier función |
| CTX-001 | La autoridad exige periodo y falta: MISSING_CONTEXT |
| CTX-002 | La autoridad exige comunidad y falta: MISSING_CONTEXT |
| CTX-003 | Faltan selectores obligatorios: MISSING_CONTEXT |
| CTX-004 | Selectores o combinación no soportados: UNSUPPORTED |
| INT-001 | UNKNOWN: UNSUPPORTED |
| INT-002 | Capacidad rechazada por la autoridad: DENIED |
| CAP-001 | CatequesisAccess permitió la capacidad en ese contexto |
| RES-001 | Todos los selectores proporcionados fueron autorizados |
| RES-002 | Recurso rechazado por el dominio: DENIED |
| RES-003 | ID no positivo: DENIED |
| RES-004 | Inscripción y asignación de boleta compatibles |
| AMB-001 | Autoridad devolvió ambigüedad: AMBIGUOUS |
| HELP-001 | Ayuda general permitida, sin capacidad nueva |
| EXEC-001 | Ejecución conversacional no implementada |
| ESC-001 | Recomendación estructurada a Secretaría |
| ESC-002 | Problema desconocido: recomendación a responsable humano del sistema |
| CAT-001 | Solicitud del catequista previamente autorizada por el núcleo |
| CAT-002 | Consulta de sus grupos permitida |
| CAT-003 | Consulta de alumnos de asignación propia autorizada |
| CAT-004 | Recurso del catequista fuera de alcance |
| CAT-005 | Lista de asistencia de asignación autorizada |
| CAT-006 | Orientación para evaluaciones permitida |
| CAT-007 | Solicitud administrativa del catequista denegada; recomendar Secretaría |
| CAT-008 | Consulta de evaluaciones permitida |
| SEC-001 | Orientación para registrar alumno |
| SEC-002 | Orientación para registrar inscripción |
| SEC-003 | Orientación para asignar grupo |
| SEC-004 | Orientación para gestionar usuarios |
| SEC-005 | Orientación para registrar tutor |
| SEC-006 | Orientación para gestionar comunidades |
| SEC-007 | Orientación para gestionar periodos |
| SEC-008 | Orientación para gestionar niveles |
| SEC-009 | Orientación para gestionar unidades |
| SEC-010 | Orientación para gestionar rubros |
| SEC-011 | Orientación para registrar evaluaciones |
| SEC-012 | Lectura de Secretaría autorizada |
| PAR-001 | Lectura del párroco autorizada |
| PAR-002 | Solicitud administrativa del párroco denegada |
| CG-001 | Lectura del coordinador general autorizada |
| CG-002 | Solicitud administrativa del coordinador general denegada |
| CC-001 | Lectura comunitaria autorizada por contexto real |
| CC-002 | Recurso fuera del alcance comunitario |
| CC-003 | Solicitud administrativa del coordinador comunitario denegada |

Las etiquetas de rol se agregan después de autorizar. No son una segunda matriz de
permisos. CTX-001/002 aparecen cuando falta contexto; CAP-001 indica que la capacidad
y sus requisitos de contexto se cumplieron. No se etiquetan reglas que no ocurrieron.

## Reglas por rol

| Rol | Lecturas y orientación | Solicitud administrativa |
|---|---|---|
| Catequista | Grupos propios, sus alumnos/listas/evaluaciones; HOW_TO evaluaciones | DENIED y recomendar Secretaría |
| Secretaría | Lecturas del dominio y orientación de las once capacidades mapeadas | MODIFY_STUDENT no soportado; modificación genérica canalizada |
| Párroco | Grupos, alumnos, evaluaciones y boletas dentro del alcance existente | DENIED y recomendar Secretaría |
| Coordinador general | Lecturas existentes, sin CRUD | DENIED y recomendar Secretaría |
| Coordinador de comunidades | Lecturas limitadas por communityId y periodo del contexto | DENIED y recomendar Secretaría |

Todos pueden solicitar ayuda general con contexto operativo válido. UNKNOWN siempre
es UNSUPPORTED después de validar identidad/estado. La asistencia conserva la
capacidad exclusiva del catequista; Secretaría/supervisores no la heredan.
La modificación administrativa genérica se canaliza para todos los roles porque
no representa una acción concreta autorizable en esta versión.

## Prioridad de inferencia

1. Identidad positiva, estado aprobado y rol tipado. Seguridad antes de HELP/UNKNOWN.
2. UNKNOWN y modificación administrativa genérica obtienen una conclusión cerrada,
   sin consultar recursos ni conceder permisos.
3. Resolver capability y consultar CatequesisAccess. Se conserva su prioridad:
   rol sin capacidad se deniega antes de diagnosticar comunidad/periodo faltante.
   No se duplica su lista de capacidades que requieren periodo.
4. Una modificación de alumno jamás se ejecuta, aunque su capacidad sea permitida.
5. Validar IDs, contrato de selección y recursos requeridos.
6. Autorizar recursos con el dominio. Si hay varios selectores (boleta), una
   denegación prevalece sobre una ambigüedad; luego comprobar su relación.
7. Emitir conclusión y trazas funcionales por rol únicamente después de permitir.

Esta variación de contexto/capacidad respecto al orden conceptual evita alterar la
autoridad de dominio. No se concede una función para compensar un rechazo previo.
AMBIGUOUS refleja incompatibilidad de asignaciones; MISSING_RESOURCE indica que
el solicitante aún no eligió una. Son problemas distintos.

## Ejemplos de inferencia académica

### Lista de asistencia propia

- H1: usuario catequista aprobado, contexto del servidor con periodo 1.
- R1: AUTH-001 permite continuar; CatequesisAccess confirma VIEW_ATTENDANCE_LIST
  y requisitos de contexto (CAP-001).
- H2: assignmentId 12, cuyo uso resulta autorizado por canUseAsignacion.
- R2: RES-001, CAT-001 y CAT-005 concluyen permiso de consulta.
- C1: ALLOWED / VIEW_ATTENDANCE_LIST_ALLOWED, sin datos ni escrituras.

### Modificación administrativa de alumno

- H1: catequista aprobado, intención MODIFY_STUDENT.
- R1: AUTH-001; el catálogo requiere MANAGE_STUDENTS.
- H2: CatequesisAccess devuelve ROLE_NOT_ALLOWED.
- R2: INT-002, CAT-007 y ESC-001.
- C1: DENIED / ROLE_NOT_ALLOWED; recommendedResponsible = secretaria.

### Comunidad ausente

- H1: coordinador comunitario aprobado, VIEW_EVALUATIONS.
- R1: AUTH-001 y consulta de capacidad al núcleo.
- H2: AccessContext.communityId es null; un ID del mensaje no lo reemplaza.
- R2: CTX-002 traduce MISSING_COMMUNITY.
- C1: MISSING_CONTEXT; no se consulta un recurso fuera de contexto.

### Asignación ambigua

- H1: solicitud de alumnos de una asignación accesible.
- R1: AUTH-001 y CAP-001 permiten continuar.
- H2: el dominio encuentra varias asignaciones válidas para grupo/periodo.
- R2: AMB-001 traduce AMBIGUOUS_ASSIGNMENT.
- C1: AMBIGUOUS; no se elige por primer registro, comunidad inferida o texto.

## Orientación, denegación y escalamiento

HOW_TO_RECORD_EVALUATIONS responde a una solicitud de orientación; ni siquiera
con evaluationId autoriza guardar una nota. No se introdujo una intención para
capturar evaluaciones. HOW_TO no escribe ni produce instrucciones ejecutables.
En esta fase ALLOWED solo permite que un futuro componente prepare orientación.

DENIED con recomendación conserva la denegación. ESCALATE tampoco es permiso ni
delegación automática. No se envían mensajes ni se crea un agente de escalamiento.
Secretaría recibe la recomendación de asuntos administrativos; UNKNOWN recomienda
un responsable humano del sistema. Los destinatarios reales se definirán después.

## Pruebas y límites

`tests/Unit/Chatbot/RuleEngineTest.php` usa PHPUnit sin Laravel ni base de datos para
reglas generales, capacidades, roles, contrato, prioridad e inmutabilidad. Usa el
CatequesisAccess real, sin simular permisos.
`tests/Feature/Chatbot/RuleEngineScopeTest.php` ejercita motor, resolver y autoridad
reales sobre CatequesisTestCase: SQLite en memoria con esquema mínimo existente.
Verifica propiedad, periodo, comunidad, ambigüedad, relaciones de boleta, IDs ajenos,
soft deletes, nivel incompatible, entrada manipulada y SQL de solo lectura en HOW_TO.
No cambia migraciones, soporte existente ni base local. No verifica MySQL real,
su rendimiento, migraciones académicas incompletas ni la suite completa.

Pendientes: consumidor de datos con proyección segura; resolución futura de lenguaje
natural; estrategia de selecciones múltiples; catálogo de textos de orientación;
política de corrección de duplicados; integración de escritores administrativos
pendientes descritos en autorizacion-catequistica.md. El resultado es una instantánea,
no un token reutilizable de autorización ni garantía frente a cambios concurrentes.

No disponibles en esta fase: persistencia digital de asistencia (no se declara
RECORD_ATTENDANCE), ejecución administrativa por chat, agentes, interfaz, endpoint,
IntentResolver de lenguaje natural, IA y conversaciones. El motor es el único
componente nuevo de comportamiento y no está expuesto a la web.
