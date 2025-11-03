<?php
/**
 * ELIMINAR ITEM DEL CARRITO - Versión actualizada para nueva estructura
 * Elimina productos del carrito usando venta_unificada y detalle_carrito
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

    // --- OBTENER DATOS DEL POST ---
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        // Fallback para form-data tradicional
        $idItemCarrito = $_POST['id_item'] ?? null;
        $idProducto = $_POST['id_producto'] ?? null;
        $accion = $_POST['accion'] ?? 'eliminar'; // 'eliminar' o 'reducir'
    } else {
        $idItemCarrito = $input['id_item'] ?? null;
        $idProducto = $input['id_producto'] ?? null;
        $accion = $input['accion'] ?? 'eliminar';
    }

    // --- BUSCAR CARRITO VIRTUAL PENDIENTE DEL USUARIO ---
    $stmtCarrito = $pdo->prepare("
        SELECT id_venta 
        FROM venta_unificada 
        WHERE id_usuario = ? 
        AND tipo_venta = 'virtual' 
        AND Estado = 'Pendiente'
        LIMIT 1
    ");
    $stmtCarrito->execute([$idUsuario]);
    $carrito = $stmtCarrito->fetch(PDO::FETCH_ASSOC);

    if (!$carrito) {
        throw new Exception('No hay carrito activo');
    }

    $idVenta = $carrito['id_venta'];

    $pdo->beginTransaction();

    if ($idItemCarrito) {
        // Eliminar por ID específico del detalle_carrito
        $stmtEliminar = $pdo->prepare("
            DELETE FROM detalle_carrito 
            WHERE Id_Detalle_Carrito = ? 
            AND Id_Venta = ?
        ");
        $resultado = $stmtEliminar->execute([$idItemCarrito, $idVenta]);
        
        if ($stmtEliminar->rowCount() === 0) {
            throw new Exception('Item no encontrado en el carrito');
        }
        
        $mensaje = "Item eliminado del carrito";
        
    } elseif ($idProducto) {
        if ($accion === 'reducir') {
            // Reducir cantidad en 1
            $stmtCheck = $pdo->prepare("
                SELECT Id_Detalle_Carrito, Cantidad 
                FROM detalle_carrito 
                WHERE Id_Venta = ? AND Id_Producto = ?
            ");
            $stmtCheck->execute([$idVenta, $idProducto]);
            $item = $stmtCheck->fetch(PDO::FETCH_ASSOC);
            
            if (!$item) {
                throw new Exception('Producto no encontrado en el carrito');
            }
            
            if ($item['Cantidad'] <= 1) {
                // Si cantidad es 1 o menos, eliminar completamente
                $stmtEliminar = $pdo->prepare("
                    DELETE FROM detalle_carrito 
                    WHERE Id_Detalle_Carrito = ?
                ");
                $stmtEliminar->execute([$item['Id_Detalle_Carrito']]);
                $mensaje = "Producto eliminado del carrito";
            } else {
                // Reducir cantidad
                $stmtReducir = $pdo->prepare("
                    UPDATE detalle_carrito 
                    SET Cantidad = Cantidad - 1 
                    WHERE Id_Detalle_Carrito = ?
                ");
                $stmtReducir->execute([$item['Id_Detalle_Carrito']]);
                $mensaje = "Cantidad reducida";
            }
        } else {
            // Eliminar todas las unidades del producto
            $stmtEliminar = $pdo->prepare("
                DELETE FROM detalle_carrito 
                WHERE Id_Venta = ? AND Id_Producto = ?
            ");
            $resultado = $stmtEliminar->execute([$idVenta, $idProducto]);
            
            if ($stmtEliminar->rowCount() === 0) {
                throw new Exception('Producto no encontrado en el carrito');
            }
            
            $mensaje = "Producto eliminado del carrito";
        }
    } else {
        throw new Exception('ID de eliminación no válido');
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

    // --- VERIFICAR SI EL CARRITO QUEDÓ VACÍO ---
    $stmtCount = $pdo->prepare("
        SELECT COUNT(*) as items_count,
               COALESCE(SUM(Total), 0) as total_carrito
        FROM detalle_carrito 
        WHERE Id_Venta = ?
    ");
    $stmtCount->execute([$idVenta]);
    $totales = $stmtCount->fetch(PDO::FETCH_ASSOC);

    // Si el carrito quedó vacío, opcionalmente podemos eliminarlo
    if ($totales['items_count'] == 0) {
        $stmtEliminarCarrito = $pdo->prepare("
            DELETE FROM venta_unificada 
            WHERE id_venta = ? AND tipo_venta = 'virtual' AND Estado = 'Pendiente'
        ");
        $stmtEliminarCarrito->execute([$idVenta]);
        $mensaje .= " - Carrito vacío eliminado";
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => $mensaje,
        'items_en_carrito' => (int)$totales['items_count'],
        'total_carrito' => (float)$totales['total_carrito']
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