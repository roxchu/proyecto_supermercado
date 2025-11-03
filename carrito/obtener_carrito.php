<?php
/**
 * Script para obtener el contenido completo del carrito de compras del usuario logueado.
 * Devuelve la lista de productos y la información de precios/cantidades en formato JSON.
 */
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/db.php';

// ---------------------------------------------
// LÓGICA DE OBTENER CARRITO
// ---------------------------------------------
try {
    // 1. --- Detectar usuario logueado ---
    // Usamos 'user_id' como en login.php/agregar_carrito.php, junto con el fallback a 'id_usuario'
    $idUsuario = $_SESSION['user_id'] ?? $_SESSION['id_usuario'] ?? null;
    $dni = $_SESSION['dni'] ?? null;

    if (!$idUsuario && !$dni) {
        http_response_code(401); // No autorizado
        echo json_encode([
            'success' => false,
            'message' => 'Debe iniciar sesión para ver su carrito.',
            'carrito' => []
        ]);
        exit;
    }

    // Si solo hay DNI, buscar id_usuario
    if (!$idUsuario && $dni) {
        $stmtUser = $pdo->prepare("SELECT id_usuario FROM usuario WHERE DNI = ?");
        $stmtUser->execute([$dni]);
        $idUsuario = $stmtUser->fetchColumn();

        if (!$idUsuario) {
            http_response_code(404); // Not Found
            echo json_encode([
                'success' => false,
                'message' => 'Usuario no encontrado en la base de datos.',
                'carrito' => []
            ]);
            exit;
        }

        // Si se encontró, lo guardamos en la sesión para el futuro
        $_SESSION['user_id'] = $idUsuario; 
    }

    // 2. --- Consultar productos del carrito ---
    // INNER JOIN con 'producto' es correcto para obtener el nombre del producto
        $sql = "
        SELECT 
            dc.Id_Detalle_Carrito as Id_Carrito,
            dc.Id_Producto,
            p.Nombre_Producto AS nombre,
            dc.Precio_Unitario_Momento,
            dc.Cantidad,
            dc.Total
        FROM venta_unificada vu
        INNER JOIN detalle_carrito dc ON vu.id_venta = dc.Id_Venta
        INNER JOIN producto p ON p.Id_Producto = dc.Id_Producto
        WHERE vu.id_usuario = ? 
        AND vu.tipo_venta = 'virtual' 
        AND vu.Estado = 'Pendiente'
        ORDER BY dc.Id_Detalle_Carrito DESC
    ";    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idUsuario]);
    $carrito = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. --- Calcular el subtotal global ---
    // También es útil calcular el subtotal total del carrito para la respuesta
    $subtotal_global = array_sum(array_column($carrito, 'Total'));

    // 4. --- Respuesta de Éxito ---
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Carrito obtenido correctamente.',
        'carrito' => $carrito,
        'subtotal_global' => $subtotal_global,
        'total_items' => count($carrito)
    ]);
} catch (Throwable $e) {
    // 5. --- Manejo de Errores ---
    error_log("Error en obtener_carrito.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor al obtener el carrito.',
        'carrito' => [],
        'debug' => $e->getMessage() // Útil para desarrollo
    ]);
}

exit;
?>