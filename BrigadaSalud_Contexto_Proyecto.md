# Contexto del proyecto — BrigadaSalud API

Pega este archivo como contexto en Claude (VS Code) para continuar el desarrollo del backend Laravel: qué ya existe, qué falta, y cómo se relaciona cada módulo.

## 1. Qué es el proyecto

**BrigadaSalud** es el Proyecto Integrador de Saberes (PIS) de Grupo 1, carrera Desarrollo de Software, Instituto Superior Tecnológico Guayaquil (ISTG), materia Fundamentos de Redes y Telecomunicaciones (docente Ivan Amat), Paralelo 3C, Período 2026-S1.

Sistema (app móvil + plataforma web) para gestionar brigadas médicas comunitarias en barrios urbano-marginales de Guayaquil: registro de pacientes, asignación de turnos por especialidad y gestión de la atención médica, reemplazando procesos manuales en papel.

Roles: **Administrador** (configuración general de la plataforma), **Coordinador** (web, crea y administra campañas médicas: fechas, especialidades, personal, cupos), **Equipo de Brigada / Brigadista** (app móvil, registra pacientes/turnos/atención) y **Paciente** (perfil mencionado en el documento académico; en el backend actual el paciente es solo un registro gestionado por el equipo, **no** tiene login — ver sección 11 "Brechas detectadas").

Decisiones de producto ya tomadas (no reabrir sin que el usuario lo pida):
- Sin QR: registro de pacientes por cédula y nombres, con registro asistido por cualquier miembro del equipo.
- Cada brigada indica especialidad(es) ofrecidas con cupos por especialidad.
- El documento académico no menciona tecnologías específicas, pero el desarrollo real sí usa las de abajo.
- El módulo de notificaciones por **WhatsApp** (mencionado como entregable clave en los documentos académicos) se maneja aparte: el usuario ya tiene infraestructura propia con Meta (varios bots) y lo explicará en detalle más adelante. No implementar nada de WhatsApp sin que el usuario dé esas indicaciones primero.

**Documentos fuente**: además de este archivo, el usuario adjuntó dos capítulos del documento académico (introducción/cuestionario, y descripción del proyecto/objetivos). De ahí salieron los perfiles de usuario (4, no 2), el módulo de solicitud de brigada por parte del ciudadano, y la mención de WhatsApp. Ver sección 11 para el detalle de qué se cubrió y qué quedó explícitamente diferido.

## 2. Stack técnico

- **Backend**: Laravel **10.50.2** (confirmado con `php artisan --version` en el entorno real), estructura **clásica** con `app/Http/Kernel.php` (NO Laravel 11 `bootstrap/app.php`). Importante: la sintaxis de casts por método (`protected function casts(): array`) es de Laravel 11 y NO funciona aquí — usar siempre `protected $casts = [...]`.
- **Auth**: Laravel Sanctum (tokens Bearer).
- **Roles y permisos**: `spatie/laravel-permission`, guard `web` en todo el proyecto.
- **Base de datos**: MySQL.
- **App móvil**: Android (Kotlin + Retrofit), offline-first con Room + sincronización diferida.
- **Web (Coordinador)**: SPA o Blade+Livewire.
- Todas las rutas bajo **`/api/v1`**.

## 3. Convenciones de código

- Patrón por recurso: **Migration → Model → FormRequest (Store/Update) → Resource → Controller** + entrada en `routes/api.php`.
- Controladores siempre devuelven `JsonResource` / `ResourceCollection`, nunca arrays crudos.
- Rutas protegidas: `auth:sanctum` + `permission:recurso.accion` (formato español, ej. `brigadas.gestionar`).
- `FormRequest::authorize()` siempre `return true;` — la autorización real la hace el middleware `permission:`.
- Middleware `permission` se registra en `app/Http/Kernel.php` → `$middlewareAliases` (no en bootstrap/app.php).
- **Mensajes de error siempre claros y en español**: `config('app.locale')` está en `'es'` y existe `lang/es/validation.php`, así que los errores 422 de validación ya salen en español (ej. "El campo cédula es obligatorio."). `app/Exceptions/Handler.php` intercepta también 401 (no autenticado), 403 (sin permiso — `Spatie\Permission\Exceptions\UnauthorizedException`), 404 (ruta o recurso inexistente, con el nombre del modelo y género correcto: "Comunidad no encontrada" vs "Usuario no encontrado") y 405 (método no permitido), siempre devolviendo JSON con `message` claro, nunca la página HTML de error de Laravel. Al agregar un modelo nuevo, sumar su mensaje a `Handler::MENSAJES_NO_ENCONTRADO` (con el género correcto) y sus atributos a `lang/es/validation.php` si el nombre del campo no es obvio.

