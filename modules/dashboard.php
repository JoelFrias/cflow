<?php
// modules/dashboard.php
// Solo frontend — datos via AJAX desde ajax/dashboard.php
?>

<!-- ════════════════════════════════════════════
     FUENTES + ESTILOS
════════════════════════════════════════════ -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">

<style>
/* ══ Variables ══════════════════════════════════════════ */
:root {
  --bg:        #f6f5f1;
  --surface:   #ffffff;
  --border:    #e8e5df;
  --border2:   #f0ede8;
  --blue:      #2563eb;
  --green:     #059669;
  --red:       #dc2626;
  --amber:     #d97706;
  --purple:    #7c3aed;
  --text:      #18181b;
  --sub:       #71717a;
  --muted:     #a1a1aa;
  --mono:      'JetBrains Mono', monospace;
  --sans:      'Plus Jakarta Sans', sans-serif;
  --r:         16px;
  --rr:        10px;
  --shadow:    0 1px 3px rgba(0,0,0,.06), 0 4px 12px rgba(0,0,0,.04);
}

* { box-sizing: border-box; margin: 0; padding: 0; }

#db { font-family: var(--sans); color: var(--text); background: var(--bg); min-height: 100vh; }

/* ══ Secciones con separación coherente ════════════════ */
.db-section { margin-bottom: 28px; }

/* ══ Encabezado ════════════════════════════════════════ */
.db-head {
  align-items: center;
  margin-bottom: 24px;
  padding-bottom: 18px;
  border-bottom: 1px solid var(--border);
}
.db-head-left { flex: 1; min-width: 0; }
.db-greeting {
  font-size: .72rem;
  font-weight: 600;
  letter-spacing: .1em;
  text-transform: uppercase;
  color: var(--muted);
  margin-bottom: 3px;
}
.db-title { font-size: 1.25rem; font-weight: 700; letter-spacing: -.025em; }
.db-title span { color: var(--blue); }

.period-row { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }

.period-sel {
  font-family: var(--sans);
  font-size: .8rem;
  font-weight: 500;
  background: var(--surface);
  border: 1px solid var(--border);
  color: var(--text);
  border-radius: var(--rr);
  padding: 7px 12px;
  cursor: pointer;
  outline: none;
  transition: border-color .2s;
}
.period-sel:focus { border-color: var(--blue); }

.custom-range { display: none; align-items: center; gap: 6px; flex-wrap: wrap; }
.custom-range.on { display: flex; }

.date-in {
  font-family: var(--mono);
  font-size: .75rem;
  background: var(--surface);
  border: 1px solid var(--border);
  color: var(--text);
  border-radius: var(--rr);
  padding: 7px 10px;
  outline: none;
}
.date-in:focus { border-color: var(--blue); }

.btn-apply {
  font-family: var(--sans);
  font-size: .8rem;
  font-weight: 600;
  background: var(--blue);
  color: #fff;
  border: none;
  border-radius: var(--rr);
  padding: 7px 16px;
  cursor: pointer;
  transition: opacity .2s;
}
.btn-apply:hover { opacity: .85; }

.range-tag {
  font-family: var(--mono);
  font-size: .68rem;
  color: var(--muted);
  white-space: nowrap;
}

/* ══ Card base ══════════════════════════════════════════ */
.card {
  background: var(--surface);
  border: 1px solid var(--border2);
  border-radius: var(--r);
  box-shadow: var(--shadow);
  overflow: hidden;
}
.card-body { padding: 20px; }
.card-body.tight { padding: 16px; }

.card-label {
  font-size: .66rem;
  font-weight: 700;
  letter-spacing: .1em;
  text-transform: uppercase;
  color: var(--muted);
  margin-bottom: 12px;
  display: flex;
  align-items: center;
  gap: 6px;
}
.card-label i { opacity: .6; }

/* ══ Acciones rápidas ═══════════════════════════════════ */
.qa-scroll {
  display: flex;
  gap: 10px;
  overflow-x: auto;
  padding-bottom: 4px;
  -webkit-overflow-scrolling: touch;
  scrollbar-width: none;
}
.qa-scroll::-webkit-scrollbar { display: none; }

