<?php
// --- Conexión a la base de datos ---
$conexion = new mysqli("localhost", "root", "", "supermercado");
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

session_start();

// --- ID del cliente (simulado mientras no hay login) ---
$dni_cliente = 12345678; // Podés cambiarlo cuando tengas login

// --- Buscar carrito activo del cliente ---
$carrito = $conexion->query("SELECT * FROM carrito WHERE DNI_Cliente = '$dni_cliente' AND Estado = 'pendiente' LIMIT 1");
if ($carrito->num_rows > 0) {
    $carrito = $carrito->fetch_assoc();
    $id_carrito = $carrito['Id_Carrito'];
} else {
    // Si no existe carrito, se crea uno nuevo
    $conexion->query("INSERT INTO carrito (DNI_Cliente, Fecha_Agregado, Estado, Costo_Envio, Total_Final)
                      VALUES ('$dni_cliente', NOW(), 'pendiente', 0, 0)");
    $id_carrito = $conexion->insert_id;
}

// --- Eliminar producto del carrito ---
if (isset($_GET['eliminar'])) {
    $id_detalle = $_GET['eliminar'];
    $conexion->query("DELETE FROM detalle_carrito WHERE Id_Detalle_Carrito = $id_detalle");
}

// --- Obtener productos del carrito ---
$sql = "SELECT dc.Id_Detalle_Carrito, dc.Cantidad, dc.Precio_Unitario, 
               p.Nombre AS producto, (dc.Cantidad * dc.Precio_Unitario) AS subtotal
        FROM detalle_carrito dc
        JOIN opciones_producto op ON dc.Id_Opcion_Producto = op.Id_Opcion_Producto
        JOIN productos p ON op.Id_Producto = p.Id_Producto
        WHERE dc.Id_Carrito = $id_carrito";

$resultado = $conexion->query($sql);

// --- Calcular total ---
$total = 0;
while ($row = $resultado->fetch_assoc()) {
    $total += $row['subtotal'];
    $items[] = $row;
}

// --- Actualizar total en tabla carrito ---
$costo_envio = 1200; // Ejemplo fijo
$total_final = $total + $costo_envio;
$conexion->query("UPDATE carrito SET Costo_Envio = $costo_envio, Total_Final = $total_final WHERE Id_Carrito = $id_carrito");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Carrito</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<header>
    <h1><i class="fas fa-shopping-cart"></i> Tu Carrito</h1>
    <a href="index.html">← Volver al inicio</a>
</header>

<main class="carrito-container">
    <?php if (!empty($items)): ?>
        <table class="tabla-carrito">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Cantidad</th>
                    <th>Precio Unitario</th>
                    <th>Subtotal</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['producto']) ?></td>
                    <td><?= $item['Cantidad'] ?></td>
                    <td>$<?= number_format($item['Precio_Unitario'], 2) ?></td>
                    <td>$<?= number_format($item['subtotal'], 2) ?></td>
                    <td><a href="carrito.php?eliminar=<?= $item['Id_Detalle_Carrito'] ?>" class="btn-eliminar">❌</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="resumen">
            <p><strong>Costo de envío:</strong> $<?= number_format($costo_envio, 2) ?></p>
            <p><strong>Total a pagar:</strong> $<?= number_format($total_final, 2) ?></p>
            <button class="btn-comprar">Finalizar compra</button>
        </div>
    <?php else: ?>
        <p>Tu carrito está vacío 🛒</p>
    <?php endif; ?>
</main>

</body>
</html>
