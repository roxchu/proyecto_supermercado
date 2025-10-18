<?php
// check_session.php - Verificar si hay una sesión activa
session_start();
header('Content-Type: application/json; charset=utf-8');

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    echo json_encode([
        'logged_in' => true,
        'nombre' => $_SESSION['nombre'] ?? 'Usuario',
        'rol' => $_SESSION['rol'] ?? 'client'
    ]);
} else {
    echo json_encode([
        'logged_in' => false
    ]);
}
?>