## 4. Módulos ya construidos (funcionando)

### Auth
`AuthController`: `POST /login`, `POST /logout`, `GET /me`. Token Sanctum, valida `activo`, devuelve `UserResource` con roles y permisos.

### Usuarios, Roles, Permisos
CRUD completo. `User` tiene `activo` (boolean), traits `HasApiTokens`, `HasRoles`. Asignación de `roles: []` a usuarios y `permissions: []` a roles vía sync.

### Rol Administrador
Distinto de `Coordinador` según el documento académico ("Administrador = configuración general de la plataforma" vs "Coordinador = administra campañas"). Implementado como un tercer rol con **todos** los permisos que ya tenía `Coordinador` **más** `configuracion.gestionar` (exclusivo). No se restringió nada de lo que ya tenía `Coordinador` — es un cambio aditivo, no una redistribución de permisos existentes (eso habría sido más riesgoso y no fue lo pedido). Usuario de prueba: `administrador@brigadasalud.test` / `password123`.

### Pacientes + Turnos
CRUD de `Paciente` (sin `destroy`, se preserva historial médico) y de `Turno` (`store`, `index` filtrable, `update` de estado). Ver detalle completo de reglas de negocio en la sección 6.1 (ya implementada). Decisiones tomadas al construir el módulo: `fecha_nacimiento` (no `edad`) y regla de cupos "solo advertir" (no bloquea la creación del turno).

