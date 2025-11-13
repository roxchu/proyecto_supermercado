<?php
/**
 * Lógica de Acciones para Empleados.
 * Maneja peticiones AJAX para obtener productos con bajo stock y renovar el stock.
 */

// 1. Configuración y Seguridad
declare(strict_types=1);
header('Content-Type: application/json');
session_start();

// Función auxiliar para enviar respuesta JSON
function sendJsonResponse(bool $success, string $message, array $data = [], int $httpCode = 200) {
    http_response_code($httpCode);
    echo json_encode(['success' => $success, 'message' => $message] + $data);
    exit;
}

// 2. Verificación de Roles
require_once __DIR__ . '/../carrito/db.php';
require_once __DIR__ . '/../login/verificar_rol.php'; 

try {
    verificar_rol(['admin', 'empleado']);
} catch (Exception $e) {
    sendJsonResponse(false, 'Acceso denegado. Rol insuficiente.', [], 403);
}

// Obtener la acción solicitada
$action = $_REQUEST['action'] ?? null;

// Si no hay acción en $_REQUEST, intentar leerla del JSON del body
$rawInput = '';
if (!$action && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
    $action = $input['action'] ?? null;
}

// DEBUG: Log de lo que se recibe
error_log("=== EMPLEADOS_ACTIOONS DEBUG ===");
error_log("REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
error_log("Raw input: " . $rawInput);
error_log("Action detected: " . ($action ?? 'NULL'));

if (!$action) {
    sendJsonResponse(false, 'Acción no especificada.', [], 400);
}

// ----------------------------------------------------
// --- 1. GET: Obtener productos con bajo stock ---
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'get_productos_sin_stock') {
    $umbral = 20; // Mostrar productos con stock menor a 20
    try {
        $sql = "SELECT p.id_producto, p.nombre_producto, p.stock, c.nombre_categoria FROM producto p LEFT JOIN categoria c ON p.id_categoria = c.id_categoria WHERE p.stock < :umbral ORDER BY p.stock ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':umbral', $umbral, PDO::PARAM_INT);
        $stmt->execute();
        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        sendJsonResponse(true, 'Productos con bajo stock cargados correctamente.', ['productos' => $productos]);
    } catch (PDOException $e) {
        error_log("Error al obtener stock: " . $e->getMessage());
        sendJsonResponse(false, 'Error interno del servidor al consultar la base de datos.', [], 500);
    }
}

// ----------------------------------------------------
// --- 2. POST: Renovar/Actualizar Stock ---
// ----------------------------------------------------
elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'renovar_stock') {
    $input = json_decode(file_get_contents('php://input'), true);

    $idProducto = (int)($input['id_producto'] ?? 0);
    $cantidadAgregar = (int)($input['cantidad_agregar'] ?? 0);

    if ($idProducto <= 0 || $cantidadAgregar <= 0) {
        sendJsonResponse(false, 'ID de producto o cantidad a agregar inválida.', [], 400);
    }

    try {
        $pdo->beginTransaction();

        $sql_update = "UPDATE producto SET stock = stock + :cantidad WHERE id_producto = :id";
        $stmt_update = $pdo->prepare($sql_update);
        $stmt_update->bindParam(':cantidad', $cantidadAgregar, PDO::PARAM_INT);
        $stmt_update->bindParam(':id', $idProducto, PDO::PARAM_INT);
        $stmt_update->execute();

        $sql_select = "SELECT stock FROM producto WHERE id_producto = :id";
        $stmt_select = $pdo->prepare($sql_select);
        $stmt_select->bindParam(':id', $idProducto, PDO::PARAM_INT);
        $stmt_select->execute();
        $nuevoStock = $stmt_select->fetchColumn();
        
        $pdo->commit();

        if ($nuevoStock === false) {
             sendJsonResponse(false, 'Producto no encontrado después de la actualización.', [], 404);
        }

        sendJsonResponse(true, 'Stock renovado con éxito. Cantidad agregada: ' . $cantidadAgregar, ['nuevo_stock' => (int)$nuevoStock]);

    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Error al renovar stock: " . $e->getMessage());
        sendJsonResponse(false, 'Error al actualizar el stock en la base de datos.', [], 500);
    }
} 

