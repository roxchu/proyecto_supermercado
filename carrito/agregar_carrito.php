<?php
// carrito/agregar_carrito.php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db.php'; // conexión local en carpeta carrito

// Verificar login (usa la sesión generada por login/login.php)
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'No has iniciado sesión']);
    exit;
}

// Leer datos del fetch JSON o form
$payload = json_decode(file_get_contents('php://input'), true);
if (!is_array($payload)) $payload = $_POST;
$id_producto = intval($payload['id_producto'] ?? $payload['id'] ?? 0);
$cantidad = max(1, intval($payload['cantidad'] ?? 1));
$id_usuario = $_SESSION['user_id'] ?? null;
if (!$id_usuario) {
    echo json_encode(['success' => false, 'message' => 'Usuario no identificado en sesión']);
    exit;
}

// Validar producto (aceptamos distintos nombres de columna para compatibilidad)
$stmt = $pdo->prepare("SELECT * FROM producto WHERE Id_Producto = ? LIMIT 1");
$stmt->execute([$id_producto]);
$producto = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$producto) {
    echo json_encode(['success' => false, 'message' => 'Producto no encontrado']);
    exit;
}

// Determinar precio del producto (compatibilidad con distintas columnas)
$precio = isset($producto['precio_actual']) ? $producto['precio_actual'] : (isset($producto['Precio']) ? $producto['Precio'] : (isset($producto['Precio_Unitario']) ? $producto['Precio_Unitario'] : 0));
if ($precio <= 0) $precio = 0.0;

// Crear carrito pendiente si no existe (por usuario)
$stmt = $pdo->prepare("SELECT Id_Carrito FROM carrito WHERE DNI_Cliente = ? AND Estado = 'Pendiente' LIMIT 1");
$stmt->execute([$id_usuario]);
$carrito = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$carrito) {
    // Nota: la columna en tu SQL se llama DNI_Cliente y Estado (Pendiente)
    $stmt = $pdo->prepare("INSERT INTO carrito (DNI_Cliente, Estado, Fecha_Agregado) VALUES (?, 'Pendiente', NOW())");
    $stmt->execute([$id_usuario]);
    $id_carrito = $pdo->lastInsertId();
} else {
    $id_carrito = $carrito['Id_Carrito'];
}

// Verificar si el producto ya está en el carrito
$stmt = $pdo->prepare("SELECT * FROM detalle_carrito WHERE Id_Carrito = ? AND Id_Producto = ?");
$stmt->execute([$id_carrito, $id_producto]);
$detalle = $stmt->fetch(PDO::FETCH_ASSOC);

if ($detalle) {
    // Aumentar cantidad
    $stmt = $pdo->prepare("UPDATE detalle_carrito SET Cantidad = Cantidad + ? WHERE Id_Carrito = ? AND Id_Producto = ?");
    $stmt->execute([$cantidad, $id_carrito, $id_producto]);
} else {
    // Insertar nuevo detalle (tu columna de precio se llama Precio_Unitario_Momento)
    $stmt = $pdo->prepare("INSERT INTO detalle_carrito (Id_Carrito, Id_Producto, Cantidad, Precio_Unitario_Momento) VALUES (?, ?, ?, ?)");
    $stmt->execute([$id_carrito, $id_producto, $cantidad, $precio]);
}

echo json_encode(['success' => true, 'message' => 'Producto agregado al carrito']);
