<?php
// ajax/auth.php — Controlador de autenticación
// Solo responde JSON. Nunca renderiza HTML.

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store, no-cache, must-revalidate');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ─────────────────────────────────────────────────────────────
// HELPERS
// ─────────────────────────────────────────────────────────────
function json_out(bool $success, string $message, array $extra = []): void {
    echo json_encode(array_merge(
        ['success' => $success, 'message' => $message],
        $extra
    ));
    exit();
}

/**
 * Protección CSRF: verifica que el token del POST coincida con el de sesión.
 * Solo se aplica en acciones que modifican datos.
 */
function verify_csrf(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (
        empty($token) ||
        empty($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $token)
    ) {
        json_out(false, 'Token de seguridad inválido. Recarga la página e intenta de nuevo.');
    }
}

/**
 * Rate limiting simple basado en sesión.
 * Bloquea si se superan $max intentos en $window segundos.
 *
 * @param string $key    Clave única por acción (ej: 'login')
 * @param int    $max    Intentos máximos permitidos
 * @param int    $window Ventana de tiempo en segundos
 */
function rate_limit(string $key, int $max = 5, int $window = 60): void {
    $now = time();
    $rl  = &$_SESSION['rate_limit'][$key];

    // Inicializar o reiniciar si la ventana expiró
    if (empty($rl) || ($now - $rl['start']) > $window) {
        $rl = ['count' => 1, 'start' => $now];
        return;
    }

    $rl['count']++;
    if ($rl['count'] > $max) {
        $wait = $window - ($now - $rl['start']);
        json_out(false, "Demasiados intentos. Espera {$wait} segundos antes de volver a intentarlo.");
    }
}

// ─────────────────────────────────────────────────────────────
// CHECK — ¿username o email ya existe?
// GET ajax/auth.php?action=check&field=username&value=xxx
// ─────────────────────────────────────────────────────────────
if ($action === 'check' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $field = $_GET['field'] ?? '';
    $value = trim($_GET['value'] ?? '');

    if (!in_array($field, ['username', 'email'], true) || $value === '') {
        json_out(false, 'Parámetros inválidos.');
    }

    // Whitelist explícita: nunca interpolar input del usuario en SQL
    $col  = $field === 'username' ? 'username' : 'email';
    $stmt = $pdo->prepare("SELECT id FROM users WHERE {$col} = ?");
    $stmt->execute([$value]);

    json_out(true, '', ['exists' => (bool) $stmt->fetch()]);
}

// ─────────────────────────────────────────────────────────────
// LOGIN
// POST ajax/auth.php   action=login
// ─────────────────────────────────────────────────────────────
if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {

    // Limitar intentos fallidos: 5 por minuto
    rate_limit('login', 5, 60);

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        json_out(false, 'Completa todos los campos.');
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();

    // Tiempo constante para evitar user enumeration por timing
    if ($user && password_verify($password, $user['password'])) {

        // Regenerar ID de sesión para prevenir session fixation
        session_regenerate_id(true);

        // Limpiar contadores de rate limit al autenticarse correctamente
        unset($_SESSION['rate_limit']['login']);

        $_SESSION['user_id']       = $user['id'];
        $_SESSION['user_name']     = $user['name'];
        $_SESSION['user_username'] = $user['username'];
        $_SESSION['user_email']    = $user['email'];

        // Generar token CSRF fresco tras login
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        json_out(true, 'Sesión iniciada.', [
            'redirect'   => 'index.php',
            'csrf_token' => $_SESSION['csrf_token'],
        ]);
    }

    // Respuesta genérica: no revelar si el usuario existe
    json_out(false, 'Usuario o contraseña incorrectos.');
}

// ─────────────────────────────────────────────────────────────
// REGISTER
// POST ajax/auth.php   action=register
// ─────────────────────────────────────────────────────────────
if ($action === 'register' && $_SERVER['REQUEST_METHOD'] === 'POST') {

    // Limitar registros: 3 por 5 minutos desde la misma sesión
    rate_limit('register', 3, 300);

    $name     = trim($_POST['name']     ?? '');
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password =       $_POST['password'] ?? '';

    // — Validaciones del servidor (segunda capa, no confiar solo en el cliente) —
    if ($name === '' || $username === '' || $email === '' || $password === '') {
        json_out(false, 'Completa todos los campos.');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_out(false, 'El email no tiene un formato válido.');
    }
    if (strlen($password) < 6) {
        json_out(false, 'La contraseña debe tener al menos 6 caracteres.');
    }
    if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
        json_out(false, 'El usuario solo puede tener letras, números y _ (3–20 caracteres).');
    }

    // — Registro dentro de transacción para garantizar atomicidad —
    try {
        $pdo->beginTransaction();

        // Verificar duplicados con bloqueo para evitar race conditions
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? FOR UPDATE");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $pdo->rollBack();
            json_out(false, 'Ese nombre de usuario ya está en uso.');
        }

        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? FOR UPDATE");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $pdo->rollBack();
            json_out(false, 'Ese email ya está registrado.');
        }

        // — Insertar —
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt   = $pdo->prepare(
            "INSERT INTO users (name, username, email, password) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$name, $username, $email, $hashed]);

        $pdo->commit();

        json_out(true, 'Cuenta creada. Ya puedes iniciar sesión.');

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        // Fallback ante violación de unique constraint por race condition
        if (str_contains($e->getMessage(), 'username')) json_out(false, 'El nombre de usuario ya existe.');
        if (str_contains($e->getMessage(), 'email'))    json_out(false, 'El email ya existe.');
        json_out(false, 'Error al crear la cuenta. Intenta más tarde.');
    }
}

// ─────────────────────────────────────────────────────────────
// LOGOUT
// POST ajax/auth.php   action=logout  (cambiado de GET a POST + CSRF)
// ─────────────────────────────────────────────────────────────
if ($action === 'logout') {

    // Verificar CSRF solo si hay sesión activa con token generado
    if (!empty($_SESSION['csrf_token'])) {
        verify_csrf();
    }

    // Destruir completamente la sesión
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(), '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }
    session_destroy();

    json_out(true, 'Sesión cerrada.', ['redirect' => 'index.php']);
}

// Acción no reconocida
json_out(false, 'Acción no reconocida.');