<?php
// ajax/categories.php

require_once '../config/database.php';

header('Content-Type: application/json');

redirectIfNotLoggedIn();

$user_id = (int) ($_SESSION['user_id'] ?? 0);
$action  = $_POST['action'] ?? $_GET['action'] ?? '';

const VALID_TYPES = ['income', 'expense'];

function inTransaction(PDO $pdo): bool
{
    try { return $pdo->inTransaction(); } catch (Throwable) { return false; }
}

try {
    switch ($action) {

        // ============================================
        // OBTENER CATEGORÍAS DEL USUARIO
        // Solo devuelve las categorías propias del
        // usuario (user_id = ?) y excluye siempre
        // las de ajuste/globales del sistema
        // (is_adjustment = 1), ya que esas no deben
        // aparecer ni editarse desde la UI.
        // ============================================
        case 'get_categories':
            $stmt = $pdo->prepare("
                SELECT c.id,
                       c.name,
                       c.type,
                       c.parent_id,
                       c.is_active,
                       p.name AS parent_name
                FROM   categories c
                LEFT JOIN categories p ON c.parent_id = p.id
                WHERE  c.user_id       = ?
                  AND  c.is_adjustment = 0
                ORDER  BY c.type, c.is_active DESC, c.name
            ");
            $stmt->execute([$user_id]);
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($categories as &$cat) {
                $cat['is_active'] = (bool)(int)$cat['is_active'];
            }
            unset($cat);

            echo json_encode(['success' => true, 'categories' => $categories]);
            break;

        // ============================================
        // CREAR CATEGORÍA
        // Los usuarios solo pueden crear sus propias
        // categorías normales. is_adjustment e
        // is_global no son configurables desde aquí.
        // ============================================
        case 'add_category':
            $name      = trim($_POST['name'] ?? '');
            $type      = trim($_POST['type'] ?? '');
            $parent_id = isset($_POST['parent_id']) && $_POST['parent_id'] !== ''
                            ? (int) $_POST['parent_id']
                            : null;

            if ($name === '' || mb_strlen($name) > 100) {
                throw new Exception('El nombre es obligatorio y no puede superar 100 caracteres.');
            }
            if (!in_array($type, VALID_TYPES, true)) {
                throw new Exception('Tipo de categoría inválido.');
            }

            $pdo->beginTransaction();

            // Verificar duplicado dentro de las categorías del propio usuario
            $chk = $pdo->prepare("
                SELECT id FROM categories
                WHERE  user_id       = ?
                  AND  name          = ?
                  AND  type          = ?
                  AND  is_adjustment = 0
                LIMIT  1
            ");
            $chk->execute([$user_id, $name, $type]);
            if ($chk->fetch()) {
                throw new Exception('Ya existe una categoría llamada "' . htmlspecialchars($name, ENT_QUOTES) . '" de ese tipo.');
            }

            // Verificar que el padre (si aplica) pertenezca al usuario
            // y no sea una categoría de ajuste
            if ($parent_id !== null) {
                $chkP = $pdo->prepare("
                    SELECT id FROM categories
                    WHERE  id            = ?
                      AND  user_id       = ?
                      AND  is_adjustment = 0
                    LIMIT  1
                ");
                $chkP->execute([$parent_id, $user_id]);
                if (!$chkP->fetch()) {
                    throw new Exception('La categoría padre no existe o no te pertenece.');
                }
            }

            $insert = $pdo->prepare("
                INSERT INTO categories (user_id, name, type, parent_id, is_active, is_adjustment, is_global)
                VALUES (?, ?, ?, ?, 1, 0, 0)
            ");
            $insert->execute([$user_id, $name, $type, $parent_id]);
            $new_id = (int) $pdo->lastInsertId();

            $pdo->commit();

            $fetch = $pdo->prepare("
                SELECT c.id, c.name, c.type, c.parent_id, c.is_active,
                       p.name AS parent_name
                FROM   categories c
                LEFT JOIN categories p ON c.parent_id = p.id
                WHERE  c.id = ?
            ");
            $fetch->execute([$new_id]);
            $new_category = $fetch->fetch(PDO::FETCH_ASSOC);
            $new_category['is_active'] = true;

            echo json_encode([
                'success'  => true,
                'message'  => 'Categoría "' . htmlspecialchars($name, ENT_QUOTES) . '" creada.',
                'category' => $new_category,
            ]);
            break;

        // ============================================
        // HABILITAR / DESHABILITAR CATEGORÍA
        // Solo aplica a categorías propias del usuario
        // y no de ajuste. Las categorías globales del
        // sistema (is_adjustment=1, user_id=NULL) no
        // pueden ser afectadas porque la condición
        // user_id = ? nunca coincide con NULL.
        // ============================================
        case 'toggle_category':
            $cat_id = (int) ($_POST['cat_id'] ?? 0);
            if ($cat_id <= 0) throw new Exception('ID de categoría inválido.');

            $pdo->beginTransaction();

            // Verificar propiedad, estado actual y que no sea de ajuste
            $stmt = $pdo->prepare("
                SELECT id, name, is_active FROM categories
                WHERE  id            = ?
                  AND  user_id       = ?
                  AND  is_adjustment = 0
                LIMIT  1
            ");
            $stmt->execute([$cat_id, $user_id]);
            $cat = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$cat) throw new Exception('Categoría no encontrada o no te pertenece.');

            $new_state = (int)$cat['is_active'] === 1 ? 0 : 1;
            $label     = $new_state === 1 ? 'habilitada' : 'deshabilitada';

            // Si se desactiva, desactivar también subcategorías activas del usuario
            if ($new_state === 0) {
                $pdo->prepare("
                    UPDATE categories
                    SET    is_active = 0
                    WHERE  parent_id     = ?
                      AND  user_id       = ?
                      AND  is_adjustment = 0
                ")->execute([$cat_id, $user_id]);
            }

            $pdo->prepare("
                UPDATE categories SET is_active = ?
                WHERE  id      = ?
                  AND  user_id = ?
            ")->execute([$new_state, $cat_id, $user_id]);

            $pdo->commit();

            echo json_encode([
                'success'   => true,
                'message'   => 'Categoría "' . htmlspecialchars($cat['name'], ENT_QUOTES) . '" ' . $label . '.',
                'cat_id'    => $cat_id,
                'is_active' => (bool) $new_state,
            ]);
            break;

        // ============================================
        // ACCIÓN NO RECONOCIDA
        // Nota: delete_category ha sido eliminado.
        // Las categorías ya no se pueden borrar
        // desde la interfaz; solo deshabilitar.
        // ============================================
        default:
            throw new Exception('Acción no reconocida.');
    }

} catch (PDOException $e) {
    if (inTransaction($pdo)) $pdo->rollBack();
    error_log('PDOException [categories.php]: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno en la base de datos.']);

} catch (Exception $e) {
    if (inTransaction($pdo)) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}