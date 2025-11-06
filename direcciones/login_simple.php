<?php
session_start();

echo "<h2>Sistema de Login Simplificado</h2>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dni = $_POST['dni'] ?? '';
    
    if ($dni) {
        // Buscar usuario por DNI
        require_once '../carrito/db.php';
        
        try {
            $stmt = $pdo->prepare("SELECT * FROM usuario WHERE DNI = ?");
            $stmt->execute([$dni]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($usuario) {
                $_SESSION['user_id'] = $usuario['id_usuario'];
                $_SESSION['logged_in'] = true;
                $_SESSION['rol'] = 'client';
                $_SESSION['nombre'] = $usuario['nombre_usuario'];
                $_SESSION['dni'] = $dni;
                
                echo "<div style='color: green; margin: 10px 0;'>✅ Login exitoso como: {$usuario['nombre_usuario']} (ID: {$usuario['id_usuario']})</div>";
                
                // Limpiar carrito y agregar productos demo para este usuario
                $stmt = $pdo->prepare("DELETE FROM carrito WHERE id_usuario = ?");
                $stmt->execute([$usuario['id_usuario']]);
                
                // Agregar productos diferentes según el usuario
                $productos = [];
                if ($usuario['id_usuario'] == 1) {
                    $productos = [
                        ['id' => 1, 'precio' => 2500.00, 'cantidad' => 1], // Manzanas
                        ['id' => 4, 'precio' => 1850.00, 'cantidad' => 2]  // Coca Cola
                    ];
                } else if ($usuario['id_usuario'] == 2) {
                    $productos = [
                        ['id' => 2, 'precio' => 8900.00, 'cantidad' => 1], // Pollo
                        ['id' => 3, 'precio' => 1250.00, 'cantidad' => 3]  // Leche
                    ];
                } else {
                    $productos = [
                        ['id' => 5, 'precio' => 1680.00, 'cantidad' => 1], // Arroz
                        ['id' => 6, 'precio' => 2200.00, 'cantidad' => 2]  // Pan
                    ];
                }
                
                foreach ($productos as $producto) {
                    $stmt = $pdo->prepare("
                        INSERT INTO carrito (id_usuario, Id_Producto, Precio_Unitario_Momento, Cantidad) 
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $usuario['id_usuario'],
                        $producto['id'],
                        $producto['precio'],
                        $producto['cantidad']
                    ]);
                }
                
                echo "<div style='color: blue; margin: 10px 0;'>🛒 Carrito configurado con productos personalizados</div>";
                
            } else {
                echo "<div style='color: red; margin: 10px 0;'>❌ Usuario no encontrado</div>";
            }
        } catch (Exception $e) {
            echo "<div style='color: red; margin: 10px 0;'>❌ Error: " . $e->getMessage() . "</div>";
        }
    }
}

// Mostrar estado actual
if (isset($_SESSION['user_id'])) {
    echo "<div style='background: #e7f3ff; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
    echo "<h3>👤 Usuario Actual</h3>";
    echo "<p><strong>ID:</strong> {$_SESSION['user_id']}</p>";
    echo "<p><strong>Nombre:</strong> {$_SESSION['nombre']}</p>";
    echo "<p><strong>DNI:</strong> {$_SESSION['dni']}</p>";
    echo "</div>";
    
    echo '<p><a href="direcciones.php" style="background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">🛒 Ir al Checkout</a></p>';
    echo '<p><a href="../index.html" style="background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">🏠 Ir al Inicio</a></p>';
    echo '<p><a href="?logout=1" style="background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">🚪 Cerrar Sesión</a></p>';
} else {
    echo "<div style='background: #fff3cd; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
    echo "<h3>🔐 Iniciar Sesión</h3>";
    echo "<form method='post' style='margin-top: 10px;'>";
    echo "<p>Ingresa tu DNI:</p>";
    echo "<input type='text' name='dni' placeholder='DNI' style='padding: 8px; margin-right: 10px;' required>";
    echo "<button type='submit' style='background: #007bff; color: white; padding: 8px 15px; border: none; border-radius: 3px;'>Ingresar</button>";
    echo "</form>";
    echo "</div>";
    
    echo "<div style='background: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
    echo "<h4>💡 DNIs de prueba disponibles:</h4>";
    echo "<ul>";
    echo "<li><strong>49553570</strong> - Cliente 49.553.570 (ID: 1)</li>";
    echo "<li><strong>11111111</strong> - Admin Supremo (ID: 2)</li>";
    echo "<li><strong>22222222</strong> - Empleado General (ID: 3)</li>";
    echo "<li><strong>12763516</strong> - Cliente 12763516 (ID: 5)</li>";
    echo "</ul>";
    echo "</div>";
}

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Login - Supermercado</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        h2 { color: #333; }
        a { text-decoration: none; margin: 5px; display: inline-block; }
    </style>
</head>
<body>
</body>
</html>