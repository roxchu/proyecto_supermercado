<?php
session_start();

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// 1. Limpiar todas las variables de sesión
$_SESSION = array();

// 2. Eliminar la cookie de sesión del navegador
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/', '', false, true);
}

// 3. Destruir el archivo de sesión en el servidor
session_destroy();

if ($isAjax) {
    // Petición AJAX: responde JSON
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, must-revalidate');
    echo json_encode([
        'success' => true, 
        'message' => 'Sesión cerrada correctamente',
        'logged_in' => false,
        'redirect' => '/proyecto_supermercado/index.html'
    ]);
    exit;
} else {
    // Acceso normal: redirige
    header("Location: /proyecto_supermercado/index.html");
    exit;
}
?>