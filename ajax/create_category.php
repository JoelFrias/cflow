<?php
// ajax/create_category.php - Endpoint para crear una nueva categoría de ingreso o gasto

require_once '../config/database.php';
header('Content-Type: application/json');

if(!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

$user_id = $_SESSION['user_id'];
$name = trim($_POST['name'] ?? '');
$type = $_POST['type'] ?? 'expense';

if(empty($name)) {
    echo json_encode(['success' => false, 'message' => 'El nombre es requerido']);
    exit();
}

if(!in_array($type, ['income', 'expense'])) {
    echo json_encode(['success' => false, 'message' => 'Tipo inválido']);
    exit();
}

try {
    $stmt = $pdo->prepare("INSERT INTO categories (user_id, name, type) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $name, $type]);
    $category_id = $pdo->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'category_id' => $category_id,
        'name' => $name,
        'type' => $type
    ]);
} catch(Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error al crear la categoría']);
}
?>