.qa-btn {
  flex: 0 0 auto;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 7px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--r);
  padding: 14px 18px;
  cursor: pointer;
  text-decoration: none;
  color: var(--text);
  transition: border-color .2s, box-shadow .2s, transform .15s;
  min-width: 76px;
}
.qa-btn:hover {
  border-color: var(--blue);
  box-shadow: 0 0 0 3px rgba(37,99,235,.08);
  transform: translateY(-2px);
  color: var(--text);
  text-decoration: none;
}
.qa-icon {
  width: 38px; height: 38px;
  border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  font-size: .95rem;
}
.qa-icon.blue   { background: #eff6ff; color: var(--blue); }
.qa-icon.green  { background: #ecfdf5; color: var(--green); }
.qa-icon.amber  { background: #fffbeb; color: var(--amber); }
.qa-icon.purple { background: #f5f3ff; color: var(--purple); }
.qa-icon.red    { background: #fef2f2; color: var(--red); }
.qa-label { font-size: .68rem; font-weight: 600; text-align: center; line-height: 1.2; }

/* ══ Perspectiva ════════════════════════════════════════ */
.insight-list { display: flex; flex-direction: column; gap: 0; }
.insight-item {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 13px 0;
  border-bottom: 1px solid var(--border2);
}
.insight-item:last-child { border-bottom: none; }
.insight-dot {
  width: 8px; height: 8px; border-radius: 50%;
  flex-shrink: 0; margin-top: 5px;
}
.insight-dot.blue   { background: var(--blue); }
.insight-dot.green  { background: var(--green); }
.insight-dot.amber  { background: var(--amber); }
.insight-dot.red    { background: var(--red); }
.insight-text { font-size: .82rem; line-height: 1.5; color: var(--sub); }
.insight-text strong { color: var(--text); font-weight: 600; }

/* ══ KPIs ════════════════════════════════════════════════ */
.kpi-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 12px;
}
@media (min-width: 600px)  { .kpi-grid { grid-template-columns: repeat(3, 1fr); } }
@media (min-width: 1024px) { .kpi-grid { grid-template-columns: repeat(6, 1fr); gap: 14px; } }

.kpi {
  background: var(--surface);
  border: 1px solid var(--border2);
  border-radius: var(--r);
  box-shadow: var(--shadow);
  padding: 16px;
  display: flex;
  flex-direction: column;
  gap: 5px;
  position: relative;
  overflow: hidden;
  animation: fadeUp .35s ease both;
}
.kpi::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 3px;
  border-radius: var(--r) var(--r) 0 0;
}
.kpi.blue::before   { background: var(--blue); }
.kpi.green::before  { background: var(--green); }
.kpi.red::before    { background: var(--red); }
.kpi.amber::before  { background: var(--amber); }
.kpi.purple::before { background: var(--purple); }
.kpi.muted::before  { background: var(--muted); }

.kpi-lbl {
  font-size: .64rem;
  font-weight: 700;
  letter-spacing: .09em;
  text-transform: uppercase;
  color: var(--muted);
}
.kpi-val {
  font-family: var(--mono);
  font-size: clamp(.82rem, 2.2vw, 1.1rem);
  font-weight: 600;
  line-height: 1.2;
  word-break: break-all;
}
.kpi-val.blue   { color: var(--blue); }
.kpi-val.green  { color: var(--green); }
.kpi-val.red    { color: var(--red); }
.kpi-val.amber  { color: var(--amber); }
.kpi-val.purple { color: var(--purple); }
.kpi-sub {
  font-family: var(--mono);
  font-size: .65rem;
  color: var(--muted);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* ══ Gráficas ════════════════════════════════════════════ */
.chart-wrap { position: relative; width: 100%; }

/* ══ Encabezado de tarjeta interna ══════════════════════ */
.sec-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}
.sec-title {
  font-size: .82rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 7px;
}
.sec-link {
  font-size: .72rem;
  font-weight: 600;
  color: var(--blue);
  text-decoration: none;
  padding: 4px 10px;
  border: 1px solid rgba(37,99,235,.25);
  border-radius: 6px;
  transition: all .2s;
}
.sec-link:hover { background: var(--blue); color: #fff; border-color: var(--blue); }

/* ══ Layout de dos columnas (gráficas) ══════════════════ */
.two-col {
  display: grid;
  grid-template-columns: 1fr;
  gap: 14px;
}
@media (min-width: 900px) { .two-col { grid-template-columns: 5fr 7fr; } }

/* ══ Tres columnas (deudas / reminders / metas) ═════════ */
.three-col {
  display: grid;
  grid-template-columns: 1fr;
  gap: 14px;
}
@media (min-width: 700px)  { .three-col { grid-template-columns: 1fr 1fr; } }
@media (min-width: 1024px) { .three-col { grid-template-columns: 1fr 1fr 1fr; } }

/* ══ Tabla transacciones ════════════════════════════════ */
.tx-tbl { width: 100%; border-collapse: collapse; font-size: .8rem; }
.tx-tbl th {
  font-size: .64rem; font-weight: 700;
  letter-spacing: .08em; text-transform: uppercase;
  color: var(--muted);
  padding: 0 10px 10px;
  text-align: left;
  border-bottom: 1px solid var(--border);
}
.tx-tbl td { padding: 10px; border-bottom: 1px solid var(--border2); vertical-align: middle; }
.tx-tbl tr:last-child td { border-bottom: none; }
.tx-tbl tr:hover td { background: #fafaf8; }

.tag {
  display: inline-block;
  font-family: var(--mono);
  font-size: .65rem;
  font-weight: 400;
  background: var(--bg);
  border: 1px solid var(--border);
  color: var(--sub);
  padding: 2px 7px;
  border-radius: 5px;
}

/* ══ Items deuda / recordatorio ═════════════════════════ */
.item {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  padding: 11px 0;
  border-bottom: 1px solid var(--border2);
  gap: 10px;
}
.item:last-child { border-bottom: none; }
.item-lbl { font-size: .8rem; font-weight: 600; }
.item-sub { font-size: .7rem; color: var(--muted); margin-top: 2px; }
.item-amt { font-family: var(--mono); font-size: .82rem; white-space: nowrap; }

/* ══ Progress bar ════════════════════════════════════════ */
.prog-wrap { margin-bottom: 14px; }
.prog-top { display: flex; justify-content: space-between; font-size: .76rem; margin-bottom: 5px; font-weight: 500; }
.prog-bg { background: var(--bg); border-radius: 99px; height: 6px; overflow: hidden; }
.prog-fill { height: 100%; border-radius: 99px; background: var(--blue); transition: width .6s ease; }
.prog-fill.warn   { background: var(--amber); }
.prog-fill.danger { background: var(--red); }
.prog-sub { font-size: .67rem; color: var(--muted); margin-top: 4px; font-family: var(--mono); }

/* ══ Skeleton ════════════════════════════════════════════ */
.sk {
  background: linear-gradient(90deg, #ebebeb 25%, #d8d8d8 50%, #ebebeb 75%);
  background-size: 200% 100%;
  animation: shimmer 1.4s infinite;
  border-radius: 6px;
}
@keyframes shimmer { to { background-position: -200% 0; } }
.sk-line { height: 13px; margin-bottom: 8px; }
.sk-val  { height: 28px; margin-bottom: 4px; width: 65%; }
.sk-sm   { height: 10px; width: 45%; }

/* ══ Estado vacío ════════════════════════════════════════ */
.empty {
  text-align: center;
  padding: 28px 0;
  color: var(--muted);
  font-size: .8rem;
}
.empty i { font-size: 1.8rem; display: block; margin-bottom: 8px; opacity: .35; }

/* ══ Animaciones ═════════════════════════════════════════ */
@keyframes fadeUp {
  from { opacity: 0; transform: translateY(8px); }
  to   { opacity: 1; transform: translateY(0); }
}
.fade-up { animation: fadeUp .35s ease both; }

/* ══ Urgencia deuda ══════════════════════════════════════ */
.urgent { border-left: 3px solid var(--red);   padding-left: 10px; }
.warn   { border-left: 3px solid var(--amber); padding-left: 10px; }

/* ══ Tip ══════════════════════════════════════════════════ */
.tip {
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: .78rem;
  color: var(--sub);
  padding: 12px 16px;
  background: #fffbeb;
  border: 1px solid #fde68a;
  border-radius: var(--r);
  line-height: 1.5;
}
.tip i { color: var(--amber); flex-shrink: 0; }
</style>

<!-- ════════════════════════════════════════════
     HTML
════════════════════════════════════════════ -->

  <!-- ── Encabezado + período ── -->
  <div class="db-head db-section">
    <div class="db-head-left">
      <div class="db-greeting" id="db-greeting">Buenos días</div>
      <div class="db-title">Panel <span>Financiero</span></div>
    </div>
    <div class="period-row">
      <select class="period-sel" id="period-select">
        <option value="current_month" selected>Este mes</option>
        <option value="prev_month">Mes anterior</option>
        <option value="current_year">Año actual</option>
        <option value="prev_year">Año anterior</option>
        <option value="custom">Personalizado</option>
      </select>
      <div class="custom-range" id="custom-range">
        <input type="date" class="date-in" id="date-from">
        <span style="color:var(--muted)">—</span>
        <input type="date" class="date-in" id="date-to">
        <button class="btn-apply" id="btn-apply">Aplicar</button>
      </div>
      <span class="range-tag" id="range-label"></span>
    </div>
  </div>

  <!-- ── TIP DEL DÍA ── (reducido) -->
  <div class="db-section">
    <div class="tip">
      <i class="fas fa-lightbulb"></i>
      <span id="tip-text"></span>
    </div>
  </div>

  <!-- ── ACCIONES RÁPIDAS (novedad útil) ── -->
  <div class="db-section">
    <div class="card-label" style="margin-bottom:10px"><i class="fas fa-bolt"></i> Acciones Rápidas</div>
    <div class="qa-scroll">
      <a href="?module=transactions" class="qa-btn">
        <div class="qa-icon blue"><i class="fas fa-plus"></i></div>
        <span class="qa-label">Nueva<br>Transacción</span>
      </a>
      <a href="?module=accounts" class="qa-btn">
        <div class="qa-icon purple"><i class="fas fa-wallet"></i></div>
        <span class="qa-label">Ver<br>Cuentas</span>
      </a>
      <a href="?module=debts" class="qa-btn">
        <div class="qa-icon red"><i class="fas fa-hand-holding-usd"></i></div>
        <span class="qa-label">Pagar<br>Deuda</span>
      </a>
      <a href="?module=goals" class="qa-btn">
        <div class="qa-icon green"><i class="fas fa-piggy-bank"></i></div>
        <span class="qa-label">Nueva<br>Meta</span>
      </a>
      <a href="?module=reminders" class="qa-btn">
        <div class="qa-icon amber"><i class="fas fa-bell"></i></div>
        <span class="qa-label">Recordatorio</span>
      </a>
    </div>
  </div>

  <!-- ── PERSPECTIVA FINANCIERA ── -->
  <div class="db-section">
    <div class="card">
      <div class="card-body tight">
        <div class="card-label"><i class="fas fa-chart-pie"></i> Perspectiva del período</div>
        <div class="insight-list" id="insight-list">
          <div class="insight-item"><div class="sk sk-line" style="width:80%"></div></div>
          <div class="insight-item"><div class="sk sk-line" style="width:65%"></div></div>
          <div class="insight-item"><div class="sk sk-line" style="width:72%"></div></div>
        </div>
      </div>
    </div>
  </div>

  <!-- ── KPIs ── -->
  <div class="db-section">
    <div class="kpi-grid" id="kpi-grid">
      <!-- 6 skeletons -->
      <?php for($i=0;$i<6;$i++): ?>
      <div class="kpi muted">
        <div class="sk sk-line" style="width:55%"></div>
        <div class="sk sk-val"></div>
        <div class="sk sk-sm"></div>
      </div>
      <?php endfor; ?>
    </div>
  </div>

  <!-- ── GRÁFICAS ── -->
  <div class="db-section two-col">
    <div class="card fade-up">
      <div class="card-body">
        <div class="sec-head">
          <span class="sec-title"><i class="fas fa-chart-bar" style="color:var(--blue)"></i> Gastos por Categoría</span>
        </div>
        <div class="chart-wrap"><canvas id="chartCategory" height="250"></canvas></div>
        <div id="cat-empty" class="empty" style="display:none"><i class="fas fa-inbox"></i>Sin gastos en el período</div>
      </div>
    </div>
    <div class="card fade-up" style="animation-delay:.06s">
      <div class="card-body">
        <div class="sec-head">
          <span class="sec-title"><i class="fas fa-chart-line" style="color:var(--blue)"></i> Tendencia de Balance</span>
        </div>
        <div class="chart-wrap"><canvas id="chartBalance" height="250"></canvas></div>
      </div>
    </div>
  </div>

  <!-- ── TRANSACCIONES RECIENTES ── -->
  <div class="db-section">
    <div class="card fade-up" style="animation-delay:.09s">
      <div class="card-body">
        <div class="sec-head">
          <span class="sec-title"><i class="fas fa-history" style="color:var(--blue)"></i> Últimas Transacciones</span>
          <a href="?module=transactions" class="sec-link">Ver todas</a>
        </div>
        <div id="tx-container" style="overflow-x:auto">
          <div class="sk sk-line"></div>
          <div class="sk sk-line" style="width:75%"></div>
          <div class="sk sk-line" style="width:60%"></div>
        </div>
      </div>
    </div>
  </div>

  <!-- ── FLUJO MENSUAL ── -->
  <div class="db-section">
    <div class="card fade-up" style="animation-delay:.12s">
      <div class="card-body">
        <div class="sec-head">
          <span class="sec-title">
            <i class="fas fa-exchange-alt" style="color:var(--blue)"></i>
            Flujo Mensual
            <span id="cashflow-year" style="color:var(--muted);font-weight:400;font-size:.75rem"></span>
          </span>
        </div>
        <div class="chart-wrap"><canvas id="chartCashflow" height="150"></canvas></div>
      </div>
    </div>
  </div>

  <!-- ── DEUDAS + RECORDATORIOS + METAS ── -->
  <div class="db-section three-col">
    <div class="card fade-up" style="animation-delay:.15s">
      <div class="card-body tight">
        <div class="sec-head">
          <span class="sec-title"><i class="fas fa-exclamation-circle" style="color:var(--amber)"></i> Deudas</span>
          <a href="?module=debts" class="sec-link">Gestionar</a>
        </div>
        <div id="debts-container">
          <div class="sk sk-val"></div><div class="sk sk-sm"></div>
        </div>
      </div>
    </div>
    <div class="card fade-up" style="animation-delay:.18s">
      <div class="card-body tight">
        <div class="sec-head">
          <span class="sec-title"><i class="fas fa-bell" style="color:var(--blue)"></i> Recordatorios</span>
          <a href="?module=reminders" class="sec-link">Ver todos</a>
        </div>
        <div id="reminders-container">
          <div class="sk sk-line"></div><div class="sk sk-line" style="width:70%"></div>
        </div>
      </div>
    </div>
    <div class="card fade-up" style="animation-delay:.21s">
      <div class="card-body tight">
        <div class="sec-head">
          <span class="sec-title"><i class="fas fa-piggy-bank" style="color:var(--green)"></i> Metas de Ahorro</span>
          <a href="?module=goals" class="sec-link">Ver todas</a>
        </div>
        <div id="goals-container">
          <div class="sk sk-line"></div><div class="sk sk-line" style="width:70%"></div>
        </div>
      </div>
    </div>
  </div>


<!-- ════════════════════════════════════════════
     SCRIPTS
════════════════════════════════════════════ -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<script>
/* ── Config Chart.js ─────────────────────────────────── */
Chart.defaults.color       = '#71717a';
Chart.defaults.borderColor = '#e8e5df';
Chart.defaults.font.family = "'JetBrains Mono', monospace";
Chart.defaults.font.size   = 11;

const AJAX = 'ajax/dashboard.php';

let chartCat  = null;
let chartBal  = null;
let chartCash = null;

/* ── Helpers ─────────────────────────────────────────── */
const $   = id => document.getElementById(id);
const fmt = v  => 'RD$ ' + Number(v).toLocaleString('es-DO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const fmtd = d => { const [y,m,day] = d.split('-'); return `${day}/${m}/${y}`; };

function post(action, extra = {}) {
  const fd = new FormData();
  fd.append('action', action);
  for (const [k, v] of Object.entries(extra)) fd.append(k, v);
  return fetch(AJAX, { method: 'POST', body: fd }).then(r => r.json());
}

/* ── Saludo según hora ────────────────────────────────── */
const hr = new Date().getHours();
$('db-greeting').textContent = hr < 12 ? 'Buenos días' : hr < 19 ? 'Buenas tardes' : 'Buenas noches';

/* ── Tip del día ─────────────────────────────────────── */
const TIPS = [
  // AHORRO
  'Ahorra aunque sea una pequeña cantidad cada semana.',
  'Guarda primero y gasta después, no al revés.',
  'Separa el dinero del ahorro en otra cuenta.',
  'Evita gastar todo lo que ganas.',
  'Ten siempre un fondo para emergencias.',
  'Aprovecha ofertas solo si realmente necesitas el producto.',
  'Reduce gastos innecesarios poco a poco.',
  'Lleva control de lo que gastas diariamente.',
  'Evita compras impulsivas.',
  'Ahorra cualquier dinero extra que recibas.',
  'Establece una meta de ahorro mensual.',
  'No toques tus ahorros sin una razón importante.',
  'Usa listas antes de salir a comprar.',
  'Revisa tus gastos cada semana.',
  'Empieza con metas pequeñas y ve aumentando.',

  // DEUDAS
  'Evita endeudarte si no es necesario.',
  'Paga siempre a tiempo para evitar recargos.',
  'Prioriza pagar las deudas más urgentes.',
  'No tomes nuevas deudas mientras pagas otras.',
  'Paga más del mínimo siempre que puedas.',
  'Organiza todas tus deudas en una lista.',
  'Evita usar la tarjeta si ya tienes deudas.',
  'Negocia pagos si no puedes cumplir.',
  'Reduce gastos para pagar deudas más rápido.',
  'No ignores las deudas, enfréntalas.',
  'Ten un plan claro para salir de deudas.',
  'Usa ingresos extra para abonar a deudas.',
  'Evita préstamos rápidos con altos intereses.',
  'No uses una deuda para pagar otra sin estrategia.',
  'Celebra cada deuda que logres pagar.',

  // MENTALIDAD
  'El dinero es una herramienta, no un fin.',
  'La disciplina vale más que ganar mucho dinero.',
  'No compres por presión social.',
  'Aprende a diferenciar necesidad de deseo.',
  'Ser constante es más importante que empezar perfecto.',
  'Tus hábitos financieros definen tu futuro.',
  'No necesitas ganar más para empezar a ahorrar.',
  'Pequeños cambios hacen grandes diferencias.',
  'El control del dinero empieza con la conciencia.',
  'Evita compararte con otros.',
  'Piensa antes de gastar.',
  'Construye hábitos, no excusas.',
  'El progreso es mejor que la perfección.',
  'Educarte financieramente es una inversión.',
  'La paciencia es clave para mejorar tus finanzas.'
];
$('tip-text').textContent = TIPS[new Date().getDate() % TIPS.length];

/* ── Estado del período ──────────────────────────────── */
let period    = 'current_month';
let dateFrom  = '';
let dateTo    = '';

function payload() {
  const p = { period };
  if (period === 'custom') { p.date_from = dateFrom; p.date_to = dateTo; }
  return p;
}

/* ── KPIs ─────────────────────────────────────────────── */
function renderKPIs() {
  const g = $('kpi-grid');
  g.innerHTML = Array(6).fill(0).map(() =>
    `<div class="kpi muted"><div class="sk sk-line" style="width:55%"></div><div class="sk sk-val"></div><div class="sk sk-sm"></div></div>`
  ).join('');

  post('get_kpis', payload()).then(d => {
    if (!d.success) return;
    $('range-label').textContent = `${fmtd(d.date_from)} – ${fmtd(d.date_to)}`;

    const netColor = d.net_cashflow >= 0 ? 'green' : 'red';
    const netLabel = d.net_cashflow >= 0 ? 'Superávit' : 'Déficit';

    g.innerHTML = `
      <div class="kpi blue fade-up" style="animation-delay:.00s">
        <div class="kpi-lbl">Balance Total</div>
        <div class="kpi-val blue">${fmt(d.total_dop)}</div>
        <div class="kpi-sub">≈ US$ ${Number(d.total_usd).toLocaleString('es-DO',{minimumFractionDigits:2})}</div>
      </div>
      <div class="kpi red fade-up" style="animation-delay:.04s">
        <div class="kpi-lbl">Balance Adeudado</div>
        <div class="kpi-val red">${fmt(d.total_owed)}</div>
        <div class="kpi-sub">Deudas + Tarjetas</div>
      </div>
      <div class="kpi green fade-up" style="animation-delay:.08s">
        <div class="kpi-lbl">Ingresos</div>
        <div class="kpi-val green">${fmt(d.total_income)}</div>
        <div class="kpi-sub">En el período</div>
      </div>
      <div class="kpi amber fade-up" style="animation-delay:.12s">
        <div class="kpi-lbl">Gastos</div>
        <div class="kpi-val amber">${fmt(d.total_expense)}</div>
        <div class="kpi-sub">En el período</div>
      </div>
      <div class="kpi ${netColor} fade-up" style="animation-delay:.16s">
        <div class="kpi-lbl">Flujo Neto</div>
        <div class="kpi-val ${netColor}">${fmt(Math.abs(d.net_cashflow))}</div>
        <div class="kpi-sub">${netLabel}</div>
      </div>
      <div class="kpi purple fade-up" style="animation-delay:.20s">
        <div class="kpi-lbl">Transacciones</div>
        <div class="kpi-val purple">${Number(d.total_transactions).toLocaleString()}</div>
        <div class="kpi-sub">En el período</div>
      </div>
    `;

    // Renderizar perspectiva ahora que tenemos los datos
    renderInsights(d);
  });
}

/* ── Perspectiva financiera ─────────────────────────────
   Genera 3 frases de insight basadas en los KPIs         */
function renderInsights(d) {
  const list = $('insight-list');
  const insights = [];

  // 1. Ratio gasto / ingreso
  if (d.total_income > 0) {
    const ratio = (d.total_expense / d.total_income) * 100;
    if (ratio >= 100) {
      insights.push({ dot: 'red',   text: `Tus gastos <strong>superan tus ingresos</strong> en un ${(ratio - 100).toFixed(0)}% este período. Considera reducir gastos no esenciales.` });
    } else if (ratio >= 80) {
      insights.push({ dot: 'amber', text: `Tus gastos representan el <strong>${ratio.toFixed(0)}% de tus ingresos</strong>. Margen de ahorro ajustado.` });
    } else {
      insights.push({ dot: 'green', text: `Tus gastos representan el <strong>${ratio.toFixed(0)}% de tus ingresos</strong>. Buen margen de ahorro este período.` });
    }
  } else {
    insights.push({ dot: 'blue', text: 'No hay ingresos registrados en el período seleccionado.' });
  }

  // 2. Deuda total
  if (d.total_owed > 0) {
    const pct = d.total_dop > 0 ? ((d.total_owed / d.total_dop) * 100).toFixed(0) : '—';
    insights.push({ dot: 'amber', text: `Tienes <strong>${fmt(d.total_owed)}</strong> en deudas y tarjetas, equivalente al ${pct}% de tu balance total.` });
  } else {
    insights.push({ dot: 'green', text: '¡Sin saldo deudor en tarjetas ni deudas pendientes! Buen trabajo.' });
  }

  // 3. Actividad del período
  if (d.total_transactions > 0) {
    const avgExpense = d.total_transactions > 0 && d.total_expense > 0
      ? fmt(d.total_expense / d.total_transactions)
      : null;
    insights.push({ dot: 'blue', text: `Registraste <strong>${d.total_transactions} transacciones</strong> en el período${avgExpense ? `. Con un gasto promedio de <strong>${avgExpense}` : ''}</strong> diario en el periodo.` });
  } else {
    insights.push({ dot: 'muted', text: 'Sin transacciones en el período. Empieza registrando tus movimientos.' });
  }

  list.innerHTML = insights.map(i => `
    <div class="insight-item">
      <div class="insight-dot ${i.dot}"></div>
      <div class="insight-text">${i.text}</div>
    </div>`).join('');
}

/* ── Gráfica: Gastos por categoría ──────────────────── */
const PAL = ['#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6','#ec4899','#06b6d4','#84cc16','#f97316','#6366f1'];

function renderCatChart() {
  post('get_expenses_by_category', payload()).then(d => {
    if (!d.success) return;
    const empty = $('cat-empty');
    const canvas = $('chartCategory');

    if (!d.labels.length) {
      empty.style.display  = 'block';
      canvas.style.display = 'none';
      if (chartCat) { chartCat.destroy(); chartCat = null; }
      return;
    }
    empty.style.display  = 'none';
    canvas.style.display = 'block';

    if (chartCat) chartCat.destroy();
    chartCat = new Chart(canvas, {
      type: 'bar',
      data: {
        labels: d.labels,
        datasets: [{ data: d.values, backgroundColor: PAL.slice(0, d.labels.length), borderRadius: 5, borderWidth: 0 }]
      },
      options: {
        indexAxis: 'y', responsive: true, maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: { callbacks: { label: ctx => fmt(ctx.raw) } }
        },
        scales: {
          x: { grid: { color: '#f0ede8' }, ticks: { callback: v => 'RD$ ' + v.toLocaleString() } },
          y: { grid: { display: false } }
        }
      }
    });
  });
}

/* ── Gráfica: Tendencia de balance ────────────────────  */
function renderBalChart() {
  post('get_balance_trend', payload()).then(d => {
    if (!d.success) return;
    if (chartBal) chartBal.destroy();

    const ctx = $('chartBalance').getContext('2d');
    const grad = ctx.createLinearGradient(0, 0, 0, 250);
    grad.addColorStop(0, 'rgba(37,99,235,.14)');
    grad.addColorStop(1, 'rgba(37,99,235,0)');

    chartBal = new Chart($('chartBalance'), {
      type: 'line',
      data: {
        labels: d.labels,
        datasets: [{
          data: d.balances, borderColor: '#2563eb', backgroundColor: grad,
          borderWidth: 2, pointRadius: d.labels.length > 50 ? 0 : 3,
          pointHoverRadius: 5, fill: true, tension: .4
        }]
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => fmt(ctx.raw) } } },
        scales: {
          x: { grid: { color: '#f0ede8' } },
          y: { grid: { color: '#f0ede8' }, ticks: { callback: v => 'RD$ ' + v.toLocaleString() } }
        }
      }
    });
  });
}

/* ── Gráfica: Cashflow mensual ────────────────────────  */
function renderCashChart() {
  post('get_cashflow_chart', payload()).then(d => {
    if (!d.success) return;
    $('cashflow-year').textContent = d.year;
    if (chartCash) chartCash.destroy();

    chartCash = new Chart($('chartCashflow'), {
      type: 'bar',
      data: {
        labels: d.cashflow.map(r => r.month),
        datasets: [
          { label: 'Ingresos', data: d.cashflow.map(r => r.income),  backgroundColor: 'rgba(16,185,129,.7)', borderRadius: 4, borderWidth: 0 },
          { label: 'Gastos',   data: d.cashflow.map(r => r.expense), backgroundColor: 'rgba(239,68,68,.7)',   borderRadius: 4, borderWidth: 0 }
        ]
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        plugins: {
          legend: { labels: { boxWidth: 10, padding: 16, font: { size: 11 } } },
          tooltip: { callbacks: { label: ctx => `${ctx.dataset.label}: ${fmt(ctx.raw)}` } }
        },
        scales: {
          x: { grid: { color: '#f0ede8' } },
          y: { grid: { color: '#f0ede8' }, ticks: { callback: v => 'RD$ ' + v.toLocaleString() } }
        }
      }
    });
  });
}

/* ── Transacciones recientes ─────────────────────────  */
function renderTx() {
  const c = $('tx-container');
  post('get_recent_transactions', payload()).then(d => {
    if (!d.success) return;
    if (!d.transactions.length) {
      c.innerHTML = `<div class="empty"><i class="fas fa-receipt"></i>Sin transacciones en el período</div>`;
      return;
    }
    const rows = d.transactions.map(t => {
      const sign = t.type === 'income' ? '+' : '–';
      const col  = t.type === 'income' ? 'var(--green)' : 'var(--red)';
      return `<tr>
        <td style="font-family:var(--mono);font-size:.72rem;white-space:nowrap">${fmtd(t.date)}</td>
        <td>${t.account_name}<br><span class="tag">${t.account_currency}</span></td>
        <td style="color:var(--sub);font-size:.76rem">${t.category_name ?? '—'}</td>
        <td style="font-family:var(--mono);font-size:.8rem;color:${col};white-space:nowrap">${sign} ${t.symbol}${Number(t.original_amount).toLocaleString('es-DO',{minimumFractionDigits:2})}</td>
        <td><span class="tag">${t.original_currency_code}</span></td>
      </tr>`;
    }).join('');
    c.innerHTML = `<table class="tx-tbl">
      <thead><tr><th>Fecha</th><th>Cuenta</th><th>Categoría</th><th>Monto</th><th>Moneda</th></tr></thead>
      <tbody>${rows}</tbody>
    </table>`;
  });
}

/* ── Deudas ───────────────────────────────────────────  */
function renderDebts() {
  const c = $('debts-container');
  post('get_debts').then(d => {
    if (!d.success) return;
    if (!d.pending_count) {
      c.innerHTML = `<div class="empty"><i class="fas fa-check-circle" style="color:var(--green);opacity:1"></i>¡Sin deudas pendientes!</div>`;
      return;
    }
    const upcoming = d.upcoming_debts.map(dbt => {
      const days = Math.ceil((new Date(dbt.due_date) - new Date()) / 86400000);
      const cls  = days <= 3 ? 'urgent' : 'warn';
      return `<div class="item ${cls}">
        <div><div class="item-lbl">${dbt.creditor}</div><div class="item-sub">Vence ${fmtd(dbt.due_date)} · ${days}d</div></div>
        <div class="item-amt" style="color:var(--red)">${fmt(dbt.remaining)}</div>
      </div>`;
    }).join('');
    c.innerHTML = `
      <div style="margin-bottom:14px">
        <div style="font-family:var(--mono);font-size:1.1rem;font-weight:600;color:var(--red)">${fmt(d.pending_amount)}</div>
        <div style="font-size:.7rem;color:var(--muted);margin-top:2px">${d.pending_count} deuda(s) activa(s)</div>
      </div>
      ${upcoming || '<div style="font-size:.76rem;color:var(--muted);padding:6px 0">Sin vencimientos próximos (30 días)</div>'}`;
  });
}

/* ── Recordatorios ───────────────────────────────────  */
function renderReminders() {
  const c = $('reminders-container');
  post('get_reminders').then(d => {
    if (!d.success) return;
    if (!d.reminders.length) {
      c.innerHTML = `<div class="empty"><i class="fas fa-check-circle" style="color:var(--green);opacity:1"></i>Sin recordatorios pendientes</div>`;
      return;
    }
    c.innerHTML = d.reminders.map(r => `
      <div class="item">
        <div>
          <div class="item-lbl">${r.title}</div>
          <div class="item-sub">${fmtd(r.reminder_date)}${r.is_recurring ? ' · 🔁' : ''}</div>
          ${r.description ? `<div class="item-sub" style="margin-top:2px">${r.description}</div>` : ''}
        </div>
      </div>`).join('');
  });
}

/* ── Metas de ahorro ─────────────────────────────────  */
function renderGoals() {
  const c = $('goals-container');
  post('get_savings_goals').then(d => {
    if (!d.success) return;
    if (!d.goals.length) {
      c.innerHTML = `<div class="empty"><i class="fas fa-chart-line"></i>Sin metas de ahorro<br>
        <a href="?module=goals" class="sec-link" style="margin-top:10px;display:inline-block">Crear meta</a></div>`;
      return;
    }
    c.innerHTML = d.goals.map(g => {
      const pct    = Math.min(100, (g.current_amount / g.target_amount) * 100);
      const fillCls = pct >= 100 ? 'danger' : pct >= 80 ? 'warn' : '';
      return `<div class="prog-wrap">
        <div class="prog-top"><span>${g.name}</span><span style="color:var(--muted)">${pct.toFixed(0)}%</span></div>
        <div class="prog-bg"><div class="prog-fill ${fillCls}" style="width:${pct}%"></div></div>
        <div class="prog-sub">${fmt(g.current_amount)} / ${fmt(g.target_amount)} · ${fmtd(g.deadline)}</div>
      </div>`;
    }).join('');
  });
}

/* ── Carga principal ─────────────────────────────────  */
function load() {
  renderKPIs();          // insights se renderizan dentro de renderKPIs()
  renderCatChart();
  renderBalChart();
  renderTx();
  renderCashChart();
  renderDebts();
  renderReminders();
  renderGoals();
}

/* ── Eventos de período ──────────────────────────────  */
const periodSel   = $('period-select');
const customRange = $('custom-range');
const dateFromEl  = $('date-from');
const dateToEl    = $('date-to');

const today = new Date();
dateToEl.value   = today.toISOString().slice(0, 10);
dateFromEl.value = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().slice(0, 10);

periodSel.addEventListener('change', () => {
  period = periodSel.value;
  customRange.classList.toggle('on', period === 'custom');
  if (period !== 'custom') load();
});

$('btn-apply').addEventListener('click', () => {
  dateFrom = dateFromEl.value;
  dateTo   = dateToEl.value;
  if (dateFrom && dateTo) load();
});

/* ── Init ────────────────────────────────────────────  */
load();
</script>