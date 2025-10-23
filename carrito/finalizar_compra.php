<?php
// finalizar_compra.php
session_start();
require_once 'db.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.html');
    exit;
}

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) die("Usuario no identificado.");

$cart = $_SESSION['carrito'] ?? [];
if (empty($cart)) die("El carrito está vacío.");

$pdo->beginTransaction();

try {
    // obtener id_cliente
    $stmt = $pdo->prepare("SELECT id_cliente FROM cliente WHERE id_cliente = ? LIMIT 1");
    $stmt->execute([$user_id]);
    $cliente = $stmt->fetch();
    if (!$cliente) throw new Exception("Perfil de cliente no encontrado.");
    $id_cliente = $cliente['id_cliente'];

    // Si se eligió dirección existente
    $id_direccion = !empty($_POST['id_direccion']) ? intval($_POST['id_direccion']) : null;

    if (!$id_direccion) {
        // crear nueva dirección
        $nombre_direccion = trim($_POST['nombre_direccion'] ?? '');
        $calle_numero = trim($_POST['calle_numero'] ?? '');
        $ciudad = trim($_POST['ciudad'] ?? '');
        $provincia = trim($_POST['provincia'] ?? '');
        $codigo_postal = trim($_POST['codigo_postal'] ?? '');
        $referencia = trim($_POST['referencia'] ?? '');

        if (empty($calle_numero) || empty($ciudad) || empty($provincia)) {
            throw new Exception("Completa calle, ciudad y provincia para crear la dirección.");
        }

        $stmt = $pdo->prepare("INSERT INTO direcciones (id_cliente, nombre_direccion, calle_numero, ciudad, provincia, codigo_postal, referencia)
                               VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$id_cliente, $nombre_direccion, $calle_numero, $ciudad, $provincia, $codigo_postal, $referencia]);
        $id_direccion = $pdo->lastInsertId();
    } else {
        // validar que la dirección seleccionada pertenece al cliente
        $stmt = $pdo->prepare("SELECT id_direccion FROM direcciones WHERE id_direccion = ? AND id_cliente = ? LIMIT 1");
        $stmt->execute([$id_direccion, $id_cliente]);
        if (!$stmt->fetch()) throw new Exception("Dirección inválida.");
    }

    // Calcular total de productos
    $subtotal = 0;
    foreach ($cart as $it) $subtotal += $it['Precio_Unitario'] * $it['cantidad'];

    // Calcular costo de envío (ejemplo: 0 si provincia == 'CABA' o tarifa fija)
    // Aquí podés implementar reglas más complejas.
    $stmt = $pdo->prepare("SELECT provincia FROM direcciones WHERE id_direccion = ? LIMIT 1");
    $stmt->execute([$id_direccion]);
    $provRow = $stmt->fetch();
    $provincia = $provRow['provincia'] ?? '';

    // Ejemplo de reglas:
    if (mb_strtolower($provincia) === 'caba' || mb_strtolower($provincia) === 'ciudad autónoma de buenos aires') {
        $costo_envio = 500;
    } else {
        $costo_envio = 1200;
    }

    $total_final = $subtotal + $costo_envio;

    // Insertar carrito (tabla carrito)
    // Campos: Id_Carrito , DNI, Id_Direccion, Fecha_Agregado, Estado, Costo_Envio, Total_Final
    // En tus scripts usaste DNI y id_usuario: pero registramos con DNI NULL si no lo tenemos
    // Vamos a intentar traer DNI desde tabla usuario si existe
    $stmt = $pdo->prepare("SELECT DNI FROM usuario WHERE id_usuario = ? LIMIT 1");
    $stmt->execute([$user_id]);
    $u = $stmt->fetch();
    $dniUsuario = $u['DNI'] ?? null;

    $estado = 'Pagado'; // o 'Pendiente' si procesas pago por separado
    $stmt = $pdo->prepare("INSERT INTO carrito (DNI, Id_Direccion, Fecha_Agregado, Estado, Costo_Envio, Total_Final)
                           VALUES (?, ?, NOW(), ?, ?, ?)");
    $stmt->execute([$dniUsuario, $id_direccion, $estado, $costo_envio, $total_final]);
    $id_carrito = $pdo->lastInsertId();

    // Insertar detalle_carrito y disminuir stock en opcion_producto
    $stmtDetalle = $pdo->prepare("INSERT INTO detalle_carrito (Id_Carrito, Id_Opcion_Producto, Cantidad, Precio_Unitario)
                                  VALUES (?, ?, ?, ?)");
    $stmtStock = $pdo->prepare("UPDATE opcion_producto SET Stock = Stock - ? WHERE Id_Opcion_Producto = ? AND Stock >= ?");

    foreach ($cart as $it) {
        $id_op = $it['Id_Opcion_Producto'];
        $cantidad = $it['cantidad'];
        $precio = $it['Precio_Unitario'];

        $stmtDetalle->execute([$id_carrito, $id_op, $cantidad, $precio]);

        // intentar decrementar stock (asegurar que quede >= 0)
        $stmtStock->execute([$cantidad, $id_op, $cantidad]);
        if ($stmtStock->rowCount() === 0) {
            // si no se pudo decrementar (stock insuficiente), revertir
            throw new Exception("Stock insuficiente para la opción de producto ID: $id_op. Compra anulada.");
        }
    }

    $pdo->commit();

    // vaciar carrito de sesión
    unset($_SESSION['carrito']);

    // mostrar resúmen
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head><meta charset="utf-8"><title>Compra realizada</title><link rel="stylesheet" href="styles.css"></head>
    <body>
        <main style="padding:20px;max-width:900px;">
            <h1>✅ Compra realizada con éxito</h1>
            <p>Resumen de la compra:</p>
            <ul>
                <li>Subtotal: $ <?php echo number_format($subtotal,2,',','.'); ?></li>
                <li>Envío: $ <?php echo number_format($costo_envio,2,',','.'); ?></li>
                <li><strong>Total final: $ <?php echo number_format($total_final,2,',','.'); ?></strong></li>
            </ul>
            <p>Tu número de pedido es: <strong>#<?php echo $id_carrito; ?></strong></p>
            <a href="index.html">Volver al inicio</a>
        </main>
    </body>
    </html>
    <?php

} catch (Exception $e) {
    $pdo->rollBack();
    // Mostrar error amigable
    http_response_code(500);
    echo "<h2>Error al procesar la compra</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<a href='carrito.php'>Volver al carrito</a>";
}
