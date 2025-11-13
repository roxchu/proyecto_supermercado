<?php
// login/verificar_rol.php

// Aseguramos que la sesión esté iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function verificar_rol(array $roles_permitidos) {
    // 1. Definir la URL base si no está definida (Asume que está en el directorio raíz)
    // Cambia esto si tu estructura de carpetas es diferente
    if (!defined('BASE_URL')) {
        // En un entorno de desarrollo, a veces es más simple definirla aquí:
        // Por ejemplo, si tu proyecto se llama 'proyecto_supermercado'
        define('BASE_URL', '/proyecto_supermercado/'); 
    }

    
    // 2. Verificar si hay sesión de rol
    if (!isset($_SESSION['rol']) || empty($_SESSION['rol'])) {
        // No hay sesión activa, redirigir a login
        header('Location: ' . BASE_URL . 'index.html'); // 
        exit;
    }

    // 3. Normalizar el rol actual de la sesión (será 'empleado', 'admin', 'owner', o 'cliente')
    $rol_actual = strtolower($_SESSION['rol']);

    // Si el rol es 'owner', lo tratamos como 'admin' para permisos
    if ($rol_actual === 'owner') {
        $rol_actual = 'admin';
    }

    // 4. Verificar si el rol actual está en la lista de permitidos (en español)
    if (in_array($rol_actual, $roles_permitidos)) {
        return true; // Acceso concedido
    } else {
        // Acceso denegado, redirigir a sin_permiso.php
        header('Location: ' . BASE_URL . 'login/sin_permiso.php');
        exit;
    }
}
?>