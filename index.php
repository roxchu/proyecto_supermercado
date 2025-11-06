<?php
/**
 * Router principal del proyecto
 * Maneja las rutas y redirecciona a los archivos correspondientes
 */

// Obtener la URI solicitada
$request = $_SERVER['REQUEST_URI'];
$path = parse_url($request, PHP_URL_PATH);

// Remover el prefijo del proyecto
$basePath = '/proyecto_supermercado';
if (strpos($path, $basePath) === 0) {
    $path = substr($path, strlen($basePath));
}

// Eliminar barras iniciales
$path = ltrim($path, '/');

// Rutas principales
switch ($path) {
    case '':
    case 'index.html':
        require_once 'public/index.html';
        break;
        
    case 'mostrar.php':
        require_once 'src/pages/mostrar.php';
        break;
        
    // APIs
    case 'productos.php':
        require_once 'src/api/productos/productos.php';
        break;
        
    case 'agregar_resena.php':
        require_once 'src/api/productos/agregar_resena.php';
        break;
        
    case 'agregar_carrito.php':
        require_once 'src/api/carrito/agregar_carrito.php';
        break;
        
    case 'obtener_carrito.php':
        require_once 'src/api/carrito/obtener_carrito.php';
        break;
        
    case 'eliminar_item.php':
        require_once 'src/api/carrito/eliminar_item.php';
        break;
        
    // Autenticación
    case 'login.php':
        require_once 'src/auth/login.php';
        break;
        
    case 'logout.php':
        require_once 'src/auth/logout.php';
        break;
        
    case 'registro.php':
        require_once 'src/auth/registro.php';
        break;
        
    case 'check_session.php':
        require_once 'src/auth/check_session.php';
        break;
        
    // Admin
    case 'dashboard_admin.php':
        require_once 'src/admin/dashboard_admin.php';
        break;
        
    case 'admin_actions.php':
        require_once 'src/admin/admin_actions.php';
        break;
        
    default:
        // Si no se encuentra la ruta, intentar servir archivo estático
        $filePath = __DIR__ . '/' . $path;
        if (file_exists($filePath) && is_file($filePath)) {
            // Determinar el tipo de contenido
            $ext = pathinfo($filePath, PATHINFO_EXTENSION);
            $mimeTypes = [
                'css' => 'text/css',
                'js' => 'application/javascript',
                'png' => 'image/png',
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'gif' => 'image/gif',
                'svg' => 'image/svg+xml'
            ];
            
            if (isset($mimeTypes[$ext])) {
                header('Content-Type: ' . $mimeTypes[$ext]);
            }
            
            readfile($filePath);
        } else {
            // 404 - Página no encontrada
            http_response_code(404);
            echo '<!DOCTYPE html>
<html>
<head>
    <title>404 - Página no encontrada</title>
    <meta charset="UTF-8">
</head>
<body>
    <h1>404 - Página no encontrada</h1>
    <p>La página que buscas no existe.</p>
    <a href="/proyecto_supermercado/">Volver al inicio</a>
</body>
</html>';
        }
        break;
}
?>