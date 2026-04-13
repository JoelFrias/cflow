<?php
// ajax/get_goal_history.php — Historial de abonos de una meta de ahorro.
// Mejoras: validación estricta de inputs, columnas explícitas, paginación,
// filtros de fecha, totales agregados y manejo de errores robusto.

require_once '../config/database.php';

header('Content-Type: application/json');

// ── Autenticación ────────────────────────────────────────────────────────────
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$user_id = (int) $_SESSION['user_id'];

// ── Validación de parámetros ─────────────────────────────────────────────────
$goal_id   = (int)   ($_GET['goal_id']   ?? 0);
$date_from = trim($_GET['date_from'] ?? '');
$date_to   = trim($_GET['date_to']   ?? '');
$page      = max(1, (int) ($_GET['page']     ?? 1));
$per_page  = min(100, max(1, (int) ($_GET['per_page'] ?? 20)));  // 1–100, default 20

if (!$goal_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de meta no válido.']);
    exit;
}

// Validar fechas si se envían
foreach (['date_from' => $date_from, 'date_to' => $date_to] as $field => $value) {
    if ($value !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Formato de $field incorrecto (YYYY-MM-DD)."]);
        exit;
    }
}

if ($date_from !== '' && $date_to !== '' && $date_from > $date_to) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'date_from no puede ser posterior a date_to.']);
    exit;
}

try {
    // ── Verificar propiedad de la meta ───────────────────────────────────────
    $stmt = $pdo->prepare("
        SELECT id, name, target_amount, current_amount, currency_code
        FROM   savings_goals
        WHERE  id = ? AND user_id = ?
    ");
    $stmt->execute([$goal_id, $user_id]);
    $goal = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$goal) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Meta no encontrada.']);
        exit;
    }

    // ── Construir filtros dinámicos ──────────────────────────────────────────
    $where  = 'goal_id = ?';
    $params = [$goal_id];

    if ($date_from !== '') { $where .= ' AND contribution_date >= ?'; $params[] = $date_from; }
    if ($date_to   !== '') { $where .= ' AND contribution_date <= ?'; $params[] = $date_to;   }

    // ── Total de registros para paginación ───────────────────────────────────
    $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM savings_contributions WHERE {$where}");
    $count_stmt->execute($params);
    $total_records = (int) $count_stmt->fetchColumn();
    $total_pages   = (int) ceil($total_records / $per_page);
    $offset        = ($page - 1) * $per_page;

    // ── Totales agregados (sobre el filtro completo, no solo la página) ──────
    $agg_stmt = $pdo->prepare("
        SELECT COALESCE(SUM(amount), 0)   AS total_contributed,
               COALESCE(MAX(amount), 0)   AS max_contribution,
               COALESCE(MIN(amount), 0)   AS min_contribution,
               COALESCE(AVG(amount), 0)   AS avg_contribution,
               COUNT(*)                   AS contribution_count
        FROM   savings_contributions
        WHERE  {$where}
    ");
    $agg_stmt->execute($params);
    $aggregates = $agg_stmt->fetch(PDO::FETCH_ASSOC);

    // ── Historial paginado con columnas explícitas ───────────────────────────
    $list_params   = array_merge($params, [$per_page, $offset]);
    $history_stmt  = $pdo->prepare("
        SELECT id,
               goal_id,
               amount,
               note,
               contribution_date,
               created_at
        FROM   savings_contributions
        WHERE  {$where}
        ORDER  BY contribution_date DESC, id DESC
        LIMIT  ? OFFSET ?
    ");
    $history_stmt->execute($list_params);
    $contributions = $history_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Castear tipos para JSON limpio
    foreach ($contributions as &$row) {
        $row['id']     = (int)   $row['id'];
        $row['goal_id']= (int)   $row['goal_id'];
        $row['amount'] = (float) $row['amount'];
    }
    unset($row);

    echo json_encode([
        'success' => true,
        'goal'    => [
            'id'             => (int)   $goal['id'],
            'name'           => $goal['name'],
            'target_amount'  => (float) $goal['target_amount'],
            'current_amount' => (float) $goal['current_amount'],
            'currency_code'  => $goal['currency_code'],
            'progress_pct'   => $goal['target_amount'] > 0
                                    ? round((float)$goal['current_amount'] / (float)$goal['target_amount'] * 100, 2)
                                    : 0,
        ],
        'aggregates' => [
            'total_contributed' => (float) $aggregates['total_contributed'],
            'max_contribution'  => (float) $aggregates['max_contribution'],
            'min_contribution'  => (float) $aggregates['min_contribution'],
            'avg_contribution'  => round((float) $aggregates['avg_contribution'], 2),
            'contribution_count'=> (int)   $aggregates['contribution_count'],
        ],
        'pagination' => [
            'page'          => $page,
            'per_page'      => $per_page,
            'total_records' => $total_records,
            'total_pages'   => $total_pages,
            'has_next'      => $page < $total_pages,
            'has_prev'      => $page > 1,
        ],
        'data' => $contributions,
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success'      => false,
        'message'      => 'Error en la base de datos.',
        'full_message' => 'PDOException: ' . $e->getMessage()
                        . ' | ' . $e->getFile() . ':' . $e->getLine(),
    ]);
}