<?php
// modules/cards.php - Módulo para gestión de tarjetas de crédito y débito

// Asegurar que la sesión esté iniciada y el usuario autenticado
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
    <!-- Bootstrap 5 + Font Awesome + SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
</head>
<body>

<div class="container py-4">
    <!-- ============================================ -->
    <!-- RESUMEN DE TARJETAS (se rellena por JS)    -->
    <!-- ============================================ -->
    <div class="row mb-4" id="cards-summary">
        <div class="col-12 text-center py-3" id="summary-loading">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
        </div>
    </div>

    <!-- Alertas de límite -->
    <div id="limit-alerts"></div>

    <!-- ============================================ -->
    <!-- LISTA DE TARJETAS                           -->
    <!-- ============================================ -->
    <div class="card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap">
            <h5 class="mb-0"><i class="fas fa-credit-card"></i> Mis Tarjetas</h5>
            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createCardModal">
                <i class="fas fa-plus"></i> Nueva Tarjeta
            </button>
        </div>
        <div class="card-body">
            <div class="row" id="cards-list">
                <div class="col-12 text-center py-3">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                </div>
            </div>
        </div>
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
                    <input type="text" id="card-name" class="form-control"
                           placeholder="Ej: Visa Platinum, Mastercard Débito" required>
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
                        <!-- Se rellena por JS -->
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
                    <small><i class="fas fa-info-circle"></i> Las cuentas de origen están en DOP. Para pagar en USD, debes especificar cuánto DOP deseas debitar.</small>
                </div>

                <div class="mb-3">
                    <label class="form-label">Cuenta de origen (DOP)</label>
                    <select id="pay-account" class="form-control" required>
                        <option value="">Seleccionar cuenta</option>
                        <!-- Se rellena por JS -->
                    </select>
                    <small id="no-dop-accounts-msg" class="text-danger d-none">
                        No tienes cuentas en DOP. <a href="?module=accounts">Crea una cuenta primero</a>.
                    </small>
                </div>

                <!-- Pago en DOP -->
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

                <!-- Pago en USD -->
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
<!-- MODAL: HISTORIAL DE TRANSACCIONES DE TARJETA -->
<!-- ============================================ -->
<div class="modal fade" id="transactionsHistoryModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="fas fa-history me-2"></i> Historial de <span id="history-card-name">Tarjeta</span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="history-card-id">
                <!-- Filtros -->
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
                        <label class="form-label">Tipo de transacción</label>
                        <select id="filter-type" class="form-select">
                            <option value="">Todos</option>
                            <option value="expense">Gasto</option>
                            <option value="payment">Pago</option>
                            <!-- Si hay otros tipos, se pueden agregar -->
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button class="btn btn-primary w-100" id="btn-apply-filters">
                            <i class="fas fa-filter"></i> Aplicar Filtros
                        </button>
                    </div>
                </div>
                <hr>
                <!-- Tabla de resultados -->
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead class="table">
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
                                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                                        <span class="visually-hidden">Cargando...</span>
                                    </div> Cargando transacciones...
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
<!-- JAVASCRIPT                                  -->
<!-- ============================================ -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
const CARDS_AJAX_URL = 'ajax/cards.php'; // Ajusta si cambia la ruta

// Estado global del módulo
let _usdRate     = 62.50;
let _categories  = [];
let _dopAccounts = [];

// ============================================
// HELPERS: toasts + consola
// ============================================
function showError(message, fullMessage = null) {
    console.error('[Cards Error]', fullMessage || message);
    Swal.fire({
        toast: true,
        position: 'top-start',
        icon: 'error',
        title: message,
        showConfirmButton: false,
        timer: 4500,
        timerProgressBar: true,
    });
}

function showSuccess(message) {
    Swal.fire({
        toast: true,
        position: 'top-start',
        icon: 'success',
        title: message,
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
    });
}

function fmt(n, currency = 'DOP') {
    return parseFloat(n).toLocaleString('es-DO', { minimumFractionDigits: 2 });
}

function escapeHtml(str) {
    if (str == null) return '';
    return String(str)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
}

function setLoading(btnId, textId, spinnerId, loading) {
    const btn = document.getElementById(btnId);
    const textEl = document.getElementById(textId);
    const spinnerEl = document.getElementById(spinnerId);
    if (!btn || !textEl || !spinnerEl) return;
    btn.disabled = loading;
    textEl.textContent = loading ? 'Procesando...' : (textEl.dataset.original || textEl.textContent);
    spinnerEl.classList.toggle('d-none', !loading);
}

