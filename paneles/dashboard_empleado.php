<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

include '../login/control_acceso.php';
include '../db.php'; 

// 1. VERIFICACIÓN DE ACCESO
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'empleado') {
    header("Location: login/check_session.php");
    exit();
}

$nombre_usuario = $_SESSION['nombre'];

// 2. CONSULTA PARA OBTENER VENTAS PENDIENTES
// (Esto se usará para llenar la tabla inicial)
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
    $error_db = "Error al cargar ventas: " . $e->getMessage();
    $ventas_pendientes = [];
}

// ... HTML COMIENZA AQUÍ ...

// En la sección de la tabla de Pedidos/Ventas pendientes:
?>
<section class="widget pedidos-pendientes">
    <h3>Ventas Pendientes a Procesar</h3>
    <?php if (isset($error_db)): ?>
        <p class="error"><?php echo $error_db; ?></p>
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
                <tr id="venta-<?php echo $venta['id_venta']; ?>">
                    <td><?php echo htmlspecialchars($venta['id_venta']); ?></td>
                    <td><?php echo htmlspecialchars($venta['nombre_cliente']); ?></td>
                    <td><?php echo htmlspecialchars($venta['Fecha_Venta']); ?></td>
                    <td><span class="status-<?php echo strtolower($venta['Estado_Venta']); ?>"><?php echo htmlspecialchars($venta['Estado_Venta']); ?></span></td>
                    <td>
                        <button 
                            onclick="procesarVenta(<?php echo $venta['id_venta']; ?>, 'Preparando')"
                            class="btn-primary">
                            Iniciar Preparación
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>