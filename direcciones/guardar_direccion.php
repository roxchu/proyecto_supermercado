<?php
session_start();
require '../carrito/db.php'; // conexión $pdo

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_cliente = $_SESSION['user_id'] ?? $_SESSION['id_usuario'] ?? $_SESSION['dni'] ?? null;

    if (!$id_cliente) {
        die("No hay usuario autenticado.");
    }

    $stmt = $pdo->prepare("INSERT INTO direcciones 
        (id_cliente, nombre_direccion, calle_numero, piso_depto, Ciudad, Provincia, Codigo_postal, Referencia)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

    $ok = $stmt->execute([
        $id_cliente,
        $_POST['nombre_direccion'],
        $_POST['calle_numero'],
        $_POST['piso_depto'] ?? null,
        $_POST['Ciudad'],
        $_POST['Provincia'],
        $_POST['Codigo_postal'],
        $_POST['Referencia'] ?? null
    ]);

    if ($ok) {
        header("Location: ../pago/metodo_pago.php");
        exit;
    } else {
        echo "❌ Error al guardar la dirección.";
    }
}
?>
