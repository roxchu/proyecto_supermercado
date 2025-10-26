<?php
declare(strict_types=1);
session_start();
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/db.php';

try {
    // --- Verificar sesión ---
    $dniSesion = $_SESSION['dni'] ?? null;

    if (!$dniSesion && !empty($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("SELECT DNI FROM usuario WHERE id_usuario = ? LIMIT 1");
        $stmt->execute([(int)$_SESSION['user_id']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $dniSesion = $row['DNI'] ?? null;
    }

    if (!$dniSesion) {
        echo json_encode(['success' => false, 'msg' => 'Debes iniciar sesión para agregar productos']);
        exit;
    }

    // --- Leer datos del cuerpo ---
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $idProducto = (int)($input['id'] ?? 0);
    $cantidad = max(1, (int)($input['cantidad'] ?? 1));

    if ($idProducto <= 0) {
        echo json_encode(['success' => false, 'msg' => 'Producto inválido']);
        exit;
    }

    // --- Buscar o crear carrito pendiente (detalle_carrito) ---
    $stmt = $pdo->prepare("SELECT Id_Detalle_Carrito FROM detalle_carrito WHERE DNI_Cliente = ? AND Estado = 'Pendiente' LIMIT 1");
    $stmt->execute([$dniSesion]);
    $detalle = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$detalle) {
        $stmt = $pdo->prepare("INSERT INTO detalle_carrito (DNI_Cliente, Estado, Fecha_Agregado, Costo_Envio, Total_Final)
                               VALUES (?, 'Pendiente', NOW(), 0, 0)");
        $stmt->execute([$dniSesion]);
        $idDetalle = (int)$pdo->lastInsertId();
    } else {
        $idDetalle = (int)$detalle['Id_Detalle_Carrito'];
    }

    // --- Verificar producto ---
    $stmt = $pdo->prepare("SELECT Stock, precio_actual FROM producto WHERE Id_Producto = ? LIMIT 1");
    $stmt->execute([$idProducto]);
    $producto = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$producto) {
        echo json_encode(['success' => false, 'msg' => 'Producto no encontrado']);
        exit;
    }

    if ($cantidad > (int)$producto['Stock']) {
        echo json_encode(['success' => false, 'msg' => 'No hay suficiente stock']);
        exit;
    }

    $precio = (float)$producto['precio_actual'];
    $total = $precio * $cantidad;

    // --- Insertar o actualizar en carrito ---
    $stmt = $pdo->prepare("
        INSERT INTO carrito (Id_Detalle_Carrito, Id_Producto, Cantidad, Precio_Unitario_Momento, Total)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
          Cantidad = Cantidad + VALUES(Cantidad),
          Precio_Unitario_Momento = VALUES(Precio_Unitario_Momento),
          Total = Cantidad * Precio_Unitario_Momento
    ");
    $stmt->execute([$idDetalle, $idProducto, $cantidad, $precio, $total]);

    // --- Actualizar total del carrito ---
    $stmt = $pdo->prepare("SELECT SUM(Total) AS subtotal FROM carrito WHERE Id_Detalle_Carrito = ?");
    $stmt->execute([$idDetalle]);
    $subtotal = (float)($stmt->fetchColumn() ?? 0);

    $stmt = $pdo->prepare("UPDATE detalle_carrito SET Total_Final = ? WHERE Id_Detalle_Carrito = ?");
    $stmt->execute([$subtotal, $idDetalle]);

    echo json_encode(['success' => true, 'msg' => 'Producto agregado al carrito']);
} catch (Throwable $e) {
    error_log("agregar_carrito error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'msg' => 'Error interno al agregar al carrito']);
}
