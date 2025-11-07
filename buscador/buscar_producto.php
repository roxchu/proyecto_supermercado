<?php
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../carrito/db.php'; // conexión correcta a la BD

try {
    if (!isset($_GET['termino']) || trim($_GET['termino']) === '') {
        echo json_encode(['error' => 'Debe ingresar un término de búsqueda.']);
        exit;
    }

    $termino = trim($_GET['termino']);
    $terminoBusqueda = '%' . $termino . '%';
    
    // Para búsquedas más flexibles, también dividimos en palabras
    $palabras = explode(' ', $termino);
    $condicionesExtras = [];
    $parametrosExtras = [];
    
    foreach ($palabras as $palabra) {
        if (strlen(trim($palabra)) >= 2) {
            $condicionesExtras[] = "(p.Nombre_Producto LIKE ? OR p.Descripcion LIKE ?)";
            $parametrosExtras[] = '%' . trim($palabra) . '%';
            $parametrosExtras[] = '%' . trim($palabra) . '%';
        }
    }

    // Log para debugging
    error_log("Búsqueda: $termino");
    
    $condicionBusqueda = "
        WHERE (p.Nombre_Producto LIKE ? 
        OR p.Descripcion LIKE ?
        OR c.Nombre_Categoria LIKE ?)
    ";
    
    $parametrosConsulta = [$terminoBusqueda, $terminoBusqueda, $terminoBusqueda];
    
    // Agregar condiciones para palabras individuales si existen
    if (!empty($condicionesExtras)) {
        $condicionBusqueda .= " OR " . implode(' OR ', $condicionesExtras);
        $parametrosConsulta = array_merge($parametrosConsulta, $parametrosExtras);
    }

    $stmt = $pdo->prepare("
        SELECT 
            p.Id_Producto AS id,
            p.Nombre_Producto AS nombre,
            p.Descripcion AS descripcion,
            COALESCE(pi.url_imagen, 'https://via.placeholder.com/250x160?text=Sin+Imagen') AS imagen,
            p.precio_actual AS precio,
            p.precio_anterior,
            p.Stock AS stock,
            p.es_destacado,
            p.etiqueta_especial,
            c.Nombre_Categoria AS categoria
        FROM producto p
        LEFT JOIN producto_imagenes pi ON p.Id_Producto = pi.Id_Producto AND pi.orden = 1
        LEFT JOIN categoria c ON p.Id_Categoria = c.Id_Categoria
        $condicionBusqueda
        ORDER BY 
            CASE WHEN p.Nombre_Producto LIKE ? THEN 1 ELSE 2 END,
            p.Nombre_Producto ASC
        LIMIT 20
    ");
    
    // Agregar el parámetro para el ORDER BY
    $parametrosConsulta[] = $terminoBusqueda;
    
    $stmt->execute($parametrosConsulta);
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Log del resultado
    error_log("Productos encontrados: " . count($productos));

    if (empty($productos)) {
        echo json_encode(['error' => "No se encontraron productos que coincidan con '$termino'"]);
    } else {
        // Formatear los datos
        $productosFormateados = array_map(function($producto) {
            return [
                'id' => $producto['id'],
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
