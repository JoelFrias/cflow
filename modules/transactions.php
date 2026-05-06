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
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;margin-bottom:18px;display:none" id="summary-bar">
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
                    <label class="filter-label">Tipo</label>
                    <select id="filter-type" class="filter-input">
                        <option value="">Todos</option>
                        <option value="income">Ingresos</option>
                        <option value="expense">Gastos</option>
                        <option value="transfer">Transferencias</option>
                    </select>
                </div>
                <div>
                    <label class="filter-label">Categoría</label>
                    <select id="filter-category" class="filter-input">
                        <option value="">Todas las categorías</option>
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
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
            Nueva
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
                        <label class="tx-type-card income-card" id="labelIncome">
                            <input type="radio" name="type" value="income" class="d-none" id="typeIncome">
                            <span class="tx-type-icon tx-icon-inc">
                                <!-- Flecha abajo (ingreso) -->
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="9"/>
                                    <path d="M12 8v8M8.5 14.5l3.5 3.5 3.5-3.5"/>
                                </svg>
                            </span>
                            <span class="tx-type-text text-success">Ingreso</span>
                        </label>
                        <label class="tx-type-card expense-card selected" id="labelExpense">
                            <input type="radio" name="type" value="expense" class="d-none" id="typeExpense" checked>
                            <span class="tx-type-icon tx-icon-exp">
                                <!-- Flecha arriba (gasto) -->
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="9"/>
                                    <path d="M12 16V8M8.5 11.5l3.5-3.5 3.5 3.5"/>
                                </svg>
                            </span>
                            <span class="tx-type-text text-danger">Gasto</span>
                        </label>
                        <label class="tx-type-card transfer-card" id="labelTransfer">
                            <input type="radio" name="type" value="transfer" class="d-none" id="typeTransfer">
                            <span class="tx-type-icon tx-icon-trf">
                                <!-- Doble flecha (transferencia) -->
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M5 8h14M15 4l4 4-4 4"/>
                                    <path d="M19 16H5M9 12l-4 4 4 4"/>
                                </svg>
                            </span>
                            <span class="tx-type-text text-primary">Transferencia</span>
                        </label>
                    </div>
                </div>

                <!-- ── Cuenta origen (cards) ── -->
                <div class="mb-3">
                    <label class="form-label tx-form-label" id="labelAccountOrigin">Cuenta</label>
                    <div class="account-cards-grid" id="account-cards-origin">
                        <div style="color:#9ca3af;font-size:13px;padding:10px 0">Cargando cuentas…</div>
                    </div>
                    <input type="hidden" id="modal-account-id">
                </div>

                <!-- ── Cuenta destino (cards — solo transferencia) ── -->
                <div class="mb-3 d-none" id="transferToDiv">
                    <label class="form-label tx-form-label">Cuenta destino</label>
                    <div class="account-cards-grid" id="account-cards-dest"></div>
                    <input type="hidden" id="modal-transfer-to">
                </div>

                <!-- ── Categoría (oculta en transferencias) ── -->
                <div class="mb-3" id="categoryDiv">
                    <label class="form-label tx-form-label">Categoría</label>
                    <div style="display:flex;gap:8px">
                        <select id="modal-category-id" class="form-control tx-form-control">
                            <option value="">Seleccionar categoría</option>
                        </select>
                        <button type="button" class="btn-add-cat" data-bs-toggle="modal" data-bs-target="#quickCategoryModal" title="Nueva categoría">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                        </button>
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
<!-- MODAL: EDITAR TRANSACCIÓN                    -->
<!-- ============================================ -->
<div class="modal fade" id="editTransactionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content tx-modal-content">
            <div class="modal-header tx-modal-header">
                <h5 class="modal-title" style="font-size:16px;font-weight:500">
                    Editar Transacción
                    <span id="edit-tx-id-badge" style="font-size:12px;color:#9ca3af;font-weight:400;margin-left:6px"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:20px 24px">
                <input type="hidden" id="edit-transaction-id">

                <!-- Tipo -->
                <div class="mb-3">
                    <label class="form-label tx-form-label">Tipo</label>
                    <div class="tx-type-row" id="edit-type-row">
                        <label class="tx-type-card income-card" id="edit-labelIncome">
                            <input type="radio" name="edit-type" value="income" class="d-none" id="edit-typeIncome">
                            <span class="tx-type-icon tx-icon-inc">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="9"/>
                                    <path d="M12 8v8M8.5 14.5l3.5 3.5 3.5-3.5"/>
                                </svg>
                            </span>
                            <span class="tx-type-text text-success">Ingreso</span>
                        </label>
                        <label class="tx-type-card expense-card" id="edit-labelExpense">
                            <input type="radio" name="edit-type" value="expense" class="d-none" id="edit-typeExpense">
                            <span class="tx-type-icon tx-icon-exp">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="9"/>
                                    <path d="M12 16V8M8.5 11.5l3.5-3.5 3.5 3.5"/>
                                </svg>
                            </span>
                            <span class="tx-type-text text-danger">Gasto</span>
                        </label>
                        <label class="tx-type-card transfer-card" id="edit-labelTransfer">
                            <input type="radio" name="edit-type" value="transfer" class="d-none" id="edit-typeTransfer">
                            <span class="tx-type-icon tx-icon-trf">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M5 8h14M15 4l4 4-4 4"/>
                                    <path d="M19 16H5M9 12l-4 4 4 4"/>
                                </svg>
                            </span>
                            <span class="tx-type-text text-primary">Transferencia</span>
                        </label>
                    </div>
                </div>

                <!-- Cuenta origen -->
                <div class="mb-3">
                    <label class="form-label tx-form-label" id="edit-labelAccountOrigin">Cuenta</label>
                    <div class="account-cards-grid" id="edit-account-cards-origin"></div>
                    <input type="hidden" id="edit-modal-account-id">
                </div>

                <!-- Cuenta destino (solo transferencia) -->
                <div class="mb-3 d-none" id="edit-transferToDiv">
                    <label class="form-label tx-form-label">Cuenta destino</label>
                    <div class="account-cards-grid" id="edit-account-cards-dest"></div>
                    <input type="hidden" id="edit-modal-transfer-to">
                </div>

                <!-- Categoría -->
                <div class="mb-3" id="edit-categoryDiv">
                    <label class="form-label tx-form-label">Categoría</label>
                    <select id="edit-modal-category-id" class="form-control tx-form-control">
                        <option value="">Seleccionar categoría</option>
                    </select>
                </div>

                <!-- Monto -->
                <div class="mb-3">
                    <label class="form-label tx-form-label">Monto</label>
                    <input type="number" step="0.01" id="edit-modal-amount" class="form-control tx-form-control" placeholder="0.00">
                </div>

                <!-- Fecha -->
                <div class="mb-3">
                    <label class="form-label tx-form-label">Fecha</label>
                    <input type="date" id="edit-modal-date" class="form-control tx-form-control">
                </div>

                <!-- Descripción -->
                <div class="mb-1">
                    <label class="form-label tx-form-label">
                        Descripción <span style="font-weight:400;color:#aaa">(opcional)</span>
                    </label>
                    <textarea id="edit-modal-description" class="form-control tx-form-control" rows="2"
                              placeholder="Ej: Compra en supermercado…"></textarea>
                </div>

            </div>
            <div class="modal-footer" style="padding:14px 24px;border-top:1px solid #f0f0f0">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn-save-tx" onclick="TransactionModule.updateTransaction()">
                    Guardar cambios
                </button>
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
.ex-val.wrap {
    white-space: normal;
    word-break: break-word;
    line-height: 1.4;
}
.ex-divider { width: 1px; height: 30px; background: var(--tx-border); flex-shrink: 0; }

