<?php
/**
 * Ejemplo de uso del ProductoCodigoHelper
 * Muestra cómo usar el sistema de códigos de producto en otros archivos
 */

// Incluir la conexión a la base de datos (siguiendo el patrón del proyecto)
require_once __DIR__ . '/../carrito/db.php';

// Incluir la clase helper
require_once __DIR__ . '/ProductoCodigoHelper.php';

echo "<h1>Sistema de Códigos de Producto - Ejemplos</h1>";

try {
    echo "<h2>1. Codificación de Productos</h2>";
    
    // Ejemplo básico de codificación
    $codigo1 = ProductoCodigoHelper::codificar('A001', 15, '|');
    echo "<p>Producto A001, cantidad 15: <strong>$codigo1</strong></p>";
    
    $codigo2 = ProductoCodigoHelper::codificar('B002', 3, '#');
    echo "<p>Producto B002, cantidad 3: <strong>$codigo2</strong></p>";
    
    $codigo3 = ProductoCodigoHelper::codificar('C003', 125, '*');
    echo "<p>Producto C003, cantidad 125: <strong>$codigo3</strong></p>";
    
    echo "<h2>2. Decodificación de Productos</h2>";
    
    // Decodificar los códigos anteriores
    $decodificado1 = ProductoCodigoHelper::decodificar($codigo1);
    echo "<p>Código $codigo1 decodificado:</p>";
    echo "<ul>";
    echo "<li>Producto: " . $decodificado1['codigo_producto'] . "</li>";
    echo "<li>Cantidad: " . $decodificado1['cantidad'] . "</li>";
    echo "<li>Separador: " . $decodificado1['separador'] . "</li>";
    echo "</ul>";
    
    echo "<h2>3. Validación de Códigos</h2>";
    
    $codigosTest = ['A001015|', 'B002003#', 'INVALID', 'C003125*', 'X999999Z'];
    
    foreach ($codigosTest as $codigo) {
        $esValido = ProductoCodigoHelper::esValido($codigo);
        $estado = $esValido ? '✅ Válido' : '❌ Inválido';
        echo "<p>$codigo - $estado</p>";
    }
    
    echo "<h2>4. Codificación Múltiple</h2>";
    
    // Ejemplo con múltiples productos (como en una venta)
    $productosVenta = [
        ['codigo' => 'A001', 'cantidad' => 2],
        ['codigo' => 'B002', 'cantidad' => 1],
        ['codigo' => 'C003', 'cantidad' => 5],
        ['codigo' => 'A004', 'cantidad' => 3]
    ];
    
    $codigosVenta = ProductoCodigoHelper::codificarMultiples($productosVenta, '|');
    
    echo "<p>Venta con múltiples productos:</p>";
    echo "<ul>";
    foreach ($codigosVenta as $codigo) {
        echo "<li>$codigo</li>";
    }
    echo "</ul>";
    
    echo "<h2>5. Agrupación por Producto</h2>";
    
    // Simular varios códigos de la misma venta
    $codigosFactura = ['A001002|', 'B002001|', 'A001003|', 'C003001|', 'A001001|'];
    
    $agrupados = ProductoCodigoHelper::agruparPorProducto($codigosFactura);
    
    echo "<p>Productos agrupados en factura:</p>";
    foreach ($agrupados as $grupo) {
        echo "<div style='border: 1px solid #ddd; padding: 10px; margin: 5px;'>";
        echo "<strong>Producto: " . $grupo['codigo_producto'] . "</strong><br>";
        echo "Cantidad total: " . $grupo['cantidad_total'] . "<br>";
        echo "Códigos originales: " . implode(', ', $grupo['codigos_originales']) . "<br>";
        echo "</div>";
    }
    
    echo "<h2>6. Integración con Base de Datos</h2>";
    
    // Ejemplo de cómo usar con la base de datos
    $stmt = $pdo->query("SELECT Id_Producto, codigo_producto, Nombre_Producto FROM producto LIMIT 5");
    $productos = $stmt->fetchAll();
    
    echo "<p>Productos desde la base de datos:</p>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Código</th><th>Nombre</th><th>Código de Venta (Cantidad 1)</th></tr>";
    
    foreach ($productos as $producto) {
        $codigoVenta = ProductoCodigoHelper::codificar($producto['codigo_producto'], 1, '|');
        echo "<tr>";
        echo "<td>" . $producto['Id_Producto'] . "</td>";
        echo "<td>" . $producto['codigo_producto'] . "</td>";
        echo "<td>" . htmlspecialchars($producto['Nombre_Producto']) . "</td>";
        echo "<td><strong>$codigoVenta</strong></td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2>7. Cálculo de Totales</h2>";
    
    $codigosParaTotal = ['A001015|', 'B002003|', 'C003125|', 'A001005|'];
    $totalProductos = ProductoCodigoHelper::calcularTotalProductos($codigosParaTotal);
    
    echo "<p>Códigos: " . implode(', ', $codigosParaTotal) . "</p>";
    echo "<p><strong>Total de productos: $totalProductos unidades</strong></p>";
    
} catch (Exception $e) {
    echo "<div style='color: red; border: 1px solid red; padding: 10px; margin: 10px 0;'>";
    echo "<h3>Error:</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ejemplo - Sistema de Códigos</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 20px; 
            line-height: 1.6;
        }
        h1 { color: #007bff; }
        h2 { 
            color: #28a745; 
            border-bottom: 2px solid #28a745; 
            padding-bottom: 5px; 
            margin-top: 30px;
        }
        table { 
            border-collapse: collapse; 
            width: 100%; 
            margin: 10px 0;
        }
        th, td { 
            border: 1px solid #ddd; 
            padding: 8px; 
            text-align: left;
        }
        th { 
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .codigo { 
            font-family: 'Courier New', monospace; 
            background-color: #f8f9fa; 
            padding: 2px 4px;
            border-radius: 3px;
        }
        ul { margin: 10px 0; }
        li { margin: 5px 0; }
    </style>
</head>
<body>
    <p><a href="../paneles/dashboard_admin.php">← Volver al Panel Admin</a></p>
    <p><a href="index.html">Ver Sistema de Reportes</a></p>
</body>
</html>