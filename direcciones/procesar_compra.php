<?php
session_start();
require_once '../carrito/db.php';

header('Content-Type: application/json');

// Verificar usuario logueado
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
    exit;
}

$usuario_id = $_SESSION['user_id'];

try {
    // 1. Verificar que hay productos en el carrito
    $stmt = $pdo->prepare("SELECT * FROM carrito WHERE id_usuario = ?");
    $stmt->execute([$usuario_id]);
    $productos_carrito = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($productos_carrito)) {
        echo json_encode(['success' => false, 'message' => 'El carrito está vacío']);
        exit;
    }
    
    // 2. Calcular total
    $total_venta = 0;
    foreach ($productos_carrito as $item) {
        $total_venta += $item['precio_unitario_momento'] * $item['cantidad'];
    }
    
    // 3. Iniciar transacción
    $pdo->beginTransaction();
    
    // 4. ✅ ARREGLADO: Usar los nombres CORRECTOS de los campos del formulario
    $ciudad = $_POST['Ciudad'] ?? $_POST['ciudad'] ?? '';  // Intenta ambos
    $provincia = $_POST['Provincia'] ?? $_POST['provincia'] ?? '';
    $codigo_postal = $_POST['Codigo_postal'] ?? $_POST['codigo_postal'] ?? '';
    $calle_numero = $_POST['calle_numero'] ?? '';
    $piso_depto = $_POST['piso_depto'] ?? null;
    $nombre_direccion = $_POST['nombre_direccion'] ?? 'Principal';
    $referencia = $_POST['Referencia'] ?? $_POST['referencia'] ?? null;
    
    // Validar que los campos obligatorios no estén vacíos
    if (empty($ciudad) || empty($provincia) || empty($codigo_postal) || empty($calle_numero)) {
        $pdo->rollBack();
        echo json_encode([
            'success' => false, 
            'message' => 'Faltan datos de dirección. Por favor completa todos los campos.'
        ]);
        exit;
    }
    
    // Crear dirección completa
    $direccion_completa = $calle_numero . 
                         ($piso_depto ? ', ' . $piso_depto : '') . 
                         ', ' . $ciudad . 
                         ', ' . $provincia . 
                         ' (' . $codigo_postal . ')';
    
    // Guardar dirección
    $stmt = $pdo->prepare("
        INSERT INTO direcciones (id_usuario, nombre_direccion, calle_numero, piso_depto, ciudad, provincia, codigo_postal, referencia) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $usuario_id,
        $nombre_direccion,
        $calle_numero,
        $piso_depto,
        $ciudad,
        $provincia,
        $codigo_postal,
        $referencia
    ]);
    
    $direccion_id = $pdo->lastInsertId();
    
    // 5. CREAR LA VENTA
    $stmt = $pdo->prepare("
        INSERT INTO venta (id_usuario, id_direccion, fecha_venta, total_venta, estado) 
        VALUES (?, ?, NOW(), ?, 1)
    ");
    $stmt->execute([$usuario_id, $direccion_id, $total_venta]);
    
    $venta_id = $pdo->lastInsertId();
    
    // 6. Guardar detalles de la venta
    foreach ($productos_carrito as $item) {
        $stmt = $pdo->prepare("
            INSERT INTO detalle_venta (id_venta, id_producto, cantidad, precio_unitario_venta, iva_aplicado) 
            VALUES (?, ?, ?, ?, 21.00)
        ");
        $stmt->execute([
            $venta_id,
            $item['id_producto'],
            $item['cantidad'],
            $item['precio_unitario_momento']
        ]);
    }
    
    // 7. Limpiar el carrito
    $stmt = $pdo->prepare("DELETE FROM carrito WHERE id_usuario = ?");
    $stmt->execute([$usuario_id]);
    
    // 8. Confirmar transacción
    $pdo->commit();
    
    // 9. Guardar ID de venta en sesión
    $_SESSION['ultima_venta_id'] = $venta_id;
    
    echo json_encode([
        'success' => true,
        'message' => 'Compra procesada exitosamente',
        'venta_id' => $venta_id
    ]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("ERROR: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Error al procesar la compra: ' . $e->getMessage()
    ]);
}