/* BrigadaMedica — Utilidades de interfaz compartidas (sidebar, modales, toasts, etc.) */

function initSidebar() {
  const toggle = document.getElementById('sidebar-toggle');
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('sidebar-overlay');

  if (!toggle || !sidebar) return;

  toggle.addEventListener('click', () => {
    sidebar.classList.toggle('is-open');
    overlay?.classList.toggle('is-visible');
  });

  overlay?.addEventListener('click', () => {
    sidebar.classList.remove('is-open');
    overlay.classList.remove('is-visible');
  });
}

function initPasswordToggle() {
  document.querySelectorAll('[data-toggle-password]').forEach(btn => {
    btn.addEventListener('click', () => {
      const input = document.getElementById(btn.dataset.togglePassword);
      if (!input) return;
      const isPassword = input.type === 'password';
      input.type = isPassword ? 'text' : 'password';
      btn.textContent = isPassword ? 'visibility_off' : 'visibility';
      btn.setAttribute('aria-label', isPassword ? 'Ocultar contraseña' : 'Mostrar contraseña');
    });
  });
}

function showToast(message, type = 'success') {
  let toast = document.getElementById('toast');
  if (!toast) {
    toast = document.createElement('div');
    toast.id = 'toast';
    toast.className = 'toast';
    document.body.appendChild(toast);
  }
  toast.className = `toast toast-${type}`;
  toast.innerHTML = `<span class="material-symbols-rounded">${type === 'success' ? 'check_circle' : 'error'}</span><span>${message}</span>`;
  toast.classList.add('show');
  clearTimeout(toast._hideTimer);
  toast._hideTimer = setTimeout(() => toast.classList.remove('show'), 3000);
}

function initModals() {
  document.querySelectorAll('[data-modal-open]').forEach(btn => {
    btn.addEventListener('click', () => {
      const modal = document.getElementById(btn.dataset.modalOpen);
      modal?.classList.add('is-open');
    });
  });

  document.querySelectorAll('[data-modal-close]').forEach(btn => {
    btn.addEventListener('click', () => {
      btn.closest('.modal-overlay')?.classList.remove('is-open');
    });
  });

  document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', e => {
      if (e.target === overlay) overlay.classList.remove('is-open');
    });
  });
}

function closeModal(id) {
  document.getElementById(id)?.classList.remove('is-open');
}

function openModal(id) {
  document.getElementById(id)?.classList.add('is-open');
}

// Oculta del sidebar y del bottom-nav los enlaces a módulos para los que este usuario
// no tiene permiso, en vez de dejar que entre y se encuentre con un 403. Si un grupo
// entero del sidebar se queda sin ningún enlace visible, se oculta también su rótulo.
function aplicarPermisosMenu() {
  document.querySelectorAll('[data-requiere-permiso]').forEach(el => {
    if (!hasPermission(el.dataset.requierePermiso)) el.style.display = 'none';
  });

  document.querySelectorAll('[data-sidebar-section]').forEach(seccion => {
    const quedaAlgunoVisible = [...seccion.querySelectorAll('.sidebar-link')]
      .some(enlace => enlace.style.display !== 'none');
    seccion.style.display = quedaAlgunoVisible ? '' : 'none';
  });
}

function initUserMenu() {
  const menu = document.getElementById('user-menu');
  const trigger = document.getElementById('user-menu-trigger');
  if (!menu || !trigger) return;

  function cerrar() {
    menu.classList.remove('is-open');
    trigger.setAttribute('aria-expanded', 'false');
  }

  trigger.addEventListener('click', (e) => {
    e.stopPropagation();
    const abrir = !menu.classList.contains('is-open');
    menu.classList.toggle('is-open', abrir);
    trigger.setAttribute('aria-expanded', String(abrir));
  });

  document.addEventListener('click', (e) => {
    if (!menu.contains(e.target)) cerrar();
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') cerrar();
  });
}

// Búsqueda global del topbar: pacientes se busca en vivo contra la API (?buscar=),
// el resto de catálogos (brigadas/medicos/comunidades/noticias) no tiene endpoint de
// búsqueda propio, así que se trae una vez por sesión y se filtra en el cliente.
const cacheBusquedaGlobal = {};

async function obtenerListaCacheada(clave, ruta) {
  if (cacheBusquedaGlobal[clave]) return cacheBusquedaGlobal[clave];

  let pagina = 1;
  let ultimaPagina = 1;
  const todos = [];
  do {
    const separador = ruta.includes('?') ? '&' : '?';
    const resultado = await Api.get(`${ruta}${separador}page=${pagina}`);
    if (!resultado.ok) break;
    todos.push(...resultado.data);
    ultimaPagina = resultado.meta?.last_page ?? 1;
    pagina++;
  } while (pagina <= ultimaPagina);

  cacheBusquedaGlobal[clave] = todos;
  return todos;
}

