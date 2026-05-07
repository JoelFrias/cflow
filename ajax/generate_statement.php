<?php
// generate_statement.php
// Generador de estado de cuenta en PDF usando FPDF
// Llamado vía GET: ?account_id=X&month=4&year=2026

require_once '../config/database.php';
require_once '../libs/fpdf/fpdf.php';

// ─── Seguridad ────────────────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit('Acceso denegado');
}
$userId = (int)$_SESSION['user_id'];

// ─── Parámetros ───────────────────────────────────────────────
$accountId = (int)($_GET['account_id'] ?? 0);
$month     = (int)($_GET['month']      ?? date('n'));
$year      = (int)($_GET['year']       ?? date('Y'));

if (!$accountId || $month < 1 || $month > 12 || $year < 2000) {
    http_response_code(400);
    exit('Parámetros inválidos');
}

// ─── Validar que la cuenta pertenece al usuario ───────────────
$stmtAcc = $pdo->prepare("
    SELECT a.*, c.symbol, c.name AS currency_name
    FROM   accounts a
    JOIN   currencies c ON c.code = a.currency_code
    WHERE  a.id = ? AND a.user_id = ? AND a.is_active = 1
");
$stmtAcc->execute([$accountId, $userId]);
$account = $stmtAcc->fetch(PDO::FETCH_ASSOC);
if (!$account) { http_response_code(404); exit('Cuenta no encontrada'); }

// ─── Rango del mes ────────────────────────────────────────────
$dateFrom = sprintf('%04d-%02d-01', $year, $month);
$dateTo   = date('Y-m-t', strtotime($dateFrom));

// ─── Helpers ──────────────────────────────────────────────────
$sym    = $account['symbol'];
$months = [
    1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',
    5=>'Mayo',6=>'Junio',7=>'Julio',8=>'Agosto',
    9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'
];
$monthName = $months[$month];

function enc($str) {
    return iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', (string)$str);
}
function fmtAmt($n) {
    return number_format((float)$n, 2, '.', ',');
}
function fmtDate($d) {
    global $months;
    if (!$d) return '';
    [$y, $m, $day] = explode('-', $d);
    return $months[(int)$m] . ' ' . ltrim($day, '0') . ', ' . $y;
}
function fmtDateShort($d) {
    if (!$d) return '';
    [$y, $m, $day] = explode('-', $d);
    return sprintf('%02d/%02d/%04d', (int)$day, (int)$m, (int)$y);
}

// ═══════════════════════════════════════════════════════════════
// CLASE PDF PERSONALIZADA
// ═══════════════════════════════════════════════════════════════
class BankStatement extends FPDF {

    public $acctName  = '';
    public $period    = '';
    public $pageNum   = 0;

    function Header() {
        $this->pageNum++;

        // ── Barra superior azul ──
        $this->SetFillColor(30, 41, 59);
        $this->Rect(0, 0, 210, 22, 'F');

        // Logo / nombre del sistema
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Helvetica', 'B', 13);
        $this->SetXY(14, 6);
        $this->Cell(100, 8, enc('CFlow'), 0, 0, 'L');

        // Título del documento
        $this->SetFont('Helvetica', '', 9);
        $this->SetXY(14, 14);
        $this->Cell(100, 5, enc('ESTADO DE CUENTA'), 0, 0, 'L');

        // Página
        $this->SetXY(130, 9);
        $this->Cell(66, 5, enc('Pag. ' . $this->pageNum), 0, 0, 'R');

        $this->SetTextColor(0, 0, 0);
        $this->SetY(28);
    }

    function Footer() {
        $this->SetY(-13);
        $this->SetFont('Helvetica', '', 7);
        $this->SetTextColor(150, 150, 150);
        $this->Cell(0, 5, enc('Documento generado el ' . date('d/m/Y H:i') . '  —  Solo para uso informativo'), 0, 0, 'C');
    }

    function HRule($y = null, $r = 203, $g = 213, $b = 225) {
        $y = $y ?? $this->GetY();
        $this->SetDrawColor($r, $g, $b);
        $this->SetLineWidth(0.2);
        $this->Line(14, $y, 196, $y);
    }

    function BorderCell($w, $h, $txt, $align = 'L', $fill = false) {
        $x = $this->GetX();
        $y = $this->GetY();
        if ($fill) {
            $this->SetFillColor(248, 250, 252);
            $this->Rect($x, $y, $w, $h, 'F');
        }
        $this->SetDrawColor(226, 232, 240);
        $this->SetLineWidth(0.2);
        $this->Line($x, $y + $h, $x + $w, $y + $h);
        $this->SetXY($x, $y);
        $this->Cell($w, $h, $txt, 0, 0, $align);
        $this->SetXY($x + $w, $y);
    }

    function RoundedRect($x, $y, $w, $h, $r, $style = '') {
        $op = match($style) { 'F' => 'f', 'FD', 'DF' => 'B', default => 'S' };
        $k = $this->k; $hp = $this->h;
        $this->_out(sprintf('%.2F %.2F m', ($x+$r)*$k, ($hp-$y)*$k));
        $this->_out(sprintf('%.2F %.2F l', ($x+$w-$r)*$k, ($hp-$y)*$k));
        $this->_Arc($x+$w-$r,$y, $x+$w,$y+$r, $x+$w,$y);
        $this->_out(sprintf('%.2F %.2F l', ($x+$w)*$k, ($hp-($y+$h-$r))*$k));
        $this->_Arc($x+$w,$y+$h-$r, $x+$w-$r,$y+$h, $x+$w-$r,$y+$h);
        $this->_out(sprintf('%.2F %.2F l', ($x+$r)*$k, ($hp-($y+$h))*$k));
        $this->_Arc($x+$r,$y+$h, $x,$y+$h-$r, $x,$y+$h-$r);
        $this->_out(sprintf('%.2F %.2F l', $x*$k, ($hp-($y+$r))*$k));
        $this->_Arc($x,$y+$r, $x+$r,$y, $x+$r,$y);
        $this->_out($op);
    }

    function _Arc($x1,$y1,$x2,$y2,$x3,$y3) {
        $h = $this->h;
        $this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c',
            $x1*$this->k, ($h-$y1)*$this->k,
            $x2*$this->k, ($h-$y2)*$this->k,
            $x3*$this->k, ($h-$y3)*$this->k));
    }
}

