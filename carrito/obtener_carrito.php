<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db.php';

$dniSesion = $_SESSION['dni'] ?? null;

if (!$dniSesion && !empty($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT DNI FROM usuario WHERE id_usuario = ? LIMIT 1");
    $stmt->execute([(int)$_SESSION['user_id']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $dniSesion = $row['DNI'] ?? null;
}

if (!$dniSesion) {
    echo json_encode(['carrito' => [], 'total_items' => 0, 'total_price' => 0]);
    exit;
}

// Buscar carrito pendiente
$stmt = $pdo->prepare("SELECT Id_Detalle_Carrito, Total_Final FROM detalle_carrito WHERE DNI_Cliente = ? AND Estado = 'Pendiente' LIMIT 1");
$stmt->execute([$dniSesion]);
$detalle = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$detalle) {
    echo json_encode(['carrito' => [], 'total_items' => 0, 'total_price' => 0]);
    exit;
}

$idDetalle = (int)$detalle['Id_Detalle_Carrito'];

// Obtener productos
$stmt = $pdo->prepare("
    SELECT c.Id_Producto, c.Cantidad, c.Precio_Unitario_Momento, c.Total,
           p.Nombre_Producto AS nombre, p.imagen_url
    FROM carrito c
    JOIN producto p ON p.Id_Producto = c.Id_Producto
    WHERE c.Id_Detalle_Carrito = ?
");
$stmt->execute([$idDetalle]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcular totales
$total_items = 0;
$total_price = 0.0;
foreach ($items as $it) {
    $total_items += (int)$it['Cantidad'];
    $total_price += (float)$it['Total'];
}

echo json_encode([
    'carrito' => $items,
    'total_items' => $total_items,
    'total_price' => $total_price
]);
