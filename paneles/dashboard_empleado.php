<?php
// Depuración activada temporalmente
ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Includes con rutas seguras relativas al archivo actual
require_once __DIR__ . '/../login/control_acceso.php';
require_once __DIR__ . '/../db.php';

// Intentamos verificar rol y capturamos cualquier error para evitar 500 silencioso
try {
    // Permitir sólo 'empleado' (o usar ['empleado','admin'] si también quieres que admin acceda)
    verificar_rol('empleado');
} catch (Throwable $e) {
    // Loguea el error y muestra un mensaje amigable (temporal para debug)
    error_log("Error en dashboard_empleado.php -> verificar_rol: " . $e->getMessage());
    http_response_code(500);
    echo "<h1>Error interno</h1>";
    echo "<p>Se produjo un error al verificar permisos. Mensaje: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// A partir de aquí la sesión debe estar iniciada por control_acceso.php
$nombre_usuario = $_SESSION['nombre'] ?? $_SESSION['nombre_usuario'] ?? 'Usuario';

// Consulta para obtener ventas pendientes
try {
    $sql = "SELECT 
                v.id_venta, 
                v.Fecha_Venta, 
                v.Total_Final, 
                v.Estado_Venta,
                c.nombre_cliente
            FROM venta v
            JOIN cliente c ON v.id_cliente = c.id_cliente
            WHERE v.Estado_Venta = 'Pendiente'
            ORDER BY v.Fecha_Venta ASC
            LIMIT 10";
            
    $stmt_ventas = $pdo->query($sql);
    $ventas_pendientes = $stmt_ventas->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error DB en dashboard_empleado.php: " . $e->getMessage());
    $error_db = "Error al cargar ventas: " . $e->getMessage();
    $ventas_pendientes = [];
}

// ... HTML / vista ...
?>
<section class="widget pedidos-pendientes">
    <h3>Ventas Pendientes a Procesar</h3>
    <?php if (isset($error_db)): ?>
        <p class="error"><?php echo htmlspecialchars($error_db); ?></p>
    <?php elseif (empty($ventas_pendientes)): ?>
        <p>¡Excelente! No hay ventas pendientes en este momento. ✅</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID Venta</th>
                    <th>Cliente</th>
                    <th>Fecha</th>
                    <th>Estado</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ventas_pendientes as $venta): ?>
                <tr id="venta-<?php echo htmlspecialchars($venta['id_venta']); ?>">
                    <td><?php echo htmlspecialchars($venta['id_venta']); ?></td>
                    <td><?php echo htmlspecialchars($venta['nombre_cliente']); ?></td>
                    <td><?php echo htmlspecialchars($venta['Fecha_Venta']); ?></td>
                    <td><span class="status-<?php echo strtolower(htmlspecialchars($venta['Estado_Venta'])); ?>"><?php echo htmlspecialchars($venta['Estado_Venta']); ?></span></td>
                    <td>
                        <button onclick="procesarVenta(<?php echo (int)$venta['id_venta']; ?>, 'Preparando')" class="btn-primary">
                            Iniciar Preparación
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>