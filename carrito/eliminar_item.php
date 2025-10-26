<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

$data = json_decode(file_get_contents("php://input"), true);
$id = $data['id'] ?? null;

if (!$id) {
    echo json_encode(['ok' => false, 'msg' => 'Falta ID de producto']);
    exit;
}

// Filtramos el carrito eliminando el producto indicado
$_SESSION['carrito'] = array_filter($_SESSION['carrito'], function ($item) use ($id) {
    return $item['Id_Producto'] != $id;
});

// Reindexamos el array
$_SESSION['carrito'] = array_values($_SESSION['carrito']);

echo json_encode(['ok' => true]);
