<?php
// ajax/reminders.php - Maneja las operaciones relacionadas con los recordatorios (agregar, completar, eliminar)

require_once '../config/database.php';
redirectIfNotLoggedIn();

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'];
$action  = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {

        // ============================================
        // AGREGAR RECORDATORIO
        // ============================================
        case 'add':
            $title        = trim($_POST['title'] ?? '');
            $description  = trim($_POST['description'] ?? '');
            $reminder_date = $_POST['reminder_date'] ?? '';
            $is_recurring  = isset($_POST['is_recurring']) ? 1 : 0;
            $recurrence    = $_POST['recurrence'] ?? null;

            if (empty($title)) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'El título es requerido']);
                exit;
            }

            if (empty($reminder_date)) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'La fecha es requerida']);
                exit;
            }

            $stmt = $pdo->prepare("
                INSERT INTO reminders (user_id, title, description, reminder_date, is_recurring, recurrence_interval)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$user_id, $title, $description, $reminder_date, $is_recurring, $recurrence]);
            $new_id = $pdo->lastInsertId();

            echo json_encode([
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
            ]);
            break;

        // ============================================
        // COMPLETAR RECORDATORIO
        // ============================================
        case 'complete':
            $reminder_id = $_POST['id'] ?? $_GET['id'] ?? null;

            if (!$reminder_id) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'ID de recordatorio no especificado']);
                exit;
            }

            $stmt = $pdo->prepare("UPDATE reminders SET completed = 1 WHERE id = ? AND user_id = ?");
            $stmt->execute([$reminder_id, $user_id]);

            if ($stmt->rowCount() === 0) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Recordatorio no encontrado']);
                exit;
            }

            // Devolver datos del recordatorio recién completado para moverlo al historial en el frontend
            $stmt = $pdo->prepare("SELECT * FROM reminders WHERE id = ?");
            $stmt->execute([$reminder_id]);
            $completed = $stmt->fetch(PDO::FETCH_ASSOC);

            echo json_encode([
                'success'  => true,
                'message'  => 'Recordatorio marcado como completado',
                'reminder' => $completed,
            ]);
            break;

        // ============================================
        // ELIMINAR RECORDATORIO
        // ============================================
        case 'delete':
            $reminder_id = $_POST['id'] ?? $_GET['id'] ?? null;

            if (!$reminder_id) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'ID de recordatorio no especificado']);
                exit;
            }

            $stmt = $pdo->prepare("DELETE FROM reminders WHERE id = ? AND user_id = ?");
            $stmt->execute([$reminder_id, $user_id]);

            if ($stmt->rowCount() === 0) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Recordatorio no encontrado']);
                exit;
            }

            echo json_encode([
                'success' => true,
                'message' => 'Recordatorio eliminado correctamente',
                'id'      => $reminder_id,
            ]);
            break;

        // ============================================
        // ACCIÓN DESCONOCIDA
        // ============================================
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "Acción desconocida: '$action'"]);
            break;
    }

} catch (Exception $e) {
    $errorMsg = $e->getMessage();
    error_log("[reminders.ajax] ERROR: $errorMsg");
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor',
        'debug'   => $errorMsg, // El frontend lo imprime en consola
    ]);
}