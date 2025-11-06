<?php
// dashboard_empleado.php

// Mantenemos ob_start() para la robustez en la gestión de headers
ob_start();

// 1. INICIAR LA SESIÓN
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// 2. Requerir el control y la conexión a la base de datos
echo '<pre>';
echo "Ruta actual: " . __DIR__ . PHP_EOL;
echo "Buscando: " . __DIR__ . '/../../config/database.php' . PHP_EOL;
echo file_exists(__DIR__ . '/../../config/database.php') ? '✅ Existe' : '❌ No existe';
echo '</pre>';
// exit;   <-- quitá esto cuando termines de probar

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../login/verificar_rol.php';
// 3. ÚNICA LLAMADA Y DEFINITIVA para verificar el rol
// Permite el acceso a 'admin' Y 'empleado'.
verificar_rol(['admin', 'empleado']); 

// A partir de aquí, el acceso está garantizado
$nombre_usuario = $_SESSION['nombre'] ?? $_SESSION['nombre_usuario'] ?? 'Usuario';

// Consulta para obtener ventas pendientes
try {
    $sql = "SELECT 
                v.id_venta, 
                v.fecha_venta, 
                v.Total_Venta,  
                v.Estado,
                -- Seleccionamos el nombre del cliente desde la tabla 'usuario'
                u.nombre_usuario AS nombre_cliente 
            FROM venta v
            JOIN usuario u ON v.id_cliente = u.id_usuario 
            
            WHERE v.Estado = 'Pendiente' 
            ORDER BY v.fecha_venta ASC
            LIMIT 10";
            
    $stmt_ventas = $pdo->query($sql);
    $ventas_pendientes = $stmt_ventas->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error DB en dashboard_empleado.php: " . $e->getMessage());
    $error_db = "Error al cargar ventas: " . $e->getMessage();
    $ventas_pendientes = [];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Empleado - Gestión de Pedidos</title>
    <link rel="stylesheet" href="../paneles/dashboard_empleado.css"> 
</head>
<body>
    
    <div class="sidebar">
        <div class="sidebar-header">
            Panel Empleado
        </div>
        <ul>
            <li><a href="dashboard_empleado.php" class="active">Gestión de Pedidos</a></li>
            <li><a href="../login/logout.php">Cerrar Sesión</a></li>
            <li><a href="../index.html">Volver al Inicio</a></li>
        </ul>
    </div>

    <div class="main-content">
        <header class="main-header">
            <h1>Gestión de Pedidos</h1>
            <p>Bienvenido, <?php echo htmlspecialchars($nombre_usuario); ?></p>
        </header>

        <section class="widget pedidos-pendientes">
            <h3>Ventas Pendientes a Procesar</h3>
            
            <?php if (isset($error_db)): ?>
                <p class="error"><?php echo htmlspecialchars($error_db); ?></p>
            <?php elseif (empty($ventas_pendientes)): ?>
                <p class="success-message">¡Excelente! No hay ventas pendientes en este momento. ✅</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID Venta</th>
                                <th>Cliente</th>
                                <th>Fecha</th>
                                <th>Monto</th>
                                <th>Estado</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ventas_pendientes as $venta): ?>
                            <tr id="venta-<?php echo htmlspecialchars($venta['id_venta']); ?>">
                                <td><?php echo htmlspecialchars($venta['id_venta']); ?></td>
                                <td><?php echo htmlspecialchars($venta['nombre_cliente']); ?></td>
                                <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($venta['Fecha_Venta']))); ?></td>
                                <td>$<?php echo htmlspecialchars(number_format($venta['Total_Venta'], 2, ',', '.')); ?></td>
                                <td><span class="status-tag status-<?php echo strtolower(htmlspecialchars($venta['Estado'])); ?>"><?php echo htmlspecialchars($venta['Estado']); ?></span></td>
                                <td>
                                    <button onclick="procesarVenta(<?php echo (int)$venta['id_venta']; ?>, 'Preparando')" class="btn-primary">
                                        Iniciar Preparación
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

    </div> 

</body>
</html>