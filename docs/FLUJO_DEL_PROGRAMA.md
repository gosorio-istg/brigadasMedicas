# Flujo del programa BrigadaSalud

Este documento explica cómo interactúan los cuatro roles del sistema —**Coordinador**,
**Brigadista**, **Médico** y **Ciudadano**— a través del backend (`brigadasMedicasec`,
Laravel + panel web Blade) y la app móvil (`BrigadaSalud`, Jetpack Compose). El rol
**Administrador** tiene todos los permisos de Coordinador más la gestión de usuarios,
roles y configuración del sistema (incluida la herramienta de "resetear a datos demo"),
así que no se describe aparte salvo que la diferencia sea relevante.

## 1. Resumen de roles y qué puede hacer cada uno

| Rol | Dónde trabaja | Permisos clave (Spatie) | Puede... |
|---|---|---|---|
| **Administrador** | Panel web | Todos, incluido `configuracion.gestionar` | Todo lo de Coordinador + gestionar usuarios/roles/permisos y datos demo |
| **Coordinador** | Panel web (y app, si inicia sesión ahí) | Todos los operativos excepto `configuracion.gestionar` | Crear/editar campañas, asignar brigadistas y médicos, revisar solicitudes, ver reportes |
| **Brigadista** | App móvil (rol operativo de campo) | `brigadas.gestionar`, `pacientes.gestionar`, `turnos.gestionar` | Ver/editar su(s) campaña(s), registrar pacientes y turnos, gestionar la fila de atención en campo |
| **Médico** | App móvil | `pacientes.gestionar`, `turnos.gestionar` | Ver su propia especialidad y las campañas donde está asignado, alternar su disponibilidad, atender turnos de su especialidad, ver/editar historial clínico |
| **Ciudadano** | App móvil (registro público / Firebase) | Ninguno (autoservicio) | Ver campañas y noticias públicas, solicitar una brigada para su sector, confirmar asistencia, ver/editar su propio perfil |

La ruta de inicio ("home") a la que llega cada usuario tras iniciar sesión se decide en
`AuthorizationPolicy.destinationFor()` (móvil), en función del rol y de si tiene permisos
de gestión: Administrador/Coordinador → módulos de gestión; Brigadista/Médico → su cola de
turnos (`HomeMedicoScreen`/`HomeScreen` con `StaffBottomBar`); Ciudadano → pantalla de
campañas públicas y noticias.

## 2. Ciclo de vida de una campaña (brigada)

```
programada  --(Coordinador: Iniciar)-->  en_curso  --(Coordinador: Finalizar)-->  finalizada
```

1. **Coordinador crea la campaña** (`POST /brigadas`): nombre, fecha, ubicación y las
   especialidades que se van a ofrecer, cada una con un cupo (`brigada_especialidad.cupos`).
2. **Coordinador asigna brigadistas** (`POST /brigadas/{id}/brigadistas`) y **médicos**
   (`POST /brigadas/{id}/medicos`). Desde este ciclo, el backend **valida que la
   especialidad de cada médico esté entre las que la campaña ofrece**
   (`BrigadaController::asignarMedicos`); si no, devuelve 422 con el nombre del médico
   incompatible. El modal "Asignar médicos" del panel web y la pantalla de asignación de
   equipo en la app ya filtran/agrupan la lista de médicos por especialidad para evitar el
   error antes de enviarlo.
3. **Ciudadanos confirman asistencia** (`PUT /brigadas/{id}/mi-asistencia`: asistiré / tal
   vez / no asistiré) mientras la campaña está `programada`.
4. **Coordinador inicia la campaña** (`PUT /brigadas/{id}` con `estado: en_curso`). El
   backend **bloquea el inicio si falta un médico para alguna especialidad ofrecida**
   (`BrigadaController::update`), devolviendo 422 con la lista de especialidades sin
   cobertura. Esto evita abrir una cola de pacientes que nadie va a poder atender.
5. **Con la campaña `en_curso`**, Brigadista y Médico ven la campaña activa y pueden
   registrar turnos y atenciones (ver secciones 4 y 5).
6. **Coordinador finaliza la campaña** (`estado: finalizada`). El backend **bloquea el
   cierre si quedan turnos `pendiente` o `en_espera` sin resolver**, para que ningún
   paciente quede en la cola sin una atención, cancelación o "no asistió" registrada.

### Quién puede ver el detalle de una campaña

