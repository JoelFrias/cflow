<?php
// ajax/profile.php - Maneja operaciones del perfil del usuario: obtener datos, actualizar perfil, cambiar contraseña.

require_once '../config/database.php';

header('Content-Type: application/json');

redirectIfNotLoggedIn();

$user_id = (int) $_SESSION['user_id'];
$action  = trim($_POST['action'] ?? $_GET['action'] ?? '');

// ─────────────────────────────────────────────────────────────
// Helper: respuesta de error + rollback + exit
// ─────────────────────────────────────────────────────────────
function fail(PDO $pdo, string $message, string $full = ''): never
{
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode([
        'success'      => false,
        'message'      => $message,
        'full_message' => $full ?: $message,
    ]);
    exit;
}

try {

    switch ($action) {

        // ============================================================
        // OBTENER DATOS DEL USUARIO
        // ============================================================
        case 'get_profile':
            $stmt = $pdo->prepare("
                SELECT name, username, email, created_at
                FROM users
                WHERE id = ?
                LIMIT 1
            ");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) throw new Exception('Usuario no encontrado.');

            echo json_encode([
                'success' => true,
                'user'    => $user,
            ]);
            break;

        // ============================================================
        // ACTUALIZAR PERFIL
        // ============================================================
        case 'update_profile':
            $name     = trim($_POST['name']     ?? '');
            $username = trim($_POST['username'] ?? '');
            $email    = trim($_POST['email']    ?? '');

            // Validaciones
            if ($name === '' || $username === '' || $email === '') {
                throw new Exception('Todos los campos son requeridos.');
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('El formato del email no es válido.');
            }
            if (!preg_match('/^[a-zA-Z0-9_.\-]{3,30}$/', $username)) {
                throw new Exception('El nombre de usuario solo puede contener letras, números, puntos, guiones y guiones bajos (3-30 caracteres).');
            }
            if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
                throw new Exception('El nombre debe tener entre 2 y 100 caracteres.');
            }

            $pdo->beginTransaction();

            // Verificar que el usuario existe y bloquear la fila
            $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? LIMIT 1 FOR UPDATE");
            $stmt->execute([$user_id]);
            if (!$stmt->fetch()) throw new Exception('Usuario no encontrado.');

            // Username duplicado
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ? LIMIT 1");
            $stmt->execute([$username, $user_id]);
            if ($stmt->fetch()) throw new Exception('El nombre de usuario ya está en uso.');

            // Email duplicado
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
            $stmt->execute([$email, $user_id]);
            if ($stmt->fetch()) throw new Exception('El email ya está en uso.');

            $pdo->prepare("
                UPDATE users SET name = ?, username = ?, email = ? WHERE id = ?
            ")->execute([$name, $username, $email, $user_id]);

            $pdo->commit();

            // Actualizar sesión solo tras confirmar el commit
            $_SESSION['user_name']     = $name;
            $_SESSION['user_username'] = $username;
            $_SESSION['user_email']    = $email;

            echo json_encode([
                'success' => true,
                'message' => 'Perfil actualizado correctamente.',
                'user'    => compact('name', 'username', 'email'),
            ]);
            break;

        // ============================================================
        // CAMBIAR CONTRASEÑA
        // ============================================================
        case 'change_password':
            $current_password = $_POST['current_password'] ?? '';
            $new_password     = $_POST['new_password']     ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';

            if ($current_password === '' || $new_password === '' || $confirm_password === '') {
                throw new Exception('Todos los campos son requeridos.');
            }
            if ($new_password !== $confirm_password) {
                throw new Exception('Las contraseñas nuevas no coinciden.');
            }
            if (strlen($new_password) < 8) {
                throw new Exception('La contraseña debe tener al menos 8 caracteres.');
            }
            // Política básica de seguridad: al menos una letra y un número
            if (!preg_match('/[A-Za-z]/', $new_password) || !preg_match('/[0-9]/', $new_password)) {
                throw new Exception('La contraseña debe contener al menos una letra y un número.');
            }
            if ($current_password === $new_password) {
                throw new Exception('La nueva contraseña no puede ser igual a la actual.');
            }

            $pdo->beginTransaction();

            // Obtener hash actual con bloqueo de fila
            $stmt = $pdo->prepare("
                SELECT password FROM users WHERE id = ? LIMIT 1 FOR UPDATE
            ");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) throw new Exception('Usuario no encontrado.');

            // Verificar contraseña actual — mensaje genérico para no dar pistas
            if (!password_verify($current_password, $user['password'])) {
                throw new Exception('Contraseña actual incorrecta.');
            }

            $new_hash = password_hash($new_password, PASSWORD_BCRYPT, ['cost' => 12]);

            $pdo->prepare("
                UPDATE users SET password = ? WHERE id = ?
            ")->execute([$new_hash, $user_id]);

            $pdo->commit();

            // Regenerar ID de sesión como buena práctica tras cambio de credenciales
            session_regenerate_id(true);

            echo json_encode([
                'success' => true,
                'message' => 'Contraseña actualizada correctamente.',
            ]);
            break;

        // ============================================================
        // ACCIÓN NO RECONOCIDA
        // ============================================================
        default:
            $safe = htmlspecialchars($action, ENT_QUOTES, 'UTF-8');
            throw new Exception("Acción '{$safe}' no reconocida.");
    }

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();

    $full = 'PDOException [' . $e->getCode() . ']: ' . $e->getMessage()
          . ' | ' . $e->getFile() . ':' . $e->getLine();

    echo json_encode([
        'success'      => false,
        'message'      => 'Error en la base de datos. Por favor, inténtalo de nuevo.',
        'full_message' => $full,
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();

    echo json_encode([
        'success'      => false,
        'message'      => $e->getMessage(),
        'full_message' => 'Exception: ' . $e->getMessage()
                        . ' | ' . $e->getFile() . ':' . $e->getLine(),
    ]);
}