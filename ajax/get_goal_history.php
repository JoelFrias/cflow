<?php
// ajax/get_goal_history.php - Devuelve el historial de abonos para una meta específica

require_once '../config/database.php';
header('Content-Type: application/json');

if(!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

$user_id = $_SESSION['user_id'];
$goal_id = $_GET['goal_id'] ?? 0;

if(!$goal_id) {
    echo json_encode(['success' => false, 'message' => 'ID de meta no válido']);
    exit();
}

// Verificar que la meta pertenece al usuario
$stmt = $pdo->prepare("SELECT id FROM savings_goals WHERE id = ? AND user_id = ?");
$stmt->execute([$goal_id, $user_id]);
if(!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Meta no encontrada']);
    exit();
}

// Obtener historial de abonos
$stmt = $pdo->prepare("SELECT * FROM savings_contributions WHERE goal_id = ? ORDER BY contribution_date DESC");
$stmt->execute([$goal_id]);
$contributions = $stmt->fetchAll();

echo json_encode([
    'success' => true,
    'data' => $contributions
]);
?>