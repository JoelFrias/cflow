<!-- modules/goals.php - Módulo para gestionar metas de ahorro -->

<style>
.goal-card {
    transition: transform 0.2s, box-shadow 0.2s;
}
.goal-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1);
}
.progress-bar {
    transition: width 0.5s ease;
}
</style>

<!-- ============================================ -->
<!-- LISTA DE METAS                              -->
<!-- ============================================ -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap">
                <h5 class="mb-0"><i class="fas fa-piggy-bank"></i> Metas de Ahorro</h5>
                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createGoalModal">
                    <i class="fas fa-plus"></i> Nueva Meta
                </button>
            </div>
            <div class="card-body">
                <div class="row" id="goals-list">
                    <div class="col-12 text-center py-4" id="goals-loading">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-2 text-muted">Cargando metas...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- MODAL: CREAR NUEVA META                     -->
<!-- ============================================ -->
<div class="modal fade" id="createGoalModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus-circle"></i> Nueva Meta de Ahorro</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Nombre de la meta</label>
                    <input type="text" id="goal-name" class="form-control"
                           placeholder="Ej: Vacaciones, Computadora nueva, Fondo de emergencia" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Monto objetivo</label>
                    <div class="input-group">
                        <span class="input-group-text">RD$</span>
                        <input type="number" step="0.01" id="goal-target" class="form-control" placeholder="0.00" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Fecha límite</label>
                    <input type="date" id="goal-deadline" class="form-control" required>
                </div>
                <div class="alert alert-info">
                    <small><i class="fas fa-info-circle"></i> Puedes ir agregando abonos desde el botón "Abonar" en cada tarjeta.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btn-add-goal">
                    <span id="btn-goal-text">Crear Meta</span>
                    <span id="btn-goal-spinner" class="spinner-border spinner-border-sm d-none"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- MODAL: AGREGAR ABONO                        -->
<!-- ============================================ -->
<div class="modal fade" id="addContributionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus-circle"></i> Abonar a: <span id="contribution-goal-name"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="contribution-goal-id">
                <div class="mb-3">
                    <label class="form-label">Monto a abonar</label>
                    <div class="input-group">
                        <span class="input-group-text">RD$</span>
                        <input type="number" step="0.01" id="contribution-amount" class="form-control" placeholder="0.00" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Fecha del abono</label>
                    <input type="date" id="contribution-date" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Notas <span class="text-muted">(opcional)</span></label>
                    <textarea id="contribution-notes" class="form-control" rows="2"
                              placeholder="Ej: Ahorro de este mes, Bono extra..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" id="btn-add-contribution">
                    <span id="btn-contribution-text">Registrar Abono</span>
                    <span id="btn-contribution-spinner" class="spinner-border spinner-border-sm d-none"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- MODAL: HISTORIAL DE ABONOS                  -->
<!-- ============================================ -->
<div class="modal fade" id="historyModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-history"></i> Historial: <span id="history-goal-name"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="history-content">
                <!-- Se rellena por JS -->
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
<script>
const GOALS_AJAX_URL = 'ajax/goals.php';

// ============================================
// HELPERS
// ============================================
function showError(message, fullMessage = null) {
    console.error('[Goals Error]', fullMessage || message);
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

function escapeHtml(str) {
    if (str == null) return '';
    return String(str)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
}

function ajaxPost(body) {
    return fetch(GOALS_AJAX_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams(body).toString(),
    }).then(r => r.json());
}

function ajaxGet(params) {
    const qs = new URLSearchParams(params).toString();
    return fetch(`${GOALS_AJAX_URL}?${qs}`).then(r => r.json());
}

function setLoading(btnId, textId, spinnerId, loading, originalText) {
    document.getElementById(btnId).disabled = loading;
    document.getElementById(textId).textContent = loading ? 'Procesando...' : originalText;
    document.getElementById(spinnerId).classList.toggle('d-none', !loading);
}

function fmt(n) {
    return parseFloat(n).toLocaleString('es-DO', { minimumFractionDigits: 2 });
}