// Guardar textos originales de botones
['btn-create-card-text', 'btn-expense-text', 'btn-pay-text'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.dataset.original = el.textContent;
});

// ============================================
// AJAX helper
// ============================================
function ajaxPost(body) {
    return fetch(CARDS_AJAX_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams(body).toString(),
    }).then(r => r.json());
}

// ============================================
// RENDER: resumen superior
// ============================================
function renderSummary(data) {
    const totalDebt = data.total_balance_dop + (data.total_balance_usd * data.usd_rate);
    const totalDebtUsd = data.total_balance_usd + (data.total_balance_dop / data.usd_rate);
    const pctUsd = data.total_limit_usd > 0 ? Math.min(100, (data.total_balance_usd / data.total_limit_usd) * 100) : 0;
    const pctDop = data.total_limit_dop > 0 ? Math.min(100, (data.total_balance_dop / data.total_limit_dop) * 100) : 0;

    const credito = data.cards.filter(c => c.type === 'credit_card').length;
    const debito  = data.cards.filter(c => c.type === 'debit_card').length;

    const loadingEl = document.getElementById('summary-loading');
    if (loadingEl) loadingEl.remove();
    document.getElementById('cards-summary').innerHTML = `
        <div class="col-md-3 mb-3">
            <div class="card bg-info text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Total Tarjetas</h6>
                            <h3 class="mb-0">${data.cards.length}</h3>
                        </div>
                        <i class="fas fa-credit-card fa-3x opacity-50"></i>
                    </div>
                    <small>${credito} Crédito | ${debito} Débito</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card bg-warning text-dark h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Deuda Total (DOP)</h6>
                            <h3 class="mb-0">RD$ ${fmt(totalDebt)}</h3>
                        </div>
                        <i class="fas fa-chart-line fa-3x opacity-50"></i>
                    </div>
                    <small>≈ US$ ${fmt(totalDebtUsd)}</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card bg-success text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Total DOP Adeudado</h6>
                            <h3 class="mb-0">RD$ ${fmt(data.total_balance_dop)}</h3>
                        </div>
                        <i class="fas fa-peso-sign fa-3x opacity-50"></i>
                    </div>
                    ${data.total_limit_dop > 0 ? `
                        <small>Límite total: RD$ ${fmt(data.total_limit_dop)}</small>
                        <div class="progress mt-2" style="height:4px;">
                            <div class="progress-bar bg-warning" style="width:${pctDop}%"></div>
                        </div>` : ''}
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card bg-primary text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Total USD Adeudado</h6>
                            <h3 class="mb-0">$${fmt(data.total_balance_usd)}</h3>
                        </div>
                        <i class="fas fa-dollar-sign fa-3x opacity-50"></i>
                    </div>
                    ${data.total_limit_usd > 0 ? `
                        <small>Límite total: $${fmt(data.total_limit_usd)}</small>
                        <div class="progress mt-2" style="height:4px;">
                            <div class="progress-bar bg-warning" style="width:${pctUsd}%"></div>
                        </div>` : ''}
                </div>
            </div>
        </div>
    `;
}

// ============================================
// RENDER: alertas de límite
// ============================================
function renderAlerts(data) {
    let html = '';
    if (data.cards_over_limit.length > 0) {
        const items = data.cards_over_limit.map(a => `<li>${escapeHtml(a.name)} - ${a.percent}% usado (${a.currency})</li>`).join('');
        html += `<div class="alert alert-danger mb-3"><i class="fas fa-exclamation-circle"></i> <strong>¡Alerta!</strong> Las siguientes tarjetas han excedido su límite:<ul class="mb-0 mt-1">${items}</ul></div>`;
    }
    if (data.cards_near_limit.length > 0) {
        const items = data.cards_near_limit.map(a => `<li>${escapeHtml(a.name)} - ${a.percent}% usado (${a.currency})</li>`).join('');
        html += `<div class="alert alert-warning mb-3"><i class="fas fa-exclamation-triangle"></i> <strong>¡Atención!</strong> Las siguientes tarjetas están cerca de su límite:<ul class="mb-0 mt-1">${items}</ul></div>`;
    }
    document.getElementById('limit-alerts').innerHTML = html;
}

