<?php
// modules/profile.php
$ajax_url         = 'ajax/profile.php';
$ajax_check_url   = 'ajax/auth.php'; // reutilizamos el check de disponibilidad
?>

<div class="row" id="profile-module">

    <!-- ── Columna izquierda: info de cuenta ── -->
    <div class="col-md-4 mb-3">
        <div class="card">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="fas fa-user-circle"></i> Información de la Cuenta</h6>
            </div>
            <div class="card-body text-center" id="profile-info-card">
                <div class="py-4">
                    <div class="spinner-border text-secondary" role="status"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Columna derecha: formularios ── -->
    <div class="col-md-8 mb-3">

        <!-- Editar Perfil -->
        <div class="card mb-3">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="fas fa-edit"></i> Editar Perfil</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nombre completo</label>
                        <input type="text" id="profile-name" class="form-control" placeholder="Cargando...">
                        <div class="field-feedback" id="fb-name"></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nombre de usuario</label>
                        <input type="text" id="profile-username" class="form-control" placeholder="Cargando..."
                               oninput="ProfileModule.checkProfileField('username')"
                               autocomplete="off" spellcheck="false">
                        <div class="field-feedback" id="fb-username"></div>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Email</label>
                        <input type="email" id="profile-email" class="form-control" placeholder="Cargando..."
                               oninput="ProfileModule.checkProfileField('email')">
                        <div class="field-feedback" id="fb-email"></div>
                    </div>
                    <div class="col-md-12">
                        <button type="button" class="btn btn-primary" id="btn-update-profile"
                                onclick="ProfileModule.updateProfile()">
                            <i class="fas fa-save"></i> Actualizar Perfil
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cambiar Contraseña -->
        <div class="card">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="fas fa-key"></i> Cambiar Contraseña</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label">Contraseña actual</label>
                        <input type="password" id="current-password" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nueva contraseña</label>
                        <input type="password" id="new-password" class="form-control"
                               oninput="ProfileModule.evalNewPassword()">
                        <!-- Barra de fuerza -->
                        <div class="strength-bar mt-1">
                            <div class="strength-seg" id="pseg1"></div>
                            <div class="strength-seg" id="pseg2"></div>
                            <div class="strength-seg" id="pseg3"></div>
                            <div class="strength-seg" id="pseg4"></div>
                        </div>
                        <div class="field-feedback" id="fb-new-password"></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Confirmar nueva contraseña</label>
                        <input type="password" id="confirm-password" class="form-control"
                               oninput="ProfileModule.evalConfirmPassword()">
                        <div class="field-feedback" id="fb-confirm-password"></div>
                    </div>
                    <div class="col-md-12">
                        <button type="button" class="btn btn-warning" id="btn-change-password"
                                onclick="ProfileModule.changePassword()">
                            <i class="fas fa-key"></i> Cambiar Contraseña
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<style>
/* ── Feedback de validación ── */
.field-feedback {
    font-size: 11.5px;
    margin-top: 4px;
    min-height: 16px;
}
.field-feedback.err  { color: #b91c1c; }
.field-feedback.ok   { color: #15803d; }
.field-feedback.info { color: #71717a; }

.form-control.is-field-ok    { border-color: #16a34a; box-shadow: 0 0 0 .2rem rgba(22,163,74,.15); }
.form-control.is-field-error { border-color: #dc2626; box-shadow: 0 0 0 .2rem rgba(220,38,38,.12); }

/* ── Barra de fuerza ── */
.strength-bar {
    display: flex;
    gap: 4px;
}
.strength-seg {
    flex: 1;
    height: 3px;
    border-radius: 99px;
    background: #e4e4e7;
    transition: background .2s;
}
.strength-seg.s-danger { background: #dc2626; }
.strength-seg.s-warn   { background: #f59e0b; }
.strength-seg.s-ok     { background: #16a34a; }
</style>

<script>
const ProfileModule = (() => {

    const AJAX_URL       = '<?= $ajax_url ?>';
    const AJAX_CHECK_URL = '<?= $ajax_check_url ?>';
    const DEBOUNCE_MS    = 600;

    // Valor original cargado del servidor (para saber si cambió)
    let originalUsername = '';
    let originalEmail    = '';
    const debounceT      = {};

    // ─────────────────────────────────────────────
    // UTILIDADES
    // ─────────────────────────────────────────────

    function showError(friendlyMsg, fullMessage) {
        console.error('[ProfileModule ERROR]', fullMessage ?? friendlyMsg);
        Swal.fire({
            title: 'Error', text: friendlyMsg, icon: 'error',
            toast: true, position: 'top-start',
            showConfirmButton: false, timer: 5000, timerProgressBar: true,
        });
    }

    function showSuccess(msg) {
        Swal.fire({
            title: '¡Éxito!', text: msg, icon: 'success',
            toast: true, position: 'top-start',
            showConfirmButton: false, timer: 3500, timerProgressBar: true,
        });
    }

    async function request(action, data = {}) {
        const body = new URLSearchParams({ action, ...data });
        const res  = await fetch(AJAX_URL, {
            method : 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body,
        });
        const raw = await res.text();
        let json;
        try { json = JSON.parse(raw); }
        catch (e) {
            console.error('[ProfileModule] Respuesta no-JSON:\n', raw);
            throw Object.assign(new Error('El servidor devolvió una respuesta inválida.'), { fullMessage: raw });
        }
        if (!json.success) {
            throw Object.assign(new Error(json.message ?? 'Error desconocido.'), {
                fullMessage: json.full_message ?? json.message,
            });
        }
        return json;
    }

    // ─────────────────────────────────────────────
    // FEEDBACK DE CAMPO
    // ─────────────────────────────────────────────

    function setFeedback(inputId, fbId, state, msg) {
        const input = document.getElementById(inputId);
        const fb    = document.getElementById(fbId);
        if (!input || !fb) return;

        input.classList.remove('is-field-ok', 'is-field-error');
        fb.textContent = msg;
        fb.className   = 'field-feedback';

        if (state === 'ok')    { input.classList.add('is-field-ok');    fb.classList.add('ok'); }
        if (state === 'error') { input.classList.add('is-field-error'); fb.classList.add('err'); }
        if (state === 'info')  { fb.classList.add('info'); }
    }

    function clearFeedback(inputId, fbId) {
        const input = document.getElementById(inputId);
        const fb    = document.getElementById(fbId);
        if (input) input.classList.remove('is-field-ok', 'is-field-error');
        if (fb)    { fb.textContent = ''; fb.className = 'field-feedback'; }
    }

    // ─────────────────────────────────────────────
    // CHECK EN TIEMPO REAL (username / email)
    // Reutiliza ajax/auth.php?action=check
    // Si el valor no cambió respecto al original → no verifica
    // ─────────────────────────────────────────────

    async function checkProfileField(field) {
        clearTimeout(debounceT[field]);

        const inputId = field === 'username' ? 'profile-username' : 'profile-email';
        const fbId    = field === 'username' ? 'fb-username'      : 'fb-email';
        const val     = document.getElementById(inputId).value.trim();
        const original = field === 'username' ? originalUsername : originalEmail;

        clearFeedback(inputId, fbId);
        if (!val) return;

        // Si el valor es el mismo que tenía al cargar → no hay conflicto
        if (val.toLowerCase() === original.toLowerCase()) {
            setFeedback(inputId, fbId, 'ok',
                field === 'email' ? 'Tu email actual ✓' : 'Tu usuario actual ✓');
            return;
        }

        // Validación de formato local
        if (field === 'username' && !/^[a-zA-Z0-9_]{3,20}$/.test(val)) {
            setFeedback(inputId, fbId, 'error', 'Solo letras, números y _ · 3–20 caracteres');
            return;
        }
        if (field === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) {
            setFeedback(inputId, fbId, 'error', 'Formato de email inválido');
            return;
        }

        setFeedback(inputId, fbId, 'info', 'Verificando…');

        debounceT[field] = setTimeout(async () => {
            try {
                const res  = await fetch(
                    `${AJAX_CHECK_URL}?action=check&field=${field}&value=${encodeURIComponent(val)}`
                );
                const data = await res.json();

                if (data.exists) {
                    setFeedback(inputId, fbId, 'error',
                        field === 'email'
                            ? 'Este email ya está registrado.'
                            : 'Este usuario ya está en uso.');
                } else {
                    setFeedback(inputId, fbId, 'ok',
                        field === 'email' ? 'Email disponible ✓' : 'Usuario disponible ✓');
                }
            } catch {
                clearFeedback(inputId, fbId);
            }
        }, DEBOUNCE_MS);
    }

    // ─────────────────────────────────────────────
    // VALIDACIÓN DE CONTRASEÑA
    // ─────────────────────────────────────────────

    function evalNewPassword() {
        const val  = document.getElementById('new-password').value;
        const segs = [1,2,3,4].map(i => document.getElementById('pseg' + i));
        let score  = 0;

        if (val.length >= 6)  score++;
        if (val.length >= 10) score++;
        if (/[A-Z]/.test(val) && /[a-z]/.test(val)) score++;
        if (/[\d\W]/.test(val)) score++;

        const cls   = ['', 's-danger', 's-danger', 's-warn', 's-ok'];
        const label = ['', 'Muy débil', 'Débil', 'Aceptable', 'Fuerte'];

        segs.forEach((seg, i) => {
            seg.className = 'strength-seg ' + (i < score ? cls[score] : '');
        });

        if (!val) {
            clearFeedback('new-password', 'fb-new-password');
        } else if (val.length < 6) {
            setFeedback('new-password', 'fb-new-password', 'error', 'Mínimo 6 caracteres');
        } else {
            setFeedback('new-password', 'fb-new-password',
                score >= 3 ? 'ok' : 'error', label[score]);
        }

        // Re-evaluar confirmación si ya tiene algo
        if (document.getElementById('confirm-password').value) evalConfirmPassword();
    }

    function evalConfirmPassword() {
        const newPwd     = document.getElementById('new-password').value;
        const confirmPwd = document.getElementById('confirm-password').value;

        if (!confirmPwd) { clearFeedback('confirm-password', 'fb-confirm-password'); return; }

        if (newPwd === confirmPwd) {
            setFeedback('confirm-password', 'fb-confirm-password', 'ok', 'Las contraseñas coinciden ✓');
        } else {
            setFeedback('confirm-password', 'fb-confirm-password', 'error', 'Las contraseñas no coinciden');
        }
    }

    // ─────────────────────────────────────────────
    // RENDER INFO CARD
    // ─────────────────────────────────────────────

    function renderInfoCard(user) {
        const since = new Date(user.created_at).toLocaleDateString('es-DO', {
            day: '2-digit', month: '2-digit', year: 'numeric'
        });
        document.getElementById('profile-info-card').innerHTML = `
            <i class="fas fa-user-circle fa-5x text-secondary mb-3"></i>
            <h5 id="info-name">${escHtml(user.name)}</h5>
            <p class="text-muted mb-1">
                <i class="fas fa-at"></i> <span id="info-username">${escHtml(user.username)}</span>
            </p>
            <p class="text-muted mb-1">
                <i class="fas fa-envelope"></i> <span id="info-email">${escHtml(user.email)}</span>
            </p>
            <p class="text-muted small">
                <i class="fas fa-calendar-alt"></i> Miembro desde: ${since}
            </p>`;
    }

    function escHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    // ─────────────────────────────────────────────
    // CARGAR PERFIL
    // ─────────────────────────────────────────────

    async function loadProfile() {
        try {
            const data = await request('get_profile');
            const user = data.user;

            document.getElementById('profile-name').value     = user.name;
            document.getElementById('profile-username').value = user.username;
            document.getElementById('profile-email').value    = user.email;

            // Guardar originales para comparar en checkProfileField
            originalUsername = user.username;
            originalEmail    = user.email;

            renderInfoCard(user);
        } catch (err) {
            showError(err.message, err.fullMessage);
        }
    }

    // ─────────────────────────────────────────────
    // ACTUALIZAR PERFIL
    // ─────────────────────────────────────────────

    async function updateProfile() {
        const name     = document.getElementById('profile-name').value.trim();
        const username = document.getElementById('profile-username').value.trim();
        const email    = document.getElementById('profile-email').value.trim();

        // Validaciones locales
        if (!name || !username || !email) {
            showError('Todos los campos son requeridos.');
            return;
        }
        if (!/^[a-zA-Z0-9_]{3,20}$/.test(username)) {
            setFeedback('profile-username', 'fb-username', 'error',
                'Solo letras, números y _ · 3–20 caracteres');
            showError('El nombre de usuario tiene un formato inválido.');
            return;
        }
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            setFeedback('profile-email', 'fb-email', 'error', 'Formato de email inválido');
            showError('El email no tiene un formato válido.');
            return;
        }

        // Bloquear si hay errores de disponibilidad marcados
        if (document.getElementById('profile-username').classList.contains('is-field-error') ||
            document.getElementById('profile-email').classList.contains('is-field-error')) {
            showError('Corrige los errores marcados antes de guardar.');
            return;
        }

        const btn = document.getElementById('btn-update-profile');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Guardando…';

        try {
            const data = await request('update_profile', { name, username, email });
            showSuccess(data.message);

            // Actualizar originales tras guardar correctamente
            originalUsername = data.user.username;
            originalEmail    = data.user.email;

            // Actualizar tarjeta lateral
            const infoName     = document.getElementById('info-name');
            const infoUsername = document.getElementById('info-username');
            const infoEmail    = document.getElementById('info-email');
            if (infoName)     infoName.textContent     = data.user.name;
            if (infoUsername) infoUsername.textContent = data.user.username;
            if (infoEmail)    infoEmail.textContent    = data.user.email;

            // Resetear feedback a "ok" con los nuevos valores
            setFeedback('profile-username', 'fb-username', 'ok', 'Tu usuario actual ✓');
            setFeedback('profile-email',    'fb-email',    'ok', 'Tu email actual ✓');

        } catch (err) {
            showError(err.message, err.fullMessage);
        } finally {
            btn.disabled  = false;
            btn.innerHTML = '<i class="fas fa-save"></i> Actualizar Perfil';
        }
    }

    // ─────────────────────────────────────────────
    // CAMBIAR CONTRASEÑA
    // ─────────────────────────────────────────────

    async function changePassword() {
        const current_password = document.getElementById('current-password').value;
        const new_password     = document.getElementById('new-password').value;
        const confirm_password = document.getElementById('confirm-password').value;

        if (!current_password || !new_password || !confirm_password) {
            showError('Todos los campos son requeridos.');
            return;
        }
        if (new_password.length < 6) {
            setFeedback('new-password', 'fb-new-password', 'error', 'Mínimo 6 caracteres');
            showError('La contraseña nueva debe tener al menos 6 caracteres.');
            return;
        }
        if (new_password !== confirm_password) {
            setFeedback('confirm-password', 'fb-confirm-password', 'error', 'Las contraseñas no coinciden');
            showError('Las contraseñas nuevas no coinciden.');
            return;
        }

        const btn = document.getElementById('btn-change-password');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Guardando…';

        try {
            const data = await request('change_password', {
                current_password, new_password, confirm_password,
            });
            showSuccess(data.message);

            document.getElementById('current-password').value = '';
            document.getElementById('new-password').value     = '';
            document.getElementById('confirm-password').value = '';

            // Limpiar barras y feedbacks
            [1,2,3,4].forEach(i => document.getElementById('pseg' + i).className = 'strength-seg');
            clearFeedback('new-password',     'fb-new-password');
            clearFeedback('confirm-password', 'fb-confirm-password');

        } catch (err) {
            showError(err.message, err.fullMessage);
        } finally {
            btn.disabled  = false;
            btn.innerHTML = '<i class="fas fa-key"></i> Cambiar Contraseña';
        }
    }

    // ─────────────────────────────────────────────
    // INIT
    // ─────────────────────────────────────────────
    loadProfile();

    return { updateProfile, changePassword, loadProfile, checkProfileField, evalNewPassword, evalConfirmPassword };
})();

console.log('[ProfileModule] Módulo cargado correctamente.');
</script>