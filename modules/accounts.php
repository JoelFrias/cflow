<?php
// modules/accounts.php - Módulo de gestión de cuentas (bancarias, efectivo, wallets, etc.)

// Obtener monedas disponibles para el <select> del modal
$stmt = $pdo->query("SELECT * FROM currencies ORDER BY code");
$currencies = $stmt->fetchAll();
?>

<div class="card">
    <div class="card-header d-flex justify-content-between">
        <h5><i class="fas fa-wallet"></i> Mis Cuentas</h5>
        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createAccountModal">
            <i class="fas fa-plus"></i> Nueva Cuenta
        </button>
    </div>
    <div class="card-body">

        <!-- Balance Total -->
        <div class="alert alert-info" id="balance-total">
            <strong>Balance Total Consolidado:</strong>
            <span id="balance-total-value">Cargando...</span>
        </div>

        <!-- Lista de Cuentas -->
        <div class="row" id="accounts-list">
            <div class="col-12 text-center py-3">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- ============================================ -->
<!-- MODAL CREAR CUENTA -->
<!-- ============================================ -->
<div class="modal fade" id="createAccountModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus"></i> Nueva Cuenta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label>Nombre de la cuenta</label>
                    <input type="text" id="acc-name" class="form-control"
                           placeholder="Ej: Banco Popular, Efectivo Casa, PayPal" required>
                </div>
                <div class="mb-3">
                    <label>Tipo de cuenta</label>
                    <select id="acc-type" class="form-control" required>
                        <option value="cash">Efectivo</option>
                        <option value="bank">Cuenta Bancaria</option>
                        <option value="wallet">Wallet Digital (PayPal, UPI, etc.)</option>
                    </select>
                    <small class="text-muted">
                        Las tarjetas de crédito/débito se crean en el módulo "Tarjetas"
                    </small>
                </div>
                <div class="mb-3">
                    <label>Moneda</label>
                    <select id="acc-currency" class="form-control" required>
                        <?php foreach($currencies as $cur): ?>
                        <option value="<?= htmlspecialchars($cur['code']) ?>">
                            <?= htmlspecialchars($cur['name']) ?> (<?= htmlspecialchars($cur['symbol']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label>Saldo inicial</label>
                    <input type="number" step="0.01" id="acc-balance" class="form-control" value="0">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btn-create-account">
                    <span id="btn-create-text">Crear Cuenta</span>
                    <span id="btn-create-spinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- MODAL EDITAR CUENTA -->
<!-- ============================================ -->
<div class="modal fade" id="editAccountModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit"></i> Editar Cuenta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="edit-account-id">
                <div class="mb-3">
                    <label>Nombre de la cuenta</label>
                    <input type="text" id="edit-acc-name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Tipo de cuenta</label>
                    <select id="edit-acc-type" class="form-control" required>
                        <option value="cash">Efectivo</option>
                        <option value="bank">Cuenta Bancaria</option>
                        <option value="wallet">Wallet Digital (PayPal, UPI, etc.)</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label>Moneda</label>
                    <select id="edit-acc-currency" class="form-control" required>
                        <?php foreach($currencies as $cur): ?>
                        <option value="<?= htmlspecialchars($cur['code']) ?>">
                            <?= htmlspecialchars($cur['name']) ?> (<?= htmlspecialchars($cur['symbol']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btn-update-account">
                    <span id="btn-update-text">Guardar Cambios</span>
                    <span id="btn-update-spinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- MODAL HISTORIAL DE TRANSACCIONES DE CUENTA  -->
<!-- ============================================ -->
<div class="modal fade" id="accountHistoryModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-history me-2"></i>
                    Historial de <span id="acc-history-name">Cuenta</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="acc-history-id">

                <!-- Filtros -->
                <div class="row g-2 mb-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Fecha desde</label>
                        <input type="date" id="acc-filter-date-from" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Fecha hasta</label>
                        <input type="date" id="acc-filter-date-to" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Tipo de transacción</label>
                        <select id="acc-filter-type" class="form-select">
                            <option value="">Todos</option>
                            <option value="income">Ingreso</option>
                            <option value="expense">Gasto / Egreso</option>
                            <option value="transfer">Transferencia</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-primary w-100" id="btn-acc-apply-filters">
                            <i class="fas fa-filter me-1"></i> Aplicar Filtros
                        </button>
                    </div>
                </div>

                <!-- Resumen rápido -->
                <div class="row g-2 mb-3" id="acc-history-summary" style="display:none!important;">
                    <div class="col-md-4">
                        <div class="card border-success">
                            <div class="card-body py-2 px-3">
                                <small class="text-muted d-block">Total Ingresos</small>
                                <strong class="text-success" id="acc-summary-income">—</strong>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-danger">
                            <div class="card-body py-2 px-3">
                                <small class="text-muted d-block">Total Egresos</small>
                                <strong class="text-danger" id="acc-summary-expense">—</strong>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-info">
                            <div class="card-body py-2 px-3">
                                <small class="text-muted d-block">Neto del período</small>
                                <strong id="acc-summary-net">—</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabla -->
                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Fecha</th>
                                <th>Descripción</th>
                                <th>Categoría</th>
                                <th>Tipo</th>
                                <th class="text-end">Monto</th>
                            </tr>
                        </thead>
                        <tbody id="acc-history-tbody">
                            <tr>
                                <td colspan="5" class="text-center py-4">
                                    <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                                    Cargando transacciones...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div id="acc-pagination" class="mt-3"></div>
                </div>

                <!-- Estado vacío -->
                <div id="acc-history-empty" class="text-center text-muted py-4 d-none">
                    <i class="fas fa-receipt fa-2x mb-2 opacity-50 d-block"></i>
                    No hay transacciones que coincidan con los filtros seleccionados.
                </div>

                <!-- Contador de resultados -->
                <div id="acc-history-count" class="text-muted small text-end mt-1 d-none"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- JAVASCRIPT -->
<!-- ============================================ -->
<script>
const ACCOUNTS_AJAX_URL = 'ajax/accounts.php';

const TYPE_NAMES = {
    cash:   'Efectivo',
    bank:   'Cuenta Bancaria',
    wallet: 'Wallet Digital',
};

// ============================================
// HELPER: mostrar error con SweetAlert2
// ============================================
function showError(message, fullMessage = null) {
    console.error('[Accounts Error]', fullMessage || message);
    Swal.fire({
        toast: true,
        position: 'top-start',
        icon: 'error',
        title: message,
        showConfirmButton: false,
        timer: 4000,
        timerProgressBar: true,
    });
}

// ============================================
// HELPER: mostrar éxito
// ============================================
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

// ============================================
// RENDER: pintar tarjeta de una cuenta (con botones de editar/eliminar/historial)
// ============================================
function renderAccountCard(acc) {
    const typeName = TYPE_NAMES[acc.type] || acc.type;
    const balance  = parseFloat(acc.balance).toLocaleString('es-DO', { minimumFractionDigits: 2 });

    return `
        <div class="col-md-4 mb-3" id="account-card-${acc.id}">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <h6 class="mb-2">${escapeHtml(acc.name)}</h6>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-info btn-history-account"
                                    data-id="${acc.id}"
                                    data-name="${escapeHtml(acc.name)}"
                                    title="Ver historial">
                                <i class="fas fa-history"></i>
                            </button>
                            <button class="btn btn-outline-secondary btn-edit-account" 
                                    data-account='${JSON.stringify(acc).replace(/'/g, "&#39;")}'
                                    title="Editar cuenta">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-outline-danger btn-delete-account" 
                                    data-id="${acc.id}" 
                                    data-name="${escapeHtml(acc.name)}"
                                    title="Eliminar cuenta">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <p class="mb-1">
                        <span class="badge bg-secondary">${typeName}</span>
                        <span class="badge bg-info">${escapeHtml(acc.currency_code)}</span>
                    </p>
                    <h4 class="mb-0">${escapeHtml(acc.symbol)} ${balance}</h4>
                </div>
            </div>
        </div>
    `;
}

// ============================================
// RENDER: estado vacío
// ============================================
function renderEmptyState() {
    return `
        <div class="col-12 text-center text-muted py-5" id="accounts-empty">
            <i class="fas fa-wallet fa-3x mb-3"></i>
            <p>No tienes cuentas registradas</p>
            <button class="btn btn-primary btn-sm"
                    data-bs-toggle="modal" data-bs-target="#createAccountModal">
                Crear primera cuenta
            </button>
        </div>
    `;
}

// ============================================
// CARGAR CUENTAS
// ============================================
function loadAccounts() {
    fetch(ACCOUNTS_AJAX_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=get_accounts',
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) {
            showError(data.message, data.full_message);
            document.getElementById('accounts-list').innerHTML = renderEmptyState();
            return;
        }

        // Balance total
        const totalDop = parseFloat(data.total_dop).toLocaleString('es-DO', { minimumFractionDigits: 2 });
        document.getElementById('balance-total-value').textContent = ` RD$ ${totalDop}`;

        // Tarjetas
        const list = document.getElementById('accounts-list');
        if (data.accounts.length === 0) {
            list.innerHTML = renderEmptyState();
        } else {
            list.innerHTML = data.accounts.map(renderAccountCard).join('');
            attachCardEvents();
        }
    })
    .catch(err => {
        showError('No se pudieron cargar las cuentas. Revisa la consola.', err.message);
        document.getElementById('accounts-list').innerHTML = renderEmptyState();
    });
}

// ============================================
// ASIGNAR EVENTOS A BOTONES
// ============================================
function attachCardEvents() {
    // Botones Historial
    document.querySelectorAll('.btn-history-account').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            openAccountHistoryModal(this.dataset.id, this.dataset.name);
        });
    });

    // Botones Editar
    document.querySelectorAll('.btn-edit-account').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const accountData = JSON.parse(this.dataset.account);
            openEditModal(accountData);
        });
    });

    // Botones Eliminar
    document.querySelectorAll('.btn-delete-account').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            deleteAccount(this.dataset.id, this.dataset.name);
        });
    });
}

