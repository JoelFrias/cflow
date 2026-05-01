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

/* ══ Secciones ══════════════════════════════════════════ */
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
  font-size: .72rem; font-weight: 600; letter-spacing: .1em;
  text-transform: uppercase; color: var(--muted); margin-bottom: 3px;
}
.db-title { font-size: 1.25rem; font-weight: 700; letter-spacing: -.025em; }
.db-title span { color: var(--blue); }

.period-row { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }

.period-sel {
  font-family: var(--sans); font-size: .8rem; font-weight: 500;
  background: var(--surface); border: 1px solid var(--border); color: var(--text);
  border-radius: var(--rr); padding: 7px 12px; cursor: pointer; outline: none;
  transition: border-color .2s;
}
.period-sel:focus { border-color: var(--blue); }

.custom-range { display: none; align-items: center; gap: 6px; flex-wrap: wrap; }
.custom-range.on { display: flex; }

.date-in {
  font-family: var(--mono); font-size: .75rem; background: var(--surface);
  border: 1px solid var(--border); color: var(--text);
  border-radius: var(--rr); padding: 7px 10px; outline: none;
}
.date-in:focus { border-color: var(--blue); }

.btn-apply {
  font-family: var(--sans); font-size: .8rem; font-weight: 600;
  background: var(--blue); color: #fff; border: none;
  border-radius: var(--rr); padding: 7px 16px; cursor: pointer; transition: opacity .2s;
}
.btn-apply:hover { opacity: .85; }

.range-tag {
  font-family: var(--mono); font-size: .68rem;
  color: var(--muted); white-space: nowrap;
}

