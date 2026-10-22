# Despliegue de SIS CATEQ en un servidor real

Lista ordenada para instalar o actualizar SIS CATEQ en producción. Sigue los pasos en orden.
Este documento nunca debe contener contraseñas, claves ni tokens: esos valores solo viven en
el `.env` del servidor.

## 1. Requisitos

- PHP 8.2 o superior con las extensiones que pide Laravel 12: `pdo_mysql`, `mbstring`,
  `openssl`, `tokenizer`, `xml`, `ctype`, `fileinfo`, `bcmath` y `curl` (`intl` recomendada).
- MySQL o MariaDB.
- Composer.
- Node.js y npm, solo para compilar los recursos visuales (puede hacerse en otro equipo).
- Un certificado HTTPS para el dominio.
- Una cuenta de correo saliente (SMTP) para los enlaces de recuperación de contraseña.

## 2. Preparar el código

1. Copia el código del repositorio a la carpeta del servidor.
2. Instala las dependencias de PHP sin las de desarrollo:

   ```
   composer install --no-dev --optimize-autoloader
   ```

3. Compila los recursos visuales (en el servidor o en otro equipo; en ese caso sube
   `public/build`):

   ```
   npm ci
   npm run build
   ```

4. **Borra `public/hot` si existe.** Ese archivo lo crea `npm run dev`; si queda en el
   servidor, la aplicación intenta cargar CSS y JS desde el servidor de desarrollo de Vite y
   las páginas se ven sin estilos.

## 3. Servidor web

- La raíz del sitio debe apuntar a la carpeta `public/`, nunca a la carpeta del proyecto, para
  que `.env` y el código no puedan descargarse.
- Apache o XAMPP: un `VirtualHost` con `DocumentRoot ".../sis_cateq/public"` y
  `AllowOverride All` en ese directorio (se usa `public/.htaccess`).
- Redirige todo el tráfico HTTP a HTTPS.
- **Proxies.** Si delante del servidor hay un proxy inverso o un balanceador, define
  `TRUSTED_PROXIES` en `.env` con su IP (o lista separada por comas). Sin eso, todos los
  visitantes comparten la IP del proxy y los límites por IP (inicio de sesión, registro,
  recuperación de contraseña) bloquearían a toda la parroquia a la vez. Nunca uses `*` si el
  servidor también es accesible directamente desde Internet. Si Apache atiende HTTPS en el
  mismo servidor, no hay proxy: deja `TRUSTED_PROXIES` sin definir.

## 4. Archivo `.env`

1. Copia `.env.example` a `.env`.
2. Ajusta los valores (los secretos solo en el servidor):

   | Variable | Valor en producción | Motivo |
   |---|---|---|
   | `APP_NAME` | `"SIS CATEQ"` | Nombre de la cookie de sesión, del prefijo de caché y del remitente |
   | `APP_ENV` | `production` | Comportamiento de producción |
   | `APP_DEBUG` | `false` | Con `true`, un error muestra código y datos internos a cualquiera |
   | `APP_URL` | `https://dominio-real` | Enlaces de los correos; con `https://`, la cookie es segura por defecto |
   | `APP_LOCALE` / `APP_FALLBACK_LOCALE` | `es` / `en` | Mensajes en español |
   | `LOG_STACK` / `LOG_LEVEL` | `daily` / `warning` | Archivos por día (14 días) y sin mensajes de depuración |
   | `DB_CONNECTION` y `DB_*` | `mysql` y un usuario propio (no `root`) | Permisos solo sobre la base de SIS CATEQ |
   | `SESSION_DRIVER` | `database` | Tabla `sessions` |
   | `SESSION_LIFETIME` | `120` | Minutos de inactividad |
   | `SESSION_ENCRYPT` | `true` | Protege el contenido de las sesiones guardadas en la base |
   | `SESSION_SECURE_COOKIE` | sin definir (o `true`) | Con `APP_URL` en `https://` ya es segura por defecto |
   | `TRUSTED_PROXIES` | IP del proxy, o sin definir | Ver el paso 3 |
   | `CACHE_STORE` | `database` (o `file`) | Los límites de intentos se guardan en la caché; nunca `array` |
   | `QUEUE_CONNECTION` | `database` | No hay trabajos en cola; no hace falta `queue:work` |
   | `MAIL_MAILER` y `MAIL_*` | `smtp` y los datos del proveedor, con TLS | Con `log`, los enlaces de recuperación no llegan a nadie |
   | `MAIL_FROM_ADDRESS` / `MAIL_FROM_NAME` | Dirección real del dominio / `"SIS CATEQ"` | Evita que los correos acaben en spam |
   | `CHATBOT_CLASSIFIER_MODE` | `off` (o sin definir) | El chatbot funciona solo con reglas; ver el paso 14 |

