<?php
// login.php

session_start();
header('Content-Type: application/json; charset=utf-8');

// ---------------------------------------------
// CONFIGURACIÓN DE LA BASE DE DATOS
// ---------------------------------------------
// Asumo que 'db.php' está en el mismo directorio. Si no, lo reescribimos aquí:
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
        // --- 1. Éxito: Configurar la sesión ---
        $_SESSION['logged_in'] = true;
        $_SESSION['user_id'] = $usuario['id_usuario'];
        $_SESSION['rol'] = $usuario['nombre_rol'];
        $_SESSION['nombre'] = $usuario['nombre_usuario'];
        
        // --- 2. Definir la URL de redirección ---
        $redirect_url = 'index.html'; // Por defecto para clientes
        
        if ($usuario['nombre_rol'] === 'admin') {
            $redirect_url = 'paneles/dashboard_admin.php'; // Panel de admin
        } elseif ($usuario['nombre_rol'] === 'empleado') {
            $redirect_url = 'paneles/dashboard_empleado.php';
        }

        // --- 3. Enviar la respuesta JSON final y salir ---
        echo json_encode([
            'success' => true,
            'message' => 'Inicio de sesión exitoso.',
            'rol' => $usuario['nombre_rol'],
            'nombre' => $usuario['nombre_usuario'],
            'redirect' => $redirect_url // URL usada por script.js
        ]);
        
    } else {
        // --- 4. Fracaso: DNI no encontrado ---
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

exit; // Aseguramos que no haya más salidas después de la respuesta JSON.
?>