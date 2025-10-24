<?php
// carrito/obtener_carrito.php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['total_items' => 0]);
    exit;
}

$id_usuario = $_SESSION['user_id'] ?? null;
if (!$id_usuario) {
    echo json_encode(['total_items' => 0]);
    exit;
}

$stmt = $pdo->prepare("SELECT c.Id_Carrito FROM carrito c WHERE c.DNI_Cliente = ? AND c.Estado = 'Pendiente' LIMIT 1");
$stmt->execute([$id_usuario]);
$carrito = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$carrito) {
    echo json_encode(['total_items' => 0]);
    exit;
}

$stmt = $pdo->prepare("SELECT SUM(Cantidad) as total_items FROM detalle_carrito WHERE Id_Carrito = ?");
$stmt->execute([$carrito['Id_Carrito']]);
$total = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode(['total_items' => intval($total['total_items'] ?? 0)]);
