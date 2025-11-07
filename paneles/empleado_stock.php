<?php
/**
 * API para empleados - Productos con stock 0
 * Solo empleados y administradores pueden acceder
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
        'message' => 'No tiene permisos para acceder a esta información'
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
    
    $accion = $_GET['accion'] ?? 'productos_sin_stock';
    
    switch ($accion) {
        case 'productos_sin_stock':
            // Obtener productos con stock 0
            $stmt = $pdo->query("
                SELECT 
                    p.Id_Producto,
                    p.Nombre_Producto,
                    p.Stock,
                    p.precio_actual,
                    c.Nombre_Categoria,
                    pi.url_imagen
                FROM producto p
                LEFT JOIN categoria c ON p.Id_Categoria = c.Id_Categoria
                LEFT JOIN producto_imagenes pi ON p.Id_Producto = pi.Id_Producto AND pi.orden = 1
                WHERE p.Stock <= 0
                ORDER BY c.Nombre_Categoria, p.Nombre_Producto
            ");
            
            $productos = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'productos_sin_stock' => $productos,
                'total' => count($productos),
                'fecha_consulta' => date('Y-m-d H:i:s')
            ]);
            break;
            
        case 'productos_stock_bajo':
            // Obtener productos con stock bajo (1-5 unidades)
            $stmt = $pdo->query("
                SELECT 
                    p.Id_Producto,
                    p.Nombre_Producto,
                    p.Stock,
                    p.precio_actual,
                    c.Nombre_Categoria,
                    pi.url_imagen
                FROM producto p
                LEFT JOIN categoria c ON p.Id_Categoria = c.Id_Categoria
                LEFT JOIN producto_imagenes pi ON p.Id_Producto = pi.Id_Producto AND pi.orden = 1
                WHERE p.Stock > 0 AND p.Stock <= 5
                ORDER BY p.Stock ASC, c.Nombre_Categoria, p.Nombre_Producto
            ");
            
            $productos = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'productos_stock_bajo' => $productos,
                'total' => count($productos),
                'fecha_consulta' => date('Y-m-d H:i:s')
            ]);
            break;
            
        case 'resumen_stock':
            // Resumen general de stock
            $stmt = $pdo->query("
                SELECT 
                    COUNT(*) as total_productos,
                    SUM(CASE WHEN Stock <= 0 THEN 1 ELSE 0 END) as sin_stock,
                    SUM(CASE WHEN Stock > 0 AND Stock <= 5 THEN 1 ELSE 0 END) as stock_bajo,
                    SUM(CASE WHEN Stock > 5 AND Stock <= 20 THEN 1 ELSE 0 END) as stock_medio,
                    SUM(CASE WHEN Stock > 20 THEN 1 ELSE 0 END) as stock_alto
                FROM producto
            ");
            
            $resumen = $stmt->fetch();
            
            echo json_encode([
                'success' => true,
                'resumen' => $resumen,
                'fecha_consulta' => date('Y-m-d H:i:s')
            ]);
            break;
            
        default:
            throw new Exception('Acción no válida');
    }
    
} catch (Exception $e) {
    error_log("Error en empleado_stock.php: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>