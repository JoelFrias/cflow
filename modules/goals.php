<!-- modules/goals.php -->

<!-- ============================================ -->
<!-- MÓDULO DE METAS — DISEÑO LIMPIO              -->
<!-- ============================================ -->
<div id="goals-module">

    <!-- ── Barra superior ── -->
    <div class="gl-top-bar">
        <div>
            <div class="gl-top-label">Metas de Ahorro</div>
            <div class="gl-top-sub" id="gl-summary-text">—</div>
        </div>
        <button class="gl-btn-new" data-bs-toggle="modal" data-bs-target="#createGoalModal">
            <span style="font-size:16px;line-height:1">+</span> Nueva Meta
        </button>
    </div>

    <!-- ── Grid de metas ── -->
    <div id="goals-list">
        <div class="gl-loading">
            <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
            <span>Cargando metas…</span>
        </div>
    </div>

</div>

<!-- ============================================ -->
<!-- MODAL: CREAR META                            -->
<!-- ============================================ -->
<div class="modal fade" id="createGoalModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content gl-modal-content">
            <div class="modal-header gl-modal-header">
                <h5 class="modal-title" style="font-size:16px;font-weight:500">Nueva Meta de Ahorro</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:20px 24px">
                <div class="mb-3">
                    <label class="gl-form-label">Nombre</label>
                    <input type="text" id="goal-name" class="form-control gl-form-control"
                           placeholder="Ej: Vacaciones, Laptop nueva, Fondo de emergencia">
                </div>
                <div class="mb-3">
                    <label class="gl-form-label">Monto objetivo (RD$)</label>
                    <input type="number" step="0.01" id="goal-target" class="form-control gl-form-control" placeholder="0.00">
                </div>
                <div class="mb-1">
                    <label class="gl-form-label">Fecha límite</label>
                    <input type="date" id="goal-deadline" class="form-control gl-form-control">
                </div>
                <div class="gl-info-note" style="margin-top:14px">
                    Puedes agregar abonos desde el botón <strong>Abonar</strong> en cada tarjeta.
                </div>
            </div>
            <div class="modal-footer" style="padding:14px 24px;border-top:1px solid #f0f0f0">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="gl-btn-save" id="btn-add-goal">
                    <span id="btn-goal-text">Crear Meta</span>
                    <span id="btn-goal-spinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- MODAL: AGREGAR ABONO                         -->
<!-- ============================================ -->
<div class="modal fade" id="addContributionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content gl-modal-content">
            <div class="modal-header gl-modal-header">
                <div>
                    <h5 class="modal-title" style="font-size:16px;font-weight:500">Abonar a meta</h5>
                    <div style="font-size:12px;color:#9ca3af;margin-top:2px" id="contribution-goal-name">—</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:20px 24px">
                <input type="hidden" id="contribution-goal-id">
                <div class="mb-3">
                    <label class="gl-form-label">Monto (RD$)</label>
                    <input type="number" step="0.01" id="contribution-amount" class="form-control gl-form-control" placeholder="0.00">
                </div>
                <div class="mb-3">
                    <label class="gl-form-label">Fecha</label>
                    <input type="date" id="contribution-date" class="form-control gl-form-control">
                </div>
                <div class="mb-1">
                    <label class="gl-form-label">Notas <span style="font-weight:400;color:#aaa">(opcional)</span></label>
                    <textarea id="contribution-notes" class="form-control gl-form-control" rows="2"
                              placeholder="Ej: Ahorro del mes, Bono extra…"></textarea>
                </div>
            </div>
            <div class="modal-footer" style="padding:14px 24px;border-top:1px solid #f0f0f0">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="gl-btn-save gl-btn-save-green" id="btn-add-contribution">
                    <span id="btn-contribution-text">Registrar Abono</span>
                    <span id="btn-contribution-spinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- MODAL: HISTORIAL DE ABONOS                   -->
<!-- ============================================ -->
<div class="modal fade" id="historyModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content gl-modal-content">
            <div class="modal-header gl-modal-header">
                <div>
                    <h5 class="modal-title" style="font-size:16px;font-weight:500" id="history-goal-name">Historial</h5>
                    <div style="font-size:12px;color:#9ca3af;margin-top:2px">Abonos registrados</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:20px 24px">

                <!-- Resumen del historial -->
                <div id="history-summary" style="display:none;grid-template-columns:1fr 1fr 1fr;gap:10px;margin-bottom:18px">
                    <div class="gl-sum-card">
                        <div class="gl-sum-label">Total ahorrado</div>
                        <div class="gl-sum-val" style="color:#1D9E75" id="hist-total">—</div>
                    </div>
                    <div class="gl-sum-card">
                        <div class="gl-sum-label">Abonos</div>
                        <div class="gl-sum-val" style="color:#374151" id="hist-count">—</div>
                    </div>
                    <div class="gl-sum-card">
                        <div class="gl-sum-label">Promedio</div>
                        <div class="gl-sum-val" style="color:#3b82f6" id="hist-avg">—</div>
                    </div>
                </div>

                <!-- Hint móvil -->
                <div class="gl-swipe-hint" id="histSwipeHint" style="display:none">
                    Desliza cada fila → para ver notas
                </div>

                <div id="history-content">
                    <div class="gl-loading">
                        <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
                        <span>Cargando historial…</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="padding:14px 24px;border-top:1px solid #f0f0f0">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- ESTILOS                                      -->
