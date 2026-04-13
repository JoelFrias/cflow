<?php
// ajax/reminders.php - Maneja las operaciones relacionadas con los recordatorios (agregar, completar, eliminar)

require_once '../config/database.php';
redirectIfNotLoggedIn();

header('Content-Type: application/json');

// 1. SEGURIDAD: Solo permitir peticiones POST para acciones que modifican datos
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido. Use POST.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action  = $_POST['action'] ?? '';
$response = [];

try {
    // 2. INICIAR TRANSACCIÓN
    $pdo->beginTransaction();

    switch ($action) {

        // ============================================
        // AGREGAR RECORDATORIO
        // ============================================
        case 'add':
            $title         = trim($_POST['title'] ?? '');
            $description   = trim($_POST['description'] ?? '');
            $reminder_date = $_POST['reminder_date'] ?? '';
            $is_recurring  = isset($_POST['is_recurring']) ? 1 : 0;
            $recurrence    = $_POST['recurrence'] ?? null;

            // Validaciones centralizadas
            if (empty($title)) {
                throw new Exception('El título es requerido', 422);
            }
            if (empty($reminder_date)) {
                throw new Exception('La fecha es requerida', 422);
            }

            $stmt = $pdo->prepare("
                INSERT INTO reminders (user_id, title, description, reminder_date, is_recurring, recurrence_interval)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$user_id, $title, $description, $reminder_date, $is_recurring, $recurrence]);
            
            $new_id = $pdo->lastInsertId();

            $response = [
                'success' => true,
                'message' => 'Recordatorio creado correctamente',
                'reminder' => [
                    'id'                  => $new_id,
                    'title'               => htmlspecialchars($title),
                    'description'         => htmlspecialchars($description),
                    'reminder_date'       => $reminder_date,
                    'is_recurring'        => $is_recurring,
                    'recurrence_interval' => $recurrence,
                    'completed'           => 0,
                ]
            ];
            break;

        // ============================================
        // COMPLETAR RECORDATORIO
        // ============================================
        case 'complete':
            $reminder_id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

            if (!$reminder_id) {
                throw new Exception('ID de recordatorio no válido o no especificado', 422);
            }

            $stmt = $pdo->prepare("UPDATE reminders SET completed = 1 WHERE id = ? AND user_id = ?");
            $stmt->execute([$reminder_id, $user_id]);

            if ($stmt->rowCount() === 0) {
                throw new Exception('Recordatorio no encontrado o ya completado', 404);
            }

            // 3. SEGURIDAD CORREGIDA: Se añade user_id al SELECT para evitar fugas de datos
            $stmt = $pdo->prepare("SELECT * FROM reminders WHERE id = ? AND user_id = ?");
            $stmt->execute([$reminder_id, $user_id]);
            $completed = $stmt->fetch(PDO::FETCH_ASSOC);

            $response = [
                'success'  => true,
                'message'  => 'Recordatorio marcado como completado',
                'reminder' => $completed,
            ];
            break;

        // ============================================
        // ELIMINAR RECORDATORIO
        // ============================================
        case 'delete':
            $reminder_id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

            if (!$reminder_id) {
                throw new Exception('ID de recordatorio no válido o no especificado', 422);
            }

            $stmt = $pdo->prepare("DELETE FROM reminders WHERE id = ? AND user_id = ?");
            $stmt->execute([$reminder_id, $user_id]);

            if ($stmt->rowCount() === 0) {
                throw new Exception('Recordatorio no encontrado o no tienes permisos', 404);
            }

            $response = [
                'success' => true,
                'message' => 'Recordatorio eliminado correctamente',
                'id'      => $reminder_id,
            ];
            break;

        // ============================================
        // ACCIÓN DESCONOCIDA
        // ============================================
        default:
            throw new Exception("Acción desconocida: '$action'", 400);
    }

    // 4. CONFIRMAR TRANSACCIÓN SI TODO SALIÓ BIEN
    $pdo->commit();
    echo json_encode($response);

} catch (Exception $e) {
    // 5. REVERTIR CAMBIOS SI HUBO UN ERROR (Incluso si fue una validación fallida)
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $errorCode = $e->getCode();
    // Asegurar que el código sea un HTTP status válido (por defecto 500 si la BD falla)
    $httpCode = ($errorCode >= 400 && $errorCode < 600) ? $errorCode : 500;
    
    $errorMsg = $e->getMessage();
    
    if ($httpCode === 500) {
        error_log("[reminders.ajax] ERROR: $errorMsg"); // Guardar error real en el log del servidor
    }

    http_response_code($httpCode);
    echo json_encode([
        'success' => false,
        // Mostrar mensaje genérico para errores 500, o el mensaje de validación para errores 4xx
        'message' => $httpCode === 500 ? 'Error interno del servidor al procesar la solicitud.' : $errorMsg,
        'debug'   => $httpCode === 500 ? $errorMsg : null 
    ]);
}