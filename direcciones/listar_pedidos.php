<?php
session_start();
require_once '../carrito/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            p.id_pedido,
            p.estado,
            p.fecha_pedido,
            p.total_final,
            u.nombre_usuario,
            u.correo,
            d.nombre_direccion,
            d.calle_numero,
            COUNT(pd.id_detalle) as cantidad_items
        FROM pedido p
        JOIN usuario u ON p.id_usuario = u.id_usuario
        JOIN direcciones d ON p.id_direccion = d.id_direccion
        LEFT JOIN pedido_detalle pd ON p.id_pedido = pd.id_pedido
        GROUP BY p.id_pedido
        ORDER BY p.fecha_pedido DESC
    ");
    $stmt->execute();
    $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'pedidos' => $pedidos
    ]);
    
} catch (Exception $e) {
    error_log("Error en listar_pedidos: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error del servidor']);
}
?>