<?php
// carrito/ver_carrito.php
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.html');
    exit;
}

$id_usuario = $_SESSION['user_id'] ?? null;
if (!$id_usuario) {
    echo "<p>Usuario no identificado.</p>";
    exit;
}

$stmt = $pdo->prepare("SELECT c.Id_Carrito FROM carrito c WHERE c.DNI_Cliente = ? AND c.Estado = 'Pendiente' LIMIT 1");
$stmt->execute([$id_usuario]);
$carrito = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$carrito) {
    echo "<p>No tienes productos en tu carrito.</p>";
    exit;
}

$stmt = $pdo->prepare(
     "SELECT p.Id_Producto, p.Nombre_Producto AS Nombre, p.precio_actual AS Precio, dc.Cantidad, p.imagen_url AS imagen
      FROM detalle_carrito dc
      JOIN producto p ON p.Id_Producto = dc.Id_Producto
      WHERE dc.Id_Carrito = ?"
);
$stmt->execute([$carrito['Id_Carrito']]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total = 0;
foreach ($items as $i) {
    $total += $i['Precio'] * $i['Cantidad'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mi Carrito</title>
    <link rel="stylesheet" href="../styles.css">
</head>
<body>
<h1>Mi Carrito</h1>
<table border="1" cellpadding="10">
    <tr><th>Producto</th><th>Precio</th><th>Cantidad</th><th>Subtotal</th></tr>
    <?php foreach ($items as $i): ?>
    <tr>
        <td><?= htmlspecialchars($i['Nombre']) ?></td>
        <td>$<?= number_format($i['Precio'], 2) ?></td>
        <td><?= $i['Cantidad'] ?></td>
        <td>$<?= number_format($i['Precio'] * $i['Cantidad'], 2) ?></td>
    </tr>
    <?php endforeach; ?>
</table>

<h3>Total: $<?= number_format($total, 2) ?></h3>

<form action="pago.php" method="POST">
    <button type="submit">Ir a pagar</button>
</form>
</body>
</html>
