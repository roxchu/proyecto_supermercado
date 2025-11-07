<?php
/**
 * Script para renovar stock manualmente
 * Solo accesible para empleados y administradores
 */
declare(strict_types=1);
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once 'gestor_stock.php';
require_once '../login/verificar_rol.php';

// Verificar que el usuario sea empleado o administrador
if (!verificarRol(['empleado', 'administrador'])) {
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
    $gestorStock = new GestorStock($pdo);
    
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
            
            if ($idProducto <= 0 || $nuevaCantidad <= 0) {
                throw new Exception('ID de producto o cantidad inválidos');
            }
            
            // Verificar que el producto existe
            $stmt = $pdo->prepare("SELECT Nombre_Producto FROM producto WHERE Id_Producto = ?");
            $stmt->execute([$idProducto]);
            $producto = $stmt->fetch();
            
            if (!$producto) {
                throw new Exception('Producto no encontrado');
            }
            
            // Actualizar stock
            $stmt = $pdo->prepare("UPDATE producto SET Stock = ? WHERE Id_Producto = ?");
            $stmt->execute([$nuevaCantidad, $idProducto]);
            
            // Log de la acción
            error_log(sprintf(
                "[%s] Stock renovado manualmente por usuario %d - Producto ID: %d (%s) - Nuevo stock: %d",
                date('Y-m-d H:i:s'),
                $_SESSION['user_id'],
                $idProducto,
                $producto['Nombre_Producto'],
                $nuevaCantidad
            ));
            
            echo json_encode([
                'success' => true,
                'message' => "Stock actualizado correctamente para {$producto['Nombre_Producto']}",
                'nuevo_stock' => $nuevaCantidad
            ]);
            break;
            
        case 'renovar_categoria':
            $idCategoria = (int)($input['id_categoria'] ?? 0);
            $cantidad = (int)($input['cantidad'] ?? 0);
            
            if ($idCategoria <= 0 || $cantidad <= 0) {
                throw new Exception('ID de categoría o cantidad inválidos');
            }
            
            // Obtener productos de la categoría con stock bajo
            $stmt = $pdo->prepare("
                SELECT Id_Producto, Nombre_Producto 
                FROM producto 
                WHERE Id_Categoria = ? AND Stock <= 10
            ");
            $stmt->execute([$idCategoria]);
            $productos = $stmt->fetchAll();
            
            if (empty($productos)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'No hay productos con stock bajo en esta categoría',
                    'productos_actualizados' => 0
                ]);
                break;
            }
            
            // Actualizar stock de todos los productos
            $pdo->beginTransaction();
            
            try {
                $stmt = $pdo->prepare("UPDATE producto SET Stock = ? WHERE Id_Producto = ?");
                
                foreach ($productos as $producto) {
                    $stmt->execute([$cantidad, $producto['Id_Producto']]);
                }
                
                $pdo->commit();
                
                // Log de la acción
                error_log(sprintf(
                    "[%s] Stock renovado por categoría por usuario %d - Categoría ID: %d - %d productos actualizados a %d unidades",
                    date('Y-m-d H:i:s'),
                    $_SESSION['user_id'],
                    $idCategoria,
                    count($productos),
                    $cantidad
                ));
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Stock actualizado correctamente para ' . count($productos) . ' productos',
                    'productos_actualizados' => count($productos),
                    'nuevo_stock' => $cantidad
                ]);
                
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
            break;
            
        case 'configurar_renovacion':
            $idCategoria = (int)($input['id_categoria'] ?? 0);
            $cantidadRenovacion = (int)($input['cantidad_renovacion'] ?? 0);
            
            if ($idCategoria <= 0 || $cantidadRenovacion <= 0) {
                throw new Exception('Datos inválidos para configuración');
            }
            
            $gestorStock->configurarRenovacionCategoria($idCategoria, $cantidadRenovacion);
            
            echo json_encode([
                'success' => true,
                'message' => 'Configuración de renovación actualizada'
            ]);
            break;
            
        default:
            throw new Exception('Acción no válida');
    }
    
} catch (Exception $e) {
    error_log("Error en renovar_stock.php: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>