<!-- ============================================ -->
<style>
#goals-module {
    --gl-radius: 10px;
    --gl-border: #e8e8e8;
    --gl-inc: #1D9E75;
    --gl-exp: #D85A30;
    --gl-blue: #3b82f6;
    --gl-inc-bg: #eaf3de;
    --gl-inc-text: #3B6D11;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
}

/* ── Barra superior ── */
.gl-top-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #fff;
    border: 1px solid var(--gl-border);
    border-radius: var(--gl-radius);
    padding: 16px 20px;
    margin-bottom: 16px;
}
.gl-top-label {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: #9ca3af;
    margin-bottom: 4px;
}
.gl-top-sub {
    font-size: 14px;
    font-weight: 500;
    color: #374151;
}
.gl-btn-new {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 18px;
    background: #374151;
    color: #fff;
    border: none;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    white-space: nowrap;
    transition: background .15s;
}
.gl-btn-new:hover { background: #1f2937; }

/* ── Loading / empty ── */
.gl-loading {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 48px 0;
    color: #9ca3af;
    font-size: 14px;
}
.gl-empty {
    text-align: center;
    padding: 56px 20px;
    color: #9ca3af;
}
.gl-empty-icon { font-size: 36px; margin-bottom: 10px; opacity: .35; }
.gl-empty p { font-size: 14px; margin-bottom: 14px; }

/* ── Grid de metas ── */
.gl-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 10px;
}

