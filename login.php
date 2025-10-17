<?php
// login.php (Lógica de autenticación y registro)

session_start();
header('Content-Type: application/json');

// -----------------------------------------------------
// CONFIGURACIÓN DE LA BASE DE DATOS - AJUSTAR ESTO
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
// LÓGICA DE AUTENTICACIÓN Y REGISTRO CENTRALIZADA
// -----------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dni'])) {
    
    $dni = trim($_POST['dni']);

    if (empty($dni)) {
        echo json_encode(['success' => false, 'message' => 'El DNI no puede estar vacío.']);
        exit;
    }

    $rol_cliente_id = 3; // ID del rol 'client' (de la tabla `rol`)

    // 1. Buscar el DNI en la tabla USUARIO y obtener su ROL
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
        // A. USUARIO ENCONTRADO (Empleado o Cliente recurrente)
        $user_id = $usuario['id_usuario'];
        $rol = $usuario['nombre_rol'];
        $nombre = $usuario['nombre_usuario'];
        
        // NOTA: Aquí se añadiría la verificación de contraseña si fuera necesario para empleados.

    } else {
        // B. USUARIO NO ENCONTRADO -> REGISTRAR COMO NUEVO CLIENTE
        
        $nombre = 'Cliente ' . $dni;
        // Contraseña por defecto (MUY IMPORTANTE: En producción usar hashing seguro)
        $contraseña_default = 'sin_pass_hashed'; 
        
        try {
            $pdo->beginTransaction();

            // 1. Insertar en la tabla `usuario`
            $stmt_u = $pdo->prepare("
                INSERT INTO usuario (DNI, id_rol, nombre_usuario, correo, contraseña) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt_u->execute([$dni, $rol_cliente_id, $nombre, $dni.'@temp.com', $contraseña_default]); 
            
            $user_id = $pdo->lastInsertId();
            $rol = 'client';

            // 2. Insertar en la tabla `cliente` para completar el registro de cliente
            $stmt_c = $pdo->prepare("INSERT INTO cliente (id_cliente) VALUES (?)");
            $stmt_c->execute([$user_id]); 
            
            $pdo->commit();

        } catch (PDOException $e) {
            $pdo->rollBack();
            http_response_code(500);
            die(json_encode(['success' => false, 'message' => 'Error al registrar el nuevo cliente.']));
        }
    }

    // 3. INICIO DE SESIÓN EXITOSO
    
    $_SESSION['logged_in'] = true;
    $_SESSION['user_id'] = $user_id;
    $_SESSION['rol'] = $rol; // CLAVE
    $_SESSION['nombre'] = $nombre;

    // Retornar éxito al frontend
    echo json_encode([
        'success' => true,
        'message' => "Inicio de sesión exitoso. Rol: $rol",
        'rol' => $rol,
        'nombre' => $nombre // Enviamos el nombre para el saludo en el frontend
    ]);

} else {
    http_response_code(400); 
    echo json_encode(['success' => false, 'message' => 'Petición inválida.']);
}
?>