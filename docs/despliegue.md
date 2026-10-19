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

3. Genera la clave de la aplicación **una sola vez**:

   ```
   php artisan key:generate
   ```

   Guarda una copia de `APP_KEY` en un lugar seguro. Si cambia, se cierran todas las sesiones
   y no se puede descifrar lo cifrado con la clave anterior.

## 5. Base de datos

1. Crea la base y el usuario de MySQL con permisos solo sobre ella.
2. **Importa la estructura desde un volcado de la base actual.** El repositorio no contiene
   las migraciones de creación de las tablas académicas (comunidades, periodos, niveles,
   grupos, alumnos, inscripciones, evaluaciones...), así que `migrate` por sí solo no crea
   una base completa.
3. Revisa qué migraciones constan como aplicadas:

   ```
   php artisan migrate:status
   ```

4. Aplica solo las pendientes, una por una, con `--path`. Por ejemplo, la bitácora del
   chatbot:

   ```
   php artisan migrate --path=database/migrations/2026_10_18_000001_create_chatbot_events_table.php
   ```

   No ejecutes `php artisan migrate` sin `--path` hasta haber revisado `migrate:status`.

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
6. `php artisan migrate:status` y aplica las migraciones pendientes con `--path` (paso 5).
7. `php artisan optimize:clear` y después `php artisan optimize`.
8. `php artisan up`

## 12. Comprobaciones después de instalar

1. `php artisan about`: entorno `production`, depuración desactivada, cachés de
   configuración, rutas y vistas activas, idioma `es`.
2. `php artisan migrate:status`: sin migraciones pendientes inesperadas.
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