/* ── Tarjeta de meta ── */
.gl-card {
    background: #fff;
    border: 1px solid var(--gl-border);
    border-radius: var(--gl-radius);
    padding: 18px;
    transition: box-shadow .15s, border-color .15s;
    position: relative;
    overflow: hidden;
}
.gl-card:hover {
    border-color: #d1d5db;
    box-shadow: 0 2px 14px rgba(0,0,0,.06);
}
.gl-card.completed {
    border-color: #86efac;
    background: linear-gradient(135deg, #fff, #f0fdf4);
}

/* Acento lateral de color */
.gl-card::before {
    content: '';
    position: absolute;
    left: 0; top: 0; bottom: 0;
    width: 4px;
    background: var(--gl-blue);
    border-radius: var(--gl-radius) 0 0 var(--gl-radius);
}
.gl-card.completed::before { background: var(--gl-inc); }
.gl-card.overdue::before   { background: var(--gl-exp); }

/* ── Header de tarjeta ── */
.gl-card-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 8px;
    margin-bottom: 12px;
}
.gl-card-info { flex: 1; min-width: 0; }
.gl-card-name {
    font-size: 15px;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 3px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.gl-card-deadline {
    font-size: 11px;
    color: #9ca3af;
    display: flex;
    align-items: center;
    gap: 4px;
}
.gl-pill {
    display: inline-flex;
    align-items: center;
    padding: 2px 8px;
    border-radius: 20px;
    font-size: 10px;
    font-weight: 600;
    letter-spacing: .03em;
}
.pill-done    { background: var(--gl-inc-bg); color: var(--gl-inc-text); }
.pill-overdue { background: #faece7; color: #993C1D; }
.pill-active  { background: #eff6ff; color: #1e40af; }

.gl-card-actions {
    display: flex;
    gap: 4px;
    flex-shrink: 0;
}
.gl-action-btn {
    height: 30px;
    padding: 0 10px;
    border-radius: 8px;
    border: 1px solid var(--gl-border);
    background: #f9fafb;
    color: #6b7280;
    font-size: 11px;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 4px;
    cursor: pointer;
    white-space: nowrap;
    transition: all .12s;
}
.gl-action-btn:hover { background: #f3f4f6; color: #374151; border-color: #d1d5db; }
.gl-action-btn.del:hover { background: #fef2f2; color: #dc2626; border-color: #fecaca; }
.gl-action-btn.abonar { background: #f0fdf4; color: var(--gl-inc-text); border-color: #86efac; }
.gl-action-btn.abonar:hover { background: #dcfce7; }

/* ── Barra de progreso ── */
.gl-progress-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 5px;
}
.gl-progress-pct {
    font-size: 12px;
    font-weight: 700;
    color: #374151;
}
.gl-progress-track {
    height: 8px;
    background: #f3f4f6;
    border-radius: 99px;
    overflow: hidden;
    margin-bottom: 14px;
}
.gl-progress-fill {
    height: 100%;
    border-radius: 99px;
    background: var(--gl-blue);
    transition: width .6s cubic-bezier(.4,0,.2,1);
}
.gl-progress-fill.done { background: var(--gl-inc); }
.gl-progress-fill.overdue { background: var(--gl-exp); }

/* ── Stats de la tarjeta ── */
.gl-stats {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 8px;
    margin-bottom: 12px;
}
.gl-stat {
    text-align: center;
    background: #f9fafb;
    border-radius: 8px;
    padding: 8px 4px;
}
.gl-stat-label {
    font-size: 9px;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: #9ca3af;
    margin-bottom: 3px;
}
.gl-stat-val {
    font-size: 13px;
    font-weight: 700;
    color: #1f2937;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.gl-stat-val.green { color: var(--gl-inc); }
.gl-stat-val.amber { color: #d97706; }
.gl-stat-val.red   { color: var(--gl-exp); }

/* ── Nota mensual ── */
.gl-monthly-note {
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    border-radius: 8px;
    padding: 8px 12px;
    font-size: 11px;
    color: #1e40af;
    line-height: 1.5;
}

/* ── Modal ── */
.gl-modal-content {
    border: none;
    border-radius: 14px;
    box-shadow: 0 20px 60px rgba(0,0,0,.12);
}
.gl-modal-header {
    padding: 18px 24px 12px;
    border-bottom: 1px solid #f3f4f6;
}
.gl-form-label {
    display: block;
    font-size: 11px !important;
    font-weight: 600 !important;
    color: #9ca3af !important;
    text-transform: uppercase;
    letter-spacing: .04em;
    margin-bottom: 5px !important;
}
.gl-form-control {
    font-size: 14px !important;
    padding: 8px 12px !important;
    border: 1px solid #e5e7eb !important;
    border-radius: 8px !important;
    background: #fafafa !important;
    color: #374151;
    width: 100%;
    outline: none;
    transition: border-color .15s, background .15s;
}
.gl-form-control:focus {
    border-color: #a5b4fc !important;
    background: #fff !important;
    box-shadow: 0 0 0 3px rgba(165,180,252,.15) !important;
}
.gl-info-note {
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    border-radius: 8px;
    padding: 10px 14px;
    font-size: 12px;
    color: #1e40af;
}
.gl-btn-save {
    padding: 8px 22px;
    background: #374151;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: background .15s;
}
.gl-btn-save:hover { background: #1f2937; }
.gl-btn-save-green { background: #15803d; }
.gl-btn-save-green:hover { background: #166534; }

/* ── Resumen historial ── */
.gl-sum-card {
    background: #f9fafb;
    border: 1px solid var(--gl-border);
    border-radius: var(--gl-radius);
    padding: 12px 14px;
}
.gl-sum-label {
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: #9ca3af;
    margin-bottom: 4px;
}
.gl-sum-val { font-size: 16px; font-weight: 700; }

/* ── Historial: hint móvil ── */
.gl-swipe-hint {
    text-align: center;
    font-size: 11px;
    color: #b0b7c3;
    margin-bottom: 8px;
    letter-spacing: .02em;
}

/* ── Historial: lista móvil deslizable ── */
.gl-hist-list { display: flex; flex-direction: column; gap: 2px; }
.gl-hist-item {
    border-radius: 8px;
    background: #fff;
    border: 1px solid var(--gl-border);
    overflow: hidden;
}
.gl-hist-scroll {
    display: flex;
    overflow-x: auto;
    scroll-snap-type: x mandatory;
    scrollbar-width: none;
    -ms-overflow-style: none;
}
.gl-hist-scroll::-webkit-scrollbar { display: none; }
.gl-hist-panel {
    flex: 0 0 100%;
    scroll-snap-align: start;
    padding: 11px 14px;
    display: flex;
    align-items: center;
    gap: 12px;
    min-height: 58px;
}
.gl-hist-panel-extra {
    flex: 0 0 100%;
    scroll-snap-align: start;
    padding: 11px 14px;
    display: flex;
    align-items: center;
    gap: 10px;
    background: #f8f9fb;
}
.gl-hist-dot {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: var(--gl-inc-bg);
    color: var(--gl-inc-text);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    font-weight: 700;
    flex-shrink: 0;
}
.gl-hist-main { flex: 1; min-width: 0; }
.gl-hist-date { font-size: 13px; font-weight: 500; color: #1f2937; }
.gl-hist-sub  { font-size: 11px; color: #9ca3af; margin-top: 2px; }
.gl-hist-amt  { font-size: 15px; font-weight: 700; color: var(--gl-inc); flex-shrink: 0; }
.gl-hist-extra-col { flex: 1; min-width: 0; }
.gl-ex-label {
    font-size: 9px; color: #9ca3af; text-transform: uppercase;
    letter-spacing: .05em; margin-bottom: 2px;
}
.gl-ex-val { font-size: 12px; color: #374151; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.gl-ex-divider { width: 1px; height: 28px; background: var(--gl-border); flex-shrink: 0; }
.gl-hist-dots {
    display: flex; align-items: center; justify-content: center;
    gap: 4px; padding: 4px 0 3px;
}
.gl-dot-ind { width: 5px; height: 5px; border-radius: 50%; background: #d1d5db; transition: background .2s; }
.gl-dot-ind.active { background: #6b7280; }

/* ── Historial: tabla desktop ── */
.gl-hist-table-wrap { overflow-x: auto; display: none; }
.gl-hist-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.gl-hist-table thead tr { border-bottom: 2px solid #f3f4f6; }
.gl-hist-table thead th {
    padding: 9px 12px; text-align: left;
    font-size: 10px; font-weight: 600; color: #9ca3af;
    text-transform: uppercase; letter-spacing: .05em;
}
.gl-hist-table tbody tr { border-bottom: 1px solid #f9fafb; transition: background .1s; }
.gl-hist-table tbody tr:last-child { border-bottom: none; }
.gl-hist-table tbody tr:hover { background: #fafafa; }
.gl-hist-table td { padding: 10px 12px; color: #374151; vertical-align: middle; }
.gl-hist-table tfoot tr { border-top: 2px solid #f3f4f6; }
.gl-hist-table tfoot td { padding: 10px 12px; font-weight: 700; font-size: 13px; }

/* ── Paginación ── */
.gl-pagination {
    display: flex; align-items: center; justify-content: center;
    gap: 4px; flex-wrap: wrap; margin-top: 12px;
}
.gl-pag-btn {
    width: 32px; height: 32px; border-radius: 8px;
    border: 1px solid var(--gl-border); background: #fff;
    color: #6b7280; font-size: 13px;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; transition: all .1s; text-decoration: none;
}
.gl-pag-btn:hover { background: #f3f4f6; color: #1f2937; }
.gl-pag-btn.active { background: #374151; color: #fff; border-color: #374151; }
.gl-pag-btn.disabled { opacity: .4; pointer-events: none; }

/* ── Responsive ── */
@media (min-width: 768px) {
    .gl-hist-list { display: none !important; }
    .gl-hist-table-wrap { display: block !important; }
    .gl-swipe-hint { display: none !important; }
    #history-summary { grid-template-columns: 1fr 1fr 1fr !important; }
}
@media (max-width: 479px) {
    .gl-stats { grid-template-columns: 1fr 1fr; }
    .gl-card-actions { flex-wrap: wrap; }
}
</style>

<!-- ============================================ -->
<!-- JAVASCRIPT                                   -->
<!-- ============================================ -->
<script>
const GOALS_AJAX_URL = 'ajax/goals.php';

// ─── Helpers ──────────────────────────────────

function glShowError(msg, full) {
    console.error('[Goals]', full ?? msg);
    Swal.fire({ toast: true, position: 'top-start', icon: 'error', title: msg,
        showConfirmButton: false, timer: 4500, timerProgressBar: true });
}
function glShowSuccess(msg) {
    Swal.fire({ toast: true, position: 'top-start', icon: 'success', title: msg,
        showConfirmButton: false, timer: 3000, timerProgressBar: true });
}
function glEsc(str) {
    return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
}
function glFmt(n) {
    return parseFloat(n).toLocaleString('es-DO', { minimumFractionDigits: 2 });
}
function glPost(body) {
    return fetch(GOALS_AJAX_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams(body).toString(),
    }).then(r => r.json());
}
function glGet(params) {
    return fetch(`${GOALS_AJAX_URL}?${new URLSearchParams(params)}`).then(r => r.json());
}
function glSetLoading(btnId, textId, spinnerId, loading, label) {
    document.getElementById(btnId).disabled = loading;
    document.getElementById(textId).textContent = loading ? 'Procesando…' : label;
    document.getElementById(spinnerId).classList.toggle('d-none', !loading);
}

// ─── Renderizar tarjeta de meta ───────────────

function renderGoalCard(goal) {
    const progress   = Math.min(100, (parseFloat(goal.current_amount) / parseFloat(goal.target_amount)) * 100);
    const remaining  = Math.max(0, parseFloat(goal.target_amount) - parseFloat(goal.current_amount));
    const deadline   = new Date(goal.deadline + 'T00:00:00');
    const today      = new Date(); today.setHours(0,0,0,0);
    const daysLeft   = Math.ceil((deadline - today) / 86400000);
    const isCompleted = goal.is_completed == 1 || progress >= 99.9;
    const isOverdue  = !isCompleted && daysLeft < 0;

    const monthsLeft     = Math.max(1, Math.ceil(Math.max(0, daysLeft) / 30));
    const monthlyNeeded  = remaining / monthsLeft;

    const cardClass   = isCompleted ? 'completed' : isOverdue ? 'overdue' : '';
    const fillClass   = isCompleted ? 'done' : isOverdue ? 'overdue' : '';
    const pillHtml    = isCompleted
        ? '<span class="gl-pill pill-done">✓ Completada</span>'
        : isOverdue
        ? '<span class="gl-pill pill-overdue">Vencida</span>'
        : `<span class="gl-pill pill-active">${daysLeft}d restantes</span>`;

    const deadlineFmt = deadline.toLocaleDateString('es-DO', { day:'2-digit', month:'short', year:'numeric' });

    const deleteBtn = (!isCompleted && parseFloat(goal.current_amount) == 0)
        ? `<button class="gl-action-btn del" onclick="confirmDeleteGoal(${goal.id}, '${glEsc(goal.name)}')" title="Eliminar">✕</button>`
        : '';

    const monthlyNote = (!isCompleted && remaining > 0 && daysLeft > 0)
        ? `<div class="gl-monthly-note">
               Ahorra <strong>RD$ ${glFmt(monthlyNeeded)}</strong> por mes para cumplir tu meta en ${monthsLeft} mes${monthsLeft !== 1 ? 'es' : ''}.
           </div>`
        : '';

    return `
    <div class="gl-card ${cardClass}" id="goal-card-${goal.id}">
        <div class="gl-card-top">
            <div class="gl-card-info">
                <div class="gl-card-name">${glEsc(goal.name)}</div>
                <div class="gl-card-deadline">
                    <span>📅 ${deadlineFmt}</span>
                    ${pillHtml}
                </div>
            </div>
            <div class="gl-card-actions">
                ${!isCompleted ? `<button class="gl-action-btn abonar" onclick="openContributionModal(${goal.id}, '${glEsc(goal.name)}')">+ Abonar</button>` : ''}
                <button class="gl-action-btn" onclick="openHistoryModal(${goal.id}, '${glEsc(goal.name)}')" title="Historial">≡</button>
                ${deleteBtn}
            </div>
        </div>

        <div class="gl-progress-row">
            <span style="font-size:11px;color:#9ca3af">Progreso</span>
            <span class="gl-progress-pct">${progress.toFixed(1)}%</span>
        </div>
        <div class="gl-progress-track">
            <div class="gl-progress-fill ${fillClass}" style="width:${progress}%"></div>
        </div>

        <div class="gl-stats">
            <div class="gl-stat">
                <div class="gl-stat-label">Meta</div>
                <div class="gl-stat-val">RD$ ${glFmt(goal.target_amount)}</div>
            </div>
            <div class="gl-stat">
                <div class="gl-stat-label">Ahorrado</div>
                <div class="gl-stat-val green">RD$ ${glFmt(goal.current_amount)}</div>
            </div>
            <div class="gl-stat">
                <div class="gl-stat-label">Faltan</div>
                <div class="gl-stat-val ${isOverdue ? 'red' : 'amber'}">RD$ ${glFmt(remaining)}</div>
            </div>
        </div>

        ${monthlyNote}
    </div>`;
}

function renderEmptyGoals() {
    return `<div class="gl-empty" id="goals-empty">
        <div class="gl-empty-icon">🎯</div>
        <p>No tienes metas de ahorro definidas aún</p>
        <button class="gl-btn-new" data-bs-toggle="modal" data-bs-target="#createGoalModal">
            <span>+</span> Crear primera meta
        </button>
    </div>`;
}

// ─── Cargar metas ──────────────────────────────

function loadGoals() {
    glPost({ action: 'get_goals' })
        .then(data => {
            document.getElementById('goals-loading')?.remove();
            if (!data.success) {
                glShowError(data.message, data.full_message);
                document.getElementById('goals-list').innerHTML = renderEmptyGoals();
                return;
            }

            const list = document.getElementById('goals-list');
            if (!data.goals.length) {
                list.innerHTML = renderEmptyGoals();
                document.getElementById('gl-summary-text').textContent = 'Sin metas activas';
                return;
            }

            list.innerHTML = '<div class="gl-grid">' + data.goals.map(renderGoalCard).join('') + '</div>';

            // Resumen en barra
            const total     = data.goals.length;
            const completed = data.goals.filter(g => g.is_completed == 1 || (parseFloat(g.current_amount)/parseFloat(g.target_amount)) >= 0.999).length;
            document.getElementById('gl-summary-text').textContent =
                `${total} meta${total !== 1 ? 's' : ''} · ${completed} completada${completed !== 1 ? 's' : ''}`;
        })
        .catch(err => {
            glShowError('No se pudieron cargar las metas.', err.message);
            document.getElementById('goals-list').innerHTML = renderEmptyGoals();
        });
}

// ─── Crear meta ────────────────────────────────

(function() {
    const d = new Date(); d.setMonth(d.getMonth() + 6);
    document.getElementById('goal-deadline').value = d.toISOString().slice(0, 10);
})();

document.getElementById('btn-add-goal').addEventListener('click', function () {
    const name     = document.getElementById('goal-name').value.trim();
    const target   = document.getElementById('goal-target').value;
    const deadline = document.getElementById('goal-deadline').value;

    if (!name)                             { glShowError('El nombre de la meta es obligatorio.'); return; }
    if (!target || parseFloat(target) <= 0) { glShowError('El monto objetivo debe ser mayor a 0.'); return; }
    if (!deadline)                         { glShowError('La fecha límite es obligatoria.'); return; }

    glSetLoading('btn-add-goal', 'btn-goal-text', 'btn-goal-spinner', true, 'Crear Meta');

    glPost({ action: 'add_goal', name, target_amount: target, deadline })
        .then(data => {
            if (!data.success) { glShowError(data.message, data.full_message); return; }
            glShowSuccess(data.message);
            bootstrap.Modal.getInstance(document.getElementById('createGoalModal')).hide();
            document.getElementById('goal-name').value   = '';
            document.getElementById('goal-target').value = '';

            const empty = document.getElementById('goals-empty');
            if (empty) {
                empty.remove();
                document.getElementById('goals-list').innerHTML = '<div class="gl-grid"></div>';
            }
            document.querySelector('.gl-grid')?.insertAdjacentHTML('beforeend', renderGoalCard(data.goal));
            loadGoals(); // refresca el contador
        })
        .catch(err => glShowError('Error de red al crear la meta.', err.message))
        .finally(() => glSetLoading('btn-add-goal', 'btn-goal-text', 'btn-goal-spinner', false, 'Crear Meta'));
});

// ─── Abonar ────────────────────────────────────

function openContributionModal(goalId, goalName) {
    document.getElementById('contribution-goal-id').value      = goalId;
    document.getElementById('contribution-goal-name').textContent = goalName;
    document.getElementById('contribution-amount').value       = '';
    document.getElementById('contribution-notes').value        = '';
    document.getElementById('contribution-date').value         = new Date().toISOString().slice(0, 10);
    new bootstrap.Modal(document.getElementById('addContributionModal')).show();
}

document.getElementById('btn-add-contribution').addEventListener('click', function () {
    const goalId = document.getElementById('contribution-goal-id').value;
    const amount = document.getElementById('contribution-amount').value;
    const date   = document.getElementById('contribution-date').value;
    const notes  = document.getElementById('contribution-notes').value.trim();

    if (!amount || parseFloat(amount) <= 0) { glShowError('El monto debe ser mayor a 0.'); return; }
    if (!date) { glShowError('La fecha del abono es obligatoria.'); return; }

    glSetLoading('btn-add-contribution', 'btn-contribution-text', 'btn-contribution-spinner', true, 'Registrar Abono');

    glPost({ action: 'add_contribution', goal_id: goalId, amount, contribution_date: date, notes })
        .then(data => {
            if (!data.success) { glShowError(data.message, data.full_message); return; }
            glShowSuccess(data.message);
            bootstrap.Modal.getInstance(document.getElementById('addContributionModal')).hide();
            const updated  = data.updated_goal;
            const existing = document.getElementById(`goal-card-${updated.id}`);
            if (existing) existing.outerHTML = renderGoalCard(updated);
            loadGoals();
        })
        .catch(err => glShowError('Error de red al registrar el abono.', err.message))
        .finally(() => glSetLoading('btn-add-contribution', 'btn-contribution-text', 'btn-contribution-spinner', false, 'Registrar Abono'));
});

// ─── Historial ─────────────────────────────────

let _glHistPage = 1;
let _glHistData = [];
const GL_HIST_PAGE = 15;

function openHistoryModal(goalId, goalName) {
    document.getElementById('history-goal-name').textContent = goalName;
    document.getElementById('history-content').innerHTML =
        '<div class="gl-loading"><div class="spinner-border spinner-border-sm text-secondary" role="status"></div><span>Cargando…</span></div>';
    document.getElementById('history-summary').style.display = 'none';
    _glHistData = []; _glHistPage = 1;
    new bootstrap.Modal(document.getElementById('historyModal')).show();

    glGet({ action: 'get_history', goal_id: goalId })
        .then(data => {
            if (!data.success) {
                glShowError(data.message, data.full_message);
                document.getElementById('history-content').innerHTML =
                    '<div class="gl-empty"><p style="color:#D85A30">Error al cargar el historial.</p></div>';
                return;
            }

            _glHistData = data.data || [];

            if (!_glHistData.length) {
                document.getElementById('history-content').innerHTML = `
                    <div class="gl-empty">
                        <div class="gl-empty-icon">📭</div>
                        <p>No hay abonos registrados aún</p>
                        <button class="gl-btn-save" onclick="
                            bootstrap.Modal.getInstance(document.getElementById('historyModal')).hide();
                            openContributionModal(${goalId}, '${glEsc(goalName)}')">
                            + Primer abono
                        </button>
                    </div>`;
                return;
            }

            // Resumen
            let total = 0;
            _glHistData.forEach(item => { total += parseFloat(item.amount); });
            const avg = total / _glHistData.length;
            document.getElementById('hist-total').textContent = 'RD$ ' + glFmt(total);
            document.getElementById('hist-count').textContent = _glHistData.length + ' abono' + (_glHistData.length !== 1 ? 's' : '');
            document.getElementById('hist-avg').textContent   = 'RD$ ' + glFmt(avg);
            const sumBar = document.getElementById('history-summary');
            sumBar.style.display = 'grid';
            sumBar.style.gridTemplateColumns = '1fr 1fr 1fr';
            sumBar.style.gap = '10px';
            sumBar.style.marginBottom = '18px';

            // Hint móvil
            const hint = document.getElementById('histSwipeHint');
            if (hint) hint.style.display = window.innerWidth < 768 ? 'block' : 'none';

            renderGlHistPage();
        })
        .catch(err => {
            glShowError('Error de red al cargar el historial.', err.message);
            document.getElementById('history-content').innerHTML =
                '<div class="gl-empty"><p style="color:#D85A30">Error de conexión.</p></div>';
        });
}

function renderGlHistPage() {
    const total      = _glHistData.length;
    const totalPages = Math.ceil(total / GL_HIST_PAGE);
    const start      = (_glHistPage - 1) * GL_HIST_PAGE;
    const end        = Math.min(start + GL_HIST_PAGE, total);
    const slice      = _glHistData.slice(start, end);

    // ── MÓVIL ──
    const mobileHtml = '<div class="gl-hist-list">' + slice.map((item, idx) => {
        const uniqueId = `ghi${_glHistPage}_${idx}`;
        const dt = item.contribution_date
            ? new Date(item.contribution_date + 'T00:00:00').toLocaleDateString('es-DO', { day:'2-digit', month:'2-digit', year:'numeric' })
            : '—';
        return `
        <div class="gl-hist-item">
            <div class="gl-hist-scroll" id="${uniqueId}">
                <div class="gl-hist-panel">
                    <div class="gl-hist-dot">↑</div>
                    <div class="gl-hist-main">
                        <div class="gl-hist-date">${dt}</div>
                        <div class="gl-hist-sub">${glEsc(item.notes || '—')}</div>
                    </div>
                    <div class="gl-hist-amt">RD$ ${glFmt(item.amount)}</div>
                </div>
                <div class="gl-hist-panel-extra">
                    <div class="gl-hist-extra-col">
                        <div class="gl-ex-label">Registrado</div>
                        <div class="gl-ex-val">${glEsc(item.created_at || '—')}</div>
                    </div>
                    <div class="gl-ex-divider"></div>
                    <div class="gl-hist-extra-col">
                        <div class="gl-ex-label">Notas</div>
                        <div class="gl-ex-val">${glEsc(item.notes || 'Sin notas')}</div>
                    </div>
                </div>
            </div>
            <div class="gl-hist-dots">
                <div class="gl-dot-ind active" id="ghd0-${uniqueId}"></div>
                <div class="gl-dot-ind" id="ghd1-${uniqueId}"></div>
            </div>
        </div>`;
    }).join('') + '</div>';

    // ── DESKTOP ──
    let totalAmt = 0;
    const desktopRows = slice.map(item => {
        totalAmt += parseFloat(item.amount);
        const dt = item.contribution_date
            ? new Date(item.contribution_date + 'T00:00:00').toLocaleDateString('es-DO', { day:'2-digit', month:'short', year:'numeric' })
            : '—';
        return `<tr>
            <td style="white-space:nowrap;color:#9ca3af;font-size:12px">${dt}</td>
            <td style="font-weight:600;color:#1D9E75">RD$ ${glFmt(item.amount)}</td>
            <td style="color:#6b7280">${glEsc(item.notes || '—')}</td>
            <td style="color:#9ca3af;font-size:11px">${glEsc(item.created_at || '—')}</td>
        </tr>`;
    }).join('');

    const desktopHtml = `<div class="gl-hist-table-wrap">
        <table class="gl-hist-table">
            <thead><tr>
                <th>Fecha</th><th>Monto</th><th>Notas</th><th>Registrado</th>
            </tr></thead>
            <tbody>${desktopRows}</tbody>
            <tfoot><tr>
                <td>Total (${total} abono${total !== 1 ? 's' : ''})</td>
                <td style="color:#1D9E75">RD$ ${glFmt(totalAmt)}</td>
                <td colspan="2"></td>
            </tr></tfoot>
        </table>
    </div>`;

    document.getElementById('history-content').innerHTML = mobileHtml + desktopHtml;

    // Dots scroll móvil
    slice.forEach((_, idx) => {
        const uniqueId = `ghi${_glHistPage}_${idx}`;
        const sc = document.getElementById(uniqueId);
        if (!sc) return;
        sc.addEventListener('scroll', () => {
            const at = sc.scrollLeft > sc.scrollWidth * 0.3;
            document.getElementById('ghd0-' + uniqueId)?.classList.toggle('active', !at);
            document.getElementById('ghd1-' + uniqueId)?.classList.toggle('active', at);
        });
    });

    // Paginación
    const pagEl = document.querySelector('#historyModal .modal-body') || document.getElementById('history-content').parentElement;
    let existingPag = document.getElementById('gl-hist-pag');
    if (!existingPag) {
        const pDiv = document.createElement('div');
        pDiv.id = 'gl-hist-pag';
        document.getElementById('history-content').insertAdjacentElement('afterend', pDiv);
        existingPag = pDiv;
    }

    if (totalPages <= 1) { existingPag.innerHTML = ''; return; }
    let ph = '<div class="gl-pagination">';
    ph += `<a class="gl-pag-btn ${_glHistPage<=1?'disabled':''}" href="#" onclick="changeGlHistPage(1);return false;">«</a>`;
    ph += `<a class="gl-pag-btn ${_glHistPage<=1?'disabled':''}" href="#" onclick="changeGlHistPage(${_glHistPage-1});return false;">‹</a>`;
    const s = Math.max(1, _glHistPage-2), e = Math.min(totalPages, _glHistPage+2);
    if (s>1) ph += `<span class="gl-pag-btn disabled">…</span>`;
    for (let i=s; i<=e; i++)
        ph += `<a class="gl-pag-btn ${i===_glHistPage?'active':''}" href="#" onclick="changeGlHistPage(${i});return false;">${i}</a>`;
    if (e<totalPages) ph += `<span class="gl-pag-btn disabled">…</span>`;
    ph += `<a class="gl-pag-btn ${_glHistPage>=totalPages?'disabled':''}" href="#" onclick="changeGlHistPage(${_glHistPage+1});return false;">›</a>`;
    ph += `<a class="gl-pag-btn ${_glHistPage>=totalPages?'disabled':''}" href="#" onclick="changeGlHistPage(${totalPages});return false;">»</a>`;
    ph += '</div>';
    existingPag.innerHTML = ph;
}

function changeGlHistPage(p) {
    const total = Math.ceil(_glHistData.length / GL_HIST_PAGE);
    if (p < 1 || p > total) return;
    _glHistPage = p;
    renderGlHistPage();
}

// ─── Eliminar meta ─────────────────────────────

function confirmDeleteGoal(goalId, goalName) {
    Swal.fire({
        title: '¿Eliminar meta?',
        text: `¿Seguro que deseas eliminar "${goalName}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
    }).then(result => {
        if (!result.isConfirmed) return;
        glPost({ action: 'delete_goal', goal_id: goalId })
            .then(data => {
                if (!data.success) { glShowError(data.message, data.full_message); return; }
                glShowSuccess(data.message);
                document.getElementById(`goal-card-${data.goal_id}`)?.remove();
                if (!document.querySelector('[id^="goal-card-"]')) {
                    document.getElementById('goals-list').innerHTML = renderEmptyGoals();
                }
                loadGoals();
            })
            .catch(err => glShowError('Error de red al eliminar la meta.', err.message));
    });
}

// ─── Init ─────────────────────────────────────
loadGoals();
</script>