3. Genera la clave de la aplicación **una sola vez**:

   ```
   php artisan key:generate
   ```

   Guarda una copia de `APP_KEY` en un lugar seguro. Si cambia, se cierran todas las sesiones
   y no se puede descifrar lo cifrado con la clave anterior.

## 5. Base de datos

Las migraciones crean el esquema completo, incluidas las tablas académicas (migraciones
`2026_01_01_*`). Cada migración de creación comprueba si su tabla ya existe: en una base que
ya la tiene no hace nada y solo queda registrada. Su `down()` está vacío a propósito, para que
un `migrate:rollback` nunca borre tablas con datos.

### Instalación nueva

1. Crea la base y el usuario de MySQL con permisos solo sobre ella.
2. Ejecuta las migraciones:

   ```
   php artisan migrate --force
   ```

3. Comprueba que todas aparecen como `Ran`:

   ```
   php artisan migrate:status
   ```

### Instalación existente (anterior a las migraciones de creación)

En una base creada antes de existir las migraciones `2026_01_01_*`, esas 11 aparecen como
pendientes. Al ejecutarlas solo se añaden 11 filas a la tabla `migrations`: no se crean,
alteran ni borran tablas, columnas ni datos. Aun así, ensáyalo primero en una copia.

No uses `php artisan migrate --pretend` para comprobarlo: en ese modo las consultas de lectura
no se ejecutan y la salida mostraría `CREATE TABLE` que en realidad nunca se ejecutarían.

1. Copia de seguridad completa (estructura y datos), según el paso 10.
2. Guarda la estructura actual, sin datos, para comparar:

   ```
   mysqldump --defaults-extra-file=/ruta/segura/respaldo.cnf --no-data --skip-dump-date nombre_base > estructura_antes.sql
   ```

3. **Ensayo en una copia:**
   - Crea una base `nombre_base_copia` y restaura en ella la copia de seguridad del paso 1.
   - Pon temporalmente `DB_DATABASE=nombre_base_copia` en `.env` y ejecuta `php artisan config:clear`.
   - Ejecuta `php artisan migrate:status`, `php artisan migrate --force` y otra vez
     `php artisan migrate:status`: las 11 `2026_01_01_*` deben pasar a `Ran`.
   - Vuelca la estructura de la copia con `--no-data --skip-dump-date` y compárala con
     `estructura_antes.sql`: no debe haber diferencias.
   - Devuelve `DB_DATABASE` a su valor y ejecuta `php artisan config:clear`.
4. **En la base real:** haz otra copia de seguridad justo antes y ejecuta
   `php artisan migrate:status`, `php artisan migrate --force` y `php artisan migrate:status`.
5. Vuelca otra vez la estructura con `--no-data --skip-dump-date` y compárala con
   `estructura_antes.sql`: no debe haber diferencias.

Revisa siempre `php artisan migrate:status` antes de migrar.

## 6. Cuenta inicial de Secretaría (solo la primera vez)

1. Añade a `.env` la variable `SEED_SECRETARIA_PASSWORD` con una contraseña de al menos 12
   caracteres.
2. Limpia la caché de configuración (el seeder lee la variable directamente y, con la
   configuración en caché, no la encontraría):

   ```
   php artisan config:clear
   ```

3. Ejecuta el seeder:

   ```
   php artisan db:seed --class=UsuariosParroquiaSeeder
   ```

   Crea la cuenta `secretaria@parroquia.org.mx` solo si no existe; nunca modifica una
   cuenta existente.
4. **Borra `SEED_SECRETARIA_PASSWORD` de `.env`.**
5. Inicia sesión con esa cuenta y cambia la contraseña desde «Cambiar contraseña».

## 7. Cachés de producción

Ejecútalo después de cada cambio de código o de `.env`:

```
php artisan optimize
```

Equivale a `config:cache`, `route:cache`, `view:cache` y `event:cache`. Para deshacerlo:
`php artisan optimize:clear`.

## 8. Permisos

- `storage/` y `bootstrap/cache/` deben poder escribirse por el usuario del servidor web; el
  resto del proyecto, solo lectura.
