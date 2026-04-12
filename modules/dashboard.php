<?php
// modules/dashboard.php
// Solo frontend — los datos se cargan via AJAX desde ajax/dashboard.php
// No incluye navbar.
?>

<!-- ════════════════════════════════════════════
     ESTILOS DEL DASHBOARD
════════════════════════════════════════════ -->
<style>
  @import url('https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;600&family=Sora:wght@300;400;600;700&display=swap');

  :root {
    --bg:         #f0f2f7;
    --surface:    #ffffff;
    --surface2:   #f5f7fb;
    --border:     #dde1eb;
    --accent:     #2f72e3;
    --accent2:    #10b981;
    --danger:     #ef4444;
    --warn:       #d97706;
    --text:       #1a202c;
    --muted:      #6b7280;
    --radius:     14px;
    --card-pad:   22px;
    --mono:       'IBM Plex Mono', monospace;
    --sans:       'Sora', sans-serif;
  }

  * { box-sizing: border-box; margin: 0; padding: 0; }

  #dashboard-root {
    font-family: var(--sans);
    color: var(--text);
  }

  /* ── Header de período ── */
  .dash-header {
    display: flex;
    align-items: center;
    gap: 14px;
    flex-wrap: wrap;
    margin-bottom: 28px;
  }

  .dash-header h1 {
    font-size: 1.35rem;
    font-weight: 700;
    letter-spacing: -.02em;
    flex: 1;
    min-width: 160px;
  }

  .dash-header h1 span { color: var(--accent); }

  .period-select {
    background: var(--surface2);
    border: 1px solid var(--border);
    color: var(--text);
    border-radius: 8px;
    padding: 8px 14px;
    font-family: var(--sans);
    font-size: .85rem;
    cursor: pointer;
    outline: none;
    transition: border-color .2s;
  }
  .period-select:focus { border-color: var(--accent); }

  .custom-range {
    display: none;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    animation: fadeIn .2s ease;
  }
  .custom-range.visible { display: flex; }

  .date-input {
    background: var(--surface2);
    border: 1px solid var(--border);
    color: var(--text);
    border-radius: 8px;
    padding: 8px 12px;
    font-family: var(--mono);
    font-size: .8rem;
    outline: none;
    transition: border-color .2s;
  }
  .date-input:focus { border-color: var(--accent); }

  .btn-apply {
    background: var(--accent);
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: 8px 18px;
    font-family: var(--sans);
    font-size: .85rem;
    font-weight: 600;
    cursor: pointer;
    transition: opacity .2s;
  }
  .btn-apply:hover { opacity: .85; }

  /* ── Grids ── */
  .grid-6 {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 16px;
    margin-bottom: 20px;
  }
  .grid-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-bottom: 20px;
  }
  .grid-3 {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 16px;
    margin-bottom: 20px;
  }
  .grid-5-7 {
    display: grid;
    grid-template-columns: 5fr 7fr;
    gap: 16px;
    margin-bottom: 20px;
  }
  /* Sección full-width (TX + Cashflow van cada una en su propia fila) */
  .grid-full {
    display: grid;
    grid-template-columns: 1fr;
    gap: 16px;
    margin-bottom: 20px;
  }

  /* Responsivo */
  @media (max-width: 900px)  {
    .grid-5-7, .grid-2 { grid-template-columns: 1fr; }
  }
  @media (max-width: 700px)  {
    /* KPIs: 3 filas de 2 */
    .grid-6 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .grid-3 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  }
  @media (max-width: 480px)  {
    /* KPIs: 1 por fila */
    .grid-6 { grid-template-columns: 1fr; }
    .grid-3 { grid-template-columns: 1fr; }
    .dash-header { gap: 10px; }
    .custom-range { flex-direction: column; align-items: flex-start; }
    .date-input { width: 100%; }
  }

  /* ── Card base ── */
  .card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: var(--card-pad);
    position: relative;
    overflow: hidden;
    box-shadow: 0 1px 4px rgba(0,0,0,.06), 0 4px 16px rgba(0,0,0,.04);
  }

  .card-title {
    font-size: .72rem;
    font-weight: 600;
    letter-spacing: .1em;
    text-transform: uppercase;
    color: var(--muted);
    margin-bottom: 10px;
  }

  /* ── KPI cards ── */
  .kpi-card {
    display: flex;
    flex-direction: column;
    gap: 6px;
    min-width: 0; /* crucial para que el texto no desborde el grid */
  }

  .kpi-card .kpi-value {
    font-family: var(--mono);
    font-size: clamp(.85rem, 2.5vw, 1.3rem); /* escala con el viewport */
    font-weight: 600;
    line-height: 1.15;
    white-space: normal;       /* permite wrap si es necesario */
    word-break: break-all;
    overflow-wrap: break-word;
  }

  .kpi-card .kpi-sub {
    font-size: .72rem;
    color: var(--muted);
    font-family: var(--mono);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .kpi-card .kpi-icon {
    position: absolute;
    top: 14px; right: 14px;
    font-size: 1.4rem;
    opacity: .08;
    pointer-events: none;
  }

  .kpi-accent   { border-top: 3px solid var(--accent); }
  .kpi-success  { border-top: 3px solid var(--accent2); }
  .kpi-danger   { border-top: 3px solid var(--danger); }
  .kpi-warn     { border-top: 3px solid var(--warn); }
  .kpi-muted    { border-top: 3px solid var(--muted); }
  .kpi-purple   { border-top: 3px solid #a78bfa; }

  .text-success { color: var(--accent2); }
  .text-danger  { color: var(--danger); }
  .text-warn    { color: var(--warn); }
  .text-accent  { color: var(--accent); }

  /* ── Chart container ── */
  .chart-wrap {
    position: relative;
    width: 100%;
  }

  /* ── Sección con header interno ── */
  .section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
  }

  .section-title {
    font-size: .85rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .section-link {
    font-size: .75rem;
    color: var(--accent);
    text-decoration: none;
    padding: 4px 10px;
    border: 1px solid var(--accent);
    border-radius: 6px;
    transition: all .2s;
  }
  .section-link:hover { background: var(--accent); color: #fff; }

  /* ── Tabla de transacciones ── */
  .tx-table { width: 100%; border-collapse: collapse; font-size: .82rem; }
  .tx-table th {
    text-align: left;
    font-size: .68rem;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: var(--muted);
    padding: 0 10px 10px;
    border-bottom: 1px solid var(--border);
  }
  .tx-table td { padding: 10px; border-bottom: 1px solid var(--border); vertical-align: middle; }
  .tx-table tr:last-child td { border-bottom: none; }
  .tx-table tr:hover td { background: var(--surface2); }

  .badge-currency {
    background: var(--surface2);
    border: 1px solid var(--border);
    color: var(--muted);
    font-family: var(--mono);
    font-size: .68rem;
    padding: 2px 7px;
    border-radius: 4px;
  }

  /* ── Debt / reminder items ── */
  .item-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 12px 0;
    border-bottom: 1px solid var(--border);
    gap: 10px;
  }
  .item-row:last-child { border-bottom: none; }

  .item-label { font-size: .83rem; font-weight: 600; }
  .item-sub   { font-size: .72rem; color: var(--muted); margin-top: 2px; }
  .item-amount { font-family: var(--mono); font-size: .85rem; white-space: nowrap; }

  /* ── Progress bar ── */
  .progress-wrap { margin-bottom: 16px; }
  .progress-top  { display: flex; justify-content: space-between; font-size: .78rem; margin-bottom: 5px; }
  .progress-bar-bg { background: var(--surface2); border-radius: 99px; height: 7px; overflow: hidden; }
  .progress-bar-fill { height: 100%; border-radius: 99px; background: var(--accent); transition: width .6s ease; }
  .progress-bar-fill.warn { background: var(--warn); }
  .progress-bar-fill.danger { background: var(--danger); }
  .progress-sub { font-size: .7rem; color: var(--muted); margin-top: 4px; font-family: var(--mono); }

  /* ── Skeleton loader ── */
  .skeleton {
    background: linear-gradient(90deg, #e8ecf4 25%, #d4d9e8 50%, #e8ecf4 75%);
    background-size: 200% 100%;
    animation: shimmer 1.4s infinite;
    border-radius: 6px;
  }
  @keyframes shimmer { to { background-position: -200% 0; } }
  .sk-line { height: 14px; margin-bottom: 8px; }
  .sk-val  { height: 30px; margin-bottom: 4px; width: 70%; }
  .sk-sm   { height: 10px; width: 50%; }

  /* ── Empty state ── */
  .empty-state {
    text-align: center;
    padding: 32px 0;
    color: var(--muted);
    font-size: .82rem;
  }
  .empty-state i { font-size: 2rem; margin-bottom: 10px; display: block; opacity: .4; }

  /* ── Animaciones ── */
  @keyframes fadeIn  { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: none; } }
  .fade-in { animation: fadeIn .35s ease both; }

  /* ── Tip banner ── */
  .tip-banner {
    display: flex;
    align-items: center;
    gap: 14px;
    background: linear-gradient(135deg, #fffbeb, #fef3c7);
    border: 1px solid #fde68a;
    border-radius: var(--radius);
    padding: 16px 20px;
    margin-bottom: 22px;
    font-size: .83rem;
  }
  .tip-banner i { color: var(--warn); font-size: 1.2rem; flex-shrink: 0; }
  .tip-banner strong { color: var(--warn); display: block; margin-bottom: 2px; font-size: .72rem; letter-spacing: .08em; text-transform: uppercase; }

  /* Alert de deuda próxima */
  .debt-urgent { border-left: 3px solid var(--danger); padding-left: 10px; }
  .debt-warn   { border-left: 3px solid var(--warn);   padding-left: 10px; }
</style>

<!-- ════════════════════════════════════════════
     ROOT DEL DASHBOARD
════════════════════════════════════════════ -->
<div id="dashboard-root">

  <!-- ── TIP FINANCIERO ── -->
  <div class="tip-banner" id="tip-banner">
    <i class="fas fa-lightbulb"></i>
    <div>
      <strong>Consejo del día</strong>
      <span id="tip-text">Cargando consejo…</span>
    </div>
  </div>

  <!-- ── HEADER + SELECTOR DE PERÍODO ── -->
  <div class="dash-header">
    <h1>Panel <span>Financiero</span></h1>

    <select class="period-select" id="period-select">
      <option value="current_month" selected>Este mes</option>
      <option value="prev_month">Mes anterior</option>
      <option value="current_year">Año actual</option>
      <option value="prev_year">Año anterior</option>
      <option value="custom">Personalizado</option>
    </select>

    <div class="custom-range" id="custom-range">
      <input type="date" class="date-input" id="date-from" />
      <span style="color:var(--muted)">—</span>
      <input type="date" class="date-input" id="date-to" />
      <button class="btn-apply" id="btn-apply">Aplicar</button>
    </div>

    <small id="range-label" style="color:var(--muted);font-size:.72rem;font-family:var(--mono)"></small>
  </div>

  <!-- ══ KPIs ══ -->
  <div class="grid-6" id="kpi-grid">
    <!-- Renderizado por JS -->
  </div>

  <!-- ══ GRÁFICAS ══ -->
  <div class="grid-5-7">
    <!-- Gastos por categoría (barras) -->
    <div class="card fade-in">
      <div class="section-header">
        <span class="section-title"><i class="fas fa-chart-bar text-accent"></i> Gastos por Categoría</span>
      </div>
      <div class="chart-wrap"><canvas id="chartCategory" height="260"></canvas></div>
      <div id="cat-empty" class="empty-state" style="display:none">
        <i class="fas fa-inbox"></i>Sin gastos en el período
      </div>
    </div>

    <!-- Balance general (línea) -->
    <div class="card fade-in">
      <div class="section-header">
        <span class="section-title"><i class="fas fa-chart-line text-accent"></i> Balance General</span>
      </div>
      <div class="chart-wrap"><canvas id="chartBalance" height="260"></canvas></div>
    </div>
  </div>

  <!-- ══ TRANSACCIONES (full width) ══ -->
  <div class="grid-full">
    <div class="card fade-in">
      <div class="section-header">
        <span class="section-title"><i class="fas fa-history text-accent"></i> Últimas Transacciones</span>
        <a href="?module=transactions" class="section-link">Ver todas</a>
      </div>
      <div id="tx-container" style="overflow-x:auto">
        <div class="skeleton sk-line"></div>
        <div class="skeleton sk-line"></div>
        <div class="skeleton sk-line" style="width:60%"></div>
      </div>
    </div>
  </div>

  <!-- ══ FLUJO MENSUAL (full width) ══ -->
  <div class="grid-full">
    <div class="card fade-in">
      <div class="section-header">
        <span class="section-title"><i class="fas fa-exchange-alt text-accent"></i> Flujo Mensual <span id="cashflow-year" style="color:var(--muted);font-size:.75rem;margin-left:6px"></span></span>
      </div>
      <div class="chart-wrap"><canvas id="chartCashflow" height="160"></canvas></div>
    </div>
  </div>

  <!-- ══ DEUDAS + RECORDATORIOS + METAS ══ -->
  <div class="grid-3">
    <!-- Deudas -->
    <div class="card fade-in">
      <div class="section-header">
        <span class="section-title"><i class="fas fa-exclamation-triangle text-warn"></i> Deudas</span>
        <a href="?module=debts" class="section-link">Gestionar</a>
      </div>
      <div id="debts-container">
        <div class="skeleton sk-val"></div>
        <div class="skeleton sk-sm"></div>
      </div>
    </div>

    <!-- Recordatorios -->
    <div class="card fade-in">
      <div class="section-header">
        <span class="section-title"><i class="fas fa-bell text-accent"></i> Recordatorios</span>
        <a href="?module=reminders" class="section-link">Ver todos</a>
      </div>
      <div id="reminders-container">
        <div class="skeleton sk-line"></div>
        <div class="skeleton sk-line"></div>
      </div>
    </div>

    <!-- Metas de ahorro -->
    <div class="card fade-in">
      <div class="section-header">
        <span class="section-title"><i class="fas fa-piggy-bank text-accent"></i> Metas de Ahorro</span>
        <a href="?module=goals" class="section-link">Ver todas</a>
      </div>
      <div id="goals-container">
        <div class="skeleton sk-line"></div>
        <div class="skeleton sk-line"></div>
      </div>
    </div>
  </div>

</div><!-- /dashboard-root -->


<!-- ════════════════════════════════════════════
     SCRIPTS
════════════════════════════════════════════ -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<script>
// ─────────────────────────────────────────────
// Config global
// ─────────────────────────────────────────────
const AJAX_URL = 'ajax/dashboard.php'; // Ruta: cflow/ajax/dashboard.php

Chart.defaults.color           = '#6b7280';
Chart.defaults.borderColor     = '#dde1eb';
Chart.defaults.font.family     = "'IBM Plex Mono', monospace";

let chartCategory = null;
let chartBalance  = null;
let chartCashflow = null;

// ─────────────────────────────────────────────
// Consejo del día
// ─────────────────────────────────────────────
const TIPS = [
  "Ahorra al menos el 20% de tus ingresos mensuales.",
  "Revisa tus suscripciones: cancela las que no usas.",
  "Establece un fondo de emergencia de 3–6 meses de gastos.",
  "Paga primero las deudas con mayor interés.",
  "Usa la regla 50/30/20: necesidades, deseos, ahorro.",
  "Registra todos tus gastos, por pequeños que sean.",
  "Espera 24 horas antes de cualquier compra impulsiva.",
  "Automatiza tus ahorros al recibir ingresos.",
  "Define metas financieras claras y medibles.",
  "No gastes más de lo que ganas.",
  "Paga el total de tu tarjeta para evitar intereses.",
  "Diversifica tus fuentes de ingresos.",
  "Invierte en educación financiera.",
  "Negocia tasas de interés cuando sea posible.",
  "Ten un plan de retiro desde temprano.",
];
document.getElementById('tip-text').textContent = TIPS[Math.floor(Math.random() * TIPS.length)];

// ─────────────────────────────────────────────
// Helpers
// ─────────────────────────────────────────────
const fmt  = v => 'RD$ ' + Number(v).toLocaleString('es-DO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const fmtd = d => { const [y,m,day] = d.split('-'); return `${day}/${m}/${y}`; };
const $ = id => document.getElementById(id);

function post(action, extra = {}) {
  const body = new FormData();
  body.append('action', action);
  for (const [k, v] of Object.entries(extra)) body.append(k, v);
  return fetch(AJAX_URL, { method: 'POST', body }).then(r => r.json());
}

// ─────────────────────────────────────────────
// Estado del período
// ─────────────────────────────────────────────
let currentPeriod   = 'current_month';
let customDateFrom  = '';
let customDateTo    = '';

function getPeriodPayload() {
  const p = { period: currentPeriod };
  if (currentPeriod === 'custom') { p.date_from = customDateFrom; p.date_to = customDateTo; }
  return p;
}

// ─────────────────────────────────────────────
// KPIs
// ─────────────────────────────────────────────
function renderKPIs() {
  const g = $('kpi-grid');
  g.innerHTML = `
    ${Array(6).fill(0).map(() => `<div class="card kpi-card"><div class="skeleton sk-line"></div><div class="skeleton sk-val"></div><div class="skeleton sk-sm"></div></div>`).join('')}
  `;

  post('get_kpis', getPeriodPayload()).then(d => {
    if (!d.success) return;

    $('range-label').textContent = `${fmtd(d.date_from)} – ${fmtd(d.date_to)}`;

    const netClass  = d.net_cashflow >= 0 ? 'text-success' : 'text-danger';
    const netIcon   = d.net_cashflow >= 0 ? 'fa-arrow-up' : 'fa-arrow-down';
    const netLabel  = d.net_cashflow >= 0 ? 'Superávit' : 'Déficit';
    const netAccent = d.net_cashflow >= 0 ? 'kpi-success' : 'kpi-danger';

    g.innerHTML = `
      <div class="card kpi-card kpi-accent fade-in">
        <i class="fas fa-wallet kpi-icon"></i>
        <div class="card-title">Balance Total</div>
        <div class="kpi-value">${fmt(d.total_dop)}</div>
        <div class="kpi-sub">≈ US$ ${Number(d.total_usd).toLocaleString('es-DO', {minimumFractionDigits:2})}</div>
      </div>

      <div class="card kpi-card kpi-danger fade-in">
        <i class="fas fa-credit-card kpi-icon"></i>
        <div class="card-title">Balance Adeudado</div>
        <div class="kpi-value text-danger">${fmt(d.total_owed)}</div>
        <div class="kpi-sub">Deudas + Tarjetas</div>
      </div>

      <div class="card kpi-card kpi-success fade-in">
        <i class="fas fa-arrow-up kpi-icon"></i>
        <div class="card-title">Ingresos</div>
        <div class="kpi-value text-success">${fmt(d.total_income)}</div>
        <div class="kpi-sub">En el período</div>
      </div>

      <div class="card kpi-card kpi-warn fade-in">
        <i class="fas fa-arrow-down kpi-icon"></i>
        <div class="card-title">Gastos</div>
        <div class="kpi-value text-warn">${fmt(d.total_expense)}</div>
        <div class="kpi-sub">En el período</div>
      </div>

      <div class="card kpi-card ${netAccent} fade-in">
        <i class="fas ${netIcon} kpi-icon"></i>
        <div class="card-title">Flujo Neto</div>
        <div class="kpi-value ${netClass}">${fmt(Math.abs(d.net_cashflow))}</div>
        <div class="kpi-sub">${netLabel}</div>
      </div>

      <div class="card kpi-card kpi-purple fade-in">
        <i class="fas fa-receipt kpi-icon"></i>
        <div class="card-title">Transacciones</div>
        <div class="kpi-value" style="color:#a78bfa">${Number(d.total_transactions).toLocaleString()}</div>
        <div class="kpi-sub">En el período</div>
      </div>
    `;
  });
}

// ─────────────────────────────────────────────
// Gráfica: Gastos por categoría (barras)
// ─────────────────────────────────────────────
const PALETTE = ['#4f8ef7','#34d399','#f87171','#fbbf24','#a78bfa','#fb923c','#22d3ee','#f472b6','#86efac','#fde68a'];

function renderCategoryChart() {
  post('get_expenses_by_category', getPeriodPayload()).then(d => {
    if (!d.success) return;
    const catEmpty = $('cat-empty');

    if (!d.labels.length) {
      catEmpty.style.display = 'block';
      $('chartCategory').style.display = 'none';
      if (chartCategory) { chartCategory.destroy(); chartCategory = null; }
      return;
    }
    catEmpty.style.display = 'none';
    $('chartCategory').style.display = 'block';

    if (chartCategory) chartCategory.destroy();
    chartCategory = new Chart($('chartCategory'), {
      type: 'bar',
      data: {
        labels: d.labels,
        datasets: [{
          data: d.values,
          backgroundColor: PALETTE.slice(0, d.labels.length),
          borderRadius: 6,
          borderWidth: 0,
        }]
      },
      options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: ctx => fmt(ctx.raw)
            }
          }
        },
        scales: {
          x: { grid: { color: '#dde1eb' }, ticks: { callback: v => 'RD$ ' + v.toLocaleString() } },
          y: { grid: { display: false } }
        }
      }
    });
  });
}

