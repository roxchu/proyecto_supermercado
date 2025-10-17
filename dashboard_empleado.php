<?php
include 'control_acceso.php';

// Permite 'admin' O 'employee'
verificar_rol(['admin', 'employee']); 
?>
<!DOCTYPE html>
<html lang="es">
<head><title>Panel de Empleado</title></head>
<body>
    <h1>Panel de EMPLEADOS 🛒</h1>
    <p>Bienvenido, <?php echo $_SESSION['nombre']; ?>. Acceso a gestión de inventario y pedidos.</p>
    <p>Tu Rol es: <?php echo $_SESSION['rol']; ?></p>
    <a href="logout.php">Cerrar Sesión</a>
</body>
</html>