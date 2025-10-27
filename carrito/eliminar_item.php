<?php
/**
 * Script para eliminar una línea de producto del carrito del usuario.
 * Se espera recibir el 'id_producto' o 'Id_Carrito' a través de POST/JSON.
 */
declare(strict_types=1);
session_start();
header('Content-Type: application/json; charset=utf-8');

// ---------------------------------------------
// CONFIGURACIÓN DE LA BASE DE DATOS
// ---------------------------------------------
// Se replica la conexión de los otros scripts
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
// LÓGICA DE ELIMINACIÓN DE ÍTEM
// ---------------------------------------------
try {
    // 1. --- Detectar usuario logueado ---
    // Usamos 'user_id' como en login.php/agregar_carrito.php
    $idUsuario = $_SESSION['user_id'] ?? $_SESSION['id_usuario'] ?? null;
    $dni = $_SESSION['dni'] ?? null;

    if (!$idUsuario && !$dni) {
        http_response_code(401); // No autorizado
        echo json_encode([
            'success' => false,
            'message' => 'Debe iniciar sesión para modificar el carrito.'
        ]);
        exit;
    }

    // Lógica para obtener idUsuario si solo hay DNI (replicada de obtener_carrito.php)
    if (!$idUsuario && $dni) {
        $stmtUser = $pdo->prepare("SELECT id_usuario FROM usuario WHERE DNI = ?");
        $stmtUser->execute([$dni]);
        $idUsuario = $stmtUser->fetchColumn();

        if (!$idUsuario) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Usuario no encontrado.'
            ]);
            exit;
        }
        $_SESSION['user_id'] = $idUsuario; 
    }

    // 2. --- Leer y validar entrada JSON ---
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método no permitido. Use POST.']);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);

    if (json_last_error() !== JSON_ERROR_NONE || (!isset($input['id_producto']) && !isset($input['id_carrito']))) {
        http_response_code(400); // Solicitud incorrecta
        echo json_encode([
            'success' => false,
            'message' => 'Datos incompletos. Se requiere id_producto o id_carrito.'
        ]);
        exit;
    }

    // Preferimos eliminar por Id_Carrito si está disponible, es más directo y seguro.
    $idItemCarrito = $input['id_carrito'] ?? null;
    $idProducto = $input['id_producto'] ?? null;


    // 3. --- Eliminar el ítem ---

    // La eliminación debe asegurar que solo se elimine ítems del usuario logueado.
    if ($idItemCarrito) {
        $sqlDelete = "DELETE FROM carrito WHERE Id_Carrito = ? AND id_usuario = ?";
        $params = [(int)$idItemCarrito, $idUsuario];
    } elseif ($idProducto) {
        // Si se elimina por Id_Producto, elimina TODAS las filas de ese producto para el usuario.
        $sqlDelete = "DELETE FROM carrito WHERE Id_Producto = ? AND id_usuario = ?";
        $params = [(int)$idProducto, $idUsuario];
    } else {
         http_response_code(400);
         echo json_encode(['success' => false, 'message' => 'ID de eliminación no válido.']);
         exit;
    }

    $stmtDelete = $pdo->prepare($sqlDelete);
    $stmtDelete->execute($params);
    $filasAfectadas = $stmtDelete->rowCount();


    // 4. --- Respuesta Final ---
    if ($filasAfectadas > 0) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Ítem(s) eliminado(s) del carrito correctamente.',
            'filas_eliminadas' => $filasAfectadas
        ]);
    } else {
        http_response_code(404); // No encontrado
        echo json_encode([
            'success' => false,
            'message' => 'El ítem especificado no se encontró en el carrito del usuario.',
            'filas_eliminadas' => 0
        ]);
    }
    
} catch (Throwable $e) {
    // 5. --- Manejo de Errores ---
    error_log("Error en eliminar_item.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno al eliminar el ítem.',
        'debug' => $e->getMessage()
    ]);
}
?>