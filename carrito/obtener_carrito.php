<?php
/**
 * Script para obtener el contenido completo del carrito de compras del usuario logueado.
 * Devuelve la lista de productos y la información de precios/cantidades en formato JSON.
 */
declare(strict_types=1);
session_start();
header('Content-Type: application/json; charset=utf-8');

// ---------------------------------------------
// CONFIGURACIÓN DE LA BASE DE DATOS
// ---------------------------------------------
// Nota: Replicamos la configuración de los otros scripts para asegurar la conexión
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
    // Si 'db.php' existe y es el estándar, lo usaríamos en lugar de duplicar el código.
    // require __DIR__ . '/db.php'; 
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos.']);
    exit;
}

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
            c.Id_Carrito,
            c.Id_Producto,
            p.Nombre_Producto AS nombre,
            c.Precio_Unitario_Momento,
            c.Cantidad,
            c.Total
        FROM carrito c
        INNER JOIN producto p ON p.Id_Producto = c.Id_Producto
        WHERE c.id_usuario = ?
        ORDER BY c.Id_Carrito DESC
    ";
    
    $stmt = $pdo->prepare($sql);
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