/* ── Botón eliminar (normal) ── */
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

/* ── Botón tarjeta (móvil) ── */
.btn-card-info-row {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    border: 1px solid #bfdbfe;
    background: #eff6ff;
    color: #3b82f6;
    font-size: 15px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    flex-shrink: 0;
    transition: background .15s;
    line-height: 1;
}
.btn-card-info-row:hover { background: #dbeafe; }

/* ── Botón bloqueado por antigüedad (móvil) ── */
.btn-locked-row {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    border: 1px solid #e5e7eb;
    background: #f9fafb;
    color: #d1d5db;
    font-size: 13px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: not-allowed;
    flex-shrink: 0;
}

/* ── Ruta de transferencia (móvil) ── */
.ex-route {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 11px;
    color: #374151;
    flex-wrap: wrap;
}
.ex-route-arrow {
    color: var(--tx-trf);
    font-size: 12px;
    font-weight: 700;
    flex-shrink: 0;
}
.ex-route-name {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 70px;
}

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

/* ═══════════════════════════════════════════════
   TABLA DESKTOP — DISEÑO REFINADO
   ═══════════════════════════════════════════════ */
.tx-table-wrap { overflow-x: auto; }

.tx-desktop-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    overflow: hidden;
    box-shadow:
        0 1px 2px rgba(0,0,0,.04),
        0 4px 16px rgba(0,0,0,.05);
}

.tx-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}
.tx-table thead tr {
    background: #f9fafb;
    border-bottom: 1px solid #e9ecef;
}
.tx-table thead th {
    padding: 11px 16px;
    text-align: left;
    font-size: 10.5px;
    font-weight: 600;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: .06em;
    white-space: nowrap;
}
.tx-table thead th:first-child { padding-left: 20px; }
.tx-table thead th:last-child  { padding-right: 20px; text-align: center; }

.tx-table tbody tr {
    border-bottom: 1px solid #f3f4f6;
    transition: background .1s;
}
.tx-table tbody tr:last-child { border-bottom: none; }
.tx-table tbody tr:hover { background: #fafbfc; }

.tx-table td {
    padding: 13px 16px;
    color: #374151;
    vertical-align: middle;
}
.tx-table td:first-child { padding-left: 20px; }
.tx-table td:last-child  { padding-right: 20px; text-align: center; }

.tx-table .tx-id {
    color: #c4c9d4;
    font-size: 11px;
    font-weight: 500;
    letter-spacing: .02em;
}
.tx-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 9px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 500;
}
.badge-inc { background: var(--tx-inc-bg); color: var(--tx-inc-text); }
.badge-exp { background: var(--tx-exp-bg); color: var(--tx-exp-text); }
.badge-trf { background: var(--tx-trf-bg); color: var(--tx-trf-text); }
.tx-table .amt-col { font-weight: 600; white-space: nowrap; }

