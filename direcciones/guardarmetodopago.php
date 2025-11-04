<?php
session_start();
require '../carrito/db.php'; // Asegúrate que la ruta a tu conexión $pdo sea correcta

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Busca el ID del usuario en la sesión (misma lógica que en el otro archivo)
    $id_usuario = $_SESSION['user_id'] ?? $_SESSION['id_usuario'] ?? $_SESSION['dni'] ?? null;

    if (!$id_usuario) {
        die("Error: No se ha podido identificar al usuario. Por favor, inicie sesión de nuevo.");
    }

    // Campos que no son de tarjeta (siempre presentes)
    $tipo_metodo = $_POST['tipo_metodo'];
    $alias = $_POST['alias'];

    // Campos de tarjeta (pueden estar vacíos o no existir si no es tarjeta)
    // Usamos !empty() para convertir "" (string vacío) en null, lo cual es mejor para la base de datos
    $nombre_titular = !empty($_POST['nombre_titular']) ? $_POST['nombre_titular'] : null;
    $numero_tarjeta = !empty($_POST['numero_tarjeta']) ? $_POST['numero_tarjeta'] : null;
    $vencimiento = !empty($_POST['vencimiento']) ? $_POST['vencimiento'] : null;

    // Prepara la consulta para la tabla 'metodo_pago_usuario'
    $stmt = $pdo->prepare("INSERT INTO metodo_pago_usuario 
        (id_usuario, tipo_metodo, nombre_titular, numero_tarjeta, vencimiento, alias)
        VALUES (?, ?, ?, ?, ?, ?)");

    // Ejecuta la consulta
    $ok = $stmt->execute([
        $id_usuario,
        $tipo_metodo,
        $nombre_titular,
        $numero_tarjeta,
        $vencimiento,
        $alias
    ]);

    if ($ok) {
        // Redirige al inicio o a la página de "compra finalizada" (ajusta esto)
        header("Location: ../index.html?pago=exito"); 
        exit;
    } else {
        echo "❌ Error al guardar el método de pago.";
        // Para depurar, podrías imprimir: print_r($stmt->errorInfo());
    }
}
?>