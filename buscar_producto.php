<?php
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/carrito/db.php'; // conexión correcta a la BD

try {
    if (!isset($_GET['termino']) || trim($_GET['termino']) === '') {
        echo json_encode(['error' => 'Debe ingresar un término de búsqueda.']);
        exit;
    }

    $termino = '%' . trim($_GET['termino']) . '%';

    $stmt = $pdo->prepare("
        SELECT 
            Id_Producto AS id,
            Nombre_Producto AS nombre,
            Descripcion AS descripcion,
            imagen_url AS imagen,
            precio_actual AS precio,
            Stock AS stock
        FROM producto
        WHERE Nombre_Producto LIKE ?
        ORDER BY Nombre_Producto ASC
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
