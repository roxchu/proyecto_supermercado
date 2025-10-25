<?php
// logout.php
session_start();

// 1. Limpiar todas las variables de sesión
$_SESSION = array();

// 2. Eliminar la cookie de sesión del navegador
if (isset($_COOKIE[session_name()])) {
    setcookie(
        session_name(), 
        '', 
        time() - 3600, 
        '/',
        '', 
        false, 
        true
    );
}

// 3. Destruir el archivo de sesión en el servidor
session_destroy();

// 4. Responder según el tipo de petición
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');
echo json_encode([
    'success' => true, 
    'message' => 'Sesión cerrada correctamente',
    'logged_in' => false
]);
exit;
?>