// ============================================
// RENDER: tarjeta individual (con botón Historial)
// ============================================
function renderCard(card) {
    const pctUsd = card.credit_limit_usd ? Math.min(100, (card.balance_usd / card.credit_limit_usd) * 100) : 0;
    const pctDop = card.credit_limit_dop ? Math.min(100, (card.balance_dop / card.credit_limit_dop) * 100) : 0;
    const colorUsd = pctUsd > 90 ? 'danger' : pctUsd > 70 ? 'warning' : 'success';
    const colorDop = pctDop > 90 ? 'danger' : pctDop > 70 ? 'warning' : 'success';
    const borderColor = card.type === 'credit_card' ? 'danger' : 'info';
    const typeLabel   = card.type === 'credit_card' ? 'Crédito' : 'Débito';
    const canDelete   = parseFloat(card.balance_usd) === 0 && parseFloat(card.balance_dop) === 0;

    const usdLimitHtml = card.credit_limit_usd
        ? `<div class="progress mt-1" style="height:6px;"><div class="progress-bar bg-${colorUsd}" style="width:${pctUsd}%"></div></div>
           <div class="d-flex justify-content-between mt-1">
               <small class="text-muted">Límite: $${fmt(card.credit_limit_usd)}</small>
               <small class="text-muted">Disponible: $${fmt(card.credit_limit_usd - card.balance_usd)}</small>
           </div>`
        : '<small class="text-muted">Sin límite definido</small>';

    const dopLimitHtml = card.credit_limit_dop
        ? `<div class="progress mt-1" style="height:6px;"><div class="progress-bar bg-${colorDop}" style="width:${pctDop}%"></div></div>
           <div class="d-flex justify-content-between mt-1">
               <small class="text-muted">Límite: RD$ ${fmt(card.credit_limit_dop)}</small>
               <small class="text-muted">Disponible: RD$ ${fmt(card.credit_limit_dop - card.balance_dop)}</small>
           </div>`
        : '<small class="text-muted">Sin límite definido</small>';

    const deleteBtn = canDelete
        ? `<button class="btn btn-sm btn-outline-danger" onclick="confirmDelete(${card.id}, '${escapeHtml(card.name)}')">
               <i class="fas fa-trash"></i>
           </button>`
        : '';

    return `
        <div class="col-md-6 mb-3" id="card-item-${card.id}">
            <div class="card h-100 border-${borderColor} shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start flex-wrap">
                        <div>
                            <h6 class="mb-1">${escapeHtml(card.name)}</h6>
                            <span class="badge bg-secondary">${typeLabel}</span>
                        </div>
                        <div class="btn-group mt-2 mt-sm-0">
                            <button class="btn btn-sm btn-outline-primary" onclick="openExpenseModal(${card.id})">
                                <i class="fas fa-shopping-cart"></i> Gasto
                            </button>
                            <button class="btn btn-sm btn-outline-success" onclick="openPayModal(${card.id})">
                                <i class="fas fa-money-bill-wave"></i> Pagar
                            </button>
                            <button class="btn btn-sm btn-outline-info" onclick="openHistoryModal(${card.id}, '${escapeHtml(card.name)}')">
                                <i class="fas fa-history"></i> Historial
                            </button>
                            ${deleteBtn}
                        </div>
                    </div>
                    <hr>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <small class="text-muted">Balance USD</small>
                            <small class="fw-bold ${parseFloat(card.balance_usd) > 0 ? 'text-danger' : 'text-success'}">
                                $${fmt(card.balance_usd)}
                            </small>
                        </div>
                        ${usdLimitHtml}
                    </div>
                    <div class="mb-2">
                        <div class="d-flex justify-content-between">
                            <small class="text-muted">Balance DOP</small>
                            <small class="fw-bold ${parseFloat(card.balance_dop) > 0 ? 'text-danger' : 'text-success'}">
                                RD$ ${fmt(card.balance_dop)}
                            </small>
                        </div>
                        ${dopLimitHtml}
                    </div>
                </div>
            </div>
        </div>
    `;
}

// ============================================
// RENDER: estado vacío
// ============================================
function renderEmptyCards() {
    return `
        <div class="col-12 text-center text-muted py-5" id="cards-empty">
            <i class="fas fa-credit-card fa-3x mb-3"></i>
            <p>No tienes tarjetas registradas</p>
            <button class="btn btn-primary btn-sm"
                    data-bs-toggle="modal" data-bs-target="#createCardModal">
                Crear primera tarjeta
            </button>
        </div>
    `;
}

