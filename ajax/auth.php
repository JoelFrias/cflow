<?php
// ajax/auth.php — Controlador de autenticación
// Solo responde JSON. Nunca renderiza HTML.

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

// Evitar caché en respuestas de auth
header('Cache-Control: no-store, no-cache, must-revalidate');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ─────────────────────────────────────────────────────────────
// HELPER
// ─────────────────────────────────────────────────────────────
function json_out(bool $success, string $message, array $extra = []): void {
    echo json_encode(array_merge(
        ['success' => $success, 'message' => $message],
        $extra
    ));
    exit();
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
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        json_out(false, 'Completa todos los campos.');
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id']       = $user['id'];
        $_SESSION['user_name']     = $user['name'];
        $_SESSION['user_username'] = $user['username'];
        $_SESSION['user_email']    = $user['email'];

        json_out(true, 'Sesión iniciada.', ['redirect' => 'index.php']);
    }

    json_out(false, 'Usuario o contraseña incorrectos.');
}

// ─────────────────────────────────────────────────────────────
// REGISTER
// POST ajax/auth.php   action=register
// ─────────────────────────────────────────────────────────────
if ($action === 'register' && $_SERVER['REQUEST_METHOD'] === 'POST') {
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

    // — Verificar duplicados —
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) json_out(false, 'Ese nombre de usuario ya está en uso.');

    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) json_out(false, 'Ese email ya está registrado.');

    // — Insertar —
    try {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt   = $pdo->prepare(
            "INSERT INTO users (name, username, email, password) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$name, $username, $email, $hashed]);

        json_out(true, 'Cuenta creada. Ya puedes iniciar sesión.');
    } catch (PDOException $e) {
        // Fallback ante race condition en unique constraint
        if (str_contains($e->getMessage(), 'username')) json_out(false, 'El nombre de usuario ya existe.');
        if (str_contains($e->getMessage(), 'email'))    json_out(false, 'El email ya existe.');
        json_out(false, 'Error al crear la cuenta. Intenta más tarde.');
    }
}

// ─────────────────────────────────────────────────────────────
// LOGOUT
// GET ajax/auth.php?action=logout
// ─────────────────────────────────────────────────────────────
if ($action === 'logout') {
    session_destroy();
    json_out(true, 'Sesión cerrada.', ['redirect' => 'index.php']);
}

// Acción no reconocida
json_out(false, 'Acción no reconocida.');