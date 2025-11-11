<?php
// Permitir el uso de sesión entre subcarpetas

session_start();
header('Content-Type: application/json; charset=utf-8');

// ---------------------------------------------
// CONFIGURACIÓN DE LA BASE DE DATOS
// ---------------------------------------------
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

// ---------------------------------------------
// LÓGICA DE REGISTRO
// ---------------------------------------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$dni = trim($_POST['dni'] ?? '');
$nombre = trim($_POST['nombre'] ?? '');
$correo = trim($_POST['correo'] ?? '');

if (empty($dni) || empty($nombre) || empty($correo)) {
    echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios']);
    exit;
}

try {
    // Verificar si ya existe el DNI
    $stmt = $pdo->prepare("SELECT DNI FROM usuario WHERE DNI = :dni");
    $stmt->execute(['dni' => $dni]);
    $existe = $stmt->fetch();

    if ($existe) {
        echo json_encode(['success' => false, 'message' => 'Este DNI ya está registrado.']);
        exit;
    }


    $pdo->beginTransaction();

    // Insertar en usuario
    $stmt = $pdo->prepare("
        INSERT INTO usuario (DNI, id_rol, nombre_usuario, correo, contrasena)
        VALUES (:dni, 3, :nombre, :correo, '')
    ");
    $stmt->execute([
        'dni' => $dni,
        'nombre' => $nombre,
        'correo' => $correo
    ]);

    $user_id = $pdo->lastInsertId();

    $pdo->commit();

    // Iniciar sesión automática
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
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'message' => 'Error al registrar el usuario.',
        'debug' => $e->getMessage()
    ]);
}
?>
