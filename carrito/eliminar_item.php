<?php
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.html');
    exit;
}

$id_producto = isset($_GET['id_producto']) ? intval($_GET['id_producto']) : 0;
$id_usuario = $_SESSION['user_id'] ?? null;

if ($id_producto > 0 && $id_usuario) {
    // buscar carrito pendiente (DNI_Cliente + Estado 'Pendiente')
    $stmt = $pdo->prepare("SELECT Id_Carrito FROM carrito WHERE DNI_Cliente = ? AND Estado = 'Pendiente' LIMIT 1");
    $stmt->execute([$id_usuario]);
    $car = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($car) {
        $stmt = $pdo->prepare("DELETE FROM detalle_carrito WHERE Id_Carrito = ? AND Id_Producto = ?");
        $stmt->execute([$car['Id_Carrito'], $id_producto]);
    }
}

header('Location: carrito.php');
exit;
