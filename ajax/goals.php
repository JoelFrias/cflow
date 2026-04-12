<?php
// ajax/goals.php - Maneja todas las operaciones relacionadas con las metas de ahorro: creación, obtención, actualización y eliminación.

require_once '../config/database.php';

header('Content-Type: application/json');

redirectIfNotLoggedIn();

$user_id = $_SESSION['user_id'];
$action  = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {

        // ============================================
        // OBTENER TODAS LAS METAS
        // ============================================
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

        // ============================================
        // CREAR META
        // ============================================
        case 'add_goal':
            $name     = trim($_POST['name'] ?? '');
            $target   = (float) ($_POST['target_amount'] ?? 0);
            $deadline = trim($_POST['deadline'] ?? '');

            if (empty($name))    throw new Exception('El nombre de la meta es obligatorio.');
            if ($target <= 0)    throw new Exception('El monto objetivo debe ser mayor a 0.');
            if (empty($deadline)) throw new Exception('La fecha límite es obligatoria.');

            $stmt = $pdo->prepare("
                INSERT INTO savings_goals (user_id, name, target_amount, deadline)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$user_id, $name, $target, $deadline]);
            $new_id = $pdo->lastInsertId();

            $stmt2 = $pdo->prepare("SELECT * FROM savings_goals WHERE id = ?");
            $stmt2->execute([$new_id]);
            $new_goal = $stmt2->fetch(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'message' => "Meta \"{$name}\" creada correctamente.",
                'goal'    => $new_goal,
            ]);
            break;

        // ============================================
        // AGREGAR ABONO A META
        // ============================================
        case 'add_contribution':
            $goal_id           = (int) ($_POST['goal_id'] ?? 0);
            $amount            = (float) ($_POST['amount'] ?? 0);
            $contribution_date = trim($_POST['contribution_date'] ?? '');
            $notes             = trim($_POST['notes'] ?? '');

            if ($amount <= 0)            throw new Exception('El monto debe ser mayor a 0.');
            if (empty($contribution_date)) throw new Exception('La fecha del abono es obligatoria.');

            // Verificar que la meta pertenece al usuario
            $stmt = $pdo->prepare("SELECT * FROM savings_goals WHERE id = ? AND user_id = ?");
            $stmt->execute([$goal_id, $user_id]);
            $goal = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$goal) throw new Exception('Meta no encontrada.');

            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                INSERT INTO savings_contributions (goal_id, user_id, amount, contribution_date, notes)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$goal_id, $user_id, $amount, $contribution_date, $notes]);

            $pdo->prepare("
                UPDATE savings_goals SET current_amount = current_amount + ? WHERE id = ? AND user_id = ?
            ")->execute([$amount, $goal_id, $user_id]);

            $pdo->prepare("
                UPDATE savings_goals SET is_completed = 1
                WHERE id = ? AND current_amount >= target_amount AND user_id = ?
            ")->execute([$goal_id, $user_id]);

            $pdo->commit();

            // Devolver la meta actualizada
            $stmt2 = $pdo->prepare("SELECT * FROM savings_goals WHERE id = ?");
            $stmt2->execute([$goal_id]);
            $updated_goal = $stmt2->fetch(PDO::FETCH_ASSOC);

            echo json_encode([
                'success'      => true,
                'message'      => 'Abono de RD$ ' . number_format($amount, 2) . ' registrado correctamente.',
                'updated_goal' => $updated_goal,
            ]);
            break;

        // ============================================
        // HISTORIAL DE ABONOS DE UNA META
        // ============================================
        case 'get_history':
            $goal_id = (int) ($_GET['goal_id'] ?? $_POST['goal_id'] ?? 0);

            // Verificar que la meta pertenece al usuario
            $chk = $pdo->prepare("SELECT id FROM savings_goals WHERE id = ? AND user_id = ?");
            $chk->execute([$goal_id, $user_id]);
            if (!$chk->fetch()) throw new Exception('Meta no encontrada.');

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

        // ============================================
        // ELIMINAR META
        // ============================================
        case 'delete_goal':
            $goal_id = (int) ($_POST['goal_id'] ?? 0);

            $stmt = $pdo->prepare("SELECT name, current_amount FROM savings_goals WHERE id = ? AND user_id = ?");
            $stmt->execute([$goal_id, $user_id]);
            $goal = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$goal) throw new Exception('Meta no encontrada.');
            if ($goal['current_amount'] > 0) throw new Exception('No puedes eliminar una meta que ya tiene abonos registrados.');

            $pdo->prepare("DELETE FROM savings_goals WHERE id = ? AND user_id = ?")->execute([$goal_id, $user_id]);

            echo json_encode([
                'success' => true,
                'message' => "Meta \"{$goal['name']}\" eliminada correctamente.",
                'goal_id' => $goal_id,
            ]);
            break;

        default:
            throw new Exception("Acción '{$action}' no reconocida.");
    }

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    $full = 'PDOException: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine();
    echo json_encode(['success' => false, 'message' => 'Error en la base de datos. Revisa la consola.', 'full_message' => $full]);
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    echo json_encode([
        'success'      => false,
        'message'      => $e->getMessage(),
        'full_message' => 'Exception: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine(),
    ]);
}