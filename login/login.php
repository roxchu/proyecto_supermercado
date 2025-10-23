<?php
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
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos.']);
    exit;
}

// ---------------------------------------------
// LÓGICA DE AUTENTICACIÓN
// ---------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dni'])) {
    $dni = trim($_POST['dni']);

    if (empty($dni)) {
        echo json_encode(['success' => false, 'message' => 'El DNI no puede estar vacío.']);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT u.id_usuario, u.nombre_usuario, r.nombre_rol
        FROM usuario u
        JOIN rol r ON u.id_rol = r.id_rol
        WHERE u.DNI = ?
    ");
    $stmt->execute([$dni]);
    $usuario = $stmt->fetch();

    if ($usuario) {
        // Usuario encontrado
        $_SESSION['logged_in'] = true;
        $_SESSION['user_id'] = $usuario['id_usuario'];
        $_SESSION['rol'] = $usuario['nombre_rol'];
        $_SESSION['nombre'] = $usuario['nombre_usuario'];

        echo json_encode([
            'success' => true,
            'message' => 'Inicio de sesión exitoso.',
            'rol' => $usuario['nombre_rol'],
            'nombre' => $usuario['nombre_usuario']
        ]);
    } else {
        // No encontrado
        echo json_encode([
            'success' => false,
            'code' => 'USER_NOT_FOUND',
            'message' => 'Tu DNI no está registrado. ¿Deseas crear una cuenta?'
        ]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Solicitud no válida.']);
}
?>
