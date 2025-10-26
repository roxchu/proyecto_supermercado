<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/db.php';

$dniSesion = $_SESSION['dni'] ?? null;

if (!$dniSesion && !empty($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT DNI FROM usuario WHERE id_usuario = ? LIMIT 1");
    $stmt->execute([(int)$_SESSION['user_id']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $dniSesion = $row['DNI'] ?? null;
}

if (!$dniSesion) {
    echo json_encode(['success' => false, 'msg' => 'Debes iniciar sesión']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$idProducto = (int)($data['id'] ?? 0);
$accion = $data['accion'] ?? '';

if ($idProducto <= 0 || !in_array($accion, ['sumar', 'restar'])) {
    echo json_encode(['success' => false, 'msg' => 'Datos inválidos']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT c.Cantidad, dc.Id_Detalle_Carrito
        FROM carrito c
        JOIN detalle_carrito dc ON c.Id_Detalle_Carrito = dc.Id_Detalle_Carrito
        WHERE dc.DNI_Cliente = ? AND dc.Estado = 'Pendiente' AND c.Id_Producto = ?
    ");
    $stmt->execute([$dniSesion, $idProducto]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode(['success' => false, 'msg' => 'Producto no encontrado']);
        exit;
    }

    $cantidad = (int)$row['Cantidad'];
    $idDetalle = (int)$row['Id_Detalle_Carrito'];

    if ($accion === 'sumar') $cantidad++;
    if ($accion === 'restar' && $cantidad > 1) $cantidad--;

    $stmt = $pdo->prepare("UPDATE carrito SET Cantidad = ?, Total = Cantidad * Precio_Unitario_Momento WHERE Id_Detalle_Carrito = ? AND Id_Producto = ?");
    $stmt->execute([$cantidad, $idDetalle, $idProducto]);

    // Actualizar total general
    $stmt = $pdo->prepare("UPDATE detalle_carrito SET Total_Final = (SELECT SUM(Total) FROM carrito WHERE Id_Detalle_Carrito = ?) WHERE Id_Detalle_Carrito = ?");
    $stmt->execute([$idDetalle, $idDetalle]);

    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'msg' => 'Error interno: ' . $e->getMessage()]);
}
