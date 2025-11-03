<?php
/**
 * AGREGAR CARRITO - Versión actualizada para nueva estructura
 * Agrega productos al carrito usando venta_unificada y detalle_carrito
 */

header('Content-Type: application/json');

// --- VERIFICAR SESSIÓN ---
session_start();
$idUsuario = $_SESSION['user_id'] ?? $_SESSION['id_usuario'] ?? null;

if (!$idUsuario) {
    echo json_encode([
        'success' => false,
        'message' => 'Usuario no autenticado'
    ]);
    exit;
}

try {
    require_once __DIR__ . '/db.php';

    // --- OBTENER Y VALIDAR DATOS DEL POST ---
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        // Fallback para form-data tradicional
        $idProducto = (int)($_POST['id_producto'] ?? 0);
        $cantidad = (int)($_POST['cantidad'] ?? 1);
    } else {
        $idProducto = (int)($input['id_producto'] ?? 0);
        $cantidad = (int)($input['cantidad'] ?? 1);
    }

    // Validaciones básicas
    if ($idProducto <= 0) {
        throw new Exception('ID de producto inválido');
    }

    if ($cantidad <= 0) {
        $cantidad = 1;
    }

    // --- VERIFICAR QUE EL PRODUCTO EXISTE Y OBTENER PRECIO ---
    $stmtProd = $pdo->prepare("
        SELECT Id_Producto, Nombre_Producto, precio_actual, Stock 
        FROM producto 
        WHERE Id_Producto = ?
    ");
    $stmtProd->execute([$idProducto]);
    $producto = $stmtProd->fetch(PDO::FETCH_ASSOC);

    if (!$producto) {
        throw new Exception('Producto no encontrado');
    }

    if ($producto['Stock'] < $cantidad) {
        throw new Exception('Stock insuficiente. Disponible: ' . $producto['Stock']);
    }

    $precioUnitario = (float)$producto['precio_actual'];

    $pdo->beginTransaction();

    // --- BUSCAR O CREAR CARRITO VIRTUAL PENDIENTE ---
    $stmtCarrito = $pdo->prepare("
        SELECT id_venta 
        FROM venta_unificada 
        WHERE id_usuario = ? 
        AND tipo_venta = 'virtual' 
        AND Estado = 'Pendiente'
        LIMIT 1
    ");
    $stmtCarrito->execute([$idUsuario]);
    $carritoExistente = $stmtCarrito->fetch(PDO::FETCH_ASSOC);

    if ($carritoExistente) {
        $idVenta = $carritoExistente['id_venta'];
    } else {
        // Crear nuevo carrito virtual
        $stmtNuevoCarrito = $pdo->prepare("
            INSERT INTO venta_unificada 
            (id_usuario, tipo_venta, Estado, Total_Venta) 
            VALUES (?, 'virtual', 'Pendiente', 0.00)
        ");
        $stmtNuevoCarrito->execute([$idUsuario]);
        $idVenta = $pdo->lastInsertId();
    }

    // --- VERIFICAR SI EL PRODUCTO YA ESTÁ EN EL CARRITO ---
    $stmtCheck = $pdo->prepare("
        SELECT Id_Detalle_Carrito, Cantidad
        FROM detalle_carrito
        WHERE Id_Venta = ? AND Id_Producto = ?
    ");
    $stmtCheck->execute([$idVenta, $idProducto]);
    $itemExistente = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if ($itemExistente) {
        // Actualizar cantidad existente
        $nuevaCantidad = $itemExistente['Cantidad'] + $cantidad;
        
        // Verificar stock para nueva cantidad
        if ($producto['Stock'] < $nuevaCantidad) {
            throw new Exception('Stock insuficiente para la cantidad solicitada. Disponible: ' . $producto['Stock'] . ', en carrito: ' . $itemExistente['Cantidad']);
        }

        $stmtUpdate = $pdo->prepare("
            UPDATE detalle_carrito 
            SET Cantidad = ?, Precio_Unitario_Momento = ?
            WHERE Id_Detalle_Carrito = ?
        ");
        $stmtUpdate->execute([$nuevaCantidad, $precioUnitario, $itemExistente['Id_Detalle_Carrito']]);
        
        $mensaje = "Cantidad actualizada en el carrito";
        
    } else {
        // Insertar nuevo item en el carrito
        $stmtInsert = $pdo->prepare("
            INSERT INTO detalle_carrito 
            (Id_Venta, Id_Producto, Precio_Unitario_Momento, Cantidad) 
            VALUES (?, ?, ?, ?)
        ");
        $stmtInsert->execute([$idVenta, $idProducto, $precioUnitario, $cantidad]);
        
        $mensaje = "Producto agregado al carrito";
    }

    // --- ACTUALIZAR TOTAL DEL CARRITO ---
    $stmtTotal = $pdo->prepare("
        UPDATE venta_unificada 
        SET Total_Venta = (
            SELECT COALESCE(SUM(Total), 0) 
            FROM detalle_carrito 
            WHERE Id_Venta = ?
        )
        WHERE id_venta = ?
    ");
    $stmtTotal->execute([$idVenta, $idVenta]);

    // --- OBTENER TOTAL ACTUALIZADO ---
    $stmtGetTotal = $pdo->prepare("
        SELECT Total_Venta, 
               (SELECT COUNT(*) FROM detalle_carrito WHERE Id_Venta = ?) as items_count
        FROM venta_unificada 
        WHERE id_venta = ?
    ");
    $stmtGetTotal->execute([$idVenta, $idVenta]);
    $totales = $stmtGetTotal->fetch(PDO::FETCH_ASSOC);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => $mensaje,
        'producto' => $producto['Nombre_Producto'],
        'cantidad_agregada' => $cantidad,
        'total_carrito' => (float)$totales['Total_Venta'],
        'items_en_carrito' => (int)$totales['items_count'],
        'id_venta' => $idVenta
    ]);

} catch (Exception $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>