// ─────────────────────────────────────────────
// Gráfica: Balance general (línea)
// ─────────────────────────────────────────────
function renderBalanceChart() {
  post('get_balance_trend', getPeriodPayload()).then(d => {
    if (!d.success) return;
    if (chartBalance) chartBalance.destroy();

    const gradient = $('chartBalance').getContext('2d').createLinearGradient(0, 0, 0, 260);
    gradient.addColorStop(0,   'rgba(47,114,227,.18)');
    gradient.addColorStop(1,   'rgba(47,114,227,0)');

    chartBalance = new Chart($('chartBalance'), {
      type: 'line',
      data: {
        labels: d.labels,
        datasets: [{
          label: 'Balance',
          data: d.balances,
          borderColor: '#2f72e3',
          backgroundColor: gradient,
          borderWidth: 2,
          pointRadius: d.labels.length > 60 ? 0 : 3,
          pointHoverRadius: 5,
          fill: true,
          tension: .4,
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: { callbacks: { label: ctx => fmt(ctx.raw) } }
        },
        scales: {
          x: { grid: { color: '#dde1eb' } },
          y: { grid: { color: '#dde1eb' }, ticks: { callback: v => 'RD$ ' + v.toLocaleString() } }
        }
      }
    });
  });
}

// ─────────────────────────────────────────────
// Gráfica: Cashflow mensual
// ─────────────────────────────────────────────
function renderCashflowChart() {
  post('get_cashflow_chart', getPeriodPayload()).then(d => {
    if (!d.success) return;
    $('cashflow-year').textContent = d.year;
    if (chartCashflow) chartCashflow.destroy();

    chartCashflow = new Chart($('chartCashflow'), {
      type: 'bar',
      data: {
        labels: d.cashflow.map(r => r.month),
        datasets: [
          {
            label: 'Ingresos',
            data: d.cashflow.map(r => r.income),
            backgroundColor: 'rgba(52,211,153,.75)',
            borderRadius: 5,
            borderWidth: 0,
          },
          {
            label: 'Gastos',
            data: d.cashflow.map(r => r.expense),
            backgroundColor: 'rgba(248,113,113,.75)',
            borderRadius: 5,
            borderWidth: 0,
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { labels: { font: { size: 11 }, boxWidth: 12, color: '#e2e8f0' } },
          tooltip: { callbacks: { label: ctx => `${ctx.dataset.label}: ${fmt(ctx.raw)}` } }
        },
        scales: {
          x: { grid: { color: '#dde1eb' } },
          y: { grid: { color: '#dde1eb' }, ticks: { callback: v => 'RD$ ' + v.toLocaleString() } }
        }
      }
    });
  });
}

// ─────────────────────────────────────────────
// Últimas transacciones
// ─────────────────────────────────────────────
function renderTransactions() {
  const c = $('tx-container');
  post('get_recent_transactions', getPeriodPayload()).then(d => {
    if (!d.success) return;

    if (!d.transactions.length) {
      c.innerHTML = `<div class="empty-state"><i class="fas fa-receipt"></i>Sin transacciones en el período</div>`;
      return;
    }

    const rows = d.transactions.map(t => {
      const sign  = t.type === 'income' ? '+' : '-';
      const cls   = t.type === 'income' ? 'text-success' : 'text-danger';
      const cat   = t.category_name || 'Sin categoría';
      const desc  = t.description   || '—';
      return `<tr>
        <td style="font-family:var(--mono);font-size:.75rem">${fmtd(t.date)}</td>
        <td>${t.account_name}<br><span class="badge-currency">${t.account_currency}</span></td>
        <td style="color:var(--muted);font-size:.78rem">${cat}</td>
        <td class="${cls}" style="font-family:var(--mono)">${sign} ${t.symbol}${Number(t.original_amount).toLocaleString('es-DO',{minimumFractionDigits:2})}</td>
        <td><span class="badge-currency">${t.original_currency_code}</span></td>
      </tr>`;
    }).join('');

    c.innerHTML = `
      <table class="tx-table">
        <thead><tr>
          <th>Fecha</th><th>Cuenta</th><th>Categoría</th><th>Monto</th><th>Moneda</th>
        </tr></thead>
        <tbody>${rows}</tbody>
      </table>`;
  });
}

// ─────────────────────────────────────────────
// Deudas
// ─────────────────────────────────────────────
function renderDebts() {
  const c = $('debts-container');
  post('get_debts').then(d => {
    if (!d.success) return;

    if (!d.pending_count) {
      c.innerHTML = `<div class="empty-state"><i class="fas fa-check-circle" style="color:var(--accent2)"></i>¡Sin deudas pendientes!</div>`;
      return;
    }

    const upcoming = d.upcoming_debts.map(dbt => {
      const days = Math.ceil((new Date(dbt.due_date) - new Date()) / 86400000);
      const cls  = days <= 3 ? 'debt-urgent' : 'debt-warn';
      return `<div class="item-row ${cls}">
        <div>
          <div class="item-label">${dbt.creditor}</div>
          <div class="item-sub">Vence: ${fmtd(dbt.due_date)} · ${days}d</div>
        </div>
        <div class="item-amount text-danger">${fmt(dbt.remaining)}</div>
      </div>`;
    }).join('');

    c.innerHTML = `
      <div style="margin-bottom:14px">
        <div style="font-family:var(--mono);font-size:1.3rem;color:var(--danger)">${fmt(d.pending_amount)}</div>
        <div style="font-size:.72rem;color:var(--muted)">${d.pending_count} deuda(s) activa(s)</div>
      </div>
      ${upcoming || '<div class="item-sub" style="padding:8px 0">Sin vencimientos próximos (30 días)</div>'}
    `;
  });
}

// ─────────────────────────────────────────────
// Recordatorios
// ─────────────────────────────────────────────
function renderReminders() {
  const c = $('reminders-container');
  post('get_reminders').then(d => {
    if (!d.success) return;

    if (!d.reminders.length) {
      c.innerHTML = `<div class="empty-state"><i class="fas fa-check-circle" style="color:var(--accent2)"></i>Sin recordatorios pendientes</div>`;
      return;
    }

    c.innerHTML = d.reminders.map(r => `
      <div class="item-row">
        <div>
          <div class="item-label">${r.title}</div>
          <div class="item-sub">${fmtd(r.reminder_date)}${r.is_recurring ? ' · 🔁' : ''}</div>
          ${r.description ? `<div class="item-sub" style="margin-top:2px">${r.description}</div>` : ''}
        </div>
      </div>`).join('');
  });
}

// ─────────────────────────────────────────────
// Metas de ahorro
// ─────────────────────────────────────────────
function renderGoals() {
  const c = $('goals-container');
  post('get_savings_goals').then(d => {
    if (!d.success) return;

    if (!d.goals.length) {
      c.innerHTML = `
        <div class="empty-state">
          <i class="fas fa-chart-line"></i>Sin metas de ahorro
          <br><a href="?module=goals" class="section-link" style="margin-top:10px;display:inline-block">Crear meta</a>
        </div>`;
      return;
    }

    c.innerHTML = d.goals.map(g => {
      const pct    = Math.min(100, (g.current_amount / g.target_amount) * 100);
      const fillCls = pct >= 100 ? 'danger' : pct >= 80 ? 'warn' : '';
      return `
        <div class="progress-wrap">
          <div class="progress-top">
            <span>${g.name}</span>
            <span style="color:var(--muted)">${pct.toFixed(0)}%</span>
          </div>
          <div class="progress-bar-bg">
            <div class="progress-bar-fill ${fillCls}" style="width:${pct}%"></div>
          </div>
          <div class="progress-sub">${fmt(g.current_amount)} / ${fmt(g.target_amount)} · Vence ${fmtd(g.deadline)}</div>
        </div>`;
    }).join('');
  });
}

// ─────────────────────────────────────────────
// Carga completa
// ─────────────────────────────────────────────
function loadDashboard() {
  renderKPIs();
  renderCategoryChart();
  renderBalanceChart();
  renderTransactions();
  renderCashflowChart();
  renderDebts();
  renderReminders();
  renderGoals();
}

// ─────────────────────────────────────────────
// Eventos del selector de período
// ─────────────────────────────────────────────
const periodSelect = $('period-select');
const customRange  = $('custom-range');
const dateFromEl   = $('date-from');
const dateToEl     = $('date-to');

// Defaults para rango personalizado
const today = new Date();
dateToEl.value   = today.toISOString().slice(0, 10);
dateFromEl.value = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().slice(0, 10);

periodSelect.addEventListener('change', () => {
  currentPeriod = periodSelect.value;
  if (currentPeriod === 'custom') {
    customRange.classList.add('visible');
  } else {
    customRange.classList.remove('visible');
    loadDashboard();
  }
});

$('btn-apply').addEventListener('click', () => {
  customDateFrom = dateFromEl.value;
  customDateTo   = dateToEl.value;
  if (!customDateFrom || !customDateTo) return;
  loadDashboard();
});

// ─────────────────────────────────────────────
// Init
// ─────────────────────────────────────────────
loadDashboard();
</script>