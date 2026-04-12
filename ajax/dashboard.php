<?php
// ajax/dashboard.php  →  cflow/ajax/dashboard.php
// Backend exclusivo para peticiones AJAX del dashboard
// Todas las respuestas son JSON

session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

header('Content-Type: application/json');

// Conexión PDO — sube un nivel desde ajax/ hasta cflow/
require_once '../config/database.php'; // $pdo disponible aquí

$user_id = $_SESSION['user_id'];
$action  = $_POST['action'] ?? $_GET['action'] ?? '';

// ─────────────────────────────────────────────
// Helpers de fecha
// ─────────────────────────────────────────────

/**
 * Devuelve [date_from, date_to] según el período solicitado.
 * Períodos válidos: current_month | prev_month | current_year | prev_year | custom
 * Para custom se esperan $_POST['date_from'] y $_POST['date_to'] en formato Y-m-d
 */
function resolveDateRange(string $period): array
{
    $now = new DateTime();

    switch ($period) {
        case 'prev_month':
            $first = (clone $now)->modify('first day of last month')->format('Y-m-d');
            $last  = (clone $now)->modify('last day of last month')->format('Y-m-d');
            return [$first, $last];

        case 'current_year':
            return [$now->format('Y') . '-01-01', $now->format('Y') . '-12-31'];

        case 'prev_year':
            $y = (int)$now->format('Y') - 1;
            return ["{$y}-01-01", "{$y}-12-31"];

        case 'custom':
            $from = $_POST['date_from'] ?? $now->format('Y-m-01');
            $to   = $_POST['date_to']   ?? $now->format('Y-m-d');
            // Sanitize
            $from = preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) ? $from : $now->format('Y-m-01');
            $to   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)   ? $to   : $now->format('Y-m-d');
            return [$from, $to];

        default: // current_month
            return [$now->format('Y-m-01'), $now->format('Y-m-d')];
    }
}

$period    = $_POST['period'] ?? $_GET['period'] ?? 'current_month';
[$date_from, $date_to] = resolveDateRange($period);

// ─────────────────────────────────────────────
// Switch principal
// ─────────────────────────────────────────────

