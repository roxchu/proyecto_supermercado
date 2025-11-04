<?php
session_start();
require '../carrito/db.php'; // conexión $pdo

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Busca el ID del usuario en la sesión
    $id_cliente = $_SESSION['user_id'] ?? $_SESSION['id_usuario'] ?? $_SESSION['dni'] ?? null;

    if (!$id_cliente) {
        die("No hay usuario autenticado.");
    }

    // Prepara la consulta para insertar la dirección
    $stmt = $pdo->prepare("INSERT INTO direcciones 
        (id_cliente, nombre_direccion, calle_numero, piso_depto, Ciudad, Provincia, Codigo_postal, Referencia)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

    // Ejecuta la consulta con los datos del formulario
    $ok = $stmt->execute([
        $id_cliente,
        $_POST['nombre_direccion'],
        $_POST['calle_numero'],
        $_POST['piso_depto'] ?? null, // Usa null si está vacío
        $_POST['Ciudad'],
        $_POST['Provincia'],
        $_POST['Codigo_postal'],
        $_POST['Referencia'] ?? null // Usa null si está vacío
    ]);

    if ($ok) {
        // Redirección original que especificaste
        header("Location: ../pago/metodo_pago.php");
        exit;
    } else {
        echo "❌ Error al guardar la dirección.";
    }
}
?>
