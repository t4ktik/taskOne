# TaskOne

TaskOne es una aplicación web de gestión de proyectos (versión 2.5, instalación propia, no SaaS). Sirve para organizar proyectos, tareas, tiempos, gastos y facturas de un equipo, con inicio de sesión, roles y una página de aterrizaje.

El panel distingue tres tipos de cuenta:

- **admin**: idiomas, plantillas de correo y ajustes del sitio.
- **owner**: proyectos, miembros, facturas y la configuración de cobro del propietario.
- **user**: miembro invitado. En un proyecto también puede participar un contacto con rol de **client**.

## Funciones principales

- Panel con totales de proyectos, tareas y gastos, estado de los proyectos y tareas próximas a vencer.
- Proyectos con presupuesto, fechas, etiquetas, progreso y estados `on_hold`, `in_progress`, `complete` y `canceled`.
- Miembros del proyecto, invitación de usuarios y clientes, y permisos por módulo (hitos, tareas, partes de horas, gastos, actividad y ajustes del proyecto).
- Tareas en tablero, con etapas, prioridad, checklist, comentarios, archivos, favoritos y arrastre en el calendario.
- Diagrama de Gantt del proyecto.
- Partes de horas (timesheet) por proyecto y por tarea.
- Gastos del proyecto.
- Facturas con líneas, impuestos, recordatorios, envío por correo y cobro con Stripe o PayPal. Esas credenciales de cobro se guardan en los ajustes del propietario, no en `.env`.
- Lista de tareas personales (to-do) y modo claro/oscuro.
- Mensajes en tiempo real con Chatify (ruta por defecto `/messages`).
- Plantillas de correo (invitación, proyecto, tarea y timesheet) e idiomas. El repositorio incluye `resources/lang/en.json` y `resources/lang/es.json`. El idioma por defecto de la aplicación es `en`.
- Registro, inicio de sesión, recuperación de contraseña y verificación de correo de Laravel (`Auth::routes()`).
- Instalador web en `/install` mientras no exista el archivo `storage/installed`.

La API solo expone `GET /api/user`, protegida con `auth:api`. El resto de la aplicación vive en las rutas web de `routes/web.php`.

## Stack

| Capa | Tecnología |
| --- | --- |
| Lenguaje | PHP `^8.3` (declarado en `composer.json`; `verification.php` y el instalador exigen PHP 8.3 o superior) |
| Framework | Laravel 13 (`laravel/framework` 13.x en `composer.lock`) |
| Base de datos | MySQL. `.env.example` usa el puerto `3306`. El asistente de instalación solo configura MySQL. Las pruebas de PHPUnit usan SQLite en memoria. |
| Frontend | Blade, Bootstrap 4, jQuery, Sass y Vite (`vite.config.js` compila `resources/js/app.js` y `resources/sass/app.scss` hacia `public/js/app.js` y `public/css/app.css`) |
| HTTP | Guzzle 7 |
| Pagos | `stripe/stripe-php` y la API REST de PayPal (`App\Services\PayPalCheckout`) |
| Mensajes | `munafio/chatify` 1.0, vendido en `packages/chatify` |
| Instalador | `wikigods/laravel-installer` |
| Correo y formularios | Laravel Mail y `laravelcollective/html` |
| Pruebas | PHPUnit 8 |

## Estructura del proyecto

```text
app/                 Modelos, controladores HTTP, correo y middleware
bootstrap/           Arranque de Laravel
config/              Configuración (app, base de datos, PayPal, Chatify, correo…)
database/
  migrations/        Esquema
  seeders/           Usuarios de demostración
public/              Assets compilados, landing, instalador y front controller de Laravel
resources/
  js/  sass/         Código que compila Vite
  lang/              Cadenas en inglés y español
  views/             Plantillas Blade
routes/
  web.php            Rutas de la aplicación
  api.php            GET /api/user
  console.php        Comando artisan inspire
tests/               Tests Unit y Feature de ejemplo
index.php            Front controller para el servidor embebido de PHP y Apache
verification.php     Comprueba PHP y permisos antes de la instalación
vite.config.js       Entradas de Vite
artisan              CLI de Laravel
```

