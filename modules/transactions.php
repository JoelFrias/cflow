<?php
// modules/transactions.php - Módulo principal de transacciones, incluye filtros avanzados, tabla, paginación y modal para agregar transacciones.

$ajax_url          = 'ajax/transactions.php';
$ajax_category_url = 'ajax/create_category.php';
?>

<!-- ============================================ -->
<!-- PANEL DE FILTROS AVANZADOS                   -->
<!-- ============================================ -->
<div class="card mb-3" id="transactions-module">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h6 class="mb-0">
            <i class="fas fa-filter"></i> Filtros Avanzados
            <span class="badge bg-primary ms-2 d-none" id="filters-active-badge">Activos</span>
        </h6>
        <button class="btn btn-sm btn-outline-primary" type="button" id="toggleFiltersBtn">
            <i class="fas fa-chevron-down" id="filterToggleIcon"></i> Mostrar Filtros
        </button>
    </div>
    <div class="card-body" id="filtersPanel" style="display:none;">
        <div class="row g-3">
            <!-- Buscador -->
            <div class="col-md-12">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" id="filter-search" class="form-control" placeholder="Buscar en descripción, cuenta o categoría...">
                    <button class="btn btn-outline-secondary" type="button" id="clearSearchBtn" style="display:none;">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <!-- Cuenta -->
            <div class="col-md-3">
                <label class="form-label small text-muted">Cuenta</label>
                <select id="filter-account" class="form-select">
                    <option value="">Todas las cuentas</option>
                </select>
            </div>
            <!-- Categoría -->
            <div class="col-md-3">
                <label class="form-label small text-muted">Categoría</label>
                <select id="filter-category" class="form-select">
                    <option value="">Todas las categorías</option>
                    <optgroup label="📈 Ingresos" id="filter-income-group"></optgroup>
                    <optgroup label="📉 Gastos"   id="filter-expense-group"></optgroup>
                </select>
            </div>
            <!-- Tipo -->
            <div class="col-md-3">
                <label class="form-label small text-muted">Tipo</label>
                <select id="filter-type" class="form-select">
                    <option value="">Todos</option>
                    <option value="income">Ingresos</option>
                    <option value="expense">Gastos</option>
                    <option value="transfer">Transferencias</option>
                </select>
            </div>
            <!-- Fechas -->
            <div class="col-md-3">
                <label class="form-label small text-muted">Rango de Fechas</label>
                <div class="input-group">
                    <input type="date" id="filter-date-from" class="form-control" placeholder="Desde">
                    <span class="input-group-text">a</span>
                    <input type="date" id="filter-date-to"   class="form-control" placeholder="Hasta">
                </div>
            </div>
            <!-- Monto -->
            <div class="col-md-4">
                <label class="form-label small text-muted">Monto (RD$)</label>
                <div class="input-group">
                    <span class="input-group-text">Desde</span>
                    <input type="number" step="0.01" id="filter-min-amount" class="form-control" placeholder="0.00">
                    <span class="input-group-text">Hasta</span>
                    <input type="number" step="0.01" id="filter-max-amount" class="form-control" placeholder="999999.99">
                </div>
            </div>
            <!-- Botones -->
            <div class="col-md-8 d-flex align-items-end">
                <div class="btn-group w-100">
                    <button type="button" class="btn btn-primary" onclick="TransactionModule.applyFilters()">
                        <i class="fas fa-search"></i> Aplicar Filtros
                    </button>
                    <button type="button" class="btn btn-outline-secondary" onclick="TransactionModule.clearFilters()">
                        <i class="fas fa-eraser"></i> Limpiar Filtros
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- CONTROLES DE PAGINACIÓN + BOTÓN NUEVA        -->
<!-- ============================================ -->
<div class="card mb-3">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-4 mb-2 mb-md-0">
                <div class="d-flex align-items-center">
                    <label class="me-2 text-muted">Mostrar:</label>
                    <select id="limitSelect" class="form-select form-select-sm w-auto">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                    <span class="text-muted ms-2">registros</span>
                </div>
            </div>
            <div class="col-md-4 text-center">
                <small class="text-muted" id="records-info"></small>
            </div>
            <div class="col-md-4 text-end">
                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#transactionModal">
                    <i class="fas fa-plus"></i> Nueva Transacción
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- TABLA DE TRANSACCIONES                       -->
<!-- ============================================ -->
<div class="card">
    <div class="card-header bg-white">
        <h5 class="mb-0"><i class="fas fa-exchange-alt"></i> Historial de Transacciones</h5>
    </div>
    <div class="card-body p-0" id="transactions-table-container">
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-2 text-muted">Cargando transacciones...</p>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- PAGINACIÓN                                   -->
<!-- ============================================ -->
<div id="pagination-container" class="mt-3"></div>

