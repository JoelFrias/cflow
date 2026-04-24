<?php
// modules/reminders.php

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM reminders WHERE user_id = ? AND completed = 0 ORDER BY reminder_date ASC");
$stmt->execute([$user_id]);
$reminders = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM reminders WHERE user_id = ? AND completed = 1 ORDER BY reminder_date DESC LIMIT 5");
$stmt->execute([$user_id]);
$completed_reminders = $stmt->fetchAll();

$total   = count($reminders);
$overdue = 0;
foreach ($reminders as $r) { if (strtotime($r['reminder_date']) < time()) $overdue++; }
?>

<style>
/* =============================================
   VARIABLES & BASE (mismo sistema que cards/categories)
   ============================================= */
:root {
    --rm-radius: 16px;
    --soft-border: 1px solid #e8e8e8;
    --muted: #6c757d;
    --success-light: #f0faf4;
    --success-mid: #1D9E75;
    --danger-light: #fff1f0;
    --danger-mid: #e24b4a;
    --warn-light: #fffbeb;
    --warn-mid: #BA7517;
    --info-light: #eef4fd;
    --info-mid: #185FA5;
}

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
.rm-summary-strip {
    background: #fff;
    border-bottom: var(--soft-border);
    padding: 10px 16px;
    display: flex;
    gap: 20px;
    overflow-x: auto;
    scrollbar-width: none;
    white-space: nowrap;
}
.rm-summary-strip::-webkit-scrollbar { display: none; }
.rm-sum-item { display: flex; flex-direction: column; }
.rm-sum-item .s-label { font-size: 10px; color: var(--muted); text-transform: uppercase; letter-spacing: 0.04em; }
.rm-sum-item .s-value { font-size: 14px; font-weight: 600; color: #212529; }
.rm-sum-item .s-value.is-danger { color: var(--danger-mid); }
.rm-sum-item .s-value.is-ok    { color: var(--success-mid); }
.rm-sum-divider { width: 1px; background: #e8e8e8; flex-shrink: 0; align-self: stretch; }

/* =============================================
   SECTION LABEL
   ============================================= */
.rm-section-label {
    font-size: 11px;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: 0.06em;
    padding: 16px 16px 8px;
}

/* =============================================
   CONTENEDOR PRINCIPAL
   ============================================= */
.rm-body { padding: 0 16px 24px; display: flex; flex-direction: column; gap: 8px; }

/* =============================================
   ITEM DE RECORDATORIO
   ============================================= */
.rm-item {
    background: #fff;
    border: var(--soft-border);
    border-radius: var(--rm-radius);
    padding: 13px 14px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    position: relative;
    overflow: hidden;
    transition: box-shadow 0.15s;
}
.rm-item:hover { box-shadow: 0 2px 10px rgba(0,0,0,.05); }
.rm-item::before {
    content: '';
    position: absolute;
    left: 0; top: 0; bottom: 0;
    width: 3px;
}
.rm-active::before  { background: var(--info-mid); }
.rm-overdue::before { background: var(--danger-mid); }

.rm-item-left { display: flex; align-items: center; gap: 10px; flex: 1; min-width: 0; }

.rm-icon-wrap {
    width: 34px; height: 34px;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 15px; flex-shrink: 0;
}
.rm-icon-active  { background: var(--info-light); }
.rm-icon-overdue { background: var(--danger-light); }

.rm-item-info { flex: 1; min-width: 0; }
.rm-item-title {
    font-size: 13px; font-weight: 600; color: #212529;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    margin-bottom: 3px;
}
.rm-item-meta {
    display: flex; align-items: center; gap: 6px; flex-wrap: wrap;
    font-size: 11px; color: var(--muted);
}
.rm-item-desc {
    font-size: 11px; color: #aaa; margin-top: 2px;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}

.rm-pill {
    font-size: 10px; font-weight: 600;
    padding: 1px 7px; border-radius: 20px;
    display: inline-flex; align-items: center;
}
.rm-pill-overdue   { background: var(--danger-light); color: var(--danger-mid); }
.rm-pill-recurring { background: #ede9fe; color: #5b21b6; }

/* =============================================
   ACCIONES DEL ITEM
   ============================================= */
.rm-item-actions { display: flex; gap: 5px; flex-shrink: 0; }

.rm-btn-complete {
    height: 28px; padding: 0 10px;
    border-radius: 8px;
    border: 1px solid #86efac;
    background: var(--success-light);
    color: var(--success-mid);
    font-size: 11px; font-weight: 500;
    cursor: pointer; white-space: nowrap;
    display: flex; align-items: center; gap: 4px;
    transition: background 0.12s;
}
.rm-btn-complete:hover { background: #dcfce7; }

.rm-btn-del {
    width: 28px; height: 28px;
    border-radius: 8px;
    border: var(--soft-border);
    background: #f9fafb;
    color: #ccc; font-size: 11px;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: all 0.12s;
}
.rm-btn-del:hover { background: var(--danger-light); color: var(--danger-mid); border-color: #fecaca; }

/* =============================================
   EMPTY STATE
   ============================================= */
.rm-empty {
    background: #fff;
    border: var(--soft-border);
    border-radius: var(--rm-radius);
    text-align: center;
    padding: 40px 16px;
    color: var(--muted);
}
.rm-empty i { font-size: 2rem; display: block; margin-bottom: 10px; opacity: 0.2; }
.rm-empty p { font-size: 13px; margin-bottom: 14px; }

/* =============================================
   HISTORIAL DE COMPLETADOS
   ============================================= */
.rm-history-panel {
    background: #fff;
    border: var(--soft-border);
    border-radius: var(--rm-radius);
    overflow: hidden;
    margin: 0 16px 24px;
}
.rm-history-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 11px 14px;
    border-bottom: var(--soft-border);
}
.rm-history-title {
    font-size: 11px; font-weight: 600; color: var(--muted);
    text-transform: uppercase; letter-spacing: 0.05em;
}
.rm-hist-item {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 14px;
    border-bottom: 1px solid #f5f5f5;
}
.rm-hist-item:last-child { border-bottom: none; }
.rm-hist-dot {
    width: 26px; height: 26px; border-radius: 8px;
    background: var(--success-light); color: var(--success-mid);
    display: flex; align-items: center; justify-content: center;
    font-size: 11px; font-weight: 700; flex-shrink: 0;
}
.rm-hist-info { flex: 1; min-width: 0; }
.rm-hist-title {
    font-size: 12px; font-weight: 500; color: #374151;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.rm-hist-date { font-size: 10px; color: var(--muted); margin-top: 1px; }
.rm-hist-desc {
    font-size: 11px; color: #aaa;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    max-width: 180px; flex-shrink: 0;
}

/* =============================================
   MODAL
   ============================================= */
.rm-form-label {
    display: block;
    font-size: 11px !important; font-weight: 600 !important;
    color: var(--muted) !important;
    text-transform: uppercase; letter-spacing: 0.04em;
    margin-bottom: 5px !important;
}
.rm-toggle-row {
    display: flex; align-items: center; justify-content: space-between;
    background: #f9fafb; border: var(--soft-border);
    border-radius: 8px; padding: 12px 14px;
}
.rm-switch { position: relative; width: 40px; height: 22px; flex-shrink: 0; }
.rm-switch input { opacity: 0; width: 0; height: 0; }
.rm-switch-track {
    position: absolute; inset: 0;
    background: #d1d5db; border-radius: 99px; cursor: pointer;
    transition: background 0.2s;
}
.rm-switch-track::before {
    content: ''; position: absolute;
    width: 16px; height: 16px; left: 3px; top: 3px;
    background: #fff; border-radius: 50%;
    transition: transform 0.2s; box-shadow: 0 1px 3px rgba(0,0,0,.2);
}
.rm-switch input:checked + .rm-switch-track { background: #374151; }
.rm-switch input:checked + .rm-switch-track::before { transform: translateX(18px); }

/* =============================================
   ANIMACIÓN AL ELIMINAR
   ============================================= */
.rm-item.removing {
    opacity: 0; transform: translateX(20px);
    transition: opacity 0.22s, transform 0.22s;
}

/* =============================================
   SKELETON
   ============================================= */
.rm-skeleton {
    background: #fff; border: var(--soft-border);
    border-radius: var(--rm-radius); padding: 13px 14px;
    display: flex; gap: 10px; align-items: center;
}
.rm-sk-icon { width: 34px; height: 34px; border-radius: 10px; background: #f0f0f0; flex-shrink: 0; animation: rmPulse 1.4s infinite; }
.rm-sk-lines { flex: 1; }
.rm-sk-line { height: 10px; background: #f0f0f0; border-radius: 5px; margin-bottom: 6px; animation: rmPulse 1.4s infinite; }
.rm-sk-line:last-child { width: 55%; margin-bottom: 0; }
@keyframes rmPulse { 0%,100%{opacity:1} 50%{opacity:.5} }
</style>

<!-- ============================================ -->
<!-- PAGE HEADER                                 -->
<!-- ============================================ -->
<div class="page-header">
    <h5><i class="fas fa-bell me-2 text-primary"></i>Recordatorios</h5>
    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#reminderModal">
        <i class="fas fa-plus"></i> Nuevo
    </button>
</div>

<!-- ============================================ -->
<!-- SUMMARY STRIP                               -->
<!-- ============================================ -->
<div class="rm-summary-strip">
    <div class="rm-sum-item">
        <span class="s-label">Activos</span>
        <span class="s-value" id="sum-active"><?= $total ?></span>
    </div>
    <div class="rm-sum-divider"></div>
    <div class="rm-sum-item">
        <span class="s-label">Vencidos</span>
        <span class="s-value <?= $overdue > 0 ? 'is-danger' : 'is-ok' ?>" id="sum-overdue"><?= $overdue ?></span>
    </div>
    <div class="rm-sum-divider"></div>
    <div class="rm-sum-item">
        <span class="s-label">Al día</span>
        <span class="s-value is-ok" id="sum-ok"><?= $total - $overdue ?></span>
    </div>
</div>

<!-- ============================================ -->
<!-- LISTA DE RECORDATORIOS ACTIVOS              -->
<!-- ============================================ -->
<div class="rm-section-label">Pendientes</div>

<div class="rm-body" id="rm-active-body">
    <?php if (empty($reminders)): ?>
    <div class="rm-empty" id="rm-empty">
        <i class="fas fa-bell-slash"></i>
        <p>No hay recordatorios pendientes</p>
        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#reminderModal">
            <i class="fas fa-plus"></i> Crear recordatorio
        </button>
    </div>
    <?php else: ?>
        <?php foreach ($reminders as $rem):
            $is_overdue = strtotime($rem['reminder_date']) < time();
            $date_fmt   = date('d/m/Y', strtotime($rem['reminder_date']));
        ?>
        <div class="rm-item <?= $is_overdue ? 'rm-overdue' : 'rm-active' ?>" id="reminder-<?= $rem['id'] ?>">
            <div class="rm-item-left">
                <div class="rm-icon-wrap <?= $is_overdue ? 'rm-icon-overdue' : 'rm-icon-active' ?>">
                    <i class="fas fa-bell" style="color:<?= $is_overdue ? 'var(--danger-mid)' : 'var(--info-mid)' ?>;font-size:14px;"></i>
                </div>
                <div class="rm-item-info">
                    <div class="rm-item-title"><?= htmlspecialchars($rem['title']) ?></div>
                    <div class="rm-item-meta">
                        <span><i class="fas fa-calendar-alt" style="font-size:9px;margin-right:2px;"></i><?= $date_fmt ?></span>
                        <?php if ($is_overdue): ?>
                            <span class="rm-pill rm-pill-overdue">Vencido</span>
                        <?php endif; ?>
                        <?php if ($rem['is_recurring']): ?>
                            <span class="rm-pill rm-pill-recurring">↻ <?= htmlspecialchars($rem['recurrence_interval']) ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($rem['description'])): ?>
                    <div class="rm-item-desc"><?= htmlspecialchars($rem['description']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="rm-item-actions">
                <button class="rm-btn-complete btn-complete" data-id="<?= $rem['id'] ?>">
                    <i class="fas fa-check" style="font-size:10px;"></i> Completar
                </button>
                <button class="rm-btn-del btn-delete" data-id="<?= $rem['id'] ?>" title="Eliminar">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- ============================================ -->
<!-- HISTORIAL DE COMPLETADOS                    -->
<!-- ============================================ -->
<?php if (!empty($completed_reminders)): ?>
<div class="rm-section-label">Completados recientes</div>
<div class="rm-history-panel" id="history-card">
    <div id="history-body">
        <?php foreach ($completed_reminders as $rem): ?>
        <div class="rm-hist-item">
            <div class="rm-hist-dot"><i class="fas fa-check" style="font-size:10px;"></i></div>
            <div class="rm-hist-info">
                <div class="rm-hist-title"><?= htmlspecialchars($rem['title']) ?></div>
                <div class="rm-hist-date"><?= date('d/m/Y', strtotime($rem['reminder_date'])) ?></div>
            </div>
            <?php if (!empty($rem['description'])): ?>
            <div class="rm-hist-desc"><?= htmlspecialchars($rem['description']) ?></div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php else: ?>
<div class="rm-history-panel" id="history-card" style="display:none;">
    <div id="history-body"></div>
</div>
<?php endif; ?>

<!-- ============================================ -->
<!-- MODAL: NUEVO RECORDATORIO                   -->
<!-- ============================================ -->
<div class="modal fade" id="reminderModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-bell"></i> Nuevo Recordatorio</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="rm-form-label">Título</label>
                    <input type="text" id="rem-title" class="form-control"
                           placeholder="Ej: Pago de tarjeta, Renovar seguro…">
                </div>
                <div class="mb-3">
                    <label class="rm-form-label">Descripción <span style="font-weight:400;color:#aaa;">(opcional)</span></label>
                    <textarea id="rem-description" class="form-control" rows="2"
                              placeholder="Detalles adicionales…"></textarea>
                </div>
                <div class="mb-3">
                    <label class="rm-form-label">Fecha</label>
                    <input type="date" id="rem-date" class="form-control" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="mb-3">
                    <div class="rm-toggle-row">
                        <div>
                            <div style="font-size:13px;font-weight:500;color:#374151;">Recordatorio recurrente</div>
                            <div style="font-size:11px;color:#aaa;">Se repetirá automáticamente</div>
                        </div>
                        <label class="rm-switch">
                            <input type="checkbox" id="recurringCheck">
                            <span class="rm-switch-track"></span>
                        </label>
                    </div>
                </div>
                <div id="recurrenceDiv" style="display:none;">
                    <label class="rm-form-label">Intervalo</label>
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
                    <span id="btn-save-text">Guardar</span>
                    <span id="btn-save-spinner" class="spinner-border spinner-border-sm d-none"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- JAVASCRIPT                                   -->
<!-- ============================================ -->
<script>
const AJAX_URL = 'ajax/reminders.php';

/* ============================================
   HELPERS
   ============================================ */
function rmEsc(str) {
    return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function showToast(icon, msg) {
    Swal.fire({ toast:true, position:'top-start', icon, title:msg,
        showConfirmButton:false, timer:3500, timerProgressBar:true });
}
async function rmHandleResponse(res) {
    let data;
    try { data = await res.json(); }
    catch (e) {
        showToast('error', 'Respuesta inválida del servidor'); return null;
    }
    if (!data.success) {
        console.error('[Reminders]', data.message);
        showToast('error', data.message); return null;
    }
    return data;
}

/* ============================================
   ACTUALIZAR SUMMARY STRIP
   ============================================ */
function rmUpdateSummary() {
    const items   = document.querySelectorAll('.rm-item');
    const overdue = document.querySelectorAll('.rm-overdue');
    const ok      = items.length - overdue.length;

    document.getElementById('sum-active').textContent  = items.length;
    document.getElementById('sum-overdue').textContent = overdue.length;
    document.getElementById('sum-ok').textContent      = ok;

    const sumOverEl = document.getElementById('sum-overdue');
    sumOverEl.classList.toggle('is-danger', overdue.length > 0);
    sumOverEl.classList.toggle('is-ok', overdue.length === 0);
}

/* ============================================
   COMPROBAR VACÍO
   ============================================ */
function rmCheckEmpty() {
    const body = document.getElementById('rm-active-body');
    if (!document.querySelectorAll('.rm-item').length) {
        body.innerHTML = `
            <div class="rm-empty" id="rm-empty">
                <i class="fas fa-bell-slash"></i>
                <p>No hay recordatorios pendientes</p>
                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#reminderModal">
                    <i class="fas fa-plus"></i> Crear recordatorio
                </button>
            </div>`;
    }
    rmUpdateSummary();
}

/* ============================================
   TOGGLE RECURRENCIA
   ============================================ */
document.getElementById('recurringCheck').addEventListener('change', function () {
    document.getElementById('recurrenceDiv').style.display = this.checked ? 'block' : 'none';
});

/* ============================================
   RENDER ITEM ACTIVO
   ============================================ */
function rmRenderItem(rem) {
    const today     = new Date().setHours(0,0,0,0);
    const remDate   = new Date(rem.reminder_date + 'T00:00:00').setHours(0,0,0,0);
    const isOverdue = remDate < today;
    const dateFmt   = new Date(rem.reminder_date + 'T00:00:00')
        .toLocaleDateString('es-DO', { day:'2-digit', month:'2-digit', year:'numeric' });

    const overduePill = isOverdue ? '<span class="rm-pill rm-pill-overdue">Vencido</span>' : '';
    const recurPill   = rem.is_recurring
        ? `<span class="rm-pill rm-pill-recurring">↻ ${rmEsc(rem.recurrence_interval)}</span>` : '';
    const descHtml = rem.description
        ? `<div class="rm-item-desc">${rmEsc(rem.description)}</div>` : '';
    const iconColor = isOverdue ? 'var(--danger-mid)' : 'var(--info-mid)';
    const iconBg    = isOverdue ? 'rm-icon-overdue' : 'rm-icon-active';

    const el = document.createElement('div');
    el.className = `rm-item ${isOverdue ? 'rm-overdue' : 'rm-active'}`;
    el.id = `reminder-${rem.id}`;
    el.innerHTML = `
        <div class="rm-item-left">
            <div class="rm-icon-wrap ${iconBg}">
                <i class="fas fa-bell" style="color:${iconColor};font-size:14px;"></i>
            </div>
            <div class="rm-item-info">
                <div class="rm-item-title">${rmEsc(rem.title)}</div>
                <div class="rm-item-meta">
                    <span><i class="fas fa-calendar-alt" style="font-size:9px;margin-right:2px;"></i>${dateFmt}</span>
                    ${overduePill}${recurPill}
                </div>
                ${descHtml}
            </div>
        </div>
        <div class="rm-item-actions">
            <button class="rm-btn-complete btn-complete" data-id="${rem.id}">
                <i class="fas fa-check" style="font-size:10px;"></i> Completar
            </button>
            <button class="rm-btn-del btn-delete" data-id="${rem.id}" title="Eliminar">
                <i class="fas fa-times"></i>
            </button>
        </div>`;
    return el;
}

/* ============================================
   RENDER ITEM HISTORIAL
   ============================================ */
function rmRenderHistItem(rem) {
    const dateFmt = new Date(rem.reminder_date + 'T00:00:00')
        .toLocaleDateString('es-DO', { day:'2-digit', month:'2-digit', year:'numeric' });
    const descHtml = rem.description
        ? `<div class="rm-hist-desc">${rmEsc(rem.description)}</div>` : '';
    const el = document.createElement('div');
    el.className = 'rm-hist-item';
    el.innerHTML = `
        <div class="rm-hist-dot"><i class="fas fa-check" style="font-size:10px;"></i></div>
        <div class="rm-hist-info">
            <div class="rm-hist-title">${rmEsc(rem.title)}</div>
            <div class="rm-hist-date">${dateFmt}</div>
        </div>
        ${descHtml}`;
    return el;
}

/* ============================================
   GUARDAR RECORDATORIO
   ============================================ */
document.getElementById('btn-save-reminder').addEventListener('click', async function () {
    const title       = document.getElementById('rem-title').value.trim();
    const description = document.getElementById('rem-description').value.trim();
    const remDate     = document.getElementById('rem-date').value;
    const isRecurring = document.getElementById('recurringCheck').checked;
    const recurrence  = document.getElementById('rem-recurrence').value;

    if (!title) { showToast('warning', 'El título es requerido'); return; }

    const txt = document.getElementById('btn-save-text');
    const spn = document.getElementById('btn-save-spinner');
    txt.textContent = 'Guardando…'; spn.classList.remove('d-none'); this.disabled = true;

    const fd = new FormData();
    fd.append('action', 'add');
    fd.append('title', title);
    fd.append('description', description);
    fd.append('reminder_date', remDate);
    if (isRecurring) { fd.append('is_recurring', '1'); fd.append('recurrence', recurrence); }

    try {
        const res  = await fetch(AJAX_URL, { method:'POST', body:fd });
        const data = await rmHandleResponse(res);
        if (!data) return;

        // Quitar empty state si existe
        document.getElementById('rm-empty')?.remove();

        const body  = document.getElementById('rm-active-body');
        const newEl = rmRenderItem(data.reminder);
        body.insertBefore(newEl, body.firstChild);

        // Limpiar modal
        document.getElementById('rem-title').value = '';
        document.getElementById('rem-description').value = '';
        document.getElementById('rem-date').value = new Date().toISOString().split('T')[0];
        document.getElementById('recurringCheck').checked = false;
        document.getElementById('recurrenceDiv').style.display = 'none';

        bootstrap.Modal.getInstance(document.getElementById('reminderModal')).hide();
        showToast('success', data.message);
        rmUpdateSummary();
    } catch (err) {
        console.error('[Reminders]', err);
        showToast('error', 'Error de conexión al guardar');
    } finally {
        txt.textContent = 'Guardar'; spn.classList.add('d-none');
        document.getElementById('btn-save-reminder').disabled = false;
    }
});

/* ============================================
   COMPLETAR
   ============================================ */
document.addEventListener('click', function (e) {
    const btn = e.target.closest('.btn-complete');
    if (!btn) return;
    const id = btn.dataset.id;

    Swal.fire({
        title:'¿Completar recordatorio?',
        text:'Se moverá al historial de completados',
        icon:'question', showCancelButton:true,
        confirmButtonColor:'#1D9E75', cancelButtonColor:'#aaa',
        confirmButtonText:'Sí, completar', cancelButtonText:'Cancelar',
    }).then(async result => {
        if (!result.isConfirmed) return;
        const fd = new FormData();
        fd.append('action', 'complete'); fd.append('id', id);
        try {
            const res  = await fetch(AJAX_URL, { method:'POST', body:fd });
            const data = await rmHandleResponse(res);
            if (!data) return;

            const el = document.getElementById(`reminder-${id}`);
            if (el) {
                el.classList.add('removing');
                setTimeout(() => { el.remove(); rmCheckEmpty(); }, 240);
            }

            const histCard = document.getElementById('history-card');
            const histBody = document.getElementById('history-body');
            histCard.style.display = 'block';

            // Mostrar label si estaba oculto
            let secLabel = document.querySelector('.rm-section-label:last-of-type');
            histBody.insertBefore(rmRenderHistItem(data.reminder), histBody.firstChild);

            // Mantener solo 5 en historial
            const histItems = histBody.querySelectorAll('.rm-hist-item');
            if (histItems.length > 5) histItems[histItems.length - 1].remove();

            showToast('success', data.message);
        } catch (err) {
            showToast('error', 'Error de conexión al completar');
        }
    });
});

/* ============================================
   ELIMINAR
   ============================================ */
document.addEventListener('click', function (e) {
    const btn = e.target.closest('.btn-delete');
    if (!btn) return;
    const id = btn.dataset.id;

    Swal.fire({
        title:'¿Eliminar recordatorio?',
        text:'Esta acción no se puede deshacer',
        icon:'warning', showCancelButton:true,
        confirmButtonColor:'#e24b4a', cancelButtonColor:'#aaa',
        confirmButtonText:'Sí, eliminar', cancelButtonText:'Cancelar',
    }).then(async result => {
        if (!result.isConfirmed) return;
        const fd = new FormData();
        fd.append('action', 'delete'); fd.append('id', id);
        try {
            const res  = await fetch(AJAX_URL, { method:'POST', body:fd });
            const data = await rmHandleResponse(res);
            if (!data) return;

            const el = document.getElementById(`reminder-${id}`);
            if (el) {
                el.classList.add('removing');
                setTimeout(() => { el.remove(); rmCheckEmpty(); }, 240);
            }
            showToast('success', data.message);
        } catch (err) {
            showToast('error', 'Error de conexión al eliminar');
        }
    });
});
</script>