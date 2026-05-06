<?php
// modules/debts.php
$ajax_url = 'ajax/debts.php';
?>

<!-- ============================================ -->
<!-- MÓDULO DE DEUDAS — DISEÑO LIMPIO             -->
<!-- ============================================ -->
<div id="debts-module">

    <!-- ── Barra superior ── -->
    <div class="db-top-bar">
        <div>
            <div class="db-top-label">Mis Deudas</div>
            <div class="db-top-sub" id="db-summary-text">—</div>
        </div>
        <button class="db-btn-new" data-bs-toggle="modal" data-bs-target="#debtModal">
            <span style="font-size:16px;line-height:1">+</span> Registrar Deuda
        </button>
    </div>

    <!-- ── Lista de deudas ── -->
    <div id="debts-list-container">
        <div class="db-loading">
            <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
            <span>Cargando deudas…</span>
        </div>
    </div>

</div>

<!-- ============================================ -->
<!-- MODAL: REGISTRAR DEUDA                       -->
<!-- ============================================ -->
<div class="modal fade" id="debtModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content db-modal-content">
            <div class="modal-header db-modal-header">
                <h5 class="modal-title" style="font-size:16px;font-weight:500">Registrar Nueva Deuda</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:20px 24px">
                <div class="mb-3">
                    <label class="db-form-label">Acreedor</label>
                    <input type="text" id="debt-creditor" class="form-control db-form-control"
                           placeholder="Ej: Banco Popular, Préstamo personal">
                </div>
                <div class="mb-3">
                    <label class="db-form-label">Monto Total (RD$)</label>
                    <input type="number" step="0.01" id="debt-total" class="form-control db-form-control" placeholder="0.00">
                </div>
                <div class="mb-3">
                    <label class="db-form-label">Tasa de Interés (%) <span style="font-weight:400;color:#aaa">opcional</span></label>
                    <input type="number" step="0.01" id="debt-interest" class="form-control db-form-control" placeholder="0">
                </div>
                <div class="mb-1">
                    <label class="db-form-label">Fecha de Vencimiento</label>
                    <input type="date" id="debt-due-date" class="form-control db-form-control">
                </div>
            </div>
            <div class="modal-footer" style="padding:14px 24px;border-top:1px solid #f0f0f0">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="db-btn-save" onclick="DebtModule.addDebt()">
                    Registrar Deuda
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- MODAL: HISTORIAL DE PAGOS                    -->
<!-- ============================================ -->
<div class="modal fade" id="paymentHistoryModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content db-modal-content">
            <div class="modal-header db-modal-header">
                <div>
                    <h5 class="modal-title" style="font-size:16px;font-weight:500" id="history-creditor-name">Historial</h5>
                    <div style="font-size:12px;color:#9ca3af;margin-top:2px">Pagos registrados</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:20px 24px">

                <!-- Resumen rápido -->
                <div id="history-summary-bar" style="display:none;grid-template-columns:1fr 1fr 1fr;gap:10px;margin-bottom:18px">
                    <div class="db-sum-card">
                        <div class="db-sum-label">Total pagado</div>
                        <div class="db-sum-val" style="color:#1D9E75" id="hist-total-paid">—</div>
                    </div>
                    <div class="db-sum-card">
                        <div class="db-sum-label">Pagos</div>
                        <div class="db-sum-val" style="color:#374151" id="hist-count">—</div>
                    </div>
                    <div class="db-sum-card">
                        <div class="db-sum-label">Promedio</div>
                        <div class="db-sum-val" style="color:#3b82f6" id="hist-avg">—</div>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="db-hist-filters">
                    <div>
                        <label class="db-form-label">Desde</label>
                        <input type="date" id="history-date-from" class="db-form-control">
                    </div>
                    <div>
                        <label class="db-form-label">Hasta</label>
                        <input type="date" id="history-date-to" class="db-form-control">
                    </div>
                    <div style="display:flex;align-items:flex-end;gap:8px">
                        <button class="db-btn-apply-hist" onclick="DebtModule.applyHistoryFilter()">Filtrar</button>
                        <button class="db-btn-clear-hist" onclick="DebtModule.clearHistoryFilter()">Limpiar</button>
                    </div>
                </div>

                <!-- Hint móvil -->
                <div class="db-swipe-hint" id="dbHistHint" style="display:none">
                    Desliza cada fila → para revertir pago
                </div>

                <!-- Contenido del historial -->
                <div id="history-table-container">
                    <div class="db-loading">
                        <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
                        <span>Cargando historial…</span>
                    </div>
                </div>

                <div id="history-total-footer" style="font-size:11px;color:#9ca3af;text-align:right;margin-top:8px"></div>
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
#debts-module {
    --db-radius: 10px;
    --db-border: #e8e8e8;
    --db-inc: #1D9E75;
    --db-exp: #D85A30;
    --db-blue: #3b82f6;
    --db-inc-bg: #eaf3de;
    --db-inc-text: #3B6D11;
    --db-exp-bg: #faece7;
    --db-exp-text: #993C1D;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
}

