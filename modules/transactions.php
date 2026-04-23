<?php
// modules/transactions.php

$ajax_url          = 'ajax/transactions.php';
$ajax_category_url = 'ajax/create_category.php';
?>

<!-- ============================================ -->
<!-- MÓDULO DE TRANSACCIONES — DISEÑO LIMPIO      -->
<!-- ============================================ -->
<div id="transactions-module">

    <!-- ── Resumen del período ── -->
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;margin-bottom:18px" id="summary-bar">
        <div class="sum-card">
            <div class="sum-label">Ingresos</div>
            <div class="sum-val sum-inc" id="sum-income">RD$ —</div>
        </div>
        <div class="sum-card">
            <div class="sum-label">Gastos</div>
            <div class="sum-val sum-exp" id="sum-expense">RD$ —</div>
        </div>
        <div class="sum-card">
            <div class="sum-label">Balance</div>
            <div class="sum-val sum-bal" id="sum-balance">RD$ —</div>
        </div>
    </div>

    <!-- ── Filtros colapsables ── -->
    <div class="tx-filter-header" id="filterToggleBtn">
        <div style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--bs-secondary-color,#6c757d)">
            <svg width="15" height="15" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M2 4h12M4 8h8M6 12h4"/></svg>
            <span>Filtros</span>
            <span class="badge-active d-none" id="filters-active-badge">activos</span>
        </div>
        <svg class="filter-chevron" id="filterChevron" width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6l4 4 4-4"/></svg>
    </div>

    <div id="filtersPanel" style="display:none;">
        <div class="tx-filter-body">
            <div class="filter-grid">
                <div class="filter-full">
                    <label class="filter-label">Buscar</label>
                    <div style="position:relative">
                        <input type="text" id="filter-search" class="filter-input" placeholder="Descripción, cuenta o categoría…">
                        <button id="clearSearchBtn" style="display:none;position:absolute;right:8px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#999;font-size:14px;padding:2px 4px">✕</button>
                    </div>
                </div>
                <div>
                    <label class="filter-label">Cuenta</label>
                    <select id="filter-account" class="filter-input">
                        <option value="">Todas las cuentas</option>
                    </select>
                </div>
                <div>
                    <label class="filter-label">Categoría</label>
                    <select id="filter-category" class="filter-input">
                        <option value="">Todas las categorías</option>
                        <optgroup label="Ingresos" id="filter-income-group"></optgroup>
                        <optgroup label="Gastos"   id="filter-expense-group"></optgroup>
                    </select>
                </div>
                <div>
                    <label class="filter-label">Tipo</label>
                    <select id="filter-type" class="filter-input">
                        <option value="">Todos</option>
                        <option value="income">Ingresos</option>
                        <option value="expense">Gastos</option>
                        <option value="transfer">Transferencias</option>
                    </select>
                </div>
                <div>
                    <label class="filter-label">Desde</label>
                    <input type="date" id="filter-date-from" class="filter-input">
                </div>
                <div>
                    <label class="filter-label">Hasta</label>
                    <input type="date" id="filter-date-to" class="filter-input">
                </div>
                <div>
                    <label class="filter-label">Monto mín.</label>
                    <input type="number" step="0.01" id="filter-min-amount" class="filter-input" placeholder="0.00">
                </div>
                <div>
                    <label class="filter-label">Monto máx.</label>
                    <input type="number" step="0.01" id="filter-max-amount" class="filter-input" placeholder="999999">
                </div>
            </div>
            <div style="display:flex;gap:8px;margin-top:12px">
                <button type="button" class="btn-apply" onclick="TransactionModule.applyFilters()">Aplicar</button>
                <button type="button" class="btn-clear" onclick="TransactionModule.clearFilters()">Limpiar</button>
            </div>
        </div>
    </div>

    <!-- ── Barra de controles ── -->
    <div class="tx-controls-bar">
        <div style="display:flex;align-items:center;gap:8px">
            <select id="limitSelect" class="filter-input" style="width:auto;padding:5px 10px;font-size:12px">
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
            <span id="records-info" style="font-size:12px;color:#888"></span>
        </div>
        <button class="btn-new-tx" data-bs-toggle="modal" data-bs-target="#transactionModal">
            <span style="font-size:16px;line-height:1">+</span> Nueva
        </button>
    </div>

    <!-- ── Hint móvil ── -->
    <div class="swipe-hint" id="swipeHint" style="display:none">
        Desliza cada fila → para ver detalles
    </div>

    <!-- ── Lista / Tabla ── -->
    <div id="transactions-table-container">
        <div class="tx-loading">
            <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
            <span>Cargando transacciones…</span>
        </div>
    </div>

    <!-- ── Paginación ── -->
    <div id="pagination-container" style="margin-top:14px"></div>

</div>