// ============================================
// CARGAR DATOS INICIALES
// ============================================
function loadCards() {
    ajaxPost({ action: 'get_cards' })
        .then(data => {
            if (!data.success) {
                showError(data.message, data.full_message);
                document.getElementById('cards-list').innerHTML = renderEmptyCards();
                return;
            }

            _usdRate     = data.usd_rate;
            _categories  = data.categories;
            _dopAccounts = data.dop_accounts;

            renderSummary(data);
            renderAlerts(data);

            const list = document.getElementById('cards-list');
            list.innerHTML = data.cards.length === 0
                ? renderEmptyCards()
                : data.cards.map(renderCard).join('');
        })
        .catch(err => {
            showError('No se pudieron cargar las tarjetas. Revisa la consola.', 'Red error: ' + err.message);
            document.getElementById('cards-list').innerHTML = renderEmptyCards();
        });
}

// ============================================
// CREAR TARJETA
// ============================================
document.getElementById('btn-create-card').addEventListener('click', function () {
    const name     = document.getElementById('card-name').value.trim();
    const type     = document.getElementById('card-type').value;
    const limitUsd = document.getElementById('card-limit-usd').value;
    const limitDop = document.getElementById('card-limit-dop').value;

    if (!name) { showError('El nombre de la tarjeta es obligatorio.'); return; }

    setLoading('btn-create-card', 'btn-create-card-text', 'btn-create-card-spinner', true);

    ajaxPost({ action: 'create_card', name, type, credit_limit_usd: limitUsd, credit_limit_dop: limitDop })
        .then(data => {
            if (!data.success) { showError(data.message, data.full_message); return; }

            showSuccess(data.message);
            bootstrap.Modal.getInstance(document.getElementById('createCardModal')).hide();
            document.getElementById('card-name').value = '';
            document.getElementById('card-limit-usd').value = '';
            document.getElementById('card-limit-dop').value = '';

            const empty = document.getElementById('cards-empty');
            if (empty) empty.remove();
            document.getElementById('cards-list').insertAdjacentHTML('afterbegin', renderCard(data.card));
            loadCards();
        })
        .catch(err => showError('Error de red al crear la tarjeta.', err.message))
        .finally(() => setLoading('btn-create-card', 'btn-create-card-text', 'btn-create-card-spinner', false));
});

// ============================================
// MODAL GASTO
// ============================================
function openExpenseModal(cardId) {
    document.getElementById('expense-card-id').value = cardId;
    document.getElementById('expense-amount').value  = '';
    document.getElementById('expense-description').value = '';
    document.getElementById('expense-date').value = localDateStr();

    const sel = document.getElementById('expense-category');
    sel.innerHTML = '<option value="">Seleccionar categoría</option>' +
        _categories.map(c => `<option value="${c.id}">${escapeHtml(c.name)}</option>`).join('');

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

    setLoading('btn-add-expense', 'btn-expense-text', 'btn-expense-spinner', true);

    ajaxPost({ action: 'add_card_expense', card_id: cardId, currency, amount, category_id: categoryId, date, description })
        .then(data => {
            if (!data.success) { showError(data.message, data.full_message); return; }
            showSuccess(data.message);
            bootstrap.Modal.getInstance(document.getElementById('expenseModal')).hide();
            loadCards();
        })
        .catch(err => showError('Error de red al registrar el gasto.', err.message))
        .finally(() => setLoading('btn-add-expense', 'btn-expense-text', 'btn-expense-spinner', false));
});

// ============================================
// MODAL PAGO
// ============================================
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

    const sel = document.getElementById('pay-account');
    sel.innerHTML = '<option value="">Seleccionar cuenta</option>';
    const noMsg = document.getElementById('no-dop-accounts-msg');
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
    if (!this.checked) { document.getElementById('pay-dop').value = '0'; updateTotalPreview(); }
});

document.getElementById('enable-usd-payment').addEventListener('change', function () {
    document.getElementById('usd-payment-section').classList.toggle('d-none', !this.checked);
    if (!this.checked) {
        document.getElementById('pay-usd').value     = '0';
        document.getElementById('dop-for-usd').value = '0';
        updateTotalPreview();
    }
});

['pay-dop', 'dop-for-usd'].forEach(id =>
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
    if (parseFloat(payUsd) <= 0 && parseFloat(payDop) <= 0) {
        showError('Debes ingresar al menos un monto a pagar.'); return;
    }

    setLoading('btn-pay-card', 'btn-pay-text', 'btn-pay-spinner', true);

    ajaxPost({ action: 'pay_card', card_id: cardId, account_id: accountId, pay_usd: payUsd, pay_dop: payDop, dop_amount_for_usd: dopForUsd, payment_date: paymentDate })
        .then(data => {
            if (!data.success) { showError(data.message, data.full_message); return; }

            bootstrap.Modal.getInstance(document.getElementById('payModal')).hide();
            Swal.fire({
                title: '¡Pago exitoso!',
                html: data.summary.join('<br>'),
                icon: 'success',
            });
            loadCards();
        })
        .catch(err => showError('Error de red al procesar el pago.', err.message))
        .finally(() => setLoading('btn-pay-card', 'btn-pay-text', 'btn-pay-spinner', false));
});