Antes, `GET /brigadas/{id}` y `GET /brigadas/{id}/medicos` exigían el permiso
`brigadas.gestionar`/`medicos.gestionar` (solo Coordinador/Administrador), así que un
Médico o Brigadista asignado a su propia campaña recibía **403** al intentar abrir el
detalle desde la app. Esto ya se corrigió: ambas rutas ahora solo exigen sesión
autenticada, y `BrigadaController::ensureCanView()` permite el acceso si el usuario tiene
el permiso de gestión **o** está realmente asignado a esa brigada (como médico o como
brigadista). Los controles de "Iniciar campaña" / "Finalizar campaña" / "Editar campaña"
en la app siguen ocultos para quien no tenga `brigadas.gestionar`, ya que esas acciones
siguen siendo exclusivas de Coordinador/Administrador.

## 3. El turno (`Turno`): la unidad que conecta a los cuatro roles

Un turno pertenece a una `brigada`, una `especialidad` y un `paciente`. Su estado avanza:

```
pendiente / en_espera  →  atendido   (o)   cancelado / no_asistio
```

- **Quién lo crea:** Coordinador o Brigadista desde el panel web o la app (pantalla
  "Crear turno"), o el propio Ciudadano si el flujo de autoservicio está habilitado desde
  la campaña pública. Al crear el turno se **busca primero si el paciente ya existe por
  cédula** (autocompletado con debounce, igual en web y en app) para no duplicar
  pacientes; si existe, se reutiliza su `paciente_id`, si no, se crea uno nuevo en la
  misma petición.
- **Quién lo atiende:** solo un Médico. El backend valida en tres capas que la
  especialidad del médico coincide con la especialidad del turno:
  1. Al asignar médicos a la campaña (sección 2, paso 2).
  2. Al asignar un `medico_id` a un turno específico (`TurnoController::update`).
  3. Al registrar la atención clínica (`ClinicalRecordController::storeAtencion`): si el
     usuario autenticado es un médico y su especialidad no coincide con la del turno, la
     petición se rechaza con 422.
- **Filtro `mis_brigadas`:** cuando un Médico pide `GET /turnos?mis_brigadas=true`, el
  backend solo devuelve turnos de las brigadas donde ese médico está asignado (no la cola
  completa del sistema). La app lo activa automáticamente en `HomeMedicoScreen` cuando el
  usuario tiene el rol Médico.

## 4. Flujo del Médico en la app

`HomeMedicoScreen` es el punto de partida del médico. Muestra:

- **Cabecera con su especialidad**: `GET /me/medico` (nuevo endpoint) devuelve el perfil
  del médico vinculado a la cuenta autenticada, con su especialidad y **todas** sus
  campañas asignadas (id, nombre, estado, fecha, ubicación) — antes solo se sabía el
  nombre/rol genérico y la "campaña actual" se adivinaba tomando el primer turno que
  llegara a la cola, lo cual fallaba si todavía no había ningún turno.
- **"Mis campañas asignadas"**: lista completa (no solo una) de las campañas donde el
  coordinador lo asignó, con una insignia de estado (`programada` / `en_curso` /
  `finalizada`) para que sepa de un vistazo cuáles están activas ahora mismo. Cada tarjeta
  abre el detalle de esa campaña.
- **Interruptor de disponibilidad** (`disponible` en la tabla `medicos`): mientras está en
  "descanso", no ve la cola de turnos (evita que le sigan asignando pacientes).
- **Cola "Por atender" / "Atendidos"**: turnos de sus brigadas y su especialidad
  (`mis_brigadas=true`). Al tocar "Atender" pasa a **Signos vitales** y luego a
  **Atención médica**, donde registra:
  - `diagnostico`, `receta`, `observaciones` (texto libre).
  - `motivo_consulta` (enfermedad común, control, chequeo preventivo, urgencia,
    seguimiento, otro), `tipo_atencion` (primera vez / seguimiento) y
    `requiere_referencia` (booleano) — los tres campos de tabulación agregados para poder
    generar reportes por motivo/tipo/derivación en vez de solo texto libre.
- **Actualización automática al volver a la pantalla**: antes existía un listener de
  Firestore muerto (la app dejó de usar Firebase hace tiempo, migró a Laravel/MySQL vía
  Retrofit) que nunca disparaba, así que un turno creado desde la web **no aparecía hasta
  reiniciar la app**. Se reemplazó por un observador de ciclo de vida que refresca los
  datos cada vez que la pantalla vuelve a primer plano (`ON_RESUME`) — cubre el caso real:
  el médico sale a otra pantalla (o cambia de app) y vuelve, o alguien crea/edita un turno
  en la web mientras él tenía la app en segundo plano.
- **Historial de paciente**: desde la ficha de un paciente (`PacienteDetailScreen`,
  accesible también desde el módulo de Pacientes) puede ver todas sus atenciones previas
  y editar una atención ya guardada si hubo un error de tabulación o diagnóstico.

## 5. Flujo del Brigadista en la app

