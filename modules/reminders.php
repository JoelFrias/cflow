<?php
// modules/reminders.php - Módulo de Recordatorios para el Panel de Control

$user_id = $_SESSION['user_id'];

// ── Recordatorios activos ────────────────────────────────────────────────────
$stmt = $pdo->prepare("
    SELECT * FROM reminders
    WHERE user_id = ? AND completed = 0
    ORDER BY reminder_date ASC
");
$stmt->execute([$user_id]);
$reminders = $stmt->fetchAll();

// ── Historial (últimos 5 completados) ───────────────────────────────────────
$stmt = $pdo->prepare("
    SELECT * FROM reminders
    WHERE user_id = ? AND completed = 1
    ORDER BY reminder_date DESC
    LIMIT 5
");
$stmt->execute([$user_id]);
$completed_reminders = $stmt->fetchAll();
?>

<!-- ============================================================ -->
<!-- RECORDATORIOS ACTIVOS                                        -->
<!-- ============================================================ -->
<div class="card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap">
        <h5 class="mb-0"><i class="fas fa-bell"></i> Recordatorios Activos</h5>
        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#reminderModal">
            <i class="fas fa-plus"></i> Nuevo Recordatorio
        </button>
    </div>

    <div class="card-body" id="reminders-list">
        <?php if (empty($reminders)): ?>
            <div class="text-center text-muted py-4" id="empty-reminders">
                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                <p>No hay recordatorios pendientes</p>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#reminderModal">
                    Crear recordatorio
                </button>
            </div>
        <?php else: ?>
            <?php foreach ($reminders as $rem):
                $is_overdue = strtotime($rem['reminder_date']) < time();
            ?>
            <div class="alert <?= $is_overdue ? 'alert-danger' : 'alert-info' ?> mb-3 shadow-sm reminder-item"
                 id="reminder-<?= $rem['id'] ?>">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <div>
                        <strong><i class="fas fa-bell"></i> <?= htmlspecialchars($rem['title']) ?></strong><br>
                        <small>
                            <i class="far fa-calendar-alt"></i>
                            <?= date('d/m/Y', strtotime($rem['reminder_date'])) ?>
                            <?php if ($is_overdue): ?>
                                <span class="badge bg-danger ms-2">VENCIDO</span>
                            <?php endif; ?>
                        </small>
                        <?php if (!empty($rem['description'])): ?>
                            <br><small class="text-muted">
                                <i class="fas fa-file-alt"></i> <?= htmlspecialchars($rem['description']) ?>
                            </small>
                        <?php endif; ?>
                        <?php if ($rem['is_recurring']): ?>
                            <br><span class="badge bg-secondary mt-1">
                                <i class="fas fa-sync-alt"></i> Recurrente (<?= $rem['recurrence_interval'] ?>)
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="mt-2 mt-sm-0">
                        <button class="btn btn-sm btn-success btn-complete" data-id="<?= $rem['id'] ?>">
                            <i class="fas fa-check"></i> Completar
                        </button>
                        <button class="btn btn-sm btn-outline-danger btn-delete" data-id="<?= $rem['id'] ?>">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- ============================================================ -->
<!-- HISTORIAL DE COMPLETADOS                                     -->
<!-- ============================================================ -->
<div class="card mt-3" id="history-card" <?= empty($completed_reminders) ? 'style="display:none"' : '' ?>>
    <div class="card-header bg-white">
        <h6 class="mb-0"><i class="fas fa-history"></i> Historial de Recordatorios</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Título</th>
                        <th>Fecha</th>
                        <th>Descripción</th>
                    </tr>
                </thead>
                <tbody id="history-body">
                    <?php foreach ($completed_reminders as $rem): ?>
                    <tr>
                        <td><?= htmlspecialchars($rem['title']) ?></td>
                        <td><?= date('d/m/Y', strtotime($rem['reminder_date'])) ?></td>
                        <td><?= htmlspecialchars($rem['description']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- MODAL: NUEVO RECORDATORIO                                    -->
<!-- ============================================================ -->
<div class="modal fade" id="reminderModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus-circle"></i> Nuevo Recordatorio</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Título</label>
                    <input type="text" id="rem-title" class="form-control"
                           placeholder="Ej: Pago de tarjeta, Cumpleaños, etc." required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Descripción (opcional)</label>
                    <textarea id="rem-description" class="form-control" rows="2"
                              placeholder="Detalles adicionales..."></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Fecha</label>
                    <input type="date" id="rem-date" class="form-control"
                           value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" id="recurringCheck" class="form-check-input">
                    <label class="form-check-label" for="recurringCheck">
                        <i class="fas fa-sync-alt"></i> Recordatorio recurrente
                    </label>
                </div>
                <div class="mb-3" id="recurrenceDiv" style="display:none;">
                    <label class="form-label">Intervalo</label>
                    <select id="rem-recurrence" class="form-control">
                        <option value="weekly">Semanal</option>
                        <option value="monthly">Mensual</option>
                        <option value="yearly">Anual</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" id="btn-save-reminder" class="btn btn-primary">
                    <i class="fas fa-save"></i> Guardar Recordatorio
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- JAVASCRIPT                                                   -->
<!-- ============================================================ -->
<script>
const AJAX_URL = 'ajax/reminders.php';

// ── Helpers ──────────────────────────────────────────────────────────────────

/**
 * Muestra un toast de SweetAlert2 en la esquina superior izquierda.
 * @param {'success'|'error'|'warning'|'info'} icon
 * @param {string} message
 */
function showToast(icon, message) {
    Swal.fire({
        toast: true,
        position: 'top-start',
        icon: icon,
        title: message,
        showConfirmButton: false,
        timer: 3500,
        timerProgressBar: true,
    });
}

/**
 * Maneja la respuesta de una petición AJAX.
 * Si hay error, muestra toast + imprime el mensaje completo en consola.
 * @returns {Promise<object|null>} datos JSON o null si hubo error
 */
async function handleResponse(response) {
    let data;
    try {
        data = await response.json();
    } catch (e) {
        const raw = await response.text().catch(() => '(sin respuesta)');
        console.error('[Reminders] Error al parsear respuesta JSON:', raw);
        showToast('error', 'Respuesta inválida del servidor');
        return null;
    }

    if (!data.success) {
        const fullMessage = data.debug
            ? `${data.message} | Debug: ${data.debug}`
            : data.message;
        console.error('[Reminders] Error del servidor:', fullMessage);
        showToast('error', data.message);
        return null;
    }

    return data;
}

// ── Mostrar/Ocultar recurrencia ───────────────────────────────────────────────
$('#recurringCheck').on('change', function () {
    $('#recurrenceDiv').slideToggle(this.checked);
});

// ── Checkear si la lista activa quedó vacía ───────────────────────────────────
function checkEmptyList() {
    if ($('.reminder-item').length === 0) {
        $('#reminders-list').html(`
            <div class="text-center text-muted py-4" id="empty-reminders">
                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                <p>No hay recordatorios pendientes</p>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#reminderModal">
                    Crear recordatorio
                </button>
            </div>
        `);
    }
}

// ── AGREGAR ───────────────────────────────────────────────────────────────────
$('#btn-save-reminder').on('click', async function () {
    const title        = $('#rem-title').val().trim();
    const description  = $('#rem-description').val().trim();
    const reminder_date = $('#rem-date').val();
    const is_recurring  = $('#recurringCheck').is(':checked') ? '1' : '0';
    const recurrence    = $('#rem-recurrence').val();

    if (!title) {
        showToast('warning', 'El título es requerido');
        return;
    }

    const formData = new FormData();
    formData.append('action', 'add');
    formData.append('title', title);
    formData.append('description', description);
    formData.append('reminder_date', reminder_date);
    if (is_recurring === '1') formData.append('is_recurring', '1');
    formData.append('recurrence', recurrence);

    const btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');

    try {
        const response = await fetch(AJAX_URL, { method: 'POST', body: formData });
        const data = await handleResponse(response);
        if (!data) return;

        const rem = data.reminder;
        const today = new Date().setHours(0, 0, 0, 0);
        const remDate = new Date(rem.reminder_date + 'T00:00:00').setHours(0, 0, 0, 0);
        const isOverdue = remDate < today;
        const formattedDate = new Date(rem.reminder_date + 'T00:00:00')
            .toLocaleDateString('es-DO', { day: '2-digit', month: '2-digit', year: 'numeric' });

        const alertClass = isOverdue ? 'alert-danger' : 'alert-info';
        const overdueTag = isOverdue ? '<span class="badge bg-danger ms-2">VENCIDO</span>' : '';
        const descTag    = rem.description
            ? `<br><small class="text-muted"><i class="fas fa-file-alt"></i> ${rem.description}</small>` : '';
        const recurTag   = rem.is_recurring
            ? `<br><span class="badge bg-secondary mt-1"><i class="fas fa-sync-alt"></i> Recurrente (${rem.recurrence_interval})</span>` : '';

        // Remover estado vacío si existe
        $('#empty-reminders').remove();

        const html = `
            <div class="alert ${alertClass} mb-3 shadow-sm reminder-item" id="reminder-${rem.id}">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <div>
                        <strong><i class="fas fa-bell"></i> ${rem.title}</strong><br>
                        <small>
                            <i class="far fa-calendar-alt"></i> ${formattedDate} ${overdueTag}
                        </small>
                        ${descTag}
                        ${recurTag}
                    </div>
                    <div class="mt-2 mt-sm-0">
                        <button class="btn btn-sm btn-success btn-complete" data-id="${rem.id}">
                            <i class="fas fa-check"></i> Completar
                        </button>
                        <button class="btn btn-sm btn-outline-danger btn-delete" data-id="${rem.id}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>`;

        $('#reminders-list').append(html);

        // Limpiar formulario
        $('#rem-title').val('');
        $('#rem-description').val('');
        $('#rem-date').val(new Date().toISOString().split('T')[0]);
        $('#recurringCheck').prop('checked', false);
        $('#recurrenceDiv').hide();

        bootstrap.Modal.getInstance(document.getElementById('reminderModal')).hide();
        showToast('success', data.message);

    } catch (err) {
        console.error('[Reminders] Error de red al agregar:', err);
        showToast('error', 'Error de conexión al guardar el recordatorio');
    } finally {
        btn.prop('disabled', false).html('<i class="fas fa-save"></i> Guardar Recordatorio');
    }
});

// ── COMPLETAR ─────────────────────────────────────────────────────────────────
$(document).on('click', '.btn-complete', function () {
    const id  = $(this).data('id');
    const $el = $(`#reminder-${id}`);

    Swal.fire({
        title: '¿Completar recordatorio?',
        text: 'Marcarás este recordatorio como completado',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, completar',
        cancelButtonText: 'Cancelar',
    }).then(async (result) => {
        if (!result.isConfirmed) return;

        const formData = new FormData();
        formData.append('action', 'complete');
        formData.append('id', id);

        try {
            const response = await fetch(AJAX_URL, { method: 'POST', body: formData });
            const data = await handleResponse(response);
            if (!data) return;

            const rem = data.reminder;
            const formattedDate = new Date(rem.reminder_date + 'T00:00:00')
                .toLocaleDateString('es-DO', { day: '2-digit', month: '2-digit', year: 'numeric' });

            // Agregar al historial en el DOM
            const historyRow = `
                <tr>
                    <td>${rem.title}</td>
                    <td>${formattedDate}</td>
                    <td>${rem.description ?? ''}</td>
                </tr>`;
            $('#history-body').prepend(historyRow);
            $('#history-card').show();

            // Quitar de la lista activa
            $el.fadeOut(300, function () {
                $(this).remove();
                checkEmptyList();
            });

            showToast('success', data.message);

        } catch (err) {
            console.error('[Reminders] Error de red al completar:', err);
            showToast('error', 'Error de conexión al completar el recordatorio');
        }
    });
});

// ── ELIMINAR ──────────────────────────────────────────────────────────────────
$(document).on('click', '.btn-delete', function () {
    const id  = $(this).data('id');
    const $el = $(`#reminder-${id}`);

    Swal.fire({
        title: '¿Eliminar recordatorio?',
        text: 'Esta acción no se puede deshacer',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
    }).then(async (result) => {
        if (!result.isConfirmed) return;

        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', id);

        try {
            const response = await fetch(AJAX_URL, { method: 'POST', body: formData });
            const data = await handleResponse(response);
            if (!data) return;

            $el.fadeOut(300, function () {
                $(this).remove();
                checkEmptyList();
            });

            showToast('success', data.message);

        } catch (err) {
            console.error('[Reminders] Error de red al eliminar:', err);
            showToast('error', 'Error de conexión al eliminar el recordatorio');
        }
    });
});
</script>