- Linux:

  ```
  chown -R www-data:www-data storage bootstrap/cache
  chmod -R 775 storage bootstrap/cache
  ```

- Windows: da permiso de «Modificar» sobre esas dos carpetas al usuario con el que se ejecuta
  el servicio de Apache.

## 9. Tarea programada

La limpieza diaria de la bitácora del chatbot (`chatbot-events-purge`) necesita que
`schedule:run` se ejecute cada minuto.

- Linux (cron del usuario del servidor web):

  ```
  * * * * * cd /ruta/sis_cateq && php artisan schedule:run >> /dev/null 2>&1
  ```

- Windows (Programador de tareas):

  ```
  schtasks /Create /SC MINUTE /MO 1 /TN "SIS CATEQ schedule" /TR "C:\xampp\php\php.exe C:\ruta\sis_cateq\artisan schedule:run" /RU SYSTEM
  ```

  En las propiedades de la tarea, en «Iniciar en», indica la carpeta del proyecto.

Comprueba que aparece la tarea:

```
php artisan schedule:list
```

## 10. Copias de seguridad

- Copia diaria de la base:

  ```
  mysqldump --defaults-extra-file=/ruta/segura/respaldo.cnf --single-transaction --routines --triggers --default-character-set=utf8mb4 nombre_base > respaldo_AAAA-MM-DD.sql
  ```

  En Windows, `mysqldump.exe` está en `C:\xampp\mysql\bin`; prográmalo con el Programador de
  tareas.
- Las credenciales van en el archivo de opciones (`--defaults-extra-file`), con permisos
  restringidos, nunca en la línea de comandos.
- Guarda las copias fuera del servidor y conserva al menos 30 días.
- **Prueba una restauración** en otro equipo de vez en cuando.
- Guarda por separado, en un lugar seguro, el `.env` del servidor (como mínimo `APP_KEY`).

## 11. Actualizar una instalación existente

1. `php artisan down`
2. Haz una copia de seguridad de la base (paso 10).
3. Actualiza el código.
4. `composer install --no-dev --optimize-autoloader`
5. `npm ci` y `npm run build`; borra `public/hot` si existe.
6. `php artisan migrate:status` y después `php artisan migrate --force` (con la copia de
   seguridad del paso 2 ya hecha).
7. `php artisan optimize:clear` y después `php artisan optimize`.
8. `php artisan up`

## 12. Comprobaciones después de instalar

1. `php artisan about`: entorno `production`, depuración desactivada, cachés de
   configuración, rutas y vistas activas, idioma `es`.
2. `php artisan migrate:status`: todas las migraciones aparecen como `Ran`.
3. `php artisan schedule:list`: aparece `chatbot-events-purge`.
4. La página de inicio de sesión carga con estilos (no existe `public/hot`).
5. Una URL inexistente muestra «Página no encontrada», sin detalles técnicos.
6. Inicia sesión con la cuenta de Secretaría.
7. Registra una cuenta de prueba por cada rol, apruébalas desde «Usuarios pendientes» e
   inicia sesión con cada una.
8. **Bloquea las cuentas de prueba.** La pantalla «Usuarios pendientes» solo muestra cuentas
   pendientes, así que una cuenta ya aprobada no aparece ahí y hoy no puede bloquearse desde
   la interfaz. Mientras no exista una pantalla para ello, bloquéalas desde la consola:

   ```
   php artisan tinker --execute="App\Models\User::where('email', 'correo-de-prueba@ejemplo')->update(['status' => 'bloqueado', 'approved_at' => null, 'approved_by' => null]);"
   ```

   Repite el comando con cada correo de prueba y comprueba después que esas cuentas ya no
   pueden iniciar sesión.
9. Cambia la contraseña de una cuenta y comprueba que la sesión abierta con esa cuenta en otro
   navegador se cierra.
10. En «¿Olvidaste tu contraseña?», solicita el enlace para un correo real: el correo llega y
    el enlace usa `https://` y el dominio correcto.
11. En las herramientas del navegador, la cookie de sesión tiene las marcas *Secure* y
    *HttpOnly*.
12. El chatbot responde y `php artisan chatbot:report` muestra eventos.
13. `storage/logs` se escribe sin errores de permisos.
14. Una copia de seguridad se genera y se restaura correctamente en otro equipo.
15. Si hay proxy: los intentos fallidos de inicio de sesión se cuentan con la IP real del
    visitante, no con la del proxy.

## 13. Notas sobre el esquema

