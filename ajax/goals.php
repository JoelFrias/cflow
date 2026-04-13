<?php
// ajax/goals.php - Maneja todas las operaciones relacionadas con las metas de ahorro.

require_once '../config/database.php';

header('Content-Type: application/json');

redirectIfNotLoggedIn();

$user_id = (int) $_SESSION['user_id'];
$action  = trim($_POST['action'] ?? $_GET['action'] ?? '');

/**
 * Devuelve un JSON de error y termina la ejecución.
 * Hace rollback si hay una transacción activa.
 */
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

// ─────────────────────────────────────────────────────────────
// Helper: verifica que una meta exista y pertenezca al usuario.
// Lanza Exception si no se encuentra.
// ─────────────────────────────────────────────────────────────
function fetchGoalOrFail(PDO $pdo, int $goal_id, int $user_id): array
{
    $stmt = $pdo->prepare("
        SELECT * FROM savings_goals
        WHERE id = ? AND user_id = ?
        LIMIT 1
    ");
    $stmt->execute([$goal_id, $user_id]);
    $goal = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$goal) {
        throw new Exception('Meta no encontrada.');
    }

    return $goal;
}

try {

    switch ($action) {

        // ============================================================
        // OBTENER TODAS LAS METAS
        // ============================================================
        case 'get_goals':
            $stmt = $pdo->prepare("
                SELECT * FROM savings_goals
                WHERE user_id = ?
                ORDER BY is_completed ASC, deadline ASC
            ");
            $stmt->execute([$user_id]);
            $goals = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $usd_rate = getUSDRate($pdo);

            echo json_encode([
                'success'  => true,
                'goals'    => $goals,
                'usd_rate' => (float) $usd_rate,
            ]);
            break;

        // ============================================================
        // CREAR META
        // ============================================================
        case 'add_goal':
            $name     = trim($_POST['name'] ?? '');
            $target   = (float) ($_POST['target_amount'] ?? 0);
            $deadline = trim($_POST['deadline'] ?? '');

            if ($name === '')     throw new Exception('El nombre de la meta es obligatorio.');
            if ($target <= 0)     throw new Exception('El monto objetivo debe ser mayor a 0.');
            if ($deadline === '') throw new Exception('La fecha límite es obligatoria.');

            // Validar formato de fecha (YYYY-MM-DD)
            $d = DateTime::createFromFormat('Y-m-d', $deadline);
            if (!$d || $d->format('Y-m-d') !== $deadline) {
                throw new Exception('El formato de la fecha límite no es válido (YYYY-MM-DD).');
            }

            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                INSERT INTO savings_goals (user_id, name, target_amount, deadline)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$user_id, $name, $target, $deadline]);
            $new_id = (int) $pdo->lastInsertId();

            $pdo->commit();

            // Leer la meta recién creada fuera de la transacción
            $stmt2 = $pdo->prepare("SELECT * FROM savings_goals WHERE id = ?");
            $stmt2->execute([$new_id]);
            $new_goal = $stmt2->fetch(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'message' => "Meta \"{$name}\" creada correctamente.",
                'goal'    => $new_goal,
            ]);
            break;

        // ============================================================
        // AGREGAR ABONO A META
        // ============================================================
        case 'add_contribution':
            $goal_id           = (int)   ($_POST['goal_id']           ?? 0);
            $amount            = (float) ($_POST['amount']            ?? 0);
            $contribution_date = trim(   $_POST['contribution_date']  ?? '');
            $notes             = trim(   $_POST['notes']              ?? '');

            if ($goal_id <= 0)         throw new Exception('El ID de la meta no es válido.');
            if ($amount  <= 0)         throw new Exception('El monto debe ser mayor a 0.');
            if ($contribution_date === '') throw new Exception('La fecha del abono es obligatoria.');

            // Validar formato de fecha
            $d = DateTime::createFromFormat('Y-m-d', $contribution_date);
            if (!$d || $d->format('Y-m-d') !== $contribution_date) {
                throw new Exception('El formato de la fecha del abono no es válido (YYYY-MM-DD).');
            }

            $pdo->beginTransaction();

            // Verificar que la meta pertenece al usuario (con FOR UPDATE para evitar
            // condiciones de carrera si se hacen abonos simultáneos)
            $stmt = $pdo->prepare("
                SELECT * FROM savings_goals
                WHERE id = ? AND user_id = ?
                LIMIT 1
                FOR UPDATE
            ");
            $stmt->execute([$goal_id, $user_id]);
            $goal = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$goal) {
                throw new Exception('Meta no encontrada.');
            }

            if ((bool) $goal['is_completed']) {
                throw new Exception('No se pueden agregar abonos a una meta ya completada.');
            }

            // Insertar el abono
            $pdo->prepare("
                INSERT INTO savings_contributions (goal_id, user_id, amount, contribution_date, notes)
                VALUES (?, ?, ?, ?, ?)
            ")->execute([$goal_id, $user_id, $amount, $contribution_date, $notes]);

            // Actualizar el monto acumulado y marcar como completada si corresponde
            // Se hace en una sola sentencia para evitar dos viajes a la BD
            $pdo->prepare("
                UPDATE savings_goals
                SET
                    current_amount = current_amount + ?,
                    is_completed   = IF(current_amount + ? >= target_amount, 1, is_completed)
                WHERE id = ? AND user_id = ?
            ")->execute([$amount, $amount, $goal_id, $user_id]);

            $pdo->commit();

            // Leer la meta actualizada fuera de la transacción
            $stmt2 = $pdo->prepare("SELECT * FROM savings_goals WHERE id = ? AND user_id = ?");
            $stmt2->execute([$goal_id, $user_id]);
            $updated_goal = $stmt2->fetch(PDO::FETCH_ASSOC);

            echo json_encode([
                'success'      => true,
                'message'      => 'Abono de RD$ ' . number_format($amount, 2) . ' registrado correctamente.',
                'updated_goal' => $updated_goal,
            ]);
            break;

        // ============================================================
        // HISTORIAL DE ABONOS DE UNA META
        // ============================================================
        case 'get_history':
            $goal_id = (int) ($_GET['goal_id'] ?? $_POST['goal_id'] ?? 0);

            if ($goal_id <= 0) throw new Exception('El ID de la meta no es válido.');

            // Lanza Exception si no existe / no pertenece al usuario
            fetchGoalOrFail($pdo, $goal_id, $user_id);

            $stmt = $pdo->prepare("
                SELECT * FROM savings_contributions
                WHERE goal_id = ? AND user_id = ?
                ORDER BY contribution_date DESC, created_at DESC
            ");
            $stmt->execute([$goal_id, $user_id]);
            $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'data'    => $history,
            ]);
            break;

        // ============================================================
        // ELIMINAR META
        // ============================================================
        case 'delete_goal':
            $goal_id = (int) ($_POST['goal_id'] ?? 0);

            if ($goal_id <= 0) throw new Exception('El ID de la meta no es válido.');

            $pdo->beginTransaction();

            // Bloquear la fila para evitar eliminaciones concurrentes
            $stmt = $pdo->prepare("
                SELECT name, current_amount FROM savings_goals
                WHERE id = ? AND user_id = ?
                LIMIT 1
                FOR UPDATE
            ");
            $stmt->execute([$goal_id, $user_id]);
            $goal = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$goal) {
                throw new Exception('Meta no encontrada.');
            }

            if ((float) $goal['current_amount'] > 0) {
                throw new Exception('No puedes eliminar una meta que ya tiene abonos registrados.');
            }

            $pdo->prepare("
                DELETE FROM savings_goals WHERE id = ? AND user_id = ?
            ")->execute([$goal_id, $user_id]);

            $pdo->commit();

            echo json_encode([
                'success' => true,
                'message' => "Meta \"{$goal['name']}\" eliminada correctamente.",
                'goal_id' => $goal_id,
            ]);
            break;

        // ============================================================
        // ACCIÓN NO RECONOCIDA
        // ============================================================
        default:
            $safe_action = htmlspecialchars($action, ENT_QUOTES, 'UTF-8');
            throw new Exception("Acción '{$safe_action}' no reconocida.");
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