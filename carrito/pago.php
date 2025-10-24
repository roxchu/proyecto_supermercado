<?php
// carrito/pago.php
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $direccion = $_POST['direccion'];
    $ciudad = $_POST['ciudad'];
    $envio = [
        'CABA' => 1000,
        'GBA' => 1500,
        'Interior' => 2500
    ][$ciudad] ?? 2000;

    // Obtener carrito pendiente (DNI_Cliente + Estado 'Pendiente')
    $stmt = $pdo->prepare("SELECT Id_Carrito FROM carrito WHERE DNI_Cliente = ? AND Estado = 'Pendiente' LIMIT 1");
    $stmt->execute([$id_usuario]);
    $carrito = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($carrito) {
        // calcular total desde detalle_carrito
        $stmtTot = $pdo->prepare("SELECT SUM(Cantidad * Precio_Unitario_Momento) AS productos_total FROM detalle_carrito WHERE Id_Carrito = ?");
        $stmtTot->execute([$carrito['Id_Carrito']]);
        $r = $stmtTot->fetch(PDO::FETCH_ASSOC);
        $productos_total = floatval($r['productos_total'] ?? 0);
        $total_final = $productos_total + $envio;

        $pdo->prepare("UPDATE carrito SET Estado = 'Pagado', Id_Direccion = ?, Costo_Envio = ?, Total_Final = ? WHERE Id_Carrito = ?")
            ->execute([$direccion_id ?? null, $envio, $total_final, $carrito['Id_Carrito']]);

        echo "<p>Compra confirmada 🛒. Tu pedido está en camino.</p>";
    } else {
        echo "<p>No hay carrito pendiente.</p>";
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pago</title>
</head>
<body>
<h2>Dirección de Envío</h2>
<form method="POST">
    <input type="text" name="direccion" placeholder="Calle y número" required><br>
    <select name="ciudad" required>
        <option value="CABA">CABA</option>
        <option value="GBA">GBA</option>
        <option value="Interior">Interior</option>
    </select><br>
    <button type="submit">Confirmar Compra</button>
</form>
</body>
</html>
