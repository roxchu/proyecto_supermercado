<?php
/**
 * Script para obtener el contenido completo del carrito de compras del usuario logueado.
 * Devuelve la lista de productos y la información de precios/cantidades en formato JSON.
 */
declare(strict_types=1);
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once 'db.php';

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Usuario no autenticado');
    }

    $idUsuario = (int)$_SESSION['user_id'];

    // Consulta para obtener los items del carrito con detalles del producto
    $stmt = $pdo->prepare("
        SELECT 
            c.Id_Carrito,
            c.Id_Producto,
            c.Cantidad,
            c.Precio_Unitario_Momento,
            c.Total,
            p.Nombre as nombre_producto,
            p.Imagen as imagen_producto
        FROM carrito c
        JOIN producto p ON c.Id_Producto = p.Id_Producto
        WHERE c.id_usuario = ?
    ");

    $stmt->execute([$idUsuario]);
    $items = $stmt->fetchAll();

    // Calcular total del carrito
    $total = 0;
    foreach ($items as $item) {
        $total += (float)$item['Total'];
    }

    echo json_encode([
        'success' => true,
        'items' => $items,
        'total' => $total
    ]);

} catch (Exception $e) {
    error_log("Error en obtener_carrito.php: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener el carrito: ' . $e->getMessage()
    ]);
}

exit;
?>