// ============================================
// RENDER: tarjeta de una meta
// ============================================
function renderGoalCard(goal) {
    const progress     = Math.min(100, (goal.current_amount / goal.target_amount) * 100);
    const remaining    = Math.max(0, goal.target_amount - goal.current_amount);
    const deadline     = new Date(goal.deadline + 'T00:00:00');
    const today        = new Date();
    today.setHours(0,0,0,0);
    const daysLeft     = Math.max(0, Math.ceil((deadline - today) / (1000 * 60 * 60 * 24)));
    const monthsLeft   = Math.max(1, Math.ceil(daysLeft / 30));
    const monthlyNeeded = remaining / monthsLeft;
    const isCompleted  = goal.is_completed == 1 || progress >= 99.9;

    const completedBadge = isCompleted
        ? `<span class="badge bg-success ms-2"><i class="fas fa-check"></i> Completada</span>`
        : '';

    const deleteBtn = (!isCompleted && parseFloat(goal.current_amount) == 0)
        ? `<button class="btn btn-sm btn-outline-danger ms-1"
                   onclick="confirmDeleteGoal(${goal.id}, '${escapeHtml(goal.name)}')">
               <i class="fas fa-trash"></i>
           </button>`
        : '';

    const monthlyAlert = (!isCompleted && remaining > 0)
        ? `<div class="alert alert-info mt-3 mb-0 py-2">
               <small>
                   <i class="fas fa-calculator"></i>
                   Necesitas ahorrar <strong>RD$ ${fmt(monthlyNeeded)}</strong> por mes
                   para alcanzar tu meta en ${monthsLeft} mes(es).
               </small>
           </div>`
        : '';

    return `
        <div class="col-md-6 mb-3" id="goal-card-${goal.id}">
            <div class="card goal-card h-100 border-${isCompleted ? 'success' : 'primary'}">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                        <div>
                            <h6 class="mb-1">
                                ${escapeHtml(goal.name)}
                                ${completedBadge}
                            </h6>
                            <small class="text-muted">
                                <i class="far fa-calendar-alt"></i>
                                Vence: ${deadline.toLocaleDateString('es-DO')}
                                (${daysLeft} días)
                            </small>
                        </div>
                        <div class="d-flex flex-wrap gap-1">
                            <button class="btn btn-sm btn-outline-primary"
                                    onclick="openContributionModal(${goal.id}, '${escapeHtml(goal.name)}')">
                                <i class="fas fa-plus-circle"></i> Abonar
                            </button>
                            <button class="btn btn-sm btn-outline-info"
                                    onclick="openHistoryModal(${goal.id}, '${escapeHtml(goal.name)}')">
                                <i class="fas fa-history"></i>
                            </button>
                            ${deleteBtn}
                        </div>
                    </div>

                    <div class="mt-3">
                        <div class="d-flex justify-content-between mb-1">
                            <small class="text-muted">Progreso</small>
                            <small class="fw-bold">${progress.toFixed(1)}%</small>
                        </div>
                        <div class="progress mb-2" style="height: 10px;">
                            <div class="progress-bar ${isCompleted ? 'bg-success' : 'bg-info'}"
                                 style="width: ${progress}%"></div>
                        </div>

                        <div class="row text-center mt-3">
                            <div class="col-4">
                                <small class="text-muted">Meta</small>
                                <h6 class="mb-0">RD$ ${fmt(goal.target_amount)}</h6>
                            </div>
                            <div class="col-4">
                                <small class="text-muted">Ahorrado</small>
                                <h6 class="mb-0 text-success">RD$ ${fmt(goal.current_amount)}</h6>
                            </div>
                            <div class="col-4">
                                <small class="text-muted">Faltan</small>
                                <h6 class="mb-0 text-warning">RD$ ${fmt(remaining)}</h6>
                            </div>
                        </div>

                        ${monthlyAlert}
                    </div>
                </div>
            </div>
        </div>
    `;
}

