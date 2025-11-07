<?php
/**
 * Script para que empleados/administradores renueven stock
 * Solo permite aumentar stock, NO renovación automática
 */
declare(strict_types=1);
session_start();
header('Content-Type: application/json; charset=utf-8');

// Verificar permisos de empleado/administrador
if (!isset($_SESSION['user_id']) || !isset($_SESSION['rol'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Debe iniciar sesión como empleado o administrador'
    ]);
    exit;
}

$rolesPermitidos = ['empleado', 'administrador'];
if (!in_array($_SESSION['rol'], $rolesPermitidos)) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'No tiene permisos para realizar esta acción'
    ]);
    exit;
}

// Configuración de la base de datos
$host = 'localhost';
$db   = 'supermercado';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE               => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE    => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES      => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    
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
            $stmt = $pdo->prepare("SELECT Nombre_Producto, Stock FROM producto WHERE Id_Producto = ?");
            $stmt->execute([$idProducto]);
            $producto = $stmt->fetch();
            
            if (!$producto) {
                throw new Exception('Producto no encontrado');
            }
            
            $stockAnterior = (int)$producto['Stock'];
            
            // Actualizar stock
            $stmt = $pdo->prepare("UPDATE producto SET Stock = ? WHERE Id_Producto = ?");
            $stmt->execute([$nuevaCantidad, $idProducto]);
            
            // Log de la acción
            error_log(sprintf(
                "[RENOVACION STOCK] Usuario: %d (%s) - Producto ID: %d (%s) - Stock anterior: %d - Stock nuevo: %d - Fecha: %s",
                $_SESSION['user_id'],
                $_SESSION['rol'],
                $idProducto,
                $producto['Nombre_Producto'],
                $stockAnterior,
                $nuevaCantidad,
                date('Y-m-d H:i:s')
            ));
            
            echo json_encode([
                'success' => true,
                'message' => "Stock actualizado para {$producto['Nombre_Producto']}",
                'producto' => $producto['Nombre_Producto'],
                'stock_anterior' => $stockAnterior,
                'stock_nuevo' => $nuevaCantidad,
                'fecha' => date('Y-m-d H:i:s')
            ]);
            break;
            
        case 'renovar_multiples':
            // Renovar múltiples productos a la vez
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
                        continue; // Saltar productos inválidos
                    }
                    
                    // Obtener información del producto
                    $stmt = $pdo->prepare("SELECT Nombre_Producto, Stock FROM producto WHERE Id_Producto = ?");
                    $stmt->execute([$idProducto]);
                    $producto = $stmt->fetch();
                    
                    if (!$producto) {
                        continue; // Saltar productos no encontrados
                    }
                    
                    $stockAnterior = (int)$producto['Stock'];
                    
                    // Actualizar stock
                    $stmt = $pdo->prepare("UPDATE producto SET Stock = ? WHERE Id_Producto = ?");
                    $stmt->execute([$nuevaCantidad, $idProducto]);
                    
                    $productosActualizados[] = [
                        'id' => $idProducto,
                        'nombre' => $producto['Nombre_Producto'],
                        'stock_anterior' => $stockAnterior,
                        'stock_nuevo' => $nuevaCantidad
                    ];
                    
                    // Log individual
                    error_log(sprintf(
                        "[RENOVACION MULTIPLE] Usuario: %d (%s) - Producto ID: %d (%s) - Stock: %d -> %d",
                        $_SESSION['user_id'],
                        $_SESSION['rol'],
                        $idProducto,
                        $producto['Nombre_Producto'],
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