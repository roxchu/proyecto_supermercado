<?php
/**
 * API para consultar información de stock y productos con stock bajo
 */
declare(strict_types=1);
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once 'gestor_stock.php';

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
    
    $accion = $_GET['accion'] ?? 'stock_bajo';
    
    switch ($accion) {
        case 'stock_bajo':
            // Obtener productos con stock bajo
            $productosStockBajo = $gestorStock->obtenerProductosStockBajo();
            
            echo json_encode([
                'success' => true,
                'productos_stock_bajo' => $productosStockBajo,
                'total' => count($productosStockBajo)
            ]);
            break;
            
        case 'stock_producto':
            // Obtener stock de un producto específico
            $idProducto = (int)($_GET['id_producto'] ?? 0);
            
            if ($idProducto <= 0) {
                throw new Exception('ID de producto inválido');
            }
            
            $stmt = $pdo->prepare("
                SELECT 
                    p.Id_Producto,
                    p.Nombre_Producto,
                    p.Stock,
                    p.precio_actual,
                    c.Nombre_Categoria
                FROM producto p
                LEFT JOIN categoria c ON p.Id_Categoria = c.Id_Categoria
                WHERE p.Id_Producto = ?
            ");
            $stmt->execute([$idProducto]);
            $producto = $stmt->fetch();
            
            if (!$producto) {
                throw new Exception('Producto no encontrado');
            }
            
            echo json_encode([
                'success' => true,
                'producto' => $producto
            ]);
            break;
            
        case 'todos_stocks':
            // Obtener stock de todos los productos
            $stmt = $pdo->query("
                SELECT 
                    p.Id_Producto,
                    p.Nombre_Producto,
                    p.Stock,
                    p.precio_actual,
                    c.Nombre_Categoria,
                    CASE 
                        WHEN p.Stock <= 0 THEN 'agotado'
                        WHEN p.Stock <= 10 THEN 'bajo'
                        WHEN p.Stock <= 50 THEN 'medio'
                        ELSE 'alto'
                    END as nivel_stock
                FROM producto p
                LEFT JOIN categoria c ON p.Id_Categoria = c.Id_Categoria
                ORDER BY p.Stock ASC, p.Nombre_Producto
            ");
            
            $productos = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'productos' => $productos,
                'total' => count($productos)
            ]);
            break;
            
        default:
            throw new Exception('Acción no válida');
    }
    
} catch (Exception $e) {
    error_log("Error en api_stock.php: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>