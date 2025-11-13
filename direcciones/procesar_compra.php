<?php
session_start();
require_once '../carrito/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
    exit;
}

$usuario_id = $_SESSION['user_id'];

try {
    // Verificar carrito
    $stmt = $pdo->prepare("SELECT * FROM carrito WHERE id_usuario = ?");
    $stmt->execute([$usuario_id]);
    $productos_carrito = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($productos_carrito)) {
        echo json_encode(['success' => false, 'message' => 'El carrito está vacío']);
        exit;
    }
    
    // Validar campos requeridos
    $campos = ['nombre_direccion', 'calle_numero', 'Codigo_postal', 'ciudad', 'tipo_metodo'];
    foreach ($campos as $campo) {
        if (empty($_POST[$campo])) {
            echo json_encode(['success' => false, 'message' => "Campo faltante: $campo"]);
            exit;
        }
    }
    
    // Calcular total
    $total_pedido = 0;
    foreach ($productos_carrito as $item) {
        $stmt_precio = $pdo->prepare("SELECT precio_actual FROM producto WHERE id_producto = ?");
        $stmt_precio->execute([$item['id_producto']]);
        $producto = $stmt_precio->fetch(PDO::FETCH_ASSOC);
        $total_pedido += (float)$producto['precio_actual'] * (int)$item['cantidad'];
    }
    
    $pdo->beginTransaction();
    
    // 1. Guardar dirección
    $direccion_completa = $_POST['calle_numero'];
    if (!empty($_POST['piso_depto'])) {
        $direccion_completa .= ', ' . $_POST['piso_depto'];
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO direcciones (id_usuario, nombre_direccion, calle_numero, piso_depto, ciudad, provincia, codigo_postal, referencia)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $usuario_id,
        $_POST['nombre_direccion'] ?? 'Principal',
        $_POST['calle_numero'],
        $_POST['piso_depto'] ?? null,
        $_POST['ciudad'],
        $_POST['provincia'] ?? $_POST['ciudad'],
        $_POST['Codigo_postal'],
        $_POST['Referencia'] ?? null
    ]);
    $direccion_id = $pdo->lastInsertId();
    
    // 2. Determinar método de pago
    $tipo_metodo = $_POST['tipo_metodo'];
    $metodo_id = $pdo->prepare("SELECT id_metodo FROM metodo_pago WHERE nombre = ?");
    $metodo_id->execute([$tipo_metodo]);
    $metodo = $metodo_id->fetch();
    $id_metodo_pago = $metodo['id_metodo'] ?? 1;
    
    // 3. Crear pedido (estado: pendiente)
    $stmt = $pdo->prepare("
        INSERT INTO pedido (id_usuario, id_metodo_pago, subtotal, costo_envio, total_final, id_direccion, estado, fecha_pedido)
        VALUES (?, ?, ?, 0, ?, ?, 'pendiente', NOW())
    ");
    $stmt->execute([$usuario_id, $id_metodo_pago, $total_pedido, $total_pedido, $direccion_id]);
    $pedido_id = $pdo->lastInsertId();
    
    // 4. Guardar detalles del pedido
    foreach ($productos_carrito as $item) {
        $stmt_precio = $pdo->prepare("SELECT precio_actual FROM producto WHERE id_producto = ?");
        $stmt_precio->execute([$item['id_producto']]);
        $producto = $stmt_precio->fetch();
        $subtotal = (float)$producto['precio_actual'] * (int)$item['cantidad'];
        
        $stmt = $pdo->prepare("
            INSERT INTO pedido_detalle (id_pedido, id_producto, cantidad, precio_unitario)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$pedido_id, $item['id_producto'], $item['cantidad'], $producto['precio_actual']]);
    }
    
    // 5. Registrar cambio de estado en log
    $stmt = $pdo->prepare("
        INSERT INTO pedido_estado_log (id_pedido, estado_anterior, estado_nuevo, cambiado_por)
        VALUES (?, NULL, 'pendiente', ?)
    ");
    $stmt->execute([$pedido_id, $usuario_id]);
    
    // 6. Eliminar carrito
    $stmt = $pdo->prepare("DELETE FROM carrito WHERE id_usuario = ?");
    $stmt->execute([$usuario_id]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Pedido creado exitosamente',
        'pedido_id' => $pedido_id
    ]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error en procesar_compra: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>