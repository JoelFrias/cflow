<?php
// ajax/dashboard.php
// Backend exclusivo para peticiones AJAX del dashboard.
// Todas las respuestas son JSON.

require_once '../config/database.php';

// ── Zona horaria ──────────────────────────────────────────────────────────────
date_default_timezone_set('America/Santo_Domingo');

header('Content-Type: application/json');

redirectIfNotLoggedIn();

$user_id = (int) ($_SESSION['user_id'] ?? 0);
$action  = $_POST['action'] ?? $_GET['action'] ?? '';

// ── Helpers de fecha ──────────────────────────────────────────────────────────

/**
 * Devuelve [date_from, date_to] según el período solicitado.
 */
function resolveDateRange(string $period): array
{
    $now = new DateTime('now');

    switch ($period) {
        case 'prev_month':
            return [
                (clone $now)->modify('first day of last month')->format('Y-m-d'),
                (clone $now)->modify('last day of last month')->format('Y-m-d'),
            ];

        case 'current_year':
            return [$now->format('Y') . '-01-01', $now->format('Y') . '-12-31'];

        case 'prev_year':
            $y = (int) $now->format('Y') - 1;
            return ["{$y}-01-01", "{$y}-12-31"];

        case 'custom':
            $from = $_POST['date_from'] ?? $now->format('Y-m-01');
            $to   = $_POST['date_to']   ?? $now->format('Y-m-d');
            $from = preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) ? $from : $now->format('Y-m-01');
            $to   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)   ? $to   : $now->format('Y-m-d');
            if ($from > $to) [$from, $to] = [$to, $from];
            return [$from, $to];

        default: // current_month
            return [$now->format('Y-m-01'), $now->format('Y-m-d')];
    }
}

$period = $_POST['period'] ?? $_GET['period'] ?? 'current_month';
[$date_from, $date_to] = resolveDateRange($period);

// ── Respuesta de error centralizada ──────────────────────────────────────────

function jsonError(string $message, int $http_code = 400): void
{
    http_response_code($http_code);
    echo json_encode(['success' => false, 'message' => $message]);
    exit();
}

// ── Switch principal ──────────────────────────────────────────────────────────

