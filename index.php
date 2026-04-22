<?php
// index.php — Punto de entrada
// Solo decide qué mostrar: dashboard (si hay sesión) o la pantalla de auth.
// No procesa formularios. No genera JSON. Solo renderiza.

require_once 'config/database.php';

if (isLoggedIn()) {
    include 'layout/main_layout.php';
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CFlow - Finanzas Personales</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <link href="assets/css/auth.css" rel="stylesheet">
    <link rel="icon" href="assets/img/favicon.ico" type="image/x-icon">

    <style>
        /* ── Validación en tiempo real ── */
        .auth-field input.field-error {
            border-color: #dc2626;
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.08);
        }
        .auth-field input.field-ok {
            border-color: #16a34a;
            box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.07);
        }
        .field-msg {
            font-size: 11.5px;
            margin-top: 5px;
            min-height: 16px;
            color: #71717a;
        }
        .field-msg.err { color: #b91c1c; }
        .field-msg.ok  { color: #15803d; }

        /* ── Barra de fuerza de contraseña ── */
        .strength-bar {
            display: flex;
            gap: 4px;
            margin-top: 7px;
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

        /* ── Spinner dentro del botón ── */
        .auth-btn .btn-spinner {
            display: none;
            width: 16px; height: 16px;
            border: 2px solid rgba(255,255,255,.35);
            border-top-color: #fff;
            border-radius: 50%;
            animation: cfSpin .7s linear infinite;
            margin: 0 auto;
        }
        .auth-btn.loading .btn-label   { display: none; }
        .auth-btn.loading .btn-spinner { display: block; }
        .auth-btn:disabled { opacity: .55; cursor: not-allowed; }
        @keyframes cfSpin { to { transform: rotate(360deg); } }

        /* ── Bloqueo por intentos ── */
        .lockout-box {
            display: none;
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
            border-radius: 9px;
            padding: 11px 13px;
            font-size: 13.5px;
            margin-bottom: 1rem;
            text-align: center;
        }
        .lockout-box strong {
            font-size: 22px;
            display: block;
            font-variant-numeric: tabular-nums;
            letter-spacing: .05em;
        }
        @media (max-width: 600px) {
            body.auth-body {
                overflow-x: hidden;
                height: auto;
                min-height: 100vh;
            }
        }
    </style>
</head>
<body class="auth-body">

<div class="auth-page">
    <div class="auth-card">

        <!-- Header — idéntico al original -->
        <div class="auth-header">
            <div class="auth-logo-row">
                <div class="auth-logo-icon">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <span class="auth-logo-name">CFlow</span>
            </div>
            <p class="auth-logo-sub">Gestión de finanzas personales.</p>
        </div>

        <!-- Tabs — idéntico al original -->
        <div class="auth-tabs">
            <button class="auth-tab active" id="tab-login"    onclick="switchTab('login')">Iniciar sesión</button>
            <button class="auth-tab"        id="tab-register" onclick="switchTab('register')">Crear cuenta</button>
        </div>

        <div class="auth-body-inner">

            <!-- ══════════════════ LOGIN ══════════════════ -->
            <div class="auth-panel active" id="panel-login">

                <div class="auth-alert auth-alert-success" id="login-success" style="display:none;"></div>
                <div class="auth-alert auth-alert-danger"  id="login-error"   style="display:none;"></div>
                <div class="auth-alert auth-alert-danger"  id="login-warn"    style="display:none;"></div>

                <!-- Contador de bloqueo -->
                <div class="lockout-box" id="lockout-box">
                    <strong id="lockout-timer">5:00</strong>
                    Demasiados intentos fallidos. Intenta de nuevo en el tiempo indicado.
                </div>

                <form onsubmit="return false;">
                    <div class="auth-field">
                        <label>Usuario o email</label>
                        <input type="text" id="login-username" placeholder="tu@email.com"
                               autocomplete="username" required>
                    </div>
                    <div class="auth-field">
                        <label>Contraseña</label>
                        <input type="password" id="login-password" placeholder="••••••••"
                               autocomplete="current-password" required>
                    </div>
                    <button type="submit" class="auth-btn" id="login-btn" onclick="doLogin()">
                        <span class="btn-label">Ingresar</span>
                        <div class="btn-spinner"></div>
                    </button>
                </form>

                <p class="auth-hint">
                    ¿No tienes cuenta?
                    <span onclick="switchTab('register')">Regístrate gratis</span>
                </p>
            </div>

            <!-- ══════════════════ REGISTER ══════════════════ -->
            <div class="auth-panel" id="panel-register">

                <div class="auth-alert auth-alert-success" id="reg-success" style="display:none;"></div>
                <div class="auth-alert auth-alert-danger"  id="reg-error"   style="display:none;"></div>
                <div class="auth-alert auth-alert-danger"  id="reg-warn"    style="display:none;"></div>

                <form onsubmit="return false;">
                    <div class="auth-field">
                        <label>Nombre completo</label>
                        <input type="text" id="reg-name" placeholder="Juan Pérez"
                               autocomplete="name" required>
                    </div>
                    <div class="auth-field">
                        <label>Usuario</label>
                        <input type="text" id="reg-username" placeholder="juanperez"
                               oninput="checkField('username')"
                               autocomplete="off" spellcheck="false" required>
                        <p class="field-msg" id="msg-username"></p>
                    </div>
                    <div class="auth-field">
                        <label>Email</label>
                        <input type="email" id="reg-email" placeholder="tu@email.com"
                               oninput="checkField('email')"
                               autocomplete="email" required>
                        <p class="field-msg" id="msg-email"></p>
                    </div>
                    <div class="auth-field">
                        <label>Contraseña</label>
                        <input type="password" id="reg-password" placeholder="Mínimo 6 caracteres"
                               oninput="evalPassword()"
                               autocomplete="new-password" required>
                        <div class="strength-bar">
                            <div class="strength-seg" id="seg1"></div>
                            <div class="strength-seg" id="seg2"></div>
                            <div class="strength-seg" id="seg3"></div>
                            <div class="strength-seg" id="seg4"></div>
                        </div>
                        <p class="field-msg" id="msg-password"></p>
                    </div>
                    <button type="submit" class="auth-btn" id="register-btn" onclick="doRegister()">
                        <span class="btn-label">Crear cuenta</span>
                        <div class="btn-spinner"></div>
                    </button>
                </form>

                <p class="auth-hint">
                    ¿Ya tienes cuenta?
                    <span onclick="switchTab('login')">Inicia sesión</span>
                </p>
            </div>

        </div><!-- /auth-body-inner -->
    </div><!-- /auth-card -->
</div><!-- /auth-page -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ═══════════════════════════════════════════════════════════════
//  CONFIG
// ═══════════════════════════════════════════════════════════════
const API            = 'ajax/auth.php';
const MAX_ATTEMPTS   = 5;
const LOCKOUT_MS     = 5 * 60 * 1000;    // 5 min bloqueo login
const REG_COOLDOWN   = 5 * 60 * 1000;    // 5 min entre registros por dispositivo
const DEBOUNCE_MS    = 600;

const LS_ATTEMPTS    = 'cflow_login_attempts';
const LS_LOCKOUT     = 'cflow_lockout_until';
const LS_LAST_REG    = 'cflow_last_register';

// ═══════════════════════════════════════════════════════════════
//  HELPERS UI
// ═══════════════════════════════════════════════════════════════
const $ = id => document.getElementById(id);

function showMsg(id, text) {
    const el = $(id);
    el.textContent  = text;
    el.style.display = 'block';
}
function hideMsg(...ids) {
    ids.forEach(id => { $(id).style.display = 'none'; });
}
function setBusy(btnId, busy) {
    $(btnId).classList.toggle('loading', busy);
    $(btnId).disabled = busy;
}

// ═══════════════════════════════════════════════════════════════
//  TABS
// ═══════════════════════════════════════════════════════════════
function switchTab(name) {
    document.querySelectorAll('.auth-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.auth-panel').forEach(p => p.classList.remove('active'));
    $('tab-'   + name).classList.add('active');
    $('panel-' + name).classList.add('active');
}

// ═══════════════════════════════════════════════════════════════
//  LOGIN — RATE LIMIT (localStorage)
// ═══════════════════════════════════════════════════════════════
let lockInterval = null;

function getAttempts()    { return parseInt(localStorage.getItem(LS_ATTEMPTS) || '0'); }
function getLockUntil()   { return parseInt(localStorage.getItem(LS_LOCKOUT)  || '0'); }

function isLocked() {
    const until = getLockUntil();
    if (until && Date.now() < until) return true;
    if (until) {                              // expiró, limpiar
        localStorage.removeItem(LS_LOCKOUT);
        localStorage.removeItem(LS_ATTEMPTS);
    }
    return false;
}

function recordFail() {
    const n = getAttempts() + 1;
    localStorage.setItem(LS_ATTEMPTS, n);
    if (n >= MAX_ATTEMPTS) {
        const until = Date.now() + LOCKOUT_MS;
        localStorage.setItem(LS_LOCKOUT, until);
        startCountdown(until);
    } else {
        const left = MAX_ATTEMPTS - n;
        showMsg('login-warn',
            `Usuario o contraseña incorrectos. ${left} intento${left !== 1 ? 's' : ''} restante${left !== 1 ? 's' : ''}.`
        );
    }
}

function startCountdown(until) {
    hideMsg('login-error', 'login-warn', 'login-success');
    $('lockout-box').style.display = 'block';
    $('login-btn').disabled        = true;

    if (lockInterval) clearInterval(lockInterval);

    const tick = () => {
        const ms = until - Date.now();
        if (ms <= 0) {
            clearInterval(lockInterval);
            lockInterval = null;
            $('lockout-box').style.display = 'none';
            $('login-btn').disabled        = false;
            localStorage.removeItem(LS_LOCKOUT);
            localStorage.removeItem(LS_ATTEMPTS);
            return;
        }
        const m = Math.floor(ms / 60000);
        const s = Math.floor((ms % 60000) / 1000).toString().padStart(2, '0');
        $('lockout-timer').textContent = `${m}:${s}`;
    };
    tick();
    lockInterval = setInterval(tick, 500);
}

// Restaurar bloqueo si la página se recarga estando bloqueado
document.addEventListener('DOMContentLoaded', () => {
    if (isLocked()) startCountdown(getLockUntil());
});

// ═══════════════════════════════════════════════════════════════
//  LOGIN — SUBMIT → AJAX
// ═══════════════════════════════════════════════════════════════
async function doLogin() {
    hideMsg('login-error', 'login-warn', 'login-success');

    if (isLocked()) { startCountdown(getLockUntil()); return; }

    const username = $('login-username').value.trim();
    const password = $('login-password').value;

    if (!username || !password) {
        showMsg('login-warn', 'Completa todos los campos.');
        return;
    }

    setBusy('login-btn', true);
    try {
        const fd = new FormData();
        fd.append('action',   'login');
        fd.append('username', username);
        fd.append('password', password);

        const res  = await fetch(API, { method: 'POST', body: fd });
        const data = await res.json();

        if (data.success) {
            localStorage.removeItem(LS_ATTEMPTS);
            localStorage.removeItem(LS_LOCKOUT);
            showMsg('login-success', '¡Bienvenido! Redirigiendo…');
            setTimeout(() => { window.location.href = data.redirect || 'index.php'; }, 600);
        } else {
            recordFail();
            if (!isLocked()) showMsg('login-error', data.message);
        }
    } catch {
        showMsg('login-error', 'Error de conexión. Intenta de nuevo.');
    } finally {
        setBusy('login-btn', false);
    }
}

// ═══════════════════════════════════════════════════════════════
//  REGISTER — COOLDOWN DE DISPOSITIVO
// ═══════════════════════════════════════════════════════════════
function canRegister() {
    const last = parseInt(localStorage.getItem(LS_LAST_REG) || '0');
    if (!last) return { ok: true };
    const diff = Date.now() - last;
    if (diff < REG_COOLDOWN) {
        const rem = REG_COOLDOWN - diff;
        const m   = Math.floor(rem / 60000);
        const s   = Math.ceil((rem % 60000) / 1000).toString().padStart(2, '0');
        return { ok: false, msg: `Espera ${m}:${s} min antes de crear otra cuenta desde este dispositivo.` };
    }
    return { ok: true };
}

// ═══════════════════════════════════════════════════════════════
//  REGISTER — CHECK EN TIEMPO REAL (username / email) → AJAX
// ═══════════════════════════════════════════════════════════════
const debounceT = {};

async function checkField(field) {
    clearTimeout(debounceT[field]);

    const input = $('reg-' + field);
    const msg   = $('msg-' + field);
    const val   = input.value.trim();

    input.classList.remove('field-error', 'field-ok');
    msg.textContent = '';
    msg.className   = 'field-msg';

    if (!val) return;

    // Validación local inmediata
    if (field === 'username' && !/^[a-zA-Z0-9_]{3,20}$/.test(val)) {
        input.classList.add('field-error');
        msg.textContent = 'Solo letras, números y _ · 3–20 caracteres';
        msg.className   = 'field-msg err';
        return;
    }
    if (field === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) {
        input.classList.add('field-error');
        msg.textContent = 'Formato de email inválido';
        msg.className   = 'field-msg err';
        return;
    }

    msg.textContent = 'Verificando…';

    // Consulta al backend con debounce
    debounceT[field] = setTimeout(async () => {
        try {
            const res  = await fetch(`${API}?action=check&field=${field}&value=${encodeURIComponent(val)}`);
            const data = await res.json();

            if (data.exists) {
                input.classList.add('field-error');
                msg.textContent = field === 'email'
                    ? 'Este email ya está registrado.'
                    : 'Este usuario ya está en uso.';
                msg.className = 'field-msg err';
            } else {
                input.classList.add('field-ok');
                msg.textContent = field === 'email' ? 'Email disponible ✓' : 'Usuario disponible ✓';
                msg.className   = 'field-msg ok';
            }
        } catch {
            msg.textContent = '';
        }
    }, DEBOUNCE_MS);
}

// ═══════════════════════════════════════════════════════════════
//  REGISTER — BARRA DE FUERZA DE CONTRASEÑA
// ═══════════════════════════════════════════════════════════════
function evalPassword() {
    const val  = $('reg-password').value;
    const msg  = $('msg-password');
    const segs = [1, 2, 3, 4].map(i => $('seg' + i));
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
        msg.textContent = ''; msg.className = 'field-msg';
    } else if (val.length < 6) {
        msg.textContent = 'Mínimo 6 caracteres'; msg.className = 'field-msg err';
    } else {
        msg.textContent = label[score];
        msg.className   = 'field-msg ' + (score >= 3 ? 'ok' : 'err');
    }
}

// ═══════════════════════════════════════════════════════════════
//  REGISTER — SUBMIT → AJAX
// ═══════════════════════════════════════════════════════════════
async function doRegister() {
    hideMsg('reg-error', 'reg-warn', 'reg-success');

    const cooldown = canRegister();
    if (!cooldown.ok) { showMsg('reg-warn', cooldown.msg); return; }

    const name     = $('reg-name').value.trim();
    const username = $('reg-username').value.trim();
    const email    = $('reg-email').value.trim();
    const password = $('reg-password').value;

    // — Validaciones locales completas antes de ir al server —
    if (!name || !username || !email || !password) {
        showMsg('reg-warn', 'Completa todos los campos.');
        return;
    }
    if (!/^[a-zA-Z0-9_]{3,20}$/.test(username)) {
        showMsg('reg-error', 'El usuario solo puede tener letras, números y _ (3–20 caracteres).');
        return;
    }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        showMsg('reg-error', 'Ingresa un email válido.');
        return;
    }
    if (password.length < 6) {
        showMsg('reg-error', 'La contraseña debe tener al menos 6 caracteres.');
        return;
    }
    if ($('reg-username').classList.contains('field-error') ||
        $('reg-email').classList.contains('field-error')) {
        showMsg('reg-error', 'Corrige los errores marcados antes de continuar.');
        return;
    }

    setBusy('register-btn', true);
    try {
        const fd = new FormData();
        fd.append('action',   'register');
        fd.append('name',     name);
        fd.append('username', username);
        fd.append('email',    email);
        fd.append('password', password);

        const res  = await fetch(API, { method: 'POST', body: fd });
        const data = await res.json();

        if (data.success) {
            localStorage.setItem(LS_LAST_REG, Date.now());
            showMsg('reg-success', data.message);

            // Limpiar form y redirigir a tab login tras 1.5 s
            setTimeout(() => {
                ['reg-name', 'reg-username', 'reg-email', 'reg-password'].forEach(id => {
                    $(id).value = '';
                    $(id).classList.remove('field-ok', 'field-error');
                });
                ['msg-username', 'msg-email', 'msg-password'].forEach(id => {
                    $(id).textContent = '';
                });
                [1, 2, 3, 4].forEach(i => $('seg' + i).className = 'strength-seg');
                switchTab('login');
                showMsg('login-success', '¡Cuenta creada! Ahora puedes iniciar sesión.');
            }, 1500);
        } else {
            showMsg('reg-error', data.message || 'Error al registrar.');
        }
    } catch {
        showMsg('reg-error', 'Error de conexión. Intenta de nuevo.');
    } finally {
        setBusy('register-btn', false);
    }
}

// ═══════════════════════════════════════════════════════════════
//  ENTER para enviar el formulario activo
// ═══════════════════════════════════════════════════════════════
document.addEventListener('keydown', e => {
    if (e.key !== 'Enter') return;
    if ($('panel-login').classList.contains('active'))    doLogin();
    if ($('panel-register').classList.contains('active')) doRegister();
});
</script>
</body>
</html>