// ============================================
// RENDER: estado vacío
// ============================================
function renderEmptyGoals() {
    return `
        <div class="col-12 text-center text-muted py-5" id="goals-empty">
            <i class="fas fa-chart-line fa-3x mb-3"></i>
            <p>No tienes metas de ahorro definidas</p>
            <button class="btn btn-primary btn-sm"
                    data-bs-toggle="modal" data-bs-target="#createGoalModal">
                Crear primera meta
            </button>
        </div>
    `;
}

// ============================================
// CARGAR METAS
// ============================================
function loadGoals() {
    ajaxPost({ action: 'get_goals' })
        .then(data => {
            document.getElementById('goals-loading')?.remove();

            if (!data.success) {
                showError(data.message, data.full_message);
                document.getElementById('goals-list').innerHTML = renderEmptyGoals();
                return;
            }

            const list = document.getElementById('goals-list');
            list.innerHTML = data.goals.length === 0
                ? renderEmptyGoals()
                : data.goals.map(renderGoalCard).join('');
        })
        .catch(err => {
            showError('No se pudieron cargar las metas. Revisa la consola.', err.message);
            document.getElementById('goals-list').innerHTML = renderEmptyGoals();
        });
}

// ============================================
// CREAR META
// ============================================
// Valor por defecto de la fecha: +6 meses
(function() {
    const d = new Date();
    d.setMonth(d.getMonth() + 6);
    document.getElementById('goal-deadline').value = d.toISOString().slice(0, 10);
})();

document.getElementById('btn-add-goal').addEventListener('click', function () {
    const name     = document.getElementById('goal-name').value.trim();
    const target   = document.getElementById('goal-target').value;
    const deadline = document.getElementById('goal-deadline').value;

    if (!name)                        { showError('El nombre de la meta es obligatorio.'); return; }
    if (!target || parseFloat(target) <= 0) { showError('El monto objetivo debe ser mayor a 0.'); return; }
    if (!deadline)                    { showError('La fecha límite es obligatoria.'); return; }

    setLoading('btn-add-goal', 'btn-goal-text', 'btn-goal-spinner', true, 'Crear Meta');

    ajaxPost({ action: 'add_goal', name, target_amount: target, deadline })
        .then(data => {
            if (!data.success) { showError(data.message, data.full_message); return; }

            showSuccess(data.message);
            bootstrap.Modal.getInstance(document.getElementById('createGoalModal')).hide();

            document.getElementById('goal-name').value   = '';
            document.getElementById('goal-target').value = '';

            const empty = document.getElementById('goals-empty');
            if (empty) empty.remove();
            document.getElementById('goals-list').insertAdjacentHTML('beforeend', renderGoalCard(data.goal));
        })
        .catch(err => showError('Error de red al crear la meta.', err.message))
        .finally(() => setLoading('btn-add-goal', 'btn-goal-text', 'btn-goal-spinner', false, 'Crear Meta'));
});

// ============================================
// MODAL ABONO
// ============================================
function openContributionModal(goalId, goalName) {
    document.getElementById('contribution-goal-id').value    = goalId;
    document.getElementById('contribution-goal-name').textContent = goalName;
    document.getElementById('contribution-amount').value     = '';
    document.getElementById('contribution-notes').value      = '';
    document.getElementById('contribution-date').value       = new Date().toISOString().slice(0, 10);
    new bootstrap.Modal(document.getElementById('addContributionModal')).show();
}

