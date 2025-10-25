<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

include '../login/control_acceso.php';

header('Content-Type: application/json');

// =======================================================
// 1. VERIFICACIÓN DE SEGURIDAD
// =======================================================
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'empleado') {
    http_response_code(403); 
    echo json_encode(['success' => false, 'message' => 'Acceso denegado. Se requiere rol de Empleado.']);
    exit();
}

// =======================================================
// 2. OBTENER ID_EMPLEADO
// Tu tabla 'venta' usa id_empleado, que se obtiene de la tabla 'empleado' 
// enlazando con el id_usuario de la sesión.
// =======================================================
try {
    $stmt_empleado = $pdo->prepare("SELECT id_empleado FROM empleado WHERE id_usuario = ?");
    $stmt_empleado->execute([$_SESSION['user_id']]);
    $empleado_data = $stmt_empleado->fetch(PDO::FETCH_ASSOC);

    if (!$empleado_data) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'No se encontró el perfil de empleado asociado a este usuario.']);
        exit();
    }
    $id_empleado_actual = $empleado_data['id_empleado'];
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al obtener ID de empleado: ' . $e->getMessage()]);
    exit();
}


$action = $_POST['action'] ?? null;
$response = ['success' => false, 'message' => 'Acción no válida o no especificada.'];

try {
    switch ($action) {
        
        // ---------------------------------------------------
        // ACCIÓN 1: ACTUALIZAR EL STOCK DE UN PRODUCTO
        // ---------------------------------------------------
        case 'update_stock':
            $producto_id = $_POST['Id_Producto'] ?? null; // Usando Id_Producto
            $new_stock = $_POST['Stock'] ?? null;         // Usando Stock
            
            if ($producto_id && $new_stock !== null && is_numeric($new_stock)) {
                $sql = "UPDATE producto SET Stock = :Stock WHERE Id_Producto = :Id"; // Nombres ajustados
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['Stock' => (int)$new_stock, 'Id' => (int)$producto_id]);

                if ($stmt->rowCount() > 0) {
                    $response = ['success' => true, 'message' => 'Stock del producto ID ' . $producto_id . ' actualizado correctamente.'];
                } else {
                    $response['message'] = 'No se encontró el producto o el stock no cambió.';
                }
            } else {
                $response['message'] = 'Faltan datos requeridos o son inválidos.';
            }
            break;

        // ---------------------------------------------------
        // ACCIÓN 2: CAMBIAR EL ESTADO DE UNA VENTA (PEDIDO)
        // ---------------------------------------------------
        case 'change_order_status':
            $venta_id = $_POST['id_venta'] ?? null; // Usando id_venta
            $new_status = $_POST['Estado_Venta'] ?? null; // Usando Estado_Venta

            // Estados válidos según tu lógica de negocio
            $valid_statuses = ['Pendiente', 'Preparando', 'Enviado', 'Entregado', 'Cancelado'];

            if ($venta_id && $new_status && in_array($new_status, $valid_statuses)) {
                $sql = "UPDATE venta SET Estado_Venta = :Estado_Venta, id_empleado = :id_empleado, Fecha_Actualizacion = NOW() WHERE id_venta = :id_venta";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    'Estado_Venta' => $new_status, 
                    'id_empleado' => $id_empleado_actual, // Usamos el ID de empleado real
                    'id_venta' => $venta_id
                ]);

                if ($stmt->rowCount() > 0) {
                    $response = ['success' => true, 'message' => 'Estado de la venta ID ' . $venta_id . ' actualizado a: ' . htmlspecialchars($new_status) . '.'];
                } else {
                     $response['message'] = 'No se encontró la venta o el estado no cambió.';
                }
            } else {
                $response['message'] = 'Faltan datos requeridos (ID de venta, estado) o el estado es inválido.';
            }
            break;

        // ---------------------------------------------------
        // ACCIÓN 3: OBTENER UN LISTADO DE VENTAS PENDIENTES
        // ---------------------------------------------------
        case 'get_pending_orders':
             $sql = "SELECT 
                        v.id_venta, 
                        v.Fecha_Venta, 
                        v.Total_Final, 
                        c.nombre_cliente AS cliente_nombre,
                        v.Estado_Venta
                     FROM venta v
                     JOIN cliente c ON v.id_cliente = c.id_cliente
                     WHERE v.Estado_Venta IN ('Pendiente', 'Preparando')
                     ORDER BY v.Fecha_Venta ASC LIMIT 50";
            
            $stmt = $pdo->query($sql);
            $ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $response = ['success' => true, 'data' => $ventas];
            break;
            
        default:
            $response['message'] = 'Acción solicitada desconocida: ' . htmlspecialchars($action) . '.';
            break;
    }

} catch (PDOException $e) {
    $response['message'] = 'Error de base de datos: ' . $e->getMessage();
}

echo json_encode($response);
?>