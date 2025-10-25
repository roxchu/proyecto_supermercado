<?php
// logout.php
session_start();

// Destruir todas las variables de sesión
$_SESSION = array();

// Si se usa cookie de sesión, destruirla también
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destruir la sesión
session_destroy();

// Si es una petición AJAX/Fetch, devolver JSON
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Sesión cerrada correctamente']);
    exit;
}

// Si es una petición directa (navegación), redirigir
header('Location: index.html'); 
exit;
?>