document.getElementById('btn-add-contribution').addEventListener('click', function () {
    const goalId = document.getElementById('contribution-goal-id').value;
    const amount = document.getElementById('contribution-amount').value;
    const date   = document.getElementById('contribution-date').value;
    const notes  = document.getElementById('contribution-notes').value.trim();

    if (!amount || parseFloat(amount) <= 0) { showError('El monto debe ser mayor a 0.'); return; }
    if (!date) { showError('La fecha del abono es obligatoria.'); return; }

    setLoading('btn-add-contribution', 'btn-contribution-text', 'btn-contribution-spinner', true, 'Registrar Abono');

    ajaxPost({ action: 'add_contribution', goal_id: goalId, amount, contribution_date: date, notes })
        .then(data => {
            if (!data.success) { showError(data.message, data.full_message); return; }

            showSuccess(data.message);
            bootstrap.Modal.getInstance(document.getElementById('addContributionModal')).hide();

            // Reemplazar la tarjeta actualizada en el DOM
            const updated = data.updated_goal;
            const existing = document.getElementById(`goal-card-${updated.id}`);
            if (existing) existing.outerHTML = renderGoalCard(updated);
        })
        .catch(err => showError('Error de red al registrar el abono.', err.message))
        .finally(() => setLoading('btn-add-contribution', 'btn-contribution-text', 'btn-contribution-spinner', false, 'Registrar Abono'));
});

// ============================================
// MODAL HISTORIAL
// ============================================
function openHistoryModal(goalId, goalName) {
    document.getElementById('history-goal-name').textContent = goalName;
    document.getElementById('history-content').innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary"></div>
            <p class="mt-2 text-muted">Cargando historial...</p>
        </div>`;
    new bootstrap.Modal(document.getElementById('historyModal')).show();

    ajaxGet({ action: 'get_history', goal_id: goalId })
        .then(data => {
            if (!data.success) {
                showError(data.message, data.full_message);
                document.getElementById('history-content').innerHTML =
                    '<div class="alert alert-danger">Error al cargar el historial.</div>';
                return;
            }

            if (data.data.length === 0) {
                document.getElementById('history-content').innerHTML = `
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-3x mb-3"></i>
                        <p>No hay abonos registrados para esta meta</p>
                        <button class="btn btn-primary btn-sm"
                                onclick="bootstrap.Modal.getInstance(document.getElementById('historyModal')).hide();
                                         openContributionModal(${goalId}, '${escapeHtml(goalName)}')">
                            <i class="fas fa-plus-circle"></i> Agregar primer abono
                        </button>
                    </div>`;
                return;
            }

            let total = 0;
            let rows  = '';
            for (const item of data.data) {
                total += parseFloat(item.amount);
                rows += `
                    <tr>
                        <td>${item.contribution_date}</td>
                        <td class="text-success fw-bold">RD$ ${fmt(item.amount)}</td>
                        <td>${escapeHtml(item.notes) || '-'}</td>
                        <td><small class="text-muted">${item.created_at}</small></td>
                    </tr>`;
            }

            document.getElementById('history-content').innerHTML = `
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Fecha</th>
                                <th>Monto</th>
                                <th>Notas</th>
                                <th>Registrado</th>
                            </tr>
                        </thead>
                        <tbody>${rows}</tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th>Total</th>
                                <th class="text-success">RD$ ${fmt(total)}</th>
                                <th colspan="2"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>`;
        })
        .catch(err => {
            showError('Error de red al cargar el historial.', err.message);
            document.getElementById('history-content').innerHTML =
                '<div class="alert alert-danger">Error de conexión.</div>';
        });
}

// ============================================
// ELIMINAR META
// ============================================
function confirmDeleteGoal(goalId, goalName) {
    Swal.fire({
        title: '¿Eliminar meta?',
        text: `¿Estás seguro de que deseas eliminar "${goalName}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonText: 'Cancelar',
        confirmButtonText: 'Sí, eliminar',
    }).then(result => {
        if (!result.isConfirmed) return;

        ajaxPost({ action: 'delete_goal', goal_id: goalId })
            .then(data => {
                if (!data.success) { showError(data.message, data.full_message); return; }

                showSuccess(data.message);
                const el = document.getElementById(`goal-card-${data.goal_id}`);
                if (el) {
                    el.remove();
                    if (document.querySelectorAll('[id^="goal-card-"]').length === 0) {
                        document.getElementById('goals-list').innerHTML = renderEmptyGoals();
                    }
                }
            })
            .catch(err => showError('Error de red al eliminar la meta.', err.message));
    });
}

// ============================================
// INICIO
// ============================================
loadGoals();
</script>