// ----------------------------------------------------
// --- 3. POST: Establecer Stock Absoluto ---
// ----------------------------------------------------
elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'establecer_stock') {
    if (!empty($rawInput)) {
        $input = json_decode($rawInput, true);
    } else {
        $input = json_decode(file_get_contents('php://input'), true);
    }

    $idProducto = (int)($input['id_producto'] ?? 0);
    $nuevoStock = (int)($input['nuevo_stock'] ?? 0);

    if ($idProducto <= 0 || $nuevoStock < 0) {
        sendJsonResponse(false, 'ID de producto inválido o stock no puede ser negativo.', [], 400);
    }

    try {
        $pdo->beginTransaction();

        $sql_check = "SELECT nombre_producto FROM producto WHERE id_producto = :id";
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->bindParam(':id', $idProducto, PDO::PARAM_INT);
        $stmt_check->execute();
        $nombreProducto = $stmt_check->fetchColumn();

        if (!$nombreProducto) {
            $pdo->rollBack();
            sendJsonResponse(false, 'Producto no encontrado.', [], 404);
        }

        $sql_update = "UPDATE producto SET stock = :nuevo_stock WHERE id_producto = :id";
        $stmt_update = $pdo->prepare($sql_update);
        $stmt_update->bindParam(':nuevo_stock', $nuevoStock, PDO::PARAM_INT);
        $stmt_update->bindParam(':id', $idProducto, PDO::PARAM_INT);
        $stmt_update->execute();

        $pdo->commit();

        sendJsonResponse(true, "Stock de '{$nombreProducto}' actualizado a {$nuevoStock} unidades.", ['nuevo_stock' => $nuevoStock]);

    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Error al establecer stock: " . $e->getMessage());
        sendJsonResponse(false, 'Error al actualizar el stock en la base de datos.', [], 500);
    }
} 

// ----------------------------------------------------
// --- 4. GET: Obtener todos los pedidos ---
// ----------------------------------------------------
elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'get_pedidos') {
    try {
        $sql = "
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
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        sendJsonResponse(true, 'Pedidos cargados correctamente.', ['pedidos' => $pedidos]);
        
    } catch (PDOException $e) {
        error_log("Error al obtener pedidos: " . $e->getMessage());
        sendJsonResponse(false, 'Error interno del servidor.', [], 500);
    }
}

// ----------------------------------------------------
// --- 5. POST: Cambiar Estado de Pedido ---
// ----------------------------------------------------
elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'cambiar_estado_pedido') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    error_log("=== CAMBIAR ESTADO PEDIDO ===");
    error_log("Input completo: " . json_encode($input));
    
    // Validar que el input se decodificó correctamente
    if (!$input) {
        error_log("❌ JSON no se decodificó correctamente");
        sendJsonResponse(false, 'Datos incompletos (JSON inválido).', [], 400);
    }
    
    $pedido_id = !empty($input['pedido_id']) ? (int)$input['pedido_id'] : 0;
    $nuevo_estado = !empty($input['nuevo_estado']) ? trim($input['nuevo_estado']) : null;
    
    error_log("Pedido ID parseado: $pedido_id");
    error_log("Nuevo estado parseado: $nuevo_estado");
    
    // VALIDACIÓN
    if (empty($pedido_id) || empty($nuevo_estado)) {
        error_log("❌ Validación fallida - Pedido: $pedido_id, Estado: $nuevo_estado");
        sendJsonResponse(false, 'Datos incompletos. Falta pedido_id o nuevo_estado.', [], 400);
    }
    
    // Validar que el estado sea válido
    $estados_validos = ['pendiente', 'en_preparacion', 'enviado', 'recibido'];
    if (!in_array($nuevo_estado, $estados_validos)) {
        error_log("❌ Estado inválido: $nuevo_estado");
        sendJsonResponse(false, 'Estado inválido.', [], 400);
    }
    
    error_log("✅ Validaciones pasadas. Procesando pedido ID: $pedido_id -> $nuevo_estado");
    
    try {
        $pdo->beginTransaction();
        
        // 1. Obtener el estado anterior del pedido
        $sql_get_anterior = "SELECT estado FROM pedido WHERE id_pedido = :id_pedido";
        $stmt_get = $pdo->prepare($sql_get_anterior);
        $stmt_get->execute([':id_pedido' => $pedido_id]);
        $estado_anterior = $stmt_get->fetchColumn();
        
        if ($estado_anterior === false) {
            $pdo->rollBack();
            error_log("❌ Pedido no encontrado: $pedido_id");
            sendJsonResponse(false, 'Pedido no encontrado.', [], 404);
        }
        
        error_log("Estado anterior: $estado_anterior");
        
        // 2. Actualizar estado del pedido
        $sql = "UPDATE pedido SET estado = :estado WHERE id_pedido = :id_pedido";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':estado' => $nuevo_estado,
            ':id_pedido' => $pedido_id
        ]);
        
        // 3. Registrar en el log
        $sql_log = "INSERT INTO pedido_estado_log (id_pedido, estado_anterior, estado_nuevo, cambiado_por) VALUES (:id_pedido, :anterior, :nuevo, :usuario_id)";
        $stmt_log = $pdo->prepare($sql_log);
        $usuario_id = $_SESSION['id_usuario'] ?? null;
        
        $stmt_log->execute([
            ':id_pedido' => $pedido_id,
            ':anterior' => $estado_anterior,
            ':nuevo' => $nuevo_estado,
            ':usuario_id' => $usuario_id
        ]);
        
        $pdo->commit();
        error_log("✅ Pedido actualizado exitosamente");
        sendJsonResponse(true, "Estado del pedido actualizado de '$estado_anterior' a '$nuevo_estado'.");
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("❌ Error DB: " . $e->getMessage());
        sendJsonResponse(false, 'Error al actualizar: ' . $e->getMessage(), [], 500);
    }
}

// Acción desconocida
else {
    sendJsonResponse(false, 'Método o acción no permitida.', [], 405);
}

?>