Diferencias conocidas entre la base original de la parroquia y una instalación nueva, para que
nadie se sorprenda al comparar estructuras:

- **Índice duplicado `uq_users_email`.** La base original tiene dos índices únicos sobre
  `users.email` (`users_email_unique` y `uq_users_email`). Las migraciones solo crean el primero;
  el segundo es redundante.
- **Migración registrada sin archivo.** La tabla `migrations` de la base original registra
  `2026_04_28_060434_add_location_to_comunidades_table`, que no está en el repositorio. Laravel
  ignora esa fila; solo fallaría un `migrate:rollback` de ese lote.
- **Columnas de sacramentos.** `2026_05_01_000001_add_sacramentos_and_rbac_fields` ya no añade
  las columnas `bautizo_*` y `primera_comunion_*` a `alumnos`: la base original no las tiene y
  ningún código las usa.

## 14. Clasificador de intenciones del chatbot (opcional)

El chatbot decide qué se le pregunta con **reglas**. Opcionalmente, un modelo de lenguaje puede ayudar
cuando las reglas no entienden el mensaje. Hay tres modos (`CHATBOT_CLASSIFIER_MODE`):

| Modo | Qué hace | ¿El usuario espera al modelo? |
|---|---|---|
| `off` (por defecto) | Solo reglas; no se contacta a ningún proveedor | No |
| `shadow` | La respuesta sale de las reglas; el modelo clasifica **después** de responder y solo se registra la comparación | No |
| `assist` | Si las reglas no reconocen el mensaje (`NO_MATCH`) o dudan entre varias intenciones (`AMBIGUOUS_INTENT`), se consulta al modelo **en la misma petición** y su elección se usa | **Sí**, solo en esos mensajes |

**Por defecto está desactivado** (`off`): un servidor sin configurar no depende de Ollama, ni de una GPU,
ni de ningún programa adicional.

### Preparación (una vez)

1. Instala Ollama y descarga el modelo en el equipo (no lo hace la aplicación):
   `hf.co/Mungert/Qwen3-4B-Instruct-2507-GGUF:Q4_K_M` (Qwen3-4B-Instruct-2507, licencia Apache 2.0).
   Con una GPU de 4 GB el modelo cabe entero.
2. Aplica la migración de las columnas del clasificador en `chatbot_events`, con la copia de
   seguridad previa:

   ```
   php artisan migrate:status
   php artisan migrate --path=database/migrations/2026_10_21_000001_add_classifier_columns_to_chatbot_events_table.php
   ```

   Sin ella, con `shadow` o `assist` los eventos del chatbot no se guardan: la respuesta al usuario no
   cambia y queda un aviso `chatbot.event_failed` en el log.

### Activar el modo assist (equipo dedicado con Ollama)

En `.env`:

```
CHATBOT_CLASSIFIER_MODE=assist
CHATBOT_CLASSIFIER_DRIVER=ollama
CHATBOT_OLLAMA_URL=http://localhost:11434
CHATBOT_OLLAMA_MODEL=hf.co/Mungert/Qwen3-4B-Instruct-2507-GGUF:Q4_K_M
CHATBOT_CLASSIFIER_TIMEOUT=5
CHATBOT_OLLAMA_KEEP_ALIVE=1h
```

Después, `php artisan config:clear` (o `php artisan optimize` en producción). Para el modo sombra, lo
mismo con `CHATBOT_CLASSIFIER_MODE=shadow` (ahí el tiempo máximo puede ser 8, porque el usuario no espera).

Para desactivarlo: `CHATBOT_CLASSIFIER_MODE=off` y otra vez `config:clear` u `optimize`.

- **`CHATBOT_OLLAMA_KEEP_ALIVE=1h`:** en un equipo dedicado, mantiene el modelo cargado una hora desde
  la última consulta, para que no se descargue entre consultas y la primera respuesta no tarde de más.
  Ollama también acepta `-1` (no descargarlo nunca), a costa de ocupar la memoria de la GPU todo el tiempo.
- **`CHATBOT_CLASSIFIER_TIMEOUT=5`:** en assist el usuario espera al modelo. Con el modelo ya cargado, la
  clasificación debería tardar del orden de 1 a 2 segundos: el prompt tiene ~1.240 tokens y la
  respuesta es muy corta. Es una estimación; mídelo con el comando de evaluación. Si el modelo estaba
  descargado y tarda más de 5 segundos, el usuario recibe la respuesta de las reglas, como sin modelo.

