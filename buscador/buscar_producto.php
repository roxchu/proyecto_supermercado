<?php
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../carrito/db.php'; // conexión correcta a la BD

try {
    if (!isset($_GET['termino']) || trim($_GET['termino']) === '') {
        echo json_encode(['error' => 'Debe ingresar un término de búsqueda.']);
        exit;
    }

    $termino = '%' . trim($_GET['termino']) . '%';

    $stmt = $pdo->prepare("
        SELECT 
            p.Id_Producto AS id,
            p.Nombre_Producto AS nombre,
            p.Descripcion AS descripcion,
            COALESCE(pi.url_imagen, 'https://via.placeholder.com/250x160?text=Sin+Imagen') AS imagen,
            p.precio_actual AS precio,
            p.Stock AS stock
        FROM producto p
        LEFT JOIN producto_imagenes pi ON p.Id_Producto = pi.Id_Producto AND pi.orden = 1
        WHERE p.Nombre_Producto LIKE ?
        ORDER BY p.Nombre_Producto ASC
    ");
    $stmt->execute([$termino]);
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($productos)) {
        echo json_encode(['error' => '❌ No se encontró ningún producto con ese nombre.']);
    } else {
        echo json_encode($productos);
    }
} catch (PDOException $e) {
    echo json_encode(['error' => 'Error en la base de datos: ' . $e->getMessage()]);
}
