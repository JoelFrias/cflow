<?php
// ajax/debts.php - Maneja las operaciones relacionadas con el perfil del usuario (obtener datos, actualizar perfil, cambiar contraseña)

require_once '../config/database.php';

header('Content-Type: application/json');

redirectIfNotLoggedIn();

$user_id = $_SESSION['user_id'];
$action  = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {

        // ============================================
        // OBTENER DATOS DEL USUARIO
        // ============================================
        case 'get_profile':
            $stmt = $pdo->prepare("SELECT name, username, email, created_at FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) throw new Exception('Usuario no encontrado.');

            echo json_encode([
                'success' => true,
                'user'    => $user,
            ]);
            break;

        // ============================================
        // ACTUALIZAR PERFIL
        // ============================================
        case 'update_profile':
            $name     = trim($_POST['name'] ?? '');
            $username = trim($_POST['username'] ?? '');
            $email    = trim($_POST['email'] ?? '');

            if (empty($name) || empty($username) || empty($email)) {
                throw new Exception('Todos los campos son requeridos.');
            }

            // Username duplicado
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $stmt->execute([$username, $user_id]);
            if ($stmt->fetch()) throw new Exception('El nombre de usuario ya está en uso.');

            // Email duplicado
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $user_id]);
            if ($stmt->fetch()) throw new Exception('El email ya está en uso.');

            $stmt = $pdo->prepare("UPDATE users SET name = ?, username = ?, email = ? WHERE id = ?");
            $stmt->execute([$name, $username, $email, $user_id]);

            // Actualizar sesión
            $_SESSION['user_name']     = $name;
            $_SESSION['user_username'] = $username;
            $_SESSION['user_email']    = $email;

            echo json_encode([
                'success' => true,
                'message' => 'Perfil actualizado correctamente.',
                'user'    => compact('name', 'username', 'email'),
            ]);
            break;

        // ============================================
        // CAMBIAR CONTRASEÑA
        // ============================================
        case 'change_password':
            $current_password = $_POST['current_password'] ?? '';
            $new_password     = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';

            if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                throw new Exception('Todos los campos son requeridos.');
            }
            if ($new_password !== $confirm_password) {
                throw new Exception('Las contraseñas nuevas no coinciden.');
            }
            if (strlen($new_password) < 6) {
                throw new Exception('La contraseña debe tener al menos 6 caracteres.');
            }

            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || !password_verify($current_password, $user['password'])) {
                throw new Exception('Contraseña actual incorrecta.');
            }

            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$new_hash, $user_id]);

            echo json_encode([
                'success' => true,
                'message' => 'Contraseña actualizada correctamente.',
            ]);
            break;

        default:
            throw new Exception("Acción '{$action}' no reconocida.");
    }

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $full = 'PDOException: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine();
    echo json_encode([
        'success'      => false,
        'message'      => 'Error en la base de datos. Revisa la consola.',
        'full_message' => $full,
    ]);
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    echo json_encode([
        'success'      => false,
        'message'      => $e->getMessage(),
        'full_message' => 'Exception: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine(),
    ]);
}