### Brigadas + Especialidades
- `Brigada`: `nombre`, `descripcion`, `fecha`, `ubicacion`, `estado` (enum `programada|en_curso|finalizada|cancelada`), `coordinador_id` (FK `users`, se asigna con `Auth::id()` al crear).
- `Especialidad`: catálogo (`nombre`, `activa`). **Ojo**: el modelo declara `protected $table = 'especialidades'` explícito (ver sección 10, bug #3).
- Pivot `brigada_especialidad` con columna `cupos` (cupos por especialidad en cada brigada).

### Médicos
CRUD de `Medico` (`nombres`, `credencial_cmp` único, `especialidad_id`, `telefono`, `disponible`). Relación many-to-many con `Brigada` vía pivot `brigada_medico` (`POST/GET /brigadas/{brigada}/medicos`, sync). `Turno.medico_id` ya tiene FK real a `medicos` (se agregó en una migración aparte porque la tabla `medicos` no existía cuando se creó `turnos`). Se puede asignar médico a un turno vía `PUT /turnos/{turno}` con `medico_id`.

### Brigadistas
No hay modelo `Brigadista` nuevo: es un `User` con rol `Brigadista`, asignado a una brigada vía pivot `brigada_brigadista` (`rol_equipo` enum `registro|apoyo_logistico|atencion_medica|coordinacion`, `asistio` boolean nullable — null mientras la asistencia no se confirma). Relación `Brigada::brigadistas()` / `User::brigadas()` (bidireccional). Endpoints anidados bajo `/brigadas/{brigada}/brigadistas`: `GET` (listar equipo), `POST` (asignar/actualizar rol vía `syncWithoutDetaching`, no quita a los demás), `PUT /{user}` (marcar asistencia vía `updateExistingPivot`), `DELETE /{user}` (quitar del equipo vía `detach`).

### Reportes
Sin tablas nuevas — `ReporteController` (solo lectura) agrega sobre `Turno`/`Especialidad`/`Paciente`. `GET /reportes/resumen?brigada_id=` (opcional el filtro) devuelve `total_turnos`, `total_atendidos`, `tiempo_promedio_espera_minutos` (diff entre `hora_registro` y `hora_atencion`, solo turnos `atendido`) y `desglose_por_especialidad`. `GET /reportes/por-sector` agrupa por `Paciente.sector` (texto libre) en vez de por `Comunidad` — esa tabla todavía no existe (ver sección 6.5); cuando se migre `sector` a `comunidad_id`, este reporte debe actualizarse para agrupar por esa FK. No se implementó `GET /reportes/exportar` (CSV/Excel) por ser explícitamente opcional en el documento original; agregarlo si se pide.

### Comunidades
Catálogo simple e independiente (`nombre` único, `sector`, `referencia_ubicacion` nullable), CRUD completo. **Decisión tomada al construir el módulo** (el documento original la dejaba pendiente): NO se migró `brigadas.ubicacion` ni `pacientes.sector` a `comunidad_id` — siguen siendo texto libre, y `Comunidad` no tiene relación `hasMany(Brigada)` todavía. Es un catálogo de referencia visual para el frontend, sin FK desde otras tablas. Si más adelante se pide la migración a FK real, hay que actualizar `Brigada`/`Paciente`, sus migrations, `ReporteController::porSector()` (hoy agrupa por `Paciente.sector`) y el `DemoDataSeeder`. **Ojo**: igual que `Especialidad`, el modelo declara `protected $table = 'comunidades'` explícito (mismo bug de pluralización, ver sección 10).

### Noticias
CRUD completo de `Noticia` (`titulo`, `resumen`, `contenido`, `imagen_url` nullable, `fecha_publicacion`, `autor_id`, `publicada`). `autor_id` se asigna con `Auth::id()` al crear (mismo patrón que `coordinador_id` en `Brigada`), no se recibe del cliente. `publicada` boolean permite guardar borradores. Sin bug de pluralización (`Str::plural('Noticia')` = `noticias`, coincide con la tabla).

### Solicitudes de Brigada
Cubre la brecha detectada en el cuestionario del documento académico: la comunidad puede pedir que se organice una brigada, sin necesidad de tener cuenta en el sistema. `POST /solicitudes-brigada` es **público** (fuera del grupo `auth:sanctum`, protegido solo por el throttle `api` por defecto de Laravel — 60 req/min por IP). Campos: `nombre_solicitante`, `telefono_contacto`, `sector`, `especialidades_solicitadas` (texto libre, ej. "Medicina General, Odontología" — responde a la necesidad de "conocer las especialidades más solicitadas antes de organizar una brigada" de la encuesta a organizadores), `motivo`. El Coordinador gestiona (`GET`/`PUT /solicitudes-brigada/{id}`) bajo el mismo permiso `brigadas.gestionar` (no se creó un permiso nuevo: gestionar solicitudes es parte de "administrar campañas"). `estado`: `pendiente|en_revision|aprobada|rechazada`; al aprobar se puede vincular una `brigada_id` ya creada y queda registrado quién la gestionó (`gestionado_por`).

### Configuración / Preferencias de notificación
Cierra la sección 6.7: gestión de especialidades y datos de cuenta ya existían; lo único nuevo es `GET/PUT /preferencias` (tabla `user_preferencias`, modelo `Preferencia`), autoservicio del usuario autenticado (no requiere permiso especial, cada quien administra las suyas). `PreferenciaController::preferenciaDelUsuario()` usa `firstOrCreate` con los valores por defecto explícitos — **ojo con el bug relacionado en sección 10 (bug #4)**.

### Permisos ya sembrados
```
usuarios.ver, usuarios.crear, usuarios.editar, usuarios.eliminar,
roles.gestionar, permisos.gestionar,
brigadas.gestionar, pacientes.gestionar, turnos.gestionar, reportes.ver,
medicos.gestionar, brigadistas.gestionar, comunidades.gestionar, noticias.gestionar,
configuracion.gestionar
```
Roles: `Administrador` (todos, incluido `configuracion.gestionar`), `Coordinador` (todos excepto `configuracion.gestionar`) y `Brigadista` (`brigadas.gestionar`, `pacientes.gestionar`, `turnos.gestionar` — no incluye los permisos de los módulos posteriores, sin reabrir esa decisión salvo que se pida). Usuarios de prueba: `administrador@brigadasalud.test`, `coordinador@brigadasalud.test`, ambos `password123`. Gestionar Solicitudes de Brigada reutiliza `brigadas.gestionar` (no es un permiso aparte).

### Datos de demostración (`DemoDataSeeder`)
`php artisan db:seed` (o `migrate:fresh --seed`) corre `RolesAndPermissionsSeeder` y luego `DemoDataSeeder`, que llena TODAS las tablas con datos variados y coherentes entre sí (usa `firstOrCreate`/`syncWithoutDetaching`, así que correrlo varias veces no duplica filas): 7 especialidades (incluye Oftalmología), 4 usuarios adicionales (1 coordinador, 3 brigadistas, password `password123`) + el usuario Administrador sembrado en `RolesAndPermissionsSeeder`, 9 médicos (con disponibilidad mixta), 5 brigadas (una de cada `estado`), asignaciones de brigadistas por brigada (roles de equipo variados y `asistio` en `true`/`false`/`null`), 10 pacientes (edades y sectores variados), 7 turnos que cubren los 4 estados posibles, 7 comunidades (los 5 sectores ya usados + 2 adicionales), 4 noticias (3 publicadas + 1 borrador) y 4 solicitudes de brigada (una de cada `estado`, incluida una aprobada y vinculada a una brigada real). Al construir un módulo nuevo, extender este seeder (no crear uno paralelo) para mantener los datos de ejemplo relacionados entre sí.

## 5. Diseño de interfaz (referencia)

Sidebar web: **Dashboard, Brigadas, Pacientes, Médicos, Brigadistas, Comunidades, Reportes, Noticias, Configuración**. Detalle completo de pantallas en `BrigadaSalud_Especificacion_UI_Web.md`.

---

## 6. Módulos por construir — detalle de backend

### 6.1 Pacientes + Turnos — CONSTRUIDO Y PROBADO (integración real en Laragon)

**Por qué van juntos**: un Turno es la relación entre un Paciente, una Brigada y una Especialidad — es la entidad que resuelve "quién espera qué atención, en qué brigada".

**Tabla `pacientes`**
- `cedula` (string, unique) — identificador principal, sin QR.
- `nombres`, `apellidos`.
- `fecha_nacimiento` o `edad` (definir uno).
- `sexo` (enum).
- `telefono` (nullable — puede no tener).
- `sector` (string libre por ahora; cuando exista el módulo Comunidades, migrar a `comunidad_id` FK).

**Tabla `turnos`**
- `brigada_id` (FK `brigadas`).
- `paciente_id` (FK `pacientes`).
- `especialidad_id` (FK `especialidades`).
- `medico_id` (FK `medicos`, nullable — se asigna cuando exista el módulo Médicos).
- `numero_turno` (string, ej. `MG-001`, prefijo por especialidad + consecutivo por brigada+especialidad).
- `estado` (enum `pendiente|en_espera|atendido|cancelado`).
- `registrado_por` (FK `users` — qué brigadista lo registró; permite registro asistido).
- `hora_registro`, `hora_atencion` (nullable, timestamps).

**Relaciones**
- `Paciente` → `hasMany(Turno)` (historial de atenciones a través de brigadas).
- `Brigada` → `hasMany(Turno)`.
- `Especialidad` → `hasMany(Turno)`.
- `Turno` → `belongsTo(Paciente, Brigada, Especialidad, Medico, User registrador)`.

**Endpoints**
- `GET /pacientes?buscar=cedula_o_nombre` — buscar antes de crear (evitar duplicados).
- `POST /pacientes` — crear si no existe.
- `GET /pacientes/{id}` — detalle + historial de turnos (`with('turnos.brigada')`).
- `POST /turnos` — registrar turno: recibe `brigada_id`, `especialidad_id`, y `paciente_id` o los datos para crear el paciente en la misma petición (flujo "registro asistido" de un tirón).
- `GET /turnos?brigada_id=&especialidad_id=&estado=` — la cola de espera, filtrable.
- `PUT /turnos/{id}` — cambiar estado (marcar atendido/cancelado), asignar médico.

**Reglas de negocio a decidir/implementar**
- Generar `numero_turno` de forma atómica (consecutivo por `brigada_id + especialidad_id`).
- Validar contra `cupos` de `brigada_especialidad` (¿bloquear al llegar al tope, o solo advertir?).

**Permisos**: ya sembrados (`pacientes.gestionar`, `turnos.gestionar`).

### 6.2 Médicos — CONSTRUIDO Y PROBADO (integración real en Laragon)

**Tabla `medicos`**
- `nombres`, `credencial_cmp` (string, unique).
- `especialidad_id` (FK `especialidades`).
- `telefono`.
- `disponible` (boolean).

**Relaciones**
- `Medico` → `belongsTo(Especialidad)`.
- `Medico` ↔ `Brigada`: many-to-many vía pivot `brigada_medico` (qué médicos asisten a cada brigada).
- `Turno.medico_id` → `belongsTo(Medico)` (quién atendió al paciente).

**Endpoints**
- CRUD `/medicos`.
- `POST /brigadas/{brigada}/medicos` — asignar médicos a una brigada (sync).
- `GET /brigadas/{brigada}/medicos`.

**Permiso nuevo a sembrar**: `medicos.gestionar`.

### 6.3 Brigadistas — CONSTRUIDO Y PROBADO (integración real en Laragon)

No es una tabla de personas nueva — un Brigadista **es un `User` con rol `Brigadista`** (ya existe). Lo que faltaba era la relación de asignación a una brigada específica, ya implementada (ver sección 4).

**Tabla pivot `brigada_brigadista`** (implementada)
- `brigada_id` (FK), `user_id` (FK `users`).
- `rol_equipo` — se implementó como `enum('registro', 'apoyo_logistico', 'atencion_medica', 'coordinacion')` (el doc original dejaba el set de valores abierto; esta es la lista elegida al construir el módulo, no reabrir sin pedirlo).
- `asistio` (boolean, nullable hasta que se confirme).

**Endpoints** (implementados)
- `POST /brigadas/{brigada}/brigadistas` — asignar usuario + rol_equipo.
- `GET /brigadas/{brigada}/brigadistas`.
- `PUT /brigadas/{brigada}/brigadistas/{user}` — marcar asistencia.
- `DELETE /brigadas/{brigada}/brigadistas/{user}`.

**Permiso sembrado**: `brigadistas.gestionar`.

### 6.4 Reportes — CONSTRUIDO Y PROBADO (integración real en Laragon)

Sin tablas nuevas — son consultas agregadas sobre `Turno`, `Especialidad`, `Paciente`. Solo lectura.

**Endpoints** (implementados)
- `GET /reportes/resumen?brigada_id=` — total atendidos, tiempo promedio de espera, desglose por especialidad. ✅
- `GET /reportes/por-sector` — agrupa por `Paciente.sector` (no por Comunidad, porque esa tabla no existe todavía; ver nota en sección 4). ✅
- `GET /reportes/exportar` (CSV/Excel) — **no implementado**, era explícitamente opcional en este documento.

**Permiso**: ya sembrado (`reportes.ver`).

### 6.5 Comunidades — CONSTRUIDO Y PROBADO (integración real en Laragon)

**Tabla `comunidades`** (implementada)
- `nombre`, `sector`, `referencia_ubicacion`.

**Relaciones**
- `Comunidad` → `hasMany(Brigada)` — **decisión tomada**: NO se implementó todavía. Se preguntó explícitamente al usuario y eligió dejar Comunidades como catálogo independiente por ahora (cambio mínimo, sin tocar módulos ya probados). Sigue pendiente para el futuro si se pide: migrar `brigadas.ubicacion` / `pacientes.sector` (string libre) a `comunidad_id` (FK).

**Endpoints** (implementados): CRUD simple `/comunidades`.

**Permiso sembrado**: `comunidades.gestionar`.

### 6.6 Noticias — CONSTRUIDO Y PROBADO (integración real en Laragon)

**Tabla `noticias`** (implementada)
- `titulo`, `resumen`, `contenido`, `imagen_url` (nullable), `fecha_publicacion`, `autor_id` (FK `users`), `publicada` (boolean).

**Endpoints** (implementados): CRUD `/noticias`.

**Permiso sembrado**: `noticias.gestionar`.

### 6.7 Configuración — CONSTRUIDO Y PROBADO (integración real en Laragon)

- Gestión de especialidades → ya existía (`EspecialidadController`).
- Datos de cuenta → ya existía (`GET/PUT` sobre `/me` y `/users/{id}`).
- Preferencias de notificación → **implementado**: tabla `user_preferencias` (`notificaciones_email`, `notificaciones_push`), `GET/PUT /preferencias`.

**Permiso sembrado**: `configuracion.gestionar` (exclusivo de `Administrador`).

Con Pacientes+Turnos, Médicos, Brigadistas, Reportes, Comunidades, Noticias y Configuración se completaron los 7 módulos originales de la sección 6. Lo que sigue (sección 11) son brechas nuevas detectadas al comparar contra los documentos académicos completos, no parte de este roadmap original.

---

## 11. Brechas detectadas vs. los documentos académicos completos (Capítulo 1 + cuestionario)

El usuario adjuntó el documento académico completo (antes solo se había trabajado con un resumen técnico). Al compararlo contra el backend construido, aparecieron 4 brechas de alcance. Decisión del usuario (2026-07-25): cubrir primero las dos "menores" (ya hecho: rol Administrador y especialidad Oftalmología), luego Solicitudes de Brigada (ya hecho), dejando explícitamente **para después** — con explicación previa del usuario — el módulo de WhatsApp. Las de RSVP y listado público quedan sin decisión tomada todavía.

**Cubiertas en esta sesión:**
1. Rol `Administrador` separado de `Coordinador` (sección 4).
2. Especialidad `Oftalmología` agregada al catálogo y al seeder.
3. Módulo Solicitudes de Brigada (sección 4).

**Explícitamente diferido — NO implementar sin que el usuario lo pida primero:**
4. **Notificaciones por WhatsApp**: el documento lo marca como entregable central (difusión de campañas, confirmación de asistencia, recordatorio de turno próximo). El usuario ya tiene infraestructura propia con la API de Meta (varios bots existentes) y va a explicar cómo integrarlo más adelante. No asumir proveedor ni empezar a codear esto todavía.

**Detectadas pero sin decisión tomada aún (preguntar antes de construir):**
5. **Auto-registro / pre-registro de pacientes desde la app**: el documento nuevo dice que el paciente "puede registrarse previamente desde la aplicación móvil o ser registrado por el personal" — esto contradice/amplía la decisión original de "todo registro es asistido" (sección 1). Implica que `Paciente` necesitaría login propio o un endpoint público de pre-registro. No implementado.
6. **Confirmación de asistencia previa del ciudadano** ("Asistiré" / "Tal vez" / "No asistiré" a una brigada, antes de que ocurra) — pedido explícito en ambas encuestas. Es distinto del `Turno` (que se crea el día de la brigada). No implementado.
7. **Listado público de brigadas**: hoy `GET /brigadas` exige `permission:brigadas.gestionar`. Las encuestas sugieren que el ciudadano/paciente debería poder consultar brigadas disponibles sin ser Coordinador/Brigadista. No implementado.

---

## 7. Permisos pendientes de agregar al seeder

_(Sección obsoleta: todos los permisos que listaba ya están sembrados. Se deja el número de sección para no romper referencias cruzadas de versiones anteriores de este documento.)_

## 8. Mapa de relaciones (resumen)

```
User (Administrador/Coordinador/Brigadista) ── crea ──> Brigada ── tiene ──> Especialidad (pivot: cupos)
                                    │                       │
                                    ├── tiene ──> Turno <───┘
                                    │              │
                                    │              ├── belongsTo ── Paciente
                                    │              └── belongsTo ── Medico (belongsTo Especialidad)
                                    │
                                    ├── pivot ──> Medico (brigada_medico)
                                    ├── pivot ──> User/Brigadista (brigada_brigadista)
                                    └── belongsTo ── Comunidad (pendiente de migrar desde string)

Noticia ── belongsTo ── User (autor)
SolicitudBrigada ── belongsTo ── Brigada (nullable) y User (gestionado_por, nullable)
User ── hasOne ── Preferencia (user_preferencias)
Reportes ── consulta agregada sobre Turno + Especialidad + Paciente
```

## 9. Testing

Colección de Postman (`BrigadaSalud_API.postman_collection.json`) con Auth / Preferencias / Usuarios / Roles / Permisos / Brigadas (incluye asignación de médicos y de brigadistas) / Médicos / Pacientes / Turnos / Reportes / Comunidades / Noticias / Solicitudes de Brigada / **Casos de error (mensajes claros)**. Variable `token` autocompletada al correr "Login" (y `brigada_id`/`especialidad_id`/`paciente_id`/`turno_id`/`medico_id`/`comunidad_id`/`noticia_id`/`solicitud_id` autocompletados por los requests de "Crear"; `brigadista_user_id` es fija). "Login como Administrador" existe como request separado que **no** sobreescribe `{{token}}` (para no romper el resto de la colección, que asume permisos de Coordinador). La carpeta "Casos de error" tiene requests que **deben fallar** a propósito (401 sin token/token inválido, 422 credenciales incorrectas y validación, 404 recurso/ruta inexistente) para verificar que los mensajes de error sean claros.

## 10. Limitación conocida del entorno donde se generó este código (histórica) y bugs reales ya corregidos

El código original se generó sin poder levantar Laravel real (sin acceso a Packagist/GitHub en ese entorno). Al construir el módulo Pacientes+Turnos en el entorno real del usuario (Laragon, PHP 8.1, Laravel 10.50.2, MySQL) se probó por primera vez de punta a punta y aparecieron 3 bugs reales que ya quedaron corregidos — **no reintroducirlos en módulos nuevos**:

1. **`protected function casts(): array` no existe en Laravel 10** (esa sintaxis llegó en Laravel 11). En este proyecto (`composer.json` fija `laravel/framework: ^10.10`) los casts se declaran con la propiedad clásica `protected $casts = [...]`. Ya corregido en `User`, `Brigada`, `Especialidad`, `Paciente`, `Turno`. Cualquier modelo nuevo con fechas/booleanos/etc. debe usar `protected $casts`.
2. **FormRequests mal ubicados**: `StoreBrigadaRequest`, `UpdateBrigadaRequest` y `StoreEspecialidadRequest` estaban físicamente guardados en `app/Http/Resources/` aunque declaraban `namespace App\Http\Requests`. Con autoload PSR-4 la ruta del archivo debe coincidir con el namespace, así que nunca se pudieron cargar (error `ReflectionException: Class ... does not exist`). Ya movidos a `app/Http/Requests/`. Verificar siempre que el archivo físico esté en la carpeta que corresponde a su namespace.
3. **`Especialidad` sin `protected $table` explícito**: el pluralizador de Eloquent (en inglés) convierte `Especialidad` en `especialidads`, pero la migración crea la tabla `especialidades`. Ya corregido agregando `protected $table = 'especialidades';`. Cualquier modelo cuyo plural en español no siga la regla inglesa simple de "+s" (ej. palabras terminadas en "-dad", "-ión", etc.) necesita declarar `$table` explícitamente. Mismo bug se previno proactivamente en `Comunidad` y `SolicitudBrigada` (tabla `solicitudes_brigada`, plural irregular).
4. **`firstOrCreate([])` sin valores por defecto explícitos deja el modelo en memoria con esos atributos en `null`**, aunque la migración tenga `->default(true)` a nivel de base de datos — porque Eloquent no vuelve a leer la fila recién insertada, solo usa lo que ya tenía en memoria antes del `save()`. Apareció en `PreferenciaController`: la primera vez que un usuario pedía sus preferencias, la respuesta mostraba `notificaciones_email: null` en vez de `true`, aunque en la base de datos sí quedaba `1`. Corregido pasando los defaults como segundo argumento: `firstOrCreate([], ['notificaciones_email' => true, 'notificaciones_push' => true])`. Aplica a cualquier `firstOrCreate`/`firstOrNew` sobre una columna con default a nivel de columna: si el valor por defecto importa para la respuesta inmediata, pasarlo explícito.

Además, `database/seeders/DatabaseSeeder.php` no llamaba a `RolesAndPermissionsSeeder` (por lo tanto `php artisan db:seed` no sembraba nada) — ya corregido con `$this->call(RolesAndPermissionsSeeder::class);`.

Todo lo anterior se verificó con integración real: migraciones corridas contra MySQL, seeder ejecutado, servidor `artisan serve` levantado, y flujo completo probado con `curl` (login, crear especialidad/brigada, registro asistido de paciente+turno, generación de `numero_turno` consecutivo, advertencia de cupo superado, cambio de estado con `hora_atencion` automática).

TODO EL CODIGO DEBE SER COMENTADO EN ESPAÑOL PARA 
QUE CUALQUIER DESARROLLADOR PUEDA ENTENDERLO, AUNQUE NO HAYA PARTICIPADO EN EL PROYECTO.