// ============================================
// HISTORIAL DE CUENTA — estado de paginación
// ============================================
let _accountTransactions = [];
let _accountPage         = 1;
const ACCOUNT_PAGE_SIZE  = 10;

function openAccountHistoryModal(accountId, accountName) {
    document.getElementById('acc-history-id').value        = accountId;
    document.getElementById('acc-history-name').textContent = accountName;

    // Limpiar filtros, paginación y resumen
    document.getElementById('acc-filter-date-from').value = '';
    document.getElementById('acc-filter-date-to').value   = '';
    document.getElementById('acc-filter-type').value      = '';
    document.getElementById('acc-pagination').innerHTML   = '';
    document.getElementById('acc-history-summary').style.display = 'none';
    _accountTransactions = [];
    _accountPage = 1;

    new bootstrap.Modal(document.getElementById('accountHistoryModal')).show();
    loadAccountTransactions(accountId);
}

function loadAccountTransactions(accountId, resetPage = true) {
    if (resetPage) _accountPage = 1;

    const tbody     = document.getElementById('acc-history-tbody');
    const emptyEl   = document.getElementById('acc-history-empty');
    const countEl   = document.getElementById('acc-history-count');
    const summaryEl = document.getElementById('acc-history-summary');
    const paginEl   = document.getElementById('acc-pagination');

    tbody.innerHTML = `
        <tr>
            <td colspan="5" class="text-center py-4">
                <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                Cargando transacciones...
            </td>
        </tr>`;
    emptyEl.classList.add('d-none');
    countEl.classList.add('d-none');
    summaryEl.style.display = 'none';
    paginEl.innerHTML = '';

    const body = {
        action:     'get_account_transactions',
        account_id: accountId,
        date_from:  document.getElementById('acc-filter-date-from').value,
        date_to:    document.getElementById('acc-filter-date-to').value,
        type:       document.getElementById('acc-filter-type').value,
    };

    fetch(ACCOUNTS_AJAX_URL, {
        method:  'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body:    new URLSearchParams(body).toString(),
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) {
            showError(data.message, data.full_message);
            tbody.innerHTML = `<tr><td colspan="5" class="text-center text-danger py-3">Error al cargar transacciones.</td></tr>`;
            return;
        }

        _accountTransactions = data.transactions || [];
        _accCurrencySymbol   = data.currency_symbol || '';

        // Calcular resumen con todos los datos
        let totalIncome = 0, totalExpense = 0;
        _accountTransactions.forEach(t => {
            const amt = parseFloat(t.amount) || 0;
            if (t.type === 'income')  totalIncome  += amt;
            if (t.type === 'expense') totalExpense += amt;
        });
        const net = totalIncome - totalExpense;
        const sym = _accCurrencySymbol;

        if (_accountTransactions.length > 0) {
            document.getElementById('acc-summary-income').textContent  = `${sym} ${totalIncome.toLocaleString('es-DO', { minimumFractionDigits: 2 })}`;
            document.getElementById('acc-summary-expense').textContent = `${sym} ${totalExpense.toLocaleString('es-DO', { minimumFractionDigits: 2 })}`;
            const netEl    = document.getElementById('acc-summary-net');
            netEl.textContent = `${sym} ${Math.abs(net).toLocaleString('es-DO', { minimumFractionDigits: 2 })}`;
            netEl.className   = net >= 0 ? 'text-success fw-bold' : 'text-danger fw-bold';
            summaryEl.style.removeProperty('display');
            summaryEl.style.display = 'flex';
            summaryEl.classList.remove('d-none');
        }

        renderAccountPage(data.currency_symbol);
    })
    .catch(err => {
        showError('Error de conexión', err.message);
        tbody.innerHTML = `<tr><td colspan="5" class="text-center text-danger py-3">Error de red.</td></tr>`;
    });
}

