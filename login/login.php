<?php
// login/login.php
// Permitir el uso de sesión entre subcarpetas

session_start();
header('Content-Type: application/json; charset=utf-8');

// ---------------------------------------------
// CONFIGURACIÓN DE LA BASE DE DATOS
// ---------------------------------------------
// Nota: Usar el include/require de 'db.php' si existe para no duplicar código
// require 'db.php'; 

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

// El bloque 'if ($user)' inicial fue eliminado por ser redundante y causar errores.

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dni'])) {
    $dni = trim($_POST['dni']);

    if (empty($dni)) {
        echo json_encode(['success' => false, 'message' => 'El DNI no puede estar vacío.']);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT u.id_usuario, u.nombre_usuario, r.nombre_rol, u.id_rol
        FROM usuario u
        JOIN rol r ON u.id_rol = r.id_rol
        WHERE u.DNI = ?
    ");
    $stmt->execute([$dni]);
    $usuario = $stmt->fetch();

    if ($usuario) {
        // --- 1. NORMALIZACIÓN DEL ROL ---
        $rol_normalizado = strtolower($usuario['nombre_rol']); // Obtiene 'admin', 'empleado' o 'client'

        // CORRECCIÓN CLAVE: Mapear según la tabla rol actual
        if ($usuario['id_rol'] == 1 || $rol_normalizado === 'admin') {
            $rol_final = 'admin';
        } elseif ($usuario['id_rol'] == 2 || $rol_normalizado === 'empleado') {
            $rol_final = 'empleado';
        } else {
            $rol_final = 'client'; // Asume rol 3 es client (según tu tabla)
        }

        // --- 2. Éxito: Configurar la sesión con el rol normalizado ---
        $_SESSION['logged_in'] = true;
        $_SESSION['user_id'] = $usuario['id_usuario'];
        $_SESSION['rol'] = $rol_final;
        $_SESSION['nombre'] = $usuario['nombre_usuario'];
        $_SESSION['id_rol'] = $usuario['id_rol'];
        $_SESSION['dni'] = (string)$dni;

        session_write_close();

        // --- 3. Definir la URL de redirección ---
        $redirect_url = 'index.html'; // Por defecto para clientes

        // ¡La lógica de redirección ahora compara contra el rol normalizado!
        if ($rol_final === 'admin') {
            $redirect_url = 'paneles/dashboard_admin.php'; // Panel de admin
        } elseif ($rol_final === 'empleado') {
            $redirect_url = 'paneles/dashboard_empleado.php';
        }

        // --- 4. Enviar la respuesta JSON final y salir ---
        echo json_encode([
            'success' => true,
            'message' => 'Inicio de sesión exitoso.',
            'rol' => $rol_final,
            'nombre' => $usuario['nombre_usuario'],
            'redirect' => $redirect_url
        ]);
    } else {
        // --- 5. Fracaso: DNI no encontrado ---
        echo json_encode([
            'success' => false,
            'code' => 'USER_NOT_FOUND',
            'message' => 'El DNI ingresado no está registrado.'
        ]);
    }
} else {
    // Si no es POST o falta el DNI
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Solicitud inválida.']);
}

exit;
