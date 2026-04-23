<!-- modules/categories.php - Módulo para gestión de categorías de ingresos y gastos -->

<!-- ============================================ -->
<!-- LISTA DE CATEGORÍAS                         -->
<!-- ============================================ -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-tags"></i> Categorías</h5>
        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#categoryModal">
            <i class="fas fa-plus"></i> Nueva Categoría
        </button>
    </div>
    <div class="card-body">
        <div class="row">
            <!-- Ingresos -->
            <div class="col-md-6">
                <h6 class="text-success"><i class="fas fa-arrow-up"></i> Ingresos</h6>
                <ul class="list-group mb-3" id="list-income">
                    <li class="list-group-item text-center text-muted py-3" id="income-loading">
                        <div class="spinner-border spinner-border-sm text-primary"></div>
                        Cargando...
                    </li>
                </ul>
            </div>

            <!-- Gastos -->
            <div class="col-md-6">
                <h6 class="text-danger"><i class="fas fa-arrow-down"></i> Gastos</h6>
                <ul class="list-group mb-3" id="list-expense">
                    <li class="list-group-item text-center text-muted py-3" id="expense-loading">
                        <div class="spinner-border spinner-border-sm text-primary"></div>
                        Cargando...
                    </li>
                </ul>
            </div>
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
                <div class="mb-3" style="display: none;">
                    <label class="form-label">Categoría Padre <span class="text-muted">(opcional)</span></label>
                    <select id="cat-parent" class="form-control">
                        <option value="">Ninguna</option>
                        <!-- Se rellena por JS -->
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

// Almacén local de categorías (para el select de padre)
let _allCategories = [];

// ============================================
// HELPERS
// ============================================
function showError(message, fullMessage = null) {
    console.error('[Categories Error]', fullMessage || message);
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
    return fetch(CATEGORIES_AJAX_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams(body).toString(),
    }).then(r => r.json());
}

// ============================================
// RENDER: ítem de categoría en la lista
// ============================================
function renderCategoryItem(cat) {
    const parentBadge = cat.parent_name
        ? `<span class="badge bg-light text-muted ms-1" title="Subcategoría de ${escapeHtml(cat.parent_name)}">
               <i class="fas fa-sitemap"></i> ${escapeHtml(cat.parent_name)}
           </span>`
        : '';

    return `
        <li class="list-group-item d-flex justify-content-between align-items-center" id="cat-item-${cat.id}">
            <span>
                ${escapeHtml(cat.name)}
                ${parentBadge}
            </span>
            <button class="btn btn-sm btn-outline-danger py-0"
                    onclick="confirmDeleteCategory(${cat.id}, '${escapeHtml(cat.name)}')"
                    title="Eliminar categoría">
                <i class="fas fa-trash-alt"></i>
            </button>
        </li>
    `;
}

// ============================================
// RENDER: estado vacío de una lista
// ============================================
function renderEmptyItem(type) {
    const label = type === 'income' ? 'ingresos' : 'gastos';
    return `
        <li class="list-group-item text-center text-muted py-3" id="${type}-empty">
            <i class="fas fa-inbox"></i> Sin categorías de ${label}
        </li>
    `;
}

// ============================================
// RENDER: select de categoría padre
// ============================================
function refreshParentSelect() {
    const sel = document.getElementById('cat-parent');
    sel.innerHTML = '<option value="">Ninguna</option>' +
        _allCategories.map(c =>
            `<option value="${c.id}">[${c.type === 'income' ? 'Ingreso' : 'Gasto'}] ${escapeHtml(c.name)}</option>`
        ).join('');
}

