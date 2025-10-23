<?php
// carrito.php (vista)
session_start();
require_once 'db.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Redirigir al index y abrir modal de login (tu JS maneja modal)
    header('Location: index.html');
    exit;
}

$cart = $_SESSION['carrito'] ?? [];
$total = 0;
foreach ($cart as $it) $total += $it['Precio_Unitario'] * $it['cantidad'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Tu Carrito - SuperMarket</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <a href="index.html">← Volver</a>
        <h1>🛒 Tu Carrito</h1>
    </header>

    <main style="padding:20px;">
        <?php if (empty($cart)): ?>
            <p>Tu carrito está vacío. <a href="index.html">Seguir comprando</a></p>
        <?php else: ?>
            <form id="form-carrito" method="post" action="actualizar_carrito.php">
                <table border="1" cellpadding="8" style="border-collapse:collapse;width:100%;max-width:900px;">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Opción</th>
                            <th style="width:120px">Cantidad</th>
                            <th>Precio unitario</th>
                            <th>Subtotal</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cart as $id => $it): 
                            $subtotal = $it['Precio_Unitario'] * $it['cantidad'];
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($it['Nombre_Producto']); ?></td>
                                <td><?php echo htmlspecialchars($it['Nombre_Opcion']); ?></td>
                                <td>
                                    <input type="number" min="1" name="cantidad" value="<?php echo $it['cantidad']; ?>"
                                        data-id="<?php echo $id; ?>" style="width:70px">
                                    <button type="button" class="btn-actualizar" data-id="<?php echo $id; ?>">Actualizar</button>
                                </td>
                                <td>$ <?php echo number_format($it['Precio_Unitario'],2,',','.'); ?></td>
                                <td>$ <?php echo number_format($subtotal,2,',','.'); ?></td>
                                <td>
                                    <a href="eliminar_carrito.php?id_opcion=<?php echo $id; ?>" class="btn-eliminar">Eliminar</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </form>

            <div style="margin-top:20px; max-width:900px;">
                <p><strong>Subtotal: </strong>$ <?php echo number_format($total,2,',','.'); ?></p>

                <!-- Aquí calculamos envío de manera simple; lo puedes reemplazar por más lógica -->
                <?php
                // Ejemplo: envío fijo según provincia (se pedirá provincia en finalizar_compra)
                $envio_estimado = 0; // se recalculará al finalizar
                ?>
                <p><strong>Envío (estimado):</strong> se calculará al confirmar la dirección.</p>

                <form method="get" action="direccion.php" style="display:inline;">
                    <button type="submit">Pagar</button>
                </form>
            </div>
        <?php endif; ?>
    </main>

    <script>
    // Pequeña mejora: manejar click actualizar sin recargar toda la página (opcional)
    document.addEventListener('click', function(e){
        if (e.target.matches('.btn-actualizar')) {
            const id = e.target.getAttribute('data-id');
            const input = document.querySelector('input[data-id="'+id+'"]');
            const cantidad = parseInt(input.value) || 1;
            const fd = new FormData();
            fd.append('id_opcion', id);
            fd.append('cantidad', cantidad);

            fetch('actualizar_carrito.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    if (res.success) location.reload();
                    else alert(res.message || 'Error al actualizar');
                }).catch(()=> alert('Error de red'));
        }
    });
    </script>
</body>
</html>
