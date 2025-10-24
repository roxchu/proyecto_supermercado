<?php
// direccion.php
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.html');
    exit;
}

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    die("Usuario no identificado.");
}

// Obtener id_cliente en tabla cliente (en tu registro guardaste id_cliente = id_usuario)
$stmt = $pdo->prepare("SELECT id_cliente FROM cliente WHERE id_cliente = ? LIMIT 1");
$stmt->execute([$user_id]);
$cliente = $stmt->fetch();
if (!$cliente) {
    // Si no existe registro cliente, puedes redirigir a completar perfil
    die("No se encontró perfil de cliente. Contacta al administrador.");
}
$id_cliente = $cliente['id_cliente'];

// Obtener direcciones del cliente
$stmt = $pdo->prepare("SELECT * FROM direcciones WHERE id_cliente = ? ORDER BY id_direccion DESC");
$stmt->execute([$id_cliente]);
$direcciones = $stmt->fetchAll();

// calcular subtotal desde carrito en DB
$stmt = $pdo->prepare("SELECT c.Id_Carrito FROM carrito c WHERE c.Id_Usuario = ? AND c.estado = 'pendiente' LIMIT 1");
$stmt->execute([$user_id]);
$c = $stmt->fetch(PDO::FETCH_ASSOC);
$subtotal = 0;
if ($c) {
    $stmt = $pdo->prepare("SELECT SUM(dc.Cantidad * dc.Precio_Unitario) AS subtotal FROM detalle_carrito dc WHERE dc.Id_Carrito = ?");
    $stmt->execute([$c['Id_Carrito']]);
    $s = $stmt->fetch(PDO::FETCH_ASSOC);
    $subtotal = floatval($s['subtotal'] ?? 0);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Dirección y Pago</title>
    <link rel="stylesheet" href="../styles.css">
</head>
<body>
    <header><a href="carrito.php">← Volver</a><h1>Dirección de envío</h1></header>

    <main style="padding:20px; max-width:900px;">
        <?php if ($subtotal <= 0): ?>
            <p>Tu carrito está vacío. <a href="../index.html">Seguir comprando</a></p>
            <?php exit; ?>
        <?php endif; ?>

        <h3>1) Elegir dirección existente</h3>
        <form method="post" action="finalizar_compra.php" id="form-finalizar">
            <label for="id_direccion">Seleccionar dirección:</label>
            <select name="id_direccion" id="id_direccion">
                <option value="">-- Seleccionar --</option>
                <?php foreach ($direcciones as $d): ?>
                    <option value="<?php echo $d['id_direccion']; ?>">
                        <?php echo htmlspecialchars($d['nombre_direccion'] . ' — ' . $d['calle_numero'] . ' • ' . $d['ciudad']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <h3>2) O crear una nueva dirección</h3>
            <p>Si elige una dirección existente, los campos nuevos se ignorarán.</p>

            <label>Nombre dirección</label>
            <input type="text" name="nombre_direccion" placeholder="Casa, Trabajo..." >

            <label>Calle y número</label>
            <input type="text" name="calle_numero" placeholder="Av. Falsa 123" >

            <label>Ciudad</label>
            <input type="text" name="ciudad" placeholder="Ciudad" >

            <label>Provincia</label>
            <input type="text" name="provincia" placeholder="Provincia" >

            <label>Código postal</label>
            <input type="text" name="codigo_postal" placeholder="0000" >

            <label>Referencia</label>
            <input type="text" name="referencia" placeholder="Piso, puerta, cerca de..." >

            <h3>Resumen</h3>
            <p>Subtotal productos: $ <?php echo number_format($subtotal,2,',','.'); ?></p>
            <p>El costo de envío se calculará según la provincia seleccionada (luego se muestra la totalización).</p>

            <button type="submit">Confirmar y pagar</button>
        </form>
    </main>
</body>
</html>
