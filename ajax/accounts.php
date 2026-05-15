<?php
// ajax/accounts.php - Maneja operaciones CRUD para cuentas y obtiene transacciones relacionadas

require_once '../config/database.php';

// Configurar cabeceras y manejo de errores
header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Función auxiliar para respuestas JSON
function jsonResponse($success, $message, $extra = []) {
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra));
    exit;
}

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    jsonResponse(false, 'Sesión no iniciada');
}
$user_id = $_SESSION['user_id'];

// Obtener acción
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Función para obtener tasa USD (si no está definida en database.php)
if (!function_exists('getUSDRate')) {
    function getUSDRate($pdo) {
        $stmt = $pdo->prepare("SELECT exchange_rate_to_dop FROM currencies WHERE code = 'USD'");
        $stmt->execute();
        $rate = $stmt->fetchColumn();
        return $rate ? (float)$rate : 1.0;
    }
}

try {
    switch ($action) {

        // ========== OBTENER CUENTAS ==========
        case 'get_accounts':
            $stmt = $pdo->prepare("
                SELECT a.*, c.symbol, c.exchange_rate_to_dop, c.name as currency_name
                FROM accounts a
                JOIN currencies c ON a.currency_code = c.code
                WHERE a.user_id = ? AND a.type IN ('cash', 'bank', 'wallet')
                ORDER BY a.type, a.name
            ");
            $stmt->execute([$user_id]);
            $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Balance total en DOP
            $stmt2 = $pdo->prepare("
                SELECT SUM(a.balance * c.exchange_rate_to_dop) as total_dop
                FROM accounts a
                JOIN currencies c ON a.currency_code = c.code
                WHERE a.user_id = ? AND a.type IN ('cash', 'bank', 'wallet')
            ");
            $stmt2->execute([$user_id]);
            $total_dop = $stmt2->fetchColumn() ?? 0;

            jsonResponse(true, 'Cuentas cargadas', [
                'accounts'  => $accounts,
                'total_dop' => (float)$total_dop,
                'total_usd' => (float)($total_dop / getUSDRate($pdo)),
            ]);
            break;

        // ========== CREAR CUENTA ==========
        case 'create_account':
            $name            = trim($_POST['name'] ?? '');
            $type            = trim($_POST['type'] ?? '');
            $currency_code   = trim($_POST['currency_code'] ?? '');
            $initial_balance = (float)($_POST['initial_balance'] ?? 0);

            if (empty($name))          throw new Exception('El nombre de la cuenta es obligatorio.');
            if (!in_array($type, ['cash', 'bank', 'wallet'])) throw new Exception('Tipo de cuenta inválido.');
            if (empty($currency_code)) throw new Exception('Debes seleccionar una moneda.');

            $pdo->beginTransaction();
            try {
                // Verificar moneda
                $chk = $pdo->prepare("SELECT code FROM currencies WHERE code = ?");
                $chk->execute([$currency_code]);
                if (!$chk->fetch()) throw new Exception("La moneda '{$currency_code}' no existe.");

                $stmt = $pdo->prepare("
                    INSERT INTO accounts (user_id, name, type, currency_code, balance)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$user_id, $name, $type, $currency_code, $initial_balance]);
                $new_id = $pdo->lastInsertId();

                // Obtener datos completos
                $stmt2 = $pdo->prepare("
                    SELECT a.*, c.symbol, c.exchange_rate_to_dop, c.name as currency_name
                    FROM accounts a
                    JOIN currencies c ON a.currency_code = c.code
                    WHERE a.id = ?
                ");
                $stmt2->execute([$new_id]);
                $new_account = $stmt2->fetch(PDO::FETCH_ASSOC);

                $pdo->commit();
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }

            jsonResponse(true, "Cuenta creada exitosamente en {$currency_code}.", [
                'account' => $new_account
            ]);
            break;

        // ========== ACTUALIZAR CUENTA ==========
        case 'update_account':
            $account_id    = (int)($_POST['account_id'] ?? 0);
            $name          = trim($_POST['name'] ?? '');
            $type          = trim($_POST['type'] ?? '');
            $currency_code = trim($_POST['currency_code'] ?? '');

            if ($account_id <= 0)      throw new Exception('ID de cuenta inválido.');
            if (empty($name))          throw new Exception('El nombre es obligatorio.');
            if (!in_array($type, ['cash', 'bank', 'wallet'])) throw new Exception('Tipo inválido.');
            if (empty($currency_code)) throw new Exception('Debes seleccionar una moneda.');

            $pdo->beginTransaction();
            try {
                // Verificar propiedad — con bloqueo pesimista para evitar race conditions
                $chk = $pdo->prepare("SELECT id FROM accounts WHERE id = ? AND user_id = ? FOR UPDATE");
                $chk->execute([$account_id, $user_id]);
                if (!$chk->fetch()) throw new Exception('Cuenta no encontrada o sin permiso.');

                // Verificar moneda
                $chkCur = $pdo->prepare("SELECT code FROM currencies WHERE code = ?");
                $chkCur->execute([$currency_code]);
                if (!$chkCur->fetch()) throw new Exception("Moneda '{$currency_code}' no existe.");

                $stmt = $pdo->prepare("
                    UPDATE accounts
                    SET name = ?, type = ?, currency_code = ?
                    WHERE id = ? AND user_id = ?
                ");
                $stmt->execute([$name, $type, $currency_code, $account_id, $user_id]);

                // Obtener datos actualizados dentro de la misma transacción
                $stmt2 = $pdo->prepare("
                    SELECT a.*, c.symbol, c.exchange_rate_to_dop, c.name as currency_name
                    FROM accounts a
                    JOIN currencies c ON a.currency_code = c.code
                    WHERE a.id = ?
                ");
                $stmt2->execute([$account_id]);
                $updated = $stmt2->fetch(PDO::FETCH_ASSOC);

                $pdo->commit();
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }

            jsonResponse(true, 'Cuenta actualizada correctamente.', [
                'account' => $updated
            ]);
            break;

        // ========== ELIMINAR CUENTA ==========
        case 'delete_account':
            $account_id = (int)($_POST['account_id'] ?? 0);
            if ($account_id <= 0) throw new Exception('ID de cuenta inválido.');

            $pdo->beginTransaction();
            try {
                // Verificar propiedad y obtener nombre — con bloqueo pesimista
                $chk = $pdo->prepare("SELECT name FROM accounts WHERE id = ? AND user_id = ? FOR UPDATE");
                $chk->execute([$account_id, $user_id]);
                $account = $chk->fetch(PDO::FETCH_ASSOC);
                if (!$account) throw new Exception('Cuenta no encontrada o sin permiso.');

                $stmt = $pdo->prepare("DELETE FROM accounts WHERE id = ? AND user_id = ?");
                $stmt->execute([$account_id, $user_id]);

                $pdo->commit();
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }

            jsonResponse(true, "Cuenta '{$account['name']}' eliminada exitosamente.");
            break;

        default:
            jsonResponse(false, "Acción '{$action}' no reconocida.");
    }

} catch (PDOException $e) {
    // Rollback de seguridad si una transacción PDO quedó abierta
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    jsonResponse(false, 'Error en la base de datos', [
        'full_message' => $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine()
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    jsonResponse(false, $e->getMessage(), [
        'full_message' => $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine()
    ]);
}