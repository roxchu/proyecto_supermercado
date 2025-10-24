<?php
// actualizar_carrito.php (actualiza cantidad en DB)
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión.']);
    exit;
}

require_once __DIR__ . '/db.php';

$payload = json_decode(file_get_contents('php://input'), true);
if (!is_array($payload)) $payload = $_POST;

$id_producto = isset($payload['id_producto']) ? intval($payload['id_producto']) : 0;
$cantidad = isset($payload['cantidad']) ? intval($payload['cantidad']) : 0;

if ($id_producto <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de producto inválido.']);
    exit;
}

if ($cantidad < 0) $cantidad = 0;

$id_usuario = $_SESSION['user_id'] ?? null;
if (!$id_usuario) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Usuario no identificado.']);
    exit;
}

// obtener carrito pendiente (usar columna DNI_Cliente y Estado 'Pendiente')
$stmt = $pdo->prepare("SELECT Id_Carrito FROM carrito WHERE DNI_Cliente = ? AND Estado = 'Pendiente' LIMIT 1");
$stmt->execute([$id_usuario]);
$car = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$car) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'No existe carrito pendiente.']);
    exit;
}

$id_carrito = $car['Id_Carrito'];

// comprobar stock si existe
$stmt = $pdo->prepare("SELECT Stock FROM producto WHERE Id_Producto = ? LIMIT 1");
$stmt->execute([$id_producto]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if ($row && isset($row['Stock']) && $cantidad > intval($row['Stock'])) {
    http_response_code(409);
    echo json_encode(['success' => false, 'message' => 'La cantidad excede el stock disponible.']);
    exit;
}

if ($cantidad === 0) {
    $stmt = $pdo->prepare("DELETE FROM detalle_carrito WHERE Id_Carrito = ? AND Id_Producto = ?");
    $stmt->execute([$id_carrito, $id_producto]);
} else {
    // actualizar cantidad si existe, sino insertar
    $stmt = $pdo->prepare("SELECT * FROM detalle_carrito WHERE Id_Carrito = ? AND Id_Producto = ? LIMIT 1");
    $stmt->execute([$id_carrito, $id_producto]);
    $det = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($det) {
        $stmt = $pdo->prepare("UPDATE detalle_carrito SET Cantidad = ? WHERE Id_Carrito = ? AND Id_Producto = ?");
        $stmt->execute([$cantidad, $id_carrito, $id_producto]);
    } else {
        // obtener precio actual del producto
        $stmtP = $pdo->prepare("SELECT COALESCE(precio_actual, 0) AS precio FROM producto WHERE Id_Producto = ? LIMIT 1");
        $stmtP->execute([$id_producto]);
        $p = $stmtP->fetch(PDO::FETCH_ASSOC);
        $precio = $p['precio'] ?? 0;
        $stmt = $pdo->prepare("INSERT INTO detalle_carrito (Id_Carrito, Id_Producto, Cantidad, Precio_Unitario_Momento) VALUES (?, ?, ?, ?)");
        $stmt->execute([$id_carrito, $id_producto, $cantidad, $precio]);
    }
}

// responder resumen
$stmt = $pdo->prepare("SELECT SUM(dc.Cantidad) AS items, SUM(dc.Cantidad * dc.Precio_Unitario) AS total
                       FROM detalle_carrito dc WHERE dc.Id_Carrito = ?");
$stmt->execute([$id_carrito]);
$sum = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'cart_summary' => [
        'items' => intval($sum['items'] ?? 0),
        'total' => floatval($sum['total'] ?? 0)
    ]
]);