// ============================================
// ELIMINAR TARJETA
// ============================================
function confirmDelete(cardId, cardName) {
    Swal.fire({
        title: '¿Eliminar tarjeta?',
        text: `¿Eliminar "${cardName}"? Solo se puede si no tiene deuda.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonText: 'Cancelar',
        confirmButtonText: 'Sí, eliminar',
    }).then(result => {
        if (!result.isConfirmed) return;

        ajaxPost({ action: 'delete_card', card_id: cardId })
            .then(data => {
                if (!data.success) { showError(data.message, data.full_message); return; }
                showSuccess(data.message);
                const el = document.getElementById(`card-item-${data.card_id}`);
                if (el) el.remove();
                loadCards();
            })
            .catch(err => showError('Error de red al eliminar la tarjeta.', err.message));
    });
}

// ============================================
// HISTORIAL DE TRANSACCIONES — estado de paginación
// ============================================
let _historyTransactions = [];
let _historyPage         = 1;
const HISTORY_PAGE_SIZE  = 10;

function openHistoryModal(cardId, cardName) {
    document.getElementById('history-card-id').value       = cardId;
    document.getElementById('history-card-name').textContent = cardName;

    // Limpiar filtros, tabla y paginación
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

    const tbody    = document.getElementById('history-table-body');
    const emptyMsg = document.getElementById('history-empty-message');
    const pagination = document.getElementById('history-pagination');

    tbody.innerHTML = `<tr><td colspan="7" class="text-center py-3">
        <div class="spinner-border spinner-border-sm text-primary"></div> Cargando...
    </td></tr>`;
    emptyMsg.classList.add('d-none');
    pagination.innerHTML = '';

    const dateFrom = document.getElementById('filter-date-from').value;
    const dateTo   = document.getElementById('filter-date-to').value;
    const type     = document.getElementById('filter-type').value;

    ajaxPost({
        action:    'get_card_transactions',
        card_id:   cardId,
        date_from: dateFrom,
        date_to:   dateTo,
        type:      type,
    }).then(data => {
        if (!data.success) {
            showError(data.message, data.full_message);
            tbody.innerHTML = `<tr><td colspan="7" class="text-center text-danger py-3">
                Error al cargar transacciones.</td></tr>`;
            return;
        }
        _historyTransactions = data.transactions || [];
        renderHistoryPage();
    }).catch(err => {
        showError('Error de conexión', err.message);
        tbody.innerHTML = `<tr><td colspan="7" class="text-center text-danger py-3">
            Error de red.</td></tr>`;
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
    const start      = (_historyPage - 1) * HISTORY_PAGE_SIZE;
    const end        = Math.min(start + HISTORY_PAGE_SIZE, _historyTransactions.length);
    const pageItems  = _historyTransactions.slice(start, end);

    // Fecha de hoy a medianoche para comparación justa
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    let html = '';
    pageItems.forEach(t => {
        const fecha = new Date(t.date + 'T00:00:00').toLocaleDateString('es-DO', {
            year: 'numeric', month: 'short', day: 'numeric'
        });
        const desc      = escapeHtml(t.description || (t.row_type === 'payment' ? 'Pago de tarjeta' : 'Gasto'));
        const categoria = t.category_name ? escapeHtml(t.category_name) : '—';
        const tipoBadge = t.row_type === 'expense'
            ? '<span class="badge bg-danger">Gasto</span>'
            : '<span class="badge bg-success">Pago</span>';
        const monto       = parseFloat(t.amount).toLocaleString('es-DO', { minimumFractionDigits: 2 });
        const signo       = t.row_type === 'expense' ? '+' : '-';
        const montoClass  = t.row_type === 'expense' ? 'text-danger fw-semibold' : 'text-success fw-semibold';
        const balAfter    = parseFloat(t.balance_after || 0).toLocaleString('es-DO', { minimumFractionDigits: 2 });
        const symbol      = t.currency_symbol || '';

        // Verificar si puede eliminarse (≤ 3 días)
        const txDate   = new Date(t.date + 'T00:00:00');
        const diffDays = Math.round((today - txDate) / 86400000);
        const canDelete = diffDays >= -1 && diffDays <= 3;

        const deleteBtn = canDelete
            ? `<button
                 class="btn btn-outline-danger btn-sm py-0 px-2"
                 style="font-size:.75rem; line-height:1.6;"
                 title="Eliminar y revertir balance"
                 onclick="confirmDeleteTransaction(${t.id}, ${cardId})">
                   <i class="fas fa-trash-alt"></i>
               </button>`
            : `<span class="text-muted" title="Solo eliminable en los primeros 3 días">
                   <i class="fas fa-lock" style="font-size:.75rem;"></i>
               </span>`;

        html += `
            <tr>
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

    // — Paginación —
    if (totalPages <= 1) {
        pagination.innerHTML = `<p class="text-center text-muted mb-0" style="font-size:.8rem;">
            Mostrando ${_historyTransactions.length} transacción(es)</p>`;
        return;
    }

    let pHtml = `<div class="d-flex flex-column align-items-center gap-1">
        <ul class="pagination pagination-sm mb-0 flex-wrap justify-content-center">`;

    pHtml += `<li class="page-item ${_historyPage === 1 ? 'disabled' : ''}">
        <button class="page-link" onclick="changeHistoryPage(${_historyPage - 1})">
            <i class="fas fa-chevron-left"></i></button></li>`;

    for (let i = 1; i <= totalPages; i++) {
        const nearCurrent = Math.abs(i - _historyPage) <= 1;
        const isEdge      = i === 1 || i === totalPages;
        if (!nearCurrent && !isEdge) {
            // Ellipsis
            if (i === 2 || i === totalPages - 1) {
                pHtml += `<li class="page-item disabled"><span class="page-link">…</span></li>`;
            }
            continue;
        }
        pHtml += `<li class="page-item ${i === _historyPage ? 'active' : ''}">
            <button class="page-link" onclick="changeHistoryPage(${i})">${i}</button></li>`;
    }

    pHtml += `<li class="page-item ${_historyPage === totalPages ? 'disabled' : ''}">
        <button class="page-link" onclick="changeHistoryPage(${_historyPage + 1})">
            <i class="fas fa-chevron-right"></i></button></li>`;

    pHtml += `</ul>
        <small class="text-muted">
            Mostrando ${start + 1}–${end} de ${_historyTransactions.length} transacciones
        </small>
    </div>`;

    pagination.innerHTML = pHtml;
}

function changeHistoryPage(page) {
    const totalPages = Math.ceil(_historyTransactions.length / HISTORY_PAGE_SIZE);
    if (page < 1 || page > totalPages) return;
    _historyPage = page;
    renderHistoryPage();
    // Hacer scroll al tope de la tabla del modal
    document.getElementById('history-table-body').closest('.table-responsive').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function confirmDeleteTransaction(txId, cardId) {
    Swal.fire({
        title: '¿Eliminar transacción?',
        html: `Esta acción <strong>revertirá los cambios en los balances</strong> de la tarjeta y, si aplica, de la cuenta de origen.<br><br>¿Deseas continuar?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonText: 'Cancelar',
        confirmButtonText: 'Sí, eliminar y revertir',
    }).then(result => {
        if (!result.isConfirmed) return;

        ajaxPost({ action: 'delete_card_transaction', transaction_id: txId, card_id: cardId })
            .then(data => {
                if (!data.success) { showError(data.message, data.full_message); return; }
                showSuccess(data.message);

                // Quitar del array local y re-renderizar sin re-fetch
                _historyTransactions = _historyTransactions.filter(t => t.id != txId);
                const totalPages = Math.ceil(_historyTransactions.length / HISTORY_PAGE_SIZE);
                if (_historyPage > totalPages && totalPages > 0) _historyPage = totalPages;
                renderHistoryPage();

                // Actualizar resumen y tarjetas en el fondo
                loadCards();
            })
            .catch(err => showError('Error de red al eliminar la transacción.', err.message));
    });
}

document.getElementById('btn-apply-filters').addEventListener('click', function () {
    const cardId = document.getElementById('history-card-id').value;
    if (cardId) loadCardTransactions(cardId);
});

// Fecha local correcta (no UTC) para los inputs date
function localDateStr() {
    const now = new Date();
    const y   = now.getFullYear();
    const m   = String(now.getMonth() + 1).padStart(2, '0');
    const d   = String(now.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
}

// ============================================
// INICIO
// ============================================
loadCards();
</script>
</body>
</html>