El documento raíz del sitio es el directorio del repositorio (donde están `index.php` y `.htaccess`), no solo `public/`. `.htaccess` reescribe las peticiones a `index.php` y sirve `css`, `js`, `assets`, `landing`, `storage` e `installer` desde `public/`.

## Requisitos

- PHP 8.3 o superior (`^8.3`). Laravel 13 no arranca en PHP 8.2 ni anteriores.
- Extensiones que comprueba el instalador: `openssl`, `pdo`, `mbstring`, `tokenizer`, `json` y `curl`. Hace falta también el driver PDO de MySQL.
- Composer.
- MySQL.
- Node.js y npm, solo para compilar los assets de `package.json`.
- Apache con `mod_rewrite` si se publica con Apache. El `.htaccess` de la raíz ya está preparado.

Mientras no exista `storage/installed`, `verification.php` exige permiso `777` en:

- `storage/framework/` y sus carpetas `cache/`, `sessions/` y `views/`
- `storage/logs/`
- `bootstrap/cache/`
- `resources/lang/`
- `.env`

## Instalación

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Crea la base de datos MySQL y rellena `DB_DATABASE`, `DB_USERNAME` y `DB_PASSWORD` en `.env`. El ejemplo usa la base `laravel`, el usuario `root` y el host `127.0.0.1` en el puerto `3306`.

```bash
php artisan migrate --seed
php artisan storage:link
touch storage/installed
npm install
npm run dev
```

`storage/installed` es el archivo que marca la aplicación como instalada. Sin él, `/` redirige al instalador (`/install`) y el `index.php` de la raíz pasa antes por `verification.php`.

`php artisan storage:link` enlaza `public/storage` con `storage/app/public`. El logo y los archivos subidos se sirven por esa ruta.

El seeder `database/seeders/UsersTableSeeder.php` crea dos usuarios locales. Las contraseñas salen de `SEED_ADMIN_PASSWORD` y `SEED_OWNER_PASSWORD`; si no están definidas, el seeder genera una contraseña aleatoria y la escribe en `storage/app/seeded-credentials.txt`.

| Correo | Tipo |
| --- | --- |
| `admin@example.com` | `admin` |
| `owner@example.com` | `owner` |

### Instalador web

Si prefieres el asistente, no crees `storage/installed` a mano. Arranca el servidor, abre el sitio y completa `/install`. El asistente escribe `.env`, ejecuta las migraciones y crea `storage/installed`. Antes tiene que cumplirse la comprobación de PHP y de permisos de `verification.php`. Después puedes cargar los usuarios de demostración con `php artisan db:seed`.

## Variables de entorno

Copia `.env.example` y no subas `.env` (ya está en `.gitignore`). Aquí solo figuran el nombre y para qué sirve cada variable. Los valores van en tu `.env` local.

### Aplicación

| Variable | Uso |
| --- | --- |
| `APP_NAME` | Nombre que se muestra en la interfaz, los correos y el remitente de las plantillas. |
| `APP_ENV` | Entorno de Laravel (`local` en desarrollo, `production` en producción). |
| `APP_KEY` | Clave de cifrado. La genera `php artisan key:generate`. |
| `APP_DEBUG` | Muestra errores detallados. Déjalo en `false` fuera de desarrollo. |
| `APP_URL` | URL pública. Tiene que coincidir con la dirección desde la que abres la aplicación. |
| `ASSET_URL` | Prefijo opcional de los assets. |
| `LOG_CHANNEL` | Canal de log. El ejemplo usa `stack`. |

### Base de datos