// Variable para conservar el símbolo entre renders de página
let _accCurrencySymbol = '';

function renderAccountPage(currencySymbol) {
    const tbody   = document.getElementById('acc-history-tbody');
    const emptyEl = document.getElementById('acc-history-empty');
    const countEl = document.getElementById('acc-history-count');
    const paginEl = document.getElementById('acc-pagination');
    const sym     = currencySymbol ?? _accCurrencySymbol;

    if (_accountTransactions.length === 0) {
        tbody.innerHTML = '';
        emptyEl.classList.remove('d-none');
        countEl.classList.add('d-none');
        paginEl.innerHTML = '';
        return;
    }

    emptyEl.classList.add('d-none');

    const total      = _accountTransactions.length;
    const totalPages = Math.ceil(total / ACCOUNT_PAGE_SIZE);
    const start      = (_accountPage - 1) * ACCOUNT_PAGE_SIZE;
    const end        = Math.min(start + ACCOUNT_PAGE_SIZE, total);
    const pageItems  = _accountTransactions.slice(start, end);

    let html = '';
    pageItems.forEach(t => {
        const fecha = t.date
            ? new Date(t.date + 'T00:00:00').toLocaleDateString('es-DO', { year: 'numeric', month: 'short', day: 'numeric' })
            : '—';

        const desc = escapeHtml(t.description || '—');
        const cat  = escapeHtml(t.category_name || '—');
        const tSym = escapeHtml(t.currency_symbol || sym);

        let tipoBadge = '', montoClass = '', signo = '';
        switch (t.type) {
            case 'income':
                tipoBadge  = '<span class="badge bg-success">Ingreso</span>';
                montoClass = 'text-success fw-semibold';
                signo      = '+';
                break;
            case 'expense':
                tipoBadge  = '<span class="badge bg-danger">Gasto</span>';
                montoClass = 'text-danger fw-semibold';
                signo      = '-';
                break;
            case 'transfer':
                tipoBadge  = '<span class="badge bg-warning text-dark">Transferencia</span>';
                montoClass = 'text-warning fw-semibold';
                signo      = '';
                break;
            default:
                tipoBadge  = `<span class="badge bg-secondary">${escapeHtml(t.type)}</span>`;
                montoClass = '';
                signo      = '';
        }

        const monto = parseFloat(t.amount).toLocaleString('es-DO', { minimumFractionDigits: 2 });

        html += `
            <tr>
                <td class="text-nowrap">${fecha}</td>
                <td>${desc}</td>
                <td>${cat}</td>
                <td>${tipoBadge}</td>
                <td class="text-end ${montoClass}">${signo} ${tSym} ${monto}</td>
            </tr>`;
    });
    tbody.innerHTML = html;

    // Contador
    countEl.textContent = `Mostrando ${start + 1}–${end} de ${total} transacción${total !== 1 ? 'es' : ''}`;
    countEl.classList.remove('d-none');

    // Paginación
    if (totalPages <= 1) {
        paginEl.innerHTML = '';
        return;
    }

    let pHtml = `<div class="d-flex flex-column align-items-center gap-1">
        <ul class="pagination pagination-sm mb-0 flex-wrap justify-content-center">`;

    pHtml += `<li class="page-item ${_accountPage === 1 ? 'disabled' : ''}">
        <button class="page-link" onclick="changeAccountPage(${_accountPage - 1})">
            <i class="fas fa-chevron-left"></i></button></li>`;

    for (let i = 1; i <= totalPages; i++) {
        const nearCurrent = Math.abs(i - _accountPage) <= 1;
        const isEdge      = i === 1 || i === totalPages;

        if (!nearCurrent && !isEdge) {
            if (i === 2 || i === totalPages - 1) {
                pHtml += `<li class="page-item disabled"><span class="page-link">…</span></li>`;
            }
            continue;
        }
        pHtml += `<li class="page-item ${i === _accountPage ? 'active' : ''}">
            <button class="page-link" onclick="changeAccountPage(${i})">${i}</button></li>`;
    }

    pHtml += `<li class="page-item ${_accountPage === totalPages ? 'disabled' : ''}">
        <button class="page-link" onclick="changeAccountPage(${_accountPage + 1})">
            <i class="fas fa-chevron-right"></i></button></li>`;

    pHtml += `</ul>
        <small class="text-muted">${total} transacción${total !== 1 ? 'es' : ''} en total</small>
    </div>`;

    paginEl.innerHTML = pHtml;
}

