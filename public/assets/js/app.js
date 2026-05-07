// =============================================================================
//  SIACEP — JavaScript global
//  Archivo: public/assets/js/app.js
//  Funciones compartidas: sidebar, AJAX, helpers de formularios.
// =============================================================================

'use strict';

// ── Sidebar toggle ────────────────────────────────────────────────────────────
const sidebar     = document.getElementById('sidebar');
const mainContent = document.getElementById('mainContent');
const toggleBtn   = document.getElementById('sidebarToggle');
const topbarBtn   = document.getElementById('topbarToggle');

function toggleSidebar() {
    if (!sidebar) return;
    const isMobile = window.innerWidth <= 768;
    if (isMobile) {
        sidebar.classList.toggle('open');
    } else {
        sidebar.classList.toggle('collapsed');
        mainContent?.classList.toggle('expanded');
        if (toggleBtn) {
            toggleBtn.textContent = sidebar.classList.contains('collapsed') ? '›' : '‹';
        }
        localStorage.setItem('siacep_sidebar_collapsed', sidebar.classList.contains('collapsed'));
    }
}

toggleBtn?.addEventListener('click', toggleSidebar);
topbarBtn?.addEventListener('click', toggleSidebar);

// Restaurar estado del sidebar
if (localStorage.getItem('siacep_sidebar_collapsed') === 'true') {
    sidebar?.classList.add('collapsed');
    mainContent?.classList.add('expanded');
    if (toggleBtn) toggleBtn.textContent = '›';
}

// ── Flash messages auto-dismiss ───────────────────────────────────────────────
document.querySelectorAll('.flash').forEach(el => {
    setTimeout(() => {
        el.style.transition = 'opacity .4s';
        el.style.opacity = '0';
        setTimeout(() => el.remove(), 400);
    }, 5000);
});

// ── Token CSRF para peticiones AJAX ──────────────────────────────────────────
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

// ── Helper: fetch con CSRF ────────────────────────────────────────────────────
async function siacepFetch(url, options = {}) {
    const defaults = {
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-Token': CSRF_TOKEN,
        }
    };
    const config = { ...defaults, ...options };
    if (config.headers) config.headers = { ...defaults.headers, ...options.headers };

    const response = await fetch(url, config);
    if (!response.ok) throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    return response.json();
}

// ── Confirmación antes de acciones destructivas ───────────────────────────────
document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', e => {
        const msg = el.dataset.confirm || '¿Está seguro de realizar esta acción?';
        if (!confirm(msg)) e.preventDefault();
    });
});

// ── Formato de números ────────────────────────────────────────────────────────
function formatNum(n, decimals = 2) {
    return Number(n).toLocaleString('es-BO', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals
    });
}

function formatPct(n, decimals = 1) {
    return formatNum(n, decimals) + '%';
}

// ── Actualizar año en footers ─────────────────────────────────────────────────
document.querySelectorAll('.current-year').forEach(el => {
    el.textContent = new Date().getFullYear();
});

// ── Helper: mostrar/ocultar spinner de carga ──────────────────────────────────
function showLoading(btnEl) {
    if (!btnEl) return;
    btnEl._originalText = btnEl.innerHTML;
    btnEl.innerHTML = '<span>⏳</span> Procesando...';
    btnEl.disabled = true;
}
function hideLoading(btnEl) {
    if (!btnEl || !btnEl._originalText) return;
    btnEl.innerHTML = btnEl._originalText;
    btnEl.disabled = false;
}

// ── Exponer helpers globalmente ───────────────────────────────────────────────
window.SIACEP = { siacepFetch, formatNum, formatPct, showLoading, hideLoading, CSRF_TOKEN };