| Variable | Uso |
| --- | --- |
| `DB_CONNECTION` | Driver. El ejemplo y el instalador usan `mysql`. |
| `DB_HOST` | Host de MySQL. |
| `DB_PORT` | Puerto de MySQL. En el ejemplo, `3306`. |
| `DB_DATABASE` | Nombre de la base. |
| `DB_USERNAME` | Usuario de MySQL. |
| `DB_PASSWORD` | Contraseña de MySQL. |
| `DB_SOCKET` | Socket de MySQL, si no conectas por host y puerto. |

### Sesión, caché y colas

| Variable | Uso |
| --- | --- |
| `BROADCAST_DRIVER` | Driver de broadcasting. El ejemplo usa `log`. El chat en vivo necesita Pusher. |
| `CACHE_DRIVER` | Driver de caché. El ejemplo usa `file`. |
| `QUEUE_CONNECTION` | Cola. Con `sync` los trabajos se ejecutan en la misma petición y no hace falta un worker. |
| `SESSION_DRIVER` | Driver de sesión. El ejemplo usa `file`. |
| `SESSION_LIFETIME` | Minutos de vida de la sesión. El ejemplo usa `120`. |

### Redis

Solo hace falta si usas Redis para caché, sesión, colas o broadcasting: `REDIS_HOST`, `REDIS_PASSWORD`, `REDIS_PORT`.

### Correo

`config/mail.php` lee `MAIL_MAILER`. La pantalla de ajustes del administrador lee y guarda `MAIL_DRIVER`. Si configuras el correo, deja las dos con el mismo valor.

| Variable | Uso |
| --- | --- |
| `MAIL_MAILER` | Mailer de Laravel (`smtp` en el ejemplo). |
| `MAIL_DRIVER` | Mismo dato, usado por la pantalla de ajustes. |
| `MAIL_HOST` | Host SMTP. |
| `MAIL_PORT` | Puerto SMTP. |
| `MAIL_USERNAME` | Usuario SMTP. |
| `MAIL_PASSWORD` | Contraseña SMTP. |
| `MAIL_ENCRYPTION` | Cifrado de la conexión SMTP. |
| `MAIL_FROM_ADDRESS` | Dirección del remitente. |
| `MAIL_FROM_NAME` | Nombre del remitente. En el ejemplo toma `APP_NAME`. |

### Pusher y Chatify

Pusher alimenta el broadcasting y el chat. Sin estas claves el resto de la aplicación arranca igual; los mensajes en vivo no.

| Variable | Uso |
| --- | --- |
| `PUSHER_APP_ID` | Identificador de la app de Pusher. |
| `PUSHER_APP_KEY` | Clave pública de Pusher. |
| `PUSHER_APP_SECRET` | Secreto de Pusher. |
| `PUSHER_APP_CLUSTER` | Cluster de Pusher. |
| `MIX_PUSHER_APP_KEY` | Ya no la lee el bundle. El chat toma `PUSHER_APP_KEY` desde la configuración de Laravel. |
| `MIX_PUSHER_APP_CLUSTER` | Ya no la lee el bundle. El chat toma `PUSHER_APP_CLUSTER` desde la configuración de Laravel. |
| `CHATIFY_NAME` | Título del módulo de mensajes. Por defecto, `Messages`. |
| `CHATIFY_PATH` | Ruta del chat. Por defecto, `messages`. |
| `CHATIFY_MIDDLEWARE` | Middleware de esas rutas. Por defecto, `XSS`. |

### Pagos

Las facturas cobran con las claves que cada propietario guarda en Ajustes. Estas variables cubren la configuración global que leen `config/paypal.php` y la ruta `POST /stripe`.

| Variable | Uso |
| --- | --- |
| `PAYPAL_CLIENT_ID` | Client ID de la API REST de PayPal. |
| `PAYPAL_SECRET_KEY` | Secreto de la API REST de PayPal. |
| `PAYPAL_MODE` | `sandbox` o `live`. Por defecto, `sandbox`. |
| `STRIPE_SECRET` | Secreto de Stripe que usa `POST /stripe`. |

### Almacenamiento S3

Opcional. El disco por defecto es `local` (`FILESYSTEM_DRIVER`). Para S3: `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION`, `AWS_BUCKET`.