.tx-table .tx-desc-cell {
    color: #6b7280;
    max-width: 220px;
    word-break: break-word;
    white-space: normal;
    font-size: 12px;
    line-height: 1.5;
}
.tx-desc-empty { color: #d1d5db; font-style: italic; font-size: 12px; }

.tx-route-wrap {
    display: flex;
    flex-direction: column;
    gap: 2px;
}
.tx-route-line {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 12px;
    color: #374151;
}
.tx-route-dot-from,
.tx-route-dot-to {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    flex-shrink: 0;
}
.tx-route-dot-from { background: var(--tx-trf); }
.tx-route-dot-to   { background: #60a5fa; }
.tx-route-connector {
    display: flex;
    align-items: center;
    padding-left: 2.5px;
    height: 6px;
}
.tx-route-connector-line {
    width: 1px;
    height: 8px;
    background: #bfdbfe;
}
.tx-route-label {
    font-size: 9.5px;
    font-weight: 600;
    letter-spacing: .04em;
    text-transform: uppercase;
    color: #9ca3af;
    min-width: 20px;
}
.tx-route-name {
    color: #374151;
    font-size: 12px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 150px;
}

.tx-account-name {
    font-size: 13px;
    color: #374151;
}

/* ── Botones de acción en desktop ── */
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
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.tx-table .btn-del-table:hover { background: #fee2e2; }

.tx-table .btn-card-info-table {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    border: 1px solid #bfdbfe;
    background: #eff6ff;
    color: #3b82f6;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    transition: background .15s;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    line-height: 1;
}
.tx-table .btn-card-info-table:hover { background: #dbeafe; }

.tx-table .btn-locked-table {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    border: 1px solid #e5e7eb;
    background: #f9fafb;
    color: #d1d5db;
    font-size: 12px;
    cursor: not-allowed;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

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
    border: 2px solid #e5e7eb;
    background: #f9fafb;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 12px 6px 10px;
    gap: 7px;
    transition: border-color .2s, background .2s, box-shadow .2s;
    user-select: none;
}
.tx-type-card:hover { background: #f3f4f6; }
/* Icono: circulo con fondo de color */
.tx-type-icon {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform .15s;
}
.tx-type-card:hover .tx-type-icon { transform: scale(1.05); }
.tx-icon-inc { background: var(--tx-inc-bg);  color: var(--tx-inc); }
.tx-icon-exp { background: var(--tx-exp-bg);  color: var(--tx-exp); }
.tx-icon-trf { background: var(--tx-trf-bg);  color: var(--tx-trf); }
.tx-type-text { font-size: 12px; font-weight: 600; }
.income-card.selected   { border-color: var(--tx-inc); background: var(--tx-inc-bg); box-shadow: 0 0 0 3px rgba(29,158,117,.1); }
.expense-card.selected  { border-color: var(--tx-exp); background: var(--tx-exp-bg); box-shadow: 0 0 0 3px rgba(216,90,48,.1); }
.transfer-card.selected { border-color: var(--tx-trf); background: var(--tx-trf-bg); box-shadow: 0 0 0 3px rgba(59,130,246,.1); }

/* ── Cards de selección de cuenta ── */
.account-cards-grid {
    display: flex;
    flex-wrap: nowrap;
    gap: 7px;
    overflow-x: auto;
    padding-bottom: 4px;
    scrollbar-width: none;
    -ms-overflow-style: none;
}
.account-cards-grid::-webkit-scrollbar { display: none; }
.account-card-item {
    cursor: pointer;
    border-radius: 10px;
    border: 2px solid #e5e7eb;
    background: #f9fafb;
    padding: 10px 12px;
    transition: border-color .18s, background .18s, box-shadow .18s;
    user-select: none;
    position: relative;
    overflow: hidden;
    flex: 0 0 140px;   /* ancho fijo, nunca se encoge */
}
.account-card-item:hover {
    background: #f3f4f6;
    border-color: #d1d5db;
}
.account-card-item.selected {
    border-color: #374151;
    background: #f8f9fb;
    box-shadow: 0 0 0 3px rgba(55,65,81,.1);
}
/* Indicador de selección (esquina superior derecha) */
.account-card-item.selected::after {
    content: '';
    position: absolute;
    top: 6px;
    right: 6px;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #374151;
}
.acc-card-name {
    font-size: 12px;
    font-weight: 600;
    color: #1f2937;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-bottom: 3px;
}
.acc-card-bal {
    font-size: 11px;
    color: #6b7280;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.acc-card-cur {
    font-size: 9px;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: .05em;
    margin-top: 2px;
    font-weight: 600;
}
/* Variante para cuenta destino (borde azul cuando seleccionada) */
.account-card-item.selected-dest {
    border-color: var(--tx-trf);
    background: var(--tx-trf-bg);
    box-shadow: 0 0 0 3px rgba(59,130,246,.1);
}
.account-card-item.selected-dest::after {
    background: var(--tx-trf);
}
.account-card-empty {
    color: #9ca3af;
    font-size: 13px;
    padding: 10px 0;
    grid-column: 1 / -1;
}

/* ── Responsive: ocultar lista / tabla según pantalla ── */
.tx-mobile-list  { display: flex; flex-direction: column; gap: 2px; }
.tx-desktop-wrap { display: none; }

@media (min-width: 768px) {
    .tx-mobile-list  { display: none !important; }
    .tx-desktop-wrap { display: block !important; }
    .swipe-hint      { display: none !important; }
    #summary-bar     { grid-template-columns: 1fr 1fr 1fr; }
    .sum-val         { font-size: 18px; }
    .account-cards-grid { gap: 9px; }
    .account-card-item  { flex: 0 0 160px; }
}

/* ── Botón editar (móvil) ── */
.btn-edit-row {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    border: 1px solid #bfdbfe;
    background: #eff6ff;
    color: #3b82f6;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    flex-shrink: 0;
    transition: background .15s;
}
.btn-edit-row:hover { background: #dbeafe; }

/* ── Botón editar (desktop) ── */
.tx-table .btn-edit-table {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    border: 1px solid #bfdbfe;
    background: #eff6ff;
    color: #3b82f6;
    cursor: pointer;
    transition: background .15s;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.tx-table .btn-edit-table:hover { background: #dbeafe; }

/* ── Grupo de botones de acción ── */
.tx-btn-group {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
}

/* ── Badge de "editable" en el panel extra móvil ── */
.tx-edit-badge {
    font-size: 9px;
    padding: 2px 6px;
    border-radius: 20px;
    background: #eff6ff;
    color: #3b82f6;
    border: 1px solid #bfdbfe;
    white-space: nowrap;
    align-self: flex-start;
    margin-top: 2px;
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
    let _transactions = [];

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

    // ─── Restricciones de eliminación ─────────────

    function isOlderThan3Days(dateStr) {
        const txDate = new Date(dateStr + 'T00:00:00');
        const today  = new Date();
        today.setHours(0, 0, 0, 0);
        return (today - txDate) / 86400000 > 3;
    }

    function isEditableByAge(t) {
        if (isCardAccount(t.account_type)) return false;
        if (t.created_at) {
            const ts = new Date(t.created_at.replace(' ', 'T'));
            return (Date.now() - ts.getTime()) <= 86400000;
        }
        // Fallback (sin columna created_at): solo permite el día actual
        const today = new Date().toISOString().split('T')[0];
        return t.date === today;
    }

    function isCardAccount(accountType) {
        return accountType === 'debit_card' || accountType === 'credit_card';
    }

    function showCardInfo() {
        Swal.fire({
            title: 'Transacción de tarjeta',
            text: 'Para eliminar transacciones de tarjetas debe hacerlo desde el módulo de tarjetas.',
            icon: 'info',
            confirmButtonColor: '#374151',
            confirmButtonText: 'Entendido',
        });
    }

    function getActionBtn(t, mode) {
        const isDesktop = mode === 'desktop';

        // ── Tarjeta: solo botón info ──
        if (isCardAccount(t.account_type)) {
            const btn = isDesktop
                ? `<button class="btn-card-info-table" onclick="TransactionModule.showCardInfo()" title="Gestionar desde módulo de tarjetas">?</button>`
                : `<button class="btn-card-info-row"   onclick="TransactionModule.showCardInfo()" title="Transacción de tarjeta">?</button>`;
            return `<div class="tx-btn-group">${btn}</div>`;
        }

        // ── Botón editar (solo si < 24 h) ──
        const pencilIcon = `<svg width="${isDesktop ? 12 : 13}" height="${isDesktop ? 12 : 13}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>`;
        const editBtn = isEditableByAge(t)
            ? (isDesktop
                ? `<button class="btn-edit-table" onclick="TransactionModule.openEditModal(${t.id})" title="Editar transacción">${pencilIcon}</button>`
                : `<button class="btn-edit-row"   onclick="TransactionModule.openEditModal(${t.id})" title="Editar">${pencilIcon}</button>`)
            : '';

        // ── Botón eliminar (con límite de 3 días) ──
        const lockIcon = `<svg width="${isDesktop ? 12 : 13}" height="${isDesktop ? 12 : 13}" viewBox="0 0 16 16" fill="currentColor"><path d="M11 7V5a3 3 0 0 0-6 0v2H3v8h10V7h-2zm-4-2a1 1 0 0 1 2 0v2H7V5z"/></svg>`;
        const deleteBtn = !isOlderThan3Days(t.date)
            ? (isDesktop
                ? `<button class="btn-del-table" onclick="TransactionModule.confirmDelete(${t.id})" title="Eliminar transacción">✕</button>`
                : `<button class="btn-del-row"   onclick="TransactionModule.confirmDelete(${t.id})" title="Eliminar">✕</button>`)
            : (isDesktop
                ? `<button class="btn-locked-table" title="No eliminable: más de 3 días" disabled>${lockIcon}</button>`
                : `<button class="btn-locked-row"   title="No eliminable: más de 3 días" disabled>${lockIcon}</button>`);

        return `<div class="tx-btn-group">${editBtn}${deleteBtn}</div>`;
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

    // ─── SVG íconos para la lista ──────────────────

    function listIcon(type) {
        if (type === 'income') {
            return `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19V5"/><path d="M5 12l7 7 7-7"/></svg>`;
        }
        if (type === 'expense') {
            return `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="M5 12l7-7 7 7"/></svg>`;
        }
        // transfer
        return `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 8h14M15 4l4 4-4 4"/><path d="M19 16H5M9 12l-4 4 4 4"/></svg>`;
    }

    // ─── Render MÓVIL (tarjetas deslizables) ──────

    function renderMobile(transactions) {
        if (!transactions.length) return '<div class="tx-empty"><div class="tx-empty-icon">📭</div>Sin transacciones</div>';

        return '<div class="tx-mobile-list">' + transactions.map(t => {
            const dotCls = t.type === 'income' ? 'dot-inc' : t.type === 'expense' ? 'dot-exp' : 'dot-trf';
            const amtCls = t.type === 'income' ? 'amt-inc' : t.type === 'expense' ? 'amt-exp' : 'amt-trf';
            const dateFmt = new Date(t.date + 'T00:00:00').toLocaleDateString('es-DO', { day:'2-digit', month:'2-digit', year:'numeric' });

            const accountSection = t.type === 'transfer'
                ? `<div class="tx-extra-col" style="min-width:0">
                       <div class="ex-label">Ruta</div>
                       <div class="ex-route">
                           <span class="ex-route-name">${esc(t.account_name ?? '?')}</span>
                           <span class="ex-route-arrow">→</span>
                           <span class="ex-route-name">${esc(t.transfer_to_account_name ?? '?')}</span>
                       </div>
                   </div>`
                : `<div class="tx-extra-col">
                       <div class="ex-label">Cuenta</div>
                       <div class="ex-val">${esc(t.account_name ?? '-')}</div>
                   </div>`;

            return `
            <div class="tx-item">
                <div class="tx-scroll" id="sc${t.id}">
                    <div class="tx-panel">
                        <div class="tx-dot ${dotCls}">${listIcon(t.type)}</div>
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
                        ${accountSection}
                        <div class="ex-divider"></div>
                        <div class="tx-extra-col">
                            <div class="ex-label">Descripción</div>
                            <div class="ex-val wrap">${esc(t.description || '—')}</div>
                        </div>
                        <div class="ex-divider"></div>
                        ${getActionBtn(t, 'mobile')}
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

            const accountCell = t.type === 'transfer'
                ? `<div class="tx-route-wrap">
                       <div class="tx-route-line">
                           <div class="tx-route-dot-from"></div>
                           <span class="tx-route-label">De</span>
                           <span class="tx-route-name" title="${esc(t.account_name ?? '')}">${esc(t.account_name ?? '—')}</span>
                       </div>
                       <div class="tx-route-connector"><div class="tx-route-connector-line"></div></div>
                       <div class="tx-route-line">
                           <div class="tx-route-dot-to"></div>
                           <span class="tx-route-label">A</span>
                           <span class="tx-route-name" title="${esc(t.transfer_to_account_name ?? '')}">${esc(t.transfer_to_account_name ?? '?')}</span>
                       </div>
                   </div>`
                : `<span class="tx-account-name">${esc(t.account_name ?? '—')}</span>`;

            const descCell = t.description
                ? `<div class="tx-desc-cell">${esc(t.description)}</div>`
                : `<span class="tx-desc-empty">—</span>`;

            return `
            <tr id="tr-${t.id}">
                <td class="tx-id">#${t.id}</td>
                <td style="white-space:nowrap">${dateFmt}</td>
                <td>${accountCell}</td>
                <td>${esc(t.category_name ?? '—')}</td>
                <td><span class="tx-badge ${badgeCls}">${label}</span></td>
                <td class="amt-col ${amtCls}">${esc(t.symbol ?? '')} ${fmt(t.original_amount)} <span style="font-size:10px;font-weight:400;color:#9ca3af">${esc(t.original_currency ?? '')}</span></td>
                <td style="color:#9ca3af;white-space:nowrap">RD$ ${fmt(t.converted_amount_dop)}</td>
                <td>${descCell}</td>
                <td>${getActionBtn(t, 'desktop')}</td>
            </tr>`;
        }).join('');

        return `<div class="tx-desktop-wrap">
            <div class="tx-desktop-card">
                <div class="tx-table-wrap">
                    <table class="tx-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Fecha</th>
                                <th>Cuenta / Ruta</th>
                                <th>Categoría</th>
                                <th>Tipo</th>
                                <th>Monto</th>
                                <th>DOP</th>
                                <th>Descripción</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>${rows}</tbody>
                    </table>
                </div>
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

        transactions.forEach(t => {
            const sc = document.getElementById('sc' + t.id);
            if (!sc) return;
            sc.addEventListener('scroll', () => {
                const at = sc.scrollLeft > sc.scrollWidth * 0.3;
                document.getElementById('md0-' + t.id)?.classList.toggle('active', !at);
                document.getElementById('md1-' + t.id)?.classList.toggle('active', at);
            });
        });

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
            _transactions = data.transactions;

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

    // ─── Cards de cuentas ─────────────────────────

    /**
     * Renderiza las cards de selección de cuenta.
     * @param {string}      containerId   - ID del div contenedor
     * @param {string}      hiddenInputId - ID del input hidden donde se guarda el valor
     * @param {number|null} selectedId    - ID de la cuenta actualmente seleccionada
     * @param {number|null} excludeId     - ID de cuenta a excluir (para destino en transferencias)
     * @param {boolean}     isDest        - Si es la selección de cuenta destino (estilo azul)
     */
    function renderAccountCards(containerId, hiddenInputId, selectedId = null, excludeId = null, isDest = false) {
        const container = document.getElementById(containerId);
        const hidden    = document.getElementById(hiddenInputId);
        if (!container || !hidden) return;

        const filtered = excludeId
            ? _accounts.filter(a => parseInt(a.id) !== parseInt(excludeId))
            : _accounts;

        if (!filtered.length) {
            container.innerHTML = '<div class="account-card-empty">No hay cuentas disponibles.</div>';
            hidden.value = '';
            return;
        }

        const selClass = isDest ? 'selected-dest' : 'selected';

        container.innerHTML = filtered.map(a => {
            const isSelected = selectedId !== null && parseInt(a.id) === parseInt(selectedId);
            return `<div class="account-card-item ${isSelected ? selClass : ''}"
                         data-id="${a.id}"
                         onclick="TransactionModule.selectAccountCard('${containerId}','${hiddenInputId}',${a.id},${isDest})">
                        <div class="acc-card-name">${esc(a.name)}</div>
                        <div class="acc-card-bal">${esc(a.symbol || '')} ${fmt(a.balance)}</div>
                        <div class="acc-card-cur">${esc(a.currency_code)}</div>
                    </div>`;
        }).join('');

        // Si había un selectedId válido, reflejarlo en el hidden input
        const validSelected = filtered.find(a => parseInt(a.id) === parseInt(selectedId));
        hidden.value = validSelected ? selectedId : '';
    }

    /**
     * Maneja el clic en una card de cuenta.
     */
    function selectAccountCard(containerId, hiddenInputId, accountId, isDest = false) {
        const selClass = isDest ? 'selected-dest' : 'selected';

        document.querySelectorAll(`#${containerId} .account-card-item`).forEach(c => {
            c.classList.remove('selected', 'selected-dest');
        });

        const card = document.querySelector(`#${containerId} [data-id="${accountId}"]`);
        if (card) card.classList.add(selClass);
        document.getElementById(hiddenInputId).value = accountId;

        // Refrescar destino al cambiar origen (tanto en modal nuevo como en edición)
        const originContainers = { 'account-cards-origin': false, 'edit-account-cards-origin': true };
        if (containerId in originContainers) {
            const isEdit   = originContainers[containerId];
            const typeInput = isEdit
                ? document.querySelector('#edit-type-row input[name="edit-type"]:checked')
                : document.querySelector('input[name="type"]:checked');
            if (typeInput?.value === 'transfer') {
                const destContainer = isEdit ? 'edit-account-cards-dest'  : 'account-cards-dest';
                const destHidden    = isEdit ? 'edit-modal-transfer-to'   : 'modal-transfer-to';
                const currentDest   = document.getElementById(destHidden).value || null;
                const newDest       = (currentDest && parseInt(currentDest) === parseInt(accountId)) ? null : currentDest;
                renderAccountCards(destContainer, destHidden, newDest ? parseInt(newDest) : null, accountId, true);
            }
        }
    }

    // ─── Categorías modal (rebuild dinámico — compatible iOS/Android) ──

    /**
     * Reconstruye las opciones del select de categoría del modal
     * según el tipo seleccionado. Evita el uso de display:none en
     * <optgroup> que no funciona en iOS/Android.
     */
    function filterModalCategories(type) {
        const select = document.getElementById('modal-category-id');
        select.innerHTML = '<option value="">Seleccionar categoría</option>';

        let cats = [];
        if (type === 'income')  cats = _incomeCats;
        if (type === 'expense') cats = _expenseCats;

        cats.forEach(c => {
            const opt = document.createElement('option');
            opt.value       = c.id;
            opt.textContent = c.name;
            select.appendChild(opt);
        });

        if (cats.length > 0) select.value = cats[0].id;
    }

    // Categorías para el modal de edición
    function filterEditModalCategories(type, selectedId = null) {
        const select = document.getElementById('edit-modal-category-id');
        select.innerHTML = '<option value="">Seleccionar categoría</option>';
        const cats = type === 'income' ? _incomeCats : _expenseCats;
        cats.forEach(c => {
            const opt = new Option(c.name, c.id);
            if (selectedId !== null && parseInt(c.id) === parseInt(selectedId)) opt.selected = true;
            select.appendChild(opt);
        });
    }

    // Abrir modal de edición pre-rellenado
    function openEditModal(id) {
        const t = _transactions.find(tx => parseInt(tx.id) === parseInt(id));
        if (!t) { showError('Datos de transacción no disponibles. Recarga la página.'); return; }

        // ID visible en el título
        document.getElementById('edit-transaction-id').value     = t.id;
        document.getElementById('edit-tx-id-badge').textContent  = `#${t.id}`;

        // Seleccionar tipo
        document.querySelectorAll('#edit-type-row .tx-type-card').forEach(c => c.classList.remove('selected'));
        const labelMap = { income: 'edit-labelIncome', expense: 'edit-labelExpense', transfer: 'edit-labelTransfer' };
        document.getElementById(labelMap[t.type])?.classList.add('selected');
        const radioEdit = document.querySelector(`#edit-type-row input[value="${t.type}"]`);
        if (radioEdit) radioEdit.checked = true;

        const isTransfer = t.type === 'transfer';
        document.getElementById('edit-transferToDiv').classList.toggle('d-none', !isTransfer);
        document.getElementById('edit-categoryDiv').classList.toggle('d-none', isTransfer);
        document.getElementById('edit-labelAccountOrigin').textContent = isTransfer ? 'Cuenta origen' : 'Cuenta';

        // Cards de cuenta origen
        renderAccountCards('edit-account-cards-origin', 'edit-modal-account-id', parseInt(t.account_id));

        // Cards destino / categoría
        if (isTransfer) {
            renderAccountCards(
                'edit-account-cards-dest', 'edit-modal-transfer-to',
                t.transfer_to_account ? parseInt(t.transfer_to_account) : null,
                parseInt(t.account_id), true
            );
            document.getElementById('edit-modal-category-id').value = '1';
        } else {
            filterEditModalCategories(t.type, t.category_id ? parseInt(t.category_id) : null);
        }

        // Monto, fecha, descripción
        document.getElementById('edit-modal-amount').value      = t.original_amount;
        document.getElementById('edit-modal-date').value        = t.date;
        document.getElementById('edit-modal-description').value = t.description || '';

        new bootstrap.Modal(document.getElementById('editTransactionModal')).show();
    }

    // Enviar edición al servidor
    async function updateTransaction() {
        const transaction_id = document.getElementById('edit-transaction-id').value;
        const type           = document.querySelector('#edit-type-row input[name="edit-type"]:checked')?.value;
        const account_id     = document.getElementById('edit-modal-account-id').value;
        const transfer_to    = document.getElementById('edit-modal-transfer-to').value;
        const category_id    = document.getElementById('edit-modal-category-id').value;
        const amount         = document.getElementById('edit-modal-amount').value;
        const date           = document.getElementById('edit-modal-date').value;
        const description    = document.getElementById('edit-modal-description').value;

        if (!account_id) { showError('Selecciona una cuenta de origen.'); return; }
        if (type === 'transfer' && !transfer_to) { showError('Selecciona una cuenta destino.'); return; }
        if (!amount || parseFloat(amount) <= 0)  { showError('El monto debe ser mayor a 0.'); return; }

        try {
            const data = await request('edit_transaction', {
                transaction_id, account_id, category_id, type,
                amount, date, description,
                transfer_to: transfer_to || '',
                payment_currency: '',
            });
            showSuccess(data.message);
            bootstrap.Modal.getInstance(document.getElementById('editTransactionModal'))?.hide();
            await loadTransactions();
        } catch (err) {
            showError(err.message, err.fullMessage);
        }
    }

    /**
     * Reconstruye las opciones del select de categoría del panel de filtros
     * según el tipo de filtro activo.
     */
    function updateFilterCategories(type) {
        const select   = document.getElementById('filter-category');
        const prevVal  = select.value;
        select.innerHTML = '<option value="">Todas las categorías</option>';

        if (type === '' || type === 'income') {
            if (type === '') {
                // Mostrar ambos grupos con optgroup (sin display:none, solo visibles)
                if (_incomeCats.length) {
                    const grp = document.createElement('optgroup');
                    grp.label = 'Ingresos';
                    _incomeCats.forEach(c => grp.appendChild(new Option(c.name, c.id)));
                    select.appendChild(grp);
                }
            } else {
                _incomeCats.forEach(c => select.appendChild(new Option(c.name, c.id)));
            }
        }

        if (type === '' || type === 'expense') {
            if (type === '') {
                if (_expenseCats.length) {
                    const grp = document.createElement('optgroup');
                    grp.label = 'Gastos';
                    _expenseCats.forEach(c => grp.appendChild(new Option(c.name, c.id)));
                    select.appendChild(grp);
                }
            } else {
                _expenseCats.forEach(c => select.appendChild(new Option(c.name, c.id)));
            }
        }

        // Para 'transfer': sin categorías (las transferencias no tienen)
        // El select queda solo con "Todas las categorías"

        // Intentar restaurar valor previo si sigue disponible
        if (prevVal) select.value = prevVal;
    }

    // ─── Cargar datos de formulario ───────────────

    async function loadFormData() {
        try {
            const data = await request('get_form_data', {}, 'GET');
            _accounts    = data.accounts;
            _incomeCats  = data.income_categories;
            _expenseCats = data.expense_categories;

            // Filtro de cuentas
            document.getElementById('filter-account').innerHTML =
                '<option value="">Todas las cuentas</option>' +
                _accounts.map(a => `<option value="${a.id}">${esc(a.name)} (${a.currency_code})</option>`).join('');

            // Render inicial de cards de cuentas (sin selección)
            renderAccountCards('account-cards-origin', 'modal-account-id');

            // Categorías del filtro
            updateFilterCategories('');

            // Categorías del modal (por defecto expense)
            filterModalCategories('expense');

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
        ['filter-account','filter-type',
         'filter-min-amount','filter-max-amount',
         'filter-date-from','filter-date-to','filter-search']
            .forEach(id => { document.getElementById(id).value = ''; });
        document.getElementById('clearSearchBtn').style.display = 'none';

        // Reconstruir el select de categorías para mostrar todos los tipos
        updateFilterCategories('');

        loadTransactions();
    }

    function goToPage(p) { _state.page = p; loadTransactions(); }

    // ─── Agregar transacción ──────────────────────

    async function addTransaction() {
        const type        = document.querySelector('input[name="type"]:checked')?.value;
        const account_id  = document.getElementById('modal-account-id').value;
        const transfer_to = document.getElementById('modal-transfer-to').value;
        // Para transferencias, se fuerza category_id = 1 (establecido al cambiar tipo)
        const category_id = document.getElementById('modal-category-id').value;
        const amount      = document.getElementById('modal-amount').value;
        const date        = document.getElementById('modal-date').value;
        const description = document.getElementById('modal-description').value;

        if (!account_id) { showError('Selecciona una cuenta de origen.'); return; }
        if (type === 'transfer' && !transfer_to) { showError('Selecciona una cuenta destino.'); return; }
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

            // Agregar a la lista interna y reconstruir el select del modal
            const newCat = { id: json.category_id, name };
            if (type === 'income') {
                _incomeCats.push(newCat);
            } else {
                _expenseCats.push(newCat);
            }

            // Reconstruir opciones del modal (aplica al tipo actual)
            const currentType = document.querySelector('input[name="type"]:checked')?.value;
            filterModalCategories(currentType);

            // Seleccionar la nueva categoría si coincide con el tipo activo
            if (currentType === type) {
                document.getElementById('modal-category-id').value = json.category_id;
            }

            // Actualizar también el select del filtro
            updateFilterCategories(document.getElementById('filter-type').value);

        } catch (err) {
            Swal.close();
            showError(err.message, err.fullMessage);
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

            const isTransfer = type === 'transfer';

            // Mostrar/ocultar cuenta destino
            document.getElementById('transferToDiv').classList.toggle('d-none', !isTransfer);

            // Mostrar/ocultar categoría
            document.getElementById('categoryDiv').classList.toggle('d-none', isTransfer);

            if (isTransfer) {
                // Transferencia: categoría = 1 fija (no visible)
                document.getElementById('modal-category-id').value = '1';

                // Label de la cuenta origen
                document.getElementById('labelAccountOrigin').textContent = 'Cuenta origen';

                // Refrescar cards destino (excluyendo la origen actual)
                const originId = document.getElementById('modal-account-id').value || null;
                renderAccountCards('account-cards-dest', 'modal-transfer-to', null, originId ? parseInt(originId) : null, true);
            } else {
                document.getElementById('labelAccountOrigin').textContent = 'Cuenta';

                // Reconstruir categorías según tipo
                filterModalCategories(type);
            }
        });
    });

    // Reset modal al abrir — por defecto: GASTO
    document.getElementById('transactionModal').addEventListener('shown.bs.modal', () => {
        // Activar tipo "gasto"
        document.querySelectorAll('.tx-type-card').forEach(c => c.classList.remove('selected'));
        document.getElementById('labelExpense').classList.add('selected');
        document.getElementById('typeExpense').checked = true;

        // Ocultar cuenta destino y mostrar categoría
        document.getElementById('transferToDiv').classList.add('d-none');
        document.getElementById('categoryDiv').classList.remove('d-none');
        document.getElementById('labelAccountOrigin').textContent = 'Cuenta';

        // Reconstruir categorías para gasto
        filterModalCategories('expense');

        // Fecha de hoy
        document.getElementById('modal-date').value = new Date().toISOString().split('T')[0];

        // Refrescar cards de cuentas sin selección
        renderAccountCards('account-cards-origin', 'modal-account-id');
    });

    // Filtro: cuando cambia el tipo, actualizar las categorías disponibles
    document.getElementById('filter-type').addEventListener('change', function () {
        updateFilterCategories(this.value);
        // Limpiar valor de categoría si ya no aplica
        document.getElementById('filter-category').value = '';
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

    // Selector de tipo en modal de EDICIÓN
    document.querySelectorAll('#edit-type-row .tx-type-card').forEach(card => {
        card.addEventListener('click', () => {
            document.querySelectorAll('#edit-type-row .tx-type-card').forEach(c => c.classList.remove('selected'));
            card.classList.add('selected');
            card.querySelector('input[type="radio"]').checked = true;
            const type = card.querySelector('input[type="radio"]').value;
            const isTransfer = type === 'transfer';

            document.getElementById('edit-transferToDiv').classList.toggle('d-none', !isTransfer);
            document.getElementById('edit-categoryDiv').classList.toggle('d-none', isTransfer);
            document.getElementById('edit-labelAccountOrigin').textContent = isTransfer ? 'Cuenta origen' : 'Cuenta';

            if (isTransfer) {
                document.getElementById('edit-modal-category-id').value = '1';
                const originId = document.getElementById('edit-modal-account-id').value || null;
                renderAccountCards('edit-account-cards-dest', 'edit-modal-transfer-to',
                    null, originId ? parseInt(originId) : null, true);
            } else {
                filterEditModalCategories(type);
            }
        });
    });

    // ─── Init ─────────────────────────────────────

    document.getElementById('modal-date').value = new Date().toISOString().split('T')[0];
    Promise.all([loadFormData(), loadTransactions()]);

    return {
    applyFilters, clearFilters, goToPage,
    addTransaction, confirmDelete,
    createQuickCategory, showCardInfo,
    selectAccountCard,
    openEditModal,
    updateTransaction,
};

})();

console.log('[TransactionModule] Módulo cargado.');
</script>