try {

    switch ($action) {

        // ──────────────────────────────────────────
        // KPIs principales
        // ──────────────────────────────────────────
        case 'get_kpis':

            // 1. Balance total (sin tarjetas de crédito)
            $stmt = $pdo->prepare("
                SELECT COALESCE(SUM(a.balance * c.exchange_rate_to_dop), 0) AS total_dop
                FROM   accounts a
                JOIN   currencies c ON a.currency_code = c.code
                WHERE  a.user_id = ?
                  AND  a.type != 'credit_card'
                  AND  a.is_active = 1
            ");
            $stmt->execute([$user_id]);
            $total_dop = (float) ($stmt->fetchColumn() ?? 0);

            // Tasa USD
            $stmt_usd = $pdo->query("SELECT exchange_rate_to_dop FROM currencies WHERE code = 'USD' LIMIT 1");
            $usd_rate  = (float) ($stmt_usd->fetchColumn() ?: 1);
            $total_usd = $usd_rate > 0 ? $total_dop / $usd_rate : 0;

            // 2. Balance adeudado
            $stmt = $pdo->prepare("
                SELECT
                    (SELECT COALESCE(SUM(total_amount - paid_amount), 0)
                     FROM   debts
                     WHERE  user_id = ? AND status = 'pending') AS debts_owed,

                    (SELECT COALESCE(SUM(a.balance_dop), 0)
                     FROM   accounts a
                     WHERE  a.user_id = ? AND a.type = 'credit_card' AND a.is_active = 1) AS credit_owed_dop,

                    (SELECT COALESCE(SUM(a.balance_usd), 0)
                     FROM   accounts a
                     WHERE  a.user_id = ? AND a.type = 'credit_card' AND a.is_active = 1) AS credit_owed_usd
            ");
            $stmt->execute([$user_id, $user_id, $user_id]);
            $owed_row = $stmt->fetch(PDO::FETCH_ASSOC);

            $debts_owed      = (float) $owed_row['debts_owed'];
            $credit_owed_dop = (float) $owed_row['credit_owed_dop'];
            $credit_owed_usd = (float) $owed_row['credit_owed_usd'];

            $total_owed     = $debts_owed + $credit_owed_dop;
            $total_owed_usd = $credit_owed_usd + ($usd_rate > 0 ? $debts_owed / $usd_rate : 0);

            // 3. Ingresos / Gastos del período
            //    EXCLUYE: transferencias Y transacciones marcadas como ajuste
            $stmt = $pdo->prepare("
                SELECT
                    COALESCE(SUM(CASE WHEN type = 'income'  THEN converted_amount_dop ELSE 0 END), 0) AS total_income,
                    COALESCE(SUM(CASE WHEN type = 'expense' THEN converted_amount_dop ELSE 0 END), 0) AS total_expense,
                    COUNT(*) AS total_transactions
                FROM transactions
                WHERE user_id = ? AND date BETWEEN ? AND ?
                  AND type IN ('income', 'expense')
                  AND excluded_from_reports = 0
            ");
            $stmt->execute([$user_id, $date_from, $date_to]);
            $period_data = $stmt->fetch(PDO::FETCH_ASSOC);

            $total_income       = (float) $period_data['total_income'];
            $total_expense      = (float) $period_data['total_expense'];
            $net_cashflow       = $total_income - $total_expense;
            $total_transactions = (int)   $period_data['total_transactions'];

            echo json_encode([
                'success'            => true,
                'total_dop'          => round($total_dop, 2),
                'total_usd'          => round($total_usd, 2),
                'total_owed'         => round($total_owed, 2),
                'total_owed_usd'     => round($total_owed_usd, 2),
                'total_income'       => round($total_income, 2),
                'total_expense'      => round($total_expense, 2),
                'net_cashflow'       => round($net_cashflow, 2),
                'total_transactions' => $total_transactions,
                'date_from'          => $date_from,
                'date_to'            => $date_to,
            ]);
            break;

        // ──────────────────────────────────────────
        // Gráfica 1: Gastos por categoría (barras)
        // ──────────────────────────────────────────
        case 'get_expenses_by_category':

            $stmt = $pdo->prepare("
                SELECT   c.name,
                         COALESCE(SUM(t.converted_amount_dop), 0) AS total
                FROM     transactions t
                JOIN     categories c ON t.category_id = c.id
                WHERE    t.user_id = ? AND t.type = 'expense'
                  AND    t.date BETWEEN ? AND ?
                  AND    t.excluded_from_reports = 0
                GROUP BY c.id, c.name
                ORDER BY total DESC
                LIMIT    10
            ");
            $stmt->execute([$user_id, $date_from, $date_to]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'labels'  => array_column($rows, 'name'),
                'values'  => array_map('floatval', array_column($rows, 'total')),
            ]);
            break;

        // ──────────────────────────────────────────
        // Gráfica 2: Tendencia de balance (línea)
        // ──────────────────────────────────────────
        case 'get_balance_trend':

            $stmt = $pdo->prepare("
                SELECT COALESCE(SUM(a.balance * c.exchange_rate_to_dop), 0) AS total_dop
                FROM   accounts a
                JOIN   currencies c ON a.currency_code = c.code
                WHERE  a.user_id = ? AND a.type != 'credit_card' AND a.is_active = 1
            ");
            $stmt->execute([$user_id]);
            $current_balance = (float) $stmt->fetchColumn();

            $stmt = $pdo->prepare("
                SELECT COALESCE(SUM(
                    CASE WHEN type = 'income'  THEN  converted_amount_dop
                         WHEN type = 'expense' THEN -converted_amount_dop
                         ELSE 0 END
                ), 0) AS period_net
                FROM transactions
                WHERE user_id = ? AND date BETWEEN ? AND ?
                  AND type IN ('income', 'expense')
                  AND excluded_from_reports = 0
            ");
            $stmt->execute([$user_id, $date_from, $date_to]);
            $period_net    = (float) $stmt->fetchColumn();
            $balance_start = $current_balance - $period_net;

            $stmt = $pdo->prepare("
                SELECT date,
                       COALESCE(SUM(CASE WHEN type = 'income'  THEN converted_amount_dop ELSE 0 END), 0) AS income,
                       COALESCE(SUM(CASE WHEN type = 'expense' THEN converted_amount_dop ELSE 0 END), 0) AS expense
                FROM   transactions
                WHERE  user_id = ? AND date BETWEEN ? AND ?
                  AND  type IN ('income', 'expense')
                  AND  excluded_from_reports = 0
                GROUP  BY date
                ORDER  BY date ASC
            ");
            $stmt->execute([$user_id, $date_from, $date_to]);
            $daily_map = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), null, 'date');

            $labels   = [];
            $balances = [];
            $running  = $balance_start;

            $cur = new DateTime($date_from);
            $end = new DateTime($date_to);

            while ($cur <= $end) {
                $d = $cur->format('Y-m-d');
                if (isset($daily_map[$d])) {
                    $running += (float) $daily_map[$d]['income'] - (float) $daily_map[$d]['expense'];
                }
                $labels[]   = $cur->format('d/m');
                $balances[] = round($running, 2);
                $cur->modify('+1 day');
            }

            echo json_encode([
                'success'  => true,
                'labels'   => $labels,
                'balances' => $balances,
            ]);
            break;

        // ──────────────────────────────────────────
        // Últimas 5 transacciones (sin transferencias ni ajustes)
        // ──────────────────────────────────────────
        case 'get_recent_transactions':

            $stmt = $pdo->prepare("
                SELECT   t.id, t.date, t.type,
                         t.original_amount, t.converted_amount_dop,
                         t.description,
                         a.name          AS account_name,
                         a.currency_code AS account_currency,
                         cat.name        AS category_name,
                         curr.symbol,
                         curr.code       AS original_currency_code
                FROM     transactions t
                LEFT JOIN accounts   a    ON t.account_id       = a.id
                LEFT JOIN categories cat  ON t.category_id      = cat.id
                LEFT JOIN currencies curr ON t.original_currency = curr.code
                WHERE    t.user_id = ? AND t.date BETWEEN ? AND ?
                  AND    t.type IN ('income', 'expense')
                  AND    t.excluded_from_reports = 0
                ORDER BY t.date DESC, t.id DESC
                LIMIT    5
            ");
            $stmt->execute([$user_id, $date_from, $date_to]);

            echo json_encode([
                'success'      => true,
                'transactions' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            ]);
            break;

        // ──────────────────────────────────────────
        // Flujo de caja mensual
        // ──────────────────────────────────────────
        case 'get_cashflow_chart':

            $year = (int) (new DateTime($date_from))->format('Y');

            $stmt = $pdo->prepare("
                SELECT   MONTH(date) AS month_num,
                         COALESCE(SUM(CASE WHEN type = 'income'  THEN converted_amount_dop ELSE 0 END), 0) AS income,
                         COALESCE(SUM(CASE WHEN type = 'expense' THEN converted_amount_dop ELSE 0 END), 0) AS expense
                FROM     transactions
                WHERE    user_id = ? AND YEAR(date) = ?
                  AND    type IN ('income', 'expense')
                  AND    excluded_from_reports = 0
                GROUP BY MONTH(date)
                ORDER BY month_num ASC
            ");
            $stmt->execute([$user_id, $year]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $by_month = [];
            foreach ($rows as $row) {
                $by_month[(int) $row['month_num']] = $row;
            }

            $cashflow = [];
            for ($m = 1; $m <= 12; $m++) {
                $cashflow[] = [
                    'month'   => date('M', mktime(0, 0, 0, $m, 1)),
                    'income'  => (float) ($by_month[$m]['income']  ?? 0),
                    'expense' => (float) ($by_month[$m]['expense'] ?? 0),
                ];
            }

            echo json_encode([
                'success'  => true,
                'year'     => $year,
                'cashflow' => $cashflow,
            ]);
            break;

        // ──────────────────────────────────────────
        // Deudas pendientes
        // ──────────────────────────────────────────
        case 'get_debts':

            $stmt = $pdo->prepare("
                SELECT COUNT(*)                        AS total_pending,
                       COALESCE(SUM(total_amount - paid_amount), 0) AS total_amount_pending
                FROM   debts
                WHERE  user_id = ? AND status = 'pending'
            ");
            $stmt->execute([$user_id]);
            $debts_info = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt = $pdo->prepare("
                SELECT id, creditor, due_date, total_amount, paid_amount,
                       (total_amount - paid_amount) AS remaining
                FROM   debts
                WHERE  user_id = ? AND status = 'pending'
                  AND  due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                ORDER  BY due_date ASC
                LIMIT  5
            ");
            $stmt->execute([$user_id]);

            echo json_encode([
                'success'        => true,
                'pending_count'  => (int)   $debts_info['total_pending'],
                'pending_amount' => (float) $debts_info['total_amount_pending'],
                'upcoming_debts' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            ]);
            break;

        // ──────────────────────────────────────────
        // Recordatorios pendientes
        // ──────────────────────────────────────────
        case 'get_reminders':

            _reactivateRecurringReminders($pdo, $user_id);

            $stmt = $pdo->prepare("
                SELECT id, title, description, reminder_date,
                       is_recurring, recurrence_interval
                FROM   reminders
                WHERE  user_id = ? AND completed = 0 AND reminder_date >= CURDATE()
                ORDER  BY reminder_date ASC
                LIMIT  5
            ");
            $stmt->execute([$user_id]);

            echo json_encode([
                'success'   => true,
                'reminders' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            ]);
            break;

        // ──────────────────────────────────────────
        // Metas de ahorro
        // ──────────────────────────────────────────
        case 'get_savings_goals':

            $stmt = $pdo->prepare("
                SELECT   id, name, target_amount, current_amount, deadline
                FROM     savings_goals
                WHERE    user_id = ?
                ORDER BY (current_amount / NULLIF(target_amount, 0)) ASC
                LIMIT    5
            ");
            $stmt->execute([$user_id]);

            echo json_encode([
                'success' => true,
                'goals'   => $stmt->fetchAll(PDO::FETCH_ASSOC),
            ]);
            break;

        default:
            jsonError('Acción no reconocida.');
    }

} catch (PDOException $e) {
    error_log('PDOException [dashboard.php / ' . $action . ']: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());
    jsonError('Error interno en la base de datos. Inténtalo de nuevo.', 500);

} catch (Exception $e) {
    error_log('Exception [dashboard.php / ' . $action . ']: ' . $e->getMessage());
    jsonError($e->getMessage(), 500);
}

// ── Funciones auxiliares ──────────────────────────────────────────────────────

function _calculateNextDate(string $date_str, string $interval): string
{
    $date = new DateTime($date_str);
    $map  = [
        'daily'   => '+1 day',
        'weekly'  => '+1 week',
        'monthly' => '+1 month',
        'yearly'  => '+1 year',
    ];
    $date->modify($map[$interval] ?? '+1 month');
    return $date->format('Y-m-d');
}

function _reactivateRecurringReminders(PDO $pdo, int $user_id): void
{
    $stmt = $pdo->prepare("
        SELECT id, title, reminder_date, recurrence_interval
        FROM   reminders
        WHERE  user_id = ? AND is_recurring = 1 AND completed = 1 AND reminder_date < CURDATE()
    ");
    $stmt->execute([$user_id]);
    $expired = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($expired)) return;

    $next_dates = [];
    foreach ($expired as $r) {
        $next_dates[$r['id']] = _calculateNextDate($r['reminder_date'], $r['recurrence_interval']);
    }

    $ids          = array_keys($next_dates);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $chk = $pdo->prepare("
        SELECT r.id
        FROM   reminders r
        WHERE  r.user_id = ? AND r.is_recurring = 1 AND r.completed = 0
          AND  r.id IN ({$placeholders})
    ");
    $chk->execute(array_merge([$user_id], $ids));
    $already_active = array_flip($chk->fetchAll(PDO::FETCH_COLUMN));

    $upd = $pdo->prepare("UPDATE reminders SET reminder_date = ?, completed = 0 WHERE id = ? AND user_id = ?");

    $pdo->beginTransaction();
    try {
        foreach ($expired as $r) {
            if (isset($already_active[$r['id']])) continue;
            $upd->execute([$next_dates[$r['id']], $r['id'], $user_id]);
        }
        $pdo->commit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log('PDOException [_reactivateRecurringReminders]: ' . $e->getMessage());
    }
}