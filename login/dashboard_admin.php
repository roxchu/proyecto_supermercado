<?php
include 'control_acceso.php';

// SOLO permite 'admin'
verificar_rol('admin'); 
?>
<!DOCTYPE html>
<html lang="es">
<head><title>Panel de Administrador</title></head>
<body>
    <h1>Panel de ADMINISTRACIÓN 🛑</h1>
    <p>Bienvenido, <?php echo $_SESSION['nombre']; ?>. Tienes control total.</p>
    <p>Tu Rol es: <?php echo $_SESSION['rol']; ?></p>
    <a href="logout.php">Cerrar Sesión</a>
</body>
</html>