<?php
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.html');
    exit;
}

$id_usuario = $_SESSION['user_id'] ?? null;
if (!$id_usuario) {
    header('Location: ../index.html');
    exit;
}

 $stmt = $pdo->prepare("SELECT c.Id_Carrito FROM carrito c WHERE c.DNI_Cliente = ? AND c.Estado = 'Pendiente' LIMIT 1");
 $stmt->execute([$id_usuario]);
 $carritoRow = $stmt->fetch(PDO::FETCH_ASSOC);

$items = [];
$total = 0;
if ($carritoRow) {
    $stmt = $pdo->prepare(
        "SELECT p.Id_Producto, p.Nombre_Producto AS Nombre, p.precio_actual AS Precio, p.imagen_url AS imagen, dc.Cantidad
         FROM detalle_carrito dc
         JOIN producto p ON p.Id_Producto = dc.Id_Producto
         WHERE dc.Id_Carrito = ?"
    );
    $stmt->execute([$carritoRow['Id_Carrito']]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($items as $it) $total += $it['Precio'] * $it['Cantidad'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Tu Carrito</title>
    <link rel="stylesheet" href="../styles.css">
</head>
<body>
<h2>🛒 Tu Carrito de Compras</h2>
<div class="carrito-contenedor">
    <?php if (empty($items)): ?>
        <p>Tu carrito está vacío. <a href="../index.html">Seguir comprando</a></p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Imagen</th>
                    <th>Precio</th>
                    <th>Cantidad</th>
                    <th>Subtotal</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $item):
                $subtotal = $item['Precio'] * $item['Cantidad'];
            ?>
                <tr>
                    <td><?= htmlspecialchars($item['Nombre']) ?></td>
                    <td><img src="<?= htmlspecialchars($item['imagen']) ?>" width="80" onerror="this.src='https://via.placeholder.com/80x80'" ></td>
                    <td>$<?= number_format($item['Precio'], 2) ?></td>
                    <td><?= $item['Cantidad'] ?></td>
                    <td>$<?= number_format($subtotal, 2) ?></td>
                    <td>
                        <a href="eliminar_item.php?id_producto=<?= $item['Id_Producto'] ?>">🗑️</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <h3>Total: $<?= number_format($total, 2) ?></h3>
        <div>
            <a href="../productos.php">⬅️ Seguir comprando</a> |
            <a href="pago.php">Ir al pago 💳</a>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
