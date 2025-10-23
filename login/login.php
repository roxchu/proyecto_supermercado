<?php
// login.php (Lógica de autenticación y sugerencia de registro)

session_start();
header('Content-Type: application/json');

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
} catch (\PDOException $e) {
     http_response_code(500);
     die(json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos.']));
}

// -----------------------------------------------------
// LÓGICA DE AUTENTICACIÓN
// -----------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dni'])) {
    
    // El campo de login en el HTML tiene name="dni"
    $dni = trim($_POST['dni']);

    if (empty($dni)) {
        echo json_encode(['success' => false, 'message' => 'El DNI no puede estar vacío.']);
        exit;
    }

    // Buscar el DNI en la tabla USUARIO y obtener su ROL
    $stmt = $pdo->prepare("
        SELECT 
            u.id_usuario, u.nombre_usuario, r.nombre_rol 
        FROM usuario u 
        JOIN rol r ON u.id_rol = r.id_rol 
        WHERE u.DNI = ?
    ");
    $stmt->execute([$dni]);
    $usuario = $stmt->fetch();

    if ($usuario) {
        // USUARIO ENCONTRADO - Iniciar sesión
        $user_id = $usuario['id_usuario'];
        $rol = $usuario['nombre_rol'];
        $nombre = $usuario['nombre_usuario'];
        
        // Iniciar sesión
        $_SESSION['logged_in'] = true;
        $_SESSION['user_id'] = $user_id;
        $_SESSION['rol'] = $rol;
        $_SESSION['nombre'] = $nombre;

        // Retornar éxito al frontend
        echo json_encode([
            'success' => true,
            'message' => "Inicio de sesión exitoso.",
            'rol' => $rol,
            'nombre' => $nombre
        ]);

    } else {
        // USUARIO NO ENCONTRADO - Devolvemos un código específico
        echo json_encode([
            'success' => false,
            'code' => 'USER_NOT_FOUND', 
            'message' => 'Tu DNI no está registrado. ¿Deseas crear una cuenta?'
        ]);
        exit;
    }
}
?>