# Guía de despliegue — BrigadaSalud API

Pasos para llevar este backend Laravel de un entorno local (Laragon) a un servidor de producción. Escrita para **este proyecto específico** (Laravel 10 clásico, MySQL, Sanctum, `spatie/laravel-permission`), no como checklist genérico de Laravel.

## 0. Antes de tocar el servidor

- [ ] `php artisan migrate:fresh --seed` corre limpio en local (confirma que migraciones + `RolesAndPermissionsSeeder` no están rotos).
- [ ] `composer.json` no tiene dependencias de desarrollo que el código de producción necesite en runtime (revisa que nada en `app/` use algo de `require-dev`).
- [ ] Decidiste dónde vive la base de datos de producción (mismo servidor o uno aparte) y tienes sus credenciales.
- [ ] Tienes un dominio (o subdominio) apuntando al servidor, o al menos su IP, para configurar `APP_URL` y CORS correctamente.

---

## 1. Requisitos del servidor

- PHP **8.1 o superior** con extensiones: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`.
- MySQL 5.7+ / 8.x (o MariaDB equivalente).
- Composer 2.x.
- Nginx o Apache (ejemplos de configuración para ambos más abajo).
- Certificado TLS (Let's Encrypt es suficiente) — la app móvil y el frontend deben hablarle a la API por **HTTPS**, no HTTP.
- Acceso SSH al servidor.

```bash
php -v            # confirmar >= 8.1
php -m | grep -E "pdo_mysql|mbstring|openssl|bcmath"
composer --version
mysql --version
```

---

## 2. Subir el código

Con Git (recomendado, facilita actualizaciones futuras):

```bash
ssh usuario@tu-servidor
cd /var/www
git clone <url-del-repositorio> brigadasalud
cd brigadasalud
```

Sin Git (subida manual): comprime el proyecto **sin** `vendor/`, `.env`, `node_modules/` ni `storage/logs/*`, súbelo por SFTP/rsync y descomprímelo en el servidor.

```bash
# Ejemplo con rsync desde tu máquina local
rsync -avz --exclude 'vendor' --exclude 'node_modules' --exclude '.env' --exclude 'storage/logs' \
  ./ usuario@tu-servidor:/var/www/brigadasalud/
```

---

## 3. Instalar dependencias (modo producción)

```bash
cd /var/www/brigadasalud
composer install --no-dev --optimize-autoloader
```

- `--no-dev` excluye Pint, Sail, Faker, PHPUnit, etc. — no se necesitan en producción.
- `--optimize-autoloader` genera un autoloader por classmap, más rápido que el PSR-4 dinámico que usamos en local.

---

## 4. Configurar `.env`

```bash
cp .env.example .env
nano .env   # o el editor que prefieras
```

Valores que **debes** cambiar respecto al `.env.example`:

```dotenv
APP_NAME=BrigadaSalud
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.tu-dominio.com

LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=brigadasalud_prod
DB_USERNAME=un_usuario_dedicado
DB_PASSWORD=una_contraseña_fuerte_y_distinta_a_la_de_local
```

**Por qué importa cada uno:**
- `APP_DEBUG=false` — con `true` en producción, cualquier error 500 expone rutas del servidor, variables de entorno y trazas completas en la respuesta JSON. Es el error de seguridad más común al desplegar Laravel.
- `APP_ENV=production` — cambia el comportamiento de varios paquetes (Ignition no se instala como error handler visual, por ejemplo) y es lo que usarías en `if (app()->environment('production'))` si agregas ese guard al seeder (ver sección 6).
- `DB_USERNAME`/`DB_PASSWORD` — nunca reutilices las credenciales de tu MySQL local (Laragon) en el servidor real.
- `APP_URL` — Sanctum y la generación de URLs absolutas dependen de este valor.

No toques `SESSION_DRIVER`, `CACHE_DRIVER` ni `QUEUE_CONNECTION` a menos que sepas que los necesitas: esta API es *stateless* (tokens Bearer, no cookies de sesión), así que los valores por defecto (`file`, `sync`) alcanzan sin infraestructura extra (Redis, colas, etc.).

---

## 5. Generar la `APP_KEY`

```bash
php artisan key:generate
```

Esto cifra las cookies y datos sensibles de la sesión. Cada entorno (local, staging, producción) debe tener su propia clave — nunca copies la de `.env` local al servidor.

---

## 6. Base de datos: migrar y sembrar

```bash
php artisan migrate --force
```

`--force` es obligatorio: en `APP_ENV=production` Laravel pide confirmación interactiva para correr migraciones, y `--force` la evita (necesario en scripts/CI sin TTY).

**⚠️ No corras `php artisan db:seed` a secas en producción.** `DatabaseSeeder` llama tanto a `RolesAndPermissionsSeeder` (roles/permisos reales, necesarios) como a `DemoDataSeeder` (pacientes, brigadas, turnos y usuarios **ficticios** para pruebas — ver `BrigadaSalud_Contexto_Proyecto.md`). Sembrar eso en una base de datos real mezclaría datos de prueba con datos reales de pacientes.

Corre solo lo que necesitas:

```bash
php artisan db:seed --class=RolesAndPermissionsSeeder --force
```

Esto crea los roles (`Administrador`, `Coordinador`, `Brigadista`), los permisos, y el usuario `administrador@brigadasalud.test` / `password123`.

**Inmediatamente después**, cambia esa contraseña (o mejor, crea el usuario real del Administrador y elimina el de prueba):

```bash
php artisan tinker
>>> $u = App\Models\User::where('email', 'administrador@brigadasalud.test')->first();
>>> $u->update(['email' => 'admin-real@tu-dominio.com', 'password' => Hash::make('una-contraseña-fuerte-real')]);
```

> **Mejora opcional para el futuro:** envolver la llamada a `DemoDataSeeder` en `DatabaseSeeder::run()` con `if (! app()->environment('production')) { $this->call(DemoDataSeeder::class); }` para que sea imposible sembrar datos ficticios en producción por accidente, incluso si alguien corre `db:seed` sin `--class`.

---

## 7. Permisos de archivos

Laravel necesita escribir en `storage/` y `bootstrap/cache/` (logs, cachés compiladas, sesiones de archivo):

```bash
sudo chown -R www-data:www-data /var/www/brigadasalud
sudo find /var/www/brigadasalud -type f -exec chmod 644 {} \;
sudo find /var/www/brigadasalud -type d -exec chmod 755 {} \;
sudo chmod -R 775 storage bootstrap/cache
```

Ajusta `www-data` al usuario real de tu servidor web (`nginx`, `apache`, etc. — revisa con `ps aux | grep -E "nginx|apache"`).

---

## 8. Configurar el servidor web

El **document root debe apuntar a `/public`**, nunca a la raíz del proyecto — de lo contrario `.env`, `app/`, `database/` quedan servibles como archivos estáticos.

### Nginx (recomendado)

```nginx
server {
    listen 80;
    server_name api.tu-dominio.com;
    root /var/www/brigadasalud/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Luego el bloque HTTPS (después de correr Certbot, sección 9) se genera automáticamente si usas `certbot --nginx`.

### Apache (alternativa)

Con `mod_rewrite` habilitado y `AllowOverride All`, el `.htaccess` que ya trae `public/` es suficiente:

```apache
<VirtualHost *:80>
    ServerName api.tu-dominio.com
    DocumentRoot /var/www/brigadasalud/public

    <Directory /var/www/brigadasalud/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/brigadasalud-error.log
    CustomLog ${APACHE_LOG_DIR}/brigadasalud-access.log combined
</VirtualHost>
```

```bash
sudo a2enmod rewrite
sudo a2ensite brigadasalud
sudo systemctl reload apache2
```

---

## 9. HTTPS con Let's Encrypt

```bash
sudo apt install certbot python3-certbot-nginx   # o python3-certbot-apache
sudo certbot --nginx -d api.tu-dominio.com
```

Certbot configura la renovación automática (`certbot renew` vía cron/systemd timer, ya viene preinstalado en la mayoría de distros). Verifica con:

```bash
sudo certbot renew --dry-run
```

---

## 10. Optimizar para producción

Con `APP_ENV=production` ya en `.env`:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

Estos comandos compilan configuración, rutas y vistas en archivos únicos — evitan que Laravel relea y reconstruya todo en cada petición.

**⚠️ Trampa común**: si después de cachear cambias algo en `.env` o en `config/*.php` y no vuelves a correr `config:cache`, el cambio **no se aplica** (Laravel lee el caché, no el archivo). Cada vez que edites `.env` en el servidor:

```bash
php artisan config:clear && php artisan config:cache
```

---

## 11. CORS y Sanctum — qué sí y qué no tocar

- Este proyecto usa **tokens Bearer** (`createToken()` en `AuthController`), no autenticación por cookies de Sanctum SPA. Por lo tanto **no necesitas** configurar `SANCTUM_STATEFUL_DOMAINS` — eso es solo para el flujo de cookies de una SPA en el mismo dominio, que aquí no se usa.
- `config/cors.php` ya trae `allowed_origins => ['*']` con `supports_credentials => false`. Como no se envían cookies, esto es seguro tal cual (no hay credenciales de sesión que un origen malicioso pueda robar). Si más adelante quieres restringirlo a los dominios reales de la app web y el panel, cámbialo a:

```php
'allowed_origins' => ['https://app.tu-dominio.com', 'https://panel.tu-dominio.com'],
```

---

## 12. Prueba de humo (smoke test)

Antes de darlo por desplegado, repite la misma prueba que hicimos en local:

```bash
curl -s -X POST https://api.tu-dominio.com/api/v1/login \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"email":"admin-real@tu-dominio.com","password":"tu-contraseña-real"}'
```

Debe devolver `{"user": {...}, "token": "...", "token_type": "Bearer"}`. Si devuelve un error 500 con traza (¡y `APP_DEBUG` está en `false`!) revisa `storage/logs/laravel.log` en el servidor — ahí sigue registrándose el detalle aunque la respuesta al cliente sea genérica.

Prueba también un caso de error para confirmar que los mensajes claros en español siguen funcionando (ver `BrigadaSalud_Contexto_Proyecto.md`, sección de manejo de errores):

```bash
curl -s https://api.tu-dominio.com/api/v1/pacientes -H "Accept: application/json"
# Debe responder 401: {"message":"No autenticado. Debes iniciar sesión para acceder a este recurso."}
```

---

## 13. Checklist de seguridad final

- [ ] `APP_DEBUG=false` en `.env` de producción.
- [ ] `APP_ENV=production`.
- [ ] Contraseñas de `administrador@brigadasalud.test` y `coordinador@brigadasalud.test` cambiadas o esos usuarios eliminados.
- [ ] `DemoDataSeeder` **no** se corrió contra la base de datos real.
- [ ] `.env` no es accesible por HTTP (probar `https://api.tu-dominio.com/.env` → debe dar 404, no el contenido del archivo).
- [ ] Credenciales de MySQL de producción distintas a las de desarrollo.
- [ ] HTTPS activo y funcionando (candado válido en el navegador).
- [ ] `storage/` y `bootstrap/cache/` con permisos de escritura para el usuario del servidor web, pero **no** servibles públicamente (ya los cubre el `document root` en `/public`).
- [ ] Backups de la base de datos programados (ver sección 15).

---

## 14. Cómo actualizar el despliegue (redeploy)

Cada vez que haya cambios nuevos que subir:

```bash
cd /var/www/brigadasalud
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan down --render="errors::503"   # modo mantenimiento (opcional, útil si hay migraciones)
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan up
```

`php artisan down` muestra una página de mantenimiento a los usuarios mientras corren las migraciones — útil si el cambio incluye una migración que tarda o que podría causar inconsistencias durante unos segundos. Para cambios sin migraciones nuevas, se puede omitir `down`/`up`.

---

## 15. Backups de base de datos (mínimo viable)

Un cron diario simple ya cubre lo esencial mientras el proyecto no tenga un volumen serio de datos:

```bash
# /etc/cron.d/brigadasalud-backup
0 3 * * * www-data mysqldump -u usuario -p'contraseña' brigadasalud_prod | gzip > /var/backups/brigadasalud/$(date +\%Y-\%m-\%d).sql.gz
```

Guarda los backups fuera del propio servidor (descarga periódica, o un bucket S3/equivalente) — un backup que vive en el mismo disco que falló no sirve de nada.

---

## 16. Notas específicas de este proyecto (no genéricas de Laravel)

Estas son cosas que **ya nos mordieron** durante el desarrollo (documentadas con más detalle en `BrigadaSalud_Contexto_Proyecto.md`, sección 10) y que vale la pena tener en mente si algo falla después de desplegar:

- El proyecto usa Laravel **10** con estructura clásica (`app/Http/Kernel.php`), no la estructura de Laravel 11 (`bootstrap/app.php`). Si alguien en el equipo corre `composer update` sin fijar bien las versiones y termina jalando Laravel 11, el proyecto se rompe. `composer.json` ya fija `"laravel/framework": "^10.10"` — no lo cambies sin migrar el proyecto a propósito.
- `config('app.locale')` está en `'es'` y depende de `lang/es/validation.php` para los mensajes de validación en español. Si copias `.env.example` pero no el resto del repo (por ejemplo, alguien arma el servidor a mano sin `git clone`), asegúrate de que la carpeta `lang/` viaje junto con el código.
- El manejo global de errores (`app/Exceptions/Handler.php`) es lo que garantiza que la API nunca devuelva la página HTML de error de Laravel. Con `APP_DEBUG=false` en producción, un error no manejado ahí devuelve `{"message": "Server Error"}` genérico — que es el comportamiento correcto y esperado (revisa `storage/logs/laravel.log`, no la respuesta HTTP, para depurar).

---

**Referencia cruzada**: para el detalle de todos los endpoints, roles y flujos de negocio, ver la documentación de la API (`BrigadaSalud_API_Docs` publicada como artifact) y `BrigadaSalud_Contexto_Proyecto.md`.
