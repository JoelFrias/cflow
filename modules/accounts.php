<?php
// modules/accounts.php

$stmt = $pdo->query("SELECT * FROM currencies ORDER BY code");
$currencies = $stmt->fetchAll();
?>

<!-- ============================================ -->
<!-- MÓDULO DE CUENTAS — DISEÑO LIMPIO            -->
<!-- ============================================ -->
<div id="accounts-module">

    <!-- ── Balance total ── -->
    <div class="acc-total-bar">
        <div>
            <div class="acc-total-label">Balance Total Consolidado</div>
            <div class="acc-total-value" id="balance-total-value">—</div>
        </div>
        <button class="btn-new-acc" data-bs-toggle="modal" data-bs-target="#createAccountModal">
            <span style="font-size:16px;line-height:1">+</span> Nueva Cuenta
        </button>
    </div>

    <!-- ── Grid de cuentas ── -->
    <div id="accounts-list">
        <div class="acc-loading">
            <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
            <span>Cargando cuentas…</span>
        </div>
    </div>

</div>

<!-- ============================================ -->
<!-- MODAL: CREAR CUENTA                          -->
<!-- ============================================ -->
<div class="modal fade" id="createAccountModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content acc-modal-content">
            <div class="modal-header acc-modal-header">
                <h5 class="modal-title" style="font-size:16px;font-weight:500">Nueva Cuenta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:20px 24px">
                <div class="mb-3">
                    <label class="acc-form-label">Nombre</label>
                    <input type="text" id="acc-name" class="form-control acc-form-control"
                           placeholder="Ej: Banco Popular, Efectivo Casa, PayPal">
                </div>
                <div class="mb-3">
                    <label class="acc-form-label">Tipo</label>
                    <select id="acc-type" class="form-control acc-form-control">
                        <option value="cash">Efectivo</option>
                        <option value="bank">Cuenta Bancaria</option>
                        <option value="wallet">Wallet Digital</option>
                    </select>
                    <div style="font-size:11px;color:#9ca3af;margin-top:4px">Las tarjetas se gestionan en el módulo "Tarjetas"</div>
                </div>
                <div class="mb-3">
                    <label class="acc-form-label">Moneda</label>
                    <select id="acc-currency" class="form-control acc-form-control">
                        <?php foreach($currencies as $cur): ?>
                        <option value="<?= htmlspecialchars($cur['code']) ?>">
                            <?= htmlspecialchars($cur['name']) ?> (<?= htmlspecialchars($cur['symbol']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-1">
                    <label class="acc-form-label">Saldo inicial</label>
                    <input type="number" step="0.01" id="acc-balance" class="form-control acc-form-control" value="0">
                </div>
            </div>
            <div class="modal-footer" style="padding:14px 24px;border-top:1px solid #f0f0f0">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn-save-acc" id="btn-create-account">
                    <span id="btn-create-text">Crear Cuenta</span>
                    <span id="btn-create-spinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- MODAL: EDITAR CUENTA                         -->
<!-- ============================================ -->
<div class="modal fade" id="editAccountModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content acc-modal-content">
            <div class="modal-header acc-modal-header">
                <h5 class="modal-title" style="font-size:16px;font-weight:500">Editar Cuenta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:20px 24px">
                <input type="hidden" id="edit-account-id">
                <div class="mb-3">
                    <label class="acc-form-label">Nombre</label>
                    <input type="text" id="edit-acc-name" class="form-control acc-form-control">
                </div>
                <div class="mb-3">
                    <label class="acc-form-label">Tipo</label>
                    <select id="edit-acc-type" class="form-control acc-form-control">
                        <option value="cash">Efectivo</option>
                        <option value="bank">Cuenta Bancaria</option>
                        <option value="wallet">Wallet Digital</option>
                    </select>
                </div>
                <div class="mb-1">
                    <label class="acc-form-label">Moneda</label>
                    <select id="edit-acc-currency" class="form-control acc-form-control">
                        <?php foreach($currencies as $cur): ?>
                        <option value="<?= htmlspecialchars($cur['code']) ?>">
                            <?= htmlspecialchars($cur['name']) ?> (<?= htmlspecialchars($cur['symbol']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer" style="padding:14px 24px;border-top:1px solid #f0f0f0">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn-save-acc" id="btn-update-account">
                    <span id="btn-update-text">Guardar Cambios</span>
                    <span id="btn-update-spinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- MODAL: HISTORIAL DE CUENTA                   -->
<!-- ============================================ -->
<div class="modal fade" id="accountHistoryModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content acc-modal-content">
            <div class="modal-header acc-modal-header">
                <div>
                    <h5 class="modal-title" style="font-size:16px;font-weight:500" id="acc-history-name">Historial</h5>
                    <div style="font-size:12px;color:#9ca3af;margin-top:2px">Transacciones de la cuenta</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:20px 24px">
                <input type="hidden" id="acc-history-id">

                <!-- Resumen rápido -->
                <div id="acc-history-summary" style="display:none;grid-template-columns:1fr 1fr 1fr;gap:10px;margin-bottom:18px">
                    <div class="acc-sum-card">
                        <div class="acc-sum-label">Ingresos</div>
                        <div class="acc-sum-val" style="color:#1D9E75" id="acc-summary-income">—</div>
                    </div>
                    <div class="acc-sum-card">
                        <div class="acc-sum-label">Egresos</div>
                        <div class="acc-sum-val" style="color:#D85A30" id="acc-summary-expense">—</div>
                    </div>
                    <div class="acc-sum-card">
                        <div class="acc-sum-label">Neto del período</div>
                        <div class="acc-sum-val" id="acc-summary-net">—</div>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="acc-history-filters">
                    <div>
                        <label class="acc-form-label">Desde</label>
                        <input type="date" id="acc-filter-date-from" class="acc-form-control">
                    </div>
                    <div>
                        <label class="acc-form-label">Hasta</label>
                        <input type="date" id="acc-filter-date-to" class="acc-form-control">
                    </div>
                    <div>
                        <label class="acc-form-label">Tipo</label>
                        <select id="acc-filter-type" class="acc-form-control">
                            <option value="">Todos</option>
                            <option value="income">Ingreso</option>
                            <option value="expense">Gasto</option>
                            <option value="transfer">Transferencia</option>
                        </select>
                    </div>
                    <div style="display:flex;align-items:flex-end">
                        <button class="btn-apply-hist" id="btn-acc-apply-filters">Aplicar</button>
                    </div>
                </div>

                <!-- Hint móvil -->
                <div class="acc-swipe-hint" id="accSwipeHint" style="display:none">
                    Desliza cada fila → para ver más
                </div>

                <!-- Contenido -->
                <div id="acc-history-container">
                    <div class="acc-loading">
                        <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
                        <span>Cargando transacciones…</span>
                    </div>
                </div>

                <!-- Paginación + contador -->
                <div id="acc-history-count" style="font-size:11px;color:#9ca3af;text-align:right;margin-top:8px"></div>
                <div id="acc-pagination" style="margin-top:10px"></div>
            </div>
            <div class="modal-footer" style="padding:14px 24px;border-top:1px solid #f0f0f0">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- MODAL: ESTADO DE CUENTA                      -->
<!-- ============================================ -->
<div class="modal fade" id="statementModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:460px">
        <div class="modal-content acc-modal-content">

            <div class="modal-header acc-modal-header">
                <div>
                    <h5 class="modal-title" style="font-size:16px;font-weight:600;color:#1f2937">
                        Estado de Cuenta
                    </h5>
                    <div style="font-size:12px;color:#9ca3af;margin-top:2px" id="stmt-account-label">—</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body" style="padding:20px 24px">
                <input type="hidden" id="stmt-account-id">

                <!-- Instrucción -->
                <p class="stmt-hint">
                    Selecciona el mes y año para generar el estado. Puedes agregar
                    varios períodos y se abrirá un PDF por cada uno.
                </p>

                <!-- Lista de períodos -->
                <div id="stmt-periods-list"></div>

                <!-- Botón agregar -->
                <button class="stmt-btn-add" id="btn-stmt-add-period" type="button">
                    <span style="font-size:15px;line-height:1">+</span>
                    Agregar otro período
                </button>
            </div>

            <div class="modal-footer" style="padding:14px 24px;border-top:1px solid #f0f0f0;gap:8px">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="stmt-btn-generate" id="btn-stmt-generate">
                    <span id="stmt-gen-text">&#128196; Generar PDF(s)</span>
                    <span id="stmt-gen-spinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
                </button>
            </div>

        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- ESTILOS                                      -->
<!-- ============================================ -->
<style>
#accounts-module {
    --acc-radius: 10px;
    --acc-border: #e8e8e8;
    --acc-inc: #1D9E75;
    --acc-exp: #D85A30;
    --acc-trf: #3b82f6;
    --acc-inc-bg: #eaf3de;
    --acc-exp-bg: #faece7;
    --acc-trf-bg: #eff6ff;
    --acc-inc-text: #3B6D11;
    --acc-exp-text: #993C1D;
    --acc-trf-text: #1e40af;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
}

/* ── Barra de total ── */
.acc-total-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #fff;
    border: 1px solid var(--acc-border);
    border-radius: var(--acc-radius);
    padding: 16px 20px;
    margin-bottom: 16px;
}
.acc-total-label {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: #9ca3af;
    margin-bottom: 4px;
}
.acc-total-value {
    font-size: 22px;
    font-weight: 600;
    color: #1f2937;
}
.btn-new-acc {
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
.btn-new-acc:hover { background: #1f2937; }

/* ── Loading / empty ── */
.acc-loading {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 48px 0;
    color: #9ca3af;
    font-size: 14px;
}
.acc-empty {
    text-align: center;
    padding: 56px 20px;
    color: #9ca3af;
}
.acc-empty-icon {
    font-size: 36px;
    margin-bottom: 10px;
    opacity: .35;
}
.acc-empty p { font-size: 14px; margin-bottom: 14px; }

/* ── Grid de cuentas ── */
.acc-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 10px;
}

/* ── Tarjeta de cuenta ── */
.acc-card {
    background: #fff;
    border: 1px solid var(--acc-border);
    border-radius: var(--acc-radius);
    padding: 16px 18px;
    transition: box-shadow .15s, border-color .15s;
    position: relative;
}
.acc-card:hover {
    border-color: #d1d5db;
    box-shadow: 0 2px 12px rgba(0,0,0,.05);
}
.acc-card-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 10px;
}
.acc-card-name {
    font-size: 14px;
    font-weight: 600;
    color: #1f2937;
    line-height: 1.3;
    max-width: 170px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.acc-card-actions {
    display: flex;
    gap: 4px;
    flex-shrink: 0;
}
.acc-action-btn {
    width: 30px;
    height: 30px;
    border-radius: 8px;
    border: 1px solid var(--acc-border);
    background: #f9fafb;
    color: #6b7280;
    font-size: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all .12s;
}
.acc-action-btn:hover { background: #f3f4f6; color: #374151; border-color: #d1d5db; }
.acc-action-btn.del:hover { background: #fef2f2; color: #dc2626; border-color: #fecaca; }
.acc-card-badges {
    display: flex;
    gap: 6px;
    margin-bottom: 12px;
}
.acc-pill {
    font-size: 10px;
    font-weight: 500;
    padding: 2px 9px;
    border-radius: 20px;
    letter-spacing: .03em;
}
.pill-type { background: #f3f4f6; color: #6b7280; border: 1px solid #e5e7eb; }
.pill-currency { background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }
.acc-card-balance {
    font-size: 22px;
    font-weight: 700;
    color: #1f2937;
    letter-spacing: -.01em;
}
.acc-card-balance-label {
    font-size: 10px;
    color: #9ca3af;
    margin-top: 2px;
    text-transform: uppercase;
    letter-spacing: .04em;
}

/* ── Modal ── */
.acc-modal-content {
    border: none;
    border-radius: 14px;
    box-shadow: 0 20px 60px rgba(0,0,0,.12);
}
.acc-modal-header {
    padding: 18px 24px 12px;
    border-bottom: 1px solid #f3f4f6;
}
.acc-form-label {
    display: block;
    font-size: 11px !important;
    font-weight: 600 !important;
    color: #9ca3af !important;
    text-transform: uppercase;
    letter-spacing: .04em;
    margin-bottom: 5px !important;
}
.acc-form-control {
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
.acc-form-control:focus {
    border-color: #a5b4fc !important;
    background: #fff !important;
    box-shadow: 0 0 0 3px rgba(165,180,252,.15) !important;
}
.btn-save-acc {
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
.btn-save-acc:hover { background: #1f2937; }

/* ── Historial: resumen ── */
.acc-sum-card {
    background: #f9fafb;
    border: 1px solid var(--acc-border);
    border-radius: var(--acc-radius);
    padding: 12px 14px;
}
.acc-sum-label {
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: #9ca3af;
    margin-bottom: 4px;
}
.acc-sum-val { font-size: 16px; font-weight: 600; }

/* ── Historial: filtros ── */
.acc-history-filters {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr auto;
    gap: 10px;
    margin-bottom: 14px;
    align-items: end;
}
@media (max-width: 767px) {
    .acc-history-filters { grid-template-columns: 1fr 1fr; }
    #acc-history-summary { grid-template-columns: 1fr !important; }
}
.btn-apply-hist {
    padding: 8px 18px;
    background: #374151;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 13px;
    cursor: pointer;
    white-space: nowrap;
    transition: background .15s;
    height: 38px;
}
.btn-apply-hist:hover { background: #1f2937; }

/* ── Historial: hint móvil ── */
.acc-swipe-hint {
    text-align: center;
    font-size: 11px;
    color: #b0b7c3;
    margin-bottom: 8px;
    letter-spacing: .02em;
}

/* ── Historial: lista móvil ── */
.acc-hist-list {
    display: flex;
    flex-direction: column;
    gap: 2px;
}
.acc-hist-item {
    border-radius: 8px;
    background: #fff;
    border: 1px solid var(--acc-border);
    overflow: hidden;
}
.acc-hist-scroll {
    display: flex;
    overflow-x: auto;
    scroll-snap-type: x mandatory;
    scrollbar-width: none;
    -ms-overflow-style: none;
}
.acc-hist-scroll::-webkit-scrollbar { display: none; }
.acc-hist-panel {
    flex: 0 0 100%;
    scroll-snap-align: start;
    padding: 11px 14px;
    display: flex;
    align-items: center;
    gap: 12px;
    min-height: 60px;
}
.acc-hist-panel-extra {
    flex: 0 0 100%;
    scroll-snap-align: start;
    padding: 11px 14px;
    display: flex;
    align-items: center;
    gap: 10px;
    background: #f8f9fb;
}
.acc-hist-dot {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 14px;
    font-weight: 700;
}
.hdot-inc { background: var(--acc-inc-bg); color: var(--acc-inc-text); }
.hdot-exp { background: var(--acc-exp-bg); color: var(--acc-exp-text); }
.hdot-trf { background: var(--acc-trf-bg); color: var(--acc-trf-text); }
.acc-hist-main { flex: 1; min-width: 0; }
.acc-hist-cat {
    font-size: 13px;
    font-weight: 500;
    color: #1f2937;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.acc-hist-date { font-size: 11px; color: #9ca3af; margin-top: 2px; }
.acc-hist-amt { text-align: right; flex-shrink: 0; }
.acc-hist-amt-main { font-size: 14px; font-weight: 600; }
.hamt-inc { color: var(--acc-inc); }
.hamt-exp { color: var(--acc-exp); }
.hamt-trf { color: var(--acc-trf); }
.acc-hist-extra-col { flex: 1; min-width: 0; }
.acc-ex-label {
    font-size: 9px;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: .05em;
    margin-bottom: 2px;
}
.acc-ex-val {
    font-size: 12px;
    color: #374151;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.acc-ex-divider { width: 1px; height: 28px; background: var(--acc-border); flex-shrink: 0; }
.acc-hist-dots {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    padding: 4px 0 3px;
}
.acc-dot-ind { width: 5px; height: 5px; border-radius: 50%; background: #d1d5db; transition: background .2s; }
.acc-dot-ind.active { background: #6b7280; }

/* ── Historial: tabla desktop ── */
.acc-hist-table-wrap { overflow-x: auto; display: none; }
.acc-hist-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}
.acc-hist-table thead tr { border-bottom: 2px solid #f3f4f6; }
.acc-hist-table thead th {
    padding: 9px 12px;
    text-align: left;
    font-size: 10px;
    font-weight: 600;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: .05em;
    white-space: nowrap;
}
.acc-hist-table tbody tr {
    border-bottom: 1px solid #f9fafb;
    transition: background .1s;
}
.acc-hist-table tbody tr:last-child { border-bottom: none; }
.acc-hist-table tbody tr:hover { background: #fafafa; }
.acc-hist-table td { padding: 10px 12px; color: #374151; vertical-align: middle; }
.acc-badge {
    display: inline-flex;
    align-items: center;
    padding: 2px 8px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 500;
}
.abadge-inc { background: var(--acc-inc-bg); color: var(--acc-inc-text); }
.abadge-exp { background: var(--acc-exp-bg); color: var(--acc-exp-text); }
.abadge-trf { background: var(--acc-trf-bg); color: var(--acc-trf-text); }

/* ── Paginación ── */
.acc-pagination {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    flex-wrap: wrap;
}
.acc-pag-btn {
    width: 32px; height: 32px;
    border-radius: 8px;
    border: 1px solid var(--acc-border);
    background: #fff;
    color: #6b7280;
    font-size: 13px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all .1s;
    text-decoration: none;
}
.acc-pag-btn:hover { background: #f3f4f6; color: #1f2937; }
.acc-pag-btn.active { background: #374151; color: #fff; border-color: #374151; }
.acc-pag-btn.disabled { opacity: .4; pointer-events: none; }

/* ── Responsive ── */
@media (min-width: 768px) {
    .acc-hist-list { display: none !important; }
    .acc-hist-table-wrap { display: block !important; }
    .acc-swipe-hint { display: none !important; }
}

/* ── Modal Estado de Cuenta ── */
.stmt-hint {
    font-size: 12px;
    color: #9ca3af;
    margin-bottom: 14px;
    line-height: 1.5;
}

/* Fila de período */
.stmt-period-row {
    display: grid;
    grid-template-columns: 1fr 1fr auto;
    gap: 8px;
    align-items: end;
    margin-bottom: 10px;
    animation: stmtFadeIn .2s ease;
}
@keyframes stmtFadeIn {
    from { opacity:0; transform:translateY(-6px); }
    to   { opacity:1; transform:translateY(0); }
}
.stmt-period-row .acc-form-label { margin-bottom: 4px !important; }
.stmt-remove-btn {
    width: 34px;
    height: 36px;
    border: 1px solid #fecaca;
    background: #fef2f2;
    color: #dc2626;
    border-radius: 8px;
    font-size: 14px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all .12s;
    flex-shrink: 0;
}
.stmt-remove-btn:hover { background: #fee2e2; }

/* Botón agregar período */
.stmt-btn-add {
    display: flex;
    align-items: center;
    gap: 6px;
    background: #f3f4f6;
    border: 1px dashed #d1d5db;
    border-radius: 8px;
    padding: 8px 14px;
    font-size: 13px;
    color: #6b7280;
    cursor: pointer;
    width: 100%;
    justify-content: center;
    transition: all .15s;
    margin-top: 4px;
}
.stmt-btn-add:hover { background: #e5e7eb; color: #374151; border-color: #9ca3af; }

/* Botón generar */
.stmt-btn-generate {
    padding: 8px 22px;
    background: #1e3a5f;
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
.stmt-btn-generate:hover { background: #162d4a; }
.stmt-btn-generate:disabled { opacity: .6; cursor: not-allowed; }
</style>

<!-- ============================================ -->
<!-- JAVASCRIPT                                   -->
<!-- ============================================ -->
<script>
const ACCOUNTS_AJAX_URL = 'ajax/accounts.php';

const ACC_TYPE_NAMES = { cash: 'Efectivo', bank: 'Banco', wallet: 'Wallet' };

// ─── Helpers ──────────────────────────────────

function accShowError(msg, full) {
    console.error('[Accounts]', full ?? msg);
    Swal.fire({ toast: true, position: 'top-start', icon: 'error', title: msg,
        showConfirmButton: false, timer: 4000, timerProgressBar: true });
}
function accShowSuccess(msg) {
    Swal.fire({ toast: true, position: 'top-start', icon: 'success', title: msg,
        showConfirmButton: false, timer: 3000, timerProgressBar: true });
}
function accEsc(str) {
    return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
}
function accFmt(n, sym) {
    return (sym ? accEsc(sym) + ' ' : '') +
        parseFloat(n).toLocaleString('es-DO', { minimumFractionDigits: 2 });
}

// ─── Renderizar tarjeta ────────────────────────

function renderAccountCard(acc) {
    const typeName = ACC_TYPE_NAMES[acc.type] || acc.type;
    const balance  = parseFloat(acc.balance).toLocaleString('es-DO', { minimumFractionDigits: 2 });
    const dataAcc  = accEsc(JSON.stringify(acc).replace(/'/g, "&#39;"));

    return `
    <div class="acc-card" id="account-card-${acc.id}">
        <div class="acc-card-top">
            <div class="acc-card-name" title="${accEsc(acc.name)}">${accEsc(acc.name)}</div>
            <div class="acc-card-actions">
                <button class="acc-action-btn btn-statement-account"
                        data-id="${acc.id}" data-name="${accEsc(acc.name)}" title="Estado de Cuenta"
                        style="font-size:13px">
                    <i class="fa-regular fa-file"></i>
                </button>
                <button class="acc-action-btn btn-edit-account"
                        data-account='${dataAcc}' title="Editar">
                    <i class="fa-regular fa-pen-to-square"></i>
                </button>
                <button class="acc-action-btn del btn-delete-account"
                        data-id="${acc.id}" data-name="${accEsc(acc.name)}" title="Eliminar">
                    <i class="fa-solid fa-x"></i>
                </button>
            </div>
        </div>
        <div class="acc-card-badges">
            <span class="acc-pill pill-type">${typeName}</span>
            <span class="acc-pill pill-currency">${accEsc(acc.currency_code)}</span>
        </div>
        <div class="acc-card-balance">${accEsc(acc.symbol)} ${balance}</div>
        <div class="acc-card-balance-label">Saldo actual</div>
    </div>`;
}

function renderEmptyState() {
    return `<div class="acc-empty" id="accounts-empty">
        <div class="acc-empty-icon">🏦</div>
        <p>No tienes cuentas registradas aún</p>
        <button class="btn-new-acc" data-bs-toggle="modal" data-bs-target="#createAccountModal">
            <span>+</span> Crear primera cuenta
        </button>
    </div>`;
}

// ─── Cargar cuentas ────────────────────────────

function loadAccounts() {
    fetch(ACCOUNTS_AJAX_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=get_accounts',
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) {
            accShowError(data.message, data.full_message);
            document.getElementById('accounts-list').innerHTML = renderEmptyState();
            return;
        }
        const totalDop = parseFloat(data.total_dop).toLocaleString('es-DO', { minimumFractionDigits: 2 });
        document.getElementById('balance-total-value').textContent = 'RD$ ' + totalDop;

        const list = document.getElementById('accounts-list');
        if (!data.accounts.length) {
            list.innerHTML = renderEmptyState();
        } else {
            list.innerHTML = '<div class="acc-grid">' + data.accounts.map(renderAccountCard).join('') + '</div>';
            attachCardEvents();
        }
    })
    .catch(err => {
        accShowError('No se pudieron cargar las cuentas.', err.message);
        document.getElementById('accounts-list').innerHTML = renderEmptyState();
    });
}

// ─── Eventos de tarjetas ──────────────────────

function attachCardEvents() {
    document.querySelectorAll('.btn-edit-account').forEach(btn => {
        btn.addEventListener('click', e => {
            e.stopPropagation();
            openEditModal(JSON.parse(btn.dataset.account));
        });
    });
    document.querySelectorAll('.btn-delete-account').forEach(btn => {
        btn.addEventListener('click', e => {
            e.stopPropagation();
            deleteAccount(btn.dataset.id, btn.dataset.name);
        });
    });
    document.querySelectorAll('.btn-statement-account').forEach(btn => {
    btn.addEventListener('click', e => {
        e.stopPropagation();
        openStatementModal(btn.dataset.id, btn.dataset.name);
    });
});
}

// ─── Historial ────────────────────────────────

let _accTxAll   = [];
let _accPage    = 1;
let _accSymbol  = '';
const ACC_PAGE  = 10;

function loadAccountTx(id) {
    const container = document.getElementById('acc-history-container');
    container.innerHTML = '<div class="acc-loading"><div class="spinner-border spinner-border-sm text-secondary" role="status"></div><span>Cargando…</span></div>';
    document.getElementById('acc-history-summary').style.display = 'none';

    fetch(ACCOUNTS_AJAX_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            action:     'get_account_transactions',
            account_id: id,
            date_from:  document.getElementById('acc-filter-date-from').value,
            date_to:    document.getElementById('acc-filter-date-to').value,
            type:       document.getElementById('acc-filter-type').value,
        }),
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) { accShowError(data.message, data.full_message); return; }

        _accTxAll  = data.transactions || [];
        _accSymbol = data.currency_symbol || '';
        _accPage   = 1;

        // Resumen
        let inc = 0, exp = 0;
        _accTxAll.forEach(t => {
            const a = parseFloat(t.amount) || 0;
            if (t.type === 'income')  inc += a;
            if (t.type === 'expense') exp += a;
        });
        if (_accTxAll.length) {
            const net   = inc - exp;
            const s     = _accSymbol;
            const fmt2  = v => v.toLocaleString('es-DO', { minimumFractionDigits: 2 });
            document.getElementById('acc-summary-income').textContent  = s + ' ' + fmt2(inc);
            document.getElementById('acc-summary-expense').textContent = s + ' ' + fmt2(exp);
            const netEl = document.getElementById('acc-summary-net');
            netEl.textContent = s + ' ' + fmt2(Math.abs(net));
            netEl.style.color = net >= 0 ? '#1D9E75' : '#D85A30';
            const sumBar = document.getElementById('acc-history-summary');
            sumBar.style.display = 'grid';
            sumBar.style.gridTemplateColumns = '1fr 1fr 1fr';
            sumBar.style.gap = '10px';
            sumBar.style.marginBottom = '18px';
        }

        // Hint móvil
        const hint = document.getElementById('accSwipeHint');
        if (hint) hint.style.display = window.innerWidth < 768 && _accTxAll.length ? 'block' : 'none';

        renderAccPage();
    })
    .catch(err => {
        accShowError('Error de conexión.', err.message);
        document.getElementById('acc-history-container').innerHTML =
            '<div class="acc-empty"><p style="color:#D85A30">Error al cargar transacciones.</p></div>';
    });
}

function renderAccPage() {
    const container = document.getElementById('acc-history-container');
    const countEl   = document.getElementById('acc-history-count');
    const paginEl   = document.getElementById('acc-pagination');

    if (!_accTxAll.length) {
        container.innerHTML = `<div class="acc-empty">
            <div class="acc-empty-icon" style="font-size:28px;opacity:.3">📭</div>
            <p>No hay transacciones que coincidan</p></div>`;
        countEl.textContent  = '';
        paginEl.innerHTML    = '';
        return;
    }

    const total      = _accTxAll.length;
    const totalPages = Math.ceil(total / ACC_PAGE);
    const start      = (_accPage - 1) * ACC_PAGE;
    const end        = Math.min(start + ACC_PAGE, total);
    const slice      = _accTxAll.slice(start, end);

    const dotCls = { income: 'hdot-inc', expense: 'hdot-exp', transfer: 'hdot-trf' };
    const amtCls = { income: 'hamt-inc', expense: 'hamt-exp', transfer: 'hamt-trf' };
    const icons  = { income: '↓', expense: '↑', transfer: '⇄' };
    const signs  = { income: '+', expense: '−', transfer: '' };

    // ── MÓVIL ──
    const mobileHtml = '<div class="acc-hist-list">' + slice.map(t => {
        const d   = dotCls[t.type] || 'hdot-trf';
        const a   = amtCls[t.type] || 'hamt-trf';
        const ico = icons[t.type]  || '⇄';
        const sgn = signs[t.type]  || '';
        const sym = accEsc(t.currency_symbol || _accSymbol);
        const amt = parseFloat(t.amount).toLocaleString('es-DO', { minimumFractionDigits: 2 });
        const dt  = t.date ? new Date(t.date + 'T00:00:00').toLocaleDateString('es-DO', { day:'2-digit', month:'2-digit', year:'numeric' }) : '—';
        return `
        <div class="acc-hist-item">
            <div class="acc-hist-scroll" id="ahs${t.id}">
                <div class="acc-hist-panel">
                    <div class="acc-hist-dot ${d}">${ico}</div>
                    <div class="acc-hist-main">
                        <div class="acc-hist-cat">${accEsc(t.category_name || 'Sin categoría')}</div>
                        <div class="acc-hist-date">${dt}</div>
                    </div>
                    <div class="acc-hist-amt">
                        <div class="acc-hist-amt-main ${a}">${sgn} ${sym} ${amt}</div>
                    </div>
                </div>
                <div class="acc-hist-panel-extra">
                    <div class="acc-hist-extra-col">
                        <div class="acc-ex-label">Descripción</div>
                        <div class="acc-ex-val">${accEsc(t.description || '—')}</div>
                    </div>
                    <div class="acc-ex-divider"></div>
                    <div class="acc-hist-extra-col">
                        <div class="acc-ex-label">Tipo</div>
                        <div class="acc-ex-val">${t.type === 'income' ? 'Ingreso' : t.type === 'expense' ? 'Gasto' : 'Transferencia'}</div>
                    </div>
                </div>
            </div>
            <div class="acc-hist-dots">
                <div class="acc-dot-ind active" id="ahd0-${t.id}"></div>
                <div class="acc-dot-ind" id="ahd1-${t.id}"></div>
            </div>
        </div>`;
    }).join('') + '</div>';

    // ── DESKTOP ──
    const desktopRows = slice.map(t => {
        const bCls = t.type === 'income' ? 'abadge-inc' : t.type === 'expense' ? 'abadge-exp' : 'abadge-trf';
        const aCls = amtCls[t.type] || 'hamt-trf';
        const sgn  = signs[t.type] || '';
        const sym  = accEsc(t.currency_symbol || _accSymbol);
        const amt  = parseFloat(t.amount).toLocaleString('es-DO', { minimumFractionDigits: 2 });
        const lbl  = t.type === 'income' ? 'Ingreso' : t.type === 'expense' ? 'Gasto' : 'Transferencia';
        const dt   = t.date ? new Date(t.date + 'T00:00:00').toLocaleDateString('es-DO', { day:'2-digit', month:'short', year:'numeric' }) : '—';
        return `<tr>
            <td style="white-space:nowrap;color:#9ca3af;font-size:12px">${dt}</td>
            <td>${accEsc(t.description || '—')}</td>
            <td>${accEsc(t.category_name || '—')}</td>
            <td><span class="acc-badge ${bCls}">${lbl}</span></td>
            <td style="text-align:right" class="${aCls}">${sgn} ${sym} ${amt}</td>
        </tr>`;
    }).join('');

    const desktopHtml = `<div class="acc-hist-table-wrap">
        <table class="acc-hist-table">
            <thead><tr>
                <th>Fecha</th><th>Descripción</th><th>Categoría</th><th>Tipo</th>
                <th style="text-align:right">Monto</th>
            </tr></thead>
            <tbody>${desktopRows}</tbody>
        </table>
    </div>`;

    container.innerHTML = mobileHtml + desktopHtml;

    // Dots scroll móvil
    slice.forEach(t => {
        const sc = document.getElementById('ahs' + t.id);
        if (!sc) return;
        sc.addEventListener('scroll', () => {
            const at = sc.scrollLeft > sc.scrollWidth * 0.3;
            document.getElementById('ahd0-' + t.id)?.classList.toggle('active', !at);
            document.getElementById('ahd1-' + t.id)?.classList.toggle('active', at);
        });
    });

    // Contador
    countEl.textContent = `${start + 1}–${end} de ${total} transacciones`;

    // Paginación
    if (totalPages <= 1) { paginEl.innerHTML = ''; return; }
    let ph = '<div class="acc-pagination">';
    ph += `<a class="acc-pag-btn ${_accPage<=1?'disabled':''}" href="#" onclick="changeAccPage(1);return false;">«</a>`;
    ph += `<a class="acc-pag-btn ${_accPage<=1?'disabled':''}" href="#" onclick="changeAccPage(${_accPage-1});return false;">‹</a>`;
    const s2 = Math.max(1, _accPage-2), e2 = Math.min(totalPages, _accPage+2);
    if (s2>1) ph += `<span class="acc-pag-btn disabled">…</span>`;
    for (let i=s2; i<=e2; i++)
        ph += `<a class="acc-pag-btn ${i===_accPage?'active':''}" href="#" onclick="changeAccPage(${i});return false;">${i}</a>`;
    if (e2<totalPages) ph += `<span class="acc-pag-btn disabled">…</span>`;
    ph += `<a class="acc-pag-btn ${_accPage>=totalPages?'disabled':''}" href="#" onclick="changeAccPage(${_accPage+1});return false;">›</a>`;
    ph += `<a class="acc-pag-btn ${_accPage>=totalPages?'disabled':''}" href="#" onclick="changeAccPage(${totalPages});return false;">»</a>`;
    ph += '</div>';
    paginEl.innerHTML = ph;
}

function changeAccPage(p) {
    const total = Math.ceil(_accTxAll.length / ACC_PAGE);
    if (p < 1 || p > total) return;
    _accPage = p;
    renderAccPage();
    document.getElementById('acc-history-container').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

document.getElementById('btn-acc-apply-filters').addEventListener('click', () => {
    const id = document.getElementById('acc-history-id').value;
    if (id) loadAccountTx(id);
});

// ─── Editar cuenta ────────────────────────────

function openEditModal(acc) {
    document.getElementById('edit-account-id').value   = acc.id;
    document.getElementById('edit-acc-name').value     = acc.name;
    document.getElementById('edit-acc-type').value     = acc.type;
    document.getElementById('edit-acc-currency').value = acc.currency_code;
    new bootstrap.Modal(document.getElementById('editAccountModal')).show();
}

document.getElementById('btn-update-account').addEventListener('click', function () {
    const id       = document.getElementById('edit-account-id').value;
    const name     = document.getElementById('edit-acc-name').value.trim();
    const type     = document.getElementById('edit-acc-type').value;
    const currency = document.getElementById('edit-acc-currency').value;
    if (!name) { accShowError('El nombre es obligatorio.'); return; }

    const txt = document.getElementById('btn-update-text');
    const spn = document.getElementById('btn-update-spinner');
    txt.textContent = 'Guardando…'; spn.classList.remove('d-none'); this.disabled = true;

    fetch(ACCOUNTS_AJAX_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ action:'update_account', account_id:id, name, type, currency_code:currency }),
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) { accShowError(data.message, data.full_message); return; }
        accShowSuccess(data.message);
        bootstrap.Modal.getInstance(document.getElementById('editAccountModal')).hide();
        loadAccounts();
    })
    .catch(err => accShowError('No se pudo actualizar la cuenta.', err.message))
    .finally(() => {
        txt.textContent = 'Guardar Cambios'; spn.classList.add('d-none');
        document.getElementById('btn-update-account').disabled = false;
    });
});

// ─── Eliminar cuenta ──────────────────────────

function deleteAccount(id, name) {
    Swal.fire({
        title: '¿Eliminar cuenta?',
        html: `¿Seguro que deseas eliminar <strong>${accEsc(name)}</strong>?<br>Esta acción no se puede deshacer.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
    }).then(result => {
        if (!result.isConfirmed) return;
        fetch(ACCOUNTS_AJAX_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ action:'delete_account', account_id:id }),
        })
        .then(r => r.json())
        .then(data => {
            if (!data.success) { accShowError(data.message, data.full_message); return; }
            accShowSuccess(data.message);
            loadAccounts();
        })
        .catch(err => accShowError('Error al eliminar la cuenta.', err.message));
    });
}

// ─── Crear cuenta ─────────────────────────────

document.getElementById('btn-create-account').addEventListener('click', function () {
    const name     = document.getElementById('acc-name').value.trim();
    const type     = document.getElementById('acc-type').value;
    const currency = document.getElementById('acc-currency').value;
    const balance  = document.getElementById('acc-balance').value || '0';
    if (!name) { accShowError('El nombre de la cuenta es obligatorio.'); return; }

    const txt = document.getElementById('btn-create-text');
    const spn = document.getElementById('btn-create-spinner');
    txt.textContent = 'Creando…'; spn.classList.remove('d-none'); this.disabled = true;

    fetch(ACCOUNTS_AJAX_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ action:'create_account', name, type, currency_code:currency, initial_balance:balance }),
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) { accShowError(data.message, data.full_message); return; }
        accShowSuccess(data.message);
        bootstrap.Modal.getInstance(document.getElementById('createAccountModal')).hide();
        document.getElementById('acc-name').value    = '';
        document.getElementById('acc-balance').value = '0';
        loadAccounts();
    })
    .catch(err => accShowError('No se pudo crear la cuenta.', err.message))
    .finally(() => {
        txt.textContent = 'Crear Cuenta'; spn.classList.add('d-none');
        document.getElementById('btn-create-account').disabled = false;
    });
});

// ─── Estado de Cuenta ────────────────────────────────────────

const STMT_MONTHS = [
    'Enero','Febrero','Marzo','Abril','Mayo','Junio',
    'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'
];

let _stmtRowCount = 0;

function buildMonthOptions(selectedMonth) {
    return STMT_MONTHS.map((m, i) => {
        const val = i + 1;
        const sel = val === selectedMonth ? ' selected' : '';
        return `<option value="${val}"${sel}>${m}</option>`;
    }).join('');
}

function buildYearOptions(selectedYear) {
    const currentYear = new Date().getFullYear();
    let opts = '';
    for (let y = currentYear; y >= currentYear - 5; y--) {
        const sel = y === selectedYear ? ' selected' : '';
        opts += `<option value="${y}"${sel}>${y}</option>`;
    }
    return opts;
}

function addStatementPeriodRow(month, year) {
    _stmtRowCount++;
    const id      = _stmtRowCount;
    const now     = new Date();
    const selMonth = month ?? (now.getMonth() + 1);
    const selYear  = year  ?? now.getFullYear();

    const row = document.createElement('div');
    row.className = 'stmt-period-row';
    row.dataset.rowId = id;

    row.innerHTML = `
        <div>
            <label class="acc-form-label">Mes</label>
            <select class="acc-form-control stmt-month-sel" id="stmt-month-${id}">
                ${buildMonthOptions(selMonth)}
            </select>
        </div>
        <div>
            <label class="acc-form-label">Año</label>
            <select class="acc-form-control stmt-year-sel" id="stmt-year-${id}">
                ${buildYearOptions(selYear)}
            </select>
        </div>
        <button class="stmt-remove-btn" onclick="removeStatementRow(${id})" title="Quitar">✕</button>
    `;

    document.getElementById('stmt-periods-list').appendChild(row);
    updateRemoveButtons();
}

function removeStatementRow(id) {
    const row = document.querySelector(`.stmt-period-row[data-row-id="${id}"]`);
    if (row) {
        row.style.transition = 'opacity .15s, transform .15s';
        row.style.opacity = '0';
        row.style.transform = 'translateY(-4px)';
        setTimeout(() => { row.remove(); updateRemoveButtons(); }, 150);
    }
}

function updateRemoveButtons() {
    const rows = document.querySelectorAll('.stmt-period-row');
    rows.forEach(r => {
        const btn = r.querySelector('.stmt-remove-btn');
        if (btn) btn.style.visibility = rows.length > 1 ? 'visible' : 'hidden';
    });
}

function openStatementModal(accountId, accountName) {
    document.getElementById('stmt-account-id').value         = accountId;
    document.getElementById('stmt-account-label').textContent = accountName;
    document.getElementById('stmt-periods-list').innerHTML   = '';
    _stmtRowCount = 0;

    // Fila inicial con mes/año actual
    addStatementPeriodRow();

    new bootstrap.Modal(document.getElementById('statementModal')).show();
}

// Botón para agregar más períodos
document.getElementById('btn-stmt-add-period').addEventListener('click', () => {
    addStatementPeriodRow();
});

// Botón generar
document.getElementById('btn-stmt-generate').addEventListener('click', function () {
    const accountId = document.getElementById('stmt-account-id').value;
    if (!accountId) return;

    const rows = document.querySelectorAll('.stmt-period-row');
    if (!rows.length) {
        accShowError('Agrega al menos un período.');
        return;
    }

    // Recopilar períodos seleccionados
    const periods = [];
    const seen    = new Set();
    let hasDupe   = false;

    rows.forEach(row => {
        const rowId = row.dataset.rowId;
        const month = document.getElementById(`stmt-month-${rowId}`).value;
        const year  = document.getElementById(`stmt-year-${rowId}`).value;
        const key   = `${month}-${year}`;
        if (seen.has(key)) { hasDupe = true; return; }
        seen.add(key);
        periods.push({ month, year });
    });

    if (hasDupe) {
        accShowError('Hay períodos duplicados. Por favor verifica la selección.');
        return;
    }

    // Feedback visual
    const btn = this;
    const txt = document.getElementById('stmt-gen-text');
    const spn = document.getElementById('stmt-gen-spinner');
    btn.disabled = true;
    txt.textContent = 'Abriendo…';
    spn.classList.remove('d-none');

    // Abrir un tab por cada período (con pequeño delay entre cada uno
    // para evitar que el bloqueador de popups los detenga)
    periods.forEach((p, idx) => {
        setTimeout(() => {
            const url = `ajax/generate_statement.php?account_id=${encodeURIComponent(accountId)}`
                      + `&month=${encodeURIComponent(p.month)}`
                      + `&year=${encodeURIComponent(p.year)}`;
            window.open(url, '_blank');

            // Restaurar botón al terminar el último
            if (idx === periods.length - 1) {
                setTimeout(() => {
                    btn.disabled = false;
                    txt.textContent = 'Generar PDF(s)';
                    spn.classList.add('d-none');
                }, 400);
            }
        }, idx * 350);   // 350ms entre tabs para no disparar bloqueador
    });
});

// ─── Init ─────────────────────────────────────
loadAccounts();
</script>