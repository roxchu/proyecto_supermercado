<?php
// control_acceso.php
// Se incluye al inicio de cada página protegida.

function verificar_rol($rol_requerido) {
    // Si la sesión no ha sido iniciada, lo hacemos ahora
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // 1. Verificar si está logueado
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        header('Location: login.html');
        exit;
    }

    $rol_actual = $_SESSION['rol'];
    
    // 2. Verificar el Rol
    // Aseguramos que $rol_requerido sea un array para manejar uno o varios roles
    $roles_permitidos = is_array($rol_requerido) ? $rol_requerido : [$rol_requerido];

    if (!in_array($rol_actual, $roles_permitidos)) {
        // Acceso denegado
        header('Location: sin_permiso.php'); 
        exit;
    }
    // Si el rol es correcto, la ejecución del script llamante continúa
}
?>