<!-- ============================================ -->
<!-- MODAL: NUEVA TRANSACCIÓN                     -->
<!-- ============================================ -->
<div class="modal fade" id="transactionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content tx-modal-content">
            <div class="modal-header tx-modal-header">
                <h5 class="modal-title" style="font-size:16px;font-weight:500">Nueva Transacción</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:20px 24px">

                <!-- Tipo -->
                <div class="mb-3">
                    <label class="form-label tx-form-label">Tipo</label>
                    <div class="tx-type-row">
                        <label class="tx-type-card income-card selected" id="labelIncome">
                            <input type="radio" name="type" value="income" class="d-none" id="typeIncome" checked>
                            <span class="tx-type-icon tx-icon-inc">↓</span>
                            <span class="tx-type-text text-success">Ingreso</span>
                        </label>
                        <label class="tx-type-card expense-card" id="labelExpense">
                            <input type="radio" name="type" value="expense" class="d-none" id="typeExpense">
                            <span class="tx-type-icon tx-icon-exp">↑</span>
                            <span class="tx-type-text text-danger">Gasto</span>
                        </label>
                        <label class="tx-type-card transfer-card" id="labelTransfer">
                            <input type="radio" name="type" value="transfer" class="d-none" id="typeTransfer">
                            <span class="tx-type-icon tx-icon-trf">⇄</span>
                            <span class="tx-type-text text-primary">Transferencia</span>
                        </label>
                    </div>
                </div>

                <!-- Cuenta origen -->
                <div class="mb-3">
                    <label class="form-label tx-form-label">Cuenta origen</label>
                    <select id="modal-account-id" class="form-control tx-form-control">
                        <option value="">Seleccionar cuenta</option>
                    </select>
                </div>

                <!-- Cuenta destino -->
                <div class="mb-3 d-none" id="transferToDiv">
                    <label class="form-label tx-form-label">Cuenta destino</label>
                    <select id="modal-transfer-to" class="form-control tx-form-control">
                        <option value="">Seleccionar cuenta destino</option>
                    </select>
                </div>

                <!-- Categoría -->
                <div class="mb-3">
                    <label class="form-label tx-form-label">Categoría</label>
                    <div style="display:flex;gap:8px">
                        <select id="modal-category-id" class="form-control tx-form-control">
                            <option value="">Seleccionar categoría</option>
                            <optgroup label="Ingresos" id="modal-income-group"></optgroup>
                            <optgroup label="Gastos"   id="modal-expense-group"></optgroup>
                        </select>
                        <button type="button" class="btn-add-cat" data-bs-toggle="modal" data-bs-target="#quickCategoryModal" title="Nueva categoría">+</button>
                    </div>
                </div>

                <!-- Monto -->
                <div class="mb-3">
                    <label class="form-label tx-form-label">Monto</label>
                    <input type="number" step="0.01" id="modal-amount" class="form-control tx-form-control" placeholder="0.00">
                </div>

                <!-- Descripción -->
                <div class="mb-1">
                    <label class="form-label tx-form-label">Descripción <span style="font-weight:400;color:#aaa">(opcional)</span></label>
                    <textarea id="modal-description" class="form-control tx-form-control" rows="2"
                              placeholder="Ej: Compra en supermercado…"></textarea>
                </div>

                <input type="hidden" id="modal-date">
            </div>
            <div class="modal-footer" style="padding:14px 24px;border-top:1px solid #f0f0f0">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn-save-tx" onclick="TransactionModule.addTransaction()">Guardar</button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- MODAL: CREAR CATEGORÍA RÁPIDA               -->
<!-- ============================================ -->
<div class="modal fade" id="quickCategoryModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content tx-modal-content">
            <div class="modal-header tx-modal-header">
                <h5 class="modal-title" style="font-size:15px;font-weight:500">Nueva Categoría</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:20px 24px">
                <div class="mb-3">
                    <label class="form-label tx-form-label">Nombre</label>
                    <input type="text" id="quickCategoryName" class="form-control tx-form-control" placeholder="Ej: Transporte…">
                </div>
                <div class="mb-1">
                    <label class="form-label tx-form-label">Tipo</label>
                    <select id="quickCategoryType" class="form-control tx-form-control">
                        <option value="income">Ingreso</option>
                        <option value="expense">Gasto</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer" style="padding:14px 24px;border-top:1px solid #f0f0f0">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn-save-tx" onclick="TransactionModule.createQuickCategory()">Crear</button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- ESTILOS                                      -->
<!-- ============================================ -->
<style>
/* ── Variables locales ── */
#transactions-module {
    --tx-radius: 10px;
    --tx-border: #e8e8e8;
    --tx-bg-page: #f6f7f9;
    --tx-inc: #1D9E75;
    --tx-exp: #D85A30;
    --tx-trf: #3b82f6;
    --tx-inc-bg: #eaf3de;
    --tx-exp-bg: #faece7;
    --tx-trf-bg: #eff6ff;
    --tx-inc-text: #3B6D11;
    --tx-exp-text: #993C1D;
    --tx-trf-text: #1e40af;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
}

