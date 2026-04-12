<?php
// modules/debts.php - Módulo para gestionar deudas (préstamos, tarjetas de crédito, etc.)

// La ruta al ajax se calcula relativa al index.php (raíz del proyecto)
$ajax_url = 'ajax/debts.php';
?>

<div class="card" id="debts-module">
    <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap">
        <h5 class="mb-0"><i class="fas fa-hand-holding-usd"></i> Mis Deudas</h5>
        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#debtModal">
            <i class="fas fa-plus"></i> Registrar Deuda
        </button>
    </div>
    <div class="card-body" id="debts-list-container">
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-2 text-muted">Cargando deudas...</p>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- MODAL: REGISTRAR DEUDA                       -->
<!-- ============================================ -->
<div class="modal fade" id="debtModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus-circle"></i> Registrar Nueva Deuda</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Acreedor</label>
                    <input type="text" id="debt-creditor" class="form-control"
                           placeholder="Ej: Banco Popular, Préstamo personal">
                </div>
                <div class="mb-3">
                    <label class="form-label">Monto Total</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" step="0.01" id="debt-total" class="form-control" placeholder="0.00">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Tasa de Interés (%)</label>
                    <input type="number" step="0.01" id="debt-interest" class="form-control" placeholder="0">
                    <small class="text-muted">Opcional - Solo para deudas con interés</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Fecha de Vencimiento</label>
                    <input type="date" id="debt-due-date" class="form-control">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="DebtModule.addDebt()">
                    <i class="fas fa-save"></i> Registrar Deuda
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
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-history"></i>
                    Historial de pagos — <span id="history-creditor-name"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Filtros -->
                <div class="row g-2 mb-3 align-items-end">
                    <div class="col-sm-4">
                        <label class="form-label form-label-sm mb-1">Desde</label>
                        <input type="date" id="history-date-from" class="form-control form-control-sm">
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label form-label-sm mb-1">Hasta</label>
                        <input type="date" id="history-date-to" class="form-control form-control-sm">
                    </div>
                    <div class="col-sm-4 d-flex gap-2">
                        <button class="btn btn-sm btn-primary w-100" onclick="DebtModule.applyHistoryFilter()">
                            <i class="fas fa-filter"></i> Filtrar
                        </button>
                        <button class="btn btn-sm btn-outline-secondary w-100" onclick="DebtModule.clearHistoryFilter()">
                            Limpiar
                        </button>
                    </div>
                </div>
                <!-- Tabla -->
                <div id="history-table-container">
                    <div class="text-center py-4">
                        <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                        <span class="ms-2 text-muted">Cargando historial...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                <div id="history-total-footer" class="text-muted small"></div>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>

let _historyDebtId = null;

