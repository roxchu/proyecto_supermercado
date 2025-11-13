<?php
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../carrito/db.php'; // conexión correcta a la BD

try {
    if (!isset($_GET['termino']) || trim($_GET['termino']) === '') {
        echo json_encode(['error' => 'No está este producto en este supermercado.']);
        exit;
    }

    $termino = trim($_GET['termino']);
    $terminoBusqueda = '%' . $termino . '%';

    // Log para debugging
    error_log("Búsqueda: $termino");
    
    // Consulta para buscar productos por nombre, descripción o categoría
    $stmt = $pdo->prepare("
        SELECT 
            p.id_producto AS id,
            p.nombre_producto AS nombre,
            p.descripcion AS descripcion,
            COALESCE(pi.url_imagen, 'https://via.placeholder.com/250x160?text=Sin+Imagen') AS imagen,
            p.precio_actual AS precio,
            p.precio_anterior,
            p.stock AS stock,
            p.es_destacado,
            p.etiqueta_especial,
            c.nombre_categoria AS categoria
        FROM producto p
        LEFT JOIN producto_imagenes pi ON p.id_producto = pi.id_producto AND pi.orden = 1
        LEFT JOIN categoria c ON p.id_categoria = c.id_categoria
        WHERE (p.nombre_producto LIKE ? 
            OR p.descripcion LIKE ?
            OR c.nombre_categoria LIKE ?)
        ORDER BY 
            CASE WHEN p.nombre_producto LIKE ? THEN 1 ELSE 2 END,
            p.nombre_producto ASC
        LIMIT 20
    ");
    
    $stmt->execute([$terminoBusqueda, $terminoBusqueda, $terminoBusqueda, $terminoBusqueda]);
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Log del resultado
    error_log("Productos encontrados: " . count($productos));

    if (empty($productos)) {
        echo json_encode(['error' => 'No está este producto en este supermercado.']);
    } else {
        // Formatear los datos
        $productosFormateados = array_map(function($producto) {
            return [
                'id' => (int)$producto['id'],
                'nombre' => $producto['nombre'],
                'descripcion' => $producto['descripcion'],
                'imagen' => $producto['imagen'],
                'precio' => number_format((float)$producto['precio'], 2, '.', ''),
                'precio_anterior' => $producto['precio_anterior'] ? number_format((float)$producto['precio_anterior'], 2, '.', '') : null,
                'stock' => (int)$producto['stock'],
                'es_destacado' => (bool)$producto['es_destacado'],
                'etiqueta_especial' => $producto['etiqueta_especial'],
                'categoria' => $producto['categoria']
            ];
        }, $productos);
        
        echo json_encode($productosFormateados);
    }
} catch (PDOException $e) {
    error_log("Error en búsqueda: " . $e->getMessage());
    echo json_encode(['error' => 'Error en la búsqueda. Intente nuevamente.']);
} catch (Exception $e) {
    error_log("Error general en búsqueda: " . $e->getMessage());
    echo json_encode(['error' => 'Error inesperado. Intente nuevamente.']);
}
