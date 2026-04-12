<?php
// ajax/categories.php - Maneja las operaciones CRUD para categorías (ingresos y gastos) a través de AJAX.

require_once '../config/database.php';

header('Content-Type: application/json');

redirectIfNotLoggedIn();

$user_id = $_SESSION['user_id'];
$action  = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {

        // ============================================
        // OBTENER TODAS LAS CATEGORÍAS
        // ============================================
        case 'get_categories':
            $stmt = $pdo->prepare("
                SELECT c.*, p.name as parent_name
                FROM categories c
                LEFT JOIN categories p ON c.parent_id = p.id
                WHERE c.user_id = ?
                ORDER BY c.type, c.name
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
            $parent_id = !empty($_POST['parent_id']) ? (int) $_POST['parent_id'] : null;

            if (empty($name)) throw new Exception('El nombre de la categoría es obligatorio.');
            if (!in_array($type, ['income', 'expense'])) throw new Exception('Tipo de categoría inválido.');

            $chk = $pdo->prepare("SELECT id FROM categories WHERE user_id = ? AND name = ? AND type = ?");
            $chk->execute([$user_id, $name, $type]);
            if ($chk->fetch()) throw new Exception("Ya existe una categoría llamada \"{$name}\" de ese tipo.");

            if ($parent_id !== null) {
                $chkP = $pdo->prepare("SELECT id FROM categories WHERE id = ? AND user_id = ?");
                $chkP->execute([$parent_id, $user_id]);
                if (!$chkP->fetch()) throw new Exception('La categoría padre seleccionada no existe.');
            }

            $stmt = $pdo->prepare("INSERT INTO categories (user_id, name, type, parent_id) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user_id, $name, $type, $parent_id]);
            $new_id = $pdo->lastInsertId();

            $stmt2 = $pdo->prepare("
                SELECT c.*, p.name as parent_name
                FROM categories c
                LEFT JOIN categories p ON c.parent_id = p.id
                WHERE c.id = ?
            ");
            $stmt2->execute([$new_id]);
            $new_category = $stmt2->fetch(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'message' => "Categoría \"{$name}\" creada correctamente.", 'category' => $new_category]);
            break;

        // ============================================
        // ELIMINAR CATEGORÍA
        // ============================================
        case 'delete_category':
            $cat_id = (int) ($_POST['cat_id'] ?? 0);

            $stmt = $pdo->prepare("SELECT id, name FROM categories WHERE id = ? AND user_id = ?");
            $stmt->execute([$cat_id, $user_id]);
            $cat = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$cat) throw new Exception('Categoría no encontrada.');

            $chkSub = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE parent_id = ?");
            $chkSub->execute([$cat_id]);
            if ($chkSub->fetchColumn() > 0) throw new Exception("No puedes eliminar \"{$cat['name']}\" porque tiene subcategorías.");

            $chkTx = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE category_id = ?");
            $chkTx->execute([$cat_id]);
            if ($chkTx->fetchColumn() > 0) throw new Exception("No puedes eliminar \"{$cat['name']}\" porque tiene transacciones asociadas.");

            $pdo->prepare("DELETE FROM categories WHERE id = ? AND user_id = ?")->execute([$cat_id, $user_id]);
            echo json_encode(['success' => true, 'message' => "Categoría \"{$cat['name']}\" eliminada.", 'cat_id' => $cat_id]);
            break;

        default:
            throw new Exception("Acción '{$action}' no reconocida.");
    }

} catch (PDOException $e) {
    $full = 'PDOException: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine();
    echo json_encode(['success' => false, 'message' => 'Error en la base de datos. Revisa la consola.', 'full_message' => $full]);
} catch (Exception $e) {
    echo json_encode([
        'success'      => false,
        'message'      => $e->getMessage(),
        'full_message' => 'Exception: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine(),
    ]);
}