const DebtModule = (() => {

    const AJAX_URL = '<?= $ajax_url ?>';
    let _debts    = [];
    let _accounts = [];

    // ─────────────────────────────────────────────
    // UTILIDADES
    // ─────────────────────────────────────────────

    function fmt(n) {
        return parseFloat(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function escHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function escJs(str) {
        return String(str).replace(/\\/g, '\\\\').replace(/'/g, "\\'");
    }

    /** Popup arriba a la izquierda + mensaje COMPLETO en consola */
    function showError(friendlyMsg, fullMessage) {
        console.error('[DebtModule ERROR]', fullMessage ?? friendlyMsg);
        Swal.fire({
            title: 'Error',
            text: friendlyMsg,
            icon: 'error',
            toast: true,
            position: 'top-start',
            showConfirmButton: false,
            timer: 5000,
            timerProgressBar: true,
        });
    }

    function showSuccess(msg) {
        Swal.fire({
            title: '¡Éxito!',
            text: msg,
            icon: 'success',
            toast: true,
            position: 'top-start',
            showConfirmButton: false,
            timer: 3500,
            timerProgressBar: true,
        });
    }

    /**
     * Fetch al backend. Si la respuesta no es JSON válido
     * imprime el HTML crudo en consola para facilitar el debug.
     */
    async function request(action, data = {}) {
        const body = new URLSearchParams({ action, ...data });

        const res = await fetch(AJAX_URL, {
            method : 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body,
        });

        const raw = await res.text();

        let json;
        try {
            json = JSON.parse(raw);
        } catch (e) {
            // El backend devolvió HTML (error de PHP) — lo mostramos completo en consola
            console.error('[DebtModule] Respuesta no-JSON del servidor:\n', raw);
            throw Object.assign(new Error('El servidor devolvió una respuesta inválida. Revisa la consola.'), {
                fullMessage: raw,
            });
        }

        if (!json.success) {
            console.error('[DebtModule ERROR]', json.full_message ?? json.message);
            throw Object.assign(new Error(json.message ?? 'Error desconocido.'), {
                fullMessage: json.full_message ?? json.message,
            });
        }

        return json;
    }

    // ─────────────────────────────────────────────
    // RENDERIZADO
    // ─────────────────────────────────────────────

    function renderDebts(debts) {
        const container = document.getElementById('debts-list-container');

        if (!debts.length) {
            container.innerHTML = `
                <div class="text-center text-muted py-5">
                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                    <p>No tienes deudas registradas</p>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#debtModal">
                        Registrar primera deuda
                    </button>
                </div>`;
            return;
        }

        const today = new Date().toISOString().split('T')[0];

        container.innerHTML = debts.map(debt => {
            const progress    = (debt.paid_amount / debt.total_amount) * 100;
            const isOverdue   = debt.due_date < today && debt.status === 'pending';
            const borderClass = isOverdue ? 'border-danger' : 'border-secondary';
            const barClass    = progress >= 100 ? 'bg-success' : 'bg-info';
            const dueFmt      = new Date(debt.due_date + 'T00:00:00').toLocaleDateString('es-DO');

            const actionBtns = debt.status === 'paid'
                ? `<span class="badge bg-success mt-2"><i class="fas fa-check"></i> Pagada</span>
                <button class="btn btn-sm btn-outline-secondary mt-2"
                        onclick="DebtModule.showHistory(${debt.id}, '${escJs(debt.creditor)}')">
                    <i class="fas fa-history"></i>
                </button>`
                : `<button class="btn btn-sm btn-success mt-2"
                        onclick="DebtModule.showPayModal(${debt.id}, '${escJs(debt.creditor)}', ${debt.pending})">
                    <i class="fas fa-money-bill-wave"></i> Pagar
                </button>
                <button class="btn btn-sm btn-outline-info mt-2"
                        onclick="DebtModule.showHistory(${debt.id}, '${escJs(debt.creditor)}')">
                    <i class="fas fa-history"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger mt-2"
                        onclick="DebtModule.confirmDelete(${debt.id}, '${escJs(debt.creditor)}')">
                    <i class="fas fa-trash"></i>
                </button>`;

            return `
            <div class="card mb-3 ${borderClass}" id="debt-card-${debt.id}">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-4">
                            <h6 class="mb-1">${escHtml(debt.creditor)}</h6>
                            <small class="text-muted">
                                <i class="fas fa-calendar-alt"></i> Vence: ${dueFmt}
                                ${isOverdue ? '<span class="badge bg-danger ms-2">VENCIDA</span>' : ''}
                            </small>
                            ${parseFloat(debt.interest_rate) > 0
                                ? `<br><small class="text-muted"><i class="fas fa-percent"></i> Interés: ${debt.interest_rate}%</small>`
                                : ''}
                        </div>
                        <div class="col-md-4">
                            <div>Total: <strong>$${fmt(debt.total_amount)}</strong></div>
                            <div>Pagado: <strong class="text-success">$${fmt(debt.paid_amount)}</strong></div>
                            <div>Pendiente: <strong class="text-danger" id="pending-${debt.id}">$${fmt(debt.pending)}</strong></div>
                        </div>
                        <div class="col-md-4">
                            <div class="progress mb-2" style="height: 8px;">
                                <div class="progress-bar ${barClass}"
                                     id="progress-bar-${debt.id}"
                                     style="width: ${Math.min(100, progress)}%"></div>
                            </div>
                            <small id="progress-label-${debt.id}">${Math.round(progress)}% completado</small>
                            <br>
                            <div id="debt-actions-${debt.id}">${actionBtns}</div>
                        </div>
                    </div>
                </div>
            </div>`;
        }).join('');
    }

    // ─────────────────────────────────────────────
    // CARGAR DEUDAS
    // ─────────────────────────────────────────────

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

    // ─────────────────────────────────────────────
    // AGREGAR DEUDA
    // ─────────────────────────────────────────────

    async function addDebt() {
        const creditor = document.getElementById('debt-creditor').value.trim();
        const total    = document.getElementById('debt-total').value;
        const interest = document.getElementById('debt-interest').value || 0;
        const due_date = document.getElementById('debt-due-date').value;

        if (!creditor || !total || parseFloat(total) <= 0) {
            showError('Complete todos los campos correctamente.');
            return;
        }

        try {
            const data = await request('add_debt', {
                creditor,
                total_amount: total,
                interest_rate: interest,
                due_date,
            });

            showSuccess(data.message);
            bootstrap.Modal.getInstance(document.getElementById('debtModal'))?.hide();

            // Limpiar campos
            document.getElementById('debt-creditor').value = '';
            document.getElementById('debt-total').value    = '';
            document.getElementById('debt-interest').value = '';

            await loadDebts();
        } catch (err) {
            showError(err.message, err.fullMessage);
        }
    }

    // ─────────────────────────────────────────────
    // MODAL DE PAGO
    // ─────────────────────────────────────────────

    function showPayModal(debtId, creditorName, pendingAmount) {
        const accountsHtml = _accounts.length
            ? _accounts.map(acc => `
                <option value="${acc.id}">
                    ${escHtml(acc.name)} (${acc.currency_code}) — Saldo: ${acc.symbol} ${fmt(acc.balance)}
                </option>`).join('')
            : '<option disabled>No hay cuentas DOP disponibles</option>';

        Swal.fire({
            title: `Pagar: ${creditorName}`,
            width: '500px',
            html: `
                <div class="text-start">
                    <div class="alert alert-info mb-3">
                        <small>Pendiente: <strong>RD$ ${fmt(pendingAmount)}</strong></small>
                    </div>
                    <div class="alert alert-warning mb-3">
                        <small><i class="fas fa-info-circle"></i>
                            Los pagos se realizan en <strong>Pesos Dominicanos (RD$)</strong>
                        </small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Cuenta de Origen</label>
                        <select id="swal-account-id" class="form-control">${accountsHtml}</select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Monto a Pagar (RD$)</label>
                        <input type="number" id="swal-amount" class="form-control"
                               placeholder="0.00" step="0.01" max="${pendingAmount}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Fecha de Pago</label>
                        <input type="date" id="swal-date" class="form-control"
                               value="${new Date().toISOString().split('T')[0]}">
                    </div>
                </div>`,
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-check"></i> Confirmar Pago',
            cancelButtonText: 'Cancelar',
            preConfirm: () => {
                const account_id   = document.getElementById('swal-account-id').value;
                const amount       = parseFloat(document.getElementById('swal-amount').value);
                const payment_date = document.getElementById('swal-date').value;

                if (!account_id) {
                    Swal.showValidationMessage('Selecciona una cuenta de origen.');
                    return false;
                }
                if (isNaN(amount) || amount <= 0) {
                    Swal.showValidationMessage('Ingresa un monto válido mayor a 0.');
                    return false;
                }
                if (amount > pendingAmount) {
                    Swal.showValidationMessage(`No puede exceder RD$ ${fmt(pendingAmount)}.`);
                    return false;
                }
                return { debt_id: debtId, account_id, amount, payment_date };
            },
        }).then(async result => {
            if (!result.isConfirmed) return;

            Swal.fire({
                title: 'Procesando pago...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading(),
            });

            try {
                const data = await request('pay_debt', result.value);
                Swal.close();
                showSuccess(data.message);
                updateDebtCard(debtId, data);
            } catch (err) {
                Swal.close();
                showError(err.message, err.fullMessage);
            }
        });
    }

    /** Actualiza la tarjeta en el DOM sin recargar la página */
    function updateDebtCard(debtId, data) {
        const debt = _debts.find(d => d.id == debtId);
        if (!debt) { loadDebts(); return; }

        debt.paid_amount = parseFloat(data.new_paid);
        debt.pending     = parseFloat(data.pending);
        if (data.is_fully_paid) debt.status = 'paid';

        const progress = (debt.paid_amount / debt.total_amount) * 100;

        const pendingEl  = document.getElementById(`pending-${debtId}`);
        const barEl      = document.getElementById(`progress-bar-${debtId}`);
        const labelEl    = document.getElementById(`progress-label-${debtId}`);
        const actionsEl  = document.getElementById(`debt-actions-${debtId}`);
        const cardEl     = document.getElementById(`debt-card-${debtId}`);

        if (pendingEl) pendingEl.textContent = `$${fmt(debt.pending)}`;
        if (barEl)     barEl.style.width = `${Math.min(100, progress)}%`;
        if (labelEl)   labelEl.textContent = `${Math.round(progress)}% completado`;

        if (actionsEl && data.is_fully_paid) {
            actionsEl.innerHTML = `<span class="badge bg-success mt-2"><i class="fas fa-check"></i> Pagada</span>`;
            cardEl?.classList.replace('border-secondary', 'border-success');
            cardEl?.classList.replace('border-danger',    'border-success');
        } else if (actionsEl) {
            // Actualizar el monto pendiente en el botón de pago
            const btn = actionsEl.querySelector('button.btn-success');
            if (btn) btn.setAttribute('onclick',
                `DebtModule.showPayModal(${debtId}, '${escJs(debt.creditor)}', ${debt.pending})`);
        }
    }

    // ─────────────────────────────────────────────
    // ELIMINAR DEUDA
    // ─────────────────────────────────────────────

    function confirmDelete(debtId, creditorName) {
        Swal.fire({
            title: '¿Eliminar deuda?',
            text: `¿Seguro que deseas eliminar la deuda con "${creditorName}"?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
        }).then(async result => {
            if (!result.isConfirmed) return;

            try {
                const data = await request('delete_debt', { debt_id: debtId });
                showSuccess(data.message);

                document.getElementById(`debt-card-${debtId}`)?.remove();
                _debts = _debts.filter(d => d.id != debtId);
                if (_debts.length === 0) renderDebts([]);
            } catch (err) {
                showError(err.message, err.fullMessage);
            }
        });
    }

    // ─────────────────────────────────────────────
    // INIT
    // ─────────────────────────────────────────────

    // Fecha por defecto: hoy + 30 días
    document.getElementById('debt-due-date').value =
        new Date(Date.now() + 30 * 864e5).toISOString().split('T')[0];

    loadDebts();

    // ─────────────────────────────────────────────
    // HISTORIAL DE PAGOS
    // ─────────────────────────────────────────────

    function renderPaymentsTable(payments, total) {
        const container = document.getElementById('history-table-container');
        const footer    = document.getElementById('history-total-footer');

        if (!payments.length) {
            container.innerHTML = `
                <div class="text-center text-muted py-4">
                    <i class="fas fa-receipt fa-2x mb-2"></i>
                    <p class="mb-0">No hay pagos en el período seleccionado.</p>
                </div>`;
            footer.textContent = '';
            return;
        }

        const rows = payments.map((p, i) => {
            const date = new Date(p.payment_date + 'T00:00:00').toLocaleDateString('es-DO', {
                day: '2-digit', month: 'short', year: 'numeric',
            });
            return `
            <tr id="payment-row-${p.id}">
                <td class="text-muted">${i + 1}</td>
                <td>${date}</td>
                <td>${escHtml(p.account_name)}</td>
                <td class="text-success fw-semibold">${p.symbol} ${fmt(p.amount)}</td>
                <td>
                    <button class="btn btn-sm btn-outline-danger py-0 px-2"
                            onclick="DebtModule.confirmRevertPayment(${p.id}, ${p.amount})"
                            title="Revertir este pago">
                        <i class="fas fa-undo"></i> Revertir
                    </button>
                </td>
            </tr>`;
        }).join('');

        container.innerHTML = `
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:40px">#</th>
                            <th>Fecha</th>
                            <th>Cuenta</th>
                            <th>Monto</th>
                            <th style="width:110px"></th>
                        </tr>
                    </thead>
                    <tbody>${rows}</tbody>
                </table>
            </div>`;

        footer.innerHTML = `Total en período: <strong>RD$ ${fmt(total)}</strong>`;
    }

    async function loadHistory(debtId, dateFrom = '', dateTo = '') {
        const container = document.getElementById('history-table-container');
        container.innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                <span class="ms-2 text-muted">Cargando...</span>
            </div>`;

        try {
            const data = await request('get_payment_history', {
                debt_id:   debtId,
                date_from: dateFrom,
                date_to:   dateTo,
            });
            renderPaymentsTable(data.payments, data.total);
        } catch (err) {
            showError(err.message, err.fullMessage);
        }
    }

    function showHistory(debtId, creditorName) {
        _historyDebtId = debtId;
        document.getElementById('history-creditor-name').textContent = creditorName;
        document.getElementById('history-date-from').value = '';
        document.getElementById('history-date-to').value   = '';
        document.getElementById('history-total-footer').textContent = '';

        bootstrap.Modal.getOrCreateInstance(
            document.getElementById('paymentHistoryModal')
        ).show();

        loadHistory(debtId);
    }

    function applyHistoryFilter() {
        if (!_historyDebtId) return;
        const from = document.getElementById('history-date-from').value;
        const to   = document.getElementById('history-date-to').value;
        loadHistory(_historyDebtId, from, to);
    }

    function clearHistoryFilter() {
        document.getElementById('history-date-from').value = '';
        document.getElementById('history-date-to').value   = '';
        if (_historyDebtId) loadHistory(_historyDebtId);
    }

    // ─────────────────────────────────────────────
    // REVERTIR PAGO
    // ─────────────────────────────────────────────

    function confirmRevertPayment(paymentId, amount) {
        Swal.fire({
            title: '¿Revertir pago?',
            html: `Se eliminará el pago de <strong>RD$ ${fmt(amount)}</strong> y el dinero será devuelto a la cuenta de origen.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, revertir',
            cancelButtonText: 'Cancelar',
        }).then(async result => {
            if (!result.isConfirmed) return;

            try {
                const data = await request('delete_payment', { payment_id: paymentId });
                showSuccess(data.message);

                // Quitar la fila del historial sin recargar
                document.getElementById(`payment-row-${paymentId}`)?.remove();

                // Si la tabla quedó vacía, mostrar mensaje
                const tbody = document.querySelector('#history-table-container tbody');
                if (tbody && tbody.children.length === 0) {
                    renderPaymentsTable([], 0);
                }

                // Actualizar la tarjeta de deuda en el fondo
                const debt = _debts.find(d => d.id == data.debt_id);
                if (debt) {
                    const pending   = debt.total_amount - data.new_paid;
                    debt.paid_amount = data.new_paid;
                    debt.pending     = pending;
                    if (debt.status === 'paid') debt.status = 'pending';

                    const progress = (data.new_paid / debt.total_amount) * 100;

                    const pendingEl = document.getElementById(`pending-${data.debt_id}`);
                    const barEl     = document.getElementById(`progress-bar-${data.debt_id}`);
                    const labelEl   = document.getElementById(`progress-label-${data.debt_id}`);
                    const actionsEl = document.getElementById(`debt-actions-${data.debt_id}`);

                    if (pendingEl) pendingEl.textContent = `$${fmt(pending)}`;
                    if (barEl)     barEl.style.width = `${Math.min(100, progress)}%`;
                    if (labelEl)   labelEl.textContent = `${Math.round(progress)}% completado`;

                    // Si la deuda volvió a 'pending', restaurar los botones de acción
                    if (actionsEl && data.new_paid < debt.total_amount) {
                        actionsEl.innerHTML = `
                            <button class="btn btn-sm btn-success mt-2"
                                    onclick="DebtModule.showPayModal(${debt.id}, '${escJs(debt.creditor)}', ${pending})">
                                <i class="fas fa-money-bill-wave"></i> Pagar
                            </button>
                            <button class="btn btn-sm btn-outline-info mt-2"
                                    onclick="DebtModule.showHistory(${debt.id}, '${escJs(debt.creditor)}')">
                                <i class="fas fa-history"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger mt-2"
                                    onclick="DebtModule.confirmDelete(${debt.id}, '${escJs(debt.creditor)}')">
                                <i class="fas fa-trash"></i>
                            </button>`;

                        // Quitar borde verde si estaba como pagada
                        const cardEl = document.getElementById(`debt-card-${data.debt_id}`);
                        cardEl?.classList.replace('border-success', 'border-secondary');
                    }

                    // Recalcular total del footer del historial en vivo
                    const footerEl = document.getElementById('history-total-footer');
                    if (footerEl && footerEl.innerHTML) {
                        loadHistory(_historyDebtId,
                            document.getElementById('history-date-from').value,
                            document.getElementById('history-date-to').value);
                    }
                }
            } catch (err) {
                showError(err.message, err.fullMessage);
            }
        });
    }

    return { addDebt, showPayModal, confirmDelete, loadDebts, showHistory, applyHistoryFilter, clearHistoryFilter, confirmRevertPayment };
})();

console.log('[DebtModule] Módulo cargado correctamente.');
</script>