// ═══════════════════════════════════════════════════════════════
// COMPARAR PERÍODO VS FECHA DE CREACIÓN DE LA CUENTA
// ═══════════════════════════════════════════════════════════════

// YearMonth numérico para comparación sencilla (ej: 202604)
$createdDt = new DateTime($account['created_at']);
$createdYM = (int)$createdDt->format('Ym');   // año+mes de creación
$periodYM  = (int)(sprintf('%04d%02d', $year, $month));  // año+mes pedido

// Nombre del archivo de salida
$filename = 'Estado_' . preg_replace('/[^a-zA-Z0-9]/', '_', $account['name'])
          . '_' . $monthName . '_' . $year . '.pdf';

$typeNames = ['cash'=>'Efectivo','bank'=>'Cuenta Bancaria','wallet'=>'Wallet Digital',
              'debit_card'=>'Tarjeta de Débito','credit_card'=>'Tarjeta de Crédito'];
$typeName  = $typeNames[$account['type']] ?? $account['type'];

// ═══════════════════════════════════════════════════════════════
// CASO A: PERÍODO ANTERIOR A LA CREACIÓN DE LA CUENTA
// ═══════════════════════════════════════════════════════════════
if ($periodYM < $createdYM) {

    $pdf = new BankStatement('P', 'mm', 'A4');
    $pdf->acctName = $account['name'];
    $pdf->period   = $monthName . ' ' . $year;
    $pdf->SetMargins(14, 28, 14);
    $pdf->SetAutoPageBreak(true, 16);
    $pdf->AddPage();

    // Info de cuenta
    $pdf->SetFont('Helvetica', 'B', 11);
    $pdf->SetTextColor(30, 41, 59);
    $pdf->Cell(0, 7, enc($account['name']), 0, 1, 'L');
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->SetTextColor(100, 116, 139);
    $pdf->Cell(95, 5, enc('Tipo de cuenta: ' . $typeName), 0, 0, 'L');
    $pdf->Cell(95, 5, enc('Moneda: ' . $account['currency_name'] . ' (' . $sym . ')'), 0, 1, 'R');
    $pdf->Cell(95, 5, enc('Período solicitado: ' . $monthName . ' ' . $year), 0, 1, 'L');
    $pdf->Ln(4);
    $pdf->HRule();
    $pdf->Ln(30);

    // Ícono / mensaje central
    $pdf->SetFont('Helvetica', 'B', 14);
    $pdf->SetTextColor(148, 163, 184);
    $pdf->Cell(0, 10, enc('Sin datos para este período'), 0, 1, 'C');
    $pdf->Ln(4);

    $pdf->SetFont('Helvetica', '', 10);
    $pdf->SetTextColor(148, 163, 184);
    $pdf->MultiCell(0, 6,
        enc('La cuenta "' . $account['name'] . '" fue creada el ' .
            fmtDate($createdDt->format('Y-m-d')) . '.' . "\n" .
            'No existen movimientos ni registros anteriores a esa fecha.'),
        0, 'C'
    );

    $pdf->Output('I', $filename);
    exit;
}

