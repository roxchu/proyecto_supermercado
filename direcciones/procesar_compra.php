<?php
session_start();
require_once '../carrito/db.php';

header('Content-Type: application/json');

// Verificar que el usuario esté logueado
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
    exit;
}

$usuario_id = $_SESSION['user_id'];

try {
    // Verificar que hay productos en el carrito
    $stmt = $pdo->prepare("SELECT * FROM carrito WHERE id_usuario = ?");
    $stmt->execute([$usuario_id]);
    $productos_carrito = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($productos_carrito)) {
        echo json_encode(['success' => false, 'message' => 'El carrito está vacío']);
        exit;
    }
    
    // Calcular total del pedido
    $total_pedido = 0;
    foreach ($productos_carrito as $item) {
        $stmt_precio = $pdo->prepare("SELECT precio_actual FROM producto WHERE Id_Producto = ?");
        $stmt_precio->execute([$item['Id_Producto']]);
        $producto = $stmt_precio->fetch(PDO::FETCH_ASSOC);
        $total_pedido += $producto['precio_actual'] * $item['Cantidad'];
    }
    
    // Iniciar transacción
    $pdo->beginTransaction();
    
    // 1. Guardar método de pago
    $tipo_metodo = $_POST['tipo_metodo'] ?? '';
    $nombre_titular = $_POST['nombre_titular'] ?? null;
    $numero_tarjeta = $_POST['numero_tarjeta'] ?? null;
    $vencimiento = $_POST['vencimiento'] ?? null;
    
    // Si es tarjeta, encriptar los últimos 4 dígitos para mostrar
    $numero_enmascarado = null;
    if ($numero_tarjeta) {
        $numero_enmascarado = '**** **** **** ' . substr($numero_tarjeta, -4);
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO metodo_pago (usuario_id, tipo, nombre_titular, numero_enmascarado, vencimiento, activo) 
        VALUES (?, ?, ?, ?, ?, 1)
    ");
    $stmt->execute([
        $usuario_id, 
        $tipo_metodo, 
        $nombre_titular, 
        $numero_enmascarado, 
        $vencimiento
    ]);
    
    $metodo_pago_id = $pdo->lastInsertId();
    
    // 2. Crear el pedido
    $direccion_completa = ($_POST['calle_numero'] ?? '') . 
                         ($_POST['piso_depto'] ? ', ' . $_POST['piso_depto'] : '') . 
                         ', ' . ($_POST['localidad'] ?? '') . 
                         ', ' . ($_POST['provincia'] ?? '') . 
                         ' (' . ($_POST['codigo_postal'] ?? '') . ')';
    
    $stmt = $pdo->prepare("
        INSERT INTO pedido (usuario_id, metodo_pago_id, total, direccion_envio, nombre_direccion, estado, fecha_pedido) 
        VALUES (?, ?, ?, ?, ?, 'pendiente', NOW())
    ");
    $stmt->execute([
        $usuario_id, 
        $metodo_pago_id, 
        $total_pedido, 
        $direccion_completa,
        $_POST['nombre_direccion'] ?? 'Principal'
    ]);
    
    $pedido_id = $pdo->lastInsertId();
    
    // 3. Guardar detalles del pedido
    foreach ($productos_carrito as $item) {
        // Obtener precio actual del producto
        $stmt_producto = $pdo->prepare("SELECT precio_actual FROM producto WHERE Id_Producto = ?");
        $stmt_producto->execute([$item['Id_Producto']]);
        $producto = $stmt_producto->fetch(PDO::FETCH_ASSOC);
        
        $subtotal = $producto['precio_actual'] * $item['Cantidad'];
        
        $stmt = $pdo->prepare("
            INSERT INTO pedido_detalle (pedido_id, producto_id, cantidad, precio_unitario, subtotal) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $pedido_id, 
            $item['Id_Producto'], 
            $item['Cantidad'], 
            $producto['precio_actual'], 
            $subtotal
        ]);
    }
    
    // 4. Limpiar el carrito
    $stmt = $pdo->prepare("DELETE FROM carrito WHERE id_usuario = ?");
    $stmt->execute([$usuario_id]);
    
    // Confirmar transacción
    $pdo->commit();
    
    // Guardar ID del pedido en la sesión para la página de éxito
    $_SESSION['ultimo_pedido_id'] = $pedido_id;
    
    echo json_encode([
        'success' => true, 
        'message' => 'Compra procesada exitosamente',
        'pedido_id' => $pedido_id
    ]);
    
} catch (Exception $e) {
    // Revertir transacción en caso de error
    $pdo->rollBack();
    
    error_log("Error al procesar compra: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Error interno del servidor'
    ]);
}
?>