function initGlobalSearch() {
  const wrap = document.getElementById('global-search');
  const input = document.getElementById('global-search-input');
  const panel = document.getElementById('global-search-results');
  if (!wrap || !input || !panel) return;

  let temporizador = null;
  let idSolicitud = 0;

  function cerrar() { wrap.classList.remove('is-open'); }
  function abrir() { wrap.classList.add('is-open'); }

  input.addEventListener('input', () => {
    clearTimeout(temporizador);
    const termino = input.value.trim();
    if (termino.length < 2) {
      cerrar();
      return;
    }
    temporizador = setTimeout(() => ejecutarBusqueda(termino), 300);
  });

  input.addEventListener('focus', () => {
    if (input.value.trim().length >= 2) abrir();
  });

  document.addEventListener('click', (e) => {
    if (!wrap.contains(e.target)) cerrar();
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') { cerrar(); input.blur(); }
  });

  async function buscarPacientes(termino) {
    if (!hasPermission('pacientes.gestionar')) return null;
    const resultado = await Api.get(`/pacientes?buscar=${encodeURIComponent(termino)}&per_page=5`);
    if (!resultado.ok || !resultado.data.length) return null;
    return {
      titulo: 'Pacientes',
      icono: 'personal_injury',
      items: resultado.data.map(p => ({
        texto: `${p.nombres} ${p.apellidos}`,
        subtexto: p.cedula,
        href: `/pacientes?paciente_id=${p.id}`,
      })),
    };
  }

  async function buscarBrigadas(terminoMin) {
    if (!hasPermission('brigadas.gestionar')) return null;
    const lista = await obtenerListaCacheada('brigadas', '/brigadas?per_page=100');
    const coincidencias = lista.filter(b => b.nombre.toLowerCase().includes(terminoMin)).slice(0, 5);
    if (!coincidencias.length) return null;
    return {
      titulo: 'Campañas',
      icono: 'groups',
      items: coincidencias.map(b => ({
        texto: b.nombre,
        subtexto: b.ubicacion,
        href: `/brigadas/${b.id}`,
      })),
    };
  }

  async function buscarMedicos(terminoMin) {
    if (!hasPermission('medicos.gestionar')) return null;
    const lista = await obtenerListaCacheada('medicos', '/medicos');
    const coincidencias = lista.filter(m =>
      m.nombres.toLowerCase().includes(terminoMin) || m.credencial_cmp.toLowerCase().includes(terminoMin)
    ).slice(0, 5);
    if (!coincidencias.length) return null;
    return {
      titulo: 'Médicos',
      icono: 'stethoscope',
      items: coincidencias.map(m => ({
        texto: m.nombres,
        subtexto: m.credencial_cmp,
        href: `/medicos/${m.id}/editar`,
      })),
    };
  }

  async function buscarComunidades(terminoMin) {
    if (!hasPermission('comunidades.gestionar')) return null;
    const lista = await obtenerListaCacheada('comunidades', '/comunidades');
    const coincidencias = lista.filter(c =>
      c.nombre.toLowerCase().includes(terminoMin) || c.sector.toLowerCase().includes(terminoMin)
    ).slice(0, 5);
    if (!coincidencias.length) return null;
    return {
      titulo: 'Comunidades',
      icono: 'location_city',
      items: coincidencias.map(c => ({
        texto: c.nombre,
        subtexto: c.sector,
        href: '/comunidades',
      })),
    };
  }

  async function buscarNoticias(terminoMin) {
    if (!hasPermission('noticias.gestionar')) return null;
    const lista = await obtenerListaCacheada('noticias', '/noticias');
    const coincidencias = lista.filter(n => n.titulo.toLowerCase().includes(terminoMin)).slice(0, 5);
    if (!coincidencias.length) return null;
    return {
      titulo: 'Noticias',
      icono: 'newspaper',
      items: coincidencias.map(n => ({
        texto: n.titulo,
        subtexto: formatearFecha(n.fecha_publicacion),
        href: `/noticias/${n.id}/editar`,
      })),
    };
  }

  async function ejecutarBusqueda(termino) {
    const miId = ++idSolicitud;
    panel.innerHTML = '<div class="search-result-empty">Buscando...</div>';
    abrir();

    const terminoMin = termino.toLowerCase();

    // Las 5 categorías se consultan en paralelo (no una tras otra) para que la
    // búsqueda responda en el tiempo de la más lenta, no en la suma de todas.
    const resultados = await Promise.all([
      buscarPacientes(termino),
      buscarBrigadas(terminoMin),
      buscarMedicos(terminoMin),
      buscarComunidades(terminoMin),
      buscarNoticias(terminoMin),
    ]);

    if (miId !== idSolicitud) return; // llegó una búsqueda más nueva mientras esperábamos
    pintarResultadosBusqueda(panel, resultados.filter(Boolean), termino);
  }
}

function pintarResultadosBusqueda(panel, grupos, termino) {
  if (!grupos.length) {
    panel.innerHTML = `<div class="search-result-empty">Sin resultados para "${termino}"</div>`;
    return;
  }

  panel.innerHTML = grupos.map(g => `
    <div class="search-result-group">
      <div class="search-result-group-label">${g.titulo}</div>
      ${g.items.map(it => `
        <a href="${it.href}" class="search-result-item">
          <span class="material-symbols-rounded">${g.icono}</span>
          <span class="search-result-item-text">
            <strong>${it.texto}</strong>
            ${it.subtexto ? `<span>${it.subtexto}</span>` : ''}
          </span>
        </a>`).join('')}
    </div>`).join('');
}

