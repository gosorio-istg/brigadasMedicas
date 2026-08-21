<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0756b8">
    <meta name="description" content="Presentación académica de BrigadaSalud">
    <title>BrigadaSalud | Presentación académica</title>
    {{-- La versión fuerza la actualización del diseño cuando producción conserva CSS en caché. --}}
    <link rel="stylesheet" href="{{ asset('css/presentation.css') }}?v=20260821-5">
</head>
<body>
    {{-- Los símbolos SVG se reutilizan para mantener una identidad visual consistente y ligera. --}}
    <svg class="symbol-library" aria-hidden="true">
        <symbol id="i-calendar" viewBox="0 0 24 24"><path d="M7 2v3M17 2v3M3.5 9h17M5 4h14a2 2 0 0 1 2 2v13a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z"/></symbol>
        <symbol id="i-users" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></symbol>
        <symbol id="i-file" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6M8 13h8M8 17h5"/></symbol>
        <symbol id="i-chart" viewBox="0 0 24 24"><path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/></symbol>
        <symbol id="i-phone" viewBox="0 0 24 24"><rect x="6" y="2" width="12" height="20" rx="2"/><path d="M10 18h4"/></symbol>
        <symbol id="i-monitor" viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></symbol>
        <symbol id="i-heart" viewBox="0 0 24 24"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 21l8.8-8.6a5.5 5.5 0 0 0 0-7.8Z"/><path d="M3.8 12H8l1.6-3.2L13 16l1.6-4H21"/></symbol>
        <symbol id="i-shield" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/><path d="m9 12 2 2 4-4"/></symbol>
        <symbol id="i-wifi" viewBox="0 0 24 24"><path d="M5 12.6a10 10 0 0 1 14 0M2 9a15 15 0 0 1 20 0M8.5 16.2a5 5 0 0 1 7 0M12 20h.01"/></symbol>
        <symbol id="i-bell" viewBox="0 0 24 24"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></symbol>
        <symbol id="i-database" viewBox="0 0 24 24"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v6c0 1.7 4 3 9 3s9-1.3 9-3V5M3 11v6c0 1.7 4 3 9 3s9-1.3 9-3v-6"/></symbol>
        <symbol id="i-api" viewBox="0 0 24 24"><path d="m8 9-4 3 4 3M16 9l4 3-4 3M14 5l-4 14"/></symbol>
        <symbol id="i-check" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="m8 12 2.6 2.6L16.5 9"/></symbol>
        <symbol id="i-arrow" viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></symbol>
        <symbol id="i-expand" viewBox="0 0 24 24"><path d="M8 3H3v5M16 3h5v5M8 21H3v-5M16 21h5v-5"/></symbol>
    </svg>

    <main class="presentation" id="presentation" aria-live="polite">
        {{-- 01 · Apertura: plantea el propósito sin convertir la diapositiva en un guion. --}}
        <section class="slide slide--cover is-active" data-title="Apertura" aria-label="Diapositiva 1 de 14">
            <div class="cover-grid">
                <div class="cover-copy">
                    <div class="brand brand--light">
                        <img src="{{ asset('images/logo_brigada.jpeg') }}" alt="Logo BrigadaSalud">
                        <span>BrigadaSalud</span>
                    </div>
                    <p class="eyebrow eyebrow--light">Gestión médica comunitaria</p>
                    <h1>Tecnología que organiza,<br><em>conecta y transforma</em><br>la atención en territorio.</h1>
                    <div class="cover-promises" aria-label="Propósitos de la plataforma">
                        <span><svg><use href="#i-calendar"/></svg> Planificar</span>
                        <span><svg><use href="#i-users"/></svg> Coordinar</span>
                        <span><svg><use href="#i-heart"/></svg> Atender</span>
                        <span><svg><use href="#i-chart"/></svg> Medir</span>
                    </div>
                </div>

                <div class="cover-product" aria-label="Acceso a la aplicación Android">
                    <div class="phone-preview phone-preview--cover">
                        <div class="phone-speaker"></div>
                        <img src="{{ asset('images/presentacion/recurso-08.jpg') }}" alt="Pantalla principal de BrigadaSalud móvil">
                    </div>
                    <a class="download-card" id="android-download" href="{{ route('android.apk.download') }}">
                        <span class="qr-frame">
                            <img id="android-qr" src="{{ route('public.configuracion.android-qr') }}" alt="Código QR para descargar BrigadaSalud en Android">
                            <span id="qr-fallback" aria-hidden="true"><svg><use href="#i-phone"/></svg></span>
                        </span>
                        <span>
                            <small>Aplicación Android</small>
                            <strong>Lleva la brigada contigo</strong>
                            <b>Escanear o descargar <svg><use href="#i-arrow"/></svg></b>
                        </span>
                    </a>
                </div>
            </div>
            <span class="slide-number">01</span>
        </section>

        {{-- 02 · Problema: tres síntomas concretos, fáciles de explicar oralmente. --}}
        <section class="slide" data-title="El problema" aria-label="Diapositiva 2 de 14">
            <header class="slide-heading">
                <p class="eyebrow">El problema</p>
                <h2>Cuando la información se dispersa,<br><em>la atención pierde ritmo.</em></h2>
            </header>
            <div class="problem-grid">
                <article class="problem-card">
                    <span class="icon-box"><svg><use href="#i-file"/></svg></span>
                    <strong>Registro manual</strong>
                    <p>Datos duplicados y difíciles de consultar.</p>
                    <span class="problem-index">01</span>
                </article>
                <article class="problem-card problem-card--main">
                    <span class="icon-box"><svg><use href="#i-users"/></svg></span>
                    <strong>Operación desconectada</strong>
                    <p>Pacientes, turnos y equipos sin una vista común.</p>
                    <span class="problem-index">02</span>
                </article>
                <article class="problem-card">
                    <span class="icon-box"><svg><use href="#i-chart"/></svg></span>
                    <strong>Impacto invisible</strong>
                    <p>Resultados sin indicadores oportunos.</p>
                    <span class="problem-index">03</span>
                </article>
            </div>
            <span class="slide-number">02</span>
        </section>

        {{-- 03 · Oportunidad: enfatiza accesibilidad y registro asistido. --}}
        <section class="slide" data-title="La oportunidad" aria-label="Diapositiva 3 de 14">
            <div class="split-layout">
                <div>
                    <p class="eyebrow">La oportunidad</p>
                    <h2>Tecnología útil,<br><em>humana y accesible.</em></h2>
                    <div class="feature-list">
                        <span><svg><use href="#i-check"/></svg><b>Datos centralizados</b></span>
                        <span><svg><use href="#i-check"/></svg><b>Registro asistido</b></span>
                        <span><svg><use href="#i-check"/></svg><b>Procesos claros</b></span>
                        <span><svg><use href="#i-check"/></svg><b>Decisiones con evidencia</b></span>
                    </div>
                </div>
                <div class="device-scene" aria-label="Aplicación móvil y plataforma web conectadas">
                    <div class="laptop">
                        <div class="laptop-screen">
                            <div class="mock-topbar"><i></i><span></span></div>
                            <div class="mock-dashboard">
                                <aside></aside>
                                <div><div class="mock-kpis"><i></i><i></i><i></i></div><div class="mock-chart"></div></div>
                            </div>
                        </div>
                        <div class="laptop-base"></div>
                    </div>
                    <div class="phone-preview phone-preview--scene">
                        <div class="phone-speaker"></div>
                        <img src="{{ asset('images/presentacion/recurso-12.jpg') }}" alt="Campañas en la app BrigadaSalud">
                    </div>
                </div>
            </div>
            <span class="slide-number">03</span>
        </section>

        {{-- 04 · Flujo: presenta el recorrido completo en cinco etapas. --}}
        <section class="slide" data-title="Flujo completo" aria-label="Diapositiva 4 de 14">
            <header class="slide-heading slide-heading--center">
                <p class="eyebrow">Flujo completo</p>
                <h2>De la planificación<br><em>a la evidencia.</em></h2>
            </header>
            <div class="process-flow">
                <article><span><svg><use href="#i-calendar"/></svg></span><b>Planificar</b><small>Campañas y cupos</small></article>
                <i><svg><use href="#i-arrow"/></svg></i>
                <article><span><svg><use href="#i-users"/></svg></span><b>Asignar</b><small>Equipo y funciones</small></article>
                <i><svg><use href="#i-arrow"/></svg></i>
                <article><span><svg><use href="#i-file"/></svg></span><b>Registrar</b><small>Pacientes y turnos</small></article>
                <i><svg><use href="#i-arrow"/></svg></i>
                <article><span><svg><use href="#i-heart"/></svg></span><b>Atender</b><small>Jornada médica</small></article>
                <i><svg><use href="#i-arrow"/></svg></i>
                <article><span><svg><use href="#i-chart"/></svg></span><b>Medir</b><small>Resultados e impacto</small></article>
            </div>
            <p class="closing-line">Un solo recorrido. Información continua.</p>
            <span class="slide-number">04</span>
        </section>

        {{-- 05 · Ecosistema: explica la responsabilidad de cada herramienta. --}}
        <section class="slide" data-title="El ecosistema" aria-label="Diapositiva 5 de 14">
            <header class="slide-heading slide-heading--center">
                <p class="eyebrow">El ecosistema</p>
                <h2>Dos herramientas.<br><em>Un mismo propósito.</em></h2>
            </header>
            <div class="ecosystem">
                <article class="ecosystem-card ecosystem-card--green">
                    <span><svg><use href="#i-phone"/></svg></span>
                    <small>App móvil</small>
                    <strong>Operación en territorio</strong>
                    <p>Registrar · Atender · Sincronizar</p>
                </article>
                <div class="ecosystem-core">
                    <i></i><i></i>
                    <img src="{{ asset('images/logo_brigada.jpeg') }}" alt="BrigadaSalud">
                    <b>Información compartida</b>
                </div>
                <article class="ecosystem-card ecosystem-card--blue">
                    <span><svg><use href="#i-monitor"/></svg></span>
                    <small>Plataforma web</small>
                    <strong>Gestión y análisis</strong>
                    <p>Planificar · Coordinar · Monitorear</p>
                </article>
            </div>
            <span class="slide-number">05</span>
        </section>

        {{-- 06 · App móvil: muestra la herramienta que acompaña al brigadista. --}}
        <section class="slide" data-title="Aplicación móvil" aria-label="Diapositiva 6 de 14">
            <div class="product-layout">
                <div>
                    <p class="eyebrow">Aplicación móvil</p>
                    <h2>La brigada,<br><em>en la mano.</em></h2>
                    <div class="compact-features">
                        <span><svg><use href="#i-users"/></svg> Pacientes</span>
                        <span><svg><use href="#i-file"/></svg> Turnos</span>
                        <span><svg><use href="#i-calendar"/></svg> Campañas</span>
                        <span><svg><use href="#i-heart"/></svg> Atención</span>
                    </div>
                    <p class="signal"><svg><use href="#i-wifi"/></svg> Preparada para trabajar y sincronizar.</p>
                </div>
                <div class="phone-gallery" aria-label="Capturas de la aplicación móvil">
                    <div class="phone-preview phone-preview--back"><img src="{{ asset('images/presentacion/recurso-09.jpg') }}" alt="Lista de pacientes"></div>
                    <div class="phone-preview phone-preview--front"><img src="{{ asset('images/presentacion/recurso-08.jpg') }}" alt="Módulos de la aplicación"></div>
                    <div class="phone-preview phone-preview--back"><img src="{{ asset('images/presentacion/recurso-10.jpg') }}" alt="Lista de turnos"></div>
                </div>
            </div>
            <span class="slide-number">06</span>
        </section>

        {{-- 07 · Atención: resume la trazabilidad del paciente. --}}
        <section class="slide" data-title="Atención del paciente" aria-label="Diapositiva 7 de 14">
            <div class="split-layout split-layout--patient">
                <div>
                    <p class="eyebrow">Atención del paciente</p>
                    <h2>Un proceso simple,<br><em>rápido y humano.</em></h2>
                    <ol class="patient-steps">
                        <li><span>1</span><b>Registrar paciente</b></li>
                        <li><span>2</span><b>Asignar especialidad</b></li>
                        <li><span>3</span><b>Generar turno</b></li>
                        <li><span>4</span><b>Registrar atención</b></li>
                        <li><span>5</span><b>Conservar historial</b></li>
                    </ol>
                </div>
                <div class="patient-visual">
                    <div class="phone-preview phone-preview--patient"><img src="{{ asset('images/presentacion/recurso-14.jpg') }}" alt="Registro de atención médica"></div>
                    <div class="trace-card"><svg><use href="#i-shield"/></svg><span><small>Trazabilidad</small><b>Del registro al resultado</b></span></div>
                </div>
            </div>
            <span class="slide-number">07</span>
        </section>

        {{-- 08 · Equipos: evidencia la preparación previa de cada jornada. --}}
        <section class="slide" data-title="Asignación de equipos" aria-label="Diapositiva 8 de 14">
            <div class="product-layout product-layout--reverse">
                <div class="assignment-visual">
                    <div class="phone-preview phone-preview--assignment"><img src="{{ asset('images/presentacion/recurso-16.jpg') }}" alt="Asignación de médicos y brigadistas"></div>
                    <div class="orbit orbit--one"><svg><use href="#i-users"/></svg></div>
                    <div class="orbit orbit--two"><svg><use href="#i-calendar"/></svg></div>
                    <div class="orbit orbit--three"><svg><use href="#i-heart"/></svg></div>
                </div>
                <div>
                    <p class="eyebrow">Asignación de equipos</p>
                    <h2>La persona correcta,<br><em>en el lugar correcto.</em></h2>
                    <div class="feature-list feature-list--short">
                        <span><svg><use href="#i-check"/></svg><b>Médicos por especialidad</b></span>
                        <span><svg><use href="#i-check"/></svg><b>Brigadistas por función</b></span>
                        <span><svg><use href="#i-check"/></svg><b>Responsabilidades visibles</b></span>
                    </div>
                </div>
            </div>
            <span class="slide-number">08</span>
        </section>

        {{-- 09 · Plataforma web: concentra control operativo e indicadores. --}}
        <section class="slide" data-title="Plataforma web" aria-label="Diapositiva 9 de 14">
            <div class="web-layout">
                <header>
                    <p class="eyebrow">Plataforma web</p>
                    <h2>Gestión, control y<br><em>visibilidad total.</em></h2>
                </header>
                <div class="dashboard-stage">
                    <div class="dashboard-window">
                        <div class="dashboard-bar"><img src="{{ asset('images/logo_brigada.jpeg') }}" alt=""><span></span><i></i><i></i></div>
                        <div class="dashboard-body">
                            <aside><b></b><i></i><i></i><i></i><i></i><i></i></aside>
                            <section>
                                <small>Resumen operativo</small>
                                <div class="dashboard-kpis"><article><b>24</b><span>Campañas</span></article><article><b>1.254</b><span>Pacientes</span></article><article><b>3.486</b><span>Atenciones</span></article></div>
                                <div class="dashboard-panels"><div class="bar-chart"><i></i><i></i><i></i><i></i><i></i></div><div class="donut-chart"></div></div>
                            </section>
                        </div>
                    </div>
                    <div class="laptop-base laptop-base--wide"></div>
                </div>
                <div class="web-capabilities">
                    <span><svg><use href="#i-calendar"/></svg> Campañas</span>
                    <span><svg><use href="#i-users"/></svg> Equipos</span>
                    <span><svg><use href="#i-chart"/></svg> Reportes</span>
                    <span><svg><use href="#i-shield"/></svg> Control</span>
                </div>
            </div>
            <span class="slide-number">09</span>
        </section>

        {{-- 10 · Arquitectura: diagrama conceptual, sin detalles técnicos innecesarios. --}}
        <section class="slide" data-title="Arquitectura" aria-label="Diapositiva 10 de 14">
            <header class="slide-heading slide-heading--center">
                <p class="eyebrow">Arquitectura tecnológica</p>
                <h2>Segura, conectada<br><em>y preparada para crecer.</em></h2>
            </header>
            <div class="architecture">
                <article><span><svg><use href="#i-phone"/></svg></span><b>Android</b><small>Operación móvil</small></article>
                <i><svg><use href="#i-arrow"/></svg></i>
                <article class="architecture--core"><span><svg><use href="#i-api"/></svg></span><b>API Laravel</b><small>Servicios REST</small></article>
                <i><svg><use href="#i-arrow"/></svg></i>
                <article><span><svg><use href="#i-database"/></svg></span><b>MySQL</b><small>Datos relacionales</small></article>
                <i><svg><use href="#i-arrow"/></svg></i>
                <article><span><svg><use href="#i-monitor"/></svg></span><b>Web responsive</b><small>Gestión central</small></article>
            </div>
            <div class="architecture-tags"><span>Sanctum</span><span>Roles y permisos</span><span>Sincronización</span><span>Diseño responsive</span></div>
            <span class="slide-number">10</span>
        </section>

        {{-- 11 · Comunicación: muestra que la información también acompaña a la comunidad. --}}
        <section class="slide" data-title="Comunicación" aria-label="Diapositiva 11 de 14">
            <div class="split-layout split-layout--communication">
                <div>
                    <p class="eyebrow">Comunicación</p>
                    <h2>Información que llega<br><em>antes de la jornada.</em></h2>
                    <div class="communication-cards">
                        <article><svg><use href="#i-calendar"/></svg><span><b>Campañas</b><small>Fechas y lugares</small></span></article>
                        <article><svg><use href="#i-bell"/></svg><span><b>Alertas</b><small>Novedades oportunas</small></span></article>
                        <article><svg><use href="#i-file"/></svg><span><b>Noticias</b><small>Contenido para la comunidad</small></span></article>
                    </div>
                </div>
                <div class="notification-stage">
                    <div class="phone-preview phone-preview--notification"><img src="{{ asset('images/presentacion/recurso-21.jpg') }}" alt="Notificaciones de BrigadaSalud"></div>
                    <span class="notification-pulse pulse--one"></span><span class="notification-pulse pulse--two"></span>
                </div>
            </div>
            <span class="slide-number">11</span>
        </section>

        {{-- 12 · Impacto: tres resultados memorables para cerrar la propuesta. --}}
        <section class="slide slide--impact" data-title="Impacto" aria-label="Diapositiva 12 de 14">
            <header class="slide-heading slide-heading--center">
                <p class="eyebrow eyebrow--light">Impacto</p>
                <h2>Menos fricción.<br><em>Más atención.</em></h2>
            </header>
            <div class="impact-grid">
                <article><span><svg><use href="#i-calendar"/></svg></span><strong>Organización</strong><small>Jornadas mejor coordinadas</small></article>
                <article><span><svg><use href="#i-shield"/></svg></span><strong>Trazabilidad</strong><small>Información confiable</small></article>
                <article><span><svg><use href="#i-chart"/></svg></span><strong>Decisiones</strong><small>Resultados que orientan</small></article>
            </div>
            <p class="impact-statement">Tecnología alrededor del cuidado humano.</p>
            <span class="slide-number">12</span>
        </section>

        {{-- 13 · Equipo: la pieza original se muestra completa, sin recortar rostros ni textos. --}}
        <section class="slide slide--team" data-title="Nuestro equipo" aria-label="Diapositiva 13 de 14">
            <header class="slide-heading slide-heading--team">
                <div><p class="eyebrow">Nuestro equipo</p><h2>Cinco perfiles.<br><em>Un mismo propósito.</em></h2></div>
                <p>Grupo 1 · Desarrollo de Software · ISTG</p>
            </header>
            <div class="team-frame">
                <img src="{{ asset('images/presentacion/equipo-brigada-salud.png') }}" alt="Integrantes y roles del equipo BrigadaSalud">
            </div>
            <span class="slide-number">13</span>
        </section>

        {{-- 14 · Cierre: agradecimiento visual; los nombres y mensajes extensos quedan para la narración. --}}
        <section class="slide slide--closing" data-title="Cierre" aria-label="Diapositiva 14 de 14">
            <div class="closing-grid">
                <div class="mentor-column">
                    <p class="eyebrow eyebrow--light">A quienes guiaron el camino</p>
                    <h3>Gracias por compartir<br>su conocimiento.</h3>
                    <div class="mentor-list">
                        {{-- Cada materia tiene un símbolo monocromático para reconocerla de inmediato. --}}
                        <article>
                            <i class="mentor-icon"><svg><use href="#i-file"/></svg></i>
                            <span><b>José Luis Haz Valero</b><small>Expresión Oral y Escrita</small></span>
                        </article>
                        <article>
                            <i class="mentor-icon"><svg><use href="#i-api"/></svg></i>
                            <span><b>Richard Tigrero</b><small>Programación Web</small></span>
                        </article>
                        <article>
                            <i class="mentor-icon"><svg><use href="#i-phone"/></svg></i>
                            <span><b>Carlos Luis Pazmiño Palma</b><small>Programación Móvil</small></span>
                        </article>
                        <article>
                            <i class="mentor-icon"><svg><use href="#i-monitor"/></svg></i>
                            <span><b>Ángel Humberto Veloz Rodríguez</b><small>Diseño de Interfaz</small></span>
                        </article>
                        <article>
                            <i class="mentor-icon"><svg><use href="#i-wifi"/></svg></i>
                            <span><b>Ivan Amat</b><small>Redes y Telecomunicaciones</small></span>
                        </article>
                    </div>
                </div>
                <div class="closing-content">
                    <img src="{{ asset('images/logo_brigada.jpeg') }}" alt="BrigadaSalud">
                    <p class="eyebrow eyebrow--light">Gracias por acompañarnos</p>
                    <h2>Salud que llega<br><em>a tu comunidad.</em></h2>
                    <p>Menos proceso. Más personas.</p>
                </div>
            </div>
            <span class="slide-number">14</span>
        </section>
    </main>

    {{-- Controles discretos para no competir con el contenido durante la exposición. --}}
    <nav class="presentation-controls" aria-label="Controles de presentación">
        <button type="button" id="previous-slide" aria-label="Diapositiva anterior">←</button>
        <div class="presentation-progress"><i id="progress-bar"></i></div>
        <span id="slide-counter">01 / 14</span>
        <button type="button" id="next-slide" aria-label="Diapositiva siguiente">→</button>
        <button type="button" id="toggle-fullscreen" aria-label="Ver en pantalla completa"><svg><use href="#i-expand"/></svg></button>
    </nav>

    <div class="gesture-hint" id="gesture-hint" aria-hidden="true">Desliza para avanzar</div>

    <script src="{{ asset('js/presentation.js') }}" defer></script>
</body>
</html>
