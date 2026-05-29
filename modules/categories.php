<!-- modules/categories.php -->

<style>
/* =============================================
   VARIABLES & BASE
   ============================================= */
:root {
    --cat-radius: 16px;
    --soft-border: 1px solid #e8e8e8;
    --muted: #6c757d;
    --success-light: #f0faf4;
    --success-mid: #1D9E75;
    --danger-light: #fff1f0;
    --danger-mid: #e24b4a;
    --info-light: #eef4fd;
    --info-mid: #185FA5;
    --disabled-bg: #f8f8f8;
    --disabled-text: #aaaaaa;
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
.cat-summary-strip {
    background: #fff;
    border-bottom: var(--soft-border);
    padding: 10px 16px;
    display: flex;
    gap: 20px;
    overflow-x: auto;
    scrollbar-width: none;
    white-space: nowrap;
}
.cat-summary-strip::-webkit-scrollbar { display: none; }
.cat-summary-item { display: flex; flex-direction: column; }
.cat-summary-item .s-label { font-size: 10px; color: var(--muted); text-transform: uppercase; letter-spacing: 0.04em; }
.cat-summary-item .s-value { font-size: 14px; font-weight: 600; color: #212529; }
.cat-summary-divider { width: 1px; background: #e8e8e8; flex-shrink: 0; align-self: stretch; }

/* =============================================
   SECTION LABEL
   ============================================= */
.cat-section-label {
    font-size: 11px;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: 0.06em;
    padding: 16px 16px 8px;
}

/* =============================================
   COLUMNAS
   ============================================= */
.cat-columns {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    padding: 0 16px 24px;
}
@media (max-width: 575px) { .cat-columns { grid-template-columns: 1fr; } }

.cat-column-panel {
    background: #fff;
    border: var(--soft-border);
    border-radius: var(--cat-radius);
    overflow: hidden;
}
.cat-column-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 14px;
    border-bottom: var(--soft-border);
}
.cat-column-title {
    font-size: 13px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 6px;
}
.cat-column-title .dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; }
.dot-income  { background: var(--success-mid); }
.dot-expense { background: var(--danger-mid); }
.cat-count-badge {
    font-size: 11px;
    font-weight: 500;
    background: #f0f0f0;
    color: var(--muted);
    padding: 1px 8px;
    border-radius: 20px;
}

/* =============================================
   SEPARADOR "DESHABILITADAS"
   ============================================= */
.cat-disabled-divider {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 14px 4px;
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: #bbb;
}
.cat-disabled-divider::before,
.cat-disabled-divider::after {
    content: '';
    flex: 1;
    height: 1px;
    background: #f0f0f0;
}

/* =============================================
   ITEM DE CATEGORÍA
   ============================================= */
.cat-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 14px;
    border-bottom: 1px solid #f5f5f5;
    transition: background 0.12s;
    gap: 8px;
}
.cat-item:last-child { border-bottom: none; }
.cat-item:hover { background: #fafafa; }

/* Deshabilitada */
.cat-item.cat-disabled {
    background: var(--disabled-bg);
    opacity: .75;
}
.cat-item.cat-disabled .cat-item-name {
    color: var(--disabled-text);
    text-decoration: line-through;
    text-decoration-color: #ccc;
}

.cat-item-left { display: flex; align-items: center; gap: 8px; flex: 1; min-width: 0; }
.cat-item-name { font-size: 13px; font-weight: 500; color: #212529; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.cat-parent-badge {
    font-size: 10px;
    color: var(--muted);
    background: #f0f0f0;
    padding: 1px 7px;
    border-radius: 20px;
    display: inline-flex;
    align-items: center;
    gap: 3px;
    flex-shrink: 0;
}

/* Badge "Deshabilitada" */
.cat-off-badge {
    font-size: 9px;
    font-weight: 600;
    color: #bbb;
    background: #f0f0f0;
    border: 1px solid #e0e0e0;
    padding: 1px 6px;
    border-radius: 20px;
    flex-shrink: 0;
    letter-spacing: .04em;
    text-transform: uppercase;
}

/* =============================================
   ACCIONES DEL ÍTEM
   (solo botón toggle; eliminar fue removido)
   ============================================= */
.cat-actions {
    display: flex;
    align-items: center;
    gap: 2px;
    flex-shrink: 0;
}
.cat-action-btn {
    background: none;
    border: none;
    padding: 5px 7px;
    border-radius: 7px;
    cursor: pointer;
    font-size: 12px;
    transition: color 0.15s, background 0.15s;
    color: #ccc;
    line-height: 1;
}
/* Toggle habilitar/deshabilitar */
.cat-action-btn.toggle-btn:hover { color: #f59e0b; background: #fffbeb; }
.cat-item.cat-disabled .cat-action-btn.toggle-btn { color: #10b981; }
.cat-item.cat-disabled .cat-action-btn.toggle-btn:hover { color: #059669; background: #ecfdf5; }

/* =============================================
   EMPTY STATE
   ============================================= */
.cat-empty {
    padding: 28px 16px;
    text-align: center;
    color: var(--muted);
    font-size: 13px;
}
.cat-empty i { font-size: 1.4rem; display: block; margin-bottom: 6px; opacity: 0.25; }

/* =============================================
   SKELETON
   ============================================= */
.cat-skeleton-item {
    height: 40px;
    background: #f5f5f5;
    border-radius: 8px;
    margin: 8px 14px;
    animation: catPulse 1.4s infinite;
}
@keyframes catPulse { 0%,100%{opacity:1} 50%{opacity:.5} }
</style>

<!-- PAGE HEADER -->
<div class="page-header">
    <h5><i class="fas fa-tags me-2 text-primary"></i>Categorías</h5>
    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#categoryModal">
        <i class="fas fa-plus"></i> Nueva
    </button>
</div>

<!-- SUMMARY STRIP -->
<div class="cat-summary-strip">
    <div class="cat-summary-item">
        <span class="s-label">Total</span>
        <span class="s-value" id="cat-sum-total">—</span>
    </div>
    <div class="cat-summary-divider"></div>
    <div class="cat-summary-item">
        <span class="s-label">Ingresos</span>
        <span class="s-value" style="color:var(--success-mid);" id="cat-sum-income">—</span>
    </div>
    <div class="cat-summary-divider"></div>
    <div class="cat-summary-item">
        <span class="s-label">Gastos</span>
        <span class="s-value" style="color:var(--danger-mid);" id="cat-sum-expense">—</span>
    </div>
    <div class="cat-summary-divider"></div>
    <div class="cat-summary-item">
        <span class="s-label">Deshabilitadas</span>
        <span class="s-value" style="color:#bbb;" id="cat-sum-disabled">—</span>
    </div>
</div>

<!-- SECTION LABEL -->
<div class="cat-section-label">Mis categorías</div>

<!-- COLUMNAS -->
<div class="cat-columns">

    <!-- INGRESOS -->
    <div class="cat-column-panel">
        <div class="cat-column-header">
            <span class="cat-column-title">
                <span class="dot dot-income"></span> Ingresos
            </span>
            <span class="cat-count-badge" id="badge-income">—</span>
        </div>
        <div id="list-income">
            <div class="cat-skeleton-item"></div>
            <div class="cat-skeleton-item"></div>
            <div class="cat-skeleton-item" style="width:70%;"></div>
        </div>
    </div>

    <!-- GASTOS -->
    <div class="cat-column-panel">
        <div class="cat-column-header">
            <span class="cat-column-title">
                <span class="dot dot-expense"></span> Gastos
            </span>
            <span class="cat-count-badge" id="badge-expense">—</span>
        </div>
        <div id="list-expense">
            <div class="cat-skeleton-item"></div>
            <div class="cat-skeleton-item"></div>
            <div class="cat-skeleton-item" style="width:60%;"></div>
        </div>
    </div>

</div>

<!-- MODAL: NUEVA CATEGORÍA -->
<div class="modal fade" id="categoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-tag"></i> Nueva Categoría</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Nombre</label>
                    <input type="text" id="cat-name" class="form-control"
                           placeholder="Ej: Alimentación, Salario, Transporte" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Tipo</label>
                    <select id="cat-type" class="form-control" required>
                        <option value="income">Ingreso</option>
                        <option value="expense">Gasto</option>
                    </select>
                </div>
                <div class="mb-3" style="display:none;">
                    <label class="form-label">Categoría Padre <span class="text-muted">(opcional)</span></label>
                    <select id="cat-parent" class="form-control">
                        <option value="">Ninguna</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btn-add-category">
                    <span id="btn-cat-text">Crear</span>
                    <span id="btn-cat-spinner" class="spinner-border spinner-border-sm d-none"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
const CATEGORIES_AJAX_URL = 'ajax/categories.php';

let _allCategories = [];

/* ── Helpers ─────────────────────────────────────────── */
function showCatError(message) {
    Swal.fire({ toast:true, position:'top-start', icon:'error', title:message,
        showConfirmButton:false, timer:4500, timerProgressBar:true });
}
function showCatSuccess(message) {
    Swal.fire({ toast:true, position:'top-start', icon:'success', title:message,
        showConfirmButton:false, timer:3000, timerProgressBar:true });
}
function escCat(str) {
    return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;')
        .replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
}
function ajaxPost(body) {
    return fetch(CATEGORIES_AJAX_URL, {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body: new URLSearchParams(body).toString(),
    }).then(r => r.json());
}

/* ── Render: un ítem ─────────────────────────────────── */
function renderCategoryItem(cat) {
    const isActive    = cat.is_active === true || cat.is_active == 1;
    const disabledCls = isActive ? '' : 'cat-disabled';

    const parentBadge = cat.parent_name
        ? `<span class="cat-parent-badge">
               <i class="fas fa-sitemap" style="font-size:9px;"></i>
               ${escCat(cat.parent_name)}
           </span>`
        : '';

    const offBadge = isActive ? '' : `<span class="cat-off-badge">Off</span>`;

    const toggleIcon  = isActive ? 'fa-eye-slash' : 'fa-eye';
    const toggleTitle = isActive ? 'Deshabilitar' : 'Habilitar';
    const toggleColor = isActive ? '' : 'style="color:#10b981"';

    return `
        <div class="cat-item ${disabledCls}" id="cat-item-${cat.id}">
            <div class="cat-item-left">
                <span class="cat-item-name">${escCat(cat.name)}</span>
                ${parentBadge}
                ${offBadge}
            </div>
            <div class="cat-actions">
                <button class="cat-action-btn toggle-btn"
                        onclick="toggleCategory(${cat.id})"
                        title="${toggleTitle}"
                        ${toggleColor}>
                    <i class="fas ${toggleIcon}"></i>
                </button>
            </div>
        </div>`;
}

/* ── Render: estado vacío ────────────────────────────── */
function renderEmpty(type) {
    const label = type === 'income' ? 'ingresos' : 'gastos';
    return `<div class="cat-empty" id="${type}-empty">
        <i class="fas fa-tag"></i>
        Sin categorías de ${label}
    </div>`;
}

/* ── Render: lista completa de un tipo ───────────────── */
function renderList(type, categories) {
    const active   = categories.filter(c => c.type === type && (c.is_active === true || c.is_active == 1));
    const inactive = categories.filter(c => c.type === type && !(c.is_active === true || c.is_active == 1));

    if (!active.length && !inactive.length) return renderEmpty(type);

    let html = active.map(renderCategoryItem).join('');

    if (inactive.length) {
        html += `<div class="cat-disabled-divider">Deshabilitadas</div>`;
        html += inactive.map(renderCategoryItem).join('');
    }

    return html;
}

/* ── Select de categoría padre (solo activas, no de ajuste) ── */
function refreshParentSelect() {
    const sel    = document.getElementById('cat-parent');
    const active = _allCategories.filter(c => c.is_active === true || c.is_active == 1);
    sel.innerHTML = '<option value="">Ninguna</option>' +
        active.map(c =>
            `<option value="${c.id}">[${c.type === 'income' ? 'Ingreso' : 'Gasto'}] ${escCat(c.name)}</option>`
        ).join('');
}

/* ── Summary strip ───────────────────────────────────── */
function updateSummary(categories) {
    const active   = categories.filter(c => c.is_active === true || c.is_active == 1);
    const inactive = categories.filter(c => !(c.is_active === true || c.is_active == 1));
    const incomes  = active.filter(c => c.type === 'income');
    const expenses = active.filter(c => c.type === 'expense');

    document.getElementById('cat-sum-total').textContent    = active.length;
    document.getElementById('cat-sum-income').textContent   = incomes.length;
    document.getElementById('cat-sum-expense').textContent  = expenses.length;
    document.getElementById('cat-sum-disabled').textContent = inactive.length;
    document.getElementById('badge-income').textContent     = incomes.length;
    document.getElementById('badge-expense').textContent    = expenses.length;
}

/* ── Cargar categorías ───────────────────────────────── */
function loadCategories() {
    ajaxPost({ action: 'get_categories' })
        .then(data => {
            if (!data.success) {
                showCatError(data.message);
                document.getElementById('list-income').innerHTML  = renderEmpty('income');
                document.getElementById('list-expense').innerHTML = renderEmpty('expense');
                return;
            }
            // El servidor ya filtra is_adjustment=1; solo llegan las del usuario
            _allCategories = data.categories;
            refreshParentSelect();
            updateSummary(data.categories);
            document.getElementById('list-income').innerHTML  = renderList('income',  data.categories);
            document.getElementById('list-expense').innerHTML = renderList('expense', data.categories);
        })
        .catch(() => {
            showCatError('No se pudieron cargar las categorías.');
            document.getElementById('list-income').innerHTML  = renderEmpty('income');
            document.getElementById('list-expense').innerHTML = renderEmpty('expense');
        });
}

/* ── Crear categoría ─────────────────────────────────── */
document.getElementById('btn-add-category').addEventListener('click', function () {
    const name     = document.getElementById('cat-name').value.trim();
    const type     = document.getElementById('cat-type').value;
    const parentId = document.getElementById('cat-parent').value;
    if (!name) { showCatError('El nombre de la categoría es obligatorio.'); return; }

    const btnText    = document.getElementById('btn-cat-text');
    const btnSpinner = document.getElementById('btn-cat-spinner');
    btnText.textContent = 'Creando...';
    btnSpinner.classList.remove('d-none');
    this.disabled = true;

    ajaxPost({ action: 'add_category', name, type, parent_id: parentId })
        .then(data => {
            if (!data.success) { showCatError(data.message); return; }
            showCatSuccess(data.message);
            bootstrap.Modal.getInstance(document.getElementById('categoryModal')).hide();
            document.getElementById('cat-name').value   = '';
            document.getElementById('cat-parent').value = '';

            _allCategories.push(data.category);
            _allCategories.sort((a, b) => a.name.localeCompare(b.name));
            refreshParentSelect();
            updateSummary(_allCategories);

            const listId = data.category.type === 'income' ? 'list-income' : 'list-expense';
            document.getElementById(listId).innerHTML = renderList(data.category.type, _allCategories);
        })
        .catch(() => showCatError('Error de red al crear la categoría.'))
        .finally(() => {
            btnText.textContent = 'Crear';
            btnSpinner.classList.add('d-none');
            document.getElementById('btn-add-category').disabled = false;
        });
});

/* ── Toggle habilitar/deshabilitar ───────────────────── */
function toggleCategory(catId) {
    const cat = _allCategories.find(c => c.id == catId);
    if (!cat) return;

    const isActive = cat.is_active === true || cat.is_active == 1;
    const action   = isActive ? 'deshabilitar' : 'habilitar';
    const icon     = isActive ? '🚫' : '✅';

    Swal.fire({
        title: `¿${action.charAt(0).toUpperCase() + action.slice(1)} categoría?`,
        html: `${icon} <strong>${escCat(cat.name)}</strong>${isActive
            ? '<br><small style="color:#6b7280">No aparecerá en los formularios de nuevas transacciones, pero el historial se conserva.</small>'
            : '<br><small style="color:#6b7280">Volverá a estar disponible en los formularios.</small>'}`,
        icon: isActive ? 'warning' : 'question',
        showCancelButton: true,
        confirmButtonColor: isActive ? '#f59e0b' : '#10b981',
        cancelButtonText: 'Cancelar',
        confirmButtonText: isActive ? 'Sí, deshabilitar' : 'Sí, habilitar',
    }).then(result => {
        if (!result.isConfirmed) return;

        ajaxPost({ action: 'toggle_category', cat_id: catId })
            .then(data => {
                if (!data.success) { showCatError(data.message); return; }
                showCatSuccess(data.message);

                const idx = _allCategories.findIndex(c => c.id == catId);
                if (idx !== -1) _allCategories[idx].is_active = data.is_active;

                if (!data.is_active) {
                    _allCategories.forEach(c => {
                        if (c.parent_id == catId) c.is_active = false;
                    });
                }

                refreshParentSelect();
                updateSummary(_allCategories);
                document.getElementById('list-income').innerHTML  = renderList('income',  _allCategories);
                document.getElementById('list-expense').innerHTML = renderList('expense', _allCategories);
            })
            .catch(() => showCatError('Error de red al cambiar el estado.'));
    });
}

/* ── Init ────────────────────────────────────────────── */
loadCategories();
</script>