El Brigadista comparte la misma cola de turnos que el Médico (`turnos.gestionar`), pero
sin el filtro de especialidad ni el interruptor de disponibilidad: su trabajo es
logístico, no clínico.

- Ve el detalle de su(s) campaña(s) asignada(s) (ahora accesible sin 403, ver sección 2).
- Registra la asistencia del equipo de brigadistas (`asistio: true/false` por persona) y
  gestiona altas/bajas del equipo desde el módulo de Turnos/Equipos.
- Crea turnos para los pacientes que llegan en el lugar (mismo flujo de
  búsqueda-por-cédula que el Coordinador en el panel web).
- No puede iniciar ni finalizar la campaña, ni editarla (esos botones están ocultos en la
  app si no tiene `brigadas.gestionar`), ni asignar médicos.

## 6. Flujo del Ciudadano

El Ciudadano es el único rol que puede crear su cuenta él mismo, sin que un
administrador lo dé de alta:

1. **Registro/login**: por Firebase (`POST /auth/firebase`, intercambia el id-token de
   Firebase por un token Sanctum; crea el usuario con rol `Ciudadano` si es la primera
   vez) o por credenciales normales si ya tiene cuenta.
2. **Ve campañas y noticias públicas** (`GET /public/brigadas`, `GET /public/noticias`) sin
   necesidad de sesión.
3. **Solicita una brigada para su sector** (`POST /solicitudes-brigada`) — esto no requiere
   cuenta; el Coordinador revisa y aprueba/rechaza estas solicitudes desde el panel web
   (mismo permiso `brigadas.gestionar` que la gestión de campañas).
4. **Con sesión iniciada**, confirma su asistencia a una campaña programada
   (`PUT /brigadas/{id}/mi-asistencia`) y puede editar su propio perfil (`PUT /me`,
   `PUT /preferencias`) — no puede autoasignarse roles ni activarse/desactivarse a sí
   mismo, el `UpdateMeRequest` lo bloquea explícitamente.
5. En campo, puede terminar convertido en `paciente` cuando un Brigadista o Médico le crea
   un turno (buscándolo/creándolo por cédula); a partir de ahí su historial clínico queda
   ligado a ese `paciente_id`, no a su cuenta de usuario (son entidades separadas:
   `User` = quien inicia sesión, `Paciente` = a quien se atiende).

## 7. Sincronización sin conexión (contexto de campo)

Como Brigadista y Médico suelen trabajar con conectividad inestable, las acciones de
creación/actualización de registros (turnos, atenciones, signos vitales, etc.) que fallan
por falta de red se guardan en una **cola persistente local** (`PendingActionsStore`,
respaldada en un archivo JSON) y se reintentan automáticamente con un `WorkManager`
(`SyncWorker`, restringido a `NetworkType.CONNECTED`) apenas vuelve la conexión, sin que el
usuario tenga que repetir la acción manualmente. Un badge en la pantalla de inicio
(`PendingSyncBadge`) indica cuántas acciones siguen pendientes de sincronizar.

## 8. Reglas de integridad que atraviesan todo el flujo

Estas validaciones existen porque en algún momento fue posible violar la regla de negocio
"un médico solo atiende su propia especialidad" desde alguno de los tres puntos de
entrada; ahora las tres capas la exigen de forma independiente:

1. Asignar un médico a una campaña → su especialidad debe estar entre las que la campaña
   ofrece (`BrigadaController::asignarMedicos`).
2. Asignar un médico a un turno puntual → su especialidad debe coincidir con la del turno
   (`TurnoController::update`).
3. Registrar la atención de un turno → si quien la registra es un médico, su especialidad
   debe coincidir con la del turno (`ClinicalRecordController::storeAtencion`).

Y a nivel de campaña: no se puede iniciar sin cobertura médica completa (sección 2, paso
4) ni finalizar con turnos sin resolver (sección 2, paso 6).

## 9. Resumen visual

```
Coordinador                Brigadista                 Médico                  Ciudadano
------------                ----------                 ------                  ---------
Crea campaña                                                                   Ve campañas
Asigna brigadistas   →      Confirma equipo                                    públicas
Asigna médicos        (valida especialidad)                                    Solicita
Inicia campaña                                                                 brigada
(valida cobertura)          Registra turno    ←──────────────────────────  Se convierte en
                             (busca/crea                                    "paciente" al
                              paciente                                      crear el turno
                              por cédula)
                                                Ve su especialidad y
                                                sus campañas asignadas
                                                Activa disponibilidad
                                                Atiende turno de su
                                                especialidad → registra
                                                signos vitales + atención
                                                (motivo, tipo, referencia,
                                                diagnóstico, receta)
Finaliza campaña                                                               Confirma
(valida turnos                                                                 asistencia
resueltos)
```
