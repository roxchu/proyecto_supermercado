<?php
// actualizar_carrito.php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

require_once 'db.php';

$id_opcion = isset($_POST['id_opcion']) ? intval($_POST['id_opcion']) : 0;
$cantidad = isset($_POST['cantidad']) ? intval($_POST['cantidad']) : 0;

if ($id_opcion <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID inválido.']);
    exit;
}

// si se pide eliminar (cantidad 0) -> borrar
if ($cantidad < 0) $cantidad = 0;

// obtener stock actual
$stmt = $pdo->prepare("SELECT Stock FROM opcion_producto WHERE Id_Opcion_Producto = ? LIMIT 1");
$stmt->execute([$id_opcion]);
$row = $stmt->fetch();

if (!$row) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Opción de producto no existe.']);
    exit;
}

$stock = intval($row['Stock']);
if ($cantidad > $stock) {
    http_response_code(409);
    echo json_encode(['success' => false, 'message' => 'La cantidad excede el stock disponible.']);
    exit;
}

if (!isset($_SESSION['carrito'])) $_SESSION['carrito'] = [];

if ($cantidad === 0) {
    if (isset($_SESSION['carrito'][$id_opcion])) {
        unset($_SESSION['carrito'][$id_opcion]);
    }
} else {
    // si no existe en carrito, traer datos mínimos para armarlo
    if (!isset($_SESSION['carrito'][$id_opcion])) {
        // traer datos
        $stmt2 = $pdo->prepare("
            SELECT op.Id_Opcion_Producto, op.Id_Producto, op.Nombre_Opcion, op.Precio_Unitario, p.Nombre_Producto
            FROM opcion_producto op
            JOIN producto p ON op.Id_Producto = p.Id_Producto
            WHERE op.Id_Opcion_Producto = ?
            LIMIT 1
        ");
        $stmt2->execute([$id_opcion]);
        $op = $stmt2->fetch();
        if (!$op) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Opción no encontrada al actualizar.']);
            exit;
        }
        $_SESSION['carrito'][$id_opcion] = [
            'Id_Opcion_Producto' => (int)$op['Id_Opcion_Producto'],
            'Id_Producto' => (int)$op['Id_Producto'],
            'Nombre_Producto' => $op['Nombre_Producto'],
            'Nombre_Opcion' => $op['Nombre_Opcion'],
            'Precio_Unitario' => (float)$op['Precio_Unitario'],
            'cantidad' => $cantidad
        ];
    } else {
        $_SESSION['carrito'][$id_opcion]['cantidad'] = $cantidad;
    }
}

// responder resumen
$total_items = 0; $total_price = 0;
foreach ($_SESSION['carrito'] as $it) {
    $total_items += $it['cantidad'];
    $total_price += $it['Precio_Unitario'] * $it['cantidad'];
}

echo json_encode([
    'success' => true,
    'message' => 'Carrito actualizado.',
    'cart_summary' => ['items' => $total_items, 'total' => $total_price],
    'cart' => $_SESSION['carrito']
]);
