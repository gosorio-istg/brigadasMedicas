# BrigadaMedica API

Backend del sistema **BrigadaMedica**: plataforma web y aplicación móvil para la gestión de brigadas médicas comunitarias en barrios urbano-marginales de Guayaquil (registro de pacientes, asignación de turnos por especialidad y atención médica), reemplazando los procesos manuales en papel.

![PHP](https://img.shields.io/badge/PHP-8.1%2B-777bb4)
![Laravel](https://img.shields.io/badge/Laravel-10.x-ff2d20)
![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-4479a1)
![License](https://img.shields.io/badge/license-MIT-blue)

> Proyecto Integrador de Saberes (PIS) — Instituto Superior Tecnológico Guayaquil (ISTG), carrera Desarrollo de Software, materia Fundamentos de Redes y Telecomunicaciones, Paralelo 3C, Período 2026-S1.

---

## Índice

- [Stack técnico](#stack-técnico)
- [Módulos](#módulos)
- [Requisitos](#requisitos)
- [Instalación local](#instalación-local)
- [Usuarios de prueba](#usuarios-de-prueba)
- [Documentación](#documentación)
- [Testing](#testing)
- [Despliegue a producción](#despliegue-a-producción)
- [Estructura de carpetas relevante](#estructura-de-carpetas-relevante)
- [Equipo](#equipo)

---

## Stack técnico

| Componente | Tecnología |
|---|---|
| Backend | Laravel 10 (estructura clásica, `app/Http/Kernel.php`) |
| Autenticación | Laravel Sanctum (tokens Bearer) |
| Roles y permisos | `spatie/laravel-permission` |
| Base de datos | MySQL |
| App móvil (equipo de brigada) | Android (Kotlin + Retrofit), offline-first |
| Idioma de la API | Español — mensajes de validación y de error (`lang/es/`) |

Todas las rutas viven bajo el prefijo **`/api/v1`** y responden JSON.

## Módulos

- **Auth** — login, logout, perfil (`/me`) con roles y permisos.
- **Usuarios, Roles, Permisos** — RBAC completo (`Administrador`, `Coordinador`, `Brigadista`).
- **Brigadas + Especialidades** — campañas médicas con cupos por especialidad.
- **Pacientes + Turnos** — registro por cédula (sin QR), registro asistido, cola de espera por especialidad.
- **Médicos** — catálogo y asignación a brigadas.
- **Brigadistas** — asignación de equipo de campo y confirmación de asistencia.
- **Reportes** — indicadores de atención por brigada, especialidad y sector.
- **Comunidades** — catálogo de sectores/barrios.
- **Noticias** — comunicados de campañas (borrador/publicado).
- **Solicitudes de Brigada** — endpoint público para que la comunidad pida una jornada.
- **Preferencias** — configuración de notificaciones por usuario.

## Requisitos

- PHP **8.1+** con extensiones `pdo_mysql`, `mbstring`, `openssl`, `bcmath`, `tokenizer`, `xml`.
- Composer 2.x
- MySQL 5.7+ / MariaDB equivalente
- (Opcional) [Laragon](https://laragon.org/) si desarrollas en Windows

## Instalación local

```bash
git clone <url-del-repositorio>
cd brigadasMedicasec

composer install

cp .env.example .env
php artisan key:generate
```

Edita `.env` con tus credenciales de MySQL:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=brigadasalud
DB_USERNAME=root
DB_PASSWORD=
```

Migra y siembra datos de ejemplo (roles, permisos y un set de datos demo variado en cada tabla):

```bash
php artisan migrate --seed
php artisan serve
```

La API queda disponible en `http://127.0.0.1:8000/api/v1`.

## Usuarios de prueba

Sembrados por `RolesAndPermissionsSeeder` / `DemoDataSeeder` (contraseña `password123` para todos):

| Rol | Email |
|---|---|
| Administrador | `administrador@brigadasalud.test` |
| Coordinador | `coordinador@brigadasalud.test` |
| Brigadista | `brigadista1@brigadasalud.test` |

> ⚠️ Estas cuentas y el `DemoDataSeeder` son **solo para desarrollo**. Ver [`BrigadaSalud_Guia_Despliegue.md`](./BrigadaSalud_Guia_Despliegue.md) para cómo sembrar de forma segura en producción.

## Documentación

| Documento | Contenido |
|---|---|
| [`BrigadaSalud_Contexto_Proyecto.md`](./BrigadaSalud_Contexto_Proyecto.md) | Estado de cada módulo, decisiones de producto, convenciones de código y bugs ya resueltos |
| [`BrigadaSalud_Documentacion_API.html`](./BrigadaSalud_Documentacion_API.html) | Referencia interactiva: esquema entidad-relación, matriz de roles/permisos y los 59 endpoints con ejemplos (ábrelo en el navegador) |
| [`BrigadaSalud_Guia_Despliegue.md`](./BrigadaSalud_Guia_Despliegue.md) | Paso a paso para desplegar a un servidor de producción |

## Testing

Colección de Postman incluida: [`BrigadaSalud_API.postman_collection.json`](./BrigadaSalud_API.postman_collection.json). Importarla, correr **Login** primero (autocompleta el token) y explorar por carpeta — incluye una carpeta **"Casos de error"** con peticiones que deben fallar a propósito, para verificar los mensajes claros de la API.

## Despliegue a producción

Ver la guía completa en [`BrigadaSalud_Guia_Despliegue.md`](./BrigadaSalud_Guia_Despliegue.md): configuración de `.env`, Nginx/Apache, HTTPS, sembrado seguro (sin datos demo) y checklist de seguridad.

## Estructura de carpetas relevante

```
app/Http/Controllers/Api/   Controladores REST (uno por recurso)
app/Http/Requests/          Validación (Store*/Update*Request)
app/Http/Resources/         Formato de respuesta JSON
app/Models/                 Eloquent
app/Exceptions/Handler.php  Mensajes de error uniformes en español
database/migrations/        Esquema de base de datos
database/seeders/           RolesAndPermissionsSeeder + DemoDataSeeder
lang/es/validation.php      Traducción de mensajes de validación
routes/api.php              Todas las rutas de la API
```

## Equipo
Paralelo:  3-C

Grupo 1 — Desarrollo de Software, ISTG:

- Cheffir Levintong Alvarado Chancay
- Jenniffer Yajaira Corozo Chávez
- Blanca Estela Marcillo Arteaga
- Gregorio Enrique Osorio Andrade
- César Orlando Tenorio Merchán 
