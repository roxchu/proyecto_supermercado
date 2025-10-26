<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// ==================================
// Conexión a la base de datos
// ==================================
$host = 'localhost';
$db   = 'supermercado';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'msg' => 'Error BD: ' . $e->getMessage()]);
    exit;
}

// ==================================
// Verificar sesión
// ==================================
if (!isset($_SESSION['dni']) && !isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'msg' => 'Usuario no autenticado']);
    exit;
}

$dni = $_SESSION['dni'] ?? $_SESSION['user_id'];

// ==================================
// Leer datos del JSON recibido
// ==================================
$data = json_decode(file_get_contents("php://input"), true);
$idProducto = $data['id_producto'] ?? null;

if (!$idProducto) {
    echo json_encode(['success' => false, 'msg' => 'Falta ID del producto']);
    exit;
}

// ==================================
// Eliminar producto del carrito
// ==================================
try {
    $stmt = $pdo->prepare("DELETE FROM carrito WHERE DNI_Usuario = ? AND Id_Producto = ?");
    $stmt->execute([$dni, $idProducto]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'msg' => 'Producto eliminado']);
    } else {
        echo json_encode(['success' => false, 'msg' => 'Producto no encontrado en carrito']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'msg' => 'Error al eliminar: ' . $e->getMessage()]);
}