**Cómo funciona el modo assist:**
- El modelo **solo** se consulta con `NO_MATCH` o `AMBIGUOUS_INTENT`; nunca si las reglas ya entendieron
  el mensaje, si lo bloqueó una guarda, ni con mensajes vacíos o inválidos.
- Con `AMBIGUOUS_INTENT`, solo puede elegir entre las intenciones candidatas de las reglas.
- La intención elegida pasa por el filtro de escritura (una consulta para un mensaje que pide cambiar o
  borrar datos se descarta) y después por el **motor de reglas y los agentes, que revalidan los permisos
  como siempre**: el modelo nunca da acceso a nada que el rol no tenga.
- Si el modelo falla, tarda más del tiempo máximo o responde algo inválido, el usuario recibe la
  respuesta de las reglas.
- En `chatbot_events`, `classifier_outcome = USED` indica que la respuesta vino del modelo, y
  `FALLBACK_<motivo>` que se volvió a las reglas. Se guardan además la intención del modelo, la latencia
  (redondeada a 100 ms) y la versión del prompt. Nunca el texto.

**Cómo funciona en general:**
- El adaptador siempre pide la respuesta sin razonamiento (`"think": false`). Si llega vacía, en un
  formato no válido, fuera de la lista de intenciones o tras el tiempo máximo, se descarta.
- El modelo nunca puede elegir una intención de modificar datos.
- Los mensajes que bloquea una guarda de las reglas, los vacíos y los inválidos no se le envían.

### Medir y mejorar

- **`php artisan chatbot:report`** resume, además de lo habitual:
  - cuántas consultas resolvió el modelo en assist y cuántas volvieron a las reglas;
  - acuerdos, desacuerdos y rescates en sombra;
  - la latencia (media, p95 y máxima);
  - las formas de mensaje (enmascaradas) de los desacuerdos, los rescates y las respuestas del modelo.
- **`php artisan chatbot:evaluate-classifier`** pasa por el modelo real las frases de
  `tests/Fixtures/chatbot_frases.php` (escritas para ello, con formas coloquiales y casos dudosos). Muestra:
  - el porcentaje de aciertos total y por intención, del modelo solo y del modo assist;
  - los fallos del proveedor y la latencia;
  - la tabla de errores.

  Necesita Ollama encendido y `CHATBOT_CLASSIFIER_DRIVER=ollama`; no forma parte de la suite de pruebas.
  Opciones: `--intent=VIEW_EVALUATIONS` y `--file=ruta`.
- **Al cambiar las descripciones** del prompt (`app/Services/Chatbot/Intent/IntentDescriptions.php`), sube
  `VERSION` y vuelve a evaluar.

**Privacidad:**
- Con Ollama en el mismo equipo (`localhost`), el texto de los mensajes no sale del servidor.
- **Un proveedor remoto** (otra máquina, un servicio en la nube) **recibiría el texto que escriben los
  usuarios**, que puede incluir nombres de alumnos. Configurarlo exige una decisión explícita de la
  parroquia, revisar las condiciones del proveedor y avisar a los usuarios.
- En ningún modo se registran el mensaje, el prompt ni la respuesta del modelo: solo códigos.

**Apache con PHP como módulo (`mod_php`, p. ej. XAMPP), solo en modo sombra** (en assist la espera es
intencionada: la respuesta depende del modelo):
- La clasificación en sombra se ejecuta después de enviar la respuesta. Con `mod_php` la conexión sigue
  abierta mientras dura, y sin más el navegador esperaría también el tiempo del modelo. Con PHP-FPM no ocurre.
- Para evitarlo, cuando hay una clasificación pendiente la respuesta del chatbot lleva `Content-Length`
  (en bytes). Así el navegador la da por completa al instante, sin esperar al modelo.
- Esa respuesta pide a Apache que no la comprima (`no-gzip`), porque `mod_deflate` quitaría la longitud.
  En XAMPP, `mod_deflate` viene desactivado.
- Si PHP comprime la salida (`zlib.output_compression=On` en `php.ini`), la longitud no coincidiría con
  lo enviado y no se añade. En ese caso el usuario volvería a esperar al modelo: deja
  `zlib.output_compression=Off`, como viene en XAMPP.
- **Comprobación recomendada** tras activar el modo sombra: en las herramientas del navegador (pestaña
  Red), la petición `chatbot/message` debe terminar en el tiempo habitual y no en el del modelo.