<!-- ============================================ -->
<!-- MODAL: NUEVA TRANSACCIÓN                     -->
<!-- ============================================ -->
<div class="modal fade" id="transactionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus-circle"></i> Nueva Transacción</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Tipo -->
                <div class="mb-4">
                    <label class="form-label fw-bold">Tipo de Transacción</label>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="transaction-type-card income-card selected" id="labelIncome">
                                <input type="radio" name="type" value="income" class="d-none" id="typeIncome" checked>
                                <div class="card-body text-center p-3">
                                    <i class="fas fa-arrow-down text-success fa-2x mb-2"></i>
                                    <h6 class="mb-1 text-success">Ingreso</h6>
                                </div>
                            </label>
                        </div>
                        <div class="col-md-4">
                            <label class="transaction-type-card expense-card" id="labelExpense">
                                <input type="radio" name="type" value="expense" class="d-none" id="typeExpense">
                                <div class="card-body text-center p-3">
                                    <i class="fas fa-arrow-up text-danger fa-2x mb-2"></i>
                                    <h6 class="mb-1 text-danger">Gasto</h6>
                                </div>
                            </label>
                        </div>
                        <div class="col-md-4">
                            <label class="transaction-type-card transfer-card" id="labelTransfer">
                                <input type="radio" name="type" value="transfer" class="d-none" id="typeTransfer">
                                <div class="card-body text-center p-3">
                                    <i class="fas fa-exchange-alt text-primary fa-2x mb-2"></i>
                                    <h6 class="mb-1 text-primary">Transferencia</h6>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
                <!-- Cuenta origen -->
                <div class="mb-3">
                    <label class="form-label">Cuenta Origen</label>
                    <select id="modal-account-id" class="form-control">
                        <option value="">Seleccionar cuenta</option>
                    </select>
                </div>
                <!-- Cuenta destino (solo transferencia) -->
                <div class="mb-3 d-none" id="transferToDiv">
                    <label class="form-label">Cuenta Destino</label>
                    <select id="modal-transfer-to" class="form-control">
                        <option value="">Seleccionar cuenta destino</option>
                    </select>
                </div>
                <!-- Categoría -->
                <div class="mb-3">
                    <label class="form-label">Categoría</label>
                    <div class="input-group">
                        <select id="modal-category-id" class="form-control">
                            <option value="">Seleccionar categoría</option>
                            <optgroup label="📈 Ingresos" id="modal-income-group"></optgroup>
                            <optgroup label="📉 Gastos"   id="modal-expense-group"></optgroup>
                        </select>
                        <button type="button" class="btn btn-outline-primary"
                                data-bs-toggle="modal" data-bs-target="#quickCategoryModal">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                    <small class="text-muted">Las categorías se filtran según el tipo de transacción</small>
                </div>
                <!-- Moneda + Monto -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Moneda de Pago</label>
                            <select id="modal-payment-currency" class="form-control">
                                <option value="">Misma que la cuenta</option>
                            </select>
                            <small class="text-muted">Si pagas en otra moneda, se convertirá automáticamente</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Monto</label>
                            <input type="number" step="0.01" id="modal-amount" class="form-control" placeholder="0.00">
                            <small id="conversionPreview" class="text-muted"></small>
                        </div>
                    </div>
                </div>
                <!-- Fecha -->
                <div class="mb-3">
                    <label class="form-label">Fecha</label>
                    <input type="date" id="modal-date" class="form-control">
                </div>
                <!-- Descripción -->
                <div class="mb-3">
                    <label class="form-label">Descripción</label>
                    <textarea id="modal-description" class="form-control" rows="2"
                              placeholder="Opcional - Ej: Compra en supermercado, Pago de luz..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="TransactionModule.addTransaction()">
                    <i class="fas fa-save"></i> Guardar Transacción
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- MODAL RÁPIDO: CREAR CATEGORÍA               -->
<!-- ============================================ -->
<div class="modal fade" id="quickCategoryModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-tag"></i> Nueva Categoría</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Nombre</label>
                    <input type="text" id="quickCategoryName" class="form-control"
                           placeholder="Ej: Transporte, Comida...">
                </div>
                <div class="mb-3">
                    <label class="form-label">Tipo</label>
                    <select id="quickCategoryType" class="form-control">
                        <option value="income">💰 Ingreso</option>
                        <option value="expense">💸 Gasto</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="TransactionModule.createQuickCategory()">
                    <i class="fas fa-save"></i> Crear Categoría
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.transaction-type-card {
    cursor: pointer;
    border-radius: 12px;
    transition: all 0.3s ease;
    background-color: #f8f9fa;
    border: 2px solid transparent;
    display: block;
}
.transaction-type-card:hover { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0,0,0,.1); }
.income-card.selected   { border-color: #28a745; background: linear-gradient(135deg,#d4edda,#c3e6cb); }
.expense-card.selected  { border-color: #dc3545; background: linear-gradient(135deg,#f8d7da,#f5c6cb); }
.transfer-card.selected { border-color: #0d6efd; background: linear-gradient(135deg,#cfe2ff,#b6d4fe); }
</style>

<script>
const TransactionModule = (() => {

    const AJAX_URL     = '<?= $ajax_url ?>';
    const CAT_AJAX_URL = '<?= $ajax_category_url ?>';

    // Estado de filtros y paginación
    let _state = {
        page: 1, limit: 10,
        filter_account: '', filter_category: '', filter_type: '',
        filter_min_amount: '', filter_max_amount: '',
        filter_date_from: '', filter_date_to: '', filter_search: '',
    };

    // Datos de formulario cargados desde el backend
    let _accounts  = [];
    let _incomeCats = [];
    let _expenseCats = [];
    let _currencies = [];

    // ─── Utilidades ───────────────────────────────

    function showError(msg, full) {
        console.error('[TransactionModule ERROR]', full ?? msg);
        Swal.fire({ title: 'Error', text: msg, icon: 'error',
            toast: true, position: 'top-start',
            showConfirmButton: false, timer: 5000, timerProgressBar: true });
    }

    function showSuccess(msg) {
        Swal.fire({ title: '¡Éxito!', text: msg, icon: 'success',
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
            throw Object.assign(new Error('El servidor devolvió una respuesta inválida. Revisa la consola.'), { fullMessage: raw });
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
    function escHtml(str) {
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    // ─── Renderizar tabla ─────────────────────────

    function renderTable(transactions) {
        const container = document.getElementById('transactions-table-container');

        if (!transactions.length) {
            container.innerHTML = `
                <div class="text-center text-muted py-5">
                    <i class="fas fa-inbox fa-3x mb-2 d-block"></i>
                    No hay transacciones que coincidan con los filtros
                </div>`;
            return;
        }

        const rows = transactions.map(t => {
            const typeBadge = t.type === 'income'
                ? `<span class="badge bg-success"><i class="fas fa-arrow-down"></i> Ingreso</span>`
                : t.type === 'expense'
                ? `<span class="badge bg-danger"><i class="fas fa-arrow-up"></i> Gasto</span>`
                : `<span class="badge bg-info"><i class="fas fa-exchange-alt"></i> Transferencia</span>`;

            const amtClass = t.type === 'income' ? 'text-success' : 'text-danger';
            const dateFmt  = new Date(t.date + 'T00:00:00').toLocaleDateString('es-DO', { day:'2-digit', month:'2-digit', year:'numeric' });

            return `
            <tr id="tr-${t.id}">
                <td><small class="text-muted">#${t.id}</small></td>
                <td>${dateFmt}</td>
                <td>${escHtml(t.account_name ?? '-')} <small class="text-muted">(${t.currency_code ?? ''})</small></td>
                <td>${escHtml(t.category_name ?? '-')}</td>
                <td>${typeBadge}</td>
                <td class="${amtClass} fw-bold">
                    ${escHtml(t.symbol ?? '')} ${fmt(t.original_amount)}
                    <small class="text-muted">(${escHtml(t.original_currency ?? '')})</small>
                </td>
                <td class="text-muted small">RD$ ${fmt(t.converted_amount_dop)}</td>
                <td>
                    ${escHtml(t.description ?? '-')}
                    ${t.transfer_to_account ? '<br><small class="text-muted">→ Transferencia a otra cuenta</small>' : ''}
                </td>
                <td>
                    <button class="btn btn-sm btn-outline-danger"
                            onclick="TransactionModule.confirmDelete(${t.id})">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>`;
        }).join('');

        container.innerHTML = `
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th><th>Fecha</th><th>Cuenta</th><th>Categoría</th>
                            <th>Tipo</th><th>Monto Original</th><th>Monto (DOP)</th>
                            <th>Descripción</th><th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>${rows}</tbody>
                </table>
            </div>`;
    }

    // ─── Renderizar paginación ────────────────────

    function renderPagination(totalPages, currentPage) {
        const container = document.getElementById('pagination-container');
        if (totalPages <= 1) { container.innerHTML = ''; return; }

        let items = '';
        const prev = currentPage - 1;
        const next = currentPage + 1;

        items += `<li class="page-item ${currentPage <= 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="TransactionModule.goToPage(1);return false;"><i class="fas fa-angle-double-left"></i></a></li>`;
        items += `<li class="page-item ${currentPage <= 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="TransactionModule.goToPage(${prev});return false;"><i class="fas fa-angle-left"></i></a></li>`;

        const start = Math.max(1, currentPage - 2);
        const end   = Math.min(totalPages, currentPage + 2);
        if (start > 1) items += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        for (let i = start; i <= end; i++) {
            items += `<li class="page-item ${i === currentPage ? 'active' : ''}">
                <a class="page-link" href="#" onclick="TransactionModule.goToPage(${i});return false;">${i}</a></li>`;
        }
        if (end < totalPages) items += `<li class="page-item disabled"><span class="page-link">...</span></li>`;

        items += `<li class="page-item ${currentPage >= totalPages ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="TransactionModule.goToPage(${next});return false;"><i class="fas fa-angle-right"></i></a></li>`;
        items += `<li class="page-item ${currentPage >= totalPages ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="TransactionModule.goToPage(${totalPages});return false;"><i class="fas fa-angle-double-right"></i></a></li>`;

        container.innerHTML = `<div class="card"><div class="card-body">
            <nav><ul class="pagination justify-content-center mb-0 flex-wrap">${items}</ul></nav>
        </div></div>`;
    }

    // ─── Cargar transacciones ─────────────────────

    async function loadTransactions() {
        document.getElementById('transactions-table-container').innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2 text-muted">Cargando...</p>
            </div>`;

        try {
            const data = await request('get_transactions', _state, 'GET');
            renderTable(data.transactions);
            renderPagination(data.total_pages, data.page);

            const from = (_state.page - 1) * _state.limit + 1;
            const to   = Math.min(_state.page * _state.limit, data.total_records);
            document.getElementById('records-info').textContent =
                data.total_records > 0
                    ? `Mostrando ${from}–${to} de ${data.total_records} registros`
                    : 'Sin resultados';

            // Mostrar badge si hay filtros activos
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
            _currencies  = data.currencies;

            // Poblar selects del modal
            const accountOpts = _accounts.map(a =>
                `<option value="${a.id}" data-currency="${a.currency_code}" data-symbol="${a.symbol}">
                    ${escHtml(a.name)} (${a.currency_code}) — Saldo: ${a.symbol} ${fmt(a.balance)}
                </option>`).join('');
            document.getElementById('modal-account-id').innerHTML   = '<option value="">Seleccionar cuenta</option>' + accountOpts;
            document.getElementById('modal-transfer-to').innerHTML  = '<option value="">Seleccionar cuenta destino</option>' + accountOpts;

            // Poblar select de filtro de cuentas
            document.getElementById('filter-account').innerHTML =
                '<option value="">Todas las cuentas</option>' +
                _accounts.map(a => `<option value="${a.id}">${escHtml(a.name)} (${a.currency_code})</option>`).join('');

            // Categorías modal
            document.getElementById('modal-income-group').innerHTML  = _incomeCats.map(c => `<option value="${c.id}">${escHtml(c.name)}</option>`).join('');
            document.getElementById('modal-expense-group').innerHTML = _expenseCats.map(c => `<option value="${c.id}">${escHtml(c.name)}</option>`).join('');

            // Categorías filtro
            document.getElementById('filter-income-group').innerHTML  = _incomeCats.map(c => `<option value="${c.id}">${escHtml(c.name)}</option>`).join('');
            document.getElementById('filter-expense-group').innerHTML = _expenseCats.map(c => `<option value="${c.id}">${escHtml(c.name)}</option>`).join('');

            // Monedas
            document.getElementById('modal-payment-currency').innerHTML =
                '<option value="">Misma que la cuenta</option>' +
                _currencies.map(c => `<option value="${c.code}">${escHtml(c.name)} (${c.symbol})</option>`).join('');

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
        const type          = document.querySelector('input[name="type"]:checked')?.value;
        const account_id    = document.getElementById('modal-account-id').value;
        const transfer_to   = document.getElementById('modal-transfer-to').value;
        const category_id   = document.getElementById('modal-category-id').value;
        const payment_currency = document.getElementById('modal-payment-currency').value;
        const amount        = document.getElementById('modal-amount').value;
        const date          = document.getElementById('modal-date').value;
        const description   = document.getElementById('modal-description').value;

        if (!account_id) { showError('Selecciona una cuenta de origen.'); return; }
        if (!amount || parseFloat(amount) <= 0) { showError('El monto debe ser mayor a 0.'); return; }
        if (!date) { showError('La fecha es obligatoria.'); return; }

        try {
            const data = await request('add_transaction', {
                account_id, category_id, type, amount, date, description,
                transfer_to, payment_currency,
            });

            showSuccess(data.message);
            bootstrap.Modal.getInstance(document.getElementById('transactionModal'))?.hide();

            // Limpiar modal
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
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
        }).then(async result => {
            if (!result.isConfirmed) return;
            try {
                const data = await request('delete_transaction', { transaction_id: id });
                showSuccess(data.message);
                document.getElementById(`tr-${id}`)?.remove();

                // Si la tabla quedó vacía, recargar
                const tbody = document.querySelector('#transactions-table-container tbody');
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

        Swal.fire({ title: 'Creando categoría...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

        try {
            const res  = await fetch(CAT_AJAX_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ name, type }),
            });
            const raw  = await res.text();
            const json = JSON.parse(raw);

            if (!json.success) throw Object.assign(new Error(json.message), { fullMessage: json.full_message ?? json.message });

            Swal.close();
            showSuccess('Categoría creada correctamente.');
            bootstrap.Modal.getInstance(document.getElementById('quickCategoryModal'))?.hide();
            document.getElementById('quickCategoryName').value = '';

            // Agregar al select del modal y a los filtros
            const opt = `<option value="${json.category_id}">${escHtml(name)}</option>`;
            if (type === 'income') {
                document.getElementById('modal-income-group').insertAdjacentHTML('beforeend', opt);
                document.getElementById('filter-income-group').insertAdjacentHTML('beforeend', opt);
                _incomeCats.push({ id: json.category_id, name });
                if (document.querySelector('input[name="type"]:checked')?.value === 'income') {
                    document.getElementById('modal-category-id').value = json.category_id;
                }
            } else {
                document.getElementById('modal-expense-group').insertAdjacentHTML('beforeend', opt);
                document.getElementById('filter-expense-group').insertAdjacentHTML('beforeend', opt);
                _expenseCats.push({ id: json.category_id, name });
                if (document.querySelector('input[name="type"]:checked')?.value === 'expense') {
                    document.getElementById('modal-category-id').value = json.category_id;
                }
            }
        } catch (err) {
            Swal.close();
            showError(err.message, err.fullMessage);
        }
    }

    // ─── Lógica del modal de transacción ─────────

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

    // Tipo de transacción
    document.querySelectorAll('.transaction-type-card').forEach(card => {
        card.addEventListener('click', () => {
            document.querySelectorAll('.transaction-type-card').forEach(c => c.classList.remove('selected'));
            card.classList.add('selected');
            card.querySelector('input[type="radio"]').checked = true;
            const type = card.querySelector('input[type="radio"]').value;
            document.getElementById('transferToDiv').classList.toggle('d-none', type !== 'transfer');
            filterModalCategories(type);
        });
    });

    // Reset modal al abrirlo
    document.getElementById('transactionModal').addEventListener('shown.bs.modal', () => {
        document.querySelectorAll('.transaction-type-card').forEach(c => c.classList.remove('selected'));
        document.getElementById('labelIncome').classList.add('selected');
        document.getElementById('typeIncome').checked = true;
        document.getElementById('transferToDiv').classList.add('d-none');
        filterModalCategories('income');
        document.getElementById('modal-date').value = new Date().toISOString().split('T')[0];
    });

    // Preview de conversión
    ['modal-payment-currency', 'modal-account-id', 'modal-amount'].forEach(id => {
        document.getElementById(id)?.addEventListener('change', updateConversionPreview);
    });
    document.getElementById('modal-amount')?.addEventListener('keyup', updateConversionPreview);

    function updateConversionPreview() {
        const selOption      = document.getElementById('modal-account-id').selectedOptions[0];
        const accountCurrency = selOption?.dataset.currency ?? '';
        const paymentCurrency = document.getElementById('modal-payment-currency').value;
        const amount          = document.getElementById('modal-amount').value;
        const preview         = document.getElementById('conversionPreview');

        if (amount && paymentCurrency && paymentCurrency !== accountCurrency && accountCurrency) {
            preview.innerHTML = `<i class="fas fa-sync-alt"></i> Se convertirá de ${paymentCurrency} a ${accountCurrency} automáticamente`;
        } else {
            preview.innerHTML = '';
        }
    }

    // Botón limpiar búsqueda
    document.getElementById('filter-search').addEventListener('input', function () {
        document.getElementById('clearSearchBtn').style.display = this.value ? '' : 'none';
    });
    document.getElementById('clearSearchBtn').addEventListener('click', () => {
        document.getElementById('filter-search').value = '';
        document.getElementById('clearSearchBtn').style.display = 'none';
    });

    // Limit select
    document.getElementById('limitSelect').addEventListener('change', function () {
        _state.limit = parseInt(this.value);
        _state.page  = 1;
        loadTransactions();
    });

    // Toggle filtros
    document.getElementById('toggleFiltersBtn').addEventListener('click', () => {
        const panel = document.getElementById('filtersPanel');
        const icon  = document.getElementById('filterToggleIcon');
        const btn   = document.getElementById('toggleFiltersBtn');
        const visible = panel.style.display !== 'none';
        panel.style.display = visible ? 'none' : '';
        icon.className = visible ? 'fas fa-chevron-down' : 'fas fa-chevron-up';
        btn.innerHTML  = `<i class="fas fa-chevron-${visible ? 'down' : 'up'}"></i> ${visible ? 'Mostrar' : 'Ocultar'} Filtros`;
    });

    // ─── Init ─────────────────────────────────────

    document.getElementById('modal-date').value = new Date().toISOString().split('T')[0];

    Promise.all([loadFormData(), loadTransactions()]);

    return { applyFilters, clearFilters, goToPage, addTransaction, confirmDelete, createQuickCategory };
})();

console.log('[TransactionModule] Módulo cargado correctamente.');
</script>