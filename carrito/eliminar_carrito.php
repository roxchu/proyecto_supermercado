<?php
// eliminar_carrito.php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('HTTP/1.1 401 Unauthorized');
    echo "Debes iniciar sesión.";
    exit;
}

$id_opcion = isset($_REQUEST['id_opcion']) ? intval($_REQUEST['id_opcion']) : 0;
if ($id_opcion <= 0) {
    header('Location: carrito.php');
    exit;
}

if (isset($_SESSION['carrito'][$id_opcion])) {
    unset($_SESSION['carrito'][$id_opcion]);
}

// redirigir a la página del carrito
header('Location: carrito.php');
exit;
