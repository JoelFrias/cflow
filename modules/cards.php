<?php
// modules/cards.php - Módulo para gestión de tarjetas de crédito y débito

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Tarjetas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

<style>
/* =============================================
   VARIABLES & BASE
   ============================================= */
:root {
    --card-radius: 16px;
    --soft-border: 1px solid #e8e8e8;
    --muted: #6c757d;
    --danger-light: #fff1f0;
    --danger-mid: #e24b4a;
    --success-light: #f0faf4;
    --success-mid: #1D9E75;
    --warn-light: #fffbeb;
    --warn-mid: #BA7517;
    --info-light: #eef4fd;
    --info-mid: #185FA5;
}

body { background: #f5f5f5; }

/* =============================================
   PAGE HEADER
   ============================================= */
.page-header {
    background: #fff;
    border-bottom: var(--soft-border);
    padding: 14px 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.page-header h5 { margin: 0; font-size: 16px; font-weight: 600; }

/* =============================================
   SUMMARY STRIP
   ============================================= */
.summary-strip {
    background: #fff;
    border-bottom: var(--soft-border);
    padding: 10px 16px;
    display: flex;
    gap: 20px;
    overflow-x: auto;
    scrollbar-width: none;
    white-space: nowrap;
}
.summary-strip::-webkit-scrollbar { display: none; }
.summary-item { display: flex; flex-direction: column; }
.summary-item .s-label { font-size: 10px; color: var(--muted); text-transform: uppercase; letter-spacing: 0.04em; }
.summary-item .s-value { font-size: 14px; font-weight: 600; color: #212529; }
.summary-item .s-value.is-danger { color: var(--danger-mid); }
.summary-divider { width: 1px; background: #e8e8e8; flex-shrink: 0; align-self: stretch; }

/* =============================================
   SECTION LABEL
   ============================================= */
.section-label {
    font-size: 11px;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: 0.06em;
    padding: 14px 16px 6px;
}

/* =============================================
   SHARED CARD INTERNALS
   ============================================= */
.card-type-badge {
    font-size: 10px;
    font-weight: 600;
    padding: 2px 8px;
    border-radius: 20px;
    display: inline-block;
    margin-bottom: 8px;
    letter-spacing: 0.03em;
}
.badge-credit { background: var(--danger-light); color: var(--danger-mid); }
.badge-debit  { background: var(--info-light); color: var(--info-mid); }

.card-name { font-size: 15px; font-weight: 600; margin-bottom: 12px; color: #212529; }

.balances-row { display: flex; gap: 10px; margin-bottom: 12px; }
.balance-box {
    flex: 1;
    background: #f8f8f8;
    border-radius: 10px;
    padding: 9px 11px;
}
.bal-label { font-size: 10px; color: var(--muted); margin-bottom: 2px; text-transform: uppercase; letter-spacing: 0.04em; }
.bal-amount { font-size: 15px; font-weight: 700; }
.bal-zero   { color: #aaa; }
.bal-debt   { color: var(--danger-mid); }

.mini-progress {
    height: 3px;
    background: #e8e8e8;
    border-radius: 2px;
    overflow: hidden;
    margin-top: 5px;
}
.mini-bar { height: 100%; border-radius: 2px; }
.bar-ok     { background: var(--success-mid); }
.bar-warn   { background: var(--warn-mid); }
.bar-danger { background: var(--danger-mid); }

.card-limit-hint { font-size: 10px; color: var(--muted); margin-top: 2px; }

.card-actions-row { display: flex; gap: 7px; margin-top: 2px; }
.card-action-btn {
    flex: 1;
    font-size: 12px;
    font-weight: 500;
    padding: 7px 4px;
    border-radius: 8px;
    border: 1px solid #e0e0e0;
    background: #fff;
    color: #212529;
    cursor: pointer;
    text-align: center;
    transition: background 0.15s;
    white-space: nowrap;
}
.card-action-btn:hover { background: #f5f5f5; }
.card-action-btn.btn-expense { background: var(--info-light); border-color: transparent; color: var(--info-mid); }
.card-action-btn.btn-pay    { background: var(--success-light); border-color: transparent; color: var(--success-mid); }

.card-limit-alert {
    font-size: 11px;
    padding: 5px 9px;
    border-radius: 7px;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 5px;
}
.alert-over { background: var(--danger-light); color: var(--danger-mid); }
.alert-near { background: var(--warn-light); color: var(--warn-mid); }

/* =============================================
   MOBILE: SLIDER (max 767px)
   ============================================= */
@media (max-width: 767px) {
    .cards-desktop { display: none !important; }

    .cards-slider-outer { padding: 0 0 0 16px; }
    .cards-slider-wrap {
        display: flex;
        gap: 12px;
        overflow-x: auto;
        scroll-snap-type: x mandatory;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
        padding-bottom: 4px;
        padding-right: 16px;
    }
    .cards-slider-wrap::-webkit-scrollbar { display: none; }

    .card-slide {
        flex: 0 0 calc(100vw - 48px);
        max-width: 340px;
        scroll-snap-align: start;
        background: #fff;
        border: var(--soft-border);
        border-radius: var(--card-radius);
        padding: 16px;
        transition: border-color 0.2s;
    }
    .card-slide.is-active { border-color: #185FA5; border-width: 1.5px; }

    .dot-indicators {
        display: flex;
        justify-content: center;
        gap: 6px;
        padding: 10px 0 4px;
    }
    .dot {
        width: 6px; height: 6px;
        border-radius: 50%;
        background: #d0d0d0;
        transition: background 0.2s, transform 0.2s;
    }
    .dot.active { background: #185FA5; transform: scale(1.35); }

    .card-detail-panel {
        margin: 12px 16px 24px;
        background: #fff;
        border: var(--soft-border);
        border-radius: var(--card-radius);
        overflow: hidden;
    }
    .detail-tabs { display: flex; border-bottom: var(--soft-border); }
    .detail-tab {
        flex: 1;
        padding: 10px 0;
        font-size: 13px;
        text-align: center;
        color: var(--muted);
        cursor: pointer;
        border-bottom: 2px solid transparent;
        background: none;
        border-top: none;
        border-left: none;
        border-right: none;
    }
    .detail-tab.active { color: #212529; font-weight: 600; border-bottom-color: #185FA5; }
    .detail-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 11px 14px;
        border-bottom: 1px solid #f0f0f0;
        font-size: 13px;
    }
    .detail-row:last-child { border-bottom: none; }
    .detail-row .dl { color: var(--muted); }
    .detail-row .dr { font-weight: 500; }
    .tx-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 11px 14px;
        border-bottom: 1px solid #f0f0f0;
    }
    .tx-row:last-child { border-bottom: none; }
    .tx-meta { font-size: 13px; font-weight: 500; }
    .tx-date { font-size: 11px; color: var(--muted); margin-top: 1px; }
    .tx-amount { font-size: 13px; font-weight: 600; text-align: right; }
    .tx-badge { font-size: 10px; padding: 1px 7px; border-radius: 20px; }
    .tx-gasto { background: var(--danger-light); color: var(--danger-mid); }
    .tx-pago  { background: var(--success-light); color: var(--success-mid); }
}

/* =============================================
   DESKTOP: GRID (min 768px)
   ============================================= */
@media (min-width: 768px) {
    .cards-slider-outer { display: none !important; }
    .dot-indicators     { display: none !important; }
    .card-detail-panel  { display: none !important; }
    .section-label      { display: none; }

    .cards-desktop {
        display: grid !important;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 16px;
    }
    .card-desktop-item {
        background: #fff;
        border: var(--soft-border);
        border-radius: var(--card-radius);
        padding: 18px;
    }
}

/* =============================================
   SKELETON / EMPTY
   ============================================= */
.skeleton-card {
    background: #fff;
    border: var(--soft-border);
    border-radius: var(--card-radius);
    padding: 16px;
    height: 200px;
    animation: pulse 1.4s infinite;
}
@keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.5} }

.empty-state { text-align: center; padding: 48px 16px; color: var(--muted); }
.empty-state i { font-size: 2.5rem; margin-bottom: 12px; opacity: 0.3; display: block; }
.empty-state p { font-size: 14px; margin-bottom: 16px; }
</style>
</head>
<body>

<!-- ============================================ -->
<!-- PAGE HEADER                                 -->
<!-- ============================================ -->
<div class="page-header">
    <h5><i class="fas fa-credit-card me-2 text-primary"></i>Mis Tarjetas</h5>
    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createCardModal">
        <i class="fas fa-plus"></i> Nueva
    </button>
</div>

<!-- ============================================ -->
<!-- SUMMARY STRIP                               -->
<!-- ============================================ -->
<div class="summary-strip">
    <div class="summary-item">
        <span class="s-label">Tarjetas</span>
        <span class="s-value" id="sum-count">—</span>
    </div>
    <div class="summary-divider"></div>
    <div class="summary-item">
        <span class="s-label">Deuda DOP</span>
        <span class="s-value is-danger" id="sum-dop">—</span>
    </div>
    <div class="summary-divider"></div>
    <div class="summary-item">
        <span class="s-label">Deuda USD</span>
        <span class="s-value is-danger" id="sum-usd">—</span>
    </div>
    <div class="summary-divider"></div>
    <div class="summary-item">
        <span class="s-label">Crédito / Débito</span>
        <span class="s-value" id="sum-types">—</span>
    </div>
</div>

<!-- Alertas globales -->
<div id="limit-alerts" class="px-3 pt-2"></div>

<!-- ============================================ -->
<!-- MOBILE: SLIDER                              -->
<!-- ============================================ -->
<div class="section-label">Selecciona una tarjeta</div>

<div class="cards-slider-outer" id="mobile-slider-outer">
    <div class="cards-slider-wrap" id="cards-slider">
        <div class="skeleton-card" style="flex:0 0 calc(100vw - 48px);max-width:340px;"></div>
        <div class="skeleton-card" style="flex:0 0 calc(100vw - 48px);max-width:340px;"></div>
    </div>
</div>

<div class="dot-indicators" id="dot-indicators"></div>

<!-- Panel detalle / historial (solo móvil) -->
<div class="card-detail-panel" id="card-detail-panel" style="display:none;">
    <div class="detail-tabs">
        <button class="detail-tab active" onclick="switchTab(event,'tab-limits')">Límites</button>
        <button class="detail-tab" onclick="switchTab(event,'tab-history')">Últimos movimientos</button>
    </div>
    <div id="tab-limits">
        <div class="detail-row"><span class="dl">Selecciona una tarjeta</span></div>
    </div>
    <div id="tab-history" style="display:none;">
        <div class="detail-row"><span class="dl">Selecciona una tarjeta</span></div>
    </div>
</div>

<!-- ============================================ -->
<!-- DESKTOP: GRID                               -->
<!-- ============================================ -->
<div class="container-fluid py-3 d-none d-md-block">
    <div class="cards-desktop" id="cards-desktop">
        <div class="skeleton-card"></div>
        <div class="skeleton-card"></div>
    </div>
</div>

<!-- ============================================ -->
<!-- MODAL: CREAR TARJETA                        -->
<!-- ============================================ -->
<div class="modal fade" id="createCardModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus-circle"></i> Nueva Tarjeta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Nombre de la tarjeta</label>
                    <input type="text" id="card-name" class="form-control" placeholder="Ej: Visa Platinum" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Tipo</label>
                    <select id="card-type" class="form-control" required>
                        <option value="debit_card">Tarjeta de Débito</option>
                        <option value="credit_card">Tarjeta de Crédito</option>
                    </select>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Límite (USD)</label>
                        <input type="number" step="0.01" id="card-limit-usd" class="form-control" placeholder="Opcional">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Límite (DOP)</label>
                        <input type="number" step="0.01" id="card-limit-dop" class="form-control" placeholder="Opcional">
                    </div>
                </div>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <small>La tarjeta puede tener gastos en USD y DOP, con balances separados por moneda.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btn-create-card">
                    <span id="btn-create-card-text">Crear Tarjeta</span>
                    <span id="btn-create-card-spinner" class="spinner-border spinner-border-sm d-none"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- MODAL: REGISTRAR GASTO                      -->
<!-- ============================================ -->
<div class="modal fade" id="expenseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-shopping-cart"></i> Registrar Gasto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="expense-card-id">
                <div class="mb-3">
                    <label class="form-label">Moneda del gasto</label>
                    <select id="expense-currency" class="form-control" required>
                        <option value="DOP">DOP - Peso Dominicano</option>
                        <option value="USD">USD - Dólar Americano</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Monto</label>
                    <input type="number" step="0.01" id="expense-amount" class="form-control" placeholder="0.00" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Categoría</label>
                    <select id="expense-category" class="form-control">
                        <option value="">Seleccionar categoría</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Fecha</label>
                    <input type="date" id="expense-date" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Descripción</label>
                    <textarea id="expense-description" class="form-control" rows="2" placeholder="Opcional"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="btn-add-expense">
                    <span id="btn-expense-text">Registrar Gasto</span>
                    <span id="btn-expense-spinner" class="spinner-border spinner-border-sm d-none"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- MODAL: PAGAR TARJETA                        -->
<!-- ============================================ -->
<div class="modal fade" id="payModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-money-bill-wave"></i> Pagar Tarjeta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="pay-card-id">
                <div class="alert alert-info">
                    <small><i class="fas fa-info-circle"></i> Las cuentas de origen están en DOP. Para pagar USD, especifica cuánto DOP deseas debitar.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Cuenta de origen (DOP)</label>
                    <select id="pay-account" class="form-control" required>
                        <option value="">Seleccionar cuenta</option>
                    </select>
                    <small id="no-dop-accounts-msg" class="text-danger d-none">
                        No tienes cuentas en DOP. <a href="?module=accounts">Crea una cuenta primero</a>.
                    </small>
                </div>
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="enable-dop-payment">
                            <label class="form-check-label fw-bold" for="enable-dop-payment">Pagar en DOP</label>
                        </div>
                    </div>
                    <div class="card-body d-none" id="dop-payment-section">
                        <label>Monto a pagar en DOP</label>
                        <div class="input-group">
                            <span class="input-group-text">RD$</span>
                            <input type="number" step="0.01" id="pay-dop" class="form-control" placeholder="0.00" value="0">
                        </div>
                    </div>
                </div>
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="enable-usd-payment">
                            <label class="form-check-label fw-bold" for="enable-usd-payment">Pagar en USD</label>
                        </div>
                    </div>
                    <div class="card-body d-none" id="usd-payment-section">
                        <div class="mb-2">
                            <label>Monto a pagar en USD</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" step="0.01" id="pay-usd" class="form-control" placeholder="0.00" value="0">
                            </div>
                        </div>
                        <div class="mb-2">
                            <label>Monto en DOP a debitar</label>
                            <div class="input-group">
                                <span class="input-group-text">RD$</span>
                                <input type="number" step="0.01" id="dop-for-usd" class="form-control" placeholder="0.00" value="0">
                            </div>
                            <small class="text-muted" id="usd-rate-hint">Tasa actual: 1 USD = RD$ ...</small>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Fecha de pago</label>
                    <input type="date" id="pay-date" class="form-control" required>
                </div>
                <div class="alert alert-secondary" id="total-preview">
                    <strong>Total a descontar:</strong> RD$ 0.00
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" id="btn-pay-card">
                    <span id="btn-pay-text">Confirmar Pago</span>
                    <span id="btn-pay-spinner" class="spinner-border spinner-border-sm d-none"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- MODAL: HISTORIAL COMPLETO                   -->
<!-- ============================================ -->
<div class="modal fade" id="transactionsHistoryModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">
                    <i class="fas fa-history me-2"></i> Historial de <span id="history-card-name">Tarjeta</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="history-card-id">
                <div class="row g-2 mb-3">
                    <div class="col-md-3">
                        <label class="form-label">Fecha desde</label>
                        <input type="date" id="filter-date-from" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Fecha hasta</label>
                        <input type="date" id="filter-date-to" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Tipo</label>
                        <select id="filter-type" class="form-select">
                            <option value="">Todos</option>
                            <option value="expense">Gasto</option>
                            <option value="payment">Pago</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button class="btn btn-primary w-100" id="btn-apply-filters">
                            <i class="fas fa-filter"></i> Aplicar
                        </button>
                    </div>
                </div>
                <hr>
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Descripción</th>
                                <th>Categoría</th>
                                <th>Tipo</th>
                                <th class="text-end">Monto</th>
                                <th class="text-end">Balance después</th>
                                <th class="text-center">Acción</th>
                            </tr>
                        </thead>
                        <tbody id="history-table-body">
                            <tr>
                                <td colspan="7" class="text-center py-3">
                                    <div class="spinner-border spinner-border-sm text-primary"></div> Cargando...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div id="history-pagination" class="mt-3"></div>
                </div>
                <div id="history-empty-message" class="text-center text-muted py-3 d-none">
                    <i class="fas fa-receipt fa-2x mb-2 opacity-50"></i>
                    <p>No hay transacciones que coincidan con los filtros.</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- SCRIPTS                                     -->
<!-- ============================================ -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
const CARDS_AJAX_URL = 'ajax/cards.php';

// Estado global
let _usdRate       = 62.50;
let _categories    = [];
let _dopAccounts   = [];
let _allCards      = [];
let _activeCardIdx = 0;

// Historial
let _historyTransactions = [];
let _historyPage         = 1;
const HISTORY_PAGE_SIZE  = 10;

/* ============================================
   HELPERS
   ============================================ */
function showError(message, fullMessage = null) {
    console.error('[Cards]', fullMessage || message);
    Swal.fire({ toast:true, position:'top-start', icon:'error', title:message,
        showConfirmButton:false, timer:4500, timerProgressBar:true });
}
function showSuccess(message) {
    Swal.fire({ toast:true, position:'top-start', icon:'success', title:message,
        showConfirmButton:false, timer:3000, timerProgressBar:true });
}
function fmt(n) {
    return parseFloat(n).toLocaleString('es-DO', { minimumFractionDigits:2 });
}
function escapeHtml(str) {
    if (str == null) return '';
    return String(str)
        .replace(/&/g,'&amp;').replace(/</g,'&lt;')
        .replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
}
function setLoading(btnId, textId, spinnerId, loading) {
    const btn = document.getElementById(btnId);
    const textEl = document.getElementById(textId);
    const spinnerEl = document.getElementById(spinnerId);
    if (!btn || !textEl || !spinnerEl) return;
    btn.disabled = loading;
    if (loading) { textEl.dataset.original = textEl.textContent; textEl.textContent = 'Procesando...'; }
    else { textEl.textContent = textEl.dataset.original || textEl.textContent; }
    spinnerEl.classList.toggle('d-none', !loading);
}
function localDateStr() {
    const now = new Date();
    return `${now.getFullYear()}-${String(now.getMonth()+1).padStart(2,'0')}-${String(now.getDate()).padStart(2,'0')}`;
}
function ajaxPost(body) {
    return fetch(CARDS_AJAX_URL, {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body: new URLSearchParams(body).toString(),
    }).then(r => r.json());
}

/* ============================================
   HTML INTERNO COMPARTIDO (slider + grid)
   ============================================ */
function buildCardInnerHTML(card) {
    const pctUsd = card.credit_limit_usd ? Math.min(100,(card.balance_usd/card.credit_limit_usd)*100) : 0;
    const pctDop = card.credit_limit_dop ? Math.min(100,(card.balance_dop/card.credit_limit_dop)*100) : 0;
    const classUsd = pctUsd>90?'bar-danger':pctUsd>70?'bar-warn':'bar-ok';
    const classDop = pctDop>90?'bar-danger':pctDop>70?'bar-warn':'bar-ok';
    const typeBadge = card.type==='credit_card'?'badge-credit':'badge-debit';
    const typeLabel = card.type==='credit_card'?'Crédito':'Débito';

    let alertHtml = '';
    if (card.credit_limit_usd && pctUsd>=100)
        alertHtml += `<div class="card-limit-alert alert-over"><i class="fas fa-exclamation-circle"></i> Límite USD excedido</div>`;
    else if (card.credit_limit_usd && pctUsd>=80)
        alertHtml += `<div class="card-limit-alert alert-near"><i class="fas fa-exclamation-triangle"></i> Cerca del límite USD (${Math.round(pctUsd)}%)</div>`;
    if (card.credit_limit_dop && pctDop>=100)
        alertHtml += `<div class="card-limit-alert alert-over"><i class="fas fa-exclamation-circle"></i> Límite DOP excedido</div>`;
    else if (card.credit_limit_dop && pctDop>=80)
        alertHtml += `<div class="card-limit-alert alert-near"><i class="fas fa-exclamation-triangle"></i> Cerca del límite DOP (${Math.round(pctDop)}%)</div>`;

    const usdExtra = card.credit_limit_usd
        ? `<div class="mini-progress"><div class="mini-bar ${classUsd}" style="width:${pctUsd}%"></div></div>
           <div class="card-limit-hint">Lím: $${fmt(card.credit_limit_usd)} &middot; Disp: $${fmt(card.credit_limit_usd-card.balance_usd)}</div>`
        : '';
    const dopExtra = card.credit_limit_dop
        ? `<div class="mini-progress"><div class="mini-bar ${classDop}" style="width:${pctDop}%"></div></div>
           <div class="card-limit-hint">Lím: RD$${fmt(card.credit_limit_dop)} &middot; Disp: RD$${fmt(card.credit_limit_dop-card.balance_dop)}</div>`
        : '';

    const canDelete = parseFloat(card.balance_usd)===0 && parseFloat(card.balance_dop)===0;
    const deleteBtn = canDelete
        ? `<button class="card-action-btn" style="flex:0 0 36px;" title="Eliminar tarjeta"
               onclick="confirmDelete(${card.id},'${escapeHtml(card.name)}')">
               <i class="fas fa-trash" style="font-size:11px;color:#e24b4a;"></i>
           </button>`
        : '';

    return `
        ${alertHtml}
        <span class="card-type-badge ${typeBadge}">${typeLabel}</span>
        <div class="card-name">${escapeHtml(card.name)}</div>
        <div class="balances-row">
            <div class="balance-box">
                <div class="bal-label">USD</div>
                <div class="bal-amount ${parseFloat(card.balance_usd)>0?'bal-debt':'bal-zero'}">
                    $${fmt(card.balance_usd)}
                </div>
                ${usdExtra}
            </div>
            <div class="balance-box">
                <div class="bal-label">DOP</div>
                <div class="bal-amount ${parseFloat(card.balance_dop)>0?'bal-debt':'bal-zero'}">
                    RD$${fmt(card.balance_dop)}
                </div>
                ${dopExtra}
            </div>
        </div>
        <div class="card-actions-row">
            <button class="card-action-btn btn-expense" onclick="openExpenseModal(${card.id})">
                <i class="fas fa-plus" style="font-size:10px;"></i> Gasto
            </button>
            <button class="card-action-btn btn-pay" onclick="openPayModal(${card.id})">
                <i class="fas fa-money-bill-wave" style="font-size:10px;"></i> Pagar
            </button>
            <button class="card-action-btn" onclick="openHistoryModal(${card.id},'${escapeHtml(card.name)}')">
                <i class="fas fa-history" style="font-size:10px;"></i> Historial
            </button>
            ${deleteBtn}
        </div>
    `;
}

/* ============================================
   SUMMARY STRIP
   ============================================ */
function renderSummaryStrip(data) {
    const credito = data.cards.filter(c=>c.type==='credit_card').length;
    const debito  = data.cards.filter(c=>c.type==='debit_card').length;
    document.getElementById('sum-count').textContent = data.cards.length;
    document.getElementById('sum-dop').textContent   = 'RD$ ' + fmt(data.total_balance_dop);
    document.getElementById('sum-usd').textContent   = '$ '   + fmt(data.total_balance_usd);
    document.getElementById('sum-types').textContent = `${credito} / ${debito}`;
}

/* ============================================
   ALERTAS GLOBALES
   ============================================ */
function renderAlerts(data) {
    let html = '';
    if (data.cards_over_limit && data.cards_over_limit.length > 0) {
        const items = data.cards_over_limit.map(a=>`<li>${escapeHtml(a.name)} — ${a.percent}% (${a.currency})</li>`).join('');
        html += `<div class="alert alert-danger py-2"><i class="fas fa-exclamation-circle"></i> <strong>Límite excedido:</strong><ul class="mb-0 mt-1">${items}</ul></div>`;
    }
    if (data.cards_near_limit && data.cards_near_limit.length > 0) {
        const items = data.cards_near_limit.map(a=>`<li>${escapeHtml(a.name)} — ${a.percent}% (${a.currency})</li>`).join('');
        html += `<div class="alert alert-warning py-2"><i class="fas fa-exclamation-triangle"></i> <strong>Cerca del límite:</strong><ul class="mb-0 mt-1">${items}</ul></div>`;
    }
    document.getElementById('limit-alerts').innerHTML = html;
}

/* ============================================
   MOBILE SLIDER
   ============================================ */
function renderMobileSlider(cards) {
    const slider = document.getElementById('cards-slider');
    const dotsEl = document.getElementById('dot-indicators');
    const panel  = document.getElementById('card-detail-panel');

    if (cards.length === 0) {
        slider.innerHTML = `
            <div style="flex:0 0 calc(100vw - 48px);max-width:340px;">
                <div class="empty-state">
                    <i class="fas fa-credit-card"></i>
                    <p>No tienes tarjetas registradas</p>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createCardModal">
                        Crear primera tarjeta
                    </button>
                </div>
            </div>`;
        dotsEl.innerHTML = '';
        panel.style.display = 'none';
        return;
    }

    slider.innerHTML = cards.map((card,idx) =>
        `<div class="card-slide ${idx===0?'is-active':''}" data-idx="${idx}">
            ${buildCardInnerHTML(card)}
        </div>`
    ).join('');

    dotsEl.innerHTML = cards.map((_,i) =>
        `<div class="dot ${i===0?'active':''}"></div>`
    ).join('');

    panel.style.display = '';
    _activeCardIdx = 0;
    renderMobileDetailPanel(cards[0]);

    // Scroll → actualizar dots y panel
    slider.addEventListener('scroll', () => {
        const slideW = slider.firstElementChild ? slider.firstElementChild.offsetWidth + 12 : 1;
        const idx    = Math.round(slider.scrollLeft / slideW);
        if (idx !== _activeCardIdx && cards[idx]) {
            _activeCardIdx = idx;
            document.querySelectorAll('.card-slide').forEach((c,i) => c.classList.toggle('is-active', i===idx));
            document.querySelectorAll('#dot-indicators .dot').forEach((d,i) => d.classList.toggle('active', i===idx));
            renderMobileDetailPanel(cards[idx]);
        }
    }, { passive: true });
}

/* ============================================
   DESKTOP GRID
   ============================================ */
function renderDesktopGrid(cards) {
    const grid = document.getElementById('cards-desktop');
    if (cards.length === 0) {
        grid.innerHTML = `
            <div style="grid-column:1/-1;">
                <div class="empty-state">
                    <i class="fas fa-credit-card"></i>
                    <p>No tienes tarjetas registradas</p>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createCardModal">
                        Crear primera tarjeta
                    </button>
                </div>
            </div>`;
        return;
    }
    grid.innerHTML = cards.map(card =>
        `<div class="card-desktop-item" id="card-item-${card.id}">
            ${buildCardInnerHTML(card)}
        </div>`
    ).join('');
}

/* ============================================
   PANEL DETALLE MÓVIL
   ============================================ */
function renderMobileDetailPanel(card) {
    const pctUsd = card.credit_limit_usd ? Math.min(100,(card.balance_usd/card.credit_limit_usd)*100) : null;
    const pctDop = card.credit_limit_dop ? Math.min(100,(card.balance_dop/card.credit_limit_dop)*100) : null;

    let limHtml = '';
    if (pctUsd !== null) {
        limHtml += `
            <div class="detail-row"><span class="dl">Límite USD</span><span class="dr">$${fmt(card.credit_limit_usd)}</span></div>
            <div class="detail-row"><span class="dl">Utilizado USD</span>
                <span class="dr ${pctUsd>90?'text-danger':pctUsd>70?'text-warning':'text-success'}">${Math.round(pctUsd)}%</span></div>
            <div class="detail-row"><span class="dl">Disponible USD</span><span class="dr">$${fmt(card.credit_limit_usd-card.balance_usd)}</span></div>`;
    }
    if (pctDop !== null) {
        limHtml += `
            <div class="detail-row"><span class="dl">Límite DOP</span><span class="dr">RD$${fmt(card.credit_limit_dop)}</span></div>
            <div class="detail-row"><span class="dl">Utilizado DOP</span>
                <span class="dr ${pctDop>90?'text-danger':pctDop>70?'text-warning':'text-success'}">${Math.round(pctDop)}%</span></div>
            <div class="detail-row"><span class="dl">Disponible DOP</span><span class="dr">RD$${fmt(card.credit_limit_dop-card.balance_dop)}</span></div>`;
    }
    if (!limHtml) limHtml = `<div class="detail-row"><span class="dl">Sin límites definidos</span></div>`;
    document.getElementById('tab-limits').innerHTML = limHtml;

    // Cargar últimos 5 movimientos
    document.getElementById('tab-history').innerHTML =
        `<div class="detail-row"><div class="spinner-border spinner-border-sm text-primary me-2"></div> Cargando...</div>`;

    ajaxPost({ action:'get_card_transactions', card_id:card.id })
        .then(data => {
            const tabHist = document.getElementById('tab-history');
            if (!data.success || !data.transactions || data.transactions.length === 0) {
                tabHist.innerHTML = `<div class="detail-row"><span class="dl">Sin movimientos recientes</span></div>`;
                return;
            }
            const recent = data.transactions.slice(0, 5);
            tabHist.innerHTML = recent.map(t => {
                const signo = t.row_type==='expense'?'+':'-';
                const cls   = t.row_type==='expense'?'text-danger':'text-success';
                const badge = t.row_type==='expense'
                    ? '<span class="tx-badge tx-gasto">Gasto</span>'
                    : '<span class="tx-badge tx-pago">Pago</span>';
                const fecha = new Date(t.date+'T00:00:00').toLocaleDateString('es-DO',{day:'numeric',month:'short'});
                const desc  = escapeHtml(t.description||(t.row_type==='payment'?'Pago de tarjeta':'Gasto'));
                return `<div class="tx-row">
                    <div>
                        <div class="tx-meta">${desc}</div>
                        <div class="tx-date">${fecha}</div>
                    </div>
                    <div>
                        <div class="tx-amount ${cls}">${signo} ${t.currency_symbol||''}${fmt(t.amount)}</div>
                        ${badge}
                    </div>
                </div>`;
            }).join('') + `
                <div class="detail-row" style="justify-content:center;">
                    <span style="font-size:12px;color:#185FA5;cursor:pointer;"
                          onclick="openHistoryModal(${card.id},'${escapeHtml(card.name)}')">
                        Ver historial completo →
                    </span>
                </div>`;
        })
        .catch(() => {
            document.getElementById('tab-history').innerHTML =
                `<div class="detail-row"><span class="dl text-danger">Error al cargar</span></div>`;
        });
}

function switchTab(event, panelId) {
    document.querySelectorAll('.detail-tab').forEach(t => t.classList.remove('active'));
    event.target.classList.add('active');
    ['tab-limits','tab-history'].forEach(id => {
        document.getElementById(id).style.display = id===panelId ? 'block' : 'none';
    });
}

/* ============================================
   CARGAR DATOS
   ============================================ */
function loadCards() {
    ajaxPost({ action:'get_cards' })
        .then(data => {
            if (!data.success) { showError(data.message, data.full_message); return; }
            _usdRate     = data.usd_rate;
            _categories  = data.categories;
            _dopAccounts = data.dop_accounts;
            _allCards    = data.cards;
            _activeCardIdx = 0;

            renderSummaryStrip(data);
            renderAlerts(data);
            renderMobileSlider(data.cards);
            renderDesktopGrid(data.cards);
        })
        .catch(err => showError('No se pudieron cargar las tarjetas.', err.message));
}

/* ============================================
   CREAR TARJETA
   ============================================ */
document.getElementById('btn-create-card').addEventListener('click', function () {
    const name     = document.getElementById('card-name').value.trim();
    const type     = document.getElementById('card-type').value;
    const limitUsd = document.getElementById('card-limit-usd').value;
    const limitDop = document.getElementById('card-limit-dop').value;
    if (!name) { showError('El nombre de la tarjeta es obligatorio.'); return; }

    setLoading('btn-create-card','btn-create-card-text','btn-create-card-spinner',true);
    ajaxPost({ action:'create_card', name, type, credit_limit_usd:limitUsd, credit_limit_dop:limitDop })
        .then(data => {
            if (!data.success) { showError(data.message, data.full_message); return; }
            showSuccess(data.message);
            bootstrap.Modal.getInstance(document.getElementById('createCardModal')).hide();
            document.getElementById('card-name').value = '';
            document.getElementById('card-limit-usd').value = '';
            document.getElementById('card-limit-dop').value = '';
            loadCards();
        })
        .catch(err => showError('Error de red al crear la tarjeta.', err.message))
        .finally(() => setLoading('btn-create-card','btn-create-card-text','btn-create-card-spinner',false));
});

/* ============================================
   MODAL GASTO
   ============================================ */
function openExpenseModal(cardId) {
    document.getElementById('expense-card-id').value     = cardId;
    document.getElementById('expense-amount').value      = '';
    document.getElementById('expense-description').value = '';
    document.getElementById('expense-date').value        = localDateStr();
    const sel = document.getElementById('expense-category');
    sel.innerHTML = '<option value="">Seleccionar categoría</option>' +
        _categories.map(c=>`<option value="${c.id}">${escapeHtml(c.name)}</option>`).join('');
    new bootstrap.Modal(document.getElementById('expenseModal')).show();
}

document.getElementById('btn-add-expense').addEventListener('click', function () {
    const cardId      = document.getElementById('expense-card-id').value;
    const currency    = document.getElementById('expense-currency').value;
    const amount      = document.getElementById('expense-amount').value;
    const categoryId  = document.getElementById('expense-category').value;
    const date        = document.getElementById('expense-date').value;
    const description = document.getElementById('expense-description').value.trim();
    if (!amount || parseFloat(amount) <= 0) { showError('El monto debe ser mayor a 0.'); return; }

    setLoading('btn-add-expense','btn-expense-text','btn-expense-spinner',true);
    ajaxPost({ action:'add_card_expense', card_id:cardId, currency, amount, category_id:categoryId, date, description })
        .then(data => {
            if (!data.success) { showError(data.message, data.full_message); return; }
            showSuccess(data.message);
            bootstrap.Modal.getInstance(document.getElementById('expenseModal')).hide();
            loadCards();
        })
        .catch(err => showError('Error de red al registrar el gasto.', err.message))
        .finally(() => setLoading('btn-add-expense','btn-expense-text','btn-expense-spinner',false));
});

/* ============================================
   MODAL PAGO
   ============================================ */
function openPayModal(cardId) {
    document.getElementById('pay-card-id').value = cardId;
    document.getElementById('pay-date').value     = localDateStr();
    document.getElementById('pay-dop').value      = '0';
    document.getElementById('pay-usd').value      = '0';
    document.getElementById('dop-for-usd').value  = '0';
    document.getElementById('enable-dop-payment').checked = false;
    document.getElementById('enable-usd-payment').checked = false;
    document.getElementById('dop-payment-section').classList.add('d-none');
    document.getElementById('usd-payment-section').classList.add('d-none');
    document.getElementById('usd-rate-hint').textContent = `Tasa actual: 1 USD = RD$ ${fmt(_usdRate)}`;
    updateTotalPreview();

    const sel   = document.getElementById('pay-account');
    const noMsg = document.getElementById('no-dop-accounts-msg');
    sel.innerHTML = '<option value="">Seleccionar cuenta</option>';
    if (_dopAccounts.length === 0) {
        noMsg.classList.remove('d-none');
    } else {
        noMsg.classList.add('d-none');
        _dopAccounts.forEach(acc => {
            sel.innerHTML += `<option value="${acc.id}">${escapeHtml(acc.name)} - Saldo: RD$ ${fmt(acc.balance)}</option>`;
        });
    }
    new bootstrap.Modal(document.getElementById('payModal')).show();
}

function updateTotalPreview() {
    const dop       = parseFloat(document.getElementById('pay-dop').value) || 0;
    const dopForUsd = parseFloat(document.getElementById('dop-for-usd').value) || 0;
    document.getElementById('total-preview').innerHTML =
        `<strong>Total a descontar:</strong> RD$ ${fmt(dop + dopForUsd)}`;
}

document.getElementById('enable-dop-payment').addEventListener('change', function () {
    document.getElementById('dop-payment-section').classList.toggle('d-none', !this.checked);
    if (!this.checked) { document.getElementById('pay-dop').value='0'; updateTotalPreview(); }
});
document.getElementById('enable-usd-payment').addEventListener('change', function () {
    document.getElementById('usd-payment-section').classList.toggle('d-none', !this.checked);
    if (!this.checked) {
        document.getElementById('pay-usd').value='0';
        document.getElementById('dop-for-usd').value='0';
        updateTotalPreview();
    }
});
['pay-dop','dop-for-usd'].forEach(id =>
    document.getElementById(id).addEventListener('input', updateTotalPreview)
);

document.getElementById('btn-pay-card').addEventListener('click', function () {
    const cardId      = document.getElementById('pay-card-id').value;
    const accountId   = document.getElementById('pay-account').value;
    const payUsd      = document.getElementById('pay-usd').value || '0';
    const payDop      = document.getElementById('pay-dop').value || '0';
    const dopForUsd   = document.getElementById('dop-for-usd').value || '0';
    const paymentDate = document.getElementById('pay-date').value;
    if (!accountId) { showError('Debes seleccionar una cuenta de origen.'); return; }
    if (parseFloat(payUsd)<=0 && parseFloat(payDop)<=0) { showError('Debes ingresar al menos un monto.'); return; }

    setLoading('btn-pay-card','btn-pay-text','btn-pay-spinner',true);
    ajaxPost({ action:'pay_card', card_id:cardId, account_id:accountId,
        pay_usd:payUsd, pay_dop:payDop, dop_amount_for_usd:dopForUsd, payment_date:paymentDate })
        .then(data => {
            if (!data.success) { showError(data.message, data.full_message); return; }
            bootstrap.Modal.getInstance(document.getElementById('payModal')).hide();
            Swal.fire({ title:'¡Pago exitoso!', html:data.summary.join('<br>'), icon:'success' });
            loadCards();
        })
        .catch(err => showError('Error de red al procesar el pago.', err.message))
        .finally(() => setLoading('btn-pay-card','btn-pay-text','btn-pay-spinner',false));
});

/* ============================================
   ELIMINAR TARJETA
   ============================================ */
function confirmDelete(cardId, cardName) {
    Swal.fire({
        title:'¿Eliminar tarjeta?',
        text:`¿Eliminar "${cardName}"? Solo es posible si no tiene deuda.`,
        icon:'warning', showCancelButton:true,
        confirmButtonColor:'#d33', cancelButtonText:'Cancelar', confirmButtonText:'Sí, eliminar',
    }).then(result => {
        if (!result.isConfirmed) return;
        ajaxPost({ action:'delete_card', card_id:cardId })
            .then(data => {
                if (!data.success) { showError(data.message, data.full_message); return; }
                showSuccess(data.message);
                loadCards();
            })
            .catch(err => showError('Error de red al eliminar.', err.message));
    });
}

/* ============================================
   HISTORIAL COMPLETO (modal)
   ============================================ */
function openHistoryModal(cardId, cardName) {
    document.getElementById('history-card-id').value         = cardId;
    document.getElementById('history-card-name').textContent = cardName;
    document.getElementById('filter-date-from').value = '';
    document.getElementById('filter-date-to').value   = '';
    document.getElementById('filter-type').value       = '';
    document.getElementById('history-pagination').innerHTML = '';
    _historyTransactions = [];
    _historyPage = 1;
    new bootstrap.Modal(document.getElementById('transactionsHistoryModal')).show();
    loadCardTransactions(cardId);
}

function loadCardTransactions(cardId, resetPage = true) {
    if (resetPage) _historyPage = 1;
    const tbody = document.getElementById('history-table-body');
    const emptyMsg = document.getElementById('history-empty-message');
    tbody.innerHTML = `<tr><td colspan="7" class="text-center py-3">
        <div class="spinner-border spinner-border-sm text-primary"></div> Cargando...</td></tr>`;
    emptyMsg.classList.add('d-none');
    document.getElementById('history-pagination').innerHTML = '';

    ajaxPost({
        action:'get_card_transactions', card_id:cardId,
        date_from: document.getElementById('filter-date-from').value,
        date_to:   document.getElementById('filter-date-to').value,
        type:      document.getElementById('filter-type').value,
    }).then(data => {
        if (!data.success) {
            showError(data.message, data.full_message);
            tbody.innerHTML = `<tr><td colspan="7" class="text-center text-danger py-3">Error al cargar.</td></tr>`;
            return;
        }
        _historyTransactions = data.transactions || [];
        renderHistoryPage();
    }).catch(err => {
        showError('Error de conexión', err.message);
        tbody.innerHTML = `<tr><td colspan="7" class="text-center text-danger py-3">Error de red.</td></tr>`;
    });
}

function renderHistoryPage() {
    const tbody      = document.getElementById('history-table-body');
    const emptyMsg   = document.getElementById('history-empty-message');
    const pagination = document.getElementById('history-pagination');
    const cardId     = document.getElementById('history-card-id').value;

    if (_historyTransactions.length === 0) {
        tbody.innerHTML = '';
        emptyMsg.classList.remove('d-none');
        pagination.innerHTML = '';
        return;
    }
    emptyMsg.classList.add('d-none');

    const totalPages = Math.ceil(_historyTransactions.length / HISTORY_PAGE_SIZE);
    const start      = (_historyPage-1) * HISTORY_PAGE_SIZE;
    const end        = Math.min(start + HISTORY_PAGE_SIZE, _historyTransactions.length);
    const today      = new Date(); today.setHours(0,0,0,0);

    let html = '';
    _historyTransactions.slice(start,end).forEach(t => {
        const fecha      = new Date(t.date+'T00:00:00').toLocaleDateString('es-DO',{year:'numeric',month:'short',day:'numeric'});
        const desc       = escapeHtml(t.description||(t.row_type==='payment'?'Pago de tarjeta':'Gasto'));
        const categoria  = t.category_name ? escapeHtml(t.category_name) : '—';
        const tipoBadge  = t.row_type==='expense'
            ? '<span class="badge bg-danger">Gasto</span>'
            : '<span class="badge bg-success">Pago</span>';
        const signo      = t.row_type==='expense' ? '+' : '-';
        const montoClass = t.row_type==='expense' ? 'text-danger fw-semibold' : 'text-success fw-semibold';
        const monto      = parseFloat(t.amount).toLocaleString('es-DO',{minimumFractionDigits:2});
        const balAfter   = parseFloat(t.balance_after||0).toLocaleString('es-DO',{minimumFractionDigits:2});
        const symbol     = t.currency_symbol||'';
        const txDate     = new Date(t.date+'T00:00:00');
        const diffDays   = Math.round((today-txDate)/86400000);
        const canDel     = diffDays>=-1 && diffDays<=3;
        const deleteBtn  = canDel
            ? `<button class="btn btn-outline-danger btn-sm py-0 px-2" style="font-size:.75rem;"
                   title="Eliminar y revertir" onclick="confirmDeleteTransaction(${t.id},${cardId})">
                   <i class="fas fa-trash-alt"></i></button>`
            : `<span class="text-muted" title="Solo eliminable los primeros 3 días"><i class="fas fa-lock" style="font-size:.75rem;"></i></span>`;
        html += `<tr>
            <td class="text-nowrap">${fecha}</td>
            <td>${desc}</td>
            <td>${categoria}</td>
            <td>${tipoBadge}</td>
            <td class="text-end ${montoClass}">${signo} ${symbol} ${monto}</td>
            <td class="text-end text-muted">${symbol} ${balAfter}</td>
            <td class="text-center">${deleteBtn}</td>
        </tr>`;
    });
    tbody.innerHTML = html;

    if (totalPages <= 1) {
        pagination.innerHTML = `<p class="text-center text-muted mb-0" style="font-size:.8rem;">
            Mostrando ${_historyTransactions.length} transacción(es)</p>`;
        return;
    }
    let pHtml = `<div class="d-flex flex-column align-items-center gap-1">
        <ul class="pagination pagination-sm mb-0 flex-wrap justify-content-center">`;
    pHtml += `<li class="page-item ${_historyPage===1?'disabled':''}">
        <button class="page-link" onclick="changeHistoryPage(${_historyPage-1})"><i class="fas fa-chevron-left"></i></button></li>`;
    for (let i=1; i<=totalPages; i++) {
        const near=Math.abs(i-_historyPage)<=1, edge=i===1||i===totalPages;
        if (!near&&!edge) { if(i===2||i===totalPages-1) pHtml+=`<li class="page-item disabled"><span class="page-link">…</span></li>`; continue; }
        pHtml+=`<li class="page-item ${i===_historyPage?'active':''}"><button class="page-link" onclick="changeHistoryPage(${i})">${i}</button></li>`;
    }
    pHtml+=`<li class="page-item ${_historyPage===totalPages?'disabled':''}">
        <button class="page-link" onclick="changeHistoryPage(${_historyPage+1})"><i class="fas fa-chevron-right"></i></button></li>`;
    pHtml+=`</ul><small class="text-muted">Mostrando ${start+1}–${end} de ${_historyTransactions.length}</small></div>`;
    pagination.innerHTML = pHtml;
}

function changeHistoryPage(page) {
    const totalPages = Math.ceil(_historyTransactions.length / HISTORY_PAGE_SIZE);
    if (page<1||page>totalPages) return;
    _historyPage = page;
    renderHistoryPage();
    document.getElementById('history-table-body')
        .closest('.table-responsive')
        .scrollIntoView({behavior:'smooth',block:'nearest'});
}

function confirmDeleteTransaction(txId, cardId) {
    Swal.fire({
        title:'¿Eliminar transacción?',
        html:`Esta acción <strong>revertirá los balances</strong> de la tarjeta y cuenta de origen.<br><br>¿Continuar?`,
        icon:'warning', showCancelButton:true,
        confirmButtonColor:'#d33', cancelButtonText:'Cancelar', confirmButtonText:'Sí, eliminar y revertir',
    }).then(result => {
        if (!result.isConfirmed) return;
        ajaxPost({ action:'delete_card_transaction', transaction_id:txId, card_id:cardId })
            .then(data => {
                if (!data.success) { showError(data.message, data.full_message); return; }
                showSuccess(data.message);
                _historyTransactions = _historyTransactions.filter(t=>t.id!=txId);
                const totalPages = Math.ceil(_historyTransactions.length/HISTORY_PAGE_SIZE);
                if (_historyPage>totalPages && totalPages>0) _historyPage=totalPages;
                renderHistoryPage();
                loadCards();
            })
            .catch(err => showError('Error de red al eliminar.', err.message));
    });
}

document.getElementById('btn-apply-filters').addEventListener('click', function () {
    const cardId = document.getElementById('history-card-id').value;
    if (cardId) loadCardTransactions(cardId);
});

/* ============================================
   INICIO
   ============================================ */
loadCards();
</script>
</body>
</html>