## Desarrollo

No hay `server.php`, así que `php artisan serve` no tiene el router que espera Laravel. El `index.php` de la raíz está escrito para el servidor embebido de PHP:

```bash
php -S 127.0.0.1:8000 index.php
```

Abre `http://127.0.0.1:8000` y pon esa misma URL en `APP_URL`. Entra en `/login` con uno de los usuarios del seeder. `/home` es el panel una vez autenticado.

Mientras cambias JavaScript o Sass:

```bash
npm run watch
```

Vite escribe `public/js/app.js` y `public/css/app.css`, que son las rutas que ya usan las plantillas. No hay servidor de recarga en caliente: las vistas no llaman a `@vite`.

## Producción

1. Apunta el virtual host a la raíz del repositorio, con `mod_rewrite` activo y `AllowOverride` para que se aplique `.htaccess`.
2. En `.env`: `APP_ENV=production` y `APP_DEBUG=false`. `APP_URL` debe ser la URL pública.
3. Instala dependencias y compila assets:

```bash
composer install --no-dev --optimize-autoloader
npm install
npm run production
php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan view:cache
```

No ejecutes `php artisan route:cache`: `routes/api.php` registra una closure y Laravel no puede cachear esas rutas.

Con `QUEUE_CONNECTION=sync` no hace falta un worker. Si cambias la cola a `database` o `redis`, arranca `php artisan queue:work`. `app/Console/Kernel.php` no programa ninguna tarea.

## Pruebas

PHPUnit está en `require-dev`. La configuración es `phpunit.xml`: el entorno es `testing`, la base es SQLite en memoria (`DB_DATABASE=:memory:`) y hay dos suites, `Unit` (`tests/Unit`) y `Feature` (`tests/Feature`).

```bash
composer install
./vendor/bin/phpunit
```

Para una sola suite:

```bash
./vendor/bin/phpunit --testsuite Unit
./vendor/bin/phpunit --testsuite Feature
```

`tests/Feature/ExampleTest.php` pide `GET /` sin `storage/installed` y espera la redirección a `/install`. `tests/Unit/ExampleTest.php` es un assert de ejemplo. No hay un script `test` en `composer.json` ni en `package.json`. PHPUnit 12 se ejecuta con `php artisan test` o `vendor/bin/phpunit`.

## Scripts

### npm (`package.json`)

| Script | Qué hace |
| --- | --- |
| `npm run dev` | Compila JS y Sass hacia `public/js/app.js` y `public/css/app.css`. |
| `npm run development` | Igual que `npm run dev`. |
| `npm run watch` | Recompila al cambiar los archivos de entrada. |
| `npm run build` | Compila los assets para producción. |
| `npm run prod` | Alias de `npm run production`. |
| `npm run production` | Igual que `npm run build`. |

### Composer

`composer.json` no define scripts de aplicación. Los que hay son los de Laravel y se lanzan solos:

- `post-autoload-dump` ejecuta `php artisan package:discover`.
- `post-root-package-install` copia `.env.example` a `.env` si aún no existe.
- `post-create-project-cmd` ejecuta `php artisan key:generate`.

### Artisan

Los comandos que usa este proyecto para instalarse y publicarse son `key:generate`, `migrate`, `db:seed`, `storage:link`, `config:cache` y `view:cache`. `routes/console.php` añade `php artisan inspire`. El listado completo del framework es `php artisan list`.

## Contribuir

Las contribuciones son bienvenidas mediante pull requests contra `main`.

- No subas `.env` ni claves reales.
- Los cambios de aplicación van en el código; este repositorio ya separa rutas (`routes/`), lógica (`app/`) y vistas (`resources/views/`).
- Si tocas textos de la interfaz, actualiza `resources/lang/en.json` y `resources/lang/es.json`.
- Ejecuta `./vendor/bin/phpunit` antes de abrir el pull request.

## Licencia

`composer.json` declara la licencia MIT. El repositorio no incluye un archivo `LICENSE` en la raíz.