switch ($action) {

    // ──────────────────────────────────────────
    // KPIs principales
    // ──────────────────────────────────────────
    case 'get_kpis':

        // 1. Balance total general en DOP
        $stmt = $pdo->prepare("
            SELECT SUM(a.balance * c.exchange_rate_to_dop) AS total_dop
            FROM accounts a
            JOIN currencies c ON a.currency_code = c.code
            WHERE a.user_id = ?
        ");
        $stmt->execute([$user_id]);
        $total_dop = (float)($stmt->fetchColumn() ?? 0);

        // Tasa USD
        $stmt_usd = $pdo->prepare("SELECT exchange_rate_to_dop FROM currencies WHERE code = 'USD'");
        $stmt_usd->execute();
        $usd_rate = (float)($stmt_usd->fetchColumn() ?: 1);
        $total_usd = $usd_rate > 0 ? $total_dop / $usd_rate : 0;

        // 2. Balance adeudado (deudas + tarjetas de crédito)
        $stmt = $pdo->prepare("
            SELECT SUM(total_amount - paid_amount) AS total_owed
            FROM debts
            WHERE user_id = ? AND status = 'pending'
        ");
        $stmt->execute([$user_id]);
        $debts_owed = (float)($stmt->fetchColumn() ?? 0);

        $stmt = $pdo->prepare("
            SELECT SUM(a.balance * c.exchange_rate_to_dop) AS credit_balance
            FROM accounts a
            JOIN currencies c ON a.currency_code = c.code
            WHERE a.user_id = ? AND a.type = 'credit_card' AND a.balance < 0
        ");
        $stmt->execute([$user_id]);
        $credit_owed = abs((float)($stmt->fetchColumn() ?? 0));

        $total_owed = $debts_owed + $credit_owed;

        // 3. Ingresos / Gastos / Neto en el período
        $stmt = $pdo->prepare("
            SELECT
                SUM(CASE WHEN type = 'income'  THEN converted_amount_dop ELSE 0 END) AS total_income,
                SUM(CASE WHEN type = 'expense' THEN converted_amount_dop ELSE 0 END) AS total_expense,
                COUNT(*) AS total_transactions
            FROM transactions
            WHERE user_id = ? AND date BETWEEN ? AND ?
        ");
        $stmt->execute([$user_id, $date_from, $date_to]);
        $period_data = $stmt->fetch(PDO::FETCH_ASSOC);

        $total_income       = (float)($period_data['total_income'] ?? 0);
        $total_expense      = (float)($period_data['total_expense'] ?? 0);
        $net_cashflow       = $total_income - $total_expense;
        $total_transactions = (int)($period_data['total_transactions'] ?? 0);

        echo json_encode([
            'success'            => true,
            'total_dop'          => $total_dop,
            'total_usd'          => $total_usd,
            'total_owed'         => $total_owed,
            'total_income'       => $total_income,
            'total_expense'      => $total_expense,
            'net_cashflow'       => $net_cashflow,
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
            SELECT c.name, SUM(t.converted_amount_dop) AS total
            FROM transactions t
            JOIN categories c ON t.category_id = c.id
            WHERE t.user_id = ? AND t.type = 'expense'
              AND t.date BETWEEN ? AND ?
            GROUP BY c.id
            ORDER BY total DESC
            LIMIT 10
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
    // Gráfica 2: Balance general (línea)
    // Balance acumulado día a día en el período
    // ──────────────────────────────────────────
    case 'get_balance_trend':

        // Obtenemos el balance previo al inicio del período
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(
                CASE WHEN type = 'income'  THEN  converted_amount_dop
                     WHEN type = 'expense' THEN -converted_amount_dop
                     ELSE 0 END
            ), 0) AS prior_net
            FROM transactions
            WHERE user_id = ? AND date < ?
        ");
        $stmt->execute([$user_id, $date_from]);
        $prior_net = (float)$stmt->fetchColumn();

        // Balance base en cuentas (snapshot actual - movimientos dentro del período)
        $stmt_base = $pdo->prepare("
            SELECT SUM(a.balance * c.exchange_rate_to_dop) AS total_dop
            FROM accounts a JOIN currencies c ON a.currency_code = c.code
            WHERE a.user_id = ?
        ");
        $stmt_base->execute([$user_id]);
        $current_balance = (float)($stmt_base->fetchColumn() ?? 0);

        // Flujo dentro del período
        $stmt = $pdo->prepare("
            SELECT date,
                   SUM(CASE WHEN type='income'  THEN  converted_amount_dop ELSE 0 END) AS income,
                   SUM(CASE WHEN type='expense' THEN  converted_amount_dop ELSE 0 END) AS expense
            FROM transactions
            WHERE user_id = ? AND date BETWEEN ? AND ?
            GROUP BY date
            ORDER BY date ASC
        ");
        $stmt->execute([$user_id, $date_from, $date_to]);
        $daily = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Flujo neto del período para calcular el balance de inicio
        $stmt_period_net = $pdo->prepare("
            SELECT COALESCE(SUM(
                CASE WHEN type='income'  THEN  converted_amount_dop
                     WHEN type='expense' THEN -converted_amount_dop
                     ELSE 0 END
            ), 0) AS period_net
            FROM transactions
            WHERE user_id = ? AND date BETWEEN ? AND ?
        ");
        $stmt_period_net->execute([$user_id, $date_from, $date_to]);
        $period_net    = (float)$stmt_period_net->fetchColumn();
        $balance_start = $current_balance - $period_net; // balance al inicio del período

        $labels  = [];
        $balances = [];
        $running = $balance_start;

        // Mapa fecha => datos
        $daily_map = [];
        foreach ($daily as $row) {
            $daily_map[$row['date']] = $row;
        }

        // Iterar cada día del rango
        $cur  = new DateTime($date_from);
        $end  = new DateTime($date_to);
        while ($cur <= $end) {
            $d = $cur->format('Y-m-d');
            if (isset($daily_map[$d])) {
                $running += (float)$daily_map[$d]['income'] - (float)$daily_map[$d]['expense'];
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
    // Últimas 10 transacciones en el período
    // ──────────────────────────────────────────
    case 'get_recent_transactions':

        $stmt = $pdo->prepare("
            SELECT t.id, t.date, t.type, t.original_amount, t.converted_amount_dop,
                   t.description,
                   a.name AS account_name, a.currency_code AS account_currency,
                   cat.name AS category_name,
                   curr.symbol, curr.code AS original_currency_code
            FROM transactions t
            LEFT JOIN accounts a   ON t.account_id  = a.id
            LEFT JOIN categories cat ON t.category_id = cat.id
            LEFT JOIN currencies curr ON t.original_currency = curr.code
            WHERE t.user_id = ? AND t.date BETWEEN ? AND ?
            ORDER BY t.date DESC, t.id DESC
            LIMIT 5
        ");
        $stmt->execute([$user_id, $date_from, $date_to]);
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success'      => true,
            'transactions' => $transactions,
        ]);
        break;

    // ──────────────────────────────────────────
    // Flujo de dinero mensual (ingresos vs gastos)
    // meses del año del período seleccionado
    // ──────────────────────────────────────────
    case 'get_cashflow_chart':

        // Año a mostrar: el del date_from
        $year = (int)(new DateTime($date_from))->format('Y');
        $cashflow = [];

        for ($m = 1; $m <= 12; $m++) {
            $stmt = $pdo->prepare("
                SELECT
                    SUM(CASE WHEN type='income'  THEN converted_amount_dop ELSE 0 END) AS income,
                    SUM(CASE WHEN type='expense' THEN converted_amount_dop ELSE 0 END) AS expense
                FROM transactions
                WHERE user_id = ? AND MONTH(date) = ? AND YEAR(date) = ?
            ");
            $stmt->execute([$user_id, $m, $year]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $cashflow[] = [
                'month'   => date('M', mktime(0, 0, 0, $m, 1)),
                'income'  => (float)($row['income']  ?? 0),
                'expense' => (float)($row['expense'] ?? 0),
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
            SELECT COUNT(*) AS total_pending,
                   SUM(total_amount - paid_amount) AS total_amount_pending
            FROM debts
            WHERE user_id = ? AND status = 'pending'
        ");
        $stmt->execute([$user_id]);
        $debts_info = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("
            SELECT id, creditor, due_date, total_amount, paid_amount,
                   (total_amount - paid_amount) AS remaining
            FROM debts
            WHERE user_id = ? AND status = 'pending'
              AND due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
            ORDER BY due_date ASC
            LIMIT 5
        ");
        $stmt->execute([$user_id]);
        $upcoming = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success'         => true,
            'pending_count'   => (int)($debts_info['total_pending']       ?? 0),
            'pending_amount'  => (float)($debts_info['total_amount_pending'] ?? 0),
            'upcoming_debts'  => $upcoming,
        ]);
        break;

    // ──────────────────────────────────────────
    // Recordatorios pendientes
    // ──────────────────────────────────────────
    case 'get_reminders':

        // Reactivar recurrentes vencidos
        _checkAndReactivateRecurringReminders($pdo, $user_id);

        $stmt = $pdo->prepare("
            SELECT id, title, description, reminder_date, is_recurring, recurrence_interval
            FROM reminders
            WHERE user_id = ? AND completed = 0 AND reminder_date >= CURDATE()
            ORDER BY reminder_date ASC
            LIMIT 5
        ");
        $stmt->execute([$user_id]);
        $reminders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success'   => true,
            'reminders' => $reminders,
        ]);
        break;

    // ──────────────────────────────────────────
    // Metas de ahorro
    // ──────────────────────────────────────────
    case 'get_savings_goals':

        $stmt = $pdo->prepare("
            SELECT id, name, target_amount, current_amount, deadline
            FROM savings_goals
            WHERE user_id = ?
            ORDER BY (current_amount / target_amount) ASC
            LIMIT 5
        ");
        $stmt->execute([$user_id]);
        $goals = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'goals'   => $goals,
        ]);
        break;

    // ──────────────────────────────────────────
    // Acción desconocida
    // ──────────────────────────────────────────
    default:
        http_response_code(400);
        echo json_encode(['error' => "Acción desconocida: {$action}"]);
        break;
}

// ─────────────────────────────────────────────
// Funciones auxiliares
// ─────────────────────────────────────────────

function _calculateNextDate(string $date_str, string $interval): string
{
    $date = new DateTime($date_str);
    switch ($interval) {
        case 'daily':   $date->modify('+1 day');   break;
        case 'weekly':  $date->modify('+1 week');  break;
        case 'monthly': $date->modify('+1 month'); break;
        case 'yearly':  $date->modify('+1 year');  break;
        default:        $date->modify('+1 month'); break;
    }
    return $date->format('Y-m-d');
}

function _checkAndReactivateRecurringReminders(PDO $pdo, int $user_id): void
{
    $stmt = $pdo->prepare("
        SELECT * FROM reminders
        WHERE user_id = ? AND is_recurring = 1 AND completed = 1 AND reminder_date < CURDATE()
    ");
    $stmt->execute([$user_id]);
    $expired = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($expired as $reminder) {
        $next_date = _calculateNextDate($reminder['reminder_date'], $reminder['recurrence_interval']);

        $check = $pdo->prepare("
            SELECT COUNT(*) FROM reminders
            WHERE user_id = ? AND title = ? AND reminder_date = ? AND completed = 0 AND is_recurring = 1
        ");
        $check->execute([$user_id, $reminder['title'], $next_date]);
        if ((int)$check->fetchColumn() > 0) continue;

        $upd = $pdo->prepare("UPDATE reminders SET reminder_date = ?, completed = 0 WHERE id = ?");
        $upd->execute([$next_date, $reminder['id']]);
    }
}