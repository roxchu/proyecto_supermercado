<?php
// api_productos.php - Endpoint JSON para obtener todos los productos
session_start();
header('Content-Type: application/json; charset=utf-8');

$host = 'localhost';
$db   = 'supermercado';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    // Obtener todos los productos CON STOCK (Stock > 0)
    $stmt = $pdo->query("
        SELECT 
            p.Id_Producto AS id, 
            p.Nombre_Producto AS nombre, 
            p.Descripcion AS descripcion,
            COALESCE(pi.url_imagen, 'https://via.placeholder.com/280x200?text=Sin+Imagen') AS imagen, 
            p.precio_actual AS precio,
            p.precio_anterior,
            p.etiqueta_especial,
            p.descuento_texto,
            p.Stock AS stock,
            p.es_destacado,
            c.Nombre_Categoria AS categoria
        FROM producto p
        LEFT JOIN categoria c ON p.Id_Categoria = c.Id_Categoria
        LEFT JOIN producto_imagenes pi ON p.Id_Producto = pi.Id_Producto AND pi.orden = 1
        WHERE p.Stock > 0
        ORDER BY 
            p.es_destacado DESC, 
            p.Id_Producto ASC
    ");

    $productos = $stmt->fetchAll();

    // Formatear los datos
    $productosFormateados = array_map(function($producto) {
        return [
            'id' => (int)$producto['id'],
            'nombre' => $producto['nombre'],
            'descripcion' => $producto['descripcion'],
            'imagen' => $producto['imagen'],
            'precio' => (float)$producto['precio'],
            'precio_anterior' => $producto['precio_anterior'] ? (float)$producto['precio_anterior'] : null,
            'stock' => (int)$producto['stock'],
            'es_destacado' => (bool)$producto['es_destacado'],
            'etiqueta_especial' => $producto['etiqueta_especial'],
            'categoria' => $producto['categoria']
        ];
    }, $productos);

    echo json_encode([
        'success' => true,
        'productos' => $productosFormateados,
        'total' => count($productosFormateados)
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    error_log("Error en api_productos.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error al cargar productos',
        'productos' => []
    ]);
}
?>