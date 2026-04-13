<?php
// ajax/debts.php — Operaciones CRUD sobre deudas y sus pagos.
// Versión refactorizada: transacciones atómicas en todos los escritos,
// validaciones más estrictas y eliminación segura con reversión contable.

require_once '../config/database.php';

header('Content-Type: application/json');

redirectIfNotLoggedIn();

$user_id = (int) $_SESSION['user_id'];
$action  = $_POST['action'] ?? $_GET['action'] ?? '';

// ─── helper: respuesta de error sin repetir código ───────────────────────────
function fail(string $msg, string $full = ''): never
{
    echo json_encode([
        'success'      => false,
        'message'      => $msg,
        'full_message' => $full ?: $msg,
    ]);
    exit;
}

try {
    switch ($action) {

        // ====================================================================
        // LISTAR DEUDAS + CUENTAS DISPONIBLES
        // ====================================================================
        case 'get_debts':
            $stmt = $pdo->prepare("
                SELECT *,
                       GREATEST(0, total_amount - paid_amount) AS pending
                FROM   debts
                WHERE  user_id = ?
                ORDER  BY due_date ASC
            ");
            $stmt->execute([$user_id]);
            $debts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Solo cuentas operativas en DOP (no tarjetas)
            $stmt2 = $pdo->prepare("
                SELECT a.id, a.name, a.balance, a.type, c.symbol
                FROM   accounts    a
                JOIN   currencies  c ON a.currency_code = c.code
                WHERE  a.user_id       = ?
                AND    a.currency_code = 'DOP'
                AND    a.type NOT IN ('debit_card', 'credit_card')
                ORDER  BY a.name
            ");
            $stmt2->execute([$user_id]);
            $accounts = $stmt2->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success'  => true,
                'debts'    => $debts,
                'accounts' => $accounts,
            ]);
            break;

        // ====================================================================
        // REGISTRAR DEUDA
        // ====================================================================
        case 'add_debt':
            $creditor      = trim($_POST['creditor']      ?? '');
            $total         = (float) ($_POST['total_amount']  ?? 0);
            $interest      = (float) ($_POST['interest_rate'] ?? 0);
            $due_date      = trim($_POST['due_date']      ?? '') ?: null;

            if ($creditor === '')    fail('El nombre del acreedor es obligatorio.');
            if ($total    <= 0)     fail('El monto total debe ser mayor a 0.');
            if ($interest <  0)     fail('La tasa de interés no puede ser negativa.');
            if ($interest >= 100)   fail('La tasa de interés parece incorrecta (≥ 100 %).');

            // Validar formato de fecha si se envió
            if ($due_date !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $due_date)) {
                fail('Formato de fecha incorrecto (esperado YYYY-MM-DD).');
            }

            // La inserción de una sola fila no requiere transacción explícita,
            // pero la envolvemos para consistencia ante futuros triggers/auditoría.
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                INSERT INTO debts
                    (user_id, creditor, total_amount, interest_rate, due_date)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$user_id, $creditor, $total, $interest, $due_date]);
            $new_id = (int) $pdo->lastInsertId();

            $pdo->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Deuda registrada correctamente.',
                'debt_id' => $new_id,
            ]);
            break;

        // ====================================================================
        // REGISTRAR PAGO DE DEUDA
        // ====================================================================
        case 'pay_debt':
            $debt_id      = (int)   ($_POST['debt_id']      ?? 0);
            $account_id   = (int)   ($_POST['account_id']   ?? 0);
            $amount       = (float) ($_POST['amount']        ?? 0);
            $payment_date = trim($_POST['payment_date'] ?? date('Y-m-d'));

            if (!$debt_id)    fail('ID de deuda inválido.');
            if (!$account_id) fail('Debe seleccionar una cuenta.');
            if ($amount <= 0) fail('El monto debe ser mayor a 0.');
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $payment_date)) {
                fail('Formato de fecha incorrecto.');
            }

            $pdo->beginTransaction();

            // — Bloqueo pesimista: leer fila con FOR UPDATE para evitar
            //   pagos simultáneos que excedan la deuda pendiente.
            $stmt = $pdo->prepare("
                SELECT * FROM debts
                WHERE id = ? AND user_id = ?
                FOR UPDATE
            ");
            $stmt->execute([$debt_id, $user_id]);
            $debt = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$debt) {
                $pdo->rollBack();
                fail('Deuda no encontrada.');
            }
            if ($debt['status'] === 'paid') {
                $pdo->rollBack();
                fail('Esta deuda ya está completamente pagada.');
            }

            $pending = round((float)$debt['total_amount'] - (float)$debt['paid_amount'], 4);
            if ($amount > $pending + 0.001) {       // tolerancia de 0.1 centavo
                $pdo->rollBack();
                fail('El monto excede la deuda pendiente. Pendiente: RD$ ' . number_format($pending, 2));
            }

            // — Verificar cuenta (FOR UPDATE para evitar saldo negativo concurrente)
            $stmt = $pdo->prepare("
                SELECT a.*, c.symbol, a.currency_code
                FROM   accounts   a
                JOIN   currencies c ON a.currency_code = c.code
                WHERE  a.id = ? AND a.user_id = ?
                FOR UPDATE
            ");
            $stmt->execute([$account_id, $user_id]);
            $account = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$account) {
                $pdo->rollBack();
                fail('Cuenta no encontrada.');
            }
            if ($account['currency_code'] !== 'DOP') {
                $pdo->rollBack();
                fail('La cuenta debe estar en Pesos Dominicanos (DOP).');
            }
            if (in_array($account['type'], ['debit_card', 'credit_card'], true)) {
                $pdo->rollBack();
                fail('No se pueden usar tarjetas para pagar deudas.');
            }
            if ((float)$account['balance'] < $amount) {
                $pdo->rollBack();
                fail(
                    "Saldo insuficiente en '{$account['name']}'. " .
                    "Disponible: {$account['symbol']} " . number_format($account['balance'], 2)
                );
            }

            // 1. Registrar pago
            $pdo->prepare("
                INSERT INTO debt_payments
                    (debt_id, account_id, amount, payment_date, currency_code)
                VALUES (?, ?, ?, ?, 'DOP')
            ")->execute([$debt_id, $account_id, $amount, $payment_date]);

            $debt_payment_id = (int) $pdo->lastInsertId();

            // 2. Registrar en transactions con referencia al debt_payment_id
            //    para poder identificarla de forma exacta en delete_payment.
            $description = 'Pago de deuda: ' . $debt['creditor'];
            $pdo->prepare("
                INSERT INTO transactions
                    (user_id, account_id, category_id, type,
                     amount, original_amount, original_currency,
                     converted_amount_dop, date, description, reference_id)
                VALUES (?, ?, 12, 'expense',
                        ?, ?, 'DOP',
                        ?, ?, ?, ?)
            ")->execute([
                $user_id,
                $account_id,
                $amount,
                $amount,        // original_amount
                $amount,        // converted_amount_dop (ya es DOP)
                $payment_date,
                $description,
                $debt_payment_id,   // ← vínculo directo, elimina búsqueda frágil
            ]);

            // 3. Actualizar deuda
            $new_paid      = round((float)$debt['paid_amount'] + $amount, 4);
            $is_fully_paid = $new_paid >= (float)$debt['total_amount'] - 0.001;

            $pdo->prepare("
                UPDATE debts
                SET paid_amount = ?,
                    status      = IF(? >= total_amount - 0.001, 'paid', status)
                WHERE id = ?
            ")->execute([$new_paid, $new_paid, $debt_id]);

            // 4. Descontar saldo de la cuenta
            $pdo->prepare("
                UPDATE accounts SET balance = balance - ? WHERE id = ?
            ")->execute([$amount, $account_id]);

            $pdo->commit();

            echo json_encode([
                'success'        => true,
                'message'        => 'Se pagaron RD$ ' . number_format($amount, 2) . ' correctamente.',
                'is_fully_paid'  => $is_fully_paid,
                'new_paid'       => $new_paid,
                'pending'        => round(max(0, $pending - $amount), 4),
                'debt_payment_id'=> $debt_payment_id,
            ]);
            break;

        // ====================================================================
        // ELIMINAR DEUDA
        // Revierte también todos los pagos asociados (saldo + transacciones)
        // para no dejar la contabilidad descuadrada.
        // ====================================================================
        case 'delete_debt':
            $debt_id = (int) ($_POST['debt_id'] ?? 0);

            if (!$debt_id) fail('ID de deuda inválido.');

            $pdo->beginTransaction();

            // Confirmar propiedad
            $stmt = $pdo->prepare("
                SELECT * FROM debts WHERE id = ? AND user_id = ? FOR UPDATE
            ");
            $stmt->execute([$debt_id, $user_id]);
            $debt = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$debt) {
                $pdo->rollBack();
                fail('Deuda no encontrada o sin permiso para eliminarla.');
            }

            // Obtener pagos existentes para revertir saldos
            $stmt = $pdo->prepare("
                SELECT dp.account_id, dp.amount, dp.id AS payment_id
                FROM   debt_payments dp
                WHERE  dp.debt_id = ?
                FOR UPDATE
            ");
            $stmt->execute([$debt_id]);
            $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($payments as $p) {
                // Devolver dinero a la cuenta
                $pdo->prepare("
                    UPDATE accounts SET balance = balance + ? WHERE id = ?
                ")->execute([(float)$p['amount'], (int)$p['account_id']]);

                // Eliminar transacción contable vinculada
                $pdo->prepare("
                    DELETE FROM transactions
                    WHERE reference_id = ?
                    AND   category_id  = 12
                    AND   type         = 'expense'
                    AND   user_id      = ?
                    LIMIT 1
                ")->execute([(int)$p['payment_id'], $user_id]);
            }

            // Eliminar pagos y deuda (FK ON DELETE CASCADE también cubriría
            // debt_payments, pero lo hacemos explícito para mayor control)
            $pdo->prepare("DELETE FROM debt_payments WHERE debt_id = ?")->execute([$debt_id]);
            $pdo->prepare("DELETE FROM debts WHERE id = ? AND user_id = ?")->execute([$debt_id, $user_id]);

            $pdo->commit();

            echo json_encode([
                'success'          => true,
                'message'          => 'Deuda eliminada y pagos revertidos correctamente.',
                'debt_id'          => $debt_id,
                'payments_reverted'=> count($payments),
            ]);
            break;

        // ====================================================================
        // HISTORIAL DE PAGOS DE UNA DEUDA
        // ====================================================================
        case 'get_payment_history':
            $debt_id   = (int)   ($_GET['debt_id']   ?? $_POST['debt_id']   ?? 0);
            $date_from = trim($_GET['date_from'] ?? $_POST['date_from'] ?? '');
            $date_to   = trim($_GET['date_to']   ?? $_POST['date_to']   ?? '');

            if (!$debt_id) fail('ID de deuda inválido.');

            // Validar fechas si se enviaron
            foreach (['date_from' => $date_from, 'date_to' => $date_to] as $field => $val) {
                if ($val !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $val)) {
                    fail("Formato de $field incorrecto (esperado YYYY-MM-DD).");
                }
            }

            // Verificar propiedad
            $stmt = $pdo->prepare("
                SELECT creditor FROM debts WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$debt_id, $user_id]);
            $debt_row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$debt_row) fail('Deuda no encontrada.');

            // Construir cláusula WHERE con parámetros posicionales
            $where  = 'dp.debt_id = ?';
            $params = [$debt_id];

            if ($date_from !== '') { $where .= ' AND dp.payment_date >= ?'; $params[] = $date_from; }
            if ($date_to   !== '') { $where .= ' AND dp.payment_date <= ?'; $params[] = $date_to;   }

            $stmt = $pdo->prepare("
                SELECT dp.id,
                       dp.amount,
                       dp.payment_date,
                       dp.currency_code,
                       a.name   AS account_name,
                       c.symbol
                FROM   debt_payments dp
                JOIN   accounts      a ON dp.account_id    = a.id
                JOIN   currencies    c ON dp.currency_code = c.code
                WHERE  {$where}
                ORDER  BY dp.payment_date DESC, dp.id DESC
            ");
            $stmt->execute($params);
            $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success'  => true,
                'creditor' => $debt_row['creditor'],
                'payments' => $payments,
                'total'    => (float) array_sum(array_column($payments, 'amount')),
            ]);
            break;

        // ====================================================================
        // REVERTIR / ELIMINAR UN PAGO INDIVIDUAL
        // ====================================================================
        case 'delete_payment':
            $payment_id = (int) ($_POST['payment_id'] ?? 0);

            if (!$payment_id) fail('ID de pago inválido.');

            $pdo->beginTransaction();

            // Obtener pago con bloqueo y verificar propiedad
            $stmt = $pdo->prepare("
                SELECT dp.*,
                       d.user_id    AS debt_owner,
                       d.total_amount,
                       d.paid_amount,
                       d.creditor
                FROM   debt_payments dp
                JOIN   debts         d ON dp.debt_id = d.id
                WHERE  dp.id = ?
                FOR UPDATE
            ");
            $stmt->execute([$payment_id]);
            $payment = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$payment) {
                $pdo->rollBack();
                fail('Pago no encontrado.');
            }
            if ((int)$payment['debt_owner'] !== $user_id) {
                $pdo->rollBack();
                fail('Sin permiso para revertir este pago.');
            }

            $amount     = (float) $payment['amount'];
            $account_id = (int)   $payment['account_id'];
            $debt_id    = (int)   $payment['debt_id'];

            // 1. Eliminar pago
            $pdo->prepare("DELETE FROM debt_payments WHERE id = ?")->execute([$payment_id]);

            // 2. Eliminar la transacción usando reference_id (vínculo directo,
            //    sin riesgo de borrar una transacción equivocada por coincidencia).
            $deleted = $pdo->prepare("
                DELETE FROM transactions
                WHERE reference_id = ?
                AND   category_id  = 12
                AND   type         = 'expense'
                AND   user_id      = ?
                LIMIT 1
            ");
            $deleted->execute([$payment_id, $user_id]);

            // Fallback: si reference_id no existe (registros previos a la migración)
            if ($deleted->rowCount() === 0) {
                $pdo->prepare("
                    DELETE FROM transactions
                    WHERE user_id     = ?
                    AND   account_id  = ?
                    AND   amount      = ?
                    AND   date        = ?
                    AND   description = ?
                    AND   type        = 'expense'
                    AND   category_id = 12
                    ORDER BY created_at DESC
                    LIMIT 1
                ")->execute([
                    $user_id,
                    $account_id,
                    $amount,
                    $payment['payment_date'],
                    'Pago de deuda: ' . $payment['creditor'],
                ]);
            }

            // 3. Ajustar paid_amount y status de la deuda
            $new_paid = round(max(0, (float)$payment['paid_amount'] - $amount), 4);
            $pdo->prepare("
                UPDATE debts
                SET paid_amount = ?,
                    status      = CASE
                                    WHEN ? < total_amount - 0.001 THEN 'pending'
                                    ELSE status
                                  END
                WHERE id = ?
            ")->execute([$new_paid, $new_paid, $debt_id]);

            // 4. Restaurar saldo en la cuenta
            $pdo->prepare("
                UPDATE accounts SET balance = balance + ? WHERE id = ?
            ")->execute([$amount, $account_id]);

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
            fail("Acción '{$action}' no reconocida.");
    }

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    $full = 'PDOException: ' . $e->getMessage()
          . ' | ' . $e->getFile() . ':' . $e->getLine();
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
        'full_message' => 'Exception: ' . $e->getMessage()
                        . ' | ' . $e->getFile() . ':' . $e->getLine(),
    ]);
}