<!-- modules/categories.php - Módulo para gestión de categorías de ingresos y gastos -->

<style>
/* =============================================
   VARIABLES & BASE (mismo sistema que cards.php)
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
   COLUMNAS DE CATEGORÍAS
   ============================================= */
.cat-columns {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    padding: 0 16px 24px;
}
@media (max-width: 575px) {
    .cat-columns { grid-template-columns: 1fr; }
}

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
.cat-column-title .dot {
    width: 8px; height: 8px;
    border-radius: 50%;
    display: inline-block;
}
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
   ITEMS DE CATEGORÍA
   ============================================= */
.cat-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 14px;
    border-bottom: 1px solid #f5f5f5;
    transition: background 0.12s;
}
.cat-item:last-child { border-bottom: none; }
.cat-item:hover { background: #fafafa; }

.cat-item-left { display: flex; align-items: center; gap: 8px; }
.cat-item-name { font-size: 13px; font-weight: 500; color: #212529; }
.cat-parent-badge {
    font-size: 10px;
    color: var(--muted);
    background: #f0f0f0;
    padding: 1px 7px;
    border-radius: 20px;
    display: inline-flex;
    align-items: center;
    gap: 3px;
}

.cat-delete-btn {
    background: none;
    border: none;
    padding: 4px 7px;
    border-radius: 7px;
    color: #ccc;
    cursor: pointer;
    font-size: 12px;
    transition: color 0.15s, background 0.15s;
    flex-shrink: 0;
}
.cat-delete-btn:hover { color: var(--danger-mid); background: var(--danger-light); }

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

<!-- ============================================ -->
<!-- PAGE HEADER                                 -->
<!-- ============================================ -->
<div class="page-header">
    <h5><i class="fas fa-tags me-2 text-primary"></i>Categorías</h5>
    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#categoryModal">
        <i class="fas fa-plus"></i> Nueva
    </button>
</div>

<!-- ============================================ -->
<!-- SUMMARY STRIP                               -->
<!-- ============================================ -->
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
</div>

<!-- ============================================ -->
<!-- COLUMNAS                                    -->
<!-- ============================================ -->
<div class="cat-section-label">Mis categorías</div>

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

<!-- ============================================ -->
<!-- MODAL: NUEVA CATEGORÍA                      -->
<!-- ============================================ -->
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

<!-- ============================================ -->
<!-- JAVASCRIPT                                  -->
<!-- ============================================ -->
<script>
const CATEGORIES_AJAX_URL = 'ajax/categories.php';

let _allCategories = [];

/* ============================================
   HELPERS
   ============================================ */
function showError(message, fullMessage = null) {
    console.error('[Categories]', fullMessage || message);
    Swal.fire({ toast:true, position:'top-start', icon:'error', title:message,
        showConfirmButton:false, timer:4500, timerProgressBar:true });
}
function showSuccess(message) {
    Swal.fire({ toast:true, position:'top-start', icon:'success', title:message,
        showConfirmButton:false, timer:3000, timerProgressBar:true });
}
function escapeHtml(str) {
    if (str == null) return '';
    return String(str)
        .replace(/&/g,'&amp;').replace(/</g,'&lt;')
        .replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
}
function ajaxPost(body) {
    return fetch(CATEGORIES_AJAX_URL, {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body: new URLSearchParams(body).toString(),
    }).then(r => r.json());
}

/* ============================================
   RENDER: item de categoría
   ============================================ */
function renderCategoryItem(cat) {
    const parentBadge = cat.parent_name
        ? `<span class="cat-parent-badge">
               <i class="fas fa-sitemap" style="font-size:9px;"></i>
               ${escapeHtml(cat.parent_name)}
           </span>`
        : '';
    return `
        <div class="cat-item" id="cat-item-${cat.id}">
            <div class="cat-item-left">
                <span class="cat-item-name">${escapeHtml(cat.name)}</span>
                ${parentBadge}
            </div>
            <button class="cat-delete-btn"
                    onclick="confirmDeleteCategory(${cat.id}, '${escapeHtml(cat.name)}')"
                    title="Eliminar categoría">
                <i class="fas fa-trash-alt"></i>
            </button>
        </div>`;
}

/* ============================================
   RENDER: estado vacío
   ============================================ */
function renderEmpty(type) {
    const label = type === 'income' ? 'ingresos' : 'gastos';
    return `<div class="cat-empty" id="${type}-empty">
        <i class="fas fa-tag"></i>
        Sin categorías de ${label}
    </div>`;
}

/* ============================================
   RENDER: select de categoría padre
   ============================================ */
function refreshParentSelect() {
    const sel = document.getElementById('cat-parent');
    sel.innerHTML = '<option value="">Ninguna</option>' +
        _allCategories.map(c =>
            `<option value="${c.id}">[${c.type==='income'?'Ingreso':'Gasto'}] ${escapeHtml(c.name)}</option>`
        ).join('');
}

/* ============================================
   ACTUALIZAR SUMMARY STRIP Y BADGES
   ============================================ */
function updateSummary(categories) {
    const incomes  = categories.filter(c => c.type === 'income');
    const expenses = categories.filter(c => c.type === 'expense');
    document.getElementById('cat-sum-total').textContent   = categories.length;
    document.getElementById('cat-sum-income').textContent  = incomes.length;
    document.getElementById('cat-sum-expense').textContent = expenses.length;
    document.getElementById('badge-income').textContent    = incomes.length;
    document.getElementById('badge-expense').textContent   = expenses.length;
}

/* ============================================
   CARGAR CATEGORÍAS
   ============================================ */
function loadCategories() {
    ajaxPost({ action:'get_categories' })
        .then(data => {
            if (!data.success) {
                showError(data.message, data.full_message);
                document.getElementById('list-income').innerHTML  = renderEmpty('income');
                document.getElementById('list-expense').innerHTML = renderEmpty('expense');
                return;
            }

            _allCategories = data.categories;
            refreshParentSelect();
            updateSummary(data.categories);

            const incomes  = data.categories.filter(c => c.type === 'income');
            const expenses = data.categories.filter(c => c.type === 'expense');

            document.getElementById('list-income').innerHTML = incomes.length
                ? incomes.map(renderCategoryItem).join('')
                : renderEmpty('income');

            document.getElementById('list-expense').innerHTML = expenses.length
                ? expenses.map(renderCategoryItem).join('')
                : renderEmpty('expense');
        })
        .catch(err => {
            showError('No se pudieron cargar las categorías.', err.message);
            document.getElementById('list-income').innerHTML  = renderEmpty('income');
            document.getElementById('list-expense').innerHTML = renderEmpty('expense');
        });
}

/* ============================================
   CREAR CATEGORÍA
   ============================================ */
document.getElementById('btn-add-category').addEventListener('click', function () {
    const name     = document.getElementById('cat-name').value.trim();
    const type     = document.getElementById('cat-type').value;
    const parentId = document.getElementById('cat-parent').value;
    if (!name) { showError('El nombre de la categoría es obligatorio.'); return; }

    const btnText    = document.getElementById('btn-cat-text');
    const btnSpinner = document.getElementById('btn-cat-spinner');
    btnText.textContent = 'Creando...';
    btnSpinner.classList.remove('d-none');
    this.disabled = true;

    ajaxPost({ action:'add_category', name, type, parent_id:parentId })
        .then(data => {
            if (!data.success) { showError(data.message, data.full_message); return; }

            showSuccess(data.message);
            bootstrap.Modal.getInstance(document.getElementById('categoryModal')).hide();
            document.getElementById('cat-name').value   = '';
            document.getElementById('cat-parent').value = '';

            const cat    = data.category;
            const listId = cat.type === 'income' ? 'list-income' : 'list-expense';
            const list   = document.getElementById(listId);
            const empty  = document.getElementById(`${cat.type}-empty`);
            if (empty) empty.remove();
            list.insertAdjacentHTML('beforeend', renderCategoryItem(cat));

            _allCategories.push(cat);
            _allCategories.sort((a,b) => a.name.localeCompare(b.name));
            refreshParentSelect();
            updateSummary(_allCategories);
        })
        .catch(err => showError('Error de red al crear la categoría.', err.message))
        .finally(() => {
            btnText.textContent = 'Crear';
            btnSpinner.classList.add('d-none');
            document.getElementById('btn-add-category').disabled = false;
        });
});

/* ============================================
   ELIMINAR CATEGORÍA
   ============================================ */
function confirmDeleteCategory(catId, catName) {
    Swal.fire({
        title:'¿Eliminar categoría?',
        text:`¿Eliminar "${catName}"? No se puede si tiene subcategorías o transacciones.`,
        icon:'warning', showCancelButton:true,
        confirmButtonColor:'#d33', cancelButtonText:'Cancelar', confirmButtonText:'Sí, eliminar',
    }).then(result => {
        if (!result.isConfirmed) return;
        ajaxPost({ action:'delete_category', cat_id:catId })
            .then(data => {
                if (!data.success) { showError(data.message, data.full_message); return; }
                showSuccess(data.message);

                const el = document.getElementById(`cat-item-${data.cat_id}`);
                if (el) {
                    const list = el.closest('[id^="list-"]');
                    el.remove();
                    if (list && list.querySelectorAll('.cat-item').length === 0) {
                        const type = list.id === 'list-income' ? 'income' : 'expense';
                        list.innerHTML = renderEmpty(type);
                    }
                }
                _allCategories = _allCategories.filter(c => c.id !== data.cat_id);
                refreshParentSelect();
                updateSummary(_allCategories);
            })
            .catch(err => showError('Error de red al eliminar la categoría.', err.message));
    });
}

/* ============================================
   INICIO
   ============================================ */
loadCategories();
</script>