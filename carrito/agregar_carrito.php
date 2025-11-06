<?php
/**
 * Script para agregar o actualizar la cantidad de un producto en el carrito del usuario.
 * Utiliza PDO para las operaciones de base de datos.
 * Estilo de conexión y respuesta JSON similar a login.php/registro.php.
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

    // 4. Verificar producto y obtener precio
    $stmtProducto = $pdo->prepare("SELECT precio_actual FROM producto WHERE Id_Producto = ?");
    $stmtProducto->execute([$idProducto]);
    $precioUnitario = $stmtProducto->fetchColumn();

    if (!$precioUnitario) {
        throw new Exception('Producto no encontrado');
    }

    // 5. Verificar carrito existente
    $stmtCarrito = $pdo->prepare("
        SELECT Id_Carrito 
        FROM carrito 
        WHERE id_usuario = ? AND Id_Producto = ?
    ");
    $stmtCarrito->execute([$idUsuario, $idProducto]);
    $itemCarrito = $stmtCarrito->fetch();

    $pdo->beginTransaction();

    try {
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
        echo json_encode([
            'success' => true,
            'message' => 'Producto agregado al carrito correctamente'
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