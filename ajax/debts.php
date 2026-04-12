<?php
// ajax/debts.php - Maneja las operaciones relacionadas con deudas: listar, agregar, pagar y eliminar.

require_once '../config/database.php';

header('Content-Type: application/json');

redirectIfNotLoggedIn();

$user_id = $_SESSION['user_id'];
$action  = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {

        // ============================================
        // LISTAR DEUDAS + CUENTAS DISPONIBLES
        // ============================================
        case 'get_debts':
            $stmt = $pdo->prepare("
                SELECT *, (total_amount - paid_amount) AS pending
                FROM debts
                WHERE user_id = ?
                ORDER BY due_date ASC
            ");
            $stmt->execute([$user_id]);
            $debts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt2 = $pdo->prepare("
                SELECT a.*, c.symbol
                FROM accounts a
                JOIN currencies c ON a.currency_code = c.code
                WHERE a.user_id = ?
                AND a.type NOT IN ('debit_card', 'credit_card')
            ");
            $stmt2->execute([$user_id]);
            $accounts = $stmt2->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success'  => true,
                'debts'    => $debts,
                'accounts' => $accounts,
            ]);
            break;

        // ============================================
        // REGISTRAR DEUDA
        // ============================================
        case 'add_debt':
            $creditor  = trim($_POST['creditor'] ?? '');
            $total     = (float) ($_POST['total_amount'] ?? 0);
            $interest  = (float) ($_POST['interest_rate'] ?? 0);
            $due_date  = trim($_POST['due_date'] ?? '');

            if (empty($creditor) || $total <= 0) {
                throw new Exception('Complete todos los campos correctamente.');
            }

            $stmt = $pdo->prepare("
                INSERT INTO debts (user_id, creditor, total_amount, interest_rate, due_date)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$user_id, $creditor, $total, $interest, $due_date]);

            echo json_encode([
                'success' => true,
                'message' => 'Deuda registrada correctamente.',
                'debt_id' => (int) $pdo->lastInsertId(),
            ]);
            break;

        // ============================================
        // REGISTRAR PAGO DE DEUDA
        // ============================================
        case 'pay_debt':
            $debt_id      = (int) ($_POST['debt_id'] ?? 0);
            $account_id   = (int) ($_POST['account_id'] ?? 0);
            $amount       = (float) ($_POST['amount'] ?? 0);
            $payment_date = trim($_POST['payment_date'] ?? date('Y-m-d'));

            if ($amount <= 0) throw new Exception('El monto debe ser mayor a 0.');

            $pdo->beginTransaction();

            // Verificar cuenta
            $stmt = $pdo->prepare("
                SELECT a.*, c.symbol
                FROM accounts a
                JOIN currencies c ON a.currency_code = c.code
                WHERE a.id = ? AND a.user_id = ?
            ");
            $stmt->execute([$account_id, $user_id]);
            $account = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$account) throw new Exception('Cuenta no encontrada.');
            if ($account['currency_code'] !== 'DOP') throw new Exception('La cuenta debe estar en Pesos Dominicanos (DOP).');
            if (in_array($account['type'], ['debit_card', 'credit_card'])) throw new Exception('No se pueden usar tarjetas para pagar deudas.');
            if ($account['balance'] < $amount) {
                throw new Exception("Saldo insuficiente en '{$account['name']}'. Disponible: {$account['symbol']} " . number_format($account['balance'], 2));
            }

            // Verificar deuda
            $stmt = $pdo->prepare("SELECT * FROM debts WHERE id = ? AND user_id = ?");
            $stmt->execute([$debt_id, $user_id]);
            $debt = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$debt) throw new Exception('Deuda no encontrada.');

            $pending = $debt['total_amount'] - $debt['paid_amount'];
            if ($amount > $pending) {
                throw new Exception('El monto excede la deuda pendiente. Pendiente: RD$ ' . number_format($pending, 2));
            }

            // 1. Registrar en debt_payments
            $pdo->prepare("
                INSERT INTO debt_payments (debt_id, account_id, amount, payment_date, currency_code)
                VALUES (?, ?, ?, ?, 'DOP')
            ")->execute([$debt_id, $account_id, $amount, $payment_date]);

            $debt_payment_id = (int) $pdo->lastInsertId();

            // 2. Registrar en transactions (tipo expense, categoría 12 = Deudas)
            $description = 'Pago de deuda: ' . $debt['creditor'];
            $pdo->prepare("
                INSERT INTO transactions
                    (user_id, account_id, category_id, type, amount, original_amount,
                    original_currency, converted_amount_dop, date, description)
                VALUES (?, ?, 12, 'expense', ?, ?, 'DOP', ?, ?, ?)
            ")->execute([
                $user_id,
                $account_id,
                $amount,
                $amount,
                $amount, // converted_amount_dop = mismo valor porque es DOP
                $payment_date,
                $description,
            ]);

            // 3. Actualizar paid_amount de la deuda
            $pdo->prepare("
                UPDATE debts SET paid_amount = paid_amount + ? WHERE id = ?
            ")->execute([$amount, $debt_id]);

            $new_paid      = $debt['paid_amount'] + $amount;
            $is_fully_paid = $new_paid >= $debt['total_amount'];
            if ($is_fully_paid) {
                $pdo->prepare("UPDATE debts SET status = 'paid' WHERE id = ?")->execute([$debt_id]);
            }

            // 4. Descontar balance de la cuenta
            $pdo->prepare("
                UPDATE accounts SET balance = balance - ? WHERE id = ?
            ")->execute([$amount, $account_id]);

            $pdo->commit();

            echo json_encode([
                'success'       => true,
                'message'       => 'Se pagaron RD$ ' . number_format($amount, 2) . ' correctamente.',
                'is_fully_paid' => $is_fully_paid,
                'new_paid'      => (float) $new_paid,
                'pending'       => (float) max(0, $pending - $amount),
            ]);
            break;

        // ============================================
        // ELIMINAR DEUDA
        // ============================================
        case 'delete_debt':
            $debt_id = (int) ($_POST['debt_id'] ?? 0);

            if (!$debt_id) throw new Exception('ID de deuda inválido.');

            $stmt = $pdo->prepare("DELETE FROM debts WHERE id = ? AND user_id = ?");
            $stmt->execute([$debt_id, $user_id]);

            if ($stmt->rowCount() === 0) throw new Exception('Deuda no encontrada o sin permiso para eliminarla.');

            echo json_encode([
                'success' => true,
                'message' => 'Deuda eliminada correctamente.',
                'debt_id' => $debt_id,
            ]);
            break;
        // ============================================
        // HISTORIAL DE PAGOS DE UNA DEUDA
        // ============================================
        case 'get_payment_history':
            $debt_id    = (int) ($_GET['debt_id'] ?? $_POST['debt_id'] ?? 0);
            $date_from  = trim($_GET['date_from']  ?? $_POST['date_from']  ?? '');
            $date_to    = trim($_GET['date_to']    ?? $_POST['date_to']    ?? '');

            if (!$debt_id) throw new Exception('ID de deuda inválido.');

            // Verificar que la deuda pertenezca al usuario
            $stmt = $pdo->prepare("SELECT creditor FROM debts WHERE id = ? AND user_id = ?");
            $stmt->execute([$debt_id, $user_id]);
            $debt_row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$debt_row) throw new Exception('Deuda no encontrada.');

            $params = [$debt_id];
            $where  = 'dp.debt_id = ?';

            if ($date_from !== '') {
                $where   .= ' AND dp.payment_date >= ?';
                $params[] = $date_from;
            }
            if ($date_to !== '') {
                $where   .= ' AND dp.payment_date <= ?';
                $params[] = $date_to;
            }

            $stmt = $pdo->prepare("
                SELECT dp.id,
                    dp.amount,
                    dp.payment_date,
                    dp.currency_code,
                    a.name  AS account_name,
                    c.symbol
                FROM   debt_payments dp
                JOIN   accounts      a ON dp.account_id   = a.id
                JOIN   currencies    c ON dp.currency_code = c.code
                WHERE  {$where}
                ORDER  BY dp.payment_date DESC, dp.id DESC
            ");
            $stmt->execute($params);
            $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $total = array_sum(array_column($payments, 'amount'));

            echo json_encode([
                'success'  => true,
                'creditor' => $debt_row['creditor'],
                'payments' => $payments,
                'total'    => (float) $total,
            ]);
            break;

        // ============================================
        // REVERTIR / ELIMINAR UN PAGO
        // ============================================
        case 'delete_payment':
            $payment_id = (int) ($_POST['payment_id'] ?? 0);

            if (!$payment_id) throw new Exception('ID de pago inválido.');

            $pdo->beginTransaction();

            // Obtener el pago y verificar ownership
            $stmt = $pdo->prepare("
                SELECT dp.*, d.user_id AS debt_owner, d.total_amount, d.paid_amount, d.creditor
                FROM   debt_payments dp
                JOIN   debts         d ON dp.debt_id = d.id
                WHERE  dp.id = ?
            ");
            $stmt->execute([$payment_id]);
            $payment = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$payment) throw new Exception('Pago no encontrado.');
            if ((int)$payment['debt_owner'] !== (int)$user_id) throw new Exception('Sin permiso para revertir este pago.');

            $amount     = (float) $payment['amount'];
            $account_id = (int)   $payment['account_id'];
            $debt_id    = (int)   $payment['debt_id'];

            // 1. Eliminar el debt_payment
            $pdo->prepare("DELETE FROM debt_payments WHERE id = ?")->execute([$payment_id]);

            // 2. Eliminar la transaction asociada (misma cuenta, monto, fecha y descripción)
            $pdo->prepare("
                DELETE FROM transactions
                WHERE  user_id     = ?
                AND  account_id  = ?
                AND  amount      = ?
                AND  date        = ?
                AND  description = ?
                AND  type        = 'expense'
                AND  category_id = 12
                ORDER  BY created_at DESC
                LIMIT  1
            ")->execute([
                $user_id,
                $account_id,
                $amount,
                $payment['payment_date'],
                'Pago de deuda: ' . $payment['creditor'],
            ]);

            // 3. Restar del paid_amount y restaurar status si corresponde
            $new_paid = max(0, (float)$payment['paid_amount'] - $amount);
            $pdo->prepare("
                UPDATE debts
                SET    paid_amount = ?,
                    status      = CASE WHEN ? < total_amount THEN 'pending' ELSE status END
                WHERE  id = ?
            ")->execute([$new_paid, $new_paid, $debt_id]);

            // 4. Devolver el dinero a la cuenta
            $pdo->prepare("UPDATE accounts SET balance = balance + ? WHERE id = ?")->execute([$amount, $account_id]);

            $pdo->commit();

            echo json_encode([
                'success'    => true,
                'message'    => 'Pago de RD$ ' . number_format($amount, 2) . ' revertido correctamente.',
                'payment_id' => $payment_id,
                'debt_id'    => $debt_id,
                'new_paid'   => $new_paid,
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