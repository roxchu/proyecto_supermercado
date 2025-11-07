<?php
/**
 * Script para agregar o actualizar la cantidad de un producto en el carrito del usuario.
 * Reduce el stock automáticamente - NO renueva automáticamente.
 * Si stock = 0, el producto no se puede comprar.
 */
declare(strict_types=1);
session_start();
header('Content-Type: application/json; charset=utf-8');

// ---------------------------------------------
// CONFIGURACIÓN DE LA BASE DE DATOS
// ---------------------------------------------
// Nota: En una aplicación real, esta configuración debería estar en un solo archivo incluido (e.g., 'db.php')
$host = 'localhost';
$db   = 'supermercado';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE               => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE    => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES      => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos.']);
    exit;
}

// ---------------------------------------------
// LÓGICA DE AGREGAR AL CARRITO
// ---------------------------------------------
try {
    // 1. Verificación de sesión
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Debe iniciar sesión para agregar productos');
    }

    $idUsuario = (int)$_SESSION['user_id'];

    // 2. Verificar que el usuario existe en la tabla usuario
    $stmtUsuario = $pdo->prepare("SELECT id_usuario FROM usuario WHERE id_usuario = ?");
    $stmtUsuario->execute([$idUsuario]);
    if (!$stmtUsuario->fetch()) {
        throw new Exception('Usuario no válido');
    }

    // 3. Obtener datos del producto
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || !isset($input['id_producto']) || !isset($input['cantidad'])) {
        throw new Exception('Datos inválidos');
    }

    $idProducto = (int)$input['id_producto'];
    $cantidad = (int)$input['cantidad'];

    // 4. Verificar producto, obtener precio y stock
    $stmtProducto = $pdo->prepare("SELECT precio_actual, Stock, Nombre_Producto FROM producto WHERE Id_Producto = ?");
    $stmtProducto->execute([$idProducto]);
    $producto = $stmtProducto->fetch();

    if (!$producto) {
        throw new Exception('Producto no encontrado');
    }

    $precioUnitario = $producto['precio_actual'];
    $stockActual = (int)$producto['Stock'];
    $nombreProducto = $producto['Nombre_Producto'];

    // 5. VERIFICAR SI HAY STOCK DISPONIBLE
    if ($stockActual <= 0) {
        throw new Exception("Producto sin stock disponible. El empleado debe renovar el inventario.");
    }

    if ($stockActual < $cantidad) {
        throw new Exception("Stock insuficiente. Solo quedan {$stockActual} unidades disponibles.");
    }

    // 6. Verificar carrito existente
    $stmtCarrito = $pdo->prepare("
        SELECT Id_Carrito, Cantidad 
        FROM carrito 
        WHERE id_usuario = ? AND Id_Producto = ?
    ");
    $stmtCarrito->execute([$idUsuario, $idProducto]);
    $itemCarrito = $stmtCarrito->fetch();

    // 7. Verificar stock total necesario si ya existe en carrito
    $cantidadTotalNecesaria = $cantidad;
    if ($itemCarrito) {
        $cantidadTotalNecesaria += (int)$itemCarrito['Cantidad'];
    }
    
    if ($stockActual < $cantidadTotalNecesaria) {
        $cantidadEnCarrito = $itemCarrito ? $itemCarrito['Cantidad'] : 0;
        throw new Exception("Stock insuficiente. Disponible: {$stockActual}, ya tienes {$cantidadEnCarrito} en el carrito.");
    }

    $pdo->beginTransaction();

    try {
        // 8. REDUCIR STOCK EN LA BASE DE DATOS
        $nuevoStock = $stockActual - $cantidad;
        $stmtStock = $pdo->prepare("UPDATE producto SET Stock = ? WHERE Id_Producto = ?");
        $stmtStock->execute([$nuevoStock, $idProducto]);

        // 9. Actualizar o insertar en carrito
        if ($itemCarrito) {
            // Actualizar cantidad existente
            $stmt = $pdo->prepare("
                UPDATE carrito 
                SET Cantidad = Cantidad + ?,
                    Precio_Unitario_Momento = ? 
                WHERE Id_Carrito = ?
            ");
            $stmt->execute([$cantidad, $precioUnitario, $itemCarrito['Id_Carrito']]);
        } else {
            // Insertar nuevo item
            $stmt = $pdo->prepare("
                INSERT INTO carrito (id_usuario, Id_Producto, Cantidad, Precio_Unitario_Momento) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$idUsuario, $idProducto, $cantidad, $precioUnitario]);
        }

        $pdo->commit();
        
        // 10. Preparar respuesta
        $mensaje = "Producto agregado al carrito correctamente";
        $alertaStock = false;
        
        if ($nuevoStock <= 0) {
            $mensaje .= ". ⚠️ PRODUCTO SIN STOCK - Necesita renovación del empleado";
            $alertaStock = true;
            
            // Log para empleados
            error_log("[STOCK AGOTADO] Producto ID: {$idProducto} ({$nombreProducto}) - Stock: 0 - Fecha: " . date('Y-m-d H:i:s'));
        } elseif ($nuevoStock <= 5) {
            $mensaje .= ". ⚠️ Quedan solo {$nuevoStock} unidades";
            $alertaStock = true;
        }
        
        echo json_encode([
            'success' => true,
            'message' => $mensaje,
            'stock_info' => [
                'stock_anterior' => $stockActual,
                'stock_actual' => $nuevoStock,
                'stock_agotado' => $nuevoStock <= 0,
                'stock_bajo' => $nuevoStock <= 5 && $nuevoStock > 0,
                'alerta_stock' => $alertaStock
            ]
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    error_log("Error en agregar_carrito.php: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Error al procesar la solicitud: ' . $e->getMessage(),
        'debug' => [
            'session_id' => session_id(),
            'user_id' => $idUsuario ?? null
        ]
    ]);
}
?>