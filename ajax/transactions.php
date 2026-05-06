<?php
// ajax/transactions.php - Maneja operaciones CRUD para transacciones, con filtros y paginación.

require_once '../config/database.php';

header('Content-Type: application/json');

redirectIfNotLoggedIn();

$user_id = (int) $_SESSION['user_id'];
$action  = trim($_POST['action'] ?? $_GET['action'] ?? '');

// Tipos de transacción válidos (única fuente de verdad)
const VALID_TYPES = ['income', 'expense', 'transfer'];

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

// ─────────────────────────────────────────────────────────────
// Helper: validar formato de fecha YYYY-MM-DD
// ─────────────────────────────────────────────────────────────
function validateDate(string $date): bool
{
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

// ─────────────────────────────────────────────────────────────
// Helper: obtener cuenta verificando que pertenezca al usuario.
// Usa FOR UPDATE cuando se llama dentro de una transacción.
// ─────────────────────────────────────────────────────────────
function fetchAccountOrFail(PDO $pdo, int $account_id, int $user_id, bool $lock = false): array
{
    $for_update = $lock ? 'FOR UPDATE' : '';
    $stmt = $pdo->prepare("
        SELECT a.*, c.exchange_rate_to_dop
        FROM accounts a
        JOIN currencies c ON a.currency_code = c.code
        WHERE a.id = ? AND a.user_id = ?
        LIMIT 1 {$for_update}
    ");
    $stmt->execute([$account_id, $user_id]);
    $account = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$account) {
        throw new Exception('Cuenta no encontrada o sin permiso para usarla.');
    }

    return $account;
}

try {

    switch ($action) {

        // ============================================================
        // OBTENER TRANSACCIONES CON FILTROS + PAGINACIÓN
        // ============================================================
        case 'get_transactions':
            $filter_account    = !empty($_GET['filter_account'])   ? (int)   $_GET['filter_account']   : null;
            $filter_category   = !empty($_GET['filter_category'])  ? (int)   $_GET['filter_category']  : null;
            $filter_type       = !empty($_GET['filter_type'])      ? trim($_GET['filter_type'])         : null;
            $filter_min_amount = (isset($_GET['filter_min_amount']) && $_GET['filter_min_amount'] !== '')
                                    ? (float) $_GET['filter_min_amount'] : null;
            $filter_max_amount = (isset($_GET['filter_max_amount']) && $_GET['filter_max_amount'] !== '')
                                    ? (float) $_GET['filter_max_amount'] : null;
            $filter_date_from  = !empty($_GET['filter_date_from']) ? trim($_GET['filter_date_from'])    : null;
            $filter_date_to    = !empty($_GET['filter_date_to'])   ? trim($_GET['filter_date_to'])      : null;
            $filter_search     = !empty($_GET['filter_search'])    ? trim($_GET['filter_search'])       : null;

            // Validar fechas de filtro si se proporcionan
            if ($filter_date_from && !validateDate($filter_date_from)) {
                throw new Exception('El formato de fecha de inicio no es válido (YYYY-MM-DD).');
            }
            if ($filter_date_to && !validateDate($filter_date_to)) {
                throw new Exception('El formato de fecha de fin no es válido (YYYY-MM-DD).');
            }
            if ($filter_date_from && $filter_date_to && $filter_date_from > $filter_date_to) {
                throw new Exception('La fecha de inicio no puede ser posterior a la fecha de fin.');
            }

            $allowed_limits = [10, 25, 50, 100];
            $limit  = in_array((int) ($_GET['limit'] ?? 10), $allowed_limits, true) ? (int) $_GET['limit'] : 10;
            $page   = max(1, (int) ($_GET['page'] ?? 1));
            $offset = ($page - 1) * $limit;

            // Construir WHERE dinámico
            $where  = ['t.user_id = ?'];
            $params = [$user_id];

            if ($filter_account !== null) {
                $where[]  = 't.account_id = ?';
                $params[] = $filter_account;
            }
            if ($filter_category !== null) {
                $where[]  = 't.category_id = ?';
                $params[] = $filter_category;
            }
            if ($filter_type !== null && in_array($filter_type, VALID_TYPES, true)) {
                $where[]  = 't.type = ?';
                $params[] = $filter_type;
            }
            if ($filter_min_amount !== null) {
                $where[]  = 't.converted_amount_dop >= ?';
                $params[] = $filter_min_amount;
            }
            if ($filter_max_amount !== null) {
                $where[]  = 't.converted_amount_dop <= ?';
                $params[] = $filter_max_amount;
            }
            if ($filter_date_from) {
                $where[]  = 't.date >= ?';
                $params[] = $filter_date_from;
            }
            if ($filter_date_to) {
                $where[]  = 't.date <= ?';
                $params[] = $filter_date_to;
            }
            if ($filter_search !== null && $filter_search !== '') {
                $where[]  = '(t.description LIKE ? OR a.name LIKE ? OR c.name LIKE ?)';
                $like     = '%' . $filter_search . '%';
                $params[] = $like;
                $params[] = $like;
                $params[] = $like;
            }

            $where_clause = implode(' AND ', $where);

            // Contar total de registros
            $count_stmt = $pdo->prepare("
                SELECT COUNT(*)
                FROM transactions t
                LEFT JOIN accounts a   ON t.account_id  = a.id
                LEFT JOIN categories c ON t.category_id = c.id
                WHERE {$where_clause}
            ");
            $count_stmt->execute($params);
            $total_records = (int) $count_stmt->fetchColumn();
            $total_pages   = $total_records > 0 ? (int) ceil($total_records / $limit) : 1;

            // Listar con paginación
            // ta = cuenta destino de transferencia
            $list_stmt = $pdo->prepare("
                SELECT t.*,
                       a.name         AS account_name,
                       a.type         AS account_type,
                       a.currency_code,
                       c.name         AS category_name,
                       curr.symbol,
                       ta.name        AS transfer_to_account_name
                FROM transactions t
                LEFT JOIN accounts   a    ON t.account_id          = a.id
                LEFT JOIN categories c    ON t.category_id         = c.id
                LEFT JOIN currencies curr ON t.original_currency   = curr.code
                LEFT JOIN accounts   ta   ON t.transfer_to_account = ta.id
                WHERE {$where_clause}
                ORDER BY t.date DESC, t.id DESC
                LIMIT ? OFFSET ?
            ");

            // Bind de parámetros de filtro
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

        // ============================================================
        // OBTENER DATOS PARA MODALES (cuentas, categorías, monedas)
        // ============================================================
        case 'get_form_data':
            $stmt_accounts = $pdo->prepare("
                SELECT a.*, c.symbol, c.exchange_rate_to_dop
                FROM accounts a
                JOIN currencies c ON a.currency_code = c.code
                WHERE a.user_id = ?
                  AND a.type NOT IN ('debit_card', 'credit_card')
                ORDER BY a.name ASC
            ");
            $stmt_accounts->execute([$user_id]);
            $accounts = $stmt_accounts->fetchAll(PDO::FETCH_ASSOC);

            $stmt_cats = $pdo->prepare("
                SELECT *, type AS category_type
                FROM categories
                WHERE user_id = ?
                  AND type IN ('income', 'expense')
                ORDER BY type ASC, name ASC
            ");
            $stmt_cats->execute([$user_id]);
            $all_cats = $stmt_cats->fetchAll(PDO::FETCH_ASSOC);

            // Separar en PHP (evita dos round-trips a la BD)
            $income_categories  = array_values(array_filter($all_cats, fn($c) => $c['type'] === 'income'));
            $expense_categories = array_values(array_filter($all_cats, fn($c) => $c['type'] === 'expense'));

            $all_currencies = $pdo->query("SELECT * FROM currencies ORDER BY code ASC")
                                  ->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success'            => true,
                'accounts'           => $accounts,
                'income_categories'  => $income_categories,
                'expense_categories' => $expense_categories,
                'currencies'         => $all_currencies,
            ]);
            break;

        // ============================================================
        // AGREGAR TRANSACCIÓN
        // ============================================================
        case 'add_transaction':
            $account_id       = (int)   ($_POST['account_id']        ?? 0);
            $category_id      = !empty($_POST['category_id'])  ? (int)  $_POST['category_id']  : null;
            $type             = trim(   $_POST['type']                ?? '');
            $amount           = (float) ($_POST['amount']            ?? 0);
            $date             = trim(   $_POST['date']               ?? '');
            $description      = trim(   $_POST['description']        ?? '');
            $transfer_to      = !empty($_POST['transfer_to'])  ? (int)  $_POST['transfer_to']  : null;
            $payment_currency = !empty($_POST['payment_currency']) ? trim($_POST['payment_currency']) : null;

            // Validaciones previas a la transacción
            if ($account_id <= 0)                         throw new Exception('Cuenta de origen no válida.');
            if ($amount <= 0)                             throw new Exception('El monto debe ser mayor a 0.');
            if (!in_array($type, VALID_TYPES, true))      throw new Exception('Tipo de transacción inválido.');
            if ($date === '' || !validateDate($date))     throw new Exception('La fecha no es válida (YYYY-MM-DD).');
            if ($type === 'transfer' && !$transfer_to)    throw new Exception('Debes indicar la cuenta destino para la transferencia.');
            if ($type === 'transfer' && $transfer_to === $account_id) {
                throw new Exception('La cuenta origen y destino no pueden ser la misma.');
            }
            if (in_array($type, ['expense', 'income'], true) && !$category_id) {
                throw new Exception('La categoría es obligatoria para ingresos y gastos.');
            }

            $pdo->beginTransaction();

            // Obtener cuenta origen con bloqueo
            $account          = fetchAccountOrFail($pdo, $account_id, $user_id, lock: true);
            $account_currency = $account['currency_code'];
            $original_currency = $payment_currency ?: $account_currency;

            // Calcular montos según moneda de pago
            if ($payment_currency && $payment_currency !== $account_currency) {
                $rate             = getExchangeRate($pdo, $payment_currency, $account_currency);
                $converted_amount = $amount * $rate;
                $original_amount  = $amount;
            } else {
                $converted_amount = $amount;
                $original_amount  = $amount;
            }

            // Verificar saldo suficiente para gastos y transferencias
            if (in_array($type, ['expense', 'transfer'], true)) {
                if ((float) $account['balance'] < $converted_amount) {
                    throw new Exception('Saldo insuficiente en la cuenta de origen.');
                }
            }

            $amount_dop = convertAmount($converted_amount, $account_currency, 'DOP', $pdo);

            // Insertar transacción
            $pdo->prepare("
                INSERT INTO transactions
                    (user_id, account_id, category_id, type, amount,
                     original_amount, original_currency, converted_amount_dop,
                     date, description, transfer_to_account)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ")->execute([
                $user_id, $account_id, $category_id, $type,
                $converted_amount, $original_amount, $original_currency,
                $amount_dop, $date, $description,
                $type === 'transfer' ? $transfer_to : null,
            ]);
            $new_id = (int) $pdo->lastInsertId();

            // Actualizar saldo(s) de cuenta(s)
            if ($type === 'income') {
                $pdo->prepare("UPDATE accounts SET balance = balance + ? WHERE id = ? AND user_id = ?")
                    ->execute([$converted_amount, $account_id, $user_id]);

            } elseif ($type === 'expense') {
                $pdo->prepare("UPDATE accounts SET balance = balance - ? WHERE id = ? AND user_id = ?")
                    ->execute([$converted_amount, $account_id, $user_id]);

            } elseif ($type === 'transfer') {
                // Bloquear cuenta destino antes de modificarla
                $dest = fetchAccountOrFail($pdo, $transfer_to, $user_id, lock: true);

                // Convertir al currency de la cuenta destino si difiere
                $amount_for_dest = $account_currency !== $dest['currency_code']
                    ? $converted_amount * getExchangeRate($pdo, $account_currency, $dest['currency_code'])
                    : $converted_amount;

                $pdo->prepare("UPDATE accounts SET balance = balance - ? WHERE id = ? AND user_id = ?")
                    ->execute([$converted_amount, $account_id, $user_id]);

                $pdo->prepare("UPDATE accounts SET balance = balance + ? WHERE id = ? AND user_id = ?")
                    ->execute([$amount_for_dest, $transfer_to, $user_id]);
            }

            $pdo->commit();

            $symbol = getCurrencySymbol($original_currency);
            echo json_encode([
                'success' => true,
                'message' => "Transacción registrada por {$symbol} " . number_format($original_amount, 2),
                'new_id'  => $new_id,
            ]);
            break;

        // ============================================================
        // ELIMINAR TRANSACCIÓN
        // ============================================================
        case 'delete_transaction':
            $delete_id = (int) ($_POST['transaction_id'] ?? 0);

            if ($delete_id <= 0) throw new Exception('ID de transacción inválido.');

            // ── Pre-verificación sin bloqueo ──────────────────────
            $pre_stmt = $pdo->prepare("
                SELECT t.date, a.type AS account_type
                FROM transactions t
                LEFT JOIN accounts a ON t.account_id = a.id
                WHERE t.id = ? AND t.user_id = ?
                LIMIT 1
            ");
            $pre_stmt->execute([$delete_id, $user_id]);
            $precheck = $pre_stmt->fetch(PDO::FETCH_ASSOC);

            if (!$precheck) {
                throw new Exception('Transacción no encontrada o sin permiso para eliminarla.');
            }

            // Bloquear eliminación de transacciones de tarjetas
            if (in_array($precheck['account_type'], ['debit_card', 'credit_card'], true)) {
                echo json_encode([
                    'success' => false,
                    'is_card' => true,
                    'message' => 'Para eliminar transacciones de tarjetas debe hacerlo desde el módulo de tarjetas.',
                ]);
                exit;
            }

            // Bloquear eliminación de transacciones con más de 3 días
            $txDate   = new DateTime($precheck['date']);
            $today    = new DateTime('today');
            $daysDiff = (int) $today->diff($txDate)->days;

            if ($daysDiff > 3) {
                echo json_encode([
                    'success' => false,
                    'is_old'  => true,
                    'message' => 'No se pueden eliminar transacciones con más de 3 días de antigüedad.',
                ]);
                exit;
            }

            // ── Eliminación real en transacción atómica ───────────
            $pdo->beginTransaction();

            // Leer y bloquear la transacción
            $stmt = $pdo->prepare("
                SELECT * FROM transactions
                WHERE id = ? AND user_id = ?
                LIMIT 1
                FOR UPDATE
            ");
            $stmt->execute([$delete_id, $user_id]);
            $trans = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$trans) throw new Exception('Transacción no encontrada o sin permiso para eliminarla.');

            // Revertir el efecto en saldo(s) de cuenta(s)
            if ($trans['type'] === 'income') {
                $pdo->prepare("UPDATE accounts SET balance = balance - ? WHERE id = ? AND user_id = ?")
                    ->execute([$trans['amount'], $trans['account_id'], $user_id]);

            } elseif ($trans['type'] === 'expense') {
                $pdo->prepare("UPDATE accounts SET balance = balance + ? WHERE id = ? AND user_id = ?")
                    ->execute([$trans['amount'], $trans['account_id'], $user_id]);

            } elseif ($trans['type'] === 'transfer' && $trans['transfer_to_account']) {
                // Revertir en ambas cuentas — deben pertenecer al usuario
                $pdo->prepare("UPDATE accounts SET balance = balance + ? WHERE id = ? AND user_id = ?")
                    ->execute([$trans['amount'], $trans['account_id'], $user_id]);

                $pdo->prepare("UPDATE accounts SET balance = balance - ? WHERE id = ? AND user_id = ?")
                    ->execute([$trans['amount'], $trans['transfer_to_account'], $user_id]);
            }

            $pdo->prepare("DELETE FROM transactions WHERE id = ? AND user_id = ?")
                ->execute([$delete_id, $user_id]);

            $pdo->commit();

            echo json_encode([
                'success'        => true,
                'message'        => 'Transacción eliminada correctamente.',
                'transaction_id' => $delete_id,
            ]);
            break;
            
        // ============================================================
        // EDITAR TRANSACCIÓN
        // ============================================================
        case 'edit_transaction':
            $transaction_id   = (int)   ($_POST['transaction_id']  ?? 0);
            $account_id       = (int)   ($_POST['account_id']      ?? 0);
            $category_id      = !empty($_POST['category_id'])      ? (int)   $_POST['category_id']      : null;
            $type             = trim(   $_POST['type']             ?? '');
            $amount           = (float) ($_POST['amount']          ?? 0);
            $date             = trim(   $_POST['date']             ?? '');
            $description      = trim(   $_POST['description']      ?? '');
            $transfer_to      = !empty($_POST['transfer_to'])      ? (int)   $_POST['transfer_to']      : null;
            $payment_currency = !empty($_POST['payment_currency']) ? trim(   $_POST['payment_currency']) : null;

            // ── Validaciones básicas ──────────────────────────────
            if ($transaction_id <= 0)                                     throw new Exception('ID de transacción inválido.');
            if ($account_id <= 0)                                         throw new Exception('Cuenta de origen no válida.');
            if ($amount <= 0)                                             throw new Exception('El monto debe ser mayor a 0.');
            if (!in_array($type, VALID_TYPES, true))                      throw new Exception('Tipo de transacción inválido.');
            if ($date === '' || !validateDate($date))                     throw new Exception('La fecha no es válida (YYYY-MM-DD).');
            if ($type === 'transfer' && !$transfer_to)                    throw new Exception('Debes indicar la cuenta destino para la transferencia.');
            if ($type === 'transfer' && $transfer_to === $account_id)     throw new Exception('La cuenta origen y destino no pueden ser la misma.');
            if (in_array($type, ['expense', 'income'], true) && !$category_id) {
                throw new Exception('La categoría es obligatoria para ingresos y gastos.');
            }

            $pdo->beginTransaction();

            // ── Leer y bloquear la transacción original ───────────
            $origStmt = $pdo->prepare("
                SELECT t.*, a.type AS account_type
                FROM transactions t
                LEFT JOIN accounts a ON t.account_id = a.id
                WHERE t.id = ? AND t.user_id = ?
                LIMIT 1 FOR UPDATE
            ");
            $origStmt->execute([$transaction_id, $user_id]);
            $orig = $origStmt->fetch(PDO::FETCH_ASSOC);

            if (!$orig) throw new Exception('Transacción no encontrada o sin permiso para editarla.');

            // ── Bloquear edición de tarjetas ──────────────────────
            if (in_array($orig['account_type'], ['debit_card', 'credit_card'], true)) {
                throw new Exception('Las transacciones de tarjetas deben editarse desde el módulo de tarjetas.');
            }

            // ── Límite de 24 horas ────────────────────────────────
            // Requiere columna: created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            // Si no existe, se usa date (precisión de día).
            $createdAt = $orig['created_at'] ?? ($orig['date'] . ' 00:00:00');
            $txTime    = new DateTime($createdAt);
            $now       = new DateTime();
            if (($now->getTimestamp() - $txTime->getTimestamp()) > 86400) {
                throw new Exception('No se pueden editar transacciones con más de 24 horas de antigüedad.');
            }

            // ── Paso 1: bloquear cuentas involucradas ─────────────
            // Ordenar IDs para evitar deadlocks
            $lockIds = array_unique(array_filter([
                $orig['account_id'],
                $orig['transfer_to_account'] ?: null,
                $account_id,
                $transfer_to,
            ]));
            sort($lockIds);
            $placeholders = implode(',', array_fill(0, count($lockIds), '?'));
            $pdo->prepare("
                SELECT id FROM accounts
                WHERE id IN ({$placeholders}) AND user_id = ?
                FOR UPDATE
            ")->execute([...$lockIds, $user_id]);

            // ── Paso 2: revertir efectos originales en saldos ─────
            if ($orig['type'] === 'income') {

                $pdo->prepare("UPDATE accounts SET balance = balance - ? WHERE id = ? AND user_id = ?")
                    ->execute([$orig['amount'], $orig['account_id'], $user_id]);

            } elseif ($orig['type'] === 'expense') {

                $pdo->prepare("UPDATE accounts SET balance = balance + ? WHERE id = ? AND user_id = ?")
                    ->execute([$orig['amount'], $orig['account_id'], $user_id]);

            } elseif ($orig['type'] === 'transfer' && $orig['transfer_to_account']) {

                // Revertir origen
                $pdo->prepare("UPDATE accounts SET balance = balance + ? WHERE id = ? AND user_id = ?")
                    ->execute([$orig['amount'], $orig['account_id'], $user_id]);

                // Revertir destino (recalcular con tasas actuales para multidivisa)
                $origAccInfo  = fetchAccountOrFail($pdo, $orig['account_id'],          $user_id);
                $origDestInfo = fetchAccountOrFail($pdo, $orig['transfer_to_account'], $user_id);

                $orig_dest_amount = ($origAccInfo['currency_code'] !== $origDestInfo['currency_code'])
                    ? $orig['amount'] * getExchangeRate($pdo, $origAccInfo['currency_code'], $origDestInfo['currency_code'])
                    : $orig['amount'];

                $pdo->prepare("UPDATE accounts SET balance = balance - ? WHERE id = ? AND user_id = ?")
                    ->execute([$orig_dest_amount, $orig['transfer_to_account'], $user_id]);
            }

            // ── Paso 3: calcular montos de la nueva transacción ───
            // Re-leer saldo post-reversión de la cuenta origen
            $balStmt = $pdo->prepare("SELECT * FROM accounts a JOIN currencies c ON a.currency_code = c.code WHERE a.id = ? AND a.user_id = ? LIMIT 1");
            $balStmt->execute([$account_id, $user_id]);
            $account = $balStmt->fetch(PDO::FETCH_ASSOC);
            if (!$account) throw new Exception('Cuenta de origen no encontrada o sin permiso.');

            $account_currency  = $account['currency_code'];
            $original_currency = $payment_currency ?: $account_currency;

            if ($payment_currency && $payment_currency !== $account_currency) {
                $rate             = getExchangeRate($pdo, $payment_currency, $account_currency);
                $converted_amount = $amount * $rate;
                $original_amount  = $amount;
            } else {
                $converted_amount = $amount;
                $original_amount  = $amount;
            }

            // ── Verificar saldo suficiente (con balance ya revertido) ──
            if (in_array($type, ['expense', 'transfer'], true)) {
                if ((float) $account['balance'] < $converted_amount) {
                    throw new Exception('Saldo insuficiente en la cuenta de origen.');
                }
            }

            $amount_dop = convertAmount($converted_amount, $account_currency, 'DOP', $pdo);

            // ── Paso 4: actualizar registro de transacción ────────
            $pdo->prepare("
                UPDATE transactions SET
                    account_id           = ?,
                    category_id          = ?,
                    type                 = ?,
                    amount               = ?,
                    original_amount      = ?,
                    original_currency    = ?,
                    converted_amount_dop = ?,
                    date                 = ?,
                    description          = ?,
                    transfer_to_account  = ?
                WHERE id = ? AND user_id = ?
            ")->execute([
                $account_id, $category_id, $type,
                $converted_amount, $original_amount, $original_currency,
                $amount_dop, $date, $description,
                $type === 'transfer' ? $transfer_to : null,
                $transaction_id, $user_id,
            ]);

            // ── Paso 5: aplicar nuevos efectos en saldos ──────────
            if ($type === 'income') {

                $pdo->prepare("UPDATE accounts SET balance = balance + ? WHERE id = ? AND user_id = ?")
                    ->execute([$converted_amount, $account_id, $user_id]);

            } elseif ($type === 'expense') {

                $pdo->prepare("UPDATE accounts SET balance = balance - ? WHERE id = ? AND user_id = ?")
                    ->execute([$converted_amount, $account_id, $user_id]);

            } elseif ($type === 'transfer') {

                $destInfo        = fetchAccountOrFail($pdo, $transfer_to, $user_id);
                $amount_for_dest = ($account_currency !== $destInfo['currency_code'])
                    ? $converted_amount * getExchangeRate($pdo, $account_currency, $destInfo['currency_code'])
                    : $converted_amount;

                $pdo->prepare("UPDATE accounts SET balance = balance - ? WHERE id = ? AND user_id = ?")
                    ->execute([$converted_amount, $account_id, $user_id]);

                $pdo->prepare("UPDATE accounts SET balance = balance + ? WHERE id = ? AND user_id = ?")
                    ->execute([$amount_for_dest, $transfer_to, $user_id]);
            }

            $pdo->commit();

            $symbol = getCurrencySymbol($original_currency);
            echo json_encode([
                'success'        => true,
                'message'        => "Transacción actualizada: {$symbol} " . number_format($original_amount, 2),
                'transaction_id' => $transaction_id,
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