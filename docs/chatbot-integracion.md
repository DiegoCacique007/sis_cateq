# Chatbot funcional end-to-end — fase 7

## Arquitectura

Usuario → componente Blade/JS → ChatbotController → ChatbotService → IntentResolver
→ ChatbotFacts/RuleEngine → CatequesisAccess → AgentRegistry → agente → Accessible*
→ conexión configurada (MySQL/MariaDB) → AgentResponse → formatter → JSON → UI.

ChatbotService es un orquestador sin SQL, consultas Eloquent, permisos propios ni
procedimientos. Recibe Request del servidor para reutilizar AccessContextResolver;
este toma identidad autenticada, recarga la cuenta y verifica periodo de sesión en
cada mensaje. Solo el controlador le pasa los campos validados. No se reutilizan
contextos enviados por el navegador ni decisiones anteriores.

IntentResolver se enlaza a RuleBasedIntentResolver en AppServiceProvider. Si la
intención es ambigua, se devuelve AMBIGUOUS_INTENT y se pide una consulta única;
no se elige una candidata. Para una intención inequívoca se usa la factoría privada
AgentRequest::evaluate, que ejecuta RuleEngine. El agente mantiene su revalidación.

## Endpoint y request

`POST /chatbot/message`, nombre `chatbot.message`, middleware web (sesión y CSRF),
auth, approved, throttle:chatbot y NoCacheHeaders. No usa periodo.activo global:
el motor decide cuándo es necesario el periodo; HELP_SYSTEM y orientación de
catálogos pueden funcionar sin él. Los layouts operativos conservan su middleware
previo de periodo; abrir esas páginas puede seleccionar periodo como antes.

SendChatbotMessageRequest valida:

- message: requerido, string, entre 2 y 2000 caracteres; TrimStrings elimina espacios exteriores.
- assignment_id, inscription_id, student_id, evaluation_id: opcionales/null,
  enteros positivos representables por PHP.
- role, user_id/userId, status, community_id/communityId/comunidad_id,
  periodo_activo_id e intent: prohibidos cuando contienen un valor.

Otros campos no incluidos en validated() se ignoran; ningún parámetro adicional
construye autoridad. Los IDs solo seleccionan recursos: el dominio decide su alcance.
No se valida existencia global para revelar si un ID ajeno existe.

```json
{"message":"Muéstrame mis alumnos.","assignment_id":15}
```

El controlador únicamente adapta los campos validados, llama al servicio y al
formatter, y devuelve JSON. No se añadieron rutas de escritura académica.

## Contrato de respuesta

```json
{
  "status":"selection_required",
  "code":"ASSIGNMENT_SELECTION_REQUIRED",
  "message":"Selecciona una asignación para continuar.",
  "data":{"items":[],"options":[{"id":15,"label":"Grupo — Nivel — Comunidad — Periodo"}]},
  "actions":[],
  "meta":{"hasMore":false}
}
```

Estados: success, denied, unsupported, selection_required, missing_context,
ambiguous y error. ESCALATE se presenta como denied con recomendación, sin permiso.
ChatbotResponseFormatter no consulta datos ni modifica decisiones: traduce los
messageKey y las capacidades ya filtradas a texto español, forma listas y convierte
rutas existentes a URLs relativas de navegación. No reinterpreta lenguaje natural.

data.items contiene texto: grupos/niveles/comunidades/periodos, nombres de alumnos,
evaluaciones o pasos. Se eliminan IDs de alumnos y grupos innecesarios para esta
interfaz. Solo options expone IDs necesarios para elegir asignación. Los enlaces
de asistencia pueden llevar el ID ya autorizado como parámetro del módulo existente.
No se exponen modelos, relaciones completas, metadatos técnicos, nombres de agentes,
reglas internas, contraseñas ni tokens. Los códigos funcionales son estables.

Una decisión funcional (incluidos denied/unsupported/selección) responde HTTP 200.
Errores de transporte/autenticación: 401 invitado, 403 cuenta no autorizada,
419 CSRF/sesión, 422 validación, 429 límite y 500 error inesperado. Todos tienen la
misma estructura JSON y Cache-Control no-store, private para este endpoint.

## Selección y continuidad

La UI conserva el mensaje original en el cierre de cada opción. Pulsar una opción
reenvía ese mensaje y assignment_id; el servidor resuelve de nuevo identidad,
intención, reglas y recurso. Cambiar el ID manualmente no evita autorización.
No se acepta una intención suministrada por el cliente ni un token de ALLOWED.

El motor mantiene MISSING_RESOURCE y los agentes lo traducen a selección requerida,
sin consultar alumnos antes de elegir. No se selecciona automáticamente aunque
haya una sola asignación. Evaluaciones también pide asignación si no tiene selector.
AMBIGUOUS_ASSIGNMENT sigue significando datos inconsistentes, no falta de elección.
Las intenciones ambiguas piden reescribir una consulta única; no hay diálogo inferido.

No hay tabla de conversaciones, localStorage, cookies de conversación ni memoria
permanente. Cerrar/reabrir conserva lo visible en la página; recargar lo descarta.
El DOM retiene como máximo 60 burbujas. Se mantienen los límites de 100 elementos
de los agentes y se muestra un aviso cuando hasMore es true; el resto se consulta
en el módulo. La UI no ofrece selección de inscripción/alumno/evaluación todavía,
aunque el endpoint admite esos selectores para consumidores validados futuros.

