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
// Asegúrate de que las rutas a db.php y verificar_rol.php sean correctas.
require_once __DIR__ . '/../carrito/db.php';
require_once __DIR__ . '/../login/verificar_rol.php'; 

// verififcar_rol(['admin', 'empleado']) garantiza que solo estos roles pueden continuar
// Si no están definidos, la función debe redirigir o salir con un error 403.
// Asumiendo que verificar_rol detiene la ejecución si falla:
try {
    verificar_rol(['admin', 'empleado']);
} catch (Exception $e) {
    // Si la verificación de rol es muy estricta, podemos devolver un error JSON 403
    sendJsonResponse(false, 'Acceso denegado. Rol insuficiente.', [], 403);
}

// Obtener la acción solicitada
$action = $_REQUEST['action'] ?? null; // Puede venir por GET (URL) o POST

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
error_log("GET params: " . json_encode($_GET));
error_log("POST params: " . json_encode($_POST));
error_log("Raw input: " . $rawInput);
error_log("Action detected: " . ($action ?? 'NULL'));

if (!$action) {
    sendJsonResponse(false, 'Acción no especificada.', [], 400);
}

// ----------------------------------------------------
// --- 1. GET: Obtener productos con bajo stock ---
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'get_productos_sin_stock') {
    // El umbral se pasa por parámetro GET (e.g., empleados_actions.php?action=...&umbral=5)
    $umbral = (int)($_GET['umbral'] ?? 5); 

    try {
        // Consulta: productos con stock menor o igual al umbral
        $sql = "SELECT Id_Producto, Nombre_Producto, Stock FROM producto WHERE Stock <= :umbral ORDER BY Stock ASC";
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
    // Leer el cuerpo de la petición (JSON)
    $input = json_decode(file_get_contents('php://input'), true);

    $idProducto = (int)($input['id_producto'] ?? 0);
    $cantidadAgregar = (int)($input['cantidad_agregar'] ?? 0);

    // Validaciones
    if ($idProducto <= 0 || $cantidadAgregar <= 0) {
        sendJsonResponse(false, 'ID de producto o cantidad a agregar inválida.', [], 400);
    }

    try {
        $pdo->beginTransaction();

        // 1. Actualizar el stock: sumar la nueva cantidad a la existente
        $sql_update = "UPDATE producto SET Stock = Stock + :cantidad WHERE Id_Producto = :id";
        $stmt_update = $pdo->prepare($sql_update);
        $stmt_update->bindParam(':cantidad', $cantidadAgregar, PDO::PARAM_INT);
        $stmt_update->bindParam(':id', $idProducto, PDO::PARAM_INT);
        $stmt_update->execute();

        // 2. Obtener el nuevo stock para devolverlo al frontend
        $sql_select = "SELECT Stock FROM producto WHERE Id_Producto = :id";
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
// --- 3. POST: Establecer Stock Absoluto (NUEVO) ---
// ----------------------------------------------------
elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'establecer_stock') {
    // Leer el cuerpo de la petición (JSON) - usar la variable ya leída si existe
    if (!empty($rawInput)) {
        $input = json_decode($rawInput, true);
    } else {
        $input = json_decode(file_get_contents('php://input'), true);
    }

    $idProducto = (int)($input['id_producto'] ?? 0);
    $nuevoStock = (int)($input['nuevo_stock'] ?? 0);

    // Validaciones
    if ($idProducto <= 0 || $nuevoStock < 0) {
        sendJsonResponse(false, 'ID de producto inválido o stock no puede ser negativo.', [], 400);
    }

    try {
        $pdo->beginTransaction();

        // 1. Verificar que el producto existe
        $sql_check = "SELECT Nombre_Producto FROM producto WHERE Id_Producto = :id";
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->bindParam(':id', $idProducto, PDO::PARAM_INT);
        $stmt_check->execute();
        $nombreProducto = $stmt_check->fetchColumn();

        if (!$nombreProducto) {
            $pdo->rollBack();
            sendJsonResponse(false, 'Producto no encontrado.', [], 404);
        }

        // 2. Establecer el nuevo stock (valor absoluto)
        $sql_update = "UPDATE producto SET Stock = :nuevo_stock WHERE Id_Producto = :id";
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
// --- Acción desconocida ---
// ----------------------------------------------------
else {
    sendJsonResponse(false, 'Método o acción no permitida.', [], 405);
}

?>