function initChipSelectors() {
  document.querySelectorAll('.chip-selectable').forEach(chip => {
    chip.addEventListener('click', () => chip.classList.toggle('is-selected'));
  });
}

function initFilterChips() {
  document.querySelectorAll('.filter-bar[data-filter-group]').forEach(bar => {
    bar.querySelectorAll('.filter-chip').forEach(chip => {
      chip.addEventListener('click', () => {
        bar.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('is-active'));
        chip.classList.add('is-active');
        const filter = chip.dataset.filter;
        const target = bar.dataset.filterGroup;
        document.querySelectorAll(`[data-filter-target="${target}"]`).forEach(el => {
          el.style.display = !filter || filter === 'all' || el.dataset.status === filter ? '' : 'none';
        });
      });
    });
  });
}

// Filtro de texto libre sobre las filas de una tabla ya renderizada (client-side).
// Se usa además del filtro real por cédula/nombre que ya hace la API (?buscar=).
function initTableSearch(tableSelector, inputSelector) {
  const input = document.querySelector(inputSelector);
  const table = document.querySelector(tableSelector);
  if (!input || !table) return;

  input.addEventListener('input', () => {
    const term = input.value.trim().toLowerCase();
    table.querySelectorAll('tbody tr').forEach(row => {
      row.style.display = !term || row.textContent.toLowerCase().includes(term) ? '' : 'none';
    });
  });
}

function initRevealAnimations() {
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) entry.target.classList.add('revealed');
    });
  }, { threshold: 0.1 });
  document.querySelectorAll('[data-reveal]').forEach(el => observer.observe(el));
}

// Mapea el nombre real de una especialidad a la clase de chip de color que ya existe
// en el sistema de diseño. Las que no estaban contempladas (Psicología, Enfermería,
// Oftalmología) caen en el estilo neutro "otra" en vez de inventar colores nuevos.
function especialidadChipClass(nombre) {
  const mapa = {
    'medicina general': 'chip-especialidad-mg',
    'odontología': 'chip-especialidad-odo',
    'odontologia': 'chip-especialidad-odo',
    'pediatría': 'chip-especialidad-ped',
    'pediatria': 'chip-especialidad-ped',
    'ginecología': 'chip-especialidad-gin',
    'ginecologia': 'chip-especialidad-gin',
    'nutrición': 'chip-especialidad-nut',
    'nutricion': 'chip-especialidad-nut',
  };
  return mapa[(nombre || '').trim().toLowerCase()] || 'chip-especialidad-otra';
}

// Mapea el estado de un Turno (backend) al chip visual ya definido en el diseño.
function estadoTurnoChipClass(estado) {
  const mapa = {
    pendiente: 'chip-pendiente',
    en_espera: 'chip-espera',
    atendido: 'chip-atendido',
    cancelado: 'chip-cancelado',
    no_asistio: 'chip-no-asistio',
  };
  return mapa[estado] || 'chip-pendiente';
}

function estadoTurnoLabel(estado) {
  const mapa = {
    pendiente: 'Pendiente',
    en_espera: 'En espera',
    atendido: 'Atendido',
    cancelado: 'Cancelado',
    no_asistio: 'No asistió',
  };
  return mapa[estado] || estado;
}

function estadoBrigadaChipClass(estado) {
  const mapa = {
    programada: 'chip-programada',
    en_curso: 'chip-en-curso',
    finalizada: 'chip-finalizada',
    cancelada: 'chip-cancelado',
  };
  return mapa[estado] || 'chip-programada';
}

function estadoBrigadaLabel(estado) {
  const mapa = {
    programada: 'Programada',
    en_curso: 'En curso',
    finalizada: 'Finalizada',
    cancelada: 'Cancelada',
  };
  return mapa[estado] || estado;
}

// Iniciales para el círculo de avatar (ej. "María Coordinadora" -> "MC").
function iniciales(nombreCompleto) {
  return (nombreCompleto || '')
    .trim()
    .split(/\s+/)
    .map(p => p[0])
    .slice(0, 2)
    .join('')
    .toUpperCase() || '--';
}

function formatearFecha(fechaIso) {
  if (!fechaIso) return '—';
  const [anio, mes, dia] = fechaIso.split('T')[0].split('-');
  const meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
  return `${parseInt(dia, 10)} ${meses[parseInt(mes, 10) - 1]} ${anio}`;
}

function formatearHora(fechaHoraIso) {
  if (!fechaHoraIso) return '—';
  const fecha = new Date(fechaHoraIso.replace(' ', 'T'));
  if (isNaN(fecha.getTime())) return '—';
  return fecha.toLocaleTimeString('es-EC', { hour: '2-digit', minute: '2-digit' });
}

document.addEventListener('DOMContentLoaded', () => {
  initSidebar();
  initPasswordToggle();
  initModals();
  initChipSelectors();
  initFilterChips();
});