## Interfaz y assets

Un único components/chatbot.blade.php se incluye en los cinco layouts operativos,
solo para cuentas aprobadas con rol válido. Usa colores azules y esquinas redondeadas
del sistema, sin alterar sidebar ni navegación existentes. Tiene botón flotante,
panel abrir/cerrar, Escape, etiquetas, aria-live, área desplazable, input, enviar,
estado procesando, opciones y enlaces. CSS propio adapta el tamaño al viewport.

chatbot.js es una entrada Vite independiente con chatbot.css, sin dependencias
externas ni necesidad de Bootstrap/Alpine para funcionar. Envía fetch con cookies
same-origin, Accept JSON y X-CSRF-TOKEN del componente. Bloquea doble envío y botones
de selección durante la petición, tiene timeout de 30 segundos, muestra fallo de
red y errores HTTP estructurados, y mantiene scroll y foco.

No usa innerHTML ni Markdown: mensajes, nombres, opciones y pasos se asignan con
textContent. Los enlaces solo admiten http/https del mismo origen. El frontend no
contiene matrices de permisos ni reglas de negocio. Los endpoints destino siguen
aplicando su autorización; una acción navigate no constituye un permiso permanente.

El build inicial encontró dos problemas previos que impedían/afectaban compilar:
app.js importaba un default inexistente en Anime.js 4 instalado, corregido a import
de namespace (no había otros consumidores de anime en resources); app.css colocaba
@import de Bootstrap después de Tailwind, movido al inicio. No se instalaron ni
actualizaron paquetes. Vite compila app y chatbot. public/build está ignorado por
Git; debe generarse durante despliegue. No se eliminó public/hot del entorno del
usuario: si usa Vite dev debe estar activo; en despliegue se usan los assets compilados.

El chatbot no necesita Internet. Los layouts previos aún usan fuentes/Bootstrap
desde CDN; esos recursos preexistentes no se han migrado en esta fase.

## Seguridad, límite y logging

El límite nativo Laravel es de 30 mensajes/minuto por identidad autenticada,
compartido entre pestañas del mismo usuario. Cuenta peticiones validadas y fallidas
que llegan al middleware. Usa el cache configurado por la aplicación; en varias
instancias debe ser un cache compartido. Sesión y rate limit pueden escribir
almacenamiento técnico normal; el chatbot no escribe datos académicos ni conversaciones.

Contexto fresco detecta cambios de rol/comunidad/estado entre solicitudes. AgentGate
y Accessible* conservan la defensa por recurso. No hay periodos ni comunidades
inferidos desde texto. La UI no envía esos campos. CSRF no se desactiva ni se exime.

Ante excepciones inesperadas, ChatbotService devuelve CHATBOT_ERROR y registra
solo código y clase de excepción. El renderizado acotado a chatbot/message en
bootstrap/app.php convierte también errores HTTP y previos al controlador a JSON
seguro, aun con APP_DEBUG=true. Se suprime el reporte automático para esa ruta y
se sustituye por logging sanitizado en errores 500; no se registran mensajes,
respuestas, SQL, bindings, excepción completa, stack trace, contraseñas o tokens.
El resto de la aplicación conserva su tratamiento previo de excepciones.

## Entorno local y validación

Se ejecutó `php artisan about --only=environment` y se arrancó Laravel en consola
para leer driver/nombre y llamar exclusivamente a DB::connection()->getPdo():

- PHP 8.2.12; Laravel 12.53.0; entorno local.
- Driver: mysql.
- Base: parroquia.
- Conexión: exitosa.
- Sin escrituras de prueba ni lectura de registros personales; .env sin cambios.

ChatbotEndpointTest usa SQLite en memoria y el soporte mínimo existente: auth,
cuentas, cinco roles, validación, datos reales de pruebas, IDs externos, comunidad,
contexto fresco, selección posterior, UNKNOWN, ambigüedad, CSRF real habilitado en
una prueba, límite por usuario, errores sanitizados y servicio/orientación.

`node --test tests/js/chatbot.test.mjs` usa node:test sin instalar paquetes. Comprueba
eventos con DOM mínimo: abrir/cerrar/reabrir, mensaje original+selección+CSRF, doble
envío, recuperación de red, texto malicioso y enlaces externos. No es una prueba de
renderizado visual ni sustituye un navegador real. Se ejecutan además las regresiones
de fases 6B, 6A, 5, 4B, 4, 3, 2 y 1, sin afirmar ejecución de la suite completa.

No se realizó prueba manual autenticada como catequista/Secretaría: el navegador
disponible no tenía sesiones ni se proporcionaron credenciales. No se crearon cuentas
ni sesiones mediante escrituras en MySQL para conseguirla. La revisión visual real
en escritorio/móvil y ambos roles queda pendiente para un entorno autenticado.

## Límites pendientes

Gramática acotada, paginación conversacional y selección de otros recursos siguen
pendientes. La ayuda de boletas remite al módulo y su flujo específico aún exige
los selectores de fase 5. No se implementa captura de asistencia, evaluaciones,
altas/bajas/modificaciones, IA, RAG ni memoria persistente. La concurrencia de
escritores administrativos y el rendimiento MySQL siguen siendo riesgos heredados;
las comprobaciones son por petición, no una transacción de snapshot completa.
