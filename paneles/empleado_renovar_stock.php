<?php
/**
 * Script para que empleados/administradores renueven stock
 * Solo permite aumentar stock, NO renovación automática
 */
declare(strict_types=1);
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../carrito/db.php';
require_once __DIR__ . '/../login/verificar_rol.php';

// Verificar permisos
try {
    verificar_rol(['admin', 'empleado']);
} catch (Exception $e) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

try {
    // Obtener datos del request
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Datos inválidos');
    }
    
    $accion = $input['accion'] ?? '';
    
    switch ($accion) {
        case 'renovar_producto':
            $idProducto = (int)($input['id_producto'] ?? 0);
            $nuevaCantidad = (int)($input['cantidad'] ?? 0);
            
            if ($idProducto <= 0 || $nuevaCantidad < 0) {
                throw new Exception('ID de producto o cantidad inválidos');
            }
            
            // Verificar que el producto existe y obtener stock actual
            $stmt = $pdo->prepare("SELECT nombre_producto, stock FROM producto WHERE id_producto = :id");
            $stmt->execute([':id' => $idProducto]);
            $producto = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$producto) {
                throw new Exception('Producto no encontrado');
            }
            
            $stockAnterior = (int)$producto['stock'];
            
            // Actualizar stock
            $stmt = $pdo->prepare("UPDATE producto SET stock = :stock WHERE id_producto = :id");
            $stmt->execute([
                ':stock' => $nuevaCantidad,
                ':id' => $idProducto
            ]);
            
            // Log de la acción
            error_log(sprintf(
                "[RENOVACION STOCK] Usuario: %d - Producto ID: %d (%s) - Stock anterior: %d - Stock nuevo: %d",
                $_SESSION['id_usuario'] ?? 'DESCONOCIDO',
                $idProducto,
                $producto['nombre_producto'],
                $stockAnterior,
                $nuevaCantidad
            ));
            
            echo json_encode([
                'success' => true,
                'message' => "Stock actualizado para {$producto['nombre_producto']}",
                'producto' => $producto['nombre_producto'],
                'stock_anterior' => $stockAnterior,
                'stock_nuevo' => $nuevaCantidad,
                'fecha' => date('Y-m-d H:i:s')
            ]);
            break;
            
        case 'renovar_multiples':
            $productos = $input['productos'] ?? [];
            
            if (empty($productos) || !is_array($productos)) {
                throw new Exception('Lista de productos inválida');
            }
            
            $pdo->beginTransaction();
            $productosActualizados = [];
            
            try {
                foreach ($productos as $item) {
                    $idProducto = (int)($item['id_producto'] ?? 0);
                    $nuevaCantidad = (int)($item['cantidad'] ?? 0);
                    
                    if ($idProducto <= 0 || $nuevaCantidad < 0) {
                        continue;
                    }
                    
                    // Obtener información del producto
                    $stmt = $pdo->prepare("SELECT nombre_producto, stock FROM producto WHERE id_producto = :id");
                    $stmt->execute([':id' => $idProducto]);
                    $producto = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$producto) {
                        continue;
                    }
                    
                    $stockAnterior = (int)$producto['stock'];
                    
                    // Actualizar stock
                    $stmt = $pdo->prepare("UPDATE producto SET stock = :stock WHERE id_producto = :id");
                    $stmt->execute([
                        ':stock' => $nuevaCantidad,
                        ':id' => $idProducto
                    ]);
                    
                    $productosActualizados[] = [
                        'id' => $idProducto,
                        'nombre' => $producto['nombre_producto'],
                        'stock_anterior' => $stockAnterior,
                        'stock_nuevo' => $nuevaCantidad
                    ];
                    
                    error_log(sprintf(
                        "[RENOVACION MULTIPLE] Producto ID: %d (%s) - Stock: %d -> %d",
                        $idProducto,
                        $producto['nombre_producto'],
                        $stockAnterior,
                        $nuevaCantidad
                    ));
                }
                
                $pdo->commit();
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Stock actualizado para ' . count($productosActualizados) . ' productos',
                    'productos_actualizados' => $productosActualizados,
                    'total' => count($productosActualizados),
                    'fecha' => date('Y-m-d H:i:s')
                ]);
                
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
            break;
            
        default:
            throw new Exception('Acción no válida');
    }
    
} catch (Exception $e) {
    error_log("Error en empleado_renovar_stock.php: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>