// ============================================
// CARGAR CATEGORÍAS
// ============================================
function loadCategories() {
    ajaxPost({ action: 'get_categories' })
        .then(data => {
            if (!data.success) {
                showError(data.message, data.full_message);
                document.getElementById('list-income').innerHTML  = renderEmptyItem('income');
                document.getElementById('list-expense').innerHTML = renderEmptyItem('expense');
                return;
            }

            _allCategories = data.categories;
            refreshParentSelect();

            const incomes  = data.categories.filter(c => c.type === 'income');
            const expenses = data.categories.filter(c => c.type === 'expense');

            document.getElementById('list-income').innerHTML = incomes.length
                ? incomes.map(renderCategoryItem).join('')
                : renderEmptyItem('income');

            document.getElementById('list-expense').innerHTML = expenses.length
                ? expenses.map(renderCategoryItem).join('')
                : renderEmptyItem('expense');
        })
        .catch(err => {
            showError('No se pudieron cargar las categorías. Revisa la consola.', 'Red error: ' + err.message);
            document.getElementById('list-income').innerHTML  = renderEmptyItem('income');
            document.getElementById('list-expense').innerHTML = renderEmptyItem('expense');
        });
}

// ============================================
// CREAR CATEGORÍA
// ============================================
document.getElementById('btn-add-category').addEventListener('click', function () {
    const name     = document.getElementById('cat-name').value.trim();
    const type     = document.getElementById('cat-type').value;
    const parentId = document.getElementById('cat-parent').value;

    if (!name) { showError('El nombre de la categoría es obligatorio.'); return; }

    // Estado cargando
    const btnText    = document.getElementById('btn-cat-text');
    const btnSpinner = document.getElementById('btn-cat-spinner');
    btnText.textContent = 'Creando...';
    btnSpinner.classList.remove('d-none');
    this.disabled = true;

    ajaxPost({ action: 'add_category', name, type, parent_id: parentId })
        .then(data => {
            if (!data.success) { showError(data.message, data.full_message); return; }

            showSuccess(data.message);
            bootstrap.Modal.getInstance(document.getElementById('categoryModal')).hide();

            // Limpiar campos
            document.getElementById('cat-name').value   = '';
            document.getElementById('cat-parent').value = '';

            // Insertar en la lista correcta sin recargar todo
            const cat        = data.category;
            const listId     = cat.type === 'income' ? 'list-income' : 'list-expense';
            const list       = document.getElementById(listId);
            const emptyItem  = document.getElementById(`${cat.type}-empty`);

            if (emptyItem) emptyItem.remove();
            list.insertAdjacentHTML('beforeend', renderCategoryItem(cat));

            // Actualizar almacén local y select de padre
            _allCategories.push(cat);
            _allCategories.sort((a, b) => a.name.localeCompare(b.name));
            refreshParentSelect();
        })
        .catch(err => showError('Error de red al crear la categoría.', err.message))
        .finally(() => {
            btnText.textContent = 'Crear';
            btnSpinner.classList.add('d-none');
            document.getElementById('btn-add-category').disabled = false;
        });
});

// ============================================
// ELIMINAR CATEGORÍA
// ============================================
function confirmDeleteCategory(catId, catName) {
    Swal.fire({
        title: '¿Eliminar categoría?',
        text: `¿Eliminar "${catName}"? No se puede si tiene subcategorías o transacciones.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonText: 'Cancelar',
        confirmButtonText: 'Sí, eliminar',
    }).then(result => {
        if (!result.isConfirmed) return;

        ajaxPost({ action: 'delete_category', cat_id: catId })
            .then(data => {
                if (!data.success) { showError(data.message, data.full_message); return; }

                showSuccess(data.message);

                // Quitar del DOM
                const el = document.getElementById(`cat-item-${data.cat_id}`);
                if (el) {
                    const list = el.closest('ul');
                    el.remove();
                    // Si quedó vacía, mostrar estado vacío
                    if (list && list.querySelectorAll('li').length === 0) {
                        const type = list.id === 'list-income' ? 'income' : 'expense';
                        list.innerHTML = renderEmptyItem(type);
                    }
                }

                // Actualizar almacén local
                _allCategories = _allCategories.filter(c => c.id !== data.cat_id);
                refreshParentSelect();
            })
            .catch(err => showError('Error de red al eliminar la categoría.', err.message));
    });
}

// ============================================
// INICIO
// ============================================
loadCategories();
</script>