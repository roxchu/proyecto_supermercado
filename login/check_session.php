<?php
// login/check_session.php - Verificar si hay una sesión activa (AJAX)

session_start();
header('Content-Type: application/json; charset=utf-8');

// NO redirigir - solo devolver el estado de la sesión para AJAX
// --- Lógica de Comprobación de Sesión ---

if (isset($_SESSION['rol']) && isset($_SESSION['user_id'])) {
    
    // Normalizamos el nombre a la variable que usa tu login.php ('nombre' o 'nombre_usuario')
    $nombre = $_SESSION['nombre'] ?? $_SESSION['nombre_usuario'] ?? 'Usuario';
    
    // El rol ya debe estar normalizado a 'empleado' o 'admin' por login.php
    $rol = strtolower($_SESSION['rol']); 
    
    $response = [
        'logged_in' => true,
        'user_id' => $_SESSION['user_id'],
        'nombre' => $nombre,
        'rol' => $rol,
        'id_rol' => $_SESSION['id_rol'] ?? null
    ];
} else {
    // Sesión no activa o faltan datos esenciales
    $response = ['logged_in' => false];
}

// Única salida JSON
echo json_encode($response);

// Aseguramos que el script finalice aquí
exit;
?>