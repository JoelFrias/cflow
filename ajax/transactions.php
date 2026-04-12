<?php
// ajax/transactions.php - Maneja operaciones CRUD para transacciones, con filtros y paginación.

require_once '../config/database.php';

header('Content-Type: application/json');

redirectIfNotLoggedIn();

$user_id = $_SESSION['user_id'];
$action  = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {

        // ============================================
        // OBTENER TRANSACCIONES CON FILTROS + PAGINACIÓN
        // ============================================
        case 'get_transactions':
            $filter_account    = !empty($_GET['filter_account'])    ? (int) $_GET['filter_account']    : null;
            $filter_category   = !empty($_GET['filter_category'])   ? (int) $_GET['filter_category']   : null;
            $filter_type       = !empty($_GET['filter_type'])       ? $_GET['filter_type']              : null;
            $filter_min_amount = isset($_GET['filter_min_amount'])  && $_GET['filter_min_amount'] !== '' ? (float) $_GET['filter_min_amount'] : null;
            $filter_max_amount = isset($_GET['filter_max_amount'])  && $_GET['filter_max_amount'] !== '' ? (float) $_GET['filter_max_amount'] : null;
            $filter_date_from  = !empty($_GET['filter_date_from'])  ? $_GET['filter_date_from']         : null;
            $filter_date_to    = !empty($_GET['filter_date_to'])    ? $_GET['filter_date_to']           : null;
            $filter_search     = !empty($_GET['filter_search'])     ? trim($_GET['filter_search'])      : null;

            $allowed_limits = [10, 25, 50, 100];
            $limit  = in_array((int) ($_GET['limit'] ?? 10), $allowed_limits) ? (int) $_GET['limit'] : 10;
            $page   = max(1, (int) ($_GET['page'] ?? 1));
            $offset = ($page - 1) * $limit;

            // Construir WHERE dinámico
            $where  = ["t.user_id = ?"];
            $params = [$user_id];

            if ($filter_account) { $where[] = "t.account_id = ?";           $params[] = $filter_account; }
            if ($filter_category) { $where[] = "t.category_id = ?";         $params[] = $filter_category; }
            if ($filter_type && in_array($filter_type, ['income','expense','transfer'])) {
                $where[] = "t.type = ?"; $params[] = $filter_type;
            }
            if ($filter_min_amount !== null) { $where[] = "t.converted_amount_dop >= ?"; $params[] = $filter_min_amount; }
            if ($filter_max_amount !== null) { $where[] = "t.converted_amount_dop <= ?"; $params[] = $filter_max_amount; }
            if ($filter_date_from) { $where[] = "t.date >= ?"; $params[] = $filter_date_from; }
            if ($filter_date_to)   { $where[] = "t.date <= ?"; $params[] = $filter_date_to; }
            if ($filter_search) {
                $where[]  = "(t.description LIKE ? OR a.name LIKE ? OR c.name LIKE ?)";
                $like     = "%{$filter_search}%";
                $params[] = $like; $params[] = $like; $params[] = $like;
            }

            $where_clause = implode(' AND ', $where);

            // Contar total
            $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM transactions t
                LEFT JOIN accounts a    ON t.account_id   = a.id
                LEFT JOIN categories c  ON t.category_id  = c.id
                WHERE {$where_clause}");
            $count_stmt->execute($params);
            $total_records = (int) $count_stmt->fetchColumn();
            $total_pages   = $limit > 0 ? (int) ceil($total_records / $limit) : 1;

            // Listar con paginación
            $list_stmt = $pdo->prepare("
                SELECT t.*, a.name AS account_name, a.currency_code,
                       c.name AS category_name, curr.symbol
                FROM transactions t
                LEFT JOIN accounts a    ON t.account_id        = a.id
                LEFT JOIN categories c  ON t.category_id       = c.id
                LEFT JOIN currencies curr ON t.original_currency = curr.code
                WHERE {$where_clause}
                ORDER BY t.id DESC
                LIMIT ? OFFSET ?
            ");

            $i = 1;
            foreach ($params as $p) {
                $list_stmt->bindValue($i++, $p, is_int($p) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            $list_stmt->bindValue($i++, $limit,  PDO::PARAM_INT);
            $list_stmt->bindValue($i,   $offset, PDO::PARAM_INT);
            $list_stmt->execute();
            $transactions = $list_stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success'       => true,
                'transactions'  => $transactions,
                'total_records' => $total_records,
                'total_pages'   => $total_pages,
                'page'          => $page,
                'limit'         => $limit,
            ]);
            break;

        // ============================================
        // OBTENER DATOS PARA MODALES (cuentas, categorías, monedas)
        // ============================================
        case 'get_form_data':
            $stmt = $pdo->prepare("
                SELECT a.*, c.symbol, c.exchange_rate_to_dop
                FROM accounts a
                JOIN currencies c ON a.currency_code = c.code
                WHERE a.user_id = ? AND a.type NOT IN ('debit_card','credit_card')
            ");
            $stmt->execute([$user_id]);
            $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt2 = $pdo->prepare("SELECT * FROM categories WHERE user_id = ? AND type = 'income' ORDER BY name");
            $stmt2->execute([$user_id]);
            $income_categories = $stmt2->fetchAll(PDO::FETCH_ASSOC);

            $stmt3 = $pdo->prepare("SELECT * FROM categories WHERE user_id = ? AND type = 'expense' ORDER BY name");
            $stmt3->execute([$user_id]);
            $expense_categories = $stmt3->fetchAll(PDO::FETCH_ASSOC);

            $all_currencies = $pdo->query("SELECT * FROM currencies")->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success'            => true,
                'accounts'           => $accounts,
                'income_categories'  => $income_categories,
                'expense_categories' => $expense_categories,
                'currencies'         => $all_currencies,
            ]);
            break;

        // ============================================
        // AGREGAR TRANSACCIÓN
        // ============================================
        case 'add_transaction':
            $account_id       = (int) ($_POST['account_id'] ?? 0);
            $category_id      = !empty($_POST['category_id']) ? (int) $_POST['category_id'] : null;
            $type             = trim($_POST['type'] ?? '');
            $amount           = (float) ($_POST['amount'] ?? 0);
            $date             = trim($_POST['date'] ?? '');
            $description      = trim($_POST['description'] ?? '');
            $transfer_to      = !empty($_POST['transfer_to']) ? (int) $_POST['transfer_to'] : null;
            $payment_currency = !empty($_POST['payment_currency']) ? trim($_POST['payment_currency']) : null;

            if ($amount <= 0) throw new Exception('El monto debe ser mayor a 0.');
            if (!in_array($type, ['income', 'expense', 'transfer'])) throw new Exception('Tipo de transacción inválido.');
            if (empty($date)) throw new Exception('La fecha es obligatoria.');

            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                SELECT a.*, c.exchange_rate_to_dop
                FROM accounts a
                JOIN currencies c ON a.currency_code = c.code
                WHERE a.id = ?
            ");
            $stmt->execute([$account_id]);
            $account = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$account) throw new Exception('Cuenta no encontrada.');

            $account_currency = $account['currency_code'];
            $original_currency = $payment_currency ?: $account_currency;

            if ($payment_currency && $payment_currency !== $account_currency) {
                $rate             = getExchangeRate($pdo, $payment_currency, $account_currency);
                $converted_amount = $amount * $rate;
                $original_amount  = $amount;
            } else {
                $converted_amount = $amount;
                $original_amount  = $amount;
            }

            $amount_dop = convertAmount($converted_amount, $account_currency, 'DOP', $pdo);

            $stmt = $pdo->prepare("
                INSERT INTO transactions
                    (user_id, account_id, category_id, type, amount, original_amount, original_currency, converted_amount_dop, date, description, transfer_to_account)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $user_id, $account_id, $category_id, $type,
                $converted_amount, $original_amount, $original_currency,
                $amount_dop, $date, $description,
                $type === 'transfer' ? $transfer_to : null,
            ]);
            $new_id = (int) $pdo->lastInsertId();

            if ($type === 'income') {
                $pdo->prepare("UPDATE accounts SET balance = balance + ? WHERE id = ?")->execute([$converted_amount, $account_id]);
            } elseif ($type === 'expense') {
                $pdo->prepare("UPDATE accounts SET balance = balance - ? WHERE id = ?")->execute([$converted_amount, $account_id]);
            } elseif ($type === 'transfer' && $transfer_to) {
                $pdo->prepare("UPDATE accounts SET balance = balance - ? WHERE id = ?")->execute([$converted_amount, $account_id]);
                $pdo->prepare("UPDATE accounts SET balance = balance + ? WHERE id = ?")->execute([$converted_amount, $transfer_to]);
            }

            $pdo->commit();

            $symbol = getCurrencySymbol($original_currency);
            echo json_encode([
                'success'    => true,
                'message'    => "Transacción registrada por {$symbol} " . number_format($original_amount, 2),
                'new_id'     => $new_id,
            ]);
            break;

        // ============================================
        // ELIMINAR TRANSACCIÓN
        // ============================================
        case 'delete_transaction':
            $delete_id = (int) ($_POST['transaction_id'] ?? 0);
            if (!$delete_id) throw new Exception('ID de transacción inválido.');

            $stmt = $pdo->prepare("SELECT * FROM transactions WHERE id = ? AND user_id = ?");
            $stmt->execute([$delete_id, $user_id]);
            $trans = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$trans) throw new Exception('Transacción no encontrada o sin permiso para eliminarla.');

            $pdo->beginTransaction();

            if ($trans['type'] === 'income') {
                $pdo->prepare("UPDATE accounts SET balance = balance - ? WHERE id = ?")->execute([$trans['amount'], $trans['account_id']]);
            } elseif ($trans['type'] === 'expense') {
                $pdo->prepare("UPDATE accounts SET balance = balance + ? WHERE id = ?")->execute([$trans['amount'], $trans['account_id']]);
            } elseif ($trans['type'] === 'transfer' && $trans['transfer_to_account']) {
                $pdo->prepare("UPDATE accounts SET balance = balance + ? WHERE id = ?")->execute([$trans['amount'], $trans['account_id']]);
                $pdo->prepare("UPDATE accounts SET balance = balance - ? WHERE id = ?")->execute([$trans['amount'], $trans['transfer_to_account']]);
            }

            $pdo->prepare("DELETE FROM transactions WHERE id = ?")->execute([$delete_id]);
            $pdo->commit();

            echo json_encode([
                'success'        => true,
                'message'        => 'Transacción eliminada correctamente.',
                'transaction_id' => $delete_id,
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