<?php
// ajax/create_category.php - Endpoint para crear una nueva categoría de ingreso o gasto.

require_once '../config/database.php';

header('Content-Type: application/json');

// ── Autenticación ────────────────────────────────────────────────────────────
// Usar redirectIfNotLoggedIn() por consistencia con el resto del sistema.
// Si el proyecto solo tiene isLoggedIn(), reemplazar por:
//   if (!isLoggedIn()) { ... exit(); }
redirectIfNotLoggedIn();

$user_id = (int) ($_SESSION['user_id'] ?? 0);

// ── Entrada ──────────────────────────────────────────────────────────────────
$name      = trim($_POST['name'] ?? '');
$type      = trim($_POST['type'] ?? '');
$parent_id = isset($_POST['parent_id']) && $_POST['parent_id'] !== ''
                ? (int) $_POST['parent_id']
                : null;

// ── Validaciones ─────────────────────────────────────────────────────────────
if ($name === '' || mb_strlen($name) > 100) {
    echo json_encode([
        'success' => false,
        'message' => 'El nombre es obligatorio y no puede superar 100 caracteres.',
    ]);
    exit();
}

if (!in_array($type, ['income', 'expense'], true)) {
    echo json_encode([
        'success' => false,
        'message' => 'Tipo inválido. Use "income" o "expense".',
    ]);
    exit();
}

// ── Operación con transacción ─────────────────────────────────────────────────
try {
    $pdo->beginTransaction();

    // Verificar duplicado (mismo usuario, nombre y tipo)
    $chk = $pdo->prepare("
        SELECT id FROM categories
        WHERE  user_id = ? AND name = ? AND type = ?
        LIMIT  1
    ");
    $chk->execute([$user_id, $name, $type]);
    if ($chk->fetch()) {
        throw new Exception('Ya existe una categoría llamada "' . htmlspecialchars($name, ENT_QUOTES) . '" de ese tipo.');
    }

    // Verificar padre si se proporcionó
    if ($parent_id !== null) {
        $chkP = $pdo->prepare("
            SELECT id FROM categories
            WHERE  id = ? AND user_id = ?
            LIMIT  1
        ");
        $chkP->execute([$parent_id, $user_id]);
        if (!$chkP->fetch()) {
            throw new Exception('La categoría padre seleccionada no existe o no te pertenece.');
        }
    }

    // Insertar
    $stmt = $pdo->prepare("
        INSERT INTO categories (user_id, name, type, parent_id)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$user_id, $name, $type, $parent_id]);
    $category_id = (int) $pdo->lastInsertId();

    $pdo->commit();

    echo json_encode([
        'success'     => true,
        'category_id' => $category_id,
        'name'        => $name,
        'type'        => $type,
        'parent_id'   => $parent_id,
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('PDOException [create_category.php]: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());
    echo json_encode([
        'success' => false,
        'message' => 'Error interno en la base de datos. Inténtalo de nuevo.',
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ]);
}