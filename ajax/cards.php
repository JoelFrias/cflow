<?php
// ajax/cards.php - Maneja todas las operaciones relacionadas con tarjetas (debit y credit) y sus transacciones asociadas.

require_once '../config/database.php';

header('Content-Type: application/json');

redirectIfNotLoggedIn();

$user_id = $_SESSION['user_id'];
$action  = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {

        // ============================================
        // OBTENER TODAS LAS TARJETAS + RESUMEN
        // ============================================
        case 'get_cards':
            $usd_rate = getUSDRate($pdo);

            $stmt = $pdo->prepare("
                SELECT * FROM accounts
                WHERE user_id = ? AND type IN ('debit_card', 'credit_card')
                ORDER BY name
            ");
            $stmt->execute([$user_id]);
            $cards = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt2 = $pdo->prepare("
                SELECT id, name, balance FROM accounts
                WHERE user_id = ? AND currency_code = 'DOP' AND type IN ('cash', 'bank')
            ");
            $stmt2->execute([$user_id]);
            $dop_accounts = $stmt2->fetchAll(PDO::FETCH_ASSOC);

            $stmt3 = $pdo->prepare("
                SELECT id, name FROM categories
                WHERE user_id = ? AND type = 'expense'
                ORDER BY name
            ");
            $stmt3->execute([$user_id]);
            $categories = $stmt3->fetchAll(PDO::FETCH_ASSOC);

            $total_balance_usd = 0;
            $total_balance_dop = 0;
            $total_limit_usd   = 0;
            $total_limit_dop   = 0;
            $cards_near_limit  = [];
            $cards_over_limit  = [];

            foreach ($cards as $card) {
                $total_balance_usd += $card['balance_usd'];
                $total_balance_dop += $card['balance_dop'];
                $total_limit_usd   += $card['credit_limit_usd'] ?? 0;
                $total_limit_dop   += $card['credit_limit_dop'] ?? 0;

                if (!empty($card['credit_limit_usd']) && $card['balance_usd'] > 0) {
                    $pct = ($card['balance_usd'] / $card['credit_limit_usd']) * 100;
                    if ($pct > 80)  $cards_near_limit[] = ['name' => $card['name'], 'currency' => 'USD', 'percent' => round($pct, 1)];
                    if ($pct >= 100) $cards_over_limit[] = ['name' => $card['name'], 'currency' => 'USD', 'percent' => round($pct, 1)];
                }
                if (!empty($card['credit_limit_dop']) && $card['balance_dop'] > 0) {
                    $pct = ($card['balance_dop'] / $card['credit_limit_dop']) * 100;
                    if ($pct > 80)  $cards_near_limit[] = ['name' => $card['name'], 'currency' => 'DOP', 'percent' => round($pct, 1)];
                    if ($pct >= 100) $cards_over_limit[] = ['name' => $card['name'], 'currency' => 'DOP', 'percent' => round($pct, 1)];
                }
            }

            echo json_encode([
                'success'           => true,
                'cards'             => $cards,
                'dop_accounts'      => $dop_accounts,
                'categories'        => $categories,
                'usd_rate'          => (float) $usd_rate,
                'total_balance_usd' => (float) $total_balance_usd,
                'total_balance_dop' => (float) $total_balance_dop,
                'total_limit_usd'   => (float) $total_limit_usd,
                'total_limit_dop'   => (float) $total_limit_dop,
                'cards_near_limit'  => $cards_near_limit,
                'cards_over_limit'  => $cards_over_limit,
            ]);
            break;

        // ============================================
        // CREAR TARJETA
        // ============================================
        case 'create_card':
            $name             = trim($_POST['name'] ?? '');
            $type             = trim($_POST['type'] ?? '');
            $credit_limit_usd = !empty($_POST['credit_limit_usd']) ? (float) $_POST['credit_limit_usd'] : null;
            $credit_limit_dop = !empty($_POST['credit_limit_dop']) ? (float) $_POST['credit_limit_dop'] : null;

            if (empty($name)) throw new Exception('El nombre de la tarjeta es obligatorio.');
            if (!in_array($type, ['debit_card', 'credit_card'])) throw new Exception('Tipo de tarjeta inválido.');

            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO accounts (user_id, name, type, credit_limit_usd, credit_limit_dop, balance_usd, balance_dop)
                    VALUES (?, ?, ?, ?, ?, 0, 0)
                ");
                $stmt->execute([$user_id, $name, $type, $credit_limit_usd, $credit_limit_dop]);
                $new_id = $pdo->lastInsertId();

                $stmt2 = $pdo->prepare("SELECT * FROM accounts WHERE id = ?");
                $stmt2->execute([$new_id]);
                $new_card = $stmt2->fetch(PDO::FETCH_ASSOC);

                $pdo->commit();
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }

            echo json_encode(['success' => true, 'message' => 'Tarjeta creada correctamente.', 'card' => $new_card]);
            break;

        // ============================================
        // REGISTRAR GASTO EN TARJETA
        // ============================================
        case 'add_card_expense':
            $card_id     = (int) ($_POST['card_id'] ?? 0);
            $currency    = trim($_POST['currency'] ?? '');
            $amount      = (float) ($_POST['amount'] ?? 0);
            $description = trim($_POST['description'] ?? '');
            $date        = trim($_POST['date'] ?? '');
            $category_id = !empty($_POST['category_id']) ? (int) $_POST['category_id'] : null;

            if ($amount <= 0)  throw new Exception('El monto debe ser mayor a 0.');
            if (!in_array($currency, ['USD', 'DOP'])) throw new Exception('Moneda inválida.');
            if (empty($date)) throw new Exception('La fecha es obligatoria.');

            $pdo->beginTransaction();
            try {
                // FOR UPDATE: bloqueo pesimista para evitar race conditions en el límite de crédito
                $stmt = $pdo->prepare("
                    SELECT balance_usd, balance_dop, credit_limit_usd, credit_limit_dop
                    FROM accounts WHERE id = ? AND user_id = ? FOR UPDATE
                ");
                $stmt->execute([$card_id, $user_id]);
                $card = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$card) throw new Exception('Tarjeta no encontrada.');

                if ($currency === 'USD') {
                    $new_balance = $card['balance_usd'] + $amount;
                    if (!empty($card['credit_limit_usd']) && $new_balance > $card['credit_limit_usd']) {
                        throw new Exception('Límite de crédito en USD excedido. Límite: $' . number_format($card['credit_limit_usd'], 2));
                    }
                    $pdo->prepare("UPDATE accounts SET balance_usd = ? WHERE id = ?")->execute([$new_balance, $card_id]);
                } else {
                    $new_balance = $card['balance_dop'] + $amount;
                    if (!empty($card['credit_limit_dop']) && $new_balance > $card['credit_limit_dop']) {
                        throw new Exception('Límite de crédito en DOP excedido. Límite: RD$ ' . number_format($card['credit_limit_dop'], 2));
                    }
                    $pdo->prepare("UPDATE accounts SET balance_dop = ? WHERE id = ?")->execute([$new_balance, $card_id]);
                }

                // Obtener tasa dentro de la transacción para consistencia
                $usd_rate      = getUSDRate($pdo);
                $converted_dop = ($currency === 'USD') ? $amount * $usd_rate : $amount;

                $stmt = $pdo->prepare("
                    INSERT INTO transactions
                        (user_id, account_id, category_id, type, amount, original_amount,
                         original_currency, card_currency, converted_amount_dop, date, description)
                    VALUES (?, ?, ?, 'expense', ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$user_id, $card_id, $category_id, $amount, $amount, $currency, $currency, $converted_dop, $date, $description]);

                $pdo->commit();
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }

            $symbol = $currency === 'USD' ? '$' : 'RD$';
            echo json_encode(['success' => true, 'message' => "Gasto registrado en {$currency}: {$symbol}" . number_format($amount, 2)]);
            break;

        // ============================================
        // PAGAR TARJETA
        // ============================================
        case 'pay_card':
            $card_id            = (int) ($_POST['card_id'] ?? 0);
            $account_id         = (int) ($_POST['account_id'] ?? 0);
            $pay_usd            = (float) ($_POST['pay_usd'] ?? 0);
            $pay_dop            = (float) ($_POST['pay_dop'] ?? 0);
            $dop_amount_for_usd = (float) ($_POST['dop_amount_for_usd'] ?? 0);
            $payment_date       = trim($_POST['payment_date'] ?? '');
            $total_dop_to_debit = $pay_dop + $dop_amount_for_usd;

            if ($pay_usd <= 0 && $pay_dop <= 0) throw new Exception('Debe ingresar al menos un monto a pagar.');
            if ($pay_usd > 0 && $dop_amount_for_usd <= 0) throw new Exception('Para pagar en USD debe especificar el monto en DOP a debitar.');
            if (empty($payment_date)) throw new Exception('La fecha de pago es obligatoria.');

            $pdo->beginTransaction();
            try {
                // Bloquear cuenta de origen y tarjeta simultáneamente para evitar race conditions
                // Orden fijo de IDs para prevenir deadlocks
                $lock_ids = [$account_id, $card_id];
                sort($lock_ids);

                foreach ($lock_ids as $lock_id) {
                    $pdo->prepare("SELECT id FROM accounts WHERE id = ? AND user_id = ? FOR UPDATE")
                        ->execute([$lock_id, $user_id]);
                }

                // Leer saldos actualizados tras el bloqueo
                $stmt = $pdo->prepare("SELECT balance FROM accounts WHERE id = ? AND user_id = ?");
                $stmt->execute([$account_id, $user_id]);
                $account = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$account) throw new Exception('Cuenta de origen no encontrada.');
                if ($account['balance'] < $total_dop_to_debit) {
                    throw new Exception('Saldo insuficiente. Disponible: RD$ ' . number_format($account['balance'], 2));
                }

                $stmt = $pdo->prepare("SELECT balance_usd, balance_dop FROM accounts WHERE id = ? AND user_id = ?");
                $stmt->execute([$card_id, $user_id]);
                $card = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$card) throw new Exception('Tarjeta no encontrada.');
                if ($pay_usd > 0 && $card['balance_usd'] < $pay_usd) throw new Exception('Balance USD insuficiente en la tarjeta.');
                if ($pay_dop > 0 && $card['balance_dop'] < $pay_dop) throw new Exception('Balance DOP insuficiente en la tarjeta.');

                // Aplicar movimientos
                $pdo->prepare("UPDATE accounts SET balance = balance - ? WHERE id = ?")
                    ->execute([$total_dop_to_debit, $account_id]);

                if ($pay_usd > 0) {
                    $pdo->prepare("UPDATE accounts SET balance_usd = balance_usd - ? WHERE id = ?")
                        ->execute([$pay_usd, $card_id]);
                }
                if ($pay_dop > 0) {
                    $pdo->prepare("UPDATE accounts SET balance_dop = balance_dop - ? WHERE id = ?")
                        ->execute([$pay_dop, $card_id]);
                }

                $description = 'Pago de tarjeta';
                if ($pay_usd > 0) $description .= ' - USD: $' . number_format($pay_usd, 2) . ' (RD$ ' . number_format($dop_amount_for_usd, 2) . ')';
                if ($pay_dop > 0) $description .= ' - DOP: RD$ ' . number_format($pay_dop, 2);

                // FIX: se guarda transfer_to_account = card_id para que
                // get_card_transactions pueda vincular pagos a la tarjeta correcta.
                $stmt = $pdo->prepare("
                    INSERT INTO transactions
                        (user_id, account_id, category_id, type, amount, original_amount,
                         original_currency, payment_usd_amount, payment_dop_amount,
                         date, description, transfer_to_account)
                    VALUES (?, ?, NULL, 'expense', ?, ?, 'DOP', ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $user_id,
                    $account_id,
                    $total_dop_to_debit,
                    $total_dop_to_debit,
                    $pay_usd,
                    $pay_dop,
                    $payment_date,
                    $description,
                    $card_id,             // transfer_to_account = card_id ← fix clave
                ]);

                $pdo->commit();
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }

            $summary = [];
            if ($pay_usd > 0) $summary[] = "💵 USD pagados: $" . number_format($pay_usd, 2) . " (RD$ " . number_format($dop_amount_for_usd, 2) . " debitados)";
            if ($pay_dop > 0) $summary[] = "🇩🇴 DOP pagados: RD$ " . number_format($pay_dop, 2);
            $summary[] = "💰 Total descontado: RD$ " . number_format($total_dop_to_debit, 2);

            echo json_encode(['success' => true, 'message' => '¡Pago registrado exitosamente!', 'summary' => $summary]);
            break;

        // ============================================
        // ELIMINAR TARJETA
        // ============================================
        case 'delete_card':
            $card_id = (int) ($_POST['card_id'] ?? 0);
            if ($card_id <= 0) throw new Exception('ID de tarjeta inválido.');

            $pdo->beginTransaction();
            try {
                // Bloqueo pesimista para evitar eliminación concurrente
                $stmt = $pdo->prepare("
                    SELECT balance_usd, balance_dop FROM accounts
                    WHERE id = ? AND user_id = ? FOR UPDATE
                ");
                $stmt->execute([$card_id, $user_id]);
                $card = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$card) throw new Exception('Tarjeta no encontrada.');
                if ($card['balance_usd'] > 0 || $card['balance_dop'] > 0) {
                    throw new Exception('No puedes eliminar una tarjeta con deuda pendiente.');
                }

                $pdo->prepare("DELETE FROM accounts WHERE id = ? AND user_id = ?")
                    ->execute([$card_id, $user_id]);

                $pdo->commit();
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }

            echo json_encode(['success' => true, 'message' => 'Tarjeta eliminada correctamente.', 'card_id' => $card_id]);
            break;

        // ============================================
        // OBTENER TRANSACCIONES DE UNA TARJETA CON FILTROS
        // ============================================
        case 'get_card_transactions':
            $card_id     = (int) ($_POST['card_id'] ?? 0);
            $date_from   = trim($_POST['date_from'] ?? '');
            $date_to     = trim($_POST['date_to']   ?? '');
            $type_filter = trim($_POST['type']      ?? '');

            if ($card_id <= 0) throw new Exception('ID de tarjeta inválido.');

            // Verificar propiedad
            $chk = $pdo->prepare("
                SELECT id, name FROM accounts
                WHERE id = ? AND user_id = ? AND type IN ('debit_card','credit_card')
            ");
            $chk->execute([$card_id, $user_id]);
            $card = $chk->fetch(PDO::FETCH_ASSOC);
            if (!$card) throw new Exception('Tarjeta no encontrada o sin permiso.');

            // Construir filtros de fecha reutilizables
            $date_where  = '';
            $date_params = [];
            if ($date_from !== '') { $date_where .= ' AND t.date >= ?'; $date_params[] = $date_from; }
            if ($date_to   !== '') { $date_where .= ' AND t.date <= ?'; $date_params[] = $date_to;   }

            // Gastos directos en la tarjeta
            $sql_expenses = "
                SELECT
                    t.id,
                    t.date,
                    t.description,
                    t.amount,
                    t.original_currency          AS currency,
                    t.converted_amount_dop,
                    'expense'                    AS row_type,
                    cat.name                     AS category_name
                FROM transactions t
                LEFT JOIN categories cat ON t.category_id = cat.id
                WHERE t.user_id    = ?
                  AND t.account_id = ?
                  AND t.type       = 'expense'
                  AND (t.payment_usd_amount IS NULL OR t.payment_usd_amount = 0)
                  AND (t.payment_dop_amount IS NULL OR t.payment_dop_amount = 0)
                {$date_where}
            ";
            $params_expenses = array_merge([$user_id, $card_id], $date_params);

            // Pagos a la tarjeta vinculados por transfer_to_account = card_id
            // (correcto gracias al fix en pay_card)
            $sql_payments = "
                SELECT
                    t.id,
                    t.date,
                    t.description,
                    t.amount,
                    'DOP'   AS currency,
                    t.amount AS converted_amount_dop,
                    'payment' AS row_type,
                    NULL    AS category_name
                FROM transactions t
                WHERE t.user_id            = ?
                  AND t.transfer_to_account = ?
                  AND t.type               = 'expense'
                {$date_where}
            ";
            $params_payments = array_merge([$user_id, $card_id], $date_params);

            // Seleccionar conjunto según filtro de tipo
            if ($type_filter === 'expense') {
                $sql    = $sql_expenses . " ORDER BY date DESC, id DESC LIMIT 200";
                $params = $params_expenses;
            } elseif ($type_filter === 'payment') {
                $sql    = $sql_payments  . " ORDER BY date DESC, id DESC LIMIT 200";
                $params = $params_payments;
            } else {
                $sql = "
                    SELECT * FROM (
                        {$sql_expenses}
                        UNION ALL
                        {$sql_payments}
                    ) combined
                    ORDER BY date DESC, id DESC
                    LIMIT 200
                ";
                $params = array_merge($params_expenses, $params_payments);
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Balance acumulado en orden cronológico inverso
            $stmtBal = $pdo->prepare("SELECT balance_usd, balance_dop FROM accounts WHERE id = ?");
            $stmtBal->execute([$card_id]);
            $currentBal  = $stmtBal->fetch(PDO::FETCH_ASSOC);
            $running_usd = (float) $currentBal['balance_usd'];
            $running_dop = (float) $currentBal['balance_dop'];

            foreach ($transactions as &$t) {
                $t['currency_symbol'] = $t['currency'] === 'USD' ? '$' : 'RD$';

                if ($t['row_type'] === 'expense') {
                    if ($t['currency'] === 'USD') {
                        $t['balance_after'] = $running_usd;
                        $running_usd = round($running_usd - (float) $t['amount'], 2);
                    } else {
                        $t['balance_after'] = $running_dop;
                        $running_dop = round($running_dop - (float) $t['amount'], 2);
                    }
                } else {
                    // Pago: redujo la deuda → sumar de vuelta para reconstruir hacia atrás
                    $t['balance_after'] = $running_dop;
                    $running_dop = round($running_dop + (float) $t['amount'], 2);
                }
            }
            unset($t);

            echo json_encode([
                'success'      => true,
                'transactions' => $transactions,
                'card_name'    => $card['name'],
            ]);
            break;

        // ============================================
        // ELIMINAR TRANSACCIÓN DE TARJETA (≤ 3 días)
        // ============================================
        case 'delete_card_transaction':
            $transaction_id = (int)($_POST['transaction_id'] ?? 0);
            $card_id        = (int)($_POST['card_id'] ?? 0);

            if ($transaction_id <= 0) throw new Exception('ID de transacción inválido.');
            if ($card_id <= 0)        throw new Exception('ID de tarjeta inválido.');

            // Verificar propiedad de la tarjeta
            $chk = $pdo->prepare("
                SELECT id FROM accounts
                WHERE id = ? AND user_id = ? AND type IN ('debit_card','credit_card')
            ");
            $chk->execute([$card_id, $user_id]);
            if (!$chk->fetch()) throw new Exception('Tarjeta no encontrada o sin permiso.');

            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare("SELECT * FROM transactions WHERE id = ? AND user_id = ? FOR UPDATE");
                $stmt->execute([$transaction_id, $user_id]);
                $tx = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$tx) throw new Exception('Transacción no encontrada.');

                // Verificar antigüedad ≤ 3 días (comparar date, no datetime)
                $tz       = new DateTimeZone('-04:00');
                $txDate   = new DateTime($tx['date'], $tz);
                $today    = new DateTime('now', $tz);
                $today->setTime(0, 0, 0);
                $daysDiff = (int)$today->diff($txDate)->days;
                if ($today < $txDate) $daysDiff = 0; // fecha futura = 0 días
                if ($daysDiff > 3) {
                    throw new Exception('Solo puedes eliminar transacciones con 3 días o menos de antigüedad.');
                }

                // Determinar si es gasto directo o pago a tarjeta
                $pay_usd = (float)($tx['payment_usd_amount'] ?? 0);
                $pay_dop_col = (float)($tx['payment_dop_amount'] ?? 0);

                $is_expense = ((int)$tx['account_id'] === $card_id && $pay_usd == 0 && $pay_dop_col == 0);
                $is_payment = ((int)$tx['transfer_to_account'] === $card_id);

                if (!$is_expense && !$is_payment) {
                    throw new Exception('Esta transacción no está vinculada a la tarjeta especificada.');
                }

                if ($is_expense) {
                    // Revertir: reducir balance de la tarjeta
                    $currency = $tx['original_currency'] ?? $tx['card_currency'] ?? 'DOP';
                    if ($currency === 'USD') {
                        $pdo->prepare("UPDATE accounts SET balance_usd = balance_usd - ? WHERE id = ? AND user_id = ?")
                            ->execute([$tx['amount'], $card_id, $user_id]);
                    } else {
                        $pdo->prepare("UPDATE accounts SET balance_dop = balance_dop - ? WHERE id = ? AND user_id = ?")
                            ->execute([$tx['amount'], $card_id, $user_id]);
                    }
                } else {
                    // Revertir pago: devolver DOP a la cuenta de origen
                    $pdo->prepare("UPDATE accounts SET balance = balance + ? WHERE id = ? AND user_id = ?")
                        ->execute([$tx['amount'], $tx['account_id'], $user_id]);

                    // Restaurar deuda en la tarjeta
                    if ($pay_usd > 0) {
                        $pdo->prepare("UPDATE accounts SET balance_usd = balance_usd + ? WHERE id = ? AND user_id = ?")
                            ->execute([$pay_usd, $card_id, $user_id]);
                    }
                    if ($pay_dop_col > 0) {
                        $pdo->prepare("UPDATE accounts SET balance_dop = balance_dop + ? WHERE id = ? AND user_id = ?")
                            ->execute([$pay_dop_col, $card_id, $user_id]);
                    }
                }

                $pdo->prepare("DELETE FROM transactions WHERE id = ? AND user_id = ?")
                    ->execute([$transaction_id, $user_id]);

                $pdo->commit();
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }

            echo json_encode([
                'success' => true,
                'message' => 'Transacción eliminada y balances restaurados correctamente.',
            ]);
            break;

        default:
            throw new Exception("Acción '{$action}' no reconocida.");
    }

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    echo json_encode([
        'success'      => false,
        'message'      => 'Error en la base de datos. Revisa la consola.',
        'full_message' => 'PDOException: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine(),
    ]);
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    echo json_encode([
        'success'      => false,
        'message'      => $e->getMessage(),
        'full_message' => 'Exception: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine(),
    ]);
}