function changeAccountPage(page) {
    const totalPages = Math.ceil(_accountTransactions.length / ACCOUNT_PAGE_SIZE);
    if (page < 1 || page > totalPages) return;
    _accountPage = page;
    renderAccountPage();
    document.getElementById('acc-history-tbody')
        .closest('.table-responsive')
        .scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

// Botón aplicar filtros
document.getElementById('btn-acc-apply-filters').addEventListener('click', function () {
    const accountId = document.getElementById('acc-history-id').value;
    if (accountId) loadAccountTransactions(accountId);
});

// ============================================
// ABRIR MODAL DE EDICIÓN CON DATOS PRELLENADOS
// ============================================
function openEditModal(account) {
    document.getElementById('edit-account-id').value      = account.id;
    document.getElementById('edit-acc-name').value        = account.name;
    document.getElementById('edit-acc-type').value        = account.type;
    document.getElementById('edit-acc-currency').value    = account.currency_code;

    const editModal = new bootstrap.Modal(document.getElementById('editAccountModal'));
    editModal.show();
}

// ============================================
// ELIMINAR CUENTA (con confirmación)
// ============================================
function deleteAccount(accountId, accountName) {
    Swal.fire({
        title: '¿Eliminar cuenta?',
        html: `Estás seguro que deseas eliminar <strong>${escapeHtml(accountName)}</strong>?<br>Esta acción no se puede deshacer.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            const body = new URLSearchParams({
                action: 'delete_account',
                account_id: accountId
            });

            fetch(ACCOUNTS_AJAX_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body.toString(),
            })
            .then(r => r.json())
            .then(data => {
                if (!data.success) {
                    showError(data.message, data.full_message);
                    return;
                }
                showSuccess(data.message);
                const card = document.getElementById(`account-card-${accountId}`);
                if (card) card.remove();
                if (document.querySelectorAll('#accounts-list .col-md-4').length === 0) {
                    document.getElementById('accounts-list').innerHTML = renderEmptyState();
                }
                loadAccounts();
            })
            .catch(err => {
                showError('Error al eliminar la cuenta.', err.message);
            });
        }
    });
}

// ============================================
// GUARDAR CAMBIOS DE EDICIÓN
// ============================================
document.getElementById('btn-update-account').addEventListener('click', function() {
    const accountId = document.getElementById('edit-account-id').value;
    const name      = document.getElementById('edit-acc-name').value.trim();
    const type      = document.getElementById('edit-acc-type').value;
    const currency  = document.getElementById('edit-acc-currency').value;

    if (!name) {
        showError('El nombre de la cuenta es obligatorio.');
        return;
    }

    const btnText    = document.getElementById('btn-update-text');
    const btnSpinner = document.getElementById('btn-update-spinner');
    btnText.textContent = 'Guardando...';
    btnSpinner.classList.remove('d-none');
    this.disabled = true;

    const body = new URLSearchParams({
        action:        'update_account',
        account_id:    accountId,
        name:          name,
        type:          type,
        currency_code: currency
    });

    fetch(ACCOUNTS_AJAX_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body.toString(),
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) {
            showError(data.message, data.full_message);
            return;
        }

        showSuccess(data.message);
        bootstrap.Modal.getInstance(document.getElementById('editAccountModal')).hide();

        const card = document.getElementById(`account-card-${accountId}`);
        if (card) {
            card.outerHTML = renderAccountCard(data.account);
            attachCardEvents();
        }

        loadAccounts();
    })
    .catch(err => {
        showError('No se pudo actualizar la cuenta.', err.message);
    })
    .finally(() => {
        btnText.textContent = 'Guardar Cambios';
        btnSpinner.classList.add('d-none');
        document.getElementById('btn-update-account').disabled = false;
    });
});

// ============================================
// CREAR CUENTA
// ============================================
document.getElementById('btn-create-account').addEventListener('click', function () {
    const name     = document.getElementById('acc-name').value.trim();
    const type     = document.getElementById('acc-type').value;
    const currency = document.getElementById('acc-currency').value;
    const balance  = document.getElementById('acc-balance').value || '0';

    if (!name) {
        showError('El nombre de la cuenta es obligatorio.');
        return;
    }

    const btnText    = document.getElementById('btn-create-text');
    const btnSpinner = document.getElementById('btn-create-spinner');
    btnText.textContent = 'Creando...';
    btnSpinner.classList.remove('d-none');
    this.disabled = true;

    const body = new URLSearchParams({
        action:          'create_account',
        name:            name,
        type:            type,
        currency_code:   currency,
        initial_balance: balance,
    });

    fetch(ACCOUNTS_AJAX_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body.toString(),
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) {
            showError(data.message, data.full_message);
            return;
        }

        showSuccess(data.message);
        bootstrap.Modal.getInstance(document.getElementById('createAccountModal')).hide();
        document.getElementById('acc-name').value    = '';
        document.getElementById('acc-balance').value = '0';

        const list = document.getElementById('accounts-list');
        const emptyState = document.getElementById('accounts-empty');
        if (emptyState) emptyState.remove();
        list.insertAdjacentHTML('afterbegin', renderAccountCard(data.account));
        attachCardEvents();

        loadAccounts();
    })
    .catch(err => {
        showError('No se pudo crear la cuenta.', err.message);
    })
    .finally(() => {
        btnText.textContent = 'Crear Cuenta';
        btnSpinner.classList.add('d-none');
        document.getElementById('btn-create-account').disabled = false;
    });
});

// ============================================
// UTIL: escapar HTML
// ============================================
function escapeHtml(str) {
    if (str == null) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

// ============================================
// INICIO
// ============================================
loadAccounts();
</script>