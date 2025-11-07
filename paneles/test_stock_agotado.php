<?php
// Script para establecer algunos productos con stock 0 para probar la funcionalidad de renovar stock
require_once '../carrito/db.php';

try {
    // Establecer stock 0 a algunos productos para pruebas
    $productos_agotados = [1, 3, 5]; // IDs de productos para agotar
    
    foreach ($productos_agotados as $id) {
        $stmt = $pdo->prepare("UPDATE producto SET Stock = 0 WHERE Id_Producto = ?");
        $stmt->execute([$id]);
    }
    
    echo "<h2>✅ Productos agotados para prueba:</h2>";
    
    // Mostrar productos con stock 0
    $stmt = $pdo->prepare("SELECT Id_Producto, Nombre_Producto, Stock FROM producto WHERE Stock = 0");
    $stmt->execute();
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($productos as $producto) {
        echo "<p>🔴 {$producto['Nombre_Producto']} (ID: {$producto['Id_Producto']}) - Stock: {$producto['Stock']}</p>";
    }
    
    echo "<br><a href='dashboard_empleado.php'>Ir al Panel del Empleado</a>";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>