/* ── Resumen ── */
.sum-card {
    background: #fff;
    border: 1px solid var(--tx-border);
    border-radius: var(--tx-radius);
    padding: 12px 14px;
}
.sum-label {
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: #9ca3af;
    margin-bottom: 4px;
}
.sum-val { font-size: 15px; font-weight: 600; }
.sum-inc  { color: var(--tx-inc); }
.sum-exp  { color: var(--tx-exp); }
.sum-bal  { color: #374151; }

/* ── Filtros ── */
.tx-filter-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #fff;
    border: 1px solid var(--tx-border);
    border-radius: var(--tx-radius);
    padding: 10px 14px;
    margin-bottom: 6px;
    cursor: pointer;
    user-select: none;
    transition: background .15s;
}
.tx-filter-header:hover { background: #fafafa; }
.filter-chevron { transition: transform .2s; color: #9ca3af; }
.filter-chevron.open { transform: rotate(180deg); }
.badge-active {
    background: #eff6ff;
    color: #1e40af;
    border: 1px solid #bfdbfe;
    font-size: 10px;
    padding: 1px 7px;
    border-radius: 20px;
}
.tx-filter-body {
    background: #fff;
    border: 1px solid var(--tx-border);
    border-radius: var(--tx-radius);
    padding: 16px;
    margin-bottom: 10px;
}
.filter-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}
.filter-full { grid-column: 1 / -1; }
.filter-label {
    display: block;
    font-size: 11px;
    color: #9ca3af;
    margin-bottom: 4px;
    text-transform: uppercase;
    letter-spacing: .04em;
}
.filter-input {
    width: 100%;
    font-size: 13px;
    padding: 7px 10px;
    border: 1px solid var(--tx-border);
    border-radius: 8px;
    background: #fafafa;
    color: #374151;
    outline: none;
    transition: border-color .15s;
}
.filter-input:focus { border-color: #a5b4fc; background: #fff; }
.btn-apply {
    flex: 1;
    padding: 8px 0;
    background: #374151;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 13px;
    cursor: pointer;
    transition: background .15s;
}
.btn-apply:hover { background: #1f2937; }
.btn-clear {
    padding: 8px 14px;
    background: #f3f4f6;
    color: #6b7280;
    border: 1px solid var(--tx-border);
    border-radius: 8px;
    font-size: 13px;
    cursor: pointer;
    transition: background .15s;
}
.btn-clear:hover { background: #e5e7eb; }

/* ── Barra de controles ── */
.tx-controls-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 10px;
}
.btn-new-tx {
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 7px 16px;
    background: #374151;
    color: #fff;
    border: none;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: background .15s;
}
.btn-new-tx:hover { background: #1f2937; }

/* ── Hint de deslizamiento ── */
.swipe-hint {
    text-align: center;
    font-size: 11px;
    color: #b0b7c3;
    margin-bottom: 8px;
    letter-spacing: .02em;
}

/* ── Loading ── */
.tx-loading {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 40px 0;
    color: #9ca3af;
    font-size: 14px;
}
.tx-empty {
    text-align: center;
    padding: 50px 20px;
    color: #9ca3af;
    font-size: 14px;
}
.tx-empty-icon {
    font-size: 32px;
    margin-bottom: 8px;
    opacity: .4;
}

/* ── Lista de transacciones (MÓVIL) ── */
.tx-list {
    display: flex;
    flex-direction: column;
    gap: 2px;
}
.tx-item {
    border-radius: var(--tx-radius);
    background: #fff;
    border: 1px solid var(--tx-border);
    overflow: hidden;
}
.tx-scroll {
    display: flex;
    overflow-x: auto;
    scroll-snap-type: x mandatory;
    scrollbar-width: none;
    -ms-overflow-style: none;
}
.tx-scroll::-webkit-scrollbar { display: none; }
.tx-panel {
    flex: 0 0 100%;
    scroll-snap-align: start;
    padding: 12px 14px;
    display: flex;
    align-items: center;
    gap: 12px;
    min-height: 64px;
}
.tx-panel-extra {
    flex: 0 0 100%;
    scroll-snap-align: start;
    padding: 12px 14px;
    display: flex;
    align-items: center;
    gap: 10px;
    background: #f8f9fb;
    border-left: 1px solid var(--tx-border);
}
.tx-dot {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 16px;
    font-weight: 600;
}
.dot-inc { background: var(--tx-inc-bg); color: var(--tx-inc-text); }
.dot-exp { background: var(--tx-exp-bg); color: var(--tx-exp-text); }
.dot-trf { background: var(--tx-trf-bg); color: var(--tx-trf-text); }
.tx-main { flex: 1; min-width: 0; }
.tx-cat {
    font-size: 13px;
    font-weight: 500;
    color: #1f2937;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.tx-date { font-size: 11px; color: #9ca3af; margin-top: 2px; }
.tx-amount { text-align: right; flex-shrink: 0; }
.tx-amt-main { font-size: 15px; font-weight: 600; }
.amt-inc { color: var(--tx-inc); }
.amt-exp { color: var(--tx-exp); }
.amt-trf { color: var(--tx-trf); }
.tx-amt-sub { font-size: 10px; color: #9ca3af; margin-top: 2px; }
.tx-extra-col { flex: 1; min-width: 0; }
.ex-label {
    font-size: 9px;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: .05em;
    margin-bottom: 2px;
}
.ex-val {
    font-size: 12px;
    color: #374151;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.ex-divider { width: 1px; height: 30px; background: var(--tx-border); flex-shrink: 0; }
.btn-del-row {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    border: 1px solid #fecaca;
    background: #fef2f2;
    color: #dc2626;
    font-size: 13px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    flex-shrink: 0;
    transition: background .15s;
}
.btn-del-row:hover { background: #fee2e2; }

/* ── Dots de posición ── */
.tx-dots {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    padding: 4px 0 3px;
}
.tx-dot-ind {
    width: 5px;
    height: 5px;
    border-radius: 50%;
    background: #d1d5db;
    transition: background .2s;
}
.tx-dot-ind.active { background: #6b7280; }

/* ── Tabla DESKTOP ── */
.tx-table-wrap { overflow-x: auto; }
.tx-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}
.tx-table thead tr {
    border-bottom: 2px solid #f3f4f6;
}
.tx-table thead th {
    padding: 10px 14px;
    text-align: left;
    font-size: 11px;
    font-weight: 600;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: .05em;
    white-space: nowrap;
}
.tx-table tbody tr {
    border-bottom: 1px solid #f9fafb;
    transition: background .12s;
}
.tx-table tbody tr:last-child { border-bottom: none; }
.tx-table tbody tr:hover { background: #fafafa; }
.tx-table td {
    padding: 11px 14px;
    color: #374151;
    vertical-align: middle;
}
.tx-table .tx-id { color: #9ca3af; font-size: 11px; }
.tx-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 8px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 500;
}
.badge-inc { background: var(--tx-inc-bg); color: var(--tx-inc-text); }
.badge-exp { background: var(--tx-exp-bg); color: var(--tx-exp-text); }
.badge-trf { background: var(--tx-trf-bg); color: var(--tx-trf-text); }
.tx-table .amt-col { font-weight: 600; white-space: nowrap; }
.tx-table .tx-desc { color: #6b7280; max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.tx-table .btn-del-table {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    border: 1px solid #fecaca;
    background: #fef2f2;
    color: #dc2626;
    font-size: 12px;
    cursor: pointer;
    transition: background .15s;
}
.tx-table .btn-del-table:hover { background: #fee2e2; }

/* ── Paginación ── */
.tx-pagination {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    flex-wrap: wrap;
}
.pag-btn {
    width: 34px;
    height: 34px;
    border-radius: 8px;
    border: 1px solid var(--tx-border);
    background: #fff;
    color: #6b7280;
    font-size: 13px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all .12s;
    text-decoration: none;
}
.pag-btn:hover { background: #f3f4f6; color: #1f2937; }
.pag-btn.active { background: #374151; color: #fff; border-color: #374151; }
.pag-btn.disabled { opacity: .4; pointer-events: none; }

/* ── Modal ── */
.tx-modal-content {
    border: none;
    border-radius: 14px;
    box-shadow: 0 20px 60px rgba(0,0,0,.12);
}
.tx-modal-header {
    padding: 18px 24px 12px;
    border-bottom: 1px solid #f3f4f6;
}
.tx-form-label {
    font-size: 12px !important;
    font-weight: 500 !important;
    color: #6b7280 !important;
    text-transform: uppercase;
    letter-spacing: .04em;
    margin-bottom: 5px !important;
}
.tx-form-control {
    border: 1px solid #e5e7eb !important;
    border-radius: 8px !important;
    font-size: 14px !important;
    padding: 8px 12px !important;
    background: #fafafa !important;
    transition: border-color .15s, background .15s;
}
.tx-form-control:focus {
    border-color: #a5b4fc !important;
    background: #fff !important;
    box-shadow: 0 0 0 3px rgba(165,180,252,.15) !important;
}
.btn-add-cat {
    width: 42px;
    height: 42px;
    flex-shrink: 0;
    border: 1px dashed #d1d5db;
    border-radius: 8px;
    background: #f9fafb;
    color: #6b7280;
    font-size: 18px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all .15s;
}
.btn-add-cat:hover { border-color: #9ca3af; color: #374151; }
.btn-save-tx {
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
.btn-save-tx:hover { background: #1f2937; }

/* ── Selector de tipo ── */
.tx-type-row {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 8px;
}
.tx-type-card {
    cursor: pointer;
    border-radius: 10px;
    border: 1.5px solid #e5e7eb;
    background: #f9fafb;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 10px 6px;
    gap: 5px;
    transition: border-color .2s, background .2s;
    user-select: none;
}
.tx-type-card:hover { background: #f3f4f6; }
.tx-type-icon {
    font-size: 18px;
    font-weight: 700;
    line-height: 1;
}
.tx-icon-inc { color: var(--tx-inc); }
.tx-icon-exp { color: var(--tx-exp); }
.tx-icon-trf { color: var(--tx-trf); }
.tx-type-text { font-size: 12px; font-weight: 500; }
.income-card.selected   { border-color: var(--tx-inc); background: var(--tx-inc-bg); }
.expense-card.selected  { border-color: var(--tx-exp); background: var(--tx-exp-bg); }
.transfer-card.selected { border-color: var(--tx-trf); background: var(--tx-trf-bg); }

/* ── Responsive: ocultar lista / tabla según pantalla ── */
.tx-mobile-list  { display: flex; flex-direction: column; gap: 2px; }
.tx-desktop-wrap { display: none; }

@media (min-width: 768px) {
    .tx-mobile-list  { display: none !important; }
    .tx-desktop-wrap { display: block !important; }
    .swipe-hint      { display: none !important; }
    #summary-bar     { grid-template-columns: 1fr 1fr 1fr; }
    .sum-val         { font-size: 18px; }
}
</style>

<!-- ============================================ -->
<!-- JAVASCRIPT                                   -->
<!-- ============================================ -->
<script>
const TransactionModule = (() => {

    const AJAX_URL     = '<?= $ajax_url ?>';
    const CAT_AJAX_URL = '<?= $ajax_category_url ?>';

    let _state = {
        page: 1, limit: 10,
        filter_account: '', filter_category: '', filter_type: '',
        filter_min_amount: '', filter_max_amount: '',
        filter_date_from: '', filter_date_to: '', filter_search: '',
    };

    let _accounts    = [];
    let _incomeCats  = [];
    let _expenseCats = [];

    // ─── Utilidades ───────────────────────────────

    function showError(msg, full) {
        console.error('[TransactionModule]', full ?? msg);
        Swal.fire({ title: 'Error', text: msg, icon: 'error',
            toast: true, position: 'top-start',
            showConfirmButton: false, timer: 5000, timerProgressBar: true });
    }

    function showSuccess(msg) {
        Swal.fire({ title: '¡Listo!', text: msg, icon: 'success',
            toast: true, position: 'top-start',
            showConfirmButton: false, timer: 3500, timerProgressBar: true });
    }

    async function request(action, data = {}, method = 'POST') {
        const isGet = method === 'GET';
        const params = new URLSearchParams({ action, ...data });
        const url = isGet ? `${AJAX_URL}?${params}` : AJAX_URL;
        const res = await fetch(url, isGet ? {} : {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params,
        });
        const raw = await res.text();
        let json;
        try { json = JSON.parse(raw); }
        catch (e) {
            console.error('[TransactionModule] Respuesta no-JSON:\n', raw);
            throw Object.assign(new Error('Respuesta inválida del servidor. Revisa la consola.'), { fullMessage: raw });
        }
        if (!json.success) {
            console.error('[TransactionModule ERROR]', json.full_message ?? json.message);
            throw Object.assign(new Error(json.message ?? 'Error desconocido.'), { fullMessage: json.full_message ?? json.message });
        }
        return json;
    }

    function fmt(n) {
        return parseFloat(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    function esc(str) {
        return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    // ─── Resumen ──────────────────────────────────

    function updateSummary(transactions) {
        let inc = 0, exp = 0;
        transactions.forEach(t => {
            if (t.type === 'income')  inc += parseFloat(t.converted_amount_dop ?? 0);
            if (t.type === 'expense') exp += parseFloat(t.converted_amount_dop ?? 0);
        });
        const bal = inc - exp;
        document.getElementById('sum-income').textContent  = 'RD$ ' + fmt(inc);
        document.getElementById('sum-expense').textContent = 'RD$ ' + fmt(exp);
        const balEl = document.getElementById('sum-balance');
        balEl.textContent = 'RD$ ' + fmt(bal);
        balEl.className = 'sum-val ' + (bal >= 0 ? 'sum-inc' : 'sum-exp');
    }

    // ─── Render MÓVIL (tarjetas deslizables) ──────

    function renderMobile(transactions) {
        if (!transactions.length) return '<div class="tx-empty"><div class="tx-empty-icon">📭</div>Sin transacciones</div>';

        return '<div class="tx-mobile-list">' + transactions.map(t => {
            const dotCls = t.type === 'income' ? 'dot-inc' : t.type === 'expense' ? 'dot-exp' : 'dot-trf';
            const amtCls = t.type === 'income' ? 'amt-inc' : t.type === 'expense' ? 'amt-exp' : 'amt-trf';
            const icon   = t.type === 'income' ? '↓' : t.type === 'expense' ? '↑' : '⇄';
            const dateFmt = new Date(t.date + 'T00:00:00').toLocaleDateString('es-DO', { day:'2-digit', month:'2-digit', year:'numeric' });

            return `
            <div class="tx-item">
                <div class="tx-scroll" id="sc${t.id}">
                    <div class="tx-panel">
                        <div class="tx-dot ${dotCls}">${icon}</div>
                        <div class="tx-main">
                            <div class="tx-cat">${esc(t.category_name ?? 'Sin categoría')}</div>
                            <div class="tx-date">${dateFmt}</div>
                        </div>
                        <div class="tx-amount">
                            <div class="tx-amt-main ${amtCls}">${esc(t.symbol ?? '')} ${fmt(t.original_amount)}</div>
                            <div class="tx-amt-sub">RD$ ${fmt(t.converted_amount_dop)}</div>
                        </div>
                    </div>
                    <div class="tx-panel-extra">
                        <div class="tx-extra-col">
                            <div class="ex-label">Cuenta</div>
                            <div class="ex-val">${esc(t.account_name ?? '-')}</div>
                        </div>
                        <div class="ex-divider"></div>
                        <div class="tx-extra-col">
                            <div class="ex-label">Descripción</div>
                            <div class="ex-val">${esc(t.description || '—')}</div>
                        </div>
                        <div class="ex-divider"></div>
                        <button class="btn-del-row" onclick="TransactionModule.confirmDelete(${t.id})" title="Eliminar">✕</button>
                    </div>
                </div>
                <div class="tx-dots">
                    <div class="tx-dot-ind active" id="md0-${t.id}"></div>
                    <div class="tx-dot-ind" id="md1-${t.id}"></div>
                </div>
            </div>`;
        }).join('') + '</div>';
    }

    // ─── Render DESKTOP (tabla limpia) ────────────

    function renderDesktop(transactions) {
        if (!transactions.length) return '';
        const rows = transactions.map(t => {
            const badgeCls = t.type === 'income' ? 'badge-inc' : t.type === 'expense' ? 'badge-exp' : 'badge-trf';
            const label    = t.type === 'income' ? 'Ingreso' : t.type === 'expense' ? 'Gasto' : 'Transferencia';
            const amtCls   = t.type === 'income' ? 'amt-inc' : t.type === 'expense' ? 'amt-exp' : 'amt-trf';
            const dateFmt  = new Date(t.date + 'T00:00:00').toLocaleDateString('es-DO', { day:'2-digit', month:'2-digit', year:'numeric' });
            return `
            <tr id="tr-${t.id}">
                <td class="tx-id">#${t.id}</td>
                <td>${dateFmt}</td>
                <td>${esc(t.account_name ?? '-')}</td>
                <td>${esc(t.category_name ?? '—')}</td>
                <td><span class="tx-badge ${badgeCls}">${label}</span></td>
                <td class="amt-col ${amtCls}">${esc(t.symbol ?? '')} ${fmt(t.original_amount)} <span style="font-size:10px;font-weight:400;color:#9ca3af">${esc(t.original_currency ?? '')}</span></td>
                <td style="color:#9ca3af">RD$ ${fmt(t.converted_amount_dop)}</td>
                <td class="tx-desc">${esc(t.description || '—')}</td>
                <td><button class="btn-del-table" onclick="TransactionModule.confirmDelete(${t.id})" title="Eliminar">✕</button></td>
            </tr>`;
        }).join('');

        return `<div class="tx-desktop-wrap">
            <div class="tx-table-wrap">
                <table class="tx-table">
                    <thead>
                        <tr>
                            <th>ID</th><th>Fecha</th><th>Cuenta</th><th>Categoría</th>
                            <th>Tipo</th><th>Monto</th><th>DOP</th><th>Descripción</th><th></th>
                        </tr>
                    </thead>
                    <tbody>${rows}</tbody>
                </table>
            </div>
        </div>`;
    }

    // ─── Renderizar contenedor ────────────────────

    function renderTable(transactions) {
        const container = document.getElementById('transactions-table-container');

        if (!transactions.length) {
            container.innerHTML = '<div class="tx-empty"><div class="tx-empty-icon">📭</div>No hay transacciones que coincidan</div>';
            return;
        }

        container.innerHTML = renderMobile(transactions) + renderDesktop(transactions);

        // Activar scroll + dots en móvil
        transactions.forEach(t => {
            const sc = document.getElementById('sc' + t.id);
            if (!sc) return;
            sc.addEventListener('scroll', () => {
                const at = sc.scrollLeft > sc.scrollWidth * 0.3;
                document.getElementById('md0-' + t.id)?.classList.toggle('active', !at);
                document.getElementById('md1-' + t.id)?.classList.toggle('active', at);
            });
        });

        // Mostrar hint móvil solo si hay datos
        const hint = document.getElementById('swipeHint');
        if (hint) hint.style.display = window.innerWidth < 768 ? 'block' : 'none';
    }

    // ─── Paginación ───────────────────────────────

    function renderPagination(totalPages, currentPage) {
        const container = document.getElementById('pagination-container');
        if (totalPages <= 1) { container.innerHTML = ''; return; }

        const prev = currentPage - 1;
        const next = currentPage + 1;
        let items = '';

        items += `<a class="pag-btn ${currentPage <= 1 ? 'disabled' : ''}" href="#" onclick="TransactionModule.goToPage(1);return false;">«</a>`;
        items += `<a class="pag-btn ${currentPage <= 1 ? 'disabled' : ''}" href="#" onclick="TransactionModule.goToPage(${prev});return false;">‹</a>`;

        const start = Math.max(1, currentPage - 2);
        const end   = Math.min(totalPages, currentPage + 2);
        if (start > 1) items += `<span class="pag-btn disabled">…</span>`;
        for (let i = start; i <= end; i++) {
            items += `<a class="pag-btn ${i === currentPage ? 'active' : ''}" href="#" onclick="TransactionModule.goToPage(${i});return false;">${i}</a>`;
        }
        if (end < totalPages) items += `<span class="pag-btn disabled">…</span>`;

        items += `<a class="pag-btn ${currentPage >= totalPages ? 'disabled' : ''}" href="#" onclick="TransactionModule.goToPage(${next});return false;">›</a>`;
        items += `<a class="pag-btn ${currentPage >= totalPages ? 'disabled' : ''}" href="#" onclick="TransactionModule.goToPage(${totalPages});return false;">»</a>`;

        container.innerHTML = `<div class="tx-pagination">${items}</div>`;
    }

    // ─── Cargar transacciones ─────────────────────

    async function loadTransactions() {
        document.getElementById('transactions-table-container').innerHTML =
            '<div class="tx-loading"><div class="spinner-border spinner-border-sm text-secondary" role="status"></div><span>Cargando…</span></div>';

        try {
            const data = await request('get_transactions', _state, 'GET');

            renderTable(data.transactions);
            renderPagination(data.total_pages, data.page);
            updateSummary(data.transactions);

            const from = (_state.page - 1) * _state.limit + 1;
            const to   = Math.min(_state.page * _state.limit, data.total_records);
            document.getElementById('records-info').textContent =
                data.total_records > 0 ? `${from}–${to} de ${data.total_records}` : 'Sin resultados';

            const hasFilters = Object.entries(_state)
                .filter(([k]) => k.startsWith('filter_'))
                .some(([, v]) => v !== '');
            document.getElementById('filters-active-badge').classList.toggle('d-none', !hasFilters);

        } catch (err) {
            showError(err.message, err.fullMessage);
        }
    }

    // ─── Cargar datos de formulario ───────────────

    async function loadFormData() {
        try {
            const data = await request('get_form_data', {}, 'GET');
            _accounts    = data.accounts;
            _incomeCats  = data.income_categories;
            _expenseCats = data.expense_categories;

            const accountOpts = _accounts.map(a =>
                `<option value="${a.id}" data-currency="${a.currency_code}" data-symbol="${a.symbol}">
                    ${esc(a.name)} (${a.currency_code}) — ${a.symbol} ${fmt(a.balance)}
                </option>`).join('');

            document.getElementById('modal-account-id').innerHTML  = '<option value="">Seleccionar cuenta</option>' + accountOpts;
            document.getElementById('modal-transfer-to').innerHTML = '<option value="">Seleccionar cuenta destino</option>' + accountOpts;
            document.getElementById('filter-account').innerHTML    =
                '<option value="">Todas las cuentas</option>' +
                _accounts.map(a => `<option value="${a.id}">${esc(a.name)} (${a.currency_code})</option>`).join('');

            document.getElementById('modal-income-group').innerHTML  = _incomeCats.map(c => `<option value="${c.id}">${esc(c.name)}</option>`).join('');
            document.getElementById('modal-expense-group').innerHTML = _expenseCats.map(c => `<option value="${c.id}">${esc(c.name)}</option>`).join('');
            document.getElementById('filter-income-group').innerHTML = _incomeCats.map(c => `<option value="${c.id}">${esc(c.name)}</option>`).join('');
            document.getElementById('filter-expense-group').innerHTML= _expenseCats.map(c => `<option value="${c.id}">${esc(c.name)}</option>`).join('');

        } catch (err) {
            showError(err.message, err.fullMessage);
        }
    }

    // ─── Filtros ──────────────────────────────────

    function applyFilters() {
        _state.page              = 1;
        _state.filter_account    = document.getElementById('filter-account').value;
        _state.filter_category   = document.getElementById('filter-category').value;
        _state.filter_type       = document.getElementById('filter-type').value;
        _state.filter_min_amount = document.getElementById('filter-min-amount').value;
        _state.filter_max_amount = document.getElementById('filter-max-amount').value;
        _state.filter_date_from  = document.getElementById('filter-date-from').value;
        _state.filter_date_to    = document.getElementById('filter-date-to').value;
        _state.filter_search     = document.getElementById('filter-search').value.trim();
        loadTransactions();
    }

    function clearFilters() {
        _state = { ..._state, page: 1,
            filter_account:'', filter_category:'', filter_type:'',
            filter_min_amount:'', filter_max_amount:'',
            filter_date_from:'', filter_date_to:'', filter_search:'' };
        ['filter-account','filter-category','filter-type',
         'filter-min-amount','filter-max-amount',
         'filter-date-from','filter-date-to','filter-search']
            .forEach(id => { document.getElementById(id).value = ''; });
        document.getElementById('clearSearchBtn').style.display = 'none';
        loadTransactions();
    }

    function goToPage(p) { _state.page = p; loadTransactions(); }

    // ─── Agregar transacción ──────────────────────

    async function addTransaction() {
        const type        = document.querySelector('input[name="type"]:checked')?.value;
        const account_id  = document.getElementById('modal-account-id').value;
        const transfer_to = document.getElementById('modal-transfer-to').value;
        const category_id = document.getElementById('modal-category-id').value;
        const amount      = document.getElementById('modal-amount').value;
        const date        = document.getElementById('modal-date').value;
        const description = document.getElementById('modal-description').value;

        if (!account_id) { showError('Selecciona una cuenta de origen.'); return; }
        if (!amount || parseFloat(amount) <= 0) { showError('El monto debe ser mayor a 0.'); return; }

        try {
            const data = await request('add_transaction', {
                account_id, category_id, type, amount, date, description,
                transfer_to, payment_currency: '',
            });
            showSuccess(data.message);
            bootstrap.Modal.getInstance(document.getElementById('transactionModal'))?.hide();
            document.getElementById('modal-amount').value      = '';
            document.getElementById('modal-description').value = '';
            await loadTransactions();
        } catch (err) {
            showError(err.message, err.fullMessage);
        }
    }

    // ─── Eliminar transacción ─────────────────────

    function confirmDelete(id) {
        Swal.fire({
            title: '¿Eliminar transacción?',
            text: 'Esta acción no se puede deshacer.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
        }).then(async result => {
            if (!result.isConfirmed) return;
            try {
                const data = await request('delete_transaction', { transaction_id: id });
                showSuccess(data.message);
                document.getElementById(`tr-${id}`)?.remove();
                const mobileItem = document.getElementById(`sc${id}`)?.closest('.tx-item');
                if (mobileItem) mobileItem.remove();
                const tbody = document.querySelector('.tx-table tbody');
                if (tbody && tbody.children.length === 0) await loadTransactions();
            } catch (err) {
                showError(err.message, err.fullMessage);
            }
        });
    }

    // ─── Crear categoría rápida ───────────────────

    async function createQuickCategory() {
        const name = document.getElementById('quickCategoryName').value.trim();
        const type = document.getElementById('quickCategoryType').value;
        if (!name) { showError('El nombre de la categoría es requerido.'); return; }

        Swal.fire({ title: 'Creando…', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

        try {
            const res  = await fetch(CAT_AJAX_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ name, type }),
            });
            const json = JSON.parse(await res.text());
            if (!json.success) throw Object.assign(new Error(json.message), { fullMessage: json.full_message ?? json.message });

            Swal.close();
            showSuccess('Categoría creada.');
            bootstrap.Modal.getInstance(document.getElementById('quickCategoryModal'))?.hide();
            document.getElementById('quickCategoryName').value = '';

            const opt = `<option value="${json.category_id}">${esc(name)}</option>`;
            if (type === 'income') {
                document.getElementById('modal-income-group').insertAdjacentHTML('beforeend', opt);
                document.getElementById('filter-income-group').insertAdjacentHTML('beforeend', opt);
                _incomeCats.push({ id: json.category_id, name });
                if (document.querySelector('input[name="type"]:checked')?.value === 'income')
                    document.getElementById('modal-category-id').value = json.category_id;
            } else {
                document.getElementById('modal-expense-group').insertAdjacentHTML('beforeend', opt);
                document.getElementById('filter-expense-group').insertAdjacentHTML('beforeend', opt);
                _expenseCats.push({ id: json.category_id, name });
                if (document.querySelector('input[name="type"]:checked')?.value === 'expense')
                    document.getElementById('modal-category-id').value = json.category_id;
            }
        } catch (err) {
            Swal.close();
            showError(err.message, err.fullMessage);
        }
    }

    // ─── Filtrar categorías según tipo ───────────

    function filterModalCategories(type) {
        const incG = document.getElementById('modal-income-group');
        const expG = document.getElementById('modal-expense-group');
        if (type === 'income') {
            incG.style.display = ''; expG.style.display = 'none';
            const first = incG.querySelector('option');
            if (first) document.getElementById('modal-category-id').value = first.value;
        } else if (type === 'expense') {
            incG.style.display = 'none'; expG.style.display = '';
            const first = expG.querySelector('option');
            if (first) document.getElementById('modal-category-id').value = first.value;
        } else {
            incG.style.display = 'none'; expG.style.display = 'none';
            document.getElementById('modal-category-id').value = '';
        }
    }

    // ─── Event listeners ─────────────────────────

    // Toggle filtros
    document.getElementById('filterToggleBtn').addEventListener('click', () => {
        const panel   = document.getElementById('filtersPanel');
        const chevron = document.getElementById('filterChevron');
        const open    = panel.style.display !== 'none';
        panel.style.display = open ? 'none' : 'block';
        chevron.classList.toggle('open', !open);
    });

    // Selector de tipo en modal
    document.querySelectorAll('.tx-type-card').forEach(card => {
        card.addEventListener('click', () => {
            document.querySelectorAll('.tx-type-card').forEach(c => c.classList.remove('selected'));
            card.classList.add('selected');
            card.querySelector('input[type="radio"]').checked = true;
            const type = card.querySelector('input[type="radio"]').value;
            document.getElementById('transferToDiv').classList.toggle('d-none', type !== 'transfer');
            filterModalCategories(type);
        });
    });

    // Reset modal al abrir
    document.getElementById('transactionModal').addEventListener('shown.bs.modal', () => {
        document.querySelectorAll('.tx-type-card').forEach(c => c.classList.remove('selected'));
        document.getElementById('labelIncome').classList.add('selected');
        document.getElementById('typeIncome').checked = true;
        document.getElementById('transferToDiv').classList.add('d-none');
        filterModalCategories('income');
        document.getElementById('modal-date').value = new Date().toISOString().split('T')[0];
    });

    // Búsqueda: mostrar/ocultar botón limpiar
    document.getElementById('filter-search').addEventListener('input', function () {
        document.getElementById('clearSearchBtn').style.display = this.value ? '' : 'none';
    });
    document.getElementById('clearSearchBtn').addEventListener('click', () => {
        document.getElementById('filter-search').value = '';
        document.getElementById('clearSearchBtn').style.display = 'none';
    });

    // Limit selector
    document.getElementById('limitSelect').addEventListener('change', function () {
        _state.limit = parseInt(this.value);
        _state.page  = 1;
        loadTransactions();
    });

    // Redimensionar: ocultar/mostrar hint móvil
    window.addEventListener('resize', () => {
        const hint = document.getElementById('swipeHint');
        if (hint) hint.style.display = window.innerWidth < 768 ? 'block' : 'none';
    });

    // ─── Init ─────────────────────────────────────

    document.getElementById('modal-date').value = new Date().toISOString().split('T')[0];
    Promise.all([loadFormData(), loadTransactions()]);

    return { applyFilters, clearFilters, goToPage, addTransaction, confirmDelete, createQuickCategory };

})();

console.log('[TransactionModule] Módulo cargado.');
</script>