// ═══════════════════════════════════════════════════════════════
// CASO B: PERÍODO VÁLIDO — CALCULAR BALANCES
// ═══════════════════════════════════════════════════════════════

// ── 1. Balance inicial REAL de la cuenta ─────────────────────
//
// Como la BD no guarda un campo "initial_balance", lo reconstruimos:
//   balance_inicial = balance_actual
//                   − Σ ingresos (all time)
//                   + Σ gastos   (all time)
//                   + Σ transferencias salientes (all time)
//                   − Σ transferencias entrantes (all time)
//
$stmtTrueInit = $pdo->prepare("
    SELECT
        COALESCE(SUM(CASE WHEN t.type = 'income'  THEN t.amount ELSE 0 END), 0) AS total_inc,
        COALESCE(SUM(CASE WHEN t.type = 'expense' THEN t.amount ELSE 0 END), 0) AS total_exp,
        COALESCE(SUM(CASE WHEN t.type = 'transfer' AND t.account_id          = :aid1 THEN t.amount ELSE 0 END), 0) AS total_out,
        COALESCE(SUM(CASE WHEN t.type = 'transfer' AND t.transfer_to_account = :aid2 THEN t.amount ELSE 0 END), 0) AS total_in
    FROM transactions t
    WHERE t.account_id = :aid3 OR t.transfer_to_account = :aid4
");
$stmtTrueInit->execute([
    ':aid1' => $accountId, ':aid2' => $accountId,
    ':aid3' => $accountId, ':aid4' => $accountId,
]);
$initRow = $stmtTrueInit->fetch(PDO::FETCH_ASSOC);

$trueInitialBalance = (float)$account['balance']
    - (float)$initRow['total_inc']
    + (float)$initRow['total_exp']
    + (float)$initRow['total_out']
    - (float)$initRow['total_in'];

// ── 2. Neto de transacciones ANTES del período ────────────────
//
// Para el mes de apertura de la cuenta esto será 0 (no hay nada previo),
// por lo que openingBalance quedará igual a trueInitialBalance.  ✓
//
$stmtAllPrev = $pdo->prepare("
    SELECT
        COALESCE(SUM(CASE WHEN type = 'income'  THEN amount ELSE 0 END), 0) AS total_inc,
        COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) AS total_exp,
        COALESCE(
            SUM(CASE WHEN type = 'transfer' AND account_id          = :aid1 THEN -amount ELSE 0 END), 0)
          + COALESCE(
            SUM(CASE WHEN type = 'transfer' AND transfer_to_account = :aid2 THEN  amount ELSE 0 END), 0)
          AS transfer_net
    FROM transactions
    WHERE (account_id = :aid3 OR transfer_to_account = :aid4)
      AND date < :dfrom
");
$stmtAllPrev->execute([
    ':aid1'  => $accountId, ':aid2'  => $accountId,
    ':aid3'  => $accountId, ':aid4'  => $accountId,
    ':dfrom' => $dateFrom,
]);
$allPrev = $stmtAllPrev->fetch(PDO::FETCH_ASSOC);

// ── 3. Balance de apertura del período ───────────────────────
//
//   opening = balance_inicial_real
//           + ingresos_previos − gastos_previos + transferencias_netas_previas
//
$openingBalance = $trueInitialBalance
    + (float)$allPrev['total_inc']
    - (float)$allPrev['total_exp']
    + (float)$allPrev['transfer_net'];

// ─── Transacciones del período ────────────────────────────────
$stmtTx = $pdo->prepare("
    SELECT
        t.id,
        t.date,
        t.type,
        t.amount,
        t.account_id,
        t.description,
        t.transfer_to_account,
        COALESCE(cat.name, 'Sin categoría') AS category_name,
        acc2.name AS transfer_to_name
    FROM   transactions t
    LEFT JOIN categories cat  ON cat.id  = t.category_id
    LEFT JOIN accounts   acc2 ON acc2.id = t.transfer_to_account
    WHERE  (t.account_id = ? OR t.transfer_to_account = ?)
      AND  t.date BETWEEN ? AND ?
    ORDER  BY t.date ASC, t.id ASC
");
$stmtTx->execute([$accountId, $accountId, $dateFrom, $dateTo]);
$transactions = $stmtTx->fetchAll(PDO::FETCH_ASSOC);

// ─── Calcular créditos, débitos y balance corrido ─────────────
$totalCredits = 0;
$totalDebits  = 0;
$runningBal   = $openingBalance;
$txProcessed  = [];

foreach ($transactions as $tx) {
    $amt    = (float)$tx['amount'];
    $credit = 0;
    $debit  = 0;

    if ($tx['type'] === 'income') {
        $credit = $amt;
        $totalCredits += $amt;
    } elseif ($tx['type'] === 'expense') {
        $debit = $amt;
        $totalDebits += $amt;
    } elseif ($tx['type'] === 'transfer') {
        if ((int)$tx['transfer_to_account'] === $accountId) {
            // Esta cuenta es el DESTINO → crédito
            $credit = $amt;
            $totalCredits += $amt;
        } else {
            // Esta cuenta es el ORIGEN → débito
            $debit = $amt;
            $totalDebits += $amt;
        }
    }

    $runningBal += $credit - $debit;

    // Descripción amigable para transferencias
    $desc = $tx['description'];
    if ($tx['type'] === 'transfer' && empty(trim($desc))) {
        if ((int)$tx['transfer_to_account'] === $accountId) {
            $desc = 'Transferencia recibida';
        } else {
            $dest = $tx['transfer_to_name'] ?? 'otra cuenta';
            $desc = 'Transferencia a ' . $dest;
        }
    }

    $txProcessed[] = [
        'date'        => $tx['date'],
        'description' => $desc ?: $tx['category_name'],
        'type'        => $tx['type'],
        'credit'      => $credit,
        'debit'       => $debit,
        'balance'     => $runningBal,
    ];
}
$closingBalance = $runningBal;

// ─── Balances diarios (último del día) ───────────────────────
$dailyBalances = [];
foreach ($txProcessed as $tx) {
    $dailyBalances[$tx['date']] = $tx['balance'];
}

// ═══════════════════════════════════════════════════════════════
// CONSTRUIR PDF
// ═══════════════════════════════════════════════════════════════
$pdf = new BankStatement('P', 'mm', 'A4');
$pdf->acctName = $account['name'];
$pdf->period   = $monthName . ' ' . $year;
$pdf->SetMargins(14, 28, 14);
$pdf->SetAutoPageBreak(true, 16);
$pdf->AddPage();

// ── Info de cuenta ────────────────────────────────────────────
$pdf->SetFont('Helvetica', 'B', 11);
$pdf->SetTextColor(30, 41, 59);
$pdf->Cell(0, 7, enc($account['name']), 0, 1, 'L');

$pdf->SetFont('Helvetica', '', 9);
$pdf->SetTextColor(100, 116, 139);
$pdf->Cell(95, 5, enc('Tipo de cuenta: ' . $typeName), 0, 0, 'L');
$pdf->Cell(95, 5, enc('Moneda: ' . $account['currency_name'] . ' (' . $sym . ')'), 0, 1, 'R');
$pdf->Cell(95, 5, enc('Período: ' . $monthName . ' ' . $year), 0, 0, 'L');
$pdf->Cell(95, 5, enc('Del ' . fmtDate($dateFrom) . ' al ' . fmtDate($dateTo)), 0, 1, 'R');

// Si es el mes de apertura, indicarlo
if ($periodYM === $createdYM) {
    $pdf->Ln(1);
    $pdf->SetFont('Helvetica', 'I', 8);
    $pdf->SetTextColor(99, 102, 241);
    $pdf->Cell(0, 5, enc('Mes de apertura de la cuenta — el balance inicial refleja el saldo registrado al crearla'), 0, 1, 'L');
}

$pdf->Ln(3);
$pdf->HRule();
$pdf->Ln(5);

// ── RESUMEN ───────────────────────────────────────────────────
$pdf->SetFont('Helvetica', 'B', 9);
$pdf->SetTextColor(100, 116, 139);
$pdf->Cell(0, 5, enc('RESUMEN DEL PERÍODO'), 0, 1, 'L');
$pdf->Ln(2);

// 4 cajas de resumen
$boxW = 43;
$boxH = 20;
$boxGap = 2;
$startX = 14;
$y0 = $pdf->GetY();

$summaryData = [
    ['Balance Inicial',  $sym . ' ' . fmtAmt($openingBalance),  [241,245,249], [30,41,59]],
    ['Total Créditos',   $sym . ' ' . fmtAmt($totalCredits),    [240,253,244], [22,101,52]],
    ['Total Débitos',    $sym . ' ' . fmtAmt($totalDebits),     [255,241,242], [153,27,27]],
    ['Balance Final',    $sym . ' ' . fmtAmt($closingBalance),  [239,246,255], [30,64,175]],
];

foreach ($summaryData as $i => [$label, $value, $bg, $valColor]) {
    $bx = $startX + $i * ($boxW + $boxGap);

    $pdf->SetFillColor($bg[0], $bg[1], $bg[2]);
    $pdf->RoundedRect($bx, $y0, $boxW, $boxH, 2, 'F');

    $pdf->SetFont('Helvetica', '', 7);
    $pdf->SetTextColor(100, 116, 139);
    $pdf->SetXY($bx + 3, $y0 + 3);
    $pdf->Cell($boxW - 6, 4, enc(strtoupper($label)), 0, 1, 'L');

    $pdf->SetFont('Helvetica', 'B', 9);
    $pdf->SetTextColor($valColor[0], $valColor[1], $valColor[2]);
    $pdf->SetXY($bx + 3, $y0 + 9);
    $pdf->Cell($boxW - 6, 6, enc($value), 0, 1, 'L');
}

$pdf->SetY($y0 + $boxH + 6);

// ── CABECERA DE TABLA ─────────────────────────────────────────
// Anchos: Fecha=28 | Descripción=82 | Crédito=24 | Débito=24 | Balance=24
$cW = [28, 82, 24, 24, 24];

$pdf->SetFont('Helvetica', '', 7.5);
$pdf->SetTextColor(100, 116, 139);
$pdf->SetFillColor(248, 250, 252);
$y1 = $pdf->GetY();
$pdf->Rect(14, $y1, 182, 7, 'F');
$pdf->SetXY(14, $y1 + 1);

$headers = ['FECHA', 'DESCRIPCI' . chr(211) . 'N', 'CR' . chr(201) . 'DITO', 'D' . chr(201) . 'BITO', 'BALANCE'];
$aligns  = ['L', 'L', 'R', 'R', 'R'];
foreach ($headers as $idx => $hdr) {
    $pdf->Cell($cW[$idx], 5, $hdr, 0, 0, $aligns[$idx]);
}
$pdf->Ln(7);
$pdf->HRule();
$pdf->Ln(0.5);

// ── FILA DE BALANCE INICIAL ───────────────────────────────────
$pdf->SetFont('Helvetica', 'I', 8);
$yRow = $pdf->GetY();
$pdf->SetFillColor(241, 245, 249);
$pdf->Rect(14, $yRow, 182, 7, 'F');
$pdf->SetXY(14, $yRow);
$pdf->SetTextColor(71, 85, 105);
$pdf->Cell($cW[0], 7, enc(fmtDate($dateFrom)), 0, 0, 'L');
$pdf->SetTextColor(30, 41, 59);
$pdf->Cell($cW[1], 7, enc('Balance Inicial del Período'), 0, 0, 'L');
$pdf->Cell($cW[2], 7, '', 0, 0, 'R');
$pdf->Cell($cW[3], 7, '', 0, 0, 'R');
$pdf->SetFont('Helvetica', 'BI', 8);
$pdf->SetTextColor(30, 64, 175);
$pdf->Cell($cW[4], 7, enc(fmtAmt($openingBalance)), 0, 0, 'R');
$pdf->SetDrawColor(226, 232, 240);
$pdf->SetLineWidth(0.15);
$pdf->Line(14, $yRow + 7, 196, $yRow + 7);
$pdf->Ln(7);

// ── FILAS DE TRANSACCIONES ────────────────────────────────────
$pdf->SetFont('Helvetica', '', 8.5);
$rowH   = 7;
$altRow = false;

if (empty($txProcessed)) {
    $pdf->Ln(4);
    $pdf->SetFont('Helvetica', 'I', 9);
    $pdf->SetTextColor(148, 163, 184);
    $pdf->Cell(0, 10, enc('No hay transacciones registradas en este período.'), 0, 1, 'C');
} else {
    foreach ($txProcessed as $tx) {
        if ($pdf->GetY() > 265) {
            $pdf->AddPage();
            // Repetir cabecera de tabla
            $pdf->SetFont('Helvetica', '', 7.5);
            $pdf->SetTextColor(100, 116, 139);
            $yH = $pdf->GetY();
            $pdf->SetFillColor(248, 250, 252);
            $pdf->Rect(14, $yH, 182, 7, 'F');
            $pdf->SetXY(14, $yH + 1);
            foreach ($headers as $idx => $hdr) {
                $pdf->Cell($cW[$idx], 5, $hdr, 0, 0, $aligns[$idx]);
            }
            $pdf->Ln(7);
            $pdf->HRule();
            $pdf->Ln(0.5);
            $pdf->SetFont('Helvetica', '', 8.5);
        }

        $altRow = !$altRow;
        $yRow   = $pdf->GetY();

        if ($altRow) {
            $pdf->SetFillColor(250, 252, 255);
            $pdf->Rect(14, $yRow, 182, $rowH, 'F');
        }

        $pdf->SetTextColor(71, 85, 105);
        $pdf->SetXY(14, $yRow);
        $pdf->Cell($cW[0], $rowH, enc(fmtDate($tx['date'])), 0, 0, 'L');

        $desc = mb_substr($tx['description'], 0, 55);
        $pdf->SetTextColor(30, 41, 59);
        $pdf->Cell($cW[1], $rowH, enc($desc), 0, 0, 'L');

        $pdf->SetTextColor(22, 101, 52);
        $pdf->Cell($cW[2], $rowH, enc($tx['credit'] > 0 ? fmtAmt($tx['credit']) : ''), 0, 0, 'R');

        $pdf->SetTextColor(153, 27, 27);
        $pdf->Cell($cW[3], $rowH, enc($tx['debit'] > 0 ? fmtAmt($tx['debit']) : ''), 0, 0, 'R');

        $balColor = $tx['balance'] >= 0 ? [30, 41, 59] : [153, 27, 27];
        $pdf->SetTextColor($balColor[0], $balColor[1], $balColor[2]);
        $pdf->SetFont('Helvetica', 'B', 8.5);
        $pdf->Cell($cW[4], $rowH, enc(fmtAmt($tx['balance'])), 0, 0, 'R');
        $pdf->SetFont('Helvetica', '', 8.5);

        $pdf->SetDrawColor(226, 232, 240);
        $pdf->SetLineWidth(0.15);
        $pdf->Line(14, $yRow + $rowH, 196, $yRow + $rowH);
        $pdf->Ln($rowH);
    }
}

$pdf->Ln(8);

// ── TABLA DE BALANCES DIARIOS ─────────────────────────────────
if (!empty($dailyBalances)) {
    if ($pdf->GetY() > 220) $pdf->AddPage();

    $pdf->SetFont('Helvetica', 'B', 9);
    $pdf->SetTextColor(100, 116, 139);
    $pdf->Cell(0, 5, enc('BALANCE CIERRE DE DÍA'), 0, 1, 'L');
    $pdf->Ln(2);

    $tW     = 80;
    $xStart = 14;

    $pdf->SetFillColor(248, 250, 252);
    $yTH = $pdf->GetY();
    $pdf->Rect($xStart, $yTH, $tW, 6, 'F');
    $pdf->SetFont('Helvetica', '', 7.5);
    $pdf->SetTextColor(100, 116, 139);
    $pdf->SetXY($xStart, $yTH + 0.5);
    $pdf->Cell(36, 5, enc('FECHA'), 0, 0, 'L');
    $pdf->Cell(44, 5, enc('BALANCE FINAL'), 0, 0, 'R');
    $pdf->Ln(6);
    $pdf->SetDrawColor(226, 232, 240);
    $pdf->SetLineWidth(0.2);
    $pdf->Line($xStart, $pdf->GetY(), $xStart + $tW, $pdf->GetY());

    $pdf->SetFont('Helvetica', '', 8.5);
    $altD = false;
    foreach ($dailyBalances as $dDate => $dBal) {
        if ($pdf->GetY() > 270) $pdf->AddPage();

        $altD = !$altD;
        $yD   = $pdf->GetY();
        if ($altD) {
            $pdf->SetFillColor(250, 252, 255);
            $pdf->Rect($xStart, $yD, $tW, 6.5, 'F');
        }
        $pdf->SetXY($xStart, $yD);
        $pdf->SetTextColor(71, 85, 105);
        $pdf->Cell(36, 6.5, enc(fmtDateShort($dDate)), 0, 0, 'L');
        $balColor = $dBal >= 0 ? [30, 41, 59] : [153, 27, 27];
        $pdf->SetTextColor($balColor[0], $balColor[1], $balColor[2]);
        $pdf->SetFont('Helvetica', 'B', 8.5);
        $pdf->Cell(44, 6.5, enc(fmtAmt($dBal)), 0, 0, 'R');
        $pdf->SetFont('Helvetica', '', 8.5);

        $pdf->SetDrawColor(226, 232, 240);
        $pdf->SetLineWidth(0.15);
        $pdf->Line($xStart, $yD + 6.5, $xStart + $tW, $yD + 6.5);
        $pdf->Ln(6.5);
    }
}

// ─── Salida ───────────────────────────────────────────────────
$pdf->Output('I', $filename);