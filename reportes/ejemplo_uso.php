<?php
/**
 * Ejemplo de uso del sistema de reportes
 * Muestra cómo integrar los reportes en otros archivos PHP del proyecto
 */

// Siempre incluir primero la conexión a la base de datos
require_once __DIR__ . '/../carrito/db.php';

// Luego incluir el control de acceso si es necesario
require_once __DIR__ . '/../login/control_acceso.php';

// Finalmente incluir la clase de reportes
require_once __DIR__ . '/ReportesVentas.php';

// Verificar permisos (solo admin o dueño pueden ver reportes)
verificar_rol(['admin', 'dueño']);

try {
    // Crear instancia del generador de reportes
    $reportes = new ReportesVentas($pdo);
    
    // Ejemplo 1: Reporte del día actual
    $reporteDiario = $reportes->reporteDiario();
    echo "<h2>Reporte del Día</h2>";
    echo "<p>Total ventas: $" . number_format($reporteDiario['total_ventas'], 2) . "</p>";
    echo "<p>Transacciones: " . $reporteDiario['cantidad_transacciones'] . "</p>";
    
    // Ejemplo 2: Productos más vendidos del mes
    $fechaInicio = date('Y-m-01'); // Primer día del mes
    $fechaFin = date('Y-m-d');     // Día actual
    
    $topProductos = $reportes->obtenerProductosMasVendidos($fechaInicio, $fechaFin, 5);
    
    echo "<h2>Top 5 Productos del Mes</h2>";
    foreach ($topProductos as $index => $producto) {
        echo "<div>";
        echo "<h4>" . ($index + 1) . ". " . htmlspecialchars($producto['Nombre_Producto']) . "</h4>";
        echo "<p>Código: " . htmlspecialchars($producto['codigo_producto']) . "</p>";
        echo "<p>Vendidos: " . $producto['total_vendido'] . " unidades</p>";
        echo "<p>Ingresos: $" . number_format($producto['ingresos_producto'], 2) . "</p>";
        echo "</div><hr>";
    }
    
    // Ejemplo 3: Estadísticas de empleados
    $estadisticasEmpleados = $reportes->obtenerEstadisticasEmpleados($fechaInicio, $fechaFin);
    
    echo "<h2>Rendimiento de Empleados (Ventas Presenciales)</h2>";
    foreach ($estadisticasEmpleados as $empleado) {
        echo "<div>";
        echo "<h4>" . htmlspecialchars($empleado['nombre_usuario']) . " - " . htmlspecialchars($empleado['Cargo']) . "</h4>";
        echo "<p>Ventas realizadas: " . $empleado['ventas_realizadas'] . "</p>";
        echo "<p>Total vendido: $" . number_format($empleado['total_vendido'], 2) . "</p>";
        echo "<p>Ticket promedio: $" . number_format($empleado['ticket_promedio'], 2) . "</p>";
        echo "</div><hr>";
    }
    
    // Ejemplo 4: Métricas de conversión (carritos virtuales)
    $metricas = $reportes->obtenerMetricasConversion($fechaInicio, $fechaFin);
    
    echo "<h2>Métricas de Conversión (Ventas Online)</h2>";
    echo "<p>Carritos creados: " . $metricas['carritos_creados'] . "</p>";
    echo "<p>Carritos completados: " . $metricas['carritos_completados'] . "</p>";
    echo "<p>Tasa de conversión: " . $metricas['tasa_conversion'] . "%</p>";
    echo "<p>Carritos abandonados: " . $metricas['carritos_abandonados'] . "</p>";
    
} catch (Exception $e) {
    echo "<div style='color: red;'>";
    echo "<h3>Error al generar reportes:</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ejemplo de Reportes</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h2 { color: #007bff; border-bottom: 2px solid #007bff; padding-bottom: 5px; }
        h4 { color: #333; margin: 10px 0 5px 0; }
        p { margin: 5px 0; }
        div { margin: 10px 0; }
        hr { border: 1px solid #eee; margin: 15px 0; }
    </style>
</head>
<body>
    <h1>Sistema de Reportes - Ejemplo de Uso</h1>
    <p><a href="../paneles/dashboard_admin.php">← Volver al Panel Admin</a></p>
    <p><a href="index.html">Ver Interfaz Completa de Reportes</a></p>
</body>
</html>