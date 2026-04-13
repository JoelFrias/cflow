<?php
// ajax/categories.php - Maneja las operaciones CRUD para categorías (ingresos y gastos) a través de AJAX.

require_once '../config/database.php';

header('Content-Type: application/json');

redirectIfNotLoggedIn();

$user_id = (int) ($_SESSION['user_id'] ?? 0);
$action  = $_POST['action'] ?? $_GET['action'] ?? '';

// Tipos válidos como constante para reutilizar en validaciones
const VALID_TYPES = ['income', 'expense'];

/**
 * Devuelve true si hay una transacción PDO activa.
 * Evita llamar rollBack() cuando no se inició beginTransaction().
 */
function inTransaction(PDO $pdo): bool
{
    try {
        return $pdo->inTransaction();
    } catch (Throwable) {
        return false;
    }
}

try {
    switch ($action) {

        // ============================================
        // OBTENER TODAS LAS CATEGORÍAS
        // ============================================
        case 'get_categories':
            $stmt = $pdo->prepare("
                SELECT c.id,
                       c.name,
                       c.type,
                       c.parent_id,
                       p.name AS parent_name
                FROM   categories c
                LEFT JOIN categories p ON c.parent_id = p.id
                WHERE  c.user_id = ?
                ORDER  BY c.type, c.name
            ");
            $stmt->execute([$user_id]);
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'categories' => $categories]);
            break;

        // ============================================
        // CREAR CATEGORÍA
        // ============================================
        case 'add_category':
            $name      = trim($_POST['name'] ?? '');
            $type      = trim($_POST['type'] ?? '');
            $parent_id = isset($_POST['parent_id']) && $_POST['parent_id'] !== ''
                            ? (int) $_POST['parent_id']
                            : null;

            // ---- Validaciones previas a la TX ----
            if ($name === '' || mb_strlen($name) > 100) {
                throw new Exception('El nombre es obligatorio y no puede superar 100 caracteres.');
            }
            if (!in_array($type, VALID_TYPES, true)) {
                throw new Exception('Tipo de categoría inválido. Use "income" o "expense".');
            }

            // ---- Inicio de transacción ----
            $pdo->beginTransaction();

            // Verificar duplicado
            $chk = $pdo->prepare("
                SELECT id FROM categories
                WHERE  user_id = ? AND name = ? AND type = ?
                LIMIT  1
            ");
            $chk->execute([$user_id, $name, $type]);
            if ($chk->fetch()) {
                throw new Exception('Ya existe una categoría llamada "' . htmlspecialchars($name, ENT_QUOTES) . '" de ese tipo.');
            }

            // Verificar padre (si aplica)
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
            $insert = $pdo->prepare("
                INSERT INTO categories (user_id, name, type, parent_id)
                VALUES (?, ?, ?, ?)
            ");
            $insert->execute([$user_id, $name, $type, $parent_id]);
            $new_id = (int) $pdo->lastInsertId();

            $pdo->commit();

            // Recuperar el registro completo tras el commit
            $fetch = $pdo->prepare("
                SELECT c.id,
                       c.name,
                       c.type,
                       c.parent_id,
                       p.name AS parent_name
                FROM   categories c
                LEFT JOIN categories p ON c.parent_id = p.id
                WHERE  c.id = ?
            ");
            $fetch->execute([$new_id]);
            $new_category = $fetch->fetch(PDO::FETCH_ASSOC);

            echo json_encode([
                'success'  => true,
                'message'  => 'Categoría "' . htmlspecialchars($name, ENT_QUOTES) . '" creada correctamente.',
                'category' => $new_category,
            ]);
            break;

        // ============================================
        // ELIMINAR CATEGORÍA
        // ============================================
        case 'delete_category':
            $cat_id = (int) ($_POST['cat_id'] ?? 0);

            if ($cat_id <= 0) {
                throw new Exception('ID de categoría inválido.');
            }

            // ---- Inicio de transacción ----
            $pdo->beginTransaction();

            // Verificar propiedad
            $stmt = $pdo->prepare("
                SELECT id, name FROM categories
                WHERE  id = ? AND user_id = ?
                LIMIT  1
            ");
            $stmt->execute([$cat_id, $user_id]);
            $cat = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$cat) {
                throw new Exception('Categoría no encontrada o no te pertenece.');
            }

            // Verificar subcategorías
            $chkSub = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE parent_id = ?");
            $chkSub->execute([$cat_id]);
            if ((int) $chkSub->fetchColumn() > 0) {
                throw new Exception('No puedes eliminar "' . htmlspecialchars($cat['name'], ENT_QUOTES) . '" porque tiene subcategorías.');
            }

            // Verificar transacciones asociadas
            $chkTx = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE category_id = ?");
            $chkTx->execute([$cat_id]);
            if ((int) $chkTx->fetchColumn() > 0) {
                throw new Exception('No puedes eliminar "' . htmlspecialchars($cat['name'], ENT_QUOTES) . '" porque tiene transacciones asociadas.');
            }

            // Eliminar
            $pdo->prepare("DELETE FROM categories WHERE id = ? AND user_id = ?")->execute([$cat_id, $user_id]);

            $pdo->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Categoría "' . htmlspecialchars($cat['name'], ENT_QUOTES) . '" eliminada.',
                'cat_id'  => $cat_id,
            ]);
            break;

        default:
            throw new Exception("Acción no reconocida.");
    }

} catch (PDOException $e) {
    if (inTransaction($pdo)) {
        $pdo->rollBack();
    }
    // Nunca exponer detalles del motor de base de datos en producción.
    // Registra $e->getMessage() en los logs del servidor en su lugar.
    error_log('PDOException [categories.php]: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());
    echo json_encode([
        'success' => false,
        'message' => 'Error interno en la base de datos. Inténtalo de nuevo.',
    ]);

} catch (Exception $e) {
    if (inTransaction($pdo)) {
        $pdo->rollBack();
    }
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ]);
}