/* ── Barra superior ── */
.db-top-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #fff;
    border: 1px solid var(--db-border);
    border-radius: var(--db-radius);
    padding: 16px 20px;
    margin-bottom: 16px;
}
.db-top-label {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: #9ca3af;
    margin-bottom: 4px;
}
.db-top-sub { font-size: 14px; font-weight: 500; color: #374151; }
.db-btn-new {
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
.db-btn-new:hover { background: #1f2937; }

/* ── Loading / empty ── */
.db-loading {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 48px 0;
    color: #9ca3af;
    font-size: 14px;
}
.db-empty {
    text-align: center;
    padding: 56px 20px;
    color: #9ca3af;
}
.db-empty-icon { font-size: 36px; margin-bottom: 10px; opacity: .35; }
.db-empty p { font-size: 14px; margin-bottom: 14px; }

/* ── Grid de deudas ── */
.db-grid {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

/* ── Tarjeta de deuda ── */
.db-card {
    background: #fff;
    border: 1px solid var(--db-border);
    border-radius: var(--db-radius);
    padding: 18px;
    transition: box-shadow .15s, border-color .15s;
    position: relative;
    overflow: hidden;
}
.db-card:hover {
    border-color: #d1d5db;
    box-shadow: 0 2px 14px rgba(0,0,0,.05);
}
.db-card::before {
    content: '';
    position: absolute;
    left: 0; top: 0; bottom: 0;
    width: 4px;
    background: var(--db-exp);
    border-radius: var(--db-radius) 0 0 var(--db-radius);
}
.db-card.paid::before   { background: var(--db-inc); }
.db-card.overdue::before { background: #7c3aed; }

/* ── Header de tarjeta ── */
.db-card-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 14px;
}
.db-card-info { flex: 1; min-width: 0; }
.db-card-name {
    font-size: 15px;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 3px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.db-card-meta {
    font-size: 11px;
    color: #9ca3af;
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}
.db-pill {
    display: inline-flex;
    align-items: center;
    padding: 2px 8px;
    border-radius: 20px;
    font-size: 10px;
    font-weight: 600;
}
.pill-paid    { background: var(--db-inc-bg); color: var(--db-inc-text); }
.pill-overdue { background: #ede9fe; color: #5b21b6; }
.pill-pending { background: #fef9c3; color: #854d0e; }

.db-card-actions {
    display: flex;
    gap: 4px;
    flex-shrink: 0;
    flex-wrap: wrap;
    justify-content: flex-end;
}
.db-action-btn {
    height: 30px;
    padding: 0 10px;
    border-radius: 8px;
    border: 1px solid var(--db-border);
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
.db-action-btn:hover { background: #f3f4f6; color: #374151; border-color: #d1d5db; }
.db-action-btn.pay    { background: #f0fdf4; color: var(--db-inc-text); border-color: #86efac; }
.db-action-btn.pay:hover { background: #dcfce7; }
.db-action-btn.del:hover { background: #fef2f2; color: #dc2626; border-color: #fecaca; }

/* ── Barra de progreso ── */
.db-progress-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 5px;
}
.db-progress-pct { font-size: 12px; font-weight: 700; color: #374151; }
.db-progress-track {
    height: 8px;
    background: #f3f4f6;
    border-radius: 99px;
    overflow: hidden;
    margin-bottom: 14px;
}
.db-progress-fill {
    height: 100%;
    border-radius: 99px;
    background: var(--db-exp);
    transition: width .6s cubic-bezier(.4,0,.2,1);
}
.db-progress-fill.paid { background: var(--db-inc); }

/* ── Stats ── */
.db-stats {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 8px;
}
.db-stat {
    text-align: center;
    background: #f9fafb;
    border-radius: 8px;
    padding: 8px 4px;
}
.db-stat-label {
    font-size: 9px;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: #9ca3af;
    margin-bottom: 3px;
}
.db-stat-val {
    font-size: 13px;
    font-weight: 700;
    color: #1f2937;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.db-stat-val.red   { color: var(--db-exp); }
.db-stat-val.green { color: var(--db-inc); }
.db-stat-val.blue  { color: var(--db-blue); }

/* ── Modal ── */
.db-modal-content {
    border: none;
    border-radius: 14px;
    box-shadow: 0 20px 60px rgba(0,0,0,.12);
}
.db-modal-header {
    padding: 18px 24px 12px;
    border-bottom: 1px solid #f3f4f6;
}
.db-form-label {
    display: block;
    font-size: 11px !important;
    font-weight: 600 !important;
    color: #9ca3af !important;
    text-transform: uppercase;
    letter-spacing: .04em;
    margin-bottom: 5px !important;
}
.db-form-control {
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
.db-form-control:focus {
    border-color: #a5b4fc !important;
    background: #fff !important;
    box-shadow: 0 0 0 3px rgba(165,180,252,.15) !important;
}
.db-btn-save {
    padding: 8px 22px;
    background: #374151;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: background .15s;
}
.db-btn-save:hover { background: #1f2937; }

/* ── Resumen historial ── */
.db-sum-card {
    background: #f9fafb;
    border: 1px solid var(--db-border);
    border-radius: var(--db-radius);
    padding: 12px 14px;
}
.db-sum-label {
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: #9ca3af;
    margin-bottom: 4px;
}
.db-sum-val { font-size: 16px; font-weight: 700; }

/* ── Filtros historial ── */
.db-hist-filters {
    display: grid;
    grid-template-columns: 1fr 1fr auto;
    gap: 10px;
    margin-bottom: 14px;
    align-items: end;
}
@media (max-width: 575px) {
    .db-hist-filters { grid-template-columns: 1fr 1fr; }
    .db-hist-filters > div:last-child { grid-column: 1/-1; }
}
.db-btn-apply-hist {
    padding: 8px 16px;
    background: #374151;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 13px;
    cursor: pointer;
    height: 38px;
    transition: background .15s;
}
.db-btn-apply-hist:hover { background: #1f2937; }
.db-btn-clear-hist {
    padding: 8px 14px;
    background: #f3f4f6;
    color: #6b7280;
    border: 1px solid var(--db-border);
    border-radius: 8px;
    font-size: 13px;
    cursor: pointer;
    height: 38px;
    transition: background .15s;
}
.db-btn-clear-hist:hover { background: #e5e7eb; }

/* ── Hint móvil ── */
.db-swipe-hint {
    text-align: center;
    font-size: 11px;
    color: #b0b7c3;
    margin-bottom: 8px;
}

/* ── Historial: lista móvil deslizable ── */
.db-hist-list { display: flex; flex-direction: column; gap: 2px; }
.db-hist-item {
    border-radius: 8px;
    background: #fff;
    border: 1px solid var(--db-border);
    overflow: hidden;
}
.db-hist-scroll {
    display: flex;
    overflow-x: auto;
    scroll-snap-type: x mandatory;
    scrollbar-width: none;
    -ms-overflow-style: none;
}
.db-hist-scroll::-webkit-scrollbar { display: none; }
.db-hist-panel {
    flex: 0 0 100%;
    scroll-snap-align: start;
    padding: 11px 14px;
    display: flex;
    align-items: center;
    gap: 12px;
    min-height: 58px;
}
.db-hist-panel-extra {
    flex: 0 0 100%;
    scroll-snap-align: start;
    padding: 11px 14px;
    display: flex;
    align-items: center;
    gap: 10px;
    background: #f8f9fb;
}
.db-hist-dot {
    width: 34px; height: 34px;
    border-radius: 50%;
    background: #f0fdf4;
    color: var(--db-inc-text);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    font-weight: 700;
    flex-shrink: 0;
}
.db-hist-main { flex: 1; min-width: 0; }
.db-hist-date { font-size: 13px; font-weight: 500; color: #1f2937; }
.db-hist-sub  { font-size: 11px; color: #9ca3af; margin-top: 2px; }
.db-hist-amt  { font-size: 14px; font-weight: 700; color: var(--db-inc); flex-shrink: 0; }
.db-hist-extra-col { flex: 1; min-width: 0; }
.db-ex-label {
    font-size: 9px; color: #9ca3af; text-transform: uppercase;
    letter-spacing: .05em; margin-bottom: 2px;
}
.db-ex-val { font-size: 12px; color: #374151; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.db-ex-divider { width: 1px; height: 28px; background: var(--db-border); flex-shrink: 0; }
.db-btn-revert {
    height: 30px; padding: 0 10px;
    border-radius: 8px;
    border: 1px solid #fecaca;
    background: #fef2f2;
    color: #dc2626;
    font-size: 11px; font-weight: 500;
    display: flex; align-items: center; gap: 4px;
    cursor: pointer; white-space: nowrap;
    flex-shrink: 0;
    transition: background .12s;
}
.db-btn-revert:hover { background: #fee2e2; }
.db-hist-dots {
    display: flex; align-items: center; justify-content: center;
    gap: 4px; padding: 4px 0 3px;
}
.db-dot-ind { width: 5px; height: 5px; border-radius: 50%; background: #d1d5db; transition: background .2s; }
.db-dot-ind.active { background: #6b7280; }

/* ── Historial: tabla desktop ── */
.db-hist-table-wrap { overflow-x: auto; display: none; }
.db-hist-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.db-hist-table thead tr { border-bottom: 2px solid #f3f4f6; }
.db-hist-table thead th {
    padding: 9px 12px; text-align: left;
    font-size: 10px; font-weight: 600; color: #9ca3af;
    text-transform: uppercase; letter-spacing: .05em;
}
.db-hist-table tbody tr { border-bottom: 1px solid #f9fafb; transition: background .1s; }
.db-hist-table tbody tr:last-child { border-bottom: none; }
.db-hist-table tbody tr:hover { background: #fafafa; }
.db-hist-table td { padding: 10px 12px; color: #374151; vertical-align: middle; }
.db-hist-table tfoot tr { border-top: 2px solid #f3f4f6; }
.db-hist-table tfoot td { padding: 10px 12px; font-weight: 700; font-size: 13px; }
.db-tbl-revert {
    height: 28px; padding: 0 10px;
    border-radius: 6px;
    border: 1px solid #fecaca;
    background: #fef2f2;
    color: #dc2626;
    font-size: 11px; font-weight: 500;
    cursor: pointer;
    transition: background .12s;
}
.db-tbl-revert:hover { background: #fee2e2; }

/* ── Paginación ── */
.db-pagination {
    display: flex; align-items: center; justify-content: center;
    gap: 4px; flex-wrap: wrap; margin-top: 12px;
}
.db-pag-btn {
    width: 32px; height: 32px; border-radius: 8px;
    border: 1px solid var(--db-border); background: #fff;
    color: #6b7280; font-size: 13px;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; transition: all .1s; text-decoration: none;
}
.db-pag-btn:hover { background: #f3f4f6; color: #1f2937; }
.db-pag-btn.active { background: #374151; color: #fff; border-color: #374151; }
.db-pag-btn.disabled { opacity: .4; pointer-events: none; }

/* ── Responsive ── */
@media (min-width: 768px) {
    .db-hist-list { display: none !important; }
    .db-hist-table-wrap { display: block !important; }
    .db-swipe-hint { display: none !important; }
    #history-summary-bar { grid-template-columns: 1fr 1fr 1fr !important; }
}
@media (max-width: 479px) {
    .db-stats { grid-template-columns: 1fr 1fr; }
    .db-card-actions { flex-wrap: wrap; }
}
</style>

<!-- ============================================ -->
<!-- JAVASCRIPT                                   -->
<!-- ============================================ -->
<script>
let _historyDebtId = null;

const DebtModule = (() => {

    const AJAX_URL = '<?= $ajax_url ?>';
    let _debts    = [];
    let _accounts = [];
    let _histPayments = [];
    let _histPage     = 1;
    const HIST_PAGE   = 15;

    // ─── Helpers ──────────────────────────────────

    function fmt(n) {
        return parseFloat(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    function dbEsc(str) {
        return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
    function dbEscJs(str) {
        return String(str ?? '').replace(/\\/g,'\\\\').replace(/'/g,"\\'");
    }
    function showError(msg, full) {
        console.error('[DebtModule]', full ?? msg);
        Swal.fire({ toast:true, position:'top-start', icon:'error', title:msg,
            showConfirmButton:false, timer:5000, timerProgressBar:true });
    }
    function showSuccess(msg) {
        Swal.fire({ toast:true, position:'top-start', icon:'success', title:msg,
            showConfirmButton:false, timer:3500, timerProgressBar:true });
    }
    async function request(action, data = {}) {
        const res = await fetch(AJAX_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ action, ...data }),
        });
        const raw = await res.text();
        let json;
        try { json = JSON.parse(raw); }
        catch (e) {
            console.error('[DebtModule] No-JSON:\n', raw);
            throw Object.assign(new Error('Respuesta inválida del servidor. Revisa la consola.'), { fullMessage: raw });
        }
        if (!json.success) {
            console.error('[DebtModule ERROR]', json.full_message ?? json.message);
            throw Object.assign(new Error(json.message ?? 'Error desconocido.'), { fullMessage: json.full_message ?? json.message });
        }
        return json;
    }

    // ─── Renderizar tarjeta de deuda ─────────────

    function renderDebtCard(debt) {
        const progress  = Math.min(100, (parseFloat(debt.paid_amount) / parseFloat(debt.total_amount)) * 100);
        const isPaid    = debt.status === 'paid' || progress >= 99.9;
        const today     = new Date().toISOString().split('T')[0];
        const isOverdue = !isPaid && debt.due_date < today;
        const dueFmt    = new Date(debt.due_date + 'T00:00:00').toLocaleDateString('es-DO', { day:'2-digit', month:'short', year:'numeric' });

        const cardClass   = isPaid ? 'paid' : isOverdue ? 'overdue' : '';
        const fillClass   = isPaid ? 'paid' : '';
        const pillHtml    = isPaid
            ? '<span class="db-pill pill-paid">✓ Pagada</span>'
            : isOverdue
            ? '<span class="db-pill pill-overdue">Vencida</span>'
            : '<span class="db-pill pill-pending">Pendiente</span>';

        const interestNote = parseFloat(debt.interest_rate) > 0
            ? `<span>· ${debt.interest_rate}% interés</span>`
            : '';

        const actionBtns = isPaid
            ? `<button class="db-action-btn" onclick="DebtModule.showHistory(${debt.id}, '${dbEscJs(debt.creditor)}')">≡ Historial</button>`
            : `<button class="db-action-btn pay" onclick="DebtModule.showPayModal(${debt.id}, '${dbEscJs(debt.creditor)}', ${debt.pending})">Pagar</button>
               <button class="db-action-btn" onclick="DebtModule.showHistory(${debt.id}, '${dbEscJs(debt.creditor)}')">≡ Historial</button>
               <button class="db-action-btn del" onclick="DebtModule.confirmDelete(${debt.id}, '${dbEscJs(debt.creditor)}')">✕</button>`;

        return `
        <div class="db-card ${cardClass}" id="debt-card-${debt.id}">
            <div class="db-card-top">
                <div class="db-card-info">
                    <div class="db-card-name">${dbEsc(debt.creditor)}</div>
                    <div class="db-card-meta">
                        <span>${dueFmt}</span>
                        ${interestNote}
                        ${pillHtml}
                    </div>
                </div>
                <div class="db-card-actions">${actionBtns}</div>
            </div>

            <div class="db-progress-row">
                <span style="font-size:11px;color:#9ca3af">Pagado</span>
                <span class="db-progress-pct" id="progress-label-${debt.id}">${progress.toFixed(1)}%</span>
            </div>
            <div class="db-progress-track">
                <div class="db-progress-fill ${fillClass}" id="progress-bar-${debt.id}" style="width:${progress}%"></div>
            </div>

            <div class="db-stats">
                <div class="db-stat">
                    <div class="db-stat-label">Total</div>
                    <div class="db-stat-val">RD$ ${fmt(debt.total_amount)}</div>
                </div>
                <div class="db-stat">
                    <div class="db-stat-label">Pagado</div>
                    <div class="db-stat-val green">RD$ ${fmt(debt.paid_amount)}</div>
                </div>
                <div class="db-stat">
                    <div class="db-stat-label">Pendiente</div>
                    <div class="db-stat-val red" id="pending-${debt.id}">RD$ ${fmt(debt.pending)}</div>
                </div>
            </div>
        </div>`;
    }

    function renderDebts(debts) {
        const container = document.getElementById('debts-list-container');
        if (!debts.length) {
            container.innerHTML = `<div class="db-empty">
                <div class="db-empty-icon">✅</div>
                <p>No tienes deudas registradas</p>
                <button class="db-btn-new" data-bs-toggle="modal" data-bs-target="#debtModal">
                    <span>+</span> Registrar primera deuda
                </button>
            </div>`;
            return;
        }
        container.innerHTML = '<div class="db-grid">' + debts.map(renderDebtCard).join('') + '</div>';

        // Resumen en barra
        const total   = debts.length;
        const paid    = debts.filter(d => d.status === 'paid' || (parseFloat(d.paid_amount)/parseFloat(d.total_amount)) >= 0.999).length;
        const pending = total - paid;
        const totalPending = debts.reduce((sum, d) => sum + parseFloat(d.pending ?? 0), 0);
        document.getElementById('db-summary-text').textContent =
            `${total} deuda${total!==1?'s':''} · ${pending} pendiente${pending!==1?'s':''} · RD$ ${fmt(totalPending)} por pagar`;
    }

    // ─── Cargar deudas ────────────────────────────

    async function loadDebts() {
        try {
            const data = await request('get_debts');
            _debts    = data.debts;
            _accounts = data.accounts;
            renderDebts(_debts);
        } catch (err) {
            showError(err.message, err.fullMessage);
        }
    }

    // ─── Agregar deuda ────────────────────────────

    async function addDebt() {
        const creditor = document.getElementById('debt-creditor').value.trim();
        const total    = document.getElementById('debt-total').value;
        const interest = document.getElementById('debt-interest').value || 0;
        const due_date = document.getElementById('debt-due-date').value;

        if (!creditor || !total || parseFloat(total) <= 0) {
            showError('Completa los campos requeridos correctamente.'); return;
        }
        try {
            const data = await request('add_debt', { creditor, total_amount: total, interest_rate: interest, due_date });
            showSuccess(data.message);
            bootstrap.Modal.getInstance(document.getElementById('debtModal'))?.hide();
            document.getElementById('debt-creditor').value = '';
            document.getElementById('debt-total').value    = '';
            document.getElementById('debt-interest').value = '';
            await loadDebts();
        } catch (err) {
            showError(err.message, err.fullMessage);
        }
    }

    // ─── Modal de pago ────────────────────────────

    function showPayModal(debtId, creditorName, pendingAmount) {
        const accountsHtml = _accounts.length
            ? _accounts.map(acc => `<option value="${acc.id}">${dbEsc(acc.name)} (${acc.currency_code}) — Saldo: ${acc.symbol} ${fmt(acc.balance)}</option>`).join('')
            : '<option disabled>No hay cuentas DOP disponibles</option>';

        Swal.fire({
            title: `Pagar: ${creditorName}`,
            width: '480px',
            customClass: { popup: 'db-swal-popup' },
            html: `
            <div style="text-align:left">
                <div style="background:#f0fdf4;border:1px solid #86efac;border-radius:8px;padding:10px 14px;margin-bottom:16px;font-size:13px;color:#166534">
                    Pendiente: <strong>RD$ ${fmt(pendingAmount)}</strong>
                </div>
                <div style="margin-bottom:12px">
                    <label style="font-size:11px;font-weight:600;color:#9ca3af;text-transform:uppercase;letter-spacing:.04em;display:block;margin-bottom:5px">Cuenta de Origen</label>
                    <select id="swal-account-id" style="width:100%;padding:8px 12px;border:1px solid #e5e7eb;border-radius:8px;font-size:14px;background:#fafafa">${accountsHtml}</select>
                </div>
                <div style="margin-bottom:12px">
                    <label style="font-size:11px;font-weight:600;color:#9ca3af;text-transform:uppercase;letter-spacing:.04em;display:block;margin-bottom:5px">Monto a Pagar (RD$)</label>
                    <input type="number" id="swal-amount" step="0.01" max="${pendingAmount}" placeholder="0.00"
                           style="width:100%;padding:8px 12px;border:1px solid #e5e7eb;border-radius:8px;font-size:14px;background:#fafafa">
                </div>
                <div>
                    <label style="font-size:11px;font-weight:600;color:#9ca3af;text-transform:uppercase;letter-spacing:.04em;display:block;margin-bottom:5px">Fecha de Pago</label>
                    <input type="date" id="swal-date" value="${new Date().toISOString().split('T')[0]}"
                           style="width:100%;padding:8px 12px;border:1px solid #e5e7eb;border-radius:8px;font-size:14px;background:#fafafa">
                </div>
            </div>`,
            showCancelButton: true,
            confirmButtonText: 'Confirmar Pago',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#374151',
            cancelButtonColor: '#9ca3af',
            preConfirm: () => {
                const account_id   = document.getElementById('swal-account-id').value;
                const amount       = parseFloat(document.getElementById('swal-amount').value);
                const payment_date = document.getElementById('swal-date').value;
                if (!account_id) { Swal.showValidationMessage('Selecciona una cuenta.'); return false; }
                if (isNaN(amount) || amount <= 0) { Swal.showValidationMessage('Ingresa un monto válido.'); return false; }
                if (amount > pendingAmount) { Swal.showValidationMessage(`No puede exceder RD$ ${fmt(pendingAmount)}.`); return false; }
                return { debt_id: debtId, account_id, amount, payment_date };
            },
        }).then(async result => {
            if (!result.isConfirmed) return;
            Swal.fire({ title: 'Procesando…', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
            try {
                const data = await request('pay_debt', result.value);
                Swal.close();
                showSuccess(data.message);
                updateDebtCard(debtId, data);
                await loadDebts();
            } catch (err) {
                Swal.close();
                showError(err.message, err.fullMessage);
            }
        });
    }

    function updateDebtCard(debtId, data) {
        const debt = _debts.find(d => d.id == debtId);
        if (!debt) { loadDebts(); return; }
        debt.paid_amount = parseFloat(data.new_paid);
        debt.pending     = parseFloat(data.pending);
        if (data.is_fully_paid) debt.status = 'paid';

        const el = document.getElementById(`debt-card-${debtId}`);
        if (el) el.outerHTML = renderDebtCard(debt);
    }

    // ─── Eliminar deuda ───────────────────────────

    function confirmDelete(debtId, creditorName) {
        Swal.fire({
            title: '¿Eliminar deuda?',
            text: `¿Seguro que deseas eliminar la deuda con "${creditorName}"?`,
            icon: 'warning', showCancelButton: true,
            confirmButtonColor: '#dc2626', cancelButtonColor: '#6b7280',
            confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Cancelar',
        }).then(async result => {
            if (!result.isConfirmed) return;
            try {
                const data = await request('delete_debt', { debt_id: debtId });
                showSuccess(data.message);
                document.getElementById(`debt-card-${debtId}`)?.remove();
                _debts = _debts.filter(d => d.id != debtId);
                if (!_debts.length) renderDebts([]);
                else {
                    const totalPending = _debts.reduce((s,d)=>s+parseFloat(d.pending??0),0);
                    const paid = _debts.filter(d=>d.status==='paid').length;
                    document.getElementById('db-summary-text').textContent =
                        `${_debts.length} deuda${_debts.length!==1?'s':''} · ${_debts.length-paid} pendiente${_debts.length-paid!==1?'s':''} · RD$ ${fmt(totalPending)} por pagar`;
                }
            } catch (err) {
                showError(err.message, err.fullMessage);
            }
        });
    }

    // ─── Historial de pagos ───────────────────────

    function showHistory(debtId, creditorName) {
        _historyDebtId = debtId;
        _histPayments  = [];
        _histPage      = 1;
        document.getElementById('history-creditor-name').textContent = creditorName;
        document.getElementById('history-date-from').value = '';
        document.getElementById('history-date-to').value   = '';
        document.getElementById('history-total-footer').textContent = '';
        document.getElementById('history-summary-bar').style.display = 'none';
        bootstrap.Modal.getOrCreateInstance(document.getElementById('paymentHistoryModal')).show();
        loadHistory(debtId);
    }

    async function loadHistory(debtId, dateFrom = '', dateTo = '') {
        document.getElementById('history-table-container').innerHTML =
            '<div class="db-loading"><div class="spinner-border spinner-border-sm text-secondary" role="status"></div><span>Cargando…</span></div>';

        try {
            const data = await request('get_payment_history', { debt_id: debtId, date_from: dateFrom, date_to: dateTo });
            _histPayments = data.payments || [];
            _histPage     = 1;

            if (_histPayments.length) {
                let total = 0;
                _histPayments.forEach(p => { total += parseFloat(p.amount); });
                const avg = total / _histPayments.length;
                document.getElementById('hist-total-paid').textContent = 'RD$ ' + fmt(total);
                document.getElementById('hist-count').textContent = _histPayments.length + ' pago' + (_histPayments.length!==1?'s':'');
                document.getElementById('hist-avg').textContent   = 'RD$ ' + fmt(avg);
                const sumBar = document.getElementById('history-summary-bar');
                sumBar.style.display = 'grid';
                sumBar.style.gridTemplateColumns = '1fr 1fr 1fr';
                sumBar.style.gap = '10px';
                sumBar.style.marginBottom = '18px';

                const hint = document.getElementById('dbHistHint');
                if (hint) hint.style.display = window.innerWidth < 768 ? 'block' : 'none';
            } else {
                document.getElementById('history-summary-bar').style.display = 'none';
            }

            renderHistPage();
        } catch (err) {
            showError(err.message, err.fullMessage);
        }
    }

    function renderHistPage() {
        const container = document.getElementById('history-table-container');
        const footerEl  = document.getElementById('history-total-footer');

        if (!_histPayments.length) {
            container.innerHTML = `<div class="db-empty"><div class="db-empty-icon" style="font-size:28px;opacity:.3">📭</div><p>Sin pagos registrados</p></div>`;
            footerEl.textContent = '';
            return;
        }

        const total      = _histPayments.length;
        const totalPages = Math.ceil(total / HIST_PAGE);
        const start      = (_histPage - 1) * HIST_PAGE;
        const end        = Math.min(start + HIST_PAGE, total);
        const slice      = _histPayments.slice(start, end);

        // ── MÓVIL ──
        const mobileHtml = '<div class="db-hist-list">' + slice.map((p, idx) => {
            const uid = `dbh${_histPage}_${idx}`;
            const dt  = new Date(p.payment_date + 'T00:00:00').toLocaleDateString('es-DO', { day:'2-digit', month:'2-digit', year:'numeric' });
            return `
            <div class="db-hist-item">
                <div class="db-hist-scroll" id="${uid}">
                    <div class="db-hist-panel">
                        <div class="db-hist-dot">↑</div>
                        <div class="db-hist-main">
                            <div class="db-hist-date">${dt}</div>
                            <div class="db-hist-sub">${dbEsc(p.account_name || '—')}</div>
                        </div>
                        <div class="db-hist-amt">${dbEsc(p.symbol || 'RD$')} ${fmt(p.amount)}</div>
                    </div>
                    <div class="db-hist-panel-extra">
                        <div class="db-hist-extra-col">
                            <div class="db-ex-label">Cuenta</div>
                            <div class="db-ex-val">${dbEsc(p.account_name || '—')}</div>
                        </div>
                        <div class="db-ex-divider"></div>
                        <button class="db-btn-revert" onclick="DebtModule.confirmRevertPayment(${p.id}, ${p.amount})">
                            ↩ Revertir
                        </button>
                    </div>
                </div>
                <div class="db-hist-dots">
                    <div class="db-dot-ind active" id="dbd0-${uid}"></div>
                    <div class="db-dot-ind" id="dbd1-${uid}"></div>
                </div>
            </div>`;
        }).join('') + '</div>';

        // ── DESKTOP ──
        let totalAmt = 0;
        const desktopRows = slice.map((p, i) => {
            totalAmt += parseFloat(p.amount);
            const dt = new Date(p.payment_date + 'T00:00:00').toLocaleDateString('es-DO', { day:'2-digit', month:'short', year:'numeric' });
            return `<tr id="payment-row-${p.id}">
                <td style="color:#9ca3af;font-size:11px">${start+i+1}</td>
                <td style="white-space:nowrap;color:#9ca3af;font-size:12px">${dt}</td>
                <td>${dbEsc(p.account_name || '—')}</td>
                <td style="font-weight:600;color:#1D9E75">${dbEsc(p.symbol||'RD$')} ${fmt(p.amount)}</td>
                <td><button class="db-tbl-revert" onclick="DebtModule.confirmRevertPayment(${p.id}, ${p.amount})">↩ Revertir</button></td>
            </tr>`;
        }).join('');

        const desktopHtml = `<div class="db-hist-table-wrap">
            <table class="db-hist-table">
                <thead><tr>
                    <th>#</th><th>Fecha</th><th>Cuenta</th><th>Monto</th><th></th>
                </tr></thead>
                <tbody>${desktopRows}</tbody>
                <tfoot><tr>
                    <td colspan="3">Total (${total} pago${total!==1?'s':''})</td>
                    <td style="color:#1D9E75">RD$ ${fmt(totalAmt)}</td>
                    <td></td>
                </tr></tfoot>
            </table>
        </div>`;

        container.innerHTML = mobileHtml + desktopHtml;

        // Dots scroll móvil
        slice.forEach((_, idx) => {
            const uid = `dbh${_histPage}_${idx}`;
            const sc  = document.getElementById(uid);
            if (!sc) return;
            sc.addEventListener('scroll', () => {
                const at = sc.scrollLeft > sc.scrollWidth * 0.3;
                document.getElementById('dbd0-' + uid)?.classList.toggle('active', !at);
                document.getElementById('dbd1-' + uid)?.classList.toggle('active', at);
            });
        });

        footerEl.textContent = `${start+1}–${end} de ${total} pagos`;

        // Paginación
        let pagEl = document.getElementById('db-hist-pag');
        if (!pagEl) {
            pagEl = document.createElement('div');
            pagEl.id = 'db-hist-pag';
            container.insertAdjacentElement('afterend', pagEl);
        }
        if (totalPages <= 1) { pagEl.innerHTML = ''; return; }
        let ph = '<div class="db-pagination">';
        ph += `<a class="db-pag-btn ${_histPage<=1?'disabled':''}" href="#" onclick="DebtModule._changePage(1);return false;">«</a>`;
        ph += `<a class="db-pag-btn ${_histPage<=1?'disabled':''}" href="#" onclick="DebtModule._changePage(${_histPage-1});return false;">‹</a>`;
        const s=Math.max(1,_histPage-2), e=Math.min(totalPages,_histPage+2);
        if(s>1) ph+=`<span class="db-pag-btn disabled">…</span>`;
        for(let i=s;i<=e;i++) ph+=`<a class="db-pag-btn ${i===_histPage?'active':''}" href="#" onclick="DebtModule._changePage(${i});return false;">${i}</a>`;
        if(e<totalPages) ph+=`<span class="db-pag-btn disabled">…</span>`;
        ph += `<a class="db-pag-btn ${_histPage>=totalPages?'disabled':''}" href="#" onclick="DebtModule._changePage(${_histPage+1});return false;">›</a>`;
        ph += `<a class="db-pag-btn ${_histPage>=totalPages?'disabled':''}" href="#" onclick="DebtModule._changePage(${totalPages});return false;">»</a>`;
        ph += '</div>';
        pagEl.innerHTML = ph;
    }

    function _changePage(p) {
        const total = Math.ceil(_histPayments.length / HIST_PAGE);
        if (p < 1 || p > total) return;
        _histPage = p;
        renderHistPage();
    }

    function applyHistoryFilter() {
        if (!_historyDebtId) return;
        loadHistory(_historyDebtId,
            document.getElementById('history-date-from').value,
            document.getElementById('history-date-to').value);
    }

    function clearHistoryFilter() {
        document.getElementById('history-date-from').value = '';
        document.getElementById('history-date-to').value   = '';
        if (_historyDebtId) loadHistory(_historyDebtId);
    }

    // ─── Revertir pago ────────────────────────────

    function confirmRevertPayment(paymentId, amount) {
        Swal.fire({
            title: '¿Revertir pago?',
            html: `Se eliminará el pago de <strong>RD$ ${fmt(amount)}</strong> y el dinero volverá a la cuenta de origen.`,
            icon: 'warning', showCancelButton: true,
            confirmButtonColor: '#dc2626', cancelButtonColor: '#6b7280',
            confirmButtonText: 'Sí, revertir', cancelButtonText: 'Cancelar',
        }).then(async result => {
            if (!result.isConfirmed) return;
            try {
                const data = await request('delete_payment', { payment_id: paymentId });
                showSuccess(data.message);

                // Quitar fila de móvil y desktop
                document.getElementById(`payment-row-${paymentId}`)?.remove();
                _histPayments = _histPayments.filter(p => p.id != paymentId);

                // Actualizar resumen
                if (_histPayments.length) {
                    let total = 0;
                    _histPayments.forEach(p => { total += parseFloat(p.amount); });
                    document.getElementById('hist-total-paid').textContent = 'RD$ ' + fmt(total);
                    document.getElementById('hist-count').textContent = _histPayments.length + ' pago' + (_histPayments.length!==1?'s':'');
                    document.getElementById('hist-avg').textContent   = 'RD$ ' + fmt(total/_histPayments.length);
                } else {
                    document.getElementById('history-summary-bar').style.display = 'none';
                }

                // Re-renderizar con datos actualizados
                renderHistPage();

                // Actualizar tarjeta de deuda en fondo
                const debt = _debts.find(d => d.id == data.debt_id);
                if (debt) {
                    debt.paid_amount = parseFloat(data.new_paid);
                    debt.pending     = parseFloat(debt.total_amount) - parseFloat(data.new_paid);
                    if (debt.status === 'paid' && data.new_paid < debt.total_amount) debt.status = 'pending';
                    const el = document.getElementById(`debt-card-${data.debt_id}`);
                    if (el) el.outerHTML = renderDebtCard(debt);
                }
            } catch (err) {
                showError(err.message, err.fullMessage);
            }
        });
    }

    // ─── Init ─────────────────────────────────────

    document.getElementById('debt-due-date').value =
        new Date(Date.now() + 30 * 864e5).toISOString().split('T')[0];

    loadDebts();

    return { addDebt, showPayModal, confirmDelete, loadDebts, showHistory,
             applyHistoryFilter, clearHistoryFilter, confirmRevertPayment, _changePage };
})();

console.log('[DebtModule] Módulo cargado.');
</script>