/* ══ Notificación de recordatorio ══════════════════════ */
#reminder-notif {
  display: none;
  align-items: center;
  gap: 12px;
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  border-radius: 12px;
  padding: 13px 16px;
  margin-bottom: 20px;
  animation: fadeUp .4s ease both;
}
.notif-icon { color: #3b82f6; font-size: 1rem; flex-shrink: 0; }
.notif-text { font-size: .82rem; color: #1e40af; flex: 1; line-height: 1.5; }
.notif-text strong { color: #1e3a8a; }
.notif-text a { color: #2563eb; font-weight: 600; text-decoration: none; }
.notif-text a:hover { text-decoration: underline; }
.notif-close {
  background: none; border: none; cursor: pointer;
  color: #93c5fd; font-size: 1rem; padding: 2px 4px;
  display: flex; align-items: center; flex-shrink: 0;
  transition: color .2s;
}
.notif-close:hover { color: #3b82f6; }

/* ══ Card base ══════════════════════════════════════════ */
.card {
  background: var(--surface); border: 1px solid var(--border2);
  border-radius: var(--r); box-shadow: var(--shadow); overflow: hidden;
}
.card-body { padding: 20px; }
.card-body.tight { padding: 16px; }
.card-label {
  font-size: .66rem; font-weight: 700; letter-spacing: .1em;
  text-transform: uppercase; color: var(--muted); margin-bottom: 12px;
  display: flex; align-items: center; gap: 6px;
}
.card-label i { opacity: .6; }

/* ══ Acciones rápidas ═══════════════════════════════════ */
.qa-scroll {
  display: flex; gap: 10px; overflow-x: auto;
  padding-bottom: 4px; -webkit-overflow-scrolling: touch; scrollbar-width: none;
}
.qa-scroll::-webkit-scrollbar { display: none; }

.qa-btn {
  flex: 0 0 auto; display: flex; flex-direction: column; align-items: center;
  gap: 7px; background: var(--surface); border: 1px solid var(--border);
  border-radius: var(--r); padding: 14px 18px; cursor: pointer;
  text-decoration: none; color: var(--text); min-width: 76px;
  transition: border-color .2s, box-shadow .2s, transform .15s;
}
.qa-btn:hover {
  border-color: var(--blue); box-shadow: 0 0 0 3px rgba(37,99,235,.08);
  transform: translateY(-2px); color: var(--text); text-decoration: none;
}
.qa-icon {
  width: 38px; height: 38px; border-radius: 10px;
  display: flex; align-items: center; justify-content: center; font-size: .95rem;
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
  display: flex; align-items: flex-start; gap: 12px;
  padding: 13px 0; border-bottom: 1px solid var(--border2);
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
.insight-dot.muted  { background: var(--muted); }
.insight-text { font-size: .82rem; line-height: 1.5; color: var(--sub); }
.insight-text strong { color: var(--text); font-weight: 600; }

/* ══ KPI Hero (Ingresos / Gastos — protagonistas) ═══════ */
.kpi-hero-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 14px;
}
@media (max-width: 480px) { .kpi-hero-row { grid-template-columns: 1fr; } }

.kpi-hero {
  background: var(--surface);
  border: 1px solid var(--border2);
  border-radius: var(--r);
  box-shadow: var(--shadow);
  padding: 24px 22px 20px;
  position: relative;
  overflow: hidden;
  animation: fadeUp .3s ease both;
}
.kpi-hero::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 4px;
  border-radius: var(--r) var(--r) 0 0;
}
.kpi-hero.income::before  { background: var(--green); }
.kpi-hero.expense::before { background: var(--amber); }

.kpi-hero-bg {
  position: absolute;
  right: 16px; bottom: 10px;
  font-size: 4.5rem;
  opacity: .055;
  line-height: 1;
  pointer-events: none;
  font-family: var(--mono);
  font-weight: 700;
}

.kpi-hero-lbl {
  font-size: .67rem; font-weight: 700; letter-spacing: .1em;
  text-transform: uppercase; color: var(--muted); margin-bottom: 10px;
  display: flex; align-items: center; gap: 6px;
}
.kpi-hero-lbl i { font-size: .75rem; }

.kpi-hero-val {
  font-family: var(--mono);
  font-size: clamp(1.25rem, 3.5vw, 2rem);
  font-weight: 700;
  line-height: 1.1;
  letter-spacing: -.02em;
  word-break: break-all;
}
.kpi-hero-val.income  { color: var(--green); }
.kpi-hero-val.expense { color: var(--amber); }

.kpi-hero-sub {
  font-size: .7rem; color: var(--muted);
  margin-top: 8px; font-family: var(--mono);
}

.kpi-hero-bar {
  margin-top: 14px;
  height: 5px;
  border-radius: 99px;
  background: var(--bg);
  overflow: hidden;
}
.kpi-hero-bar-fill {
  height: 100%; border-radius: 99px;
  transition: width .7s cubic-bezier(.4,0,.2,1);
}
.kpi-hero-bar-fill.income  { background: linear-gradient(90deg, #059669, #34d399); }
.kpi-hero-bar-fill.expense { background: linear-gradient(90deg, #d97706, #fbbf24); }

.kpi-hero-bar-label {
  font-size: .64rem; color: var(--muted);
  margin-top: 5px; font-family: var(--mono);
}

/* ══ KPIs secundarios ════════════════════════════════════ */
.kpi-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 12px;
}
@media (min-width: 600px)  { .kpi-grid { grid-template-columns: repeat(2, 1fr); } }
@media (min-width: 768px)  { .kpi-grid { grid-template-columns: repeat(4, 1fr); } }
@media (min-width: 1024px) { .kpi-grid { grid-template-columns: repeat(4, 1fr); gap: 14px; } }

.kpi {
  background: var(--surface); border: 1px solid var(--border2);
  border-radius: var(--r); box-shadow: var(--shadow);
  padding: 16px; display: flex; flex-direction: column; gap: 5px;
  position: relative; overflow: hidden; animation: fadeUp .35s ease both;
}
.kpi::before {
  content: ''; position: absolute; top: 0; left: 0; right: 0;
  height: 3px; border-radius: var(--r) var(--r) 0 0;
}
.kpi.blue::before   { background: var(--blue); }
.kpi.green::before  { background: var(--green); }
.kpi.red::before    { background: var(--red); }
.kpi.amber::before  { background: var(--amber); }
.kpi.purple::before { background: var(--purple); }
.kpi.muted::before  { background: var(--muted); }

.kpi-lbl {
  font-size: .63rem; font-weight: 700; letter-spacing: .09em;
  text-transform: uppercase; color: var(--muted);
}
.kpi-val {
  font-family: var(--mono); font-size: clamp(.78rem, 2vw, 1rem);
  font-weight: 600; line-height: 1.2; word-break: break-all;
}
.kpi-val.blue   { color: var(--blue); }
.kpi-val.green  { color: var(--green); }
.kpi-val.red    { color: var(--red); }
.kpi-val.amber  { color: var(--amber); }
.kpi-val.purple { color: var(--purple); }
.kpi-sub {
  font-family: var(--mono); font-size: .63rem; color: var(--muted);
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}

/* ══ Gráficas ════════════════════════════════════════════ */
.chart-wrap { position: relative; width: 100%; }

/* ══ Encabezado interno ══════════════════════════════════ */
.sec-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
.sec-title { font-size: .82rem; font-weight: 700; display: flex; align-items: center; gap: 7px; }
.sec-link {
  font-size: .72rem; font-weight: 600; color: var(--blue); text-decoration: none;
  padding: 4px 10px; border: 1px solid rgba(37,99,235,.25); border-radius: 6px; transition: all .2s;
}
.sec-link:hover { background: var(--blue); color: #fff; border-color: var(--blue); }

/* ══ Layouts ══════════════════════════════════════════════ */
.two-col { display: grid; grid-template-columns: 1fr; gap: 14px; }
@media (min-width: 900px) { .two-col { grid-template-columns: 5fr 7fr; } }

.three-col { display: grid; grid-template-columns: 1fr; gap: 14px; }
@media (min-width: 700px)  { .three-col { grid-template-columns: 1fr 1fr; } }
@media (min-width: 1024px) { .three-col { grid-template-columns: 1fr 1fr 1fr; } }

/* ══ Tabla transacciones ════════════════════════════════ */
.tx-tbl { width: 100%; border-collapse: collapse; font-size: .8rem; }
.tx-tbl th {
  font-size: .64rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase;
  color: var(--muted); padding: 0 10px 10px; text-align: left; border-bottom: 1px solid var(--border);
}
.tx-tbl td { padding: 10px; border-bottom: 1px solid var(--border2); vertical-align: middle; }
.tx-tbl tr:last-child td { border-bottom: none; }
.tx-tbl tr:hover td { background: #fafaf8; }

.tag {
  display: inline-block; font-family: var(--mono); font-size: .65rem; font-weight: 400;
  background: var(--bg); border: 1px solid var(--border); color: var(--sub);
  padding: 2px 7px; border-radius: 5px;
}

/* ══ Items deuda / recordatorio ═════════════════════════ */
.item {
  display: flex; justify-content: space-between; align-items: flex-start;
  padding: 11px 0; border-bottom: 1px solid var(--border2); gap: 10px;
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
  background-size: 200% 100%; animation: shimmer 1.4s infinite; border-radius: 6px;
}
@keyframes shimmer { to { background-position: -200% 0; } }
.sk-line { height: 13px; margin-bottom: 8px; }
.sk-val  { height: 28px; margin-bottom: 4px; width: 65%; }
.sk-hero { height: 44px; margin-bottom: 4px; width: 80%; }
.sk-sm   { height: 10px; width: 45%; }

/* ══ Estado vacío ════════════════════════════════════════ */
.empty { text-align: center; padding: 28px 0; color: var(--muted); font-size: .8rem; }
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
  display: flex; align-items: center; gap: 12px; font-size: .78rem; color: var(--sub);
  padding: 12px 16px; background: #fffbeb; border: 1px solid #fde68a;
  border-radius: var(--r); line-height: 1.5;
}
.tip i { color: var(--amber); flex-shrink: 0; }
</style>

<!-- ════════════════════════════════════════════
     HTML
════════════════════════════════════════════ -->

  <!-- ── Notificación de recordatorios pendientes ── -->
  <div id="reminder-notif">
    <i class="fas fa-bell notif-icon"></i>
    <span class="notif-text">
      Tienes <strong id="notif-count">0</strong> recordatorio(s) pendiente(s).
      <a href="?module=reminders">Ver recordatorios →</a>
    </span>
    <button class="notif-close" onclick="dismissReminderNotif()" title="Cerrar">
      <i class="fas fa-times"></i>
    </button>
  </div>

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

  <!-- ── TIP DEL DÍA ── -->
  <div class="db-section">
    <div class="tip">
      <i class="fas fa-lightbulb"></i>
      <span id="tip-text"></span>
    </div>
  </div>

  <!-- ── ACCIONES RÁPIDAS ── -->
  <div class="db-section">
    <div class="card-label" style="margin-bottom:10px"><i class="fas fa-bolt"></i> Acciones Rápidas</div>
    <div class="qa-scroll">
      <button onclick="DashQuickTx.open()" class="qa-btn" style="border:none;cursor:pointer">
        <div class="qa-icon blue"><i class="fas fa-plus"></i></div>
        <span class="qa-label">Nueva<br>Transacción</span>
      </button>
      <a href="?module=accounts" class="qa-btn">
        <div class="qa-icon purple"><i class="fas fa-wallet"></i></div>
        <span class="qa-label">Ver<br>Cuentas</span>
      </a>
      <a href="?module=reminders" class="qa-btn">
        <div class="qa-icon amber"><i class="fas fa-bell"></i></div>
        <span class="qa-label">Recordatorio</span>
      </a>
      <a href="?module=debts" class="qa-btn">
        <div class="qa-icon red"><i class="fas fa-hand-holding-usd"></i></div>
        <span class="qa-label">Pagar<br>Deuda</span>
      </a>
      <a href="?module=goals" class="qa-btn">
        <div class="qa-icon green"><i class="fas fa-piggy-bank"></i></div>
        <span class="qa-label">Nueva<br>Meta</span>
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

  <!-- ── KPIs HERO: Ingresos y Gastos (protagonistas) ── -->
  <div class="db-section">
    <div class="kpi-hero-row" id="kpi-hero-row">
      <!-- Skeleton: Ingresos -->
      <div class="kpi-hero income">
        <div class="kpi-hero-lbl"><i class="fas fa-arrow-down"></i> Ingresos del Período</div>
        <div class="sk sk-hero"></div>
        <div class="sk sk-sm" style="margin-top:6px"></div>
        <div class="kpi-hero-bar"><div class="kpi-hero-bar-fill income" style="width:0%"></div></div>
      </div>
      <!-- Skeleton: Gastos -->
      <div class="kpi-hero expense">
        <div class="kpi-hero-lbl"><i class="fas fa-arrow-up"></i> Gastos del Período</div>
        <div class="sk sk-hero"></div>
        <div class="sk sk-sm" style="margin-top:6px"></div>
        <div class="kpi-hero-bar"><div class="kpi-hero-bar-fill expense" style="width:0%"></div></div>
      </div>
    </div>
  </div>

  <!-- ── KPIs SECUNDARIOS (4) ── -->
  <div class="db-section">
    <div class="kpi-grid" id="kpi-grid">
      <?php for($i=0;$i<4;$i++): ?>
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
     MODAL NUEVA TRANSACCIÓN
════════════════════════════════════════════ -->
<div class="modal fade" id="dashTxModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content" style="border:none;border-radius:14px;box-shadow:0 20px 60px rgba(0,0,0,.12)">
      <div class="modal-header" style="padding:18px 24px 12px;border-bottom:1px solid #f3f4f6">
        <h5 class="modal-title" style="font-size:16px;font-weight:500">Nueva Transacción</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="padding:20px 24px">
        <div class="mb-3">
          <label class="dtx-form-label">Tipo</label>
          <div class="dtx-type-row">
            <label class="dtx-type-card dtx-income-card" id="dtx-labelIncome">
              <input type="radio" name="dtx-type" value="income" class="d-none" id="dtx-typeIncome">
              <span class="dtx-type-icon dtx-icon-inc">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 8v8M8.5 14.5l3.5 3.5 3.5-3.5"/></svg>
              </span>
              <span class="dtx-type-text" style="color:#1D9E75">Ingreso</span>
            </label>
            <label class="dtx-type-card dtx-expense-card dtx-selected" id="dtx-labelExpense">
              <input type="radio" name="dtx-type" value="expense" class="d-none" id="dtx-typeExpense" checked>
              <span class="dtx-type-icon dtx-icon-exp">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 16V8M8.5 11.5l3.5-3.5 3.5 3.5"/></svg>
              </span>
              <span class="dtx-type-text" style="color:#D85A30">Gasto</span>
            </label>
            <label class="dtx-type-card dtx-transfer-card" id="dtx-labelTransfer">
              <input type="radio" name="dtx-type" value="transfer" class="d-none" id="dtx-typeTransfer">
              <span class="dtx-type-icon dtx-icon-trf">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 8h14M15 4l4 4-4 4"/><path d="M19 16H5M9 12l-4 4 4 4"/></svg>
              </span>
              <span class="dtx-type-text" style="color:#3b82f6">Transferencia</span>
            </label>
          </div>
        </div>
        <div class="mb-3">
          <label class="dtx-form-label" id="dtx-labelOrigin">Cuenta</label>
          <div class="dtx-acc-grid" id="dtx-acc-origin">
            <div style="color:#9ca3af;font-size:13px;padding:10px 0">Cargando cuentas…</div>
          </div>
          <input type="hidden" id="dtx-account-id">
        </div>
        <div class="mb-3 d-none" id="dtx-transferToDiv">
          <label class="dtx-form-label">Cuenta destino</label>
          <div class="dtx-acc-grid" id="dtx-acc-dest"></div>
          <input type="hidden" id="dtx-transfer-to">
        </div>
        <div class="mb-3" id="dtx-categoryDiv">
          <label class="dtx-form-label">Categoría</label>
          <select id="dtx-category-id" class="dtx-form-control"></select>
        </div>
        <div class="mb-3">
          <label class="dtx-form-label">Monto</label>
          <input type="number" step="0.01" id="dtx-amount" class="dtx-form-control" placeholder="0.00">
        </div>
        <div class="mb-1">
          <label class="dtx-form-label">Descripción <span style="font-weight:400;color:#aaa">(opcional)</span></label>
          <textarea id="dtx-description" class="dtx-form-control" rows="2" placeholder="Ej: Compra en supermercado…"></textarea>
        </div>
        <input type="hidden" id="dtx-date">
      </div>
      <div class="modal-footer" style="padding:14px 24px;border-top:1px solid #f0f0f0">
        <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" onclick="DashQuickTx.save()"
          style="padding:8px 22px;background:#374151;color:#fff;border:none;border-radius:8px;font-size:14px;font-weight:500;cursor:pointer">
          Guardar
        </button>
      </div>
    </div>
  </div>
</div>

<style>
.dtx-form-label {
  display:block;font-size:12px;font-weight:500;color:#6b7280;
  text-transform:uppercase;letter-spacing:.04em;margin-bottom:5px;
}
.dtx-form-control {
  width:100%;font-size:14px;padding:8px 12px;
  border:1px solid #e5e7eb;border-radius:8px;
  background:#fafafa;color:#374151;outline:none;
  transition:border-color .15s,background .15s;font-family:inherit;
}
.dtx-form-control:focus { border-color:#a5b4fc;background:#fff;box-shadow:0 0 0 3px rgba(165,180,252,.15); }
.dtx-type-row { display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px; }
.dtx-type-card {
  cursor:pointer;border-radius:10px;border:2px solid #e5e7eb;
  background:#f9fafb;display:flex;flex-direction:column;align-items:center;
  justify-content:center;padding:12px 6px 10px;gap:7px;
  transition:border-color .2s,background .2s,box-shadow .2s;user-select:none;
}
.dtx-type-card:hover { background:#f3f4f6; }
.dtx-type-icon {
  width:44px;height:44px;border-radius:50%;
  display:flex;align-items:center;justify-content:center;transition:transform .15s;
}
.dtx-type-card:hover .dtx-type-icon { transform:scale(1.05); }
.dtx-icon-inc { background:#eaf3de;color:#1D9E75; }
.dtx-icon-exp { background:#faece7;color:#D85A30; }
.dtx-icon-trf { background:#eff6ff;color:#3b82f6; }
.dtx-type-text { font-size:12px;font-weight:600; }
.dtx-income-card.dtx-selected   { border-color:#1D9E75;background:#eaf3de;box-shadow:0 0 0 3px rgba(29,158,117,.1); }
.dtx-expense-card.dtx-selected  { border-color:#D85A30;background:#faece7;box-shadow:0 0 0 3px rgba(216,90,48,.1); }
.dtx-transfer-card.dtx-selected { border-color:#3b82f6;background:#eff6ff;box-shadow:0 0 0 3px rgba(59,130,246,.1); }
.dtx-acc-grid {
  display:flex;flex-wrap:nowrap;gap:7px;overflow-x:auto;padding-bottom:4px;
  scrollbar-width:none;-ms-overflow-style:none;
}
.dtx-acc-grid::-webkit-scrollbar { display:none; }
.dtx-acc-card {
  cursor:pointer;border-radius:10px;border:2px solid #e5e7eb;background:#f9fafb;
  padding:10px 12px;transition:border-color .18s,background .18s,box-shadow .18s;
  user-select:none;position:relative;overflow:hidden;flex:0 0 140px;
}
.dtx-acc-card:hover { background:#f3f4f6;border-color:#d1d5db; }
.dtx-acc-card.dtx-sel-origin { border-color:#374151;background:#f8f9fb;box-shadow:0 0 0 3px rgba(55,65,81,.1); }
.dtx-acc-card.dtx-sel-origin::after {
  content:'';position:absolute;top:6px;right:6px;
  width:8px;height:8px;border-radius:50%;background:#374151;
}
.dtx-acc-card.dtx-sel-dest { border-color:#3b82f6;background:#eff6ff;box-shadow:0 0 0 3px rgba(59,130,246,.1); }
.dtx-acc-card.dtx-sel-dest::after {
  content:'';position:absolute;top:6px;right:6px;
  width:8px;height:8px;border-radius:50%;background:#3b82f6;
}
.dtx-acc-name { font-size:12px;font-weight:600;color:#1f2937;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-bottom:3px; }
.dtx-acc-bal  { font-size:11px;color:#6b7280;white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
.dtx-acc-cur  { font-size:9px;color:#9ca3af;text-transform:uppercase;letter-spacing:.05em;margin-top:2px;font-weight:600; }
</style>

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
const fmtUSD = v => 'US$ ' + Number(v).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

/* ── Fecha local (evita el bug de UTC/toISOString) ──────
   toISOString() convierte a UTC, lo que puede adelantar
   un día en zonas horarias negativas como DR (UTC-4).   */
const _pad = n => String(n).padStart(2, '0');
const toLocalDate = d =>
  `${d.getFullYear()}-${_pad(d.getMonth() + 1)}-${_pad(d.getDate())}`;

/* ── Saludo según hora ────────────────────────────────── */
const hr = new Date().getHours();
$('db-greeting').textContent = hr < 12 ? 'Buenos días' : hr < 19 ? 'Buenas tardes' : 'Buenas noches';

/* ── Tip del día ─────────────────────────────────────── */
const TIPS = [
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
$('tip-text').textContent = TIPS[Math.floor(Math.random() * TIPS.length)];

/* ── Estado del período ──────────────────────────────── */
let period   = 'current_month';
let dateFrom = '';
let dateTo   = '';

function payload() {
  const p = { period };
  if (period === 'custom') { p.date_from = dateFrom; p.date_to = dateTo; }
  return p;
}

/* ── KPIs ─────────────────────────────────────────────── */
function renderKPIs() {
  // Reset hero skeletons
  $('kpi-hero-row').innerHTML = `
    <div class="kpi-hero income">
      <div class="kpi-hero-lbl"><i class="fas fa-arrow-down"></i> Ingresos del Período</div>
      <div class="sk sk-hero"></div>
      <div class="sk sk-sm" style="margin-top:6px"></div>
      <div class="kpi-hero-bar"><div class="kpi-hero-bar-fill income" style="width:0%"></div></div>
    </div>
    <div class="kpi-hero expense">
      <div class="kpi-hero-lbl"><i class="fas fa-arrow-up"></i> Gastos del Período</div>
      <div class="sk sk-hero"></div>
      <div class="sk sk-sm" style="margin-top:6px"></div>
      <div class="kpi-hero-bar"><div class="kpi-hero-bar-fill expense" style="width:0%"></div></div>
    </div>`;

  // Reset secondary skeletons
  $('kpi-grid').innerHTML = Array(4).fill(0).map(() =>
    `<div class="kpi muted"><div class="sk sk-line" style="width:55%"></div><div class="sk sk-val"></div><div class="sk sk-sm"></div></div>`
  ).join('');

  post('get_kpis', payload()).then(d => {
    if (!d.success) return;
    $('range-label').textContent = `${fmtd(d.date_from)} – ${fmtd(d.date_to)}`;

    /* ─── HERO: Ingresos y Gastos ─────────────────────── */
    const maxVal   = Math.max(d.total_income, d.total_expense, 1);
    const incBar   = Math.min(100, (d.total_income / maxVal) * 100).toFixed(1);
    const expBar   = Math.min(100, (d.total_expense / maxVal) * 100).toFixed(1);
    const expRatio = d.total_income > 0
      ? ((d.total_expense / d.total_income) * 100).toFixed(1)
      : '—';

    $('kpi-hero-row').innerHTML = `
      <div class="kpi-hero income fade-up">
        <div class="kpi-hero-bg">↑</div>
        <div class="kpi-hero-lbl"><i class="fas fa-arrow-down"></i> Ingresos del Período</div>
        <div class="kpi-hero-val income">${fmt(d.total_income)}</div>
        <div class="kpi-hero-sub">Período seleccionado</div>
        <div class="kpi-hero-bar">
          <div class="kpi-hero-bar-fill income" style="width:${incBar}%"></div>
        </div>
        <div class="kpi-hero-bar-label">${incBar}% del mayor valor</div>
      </div>
      <div class="kpi-hero expense fade-up" style="animation-delay:.06s">
        <div class="kpi-hero-bg">↓</div>
        <div class="kpi-hero-lbl"><i class="fas fa-arrow-up"></i> Gastos del Período</div>
        <div class="kpi-hero-val expense">${fmt(d.total_expense)}</div>
        <div class="kpi-hero-sub">${expRatio !== '—' ? expRatio + '% de los ingresos' : 'Sin ingresos registrados'}</div>
        <div class="kpi-hero-bar">
          <div class="kpi-hero-bar-fill expense" style="width:${expBar}%"></div>
        </div>
        <div class="kpi-hero-bar-label">${expBar}% del mayor valor</div>
      </div>`;

    /* ─── KPIs secundarios (4) ────────────────────────── */
    const netColor = d.net_cashflow >= 0 ? 'green' : 'red';
    const netLabel = d.net_cashflow >= 0 ? 'Superávit' : 'Déficit';

    $('kpi-grid').innerHTML = `
      <div class="kpi blue fade-up" style="animation-delay:.00s">
        <div class="kpi-lbl">Balance Total</div>
        <div class="kpi-val blue">${fmt(d.total_dop)}</div>
        <div class="kpi-sub">≈ US$ ${Number(d.total_usd).toLocaleString('en-US',{minimumFractionDigits:2})}</div>
      </div>
      <div class="kpi ${netColor} fade-up" style="animation-delay:.10s">
        <div class="kpi-lbl">Flujo Neto</div>
        <div class="kpi-val ${netColor}">${fmt(Math.abs(d.net_cashflow))}</div>
        <div class="kpi-sub">${netLabel}</div>
      </div>
      <div class="kpi red fade-up" style="animation-delay:.05s">
        <div class="kpi-lbl">Total Adeudado DOP</div>
        <div class="kpi-val red">${fmt(d.total_owed)}</div>
        <div class="kpi-sub">Deudas + Tarjetas</div>
      </div>
      <div class="kpi amber fade-up" style="animation-delay:.15s">
        <div class="kpi-lbl">Total Adeudado USD</div>
        <div class="kpi-val amber">${fmtUSD(d.total_owed_usd)}</div>
        <div class="kpi-sub">Tarjetas + Deudas</div>
      </div>`;

    // Renderizar perspectiva con los datos ya disponibles
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

  // 2. Deuda total (incluye tarjetas de crédito + deudas)
  if (d.total_owed > 0) {
    const pct = d.total_dop > 0 ? ((d.total_owed / d.total_dop) * 100).toFixed(0) : '—';
    const owedUSD = d.total_owed_usd > 0 ? ` ${fmtUSD(d.total_owed_usd)}` : '';
    insights.push({ dot: 'amber', text: `Tienes <strong>${fmt(d.total_owed)} - ${owedUSD} </strong>en deudas y tarjetas${pct !== '—' ? ', equivalente al ' + pct + '% de tu balance' : ''}.` });
  } else {
    insights.push({ dot: 'green', text: '¡Sin saldo deudor en tarjetas ni deudas pendientes! Buen trabajo.' });
  }

  // 3. Gasto promedio diario
  if (d.total_expense > 0 && d.date_from && d.date_to) {
    const fromDate = new Date(d.date_from + 'T00:00:00');
    const toDate   = new Date(d.date_to   + 'T00:00:00');
    const days     = Math.max(1, Math.round((toDate - fromDate) / 86400000) + 1);
    const dailyAvg = d.total_expense / days;
    insights.push({ dot: 'blue', text: `Tienes un gasto promedio de <strong>${fmt(dailyAvg)}</strong> diario.` });
  } else {
    insights.push({ dot: 'muted', text: 'Sin gastos en el período. Empieza registrando tus movimientos.' });
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
    const empty  = $('cat-empty');
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
    const ctx  = $('chartBalance').getContext('2d');
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

/* ── POST helper ─────────────────────────────────────  */
function post(action, extra = {}) {
  const fd = new FormData();
  fd.append('action', action);
  for (const [k, v] of Object.entries(extra)) fd.append(k, v);
  return fetch(AJAX, { method: 'POST', body: fd }).then(r => r.json());
}

/* ── Notificación de recordatorios pendientes ───────────
   Muestra una vez cada 5 horas usando localStorage.
   Si hay recordatorios pendientes, aparece un banner
   en la parte superior del dashboard.                    */
const NOTIF_KEY      = 'reminder_dismissed_at';
const NOTIF_COOLDOWN = 5 * 60 * 60 * 1000; // 5 horas en ms

function checkReminderNotif() {
  try {
    const lastDismissed = parseInt(localStorage.getItem(NOTIF_KEY) || '0');
    if (Date.now() - lastDismissed < NOTIF_COOLDOWN) return; // aún en cooldown
  } catch (e) { /* localStorage no disponible */ return; }

  post('get_reminders').then(d => {
    if (!d.success || !d.reminders || !d.reminders.length) return;
    $('notif-count').textContent = d.reminders.length;
    const notif = $('reminder-notif');
    notif.style.display = 'flex';
  });
}

function dismissReminderNotif() {
  try { localStorage.setItem(NOTIF_KEY, String(Date.now())); } catch (e) {}
  const notif = $('reminder-notif');
  if (notif) { notif.style.opacity = '0'; notif.style.transition = 'opacity .3s'; setTimeout(() => notif.style.display = 'none', 300); }
}

/* ── Carga principal ─────────────────────────────────  */
function load() {
  renderKPIs();
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

// ✅ Usa fecha LOCAL para evitar el bug de UTC/toISOString
const _today = new Date();
dateToEl.value   = toLocalDate(_today);
dateFromEl.value = toLocalDate(new Date(_today.getFullYear(), _today.getMonth(), 1));

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
checkReminderNotif();

/* ══════════════════════════════════════════════════════
   DashQuickTx — Modal de transacción rápida en dashboard
══════════════════════════════════════════════════════ */
const DashQuickTx = (() => {
  const AJAX = 'ajax/transactions.php';

  let _accounts  = [];
  let _incCats   = [];
  let _expCats   = [];
  let _modalInst = null;

  function esc(s) {
    return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }
  function fmtBal(n) {
    return parseFloat(n).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2});
  }
  function err(msg) {
    Swal.fire({title:'Error',text:msg,icon:'error',toast:true,position:'top-start',
      showConfirmButton:false,timer:4500,timerProgressBar:true});
  }
  function ok(msg) {
    Swal.fire({title:'¡Listo!',text:msg,icon:'success',toast:true,position:'top-start',
      showConfirmButton:false,timer:3000,timerProgressBar:true});
  }

  async function loadFormData() {
    try {
      const res  = await fetch(`${AJAX}?action=get_form_data`);
      const data = await res.json();
      if (!data.success) throw new Error(data.message);
      _accounts = data.accounts;
      _incCats  = data.income_categories;
      _expCats  = data.expense_categories;
    } catch(e) {
      err('No se pudieron cargar cuentas/categorías: ' + e.message);
    }
  }

  function renderAccCards(containerId, hiddenId, selId, excludeId, isDest) {
    const wrap   = document.getElementById(containerId);
    const hidden = document.getElementById(hiddenId);
    if (!wrap || !hidden) return;

    const list = excludeId
      ? _accounts.filter(a => parseInt(a.id) !== parseInt(excludeId))
      : _accounts;

    if (!list.length) {
      wrap.innerHTML = '<div style="color:#9ca3af;font-size:13px;padding:10px 0">Sin cuentas disponibles.</div>';
      hidden.value = '';
      return;
    }

    const selCls = isDest ? 'dtx-sel-dest' : 'dtx-sel-origin';
    wrap.innerHTML = list.map(a => {
      const isSel = selId !== null && parseInt(a.id) === parseInt(selId);
      return `<div class="dtx-acc-card ${isSel ? selCls : ''}"
                   data-id="${a.id}"
                   onclick="DashQuickTx.pickAcc('${containerId}','${hiddenId}',${a.id},${isDest})">
                <div class="dtx-acc-name">${esc(a.name)}</div>
                <div class="dtx-acc-bal">${esc(a.symbol||'')} ${fmtBal(a.balance)}</div>
                <div class="dtx-acc-cur">${esc(a.currency_code)}</div>
              </div>`;
    }).join('');

    const valid = list.find(a => parseInt(a.id) === parseInt(selId));
    hidden.value = valid ? selId : '';
  }

  function pickAcc(containerId, hiddenId, accountId, isDest) {
    const selCls = isDest ? 'dtx-sel-dest' : 'dtx-sel-origin';
    document.querySelectorAll(`#${containerId} .dtx-acc-card`)
      .forEach(c => c.classList.remove('dtx-sel-origin','dtx-sel-dest'));
    const card = document.querySelector(`#${containerId} [data-id="${accountId}"]`);
    if (card) card.classList.add(selCls);
    document.getElementById(hiddenId).value = accountId;

    if (containerId === 'dtx-acc-origin') {
      const type = document.querySelector('input[name="dtx-type"]:checked')?.value;
      if (type === 'transfer') {
        const curDest = document.getElementById('dtx-transfer-to').value || null;
        const newDest = (curDest && parseInt(curDest) === parseInt(accountId)) ? null : curDest;
        renderAccCards('dtx-acc-dest','dtx-transfer-to', newDest ? parseInt(newDest) : null, accountId, true);
      }
    }
  }

  function buildCatSelect(type) {
    const sel = document.getElementById('dtx-category-id');
    sel.innerHTML = '<option value="">Seleccionar categoría</option>';
    const cats = type === 'income' ? _incCats : _expCats;
    cats.forEach(c => {
      const o = document.createElement('option');
      o.value = c.id; o.textContent = c.name;
      sel.appendChild(o);
    });
    if (cats.length) sel.value = cats[0].id;
  }

  function onTypeChange(type) {
    const isTransfer = type === 'transfer';
    document.getElementById('dtx-transferToDiv').classList.toggle('d-none', !isTransfer);
    document.getElementById('dtx-categoryDiv').classList.toggle('d-none', isTransfer);
    document.getElementById('dtx-labelOrigin').textContent = isTransfer ? 'Cuenta origen' : 'Cuenta';
    if (isTransfer) {
      document.getElementById('dtx-category-id').value = '1';
      const originId = document.getElementById('dtx-account-id').value || null;
      renderAccCards('dtx-acc-dest','dtx-transfer-to', null, originId ? parseInt(originId) : null, true);
    } else {
      buildCatSelect(type);
    }
  }

  async function open() {
    if (!_accounts.length) await loadFormData();

    document.querySelectorAll('.dtx-type-card').forEach(c => c.classList.remove('dtx-selected'));
    document.getElementById('dtx-labelExpense').classList.add('dtx-selected');
    document.getElementById('dtx-typeExpense').checked = true;

    document.getElementById('dtx-transferToDiv').classList.add('d-none');
    document.getElementById('dtx-categoryDiv').classList.remove('d-none');
    document.getElementById('dtx-labelOrigin').textContent = 'Cuenta';
    document.getElementById('dtx-amount').value      = '';
    document.getElementById('dtx-description').value = '';

    // ✅ Fecha local correcta (sin bug de UTC)
    document.getElementById('dtx-date').value = toLocalDate(new Date());

    renderAccCards('dtx-acc-origin','dtx-account-id', null, null, false);
    buildCatSelect('expense');

    if (!_modalInst) {
      _modalInst = new bootstrap.Modal(document.getElementById('dashTxModal'));
    }
    _modalInst.show();
  }

  async function save() {
    const type       = document.querySelector('input[name="dtx-type"]:checked')?.value;
    const accountId  = document.getElementById('dtx-account-id').value;
    const transferTo = document.getElementById('dtx-transfer-to').value;
    const categoryId = document.getElementById('dtx-category-id').value;
    const amount     = document.getElementById('dtx-amount').value;
    const date       = document.getElementById('dtx-date').value;
    const desc       = document.getElementById('dtx-description').value;

    if (!accountId) { err('Selecciona una cuenta de origen.'); return; }
    if (type === 'transfer' && !transferTo) { err('Selecciona una cuenta destino.'); return; }
    if (!amount || parseFloat(amount) <= 0) { err('El monto debe ser mayor a 0.'); return; }

    try {
      const params = new URLSearchParams({
        action: 'add_transaction',
        account_id: accountId, category_id: categoryId,
        type, amount, date, description: desc,
        transfer_to: transferTo, payment_currency: '',
      });
      const res  = await fetch(AJAX, {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: params,
      });
      const data = await res.json();
      if (!data.success) throw new Error(data.message);

      ok(data.message);
      _modalInst?.hide();
      load();
    } catch(e) {
      err(e.message);
    }
  }

  document.querySelectorAll('.dtx-type-card').forEach(card => {
    card.addEventListener('click', () => {
      document.querySelectorAll('.dtx-type-card').forEach(c => c.classList.remove('dtx-selected'));
      card.classList.add('dtx-selected');
      card.querySelector('input[type="radio"]').checked = true;
      onTypeChange(card.querySelector('input[type="radio"]').value);
    });
  });

  return { open, save, pickAcc };
})();
</script>