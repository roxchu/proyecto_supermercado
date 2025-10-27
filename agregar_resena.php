<?php
// agregar_resena.php - Procesar nuevas reseñas de productos

// Configurar errores (quitar en producción)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Configurar headers para JSON
header('Content-Type: application/json; charset=utf-8');

// Iniciar sesión
session_start();

// Incluir conexión a la base de datos
require __DIR__ . '/carrito/db.php';

// Verificar que es una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Verificar que el usuario esté logueado
if (!isset($_SESSION['user_id']) || !isset($_SESSION['rol'])) {
    echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión para agregar una reseña']);
    exit;
}

// Obtener datos del formulario
$accion = $_POST['accion'] ?? '';
$producto_id = $_POST['producto_id'] ?? '';
$calificacion = $_POST['calificacion'] ?? '';
$comentario = $_POST['comentario'] ?? '';
$user_id = $_SESSION['user_id'];

// Validar datos
if ($accion !== 'agregar_resena') {
    echo json_encode(['success' => false, 'message' => 'Acción no válida']);
    exit;
}

if (empty($producto_id) || !is_numeric($producto_id)) {
    echo json_encode(['success' => false, 'message' => 'ID de producto no válido']);
    exit;
}

if (empty($calificacion) || !is_numeric($calificacion) || $calificacion < 1 || $calificacion > 5) {
    echo json_encode(['success' => false, 'message' => 'Calificación debe ser entre 1 y 5 estrellas']);
    exit;
}

// El comentario es opcional, pero si se proporciona, validar longitud
if (!empty($comentario) && strlen($comentario) > 500) {
    echo json_encode(['success' => false, 'message' => 'El comentario no puede exceder 500 caracteres']);
    exit;
}

try {
    // Verificar que el producto existe
    $stmt_producto = $pdo->prepare("SELECT Id_Producto FROM producto WHERE Id_Producto = ?");
    $stmt_producto->execute([$producto_id]);
    
    if (!$stmt_producto->fetch()) {
        echo json_encode(['success' => false, 'message' => 'El producto no existe']);
        exit;
    }
    
    // Verificar si el usuario ya ha reseñado este producto
    $stmt_check = $pdo->prepare("SELECT Id_Opinion FROM opinion WHERE Id_Producto = ? AND Id_Usuario = ?");
    $stmt_check->execute([$producto_id, $user_id]);
    
    if ($stmt_check->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Ya has reseñado este producto']);
        exit;
    }
    
    // Insertar la nueva reseña
    $sql_insert = "INSERT INTO opinion (Id_Producto, Id_Usuario, Calificacion, Comentario, Fecha_Opinion) 
                   VALUES (?, ?, ?, ?, NOW())";
    
    $stmt_insert = $pdo->prepare($sql_insert);
    $success = $stmt_insert->execute([
        $producto_id,
        $user_id,
        $calificacion,
        $comentario
    ]);
    
    if ($success) {
        echo json_encode([
            'success' => true, 
            'message' => '¡Reseña agregada exitosamente! Gracias por tu opinión.'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al guardar la reseña']);
    }
    
} catch (PDOException $e) {
    error_log("Error en agregar_resena.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
} catch (Exception $e) {
    error_log("Error general en agregar_resena.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error inesperado']);
}
?>