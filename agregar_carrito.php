<?php
// agregar_carrito.php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión para usar el carrito.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

require_once 'db.php';

$id_opcion = isset($_POST['id_opcion']) ? intval($_POST['id_opcion']) : 0;
$cantidad = isset($_POST['cantidad']) ? intval($_POST['cantidad']) : 1;
if ($id_opcion <= 0 || $cantidad <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Datos inválidos.']);
    exit;
}

// Obtener info de la opción (precio, stock, nombre, producto)
$stmt = $pdo->prepare("
    SELECT op.Id_Opcion_Producto, op.Id_Producto, op.Nombre_Opcion, op.Precio_Unitario, op.Stock AS stock_opcion,
           p.Nombre_Producto
    FROM opcion_producto op
    JOIN producto p ON op.Id_Producto = p.Id_Producto
    WHERE op.Id_Opcion_Producto = ?
    LIMIT 1
");
$stmt->execute([$id_opcion]);
$op = $stmt->fetch();

if (!$op) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Opción de producto no encontrada.']);
    exit;
}

if ($cantidad > intval($op['stock_opcion'])) {
    http_response_code(409);
    echo json_encode(['success' => false, 'message' => 'No hay suficiente stock disponible.']);
    exit;
}

// Inicializar carrito en sesión si no existe
if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

// clave por Id_Opcion_Producto
$key = $op['Id_Opcion_Producto'];

if (isset($_SESSION['carrito'][$key])) {
    // sumar cantidad, respetando stock
    $nuevaCant = $_SESSION['carrito'][$key]['cantidad'] + $cantidad;
    if ($nuevaCant > intval($op['stock_opcion'])) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Cantidad solicitada excede stock disponible.']);
        exit;
    }
    $_SESSION['carrito'][$key]['cantidad'] = $nuevaCant;
} else {
    $_SESSION['carrito'][$key] = [
        'Id_Opcion_Producto' => (int)$op['Id_Opcion_Producto'],
        'Id_Producto' => (int)$op['Id_Producto'],
        'Nombre_Producto' => $op['Nombre_Producto'],
        'Nombre_Opcion' => $op['Nombre_Opcion'],
        'Precio_Unitario' => (float)$op['Precio_Unitario'],
        'cantidad' => $cantidad
    ];
}

// Retornar carrito resumido
$total_items = 0;
$total_price = 0;
foreach ($_SESSION['carrito'] as $it) {
    $total_items += $it['cantidad'];
    $total_price += $it['Precio_Unitario'] * $it['cantidad'];
}

echo json_encode([
    'success' => true,
    'message' => 'Producto agregado al carrito.',
    'cart_summary' => [
        'items' => $total_items,
        'total' => $total_price
    ],
    'cart' => $_SESSION['carrito']
]);
