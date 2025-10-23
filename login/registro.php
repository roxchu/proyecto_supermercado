<?php
// registro.php - Registro de nuevos clientes
error_reporting(E_ALL);
ini_set('display_errors', 0); // En producción debe estar en 0
ini_set('log_errors', 1);

session_start();
header('Content-Type: application/json; charset=utf-8');

// -----------------------------------------------------
// CONFIGURACIÓN DE LA BASE DE DATOS - AJUSTAR SI ES NECESARIO
// -----------------------------------------------------
$host = 'localhost';
$db   = 'supermercado';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error de conexión a la base de datos',
        'debug' => $e->getMessage()
    ]);
    exit;
}

// Verificar que sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Obtener datos del POST (Coinciden con los 'name's del index.html)
$dni = trim($_POST['dni'] ?? '');
$nombre = trim($_POST['nombre'] ?? '');
$correo = trim($_POST['correo'] ?? '');

// Validaciones
if (empty($dni) || empty($nombre) || empty($correo)) {
    echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios']);
    exit;
}

try {
    // 1. Verificar si el DNI ya existe
    $stmt = $pdo->prepare("SELECT DNI FROM usuario WHERE DNI = :dni");
    $stmt->execute(['dni' => $dni]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Este DNI ya está registrado.']);
        exit;
    }
    
    // 2. Iniciar transacción
    $pdo->beginTransaction();
    
    // 3. Insertar en tabla usuario (rol 3 = client, contrasena como 'contrasena')
    $stmt = $pdo->prepare("
        INSERT INTO usuario (DNI, id_rol, nombre_usuario, correo, contrasena) 
        VALUES (:dni, 3, :nombre, :correo, '')
    ");
    
    $stmt->execute([
        'dni' => $dni,
        'nombre' => $nombre,
        'correo' => $correo
    ]);
    
    // 4. Obtener el ID generado (funciona porque id_usuario es AUTO_INCREMENT)
    $user_id = $pdo->lastInsertId();
    
    // 5. Insertar en tabla cliente (solo requiere el id_cliente)
    $stmt = $pdo->prepare("INSERT INTO cliente (id_cliente) VALUES (:id)");
    $stmt->execute(['id' => $user_id]);
    
    // 6. Confirmar transacción
    $pdo->commit();
    
    // 7. Iniciar sesión automáticamente
    $_SESSION['logged_in'] = true;
    $_SESSION['user_id'] = $user_id;
    $_SESSION['rol'] = 'client';
    $_SESSION['nombre'] = $nombre;
    
    echo json_encode([
        'success' => true,
        'message' => 'Registro exitoso. ¡Bienvenido!',
        'nombre' => $nombre,
        'rol' => 'client'
    ]);
    
} catch (PDOException $e) {
    // Revertir transacción en caso de error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Devolvemos el error real de MySQL
    echo json_encode([
        'success' => false,
        'message' => 'Error al registrar el usuario. Intenta nuevamente.',
        'debug' => $e->getMessage()
    ]);
}
?>