<?php
// check_session.php - Verificar si hay una sesión activa (AJAX)
// Salida JSON consistente con 'rol' y 'id_rol' y nombre del usuario.
session_start();
header('Content-Type: application/json; charset=utf-8');

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    echo json_encode([
        'logged_in' => true,
        // usamos 'nombre' para mostrar en la UI; intentamos nombre_usuario, luego nombre
        'nombre' => $_SESSION['nombre_usuario'] ?? $_SESSION['nombre'] ?? 'Usuario',
        // 'rol' preferible en forma textual; si no existe, devolvemos id_rol para que el cliente lo compruebe
        'rol' => $_SESSION['rol'] ?? null,
        'id_rol' => $_SESSION['id_rol'] ?? null
    ]);
} else {
    echo json_encode([
        'logged_in' => false
    ]);
}
?>